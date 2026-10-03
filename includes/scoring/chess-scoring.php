<?php
/**
 * SportsHub - Chess Result Adapter Engine
 */
require_once __DIR__ . '/scoring-engine.php';

class ChessScoringAdapter extends SportScoringAdapter {
    public function getInitialState($matchData) {
        return [
            'round'        => 'Round 1',
            'board'        => 1,
            'result'       => 'Pending',
            'winner'       => null,
            'status'       => $matchData['status'] ?? 'scheduled'
        ];
    }

    public function processEvent($state, $eventType, $eventValue = 0, $eventData = []) {
        $eventType = strtolower($eventType);
        if ($eventType === 'game_result') {
            $state['result'] = $eventData['outcome'] ?? 'Win';
            $state['winner'] = $eventData['winner'] ?? 'Player A';
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
