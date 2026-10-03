<?php
/**
 * SportsHub - Cricket Scoring Rule Adapter
 * Complete Cricket Scoring Logic: Runs, Extras, Wickets, Overs, Striker/Bowler tracking, NRR & Target Calculations
 */

require_once __DIR__ . '/scoring-engine.php';

class CricketScoringAdapter extends SportScoringAdapter {

    public function getInitialState($matchData) {
        $teamAId = $matchData['team_a_id'] ?? 1;
        $teamAName = $matchData['team_a_name'] ?? 'Team A';
        $teamBId = $matchData['team_b_id'] ?? 2;
        $teamBName = $matchData['team_b_name'] ?? 'Team B';

        return [
            'current_innings'   => 1, // 1 or 2
            'max_overs'         => 20.0,
            'max_wickets'       => 10,
            'innings_1' => [
                'team_id'       => $teamAId,
                'team_name'     => $teamAName,
                'runs'          => 0,
                'wickets'       => 0,
                'legal_balls'   => 0,
                'extras'        => 0,
                'completed'     => false,
            ],
            'innings_2' => [
                'team_id'       => $teamBId,
                'team_name'     => $teamBName,
                'runs'          => 0,
                'wickets'       => 0,
                'legal_balls'   => 0,
                'extras'        => 0,
                'completed'     => false,
            ],
            'striker'           => 'Rohit Sharma',
            'non_striker'       => 'Virat Kohli',
            'bowler'            => 'Jasprit Bumrah',
            'recent_balls'      => [],
            'status'            => $matchData['status'] ?? 'scheduled',
            'target'            => null,
            'result_summary'    => '',
        ];
    }

    public function processEvent($state, $eventType, $eventValue = 0, $eventData = []) {
        $innKey = 'innings_' . ($state['current_innings'] ?? 1);
        $inn = &$state[$innKey];

        if ($inn['completed'] || $state['status'] === 'completed') {
            return $state;
        }

        $ballLabel = '';
        $isLegalBall = true;
        $strikerSwapNeeded = false;

        switch ($eventType) {
            case 'run':
            case 'runs':
                $runsScored = (int)$eventValue;
                $inn['runs'] += $runsScored;
                $inn['legal_balls']++;
                $ballLabel = (string)$runsScored;

                if ($runsScored % 2 !== 0) {
                    $strikerSwapNeeded = true;
                }
                break;

            case 'boundary_4':
                $inn['runs'] += 4;
                $inn['legal_balls']++;
                $ballLabel = '4';
                break;

            case 'boundary_6':
                $inn['runs'] += 6;
                $inn['legal_balls']++;
                $ballLabel = '6';
                break;

            case 'wide':
                $extraRuns = 1 + (int)$eventValue;
                $inn['runs'] += $extraRuns;
                $inn['extras'] += $extraRuns;
                $isLegalBall = false;
                $ballLabel = 'Wd' . ($eventValue > 0 ? "+$eventValue" : "");
                break;

            case 'no_ball':
                $extraRuns = 1 + (int)$eventValue;
                $inn['runs'] += $extraRuns;
                $inn['extras'] += $extraRuns;
                $isLegalBall = false;
                $ballLabel = 'NB' . ($eventValue > 0 ? "+$eventValue" : "");
                if ((int)$eventValue % 2 !== 0) {
                    $strikerSwapNeeded = true;
                }
                break;

            case 'bye':
                $byeRuns = (int)($eventValue ?: 1);
                $inn['runs'] += $byeRuns;
                $inn['extras'] += $byeRuns;
                $inn['legal_balls']++;
                $ballLabel = "B$byeRuns";
                if ($byeRuns % 2 !== 0) {
                    $strikerSwapNeeded = true;
                }
                break;

            case 'leg_bye':
                $lbRuns = (int)($eventValue ?: 1);
                $inn['runs'] += $lbRuns;
                $inn['extras'] += $lbRuns;
                $inn['legal_balls']++;
                $ballLabel = "LB$lbRuns";
                if ($lbRuns % 2 !== 0) {
                    $strikerSwapNeeded = true;
                }
                break;

            case 'wicket':
                $inn['wickets']++;
                $inn['legal_balls']++;
                $ballLabel = 'W';

                if (!empty($eventData['new_batsman'])) {
                    $state['striker'] = $eventData['new_batsman'];
                }
                break;

            case 'swap_striker':
                $strikerSwapNeeded = true;
                $isLegalBall = false;
                break;

            case 'change_bowler':
                if (!empty($eventData['bowler'])) {
                    $state['bowler'] = $eventData['bowler'];
                }
                $isLegalBall = false;
                break;

            case 'change_players':
                if (!empty($eventData['striker'])) $state['striker'] = $eventData['striker'];
                if (!empty($eventData['non_striker'])) $state['non_striker'] = $eventData['non_striker'];
                if (!empty($eventData['bowler'])) $state['bowler'] = $eventData['bowler'];
                $isLegalBall = false;
                break;

            case 'end_over':
                $strikerSwapNeeded = true;
                $isLegalBall = false;
                break;

            case 'end_innings':
                $inn['completed'] = true;
                if ($state['current_innings'] === 1) {
                    $state['current_innings'] = 2;
                    $state['target'] = $state['innings_1']['runs'] + 1;
                    // Swap Batting and Bowling roles
                    $tempTeam = $state['innings_1']['team_id'];
                    $tempName = $state['innings_1']['team_name'];
                    // Swap striker/non-striker defaults
                    $state['striker'] = 'Player 1';
                    $state['non_striker'] = 'Player 2';
                    $state['bowler'] = 'Bowler A';
                } else {
                    $state['status'] = 'completed';
                    $state = $this->determineWinner($state);
                }
                $isLegalBall = false;
                break;
        }

        // Add to recent balls log if a delivery was bowled
        if ($ballLabel !== '') {
            array_unshift($state['recent_balls'], $ballLabel);
            if (count($state['recent_balls']) > 12) {
                array_pop($state['recent_balls']);
            }
        }

        // End of Over check (6 legal deliveries)
        if ($isLegalBall && ($inn['legal_balls'] % 6 === 0) && $inn['legal_balls'] > 0) {
            $strikerSwapNeeded = true;
        }

        // Swap Striker & Non-Striker
        if ($strikerSwapNeeded) {
            $temp = $state['striker'];
            $state['striker'] = $state['non_striker'];
            $state['non_striker'] = $temp;
        }

        // Check All-Out (10 Wickets)
        if ($inn['wickets'] >= $state['max_wickets']) {
            $inn['completed'] = true;
            if ($state['current_innings'] === 1) {
                $state['current_innings'] = 2;
                $state['target'] = $state['innings_1']['runs'] + 1;
            } else {
                $state['status'] = 'completed';
                $state = $this->determineWinner($state);
            }
        }

        // Check 2nd Innings Target Achieved
        if ($state['current_innings'] === 2 && $state['target'] !== null) {
            if ($state['innings_2']['runs'] >= $state['target']) {
                $state['innings_2']['completed'] = true;
                $state['status'] = 'completed';
                $state = $this->determineWinner($state);
            }
        }

        // Check Overs Completed (e.g. 20.0 Overs = 120 legal balls)
        $maxLegalBalls = (int)($state['max_overs'] * 6);
        if ($inn['legal_balls'] >= $maxLegalBalls && !$inn['completed']) {
            $inn['completed'] = true;
            if ($state['current_innings'] === 1) {
                $state['current_innings'] = 2;
                $state['target'] = $state['innings_1']['runs'] + 1;
            } else {
                $state['status'] = 'completed';
                $state = $this->determineWinner($state);
            }
        }

        return $this->calculateMetrics($state);
    }

