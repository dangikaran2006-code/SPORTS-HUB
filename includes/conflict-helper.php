<?php
/**
 * SportsHub - Scheduling Conflict Prevention Engine
 * Validates Venue booking time overlaps & Department participant time overlaps
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/match-functions.php';

class ConflictService {

    /**
     * Check if a Venue is occupied during scheduled time slot
     */
    public static function checkVenueOverlap($venueId, $scheduledDate, $startTime, $endTime, $excludeMatchId = 0) {
        if (empty($venueId)) return false;
        return checkVenueConflict($venueId, $scheduledDate, $startTime, $endTime, $excludeMatchId);
    }

    /**
     * Check if a Department / Team has a scheduling overlap
     */
    public static function checkDepartmentOverlap($teamAId, $teamBId, $scheduledDate, $startTime, $endTime, $excludeMatchId = 0) {
        return checkTeamConflict($teamAId, $teamBId, $scheduledDate, $startTime, $endTime, $excludeMatchId);
    }

    /**
     * Full Validation Helper before Match / Event Scheduling
     */
    public static function validateSchedule($data) {
        $venueId    = !empty($data['venue_id']) ? (int)$data['venue_id'] : null;
        $teamAId    = (int)($data['team_a_id'] ?? 0);
        $teamBId    = (int)($data['team_b_id'] ?? 0);
        $sDate      = $data['scheduled_date'] ?? '';
        $sTime      = $data['scheduled_time'] ?? '10:00:00';
        $duration   = intval($data['duration_minutes'] ?? 90);
        $excludeId  = intval($data['match_id'] ?? 0);

        if (empty($sDate) || empty($sTime)) {
            return ['valid' => false, 'error' => 'Scheduled Date and Time are required.'];
        }

        // Compute end time
        $startTs = strtotime($sDate . ' ' . $sTime);
        $endTs   = $startTs + ($duration * 60);
        $eTime   = date('H:i:s', $endTs);

        // 1. Check Venue Overlap
        if ($venueId && self::checkVenueOverlap($venueId, $sDate, $sTime, $eTime, $excludeId)) {
            return ['valid' => false, 'error' => 'Venue is already booked during this time slot. Please choose another venue or time.'];
        }

        // 2. Check Department Overlap
        if ($teamAId && $teamBId && self::checkDepartmentOverlap($teamAId, $teamBId, $sDate, $sTime, $eTime, $excludeId)) {
            return ['valid' => false, 'error' => 'One of the competing departments has another match scheduled during this overlapping time slot.'];
        }

        return ['valid' => true];
    }
}
