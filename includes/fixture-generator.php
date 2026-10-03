<?php
/**
 * SportsHub - Fixture Generator Engine & Conflict Detection Service
 * Supports Round-Robin (Single & Double), Knockout (with BYE handling), Group Stage + Knockout.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/tournament-functions.php';

/**
 * Calculate Round-Robin match metrics mathematically
 */
function calculateRoundRobinMatches($teamsCount, $isDouble = false) {
    $n = intval($teamsCount);
    if ($n < 2) return ['matches_per_team' => 0, 'total_matches' => 0];

    $matchesPerTeam = $isDouble ? 2 * ($n - 1) : ($n - 1);
    $totalMatches   = $isDouble ? ($n * ($n - 1)) : intval(($n * ($n - 1)) / 2);

    return [
        'teams_count'      => $n,
        'matches_per_team' => $matchesPerTeam,
        'total_matches'    => $totalMatches,
        'rounds_count'     => ($n % 2 === 0) ? ($n - 1) * ($isDouble ? 2 : 1) : $n * ($isDouble ? 2 : 1)
    ];
}

/**
 * Conflict Detection: Venue Overlapping Match Check
 */
function checkVenueConflict($venueId, $scheduledDate, $startTime, $endTime, $excludeMatchId = 0) {
    if (!$venueId || empty($scheduledDate) || empty($startTime)) return false;

    $db = getDB();
    if ($db->getConnection()) {
        $sql = "
            SELECT id, scheduled_date, scheduled_time 
            FROM matches 
            WHERE venue_id = :venue_id 
              AND scheduled_date = :sdate
              AND status NOT IN ('cancelled')
              AND id != :exclude_id
              AND (scheduled_time >= :stime AND scheduled_time <= :etime)
        ";
        $params = [
            ':venue_id'   => intval($venueId),
            ':sdate'      => $scheduledDate,
            ':stime'      => $startTime,
            ':etime'      => $endTime,
            ':exclude_id' => intval($excludeMatchId)
        ];
        $conflict = fetchOne($sql, $params);
        return $conflict ? true : false;
    }
    return false;
}

/**
 * Conflict Detection: Team Playing Overlapping Matches Check
 */
function checkTeamConflict($teamAId, $teamBId, $scheduledDate, $startTime, $endTime, $excludeMatchId = 0) {
    if (empty($scheduledDate) || empty($startTime)) return false;

    $db = getDB();
    if ($db->getConnection()) {
        $sql = "
            SELECT id 
            FROM matches 
            WHERE scheduled_date = :sdate
              AND status NOT IN ('cancelled')
              AND id != :exclude_id
              AND (team_a_id = :ta1 OR team_a_id = :tb1 OR team_b_id = :ta2 OR team_b_id = :tb2)
              AND (scheduled_time >= :stime AND scheduled_time <= :etime)
        ";
        $params = [
            ':sdate'      => $scheduledDate,
            ':stime'      => $startTime,
            ':etime'      => $endTime,
            ':ta1'        => intval($teamAId),
            ':tb1'        => intval($teamBId),
            ':ta2'        => intval($teamAId),
            ':tb2'        => intval($teamBId),
            ':exclude_id' => intval($excludeMatchId)
        ];
        $conflict = fetchOne($sql, $params);
        return $conflict ? true : false;
    }
    return false;
}

/**
 * Conflict Detection: Official Assigned Overlapping Matches Check
 */
function checkOfficialConflict($officialId, $scheduledDate, $startTime, $endTime, $excludeMatchId = 0) {
    if (!$officialId || empty($scheduledDate) || empty($startTime)) return false;

    $db = getDB();
    if ($db->getConnection()) {
        $sql = "
            SELECT id 
            FROM matches 
            WHERE official_id = :off_id 
              AND scheduled_date = :sdate
              AND status NOT IN ('cancelled')
              AND id != :exclude_id
              AND (scheduled_time >= :stime AND scheduled_time <= :etime)
        ";
        $params = [
            ':off_id'     => intval($officialId),
            ':sdate'      => $scheduledDate,
            ':stime'      => $startTime,
            ':etime'      => $endTime,
            ':exclude_id' => intval($excludeMatchId)
        ];
        $conflict = fetchOne($sql, $params);
        return $conflict ? true : false;
    }
    return false;
}

/**
 * Generate Round Robin Fixtures (Single & Double) with BYE Handling
 */
