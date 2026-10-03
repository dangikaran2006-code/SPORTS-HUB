<?php
/**
 * SportsHub - Live Scoring Data Access & State Persistence Helper
 * Encapsulates database transactions, event logging, state calculation, and session fallback
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/match-functions.php';
require_once __DIR__ . '/statistics-helper.php';
require_once __DIR__ . '/scoring/scoring-engine.php';

class ScoringService {

    /**
     * Get full live score state for a match
     */
    public static function getLiveState($matchId) {
        $matchId = (int)$matchId;
        $match = getMatchById($matchId);
        if (!$match) return null;

        $sportCode = $match['sport_code'] ?? 'cricket';
        $adapter = ScoringEngineFactory::getAdapter($sportCode);

        // Fetch all recorded match events for this match
        $events = self::getMatchEvents($matchId);

        // Get initial state
        $initialState = $adapter->getInitialState($match);

        // Override status if match status changed in DB
        $initialState['status'] = $match['status'] ?? 'scheduled';
        $initialState['winner_team_id'] = $match['winner_team_id'] ?? null;
        $initialState['result_summary'] = $match['result_summary'] ?? '';

        // Recalculate full live state from ordered events
        $liveState = $adapter->recalculateState($initialState, $events);

        return [
            'match'      => $match,
            'live_state' => $liveState,
            'events'     => $events,
            'timestamp'  => time(),
        ];
    }

    /**
     * Get ordered list of match events from DB or Session
     */
    public static function getMatchEvents($matchId) {
        $db = getDB();
        if ($db->getConnection()) {
            $sql = "SELECT * FROM match_events WHERE match_id = :mid ORDER BY id ASC";
            $results = fetchAll($sql, [':mid' => (int)$matchId]);
            if (!empty($results)) {
                return array_map(function($ev) {
                    if (is_string($ev['event_data']) && !empty($ev['event_data'])) {
                        $ev['event_data'] = json_decode($ev['event_data'], true) ?: [];
                    }
                    return $ev;
                }, $results);
            }
        }

        // Session Fallback for Demo Mode
        if (session_status() === PHP_SESSION_NONE) session_start();
        return $_SESSION["match_events_{$matchId}"] ?? [];
    }

    /**
     * Record a new scoring event with concurrency protection & transaction safety
     */
    public static function addEvent($matchId, $eventType, $eventValue = 0, $eventData = [], $teamId = null, $playerId = null) {
        $matchId = (int)$matchId;
        $match = getMatchById($matchId);
        if (!$match) return ['success' => false, 'error' => 'Match not found.'];

        $db = getDB();
        $conn = $db->getConnection();
        $eventTime = date('H:i:s');
        $jsonExtra = !empty($eventData) ? json_encode($eventData) : null;

        if ($conn) {
            try {
                $conn->beginTransaction();

                // Lock match row for concurrency control
                $stmtLock = $conn->prepare("SELECT id, status FROM matches WHERE id = :mid FOR UPDATE");
                $stmtLock->execute([':mid' => $matchId]);
                $matchRow = $stmtLock->fetch();

                if (!$matchRow || $matchRow['status'] !== 'live') {
                    $conn->rollBack();
                    return ['success' => false, 'error' => 'Match is not currently live.'];
                }

                // Insert into match_events
                $stmtEv = $conn->prepare("
                    INSERT INTO match_events (match_id, team_id, player_id, event_type, event_value, event_data, event_time, created_at)
                    VALUES (:mid, :tid, :pid, :etype, :eval, :edata, :etime, NOW())
                ");
                $stmtEv->execute([
                    ':mid'   => $matchId,
                    ':tid'   => $teamId ?: ($match['team_a_id'] ?? 1),
                    ':pid'   => $playerId,
                    ':etype' => $eventType,
                    ':eval'  => (int)$eventValue,
                    ':edata' => $jsonExtra,
                    ':etime' => $eventTime,
                ]);

                $conn->commit();
            } catch (Exception $e) {
                if ($conn->inTransaction()) $conn->rollBack();
                error_log("Scoring Add Event DB Error: " . $e->getMessage());
            }
        } else {
            // Session Fallback
            if (session_status() === PHP_SESSION_NONE) session_start();
            if (!isset($_SESSION["match_events_{$matchId}"])) {
                $_SESSION["match_events_{$matchId}"] = [];
            }
            $_SESSION["match_events_{$matchId}"][] = [
                'id'          => time() . rand(100, 999),
                'match_id'    => $matchId,
                'team_id'     => $teamId ?: ($match['team_a_id'] ?? 1),
                'player_id'   => $playerId,
                'event_type'  => $eventType,
                'event_value' => (int)$eventValue,
                'event_data'  => $eventData,
                'event_time'  => $eventTime,
                'created_at'  => date('Y-m-d H:i:s'),
            ];
        }


        return self::getLiveState($matchId);
    }

    /**
     * Safely undo the last recorded event
     */
    public static function undoLastEvent($matchId) {
        $matchId = (int)$matchId;
        $db = getDB();
        $conn = $db->getConnection();

        if ($conn) {
            try {
                $conn->beginTransaction();

                // Fetch last event ID for this match
                $stmtLast = $conn->prepare("SELECT id FROM match_events WHERE match_id = :mid ORDER BY id DESC LIMIT 1 FOR UPDATE");
                $stmtLast->execute([':mid' => $matchId]);
                $lastEv = $stmtLast->fetch();

                if ($lastEv) {
                    $stmtDel = $conn->prepare("DELETE FROM match_events WHERE id = :id");
                    $stmtDel->execute([':id' => $lastEv['id']]);
                }

                $conn->commit();
            } catch (Exception $e) {
                if ($conn->inTransaction()) $conn->rollBack();
            }
        } else {
            if (session_status() === PHP_SESSION_NONE) session_start();
            if (!empty($_SESSION["match_events_{$matchId}"])) {
                array_pop($_SESSION["match_events_{$matchId}"]);
            }
        }

        return self::getLiveState($matchId);
    }

    /**
     * Start/Pause/Resume/Cancel/Postpone match status
     */
    public static function updateMatchStatus($matchId, $status) {
        $matchId = (int)$matchId;
        $allowedStatuses = ['scheduled', 'live', 'completed', 'postponed', 'cancelled'];
        if (!in_array($status, $allowedStatuses)) {
            return ['success' => false, 'error' => 'Invalid status parameter.'];
        }

        $db = getDB();
        if ($db->getConnection()) {
            update('matches', ['status' => $status], 'id = :id', [':id' => $matchId]);
        } else {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION["match_status_{$matchId}"] = $status;
        }

        return ['success' => true, 'message' => "Match status changed to {$status}."];
    }

    /**
     * Finish Match and record final winner & summary
     */
    public static function finishMatch($matchId, $winnerTeamId = null, $resultSummary = '') {
        $matchId = (int)$matchId;
        
        // Trigger Automatic Standings & Points Table Processing Workflow
        StatisticsService::processMatchResult($matchId, $winnerTeamId, $resultSummary);

        return self::getLiveState($matchId);
    }
}
