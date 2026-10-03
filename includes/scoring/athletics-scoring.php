<?php
/**
 * SportsHub - Athletics Track & Field Scoring Adapter Engine
 */
require_once __DIR__ . '/scoring-engine.php';

class AthleticsScoringAdapter extends SportScoringAdapter {
    public function getInitialState($matchData) {
        return [
            'event_name'   => $matchData['sport_name'] ?? '100m Final',
            'heat'         => 'Heat 1',
            'participants' => [],
            'status'       => $matchData['status'] ?? 'scheduled'
        ];
    }

    public function processEvent($state, $eventType, $eventValue = 0, $eventData = []) {
        $eventType = strtolower($eventType);
        if ($eventType === 'record_result') {
            $state['participants'][] = [
                'athlete'  => $eventData['athlete'] ?? 'Athlete',
                'dept'     => $eventData['dept'] ?? 'CSE',
                'time_sec' => $eventValue,
                'position' => $eventData['position'] ?? 1
            ];
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