function generateRoundRobinFixtures($tournamentId, $isDouble = false, $customRoundName = 'League') {
    $tournament = getTournamentById($tournamentId);
    if (!$tournament) return ['success' => false, 'error' => 'Tournament not found.'];

    $db = getDB();
    $pdo = $db->getConnection();

    $teams = getTournamentApprovedTeams($tournamentId);
    if (count($teams) < 2) {
        return ['success' => false, 'error' => 'At least 2 approved teams are required to generate fixtures.'];
    }

    // Extract Team IDs
    $teamIds = array_map(function($t) { return intval($t['team_id']); }, $teams);

    // If odd number of teams, add BYE placeholder (null)
    $hasBye = false;
    if (count($teamIds) % 2 !== 0) {
        $teamIds[] = null;
        $hasBye = true;
    }

    $numTeams = count($teamIds);
    $numRounds = $numTeams - 1;
    $halfSize = $numTeams / 2;

    $fixtures = [];
    $matchNum = 1;

    // Single Round Robin Rotation Algorithm (Berger System)
    for ($round = 1; $round <= $numRounds; $round++) {
        for ($i = 0; $i < $halfSize; $i++) {
            $teamA = $teamIds[$i];
            $teamB = $teamIds[$numTeams - 1 - $i];

            // Ignore BYE match pairings
            if ($teamA !== null && $teamB !== null) {
                // Alternate home/away to balance matches
                if ($i % 2 === 0) {
                    $fixtures[] = [
                        'round_number' => $round,
                        'round_name'   => $customRoundName,
                        'match_number' => $matchNum++,
                        'team_a_id'    => $teamA,
                        'team_b_id'    => $teamB
                    ];
                } else {
                    $fixtures[] = [
                        'round_number' => $round,
                        'round_name'   => $customRoundName,
                        'match_number' => $matchNum++,
                        'team_a_id'    => $teamB,
                        'team_b_id'    => $teamA
                    ];
                }
            }
        }

        // Rotate teams (keep first team fixed)
        $firstTeam = array_shift($teamIds);
        $lastTeam  = array_pop($teamIds);
        array_unshift($teamIds, $firstTeam, $lastTeam);
    }

    // Double Round Robin: Duplicate fixtures with swapped home/away teams
    if ($isDouble) {
        $secondLeg = [];
        foreach ($fixtures as $fix) {
            $secondLeg[] = [
                'round_number' => $fix['round_number'] + $numRounds,
                'round_name'   => $customRoundName . ' (Return Leg)',
                'match_number' => $matchNum++,
                'team_a_id'    => $fix['team_b_id'],
                'team_b_id'    => $fix['team_a_id']
            ];
        }
        $fixtures = array_merge($fixtures, $secondLeg);
    }

    // MySQL Transactional Insertion
    if ($pdo) {
        try {
            $pdo->beginTransaction();

            // Clear old non-completed matches if regenerating
            $pdo->prepare("DELETE FROM matches WHERE tournament_id = :tid AND status = 'scheduled'")->execute([':tid' => $tournamentId]);

            $stmt = $pdo->prepare("
                INSERT INTO matches (tournament_id, sport_id, team_a_id, team_b_id, venue_id, scheduled_date, scheduled_time, status, round_name, round_number, match_number)
                VALUES (:tid, :sid, :ta, :tb, :vid, :sdate, :stime, 'scheduled', :rname, :rnum, :mnum)
            ");

            // Default fallback venue & date
            $defaultVenueId = fetchOne("SELECT id FROM venues WHERE status = 1 LIMIT 1")['id'] ?? 1;
            $startDate = !empty($tournament['start_date']) ? $tournament['start_date'] : date('Y-m-d');

            foreach ($fixtures as $fix) {
                $stmt->execute([
                    ':tid'   => $tournamentId,
                    ':sid'   => $tournament['sport_id'],
                    ':ta'    => $fix['team_a_id'],
                    ':tb'    => $fix['team_b_id'],
                    ':vid'   => $defaultVenueId,
                    ':sdate' => $startDate,
                    ':stime' => '10:00:00',
                    ':rname' => $fix['round_name'],
                    ':rnum'  => $fix['round_number'],
                    ':mnum'  => $fix['match_number']
                ]);
            }

            $pdo->commit();
            return ['success' => true, 'count' => count($fixtures), 'message' => count($fixtures) . ' round-robin matches generated successfully.'];
        } catch (Exception $e) {
            $pdo->rollBack();
            return ['success' => false, 'error' => 'Database transaction failed: ' . $e->getMessage()];
        }
    }

    return ['success' => true, 'count' => count($fixtures), 'message' => count($fixtures) . ' matches generated.'];
}

/**
 * Generate Knockout Tournament Fixtures with Power-of-2 & BYE Support
 */
function generateKnockoutFixtures($tournamentId) {
    $tournament = getTournamentById($tournamentId);
    if (!$tournament) return ['success' => false, 'error' => 'Tournament not found.'];

    $teams = getTournamentApprovedTeams($tournamentId);
    $n = count($teams);
    if ($n < 2) return ['success' => false, 'error' => 'At least 2 approved teams required for knockout.'];

    $db = getDB();
    $pdo = $db->getConnection();

    // Extract Team IDs
    $teamIds = array_map(function($t) { return intval($t['team_id']); }, $teams);
    shuffle($teamIds); // Randomize seeding

    // Find next power of 2
    $targetSize = 2;
    while ($targetSize < $n) {
        $targetSize *= 2;
    }

    $byesCount = $targetSize - $n;
    $firstRoundMatchesCount = ($n - $byesCount) / 2;

    $fixtures = [];
    $matchNum = 1;

    // Round 1 (First Stage matches for non-BYE teams)
    $qualifiedForRound2 = [];
    for ($i = 0; $i < $byesCount; $i++) {
        $qualifiedForRound2[] = array_shift($teamIds); // BYE teams advance directly
    }

    $roundName = ($targetSize == 8) ? 'Quarter Final' : (($targetSize == 4) ? 'Semi Final' : 'Knockout Round 1');
    for ($i = 0; $i < $firstRoundMatchesCount; $i++) {
        $teamA = array_shift($teamIds);
        $teamB = array_shift($teamIds);

        $fixtures[] = [
            'round_number' => 1,
            'round_name'   => $roundName,
            'match_number' => $matchNum++,
            'team_a_id'    => $teamA,
            'team_b_id'    => $teamB
        ];
    }

    // Insert to DB using Transaction
    if ($pdo) {
        try {
            $pdo->beginTransaction();

            $pdo->prepare("DELETE FROM matches WHERE tournament_id = :tid AND status = 'scheduled'")->execute([':tid' => $tournamentId]);

            $stmt = $pdo->prepare("
                INSERT INTO matches (tournament_id, sport_id, team_a_id, team_b_id, venue_id, scheduled_date, scheduled_time, status, round_name, round_number, match_number)
                VALUES (:tid, :sid, :ta, :tb, :vid, :sdate, :stime, 'scheduled', :rname, :rnum, :mnum)
            ");

            $defaultVenueId = fetchOne("SELECT id FROM venues WHERE status = 1 LIMIT 1")['id'] ?? 1;
            $startDate = !empty($tournament['start_date']) ? $tournament['start_date'] : date('Y-m-d');

            foreach ($fixtures as $fix) {
                $stmt->execute([
                    ':tid'   => $tournamentId,
                    ':sid'   => $tournament['sport_id'],
                    ':ta'    => $fix['team_a_id'],
                    ':tb'    => $fix['team_b_id'],
                    ':vid'   => $defaultVenueId,
                    ':sdate' => $startDate,
                    ':stime' => '10:00:00',
                    ':rname' => $fix['round_name'],
                    ':rnum'  => $fix['round_number'],
                    ':mnum'  => $fix['match_number']
                ]);
            }

            $pdo->commit();
            return ['success' => true, 'count' => count($fixtures), 'message' => count($fixtures) . ' knockout matches generated successfully.'];
        } catch (Exception $e) {
            $pdo->rollBack();
            return ['success' => false, 'error' => 'Database transaction failed: ' . $e->getMessage()];
        }
    }

    return ['success' => true, 'count' => count($fixtures)];
}

/**
 * Generate Group Stage + Knockout Fixtures
 */
function generateGroupKnockoutFixtures($tournamentId, $numGroups = 2) {
    $tournament = getTournamentById($tournamentId);
    if (!$tournament) return ['success' => false, 'error' => 'Tournament not found.'];

    $teams = getTournamentApprovedTeams($tournamentId);
    $totalTeams = count($teams);
    if ($totalTeams < 4) return ['success' => false, 'error' => 'At least 4 approved teams required for group stage.'];

    $db = getDB();
    $pdo = $db->getConnection();

    // Divide teams evenly into Group A, Group B, Group C, etc.
    $groupLetters = ['Group A', 'Group B', 'Group C', 'Group D'];
    $numGroups = min($numGroups, count($groupLetters));
    
    $groupedTeams = [];
    for ($g = 0; $g < $numGroups; $g++) {
        $groupedTeams[$groupLetters[$g]] = [];
    }

    foreach ($teams as $idx => $tm) {
        $groupIndex = $idx % $numGroups;
        $groupedTeams[$groupLetters[$groupIndex]][] = intval($tm['team_id']);
    }

    $allFixtures = [];
    $matchNum = 1;

    // Generate intra-group Round Robin matches
    foreach ($groupedTeams as $groupName => $teamIds) {
        if (count($teamIds) < 2) continue;

        if (count($teamIds) % 2 !== 0) $teamIds[] = null;
        $numT = count($teamIds);
        $rounds = $numT - 1;

        for ($r = 1; $r <= $rounds; $r++) {
            for ($i = 0; $i < $numT / 2; $i++) {
                $ta = $teamIds[$i];
                $tb = $teamIds[$numT - 1 - $i];
                if ($ta !== null && $tb !== null) {
                    $allFixtures[] = [
                        'round_number' => $r,
                        'round_name'   => $groupName,
                        'match_number' => $matchNum++,
                        'team_a_id'    => $ta,
                        'team_b_id'    => $tb
                    ];
                }
            }
            $first = array_shift($teamIds);
            $last = array_pop($teamIds);
            array_unshift($teamIds, $first, $last);
        }
    }

    if ($pdo) {
        try {
            $pdo->beginTransaction();

            $pdo->prepare("DELETE FROM matches WHERE tournament_id = :tid AND status = 'scheduled'")->execute([':tid' => $tournamentId]);

            $stmt = $pdo->prepare("
                INSERT INTO matches (tournament_id, sport_id, team_a_id, team_b_id, venue_id, scheduled_date, scheduled_time, status, round_name, round_number, match_number)
                VALUES (:tid, :sid, :ta, :tb, :vid, :sdate, :stime, 'scheduled', :rname, :rnum, :mnum)
            ");

            $defaultVenueId = fetchOne("SELECT id FROM venues WHERE status = 1 LIMIT 1")['id'] ?? 1;
            $startDate = !empty($tournament['start_date']) ? $tournament['start_date'] : date('Y-m-d');

            foreach ($allFixtures as $fix) {
                $stmt->execute([
                    ':tid'   => $tournamentId,
                    ':sid'   => $tournament['sport_id'],
                    ':ta'    => $fix['team_a_id'],
                    ':tb'    => $fix['team_b_id'],
                    ':vid'   => $defaultVenueId,
                    ':sdate' => $startDate,
                    ':stime' => '10:00:00',
                    ':rname' => $fix['round_name'],
                    ':rnum'  => $fix['round_number'],
                    ':mnum'  => $fix['match_number']
                ]);
            }

            $pdo->commit();
            return ['success' => true, 'count' => count($allFixtures), 'message' => count($allFixtures) . ' group stage matches generated successfully.'];
        } catch (Exception $e) {
            $pdo->rollBack();
            return ['success' => false, 'error' => 'Database transaction failed: ' . $e->getMessage()];
        }
    }

    return ['success' => true, 'count' => count($allFixtures)];
}

/**
 * Automatic Date, Time & Venue Scheduler Engine with Conflict Checking
 */
function assignFixtureSchedule($tournamentId, $firstDate, $dailyMatchCount = 4, $startTime = '09:00', $durationMinutes = 90, $breakMinutes = 30) {
    $db = getDB();
    if (!$db->getConnection()) return false;

    $matches = fetchAll("SELECT id, team_a_id, team_b_id FROM matches WHERE tournament_id = :tid AND status = 'scheduled' ORDER BY id ASC", [':tid' => (int)$tournamentId]);
    $venues  = fetchAll("SELECT id FROM venues WHERE status = 1");

    if (empty($matches) || empty($venues)) return false;

    $currentDate = strtotime($firstDate);
    $matchIndex  = 0;

    foreach ($matches as $m) {
        $daySlot = $matchIndex % $dailyMatchCount;
        if ($matchIndex > 0 && $daySlot === 0) {
            $currentDate = strtotime('+1 day', $currentDate); // Advance to next day
        }

        // Calculate Start & End Time for slot
        $totalSlotMinutes = ($durationMinutes + $breakMinutes) * $daySlot;
        $slotStartTimestamp = strtotime(date('Y-m-d', $currentDate) . ' ' . $startTime) + ($totalSlotMinutes * 60);
        $slotEndTimestamp   = $slotStartTimestamp + ($durationMinutes * 60);

        $scheduledDate = date('Y-m-d', $slotStartTimestamp);
        $sTimeStr      = date('H:i:s', $slotStartTimestamp);
        $eTimeStr      = date('H:i:s', $slotEndTimestamp);

        // Assign first venue without conflict
        $assignedVenueId = $venues[0]['id'];
        foreach ($venues as $v) {
            if (!checkVenueConflict($v['id'], $scheduledDate, $sTimeStr, $eTimeStr, $m['id'])) {
                $assignedVenueId = $v['id'];
                break;
            }
        }

        // Update Match in DB
        update('matches', [
            'venue_id'        => $assignedVenueId,
            'scheduled_date'  => $scheduledDate,
            'scheduled_time'  => $sTimeStr,
            'scheduled_start' => date('Y-m-d H:i:s', $slotStartTimestamp),
            'scheduled_end'   => date('Y-m-d H:i:s', $slotEndTimestamp)
        ], 'id = :id', [':id' => $m['id']]);

        $matchIndex++;
    }

    return true;
}
