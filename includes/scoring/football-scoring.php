<?php
/**
 * SportsHub - Football Scoring Rule Adapter Stub
 */
require_once __DIR__ . '/scoring-engine.php';

class FootballScoringAdapter extends SportScoringAdapter {
    public function getInitialState($matchData) {
        return [
            'period'      => '1st Half',
            'team_a_score'=> 0,
            'team_b_score'=> 0,
            'events'      => [],
            'status'      => $matchData['status'] ?? 'scheduled'
        ];
    }
    public function processEvent($state, $eventType, $eventValue = 0, $eventData = []) {
        return $state;
    }
    public function recalculateState($initialState, $eventsList) {
        return $initialState;
    }
    public function calculateMetrics($state) {
        return $state;
    }
}