    public function recalculateState($initialState, $eventsList) {
        $state = $initialState;
        foreach ($eventsList as $ev) {
            $type = $ev['event_type'] ?? '';
            $val  = $ev['event_value'] ?? 0;
            $data = is_array($ev['event_data']) ? $ev['event_data'] : (json_decode($ev['event_data'] ?? '', true) ?: []);
            $state = $this->processEvent($state, $type, $val, $data);
        }
        return $state;
    }

    public function calculateMetrics($state) {
        $inn1 = $state['innings_1'];
        $inn2 = $state['innings_2'];

        $state['innings_1']['overs_formatted'] = $this->formatOvers($inn1['legal_balls']);
        $state['innings_2']['overs_formatted'] = $this->formatOvers($inn2['legal_balls']);

        $currentInn = 'innings_' . ($state['current_innings'] ?? 1);
        $activeInn = $state[$currentInn];

        $ballsBowled = $activeInn['legal_balls'];
        $oversFloat = floor($ballsBowled / 6) + (($ballsBowled % 6) / 6);
        $state['crr'] = $oversFloat > 0 ? number_format($activeInn['runs'] / $oversFloat, 2) : '0.00';

        if ($state['current_innings'] === 2 && !empty($state['target'])) {
            $needRuns = max(0, $state['target'] - $inn2['runs']);
            $maxBalls = (int)($state['max_overs'] * 6);
            $ballsLeft = max(0, $maxBalls - $inn2['legal_balls']);
            
            $state['need_runs'] = $needRuns;
            $state['balls_left'] = $ballsLeft;
            $state['rrr'] = $ballsLeft > 0 ? number_format(($needRuns / $ballsLeft) * 6, 2) : '0.00';
            
            if ($state['status'] !== 'completed') {
                $state['match_commentary'] = "{$inn2['team_name']} need {$needRuns} runs off {$ballsLeft} balls";
            }
        } else {
            $state['target'] = null;
            $state['need_runs'] = null;
            $state['balls_left'] = null;
            $state['rrr'] = null;
            if ($state['status'] !== 'completed') {
                $state['match_commentary'] = "{$inn1['team_name']} batting 1st innings (" . ($state['innings_1']['overs_formatted'] ?? '0.0') . " Overs)";
            }
        }

        return $state;
    }

    private function formatOvers($legalBalls) {
        $overs = floor($legalBalls / 6);
        $balls = $legalBalls % 6;
        return $overs . '.' . $balls;
    }

    private function determineWinner($state) {
        $inn1 = $state['innings_1'];
        $inn2 = $state['innings_2'];

        if ($inn2['runs'] > $inn1['runs']) {
            $wicketsLeft = 10 - $inn2['wickets'];
            $state['winner_team_id'] = $inn2['team_id'];
            $state['winner_name'] = $inn2['team_name'];
            $state['result_summary'] = "{$inn2['team_name']} won by {$wicketsLeft} wickets";
        } elseif ($inn1['runs'] > $inn2['runs']) {
            $margin = $inn1['runs'] - $inn2['runs'];
            $state['winner_team_id'] = $inn1['team_id'];
            $state['winner_name'] = $inn1['team_name'];
            $state['result_summary'] = "{$inn1['team_name']} won by {$margin} runs";
        } else {
            $state['winner_team_id'] = null;
            $state['winner_name'] = 'Tie / Draw';
            $state['result_summary'] = "Match Tied! Scores level ({$inn1['runs']} runs each)";
        }

        $state['match_commentary'] = $state['result_summary'];
        return $state;
    }
}
