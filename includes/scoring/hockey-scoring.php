<?php
/**
 * SportsHub - Hockey Scoring Adapter Engine
 */
require_once __DIR__ . '/scoring-engine.php';

class HockeyScoringAdapter extends SportScoringAdapter {
    public function getInitialState($matchData) {
        return [
            'period'       => 'Quarter 1',
            'team_a_score' => 0,
            'team_b_score' => 0,
            'events'       => [],
            'status'       => $matchData['status'] ?? 'scheduled'
        ];
    }

    public function processEvent($state, $eventType, $eventValue = 0, $eventData = []) {
        $eventType = strtolower($eventType);
        if ($eventType === 'goal') {
            $team = $eventData['team'] ?? 'team_a';
            if ($team === 'team_a') {
                $state['team_a_score'] += 1;
            } else {
                $state['team_b_score'] += 1;
            }
        }
        return $state;
    }

    public function recalculateState($initialState, $eventsList) {
        $state = $initialState;
        foreach ($eventsList as $ev) {
            $state = $this->processEvent($state, $ev['event_type'], $ev['event_value'], $ev);
        }
        return $state;
    }

    public function calculateMetrics($state) {
        return $state;
    }
}
