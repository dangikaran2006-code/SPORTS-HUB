<?php
/**
 * SportsHub - Multi-Sport Scoring Engine Architecture
 * Defines abstract SportScoringAdapter and Factory for sport-specific engines
 */

abstract class SportScoringAdapter {
    /**
     * Get initial score state for a new match
     */
    abstract public function getInitialState($matchData);

    /**
     * Process a new scoring event and update live match state
     */
    abstract public function processEvent($currentState, $eventType, $eventValue = 0, $eventData = []);

    /**
     * Undo last event by re-calculating state from event stream
     */
    abstract public function recalculateState($initialState, $eventsList);

    /**
     * Calculate live metrics (e.g. CRR, RRR, Target, Need Runs)
     */
    abstract public function calculateMetrics($currentState);
}

class ScoringEngineFactory {
    /**
     * Resolve scoring adapter for given sport code/name
     */
    public static function getAdapter($sportCode) {
        $sportCode = strtolower(trim($sportCode));

        switch ($sportCode) {
            case 'cricket':
                require_once __DIR__ . '/cricket-scoring.php';
                return new CricketScoringAdapter();
            case 'football':
                require_once __DIR__ . '/football-scoring.php';
                return new FootballScoringAdapter();
            case 'kabaddi':
                require_once __DIR__ . '/kabaddi-scoring.php';
                return new KabaddiScoringAdapter();
            case 'basketball':
                require_once __DIR__ . '/basketball-scoring.php';
                return new BasketballScoringAdapter();
            case 'volleyball':
                require_once __DIR__ . '/volleyball-scoring.php';
                return new VolleyballScoringAdapter();
            case 'badminton':
                require_once __DIR__ . '/badminton-scoring.php';
                return new BadmintonScoringAdapter();
            case 'tennis':
                require_once __DIR__ . '/tennis-scoring.php';
                return new TennisScoringAdapter();
            case 'table-tennis':
            case 'table_tennis':
                require_once __DIR__ . '/table-tennis-scoring.php';
                return new TableTennisScoringAdapter();
            case 'hockey':
                require_once __DIR__ . '/hockey-scoring.php';
                return new HockeyScoringAdapter();
            case 'athletics':
                require_once __DIR__ . '/athletics-scoring.php';
                return new AthleticsScoringAdapter();
            case 'chess':
                require_once __DIR__ . '/chess-scoring.php';
                return new ChessScoringAdapter();
            default:
                require_once __DIR__ . '/cricket-scoring.php';
                return new CricketScoringAdapter();
        }
    }
}
