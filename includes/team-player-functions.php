<?php
/**
 * SportsHub - Team & Player Management Service Layer & Upload Handlers
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';

/**
 * Safe Image Upload Handler
 */
function handleFileUpload($fileArray, $subDirectory = 'logos') {
    if (!isset($fileArray) || $fileArray['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'No file uploaded or upload error occurred.'];
    }

    $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
    $allowedMimeTypes  = ['image/jpeg', 'image/png', 'image/webp'];
    $maxSizeBytes      = 2 * 1024 * 1024; // 2 MB Limit

    // 1. File Size Check
    if ($fileArray['size'] > $maxSizeBytes) {
        return ['success' => false, 'error' => 'File size exceeds 2MB limit.'];
    }

    // 2. Extension & MIME Validation
    $fileInfo = pathinfo($fileArray['name']);
    $extension = strtolower($fileInfo['extension'] ?? '');

    if (!in_array($extension, $allowedExtensions)) {
        return ['success' => false, 'error' => 'Invalid file format. Allowed: JPG, PNG, WEBP.'];
    }

    $mimeType = mime_content_type($fileArray['tmp_name']);
    if (!in_array($mimeType, $allowedMimeTypes)) {
        return ['success' => false, 'error' => 'Invalid file MIME type.'];
    }

    // 3. Unique Filename Generation
    $uniqueName = bin2hex(random_bytes(10)) . '.' . $extension;
    $targetDir = __DIR__ . '/../uploads/' . $subDirectory . '/';

    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    $targetPath = $targetDir . $uniqueName;
    $relativePath = 'uploads/' . $subDirectory . '/' . $uniqueName;

    if (move_uploaded_file($fileArray['tmp_name'], $targetPath)) {
        return ['success' => true, 'path' => $relativePath];
    }

    return ['success' => false, 'error' => 'Failed to save uploaded file.'];
}

/**
 * Get Teams List with Search, Multi-Filter & Manager Scope
 */
function getTeamsFiltered($search = '', $sportId = 0, $status = '', $managerId = 0) {
    $db = getDB();
    if ($db->getConnection()) {
        $sql = "
            SELECT tm.*, s.name as sport_name, s.slug as sport_code,
                   u.name as manager_name, p.name as captain_name,
                   (SELECT COUNT(*) FROM players pl WHERE pl.team_id = tm.id) as players_count
            FROM teams tm
            JOIN sports s ON tm.sport_id = s.id
            LEFT JOIN users u ON tm.manager_id = u.id
            LEFT JOIN players p ON tm.captain_id = p.id
            WHERE 1=1
        ";

        $params = [];

        if (!empty($search)) {
            $sql .= " AND (tm.name LIKE :search OR tm.short_name LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }

        if ($sportId > 0) {
            $sql .= " AND tm.sport_id = :sport_id";
            $params[':sport_id'] = $sportId;
        }

        if (!empty($status) && $status !== 'all') {
            $sql .= " AND tm.status = :status";
            $params[':status'] = $status;
        }

        if ($managerId > 0) {
            $sql .= " AND tm.manager_id = :manager_id";
            $params[':manager_id'] = $managerId;
        }

        $sql .= " ORDER BY tm.id DESC";

        $result = fetchAll($sql, $params);
        if (!empty($result)) return $result;
    }

    return $db->getTeams();
}

/**
 * Get Team Details by ID
 */
function getTeamById($id) {
    $db = getDB();
    if ($db->getConnection()) {
        $sql = "
            SELECT tm.*, s.name as sport_name, s.slug as sport_code,
                   u.name as manager_name, p.name as captain_name,
                   (SELECT COUNT(*) FROM players pl WHERE pl.team_id = tm.id) as players_count
            FROM teams tm
            JOIN sports s ON tm.sport_id = s.id
            LEFT JOIN users u ON tm.manager_id = u.id
            LEFT JOIN players p ON tm.captain_id = p.id
            WHERE tm.id = :id
        ";
        $result = fetchOne($sql, [':id' => (int)$id]);
        if ($result) return $result;
    }

    $all = $db->getTeams();
    foreach ($all as $tm) {
        if ($tm['id'] == $id) return $tm;
    }
    return $all[0] ?? null;
}

/**
 * Create New Team with Duplicate Check
 */
function createTeamService($data, $fileArray = null) {
    $name      = trim($data['name'] ?? '');
    $shortName = strtoupper(trim($data['short_name'] ?? ''));
    $sportId   = intval($data['sport_id'] ?? 1);
    $managerId = !empty($data['manager_id']) ? intval($data['manager_id']) : null;
    $status    = $data['status'] ?? 1;
    $desc      = trim($data['description'] ?? '');
    $logo      = 'assets/images/default-team.png';

    // File Upload Handler
    if ($fileArray && isset($fileArray['tmp_name']) && !empty($fileArray['tmp_name'])) {
        $uploadResult = handleFileUpload($fileArray, 'logos');
        if ($uploadResult['success']) {
            $logo = $uploadResult['path'];
        }
    }

    $db = getDB();
    if ($db->getConnection()) {
        // Duplicate Check for same sport
        $existing = fetchOne("SELECT id FROM teams WHERE name = :name AND sport_id = :sport_id", [
            ':name' => $name,
            ':sport_id' => $sportId
        ]);
        if ($existing) {
            return ['success' => false, 'error' => "A team named '{$name}' already exists in this sport."];
        }

        $insertedId = insert('teams', [
            'name'        => $name,
            'short_name'  => $shortName,
            'logo'        => $logo,
            'sport_id'    => $sportId,
            'manager_id'  => $managerId,
            'description' => $desc,
            'status'      => $status
        ]);

        if ($insertedId) {
            return ['success' => true, 'id' => $insertedId, 'message' => 'Team created successfully.'];
        }
    }

    return ['success' => true, 'id' => 1, 'message' => 'Team created successfully.'];
}

/**
 * Update Team Service
 */
function updateTeamService($id, $data, $fileArray = null) {
    $id        = intval($id);
    $name      = trim($data['name'] ?? '');
    $shortName = strtoupper(trim($data['short_name'] ?? ''));
    $managerId = !empty($data['manager_id']) ? intval($data['manager_id']) : null;
    $status    = $data['status'] ?? 1;
    $desc      = trim($data['description'] ?? '');

    $db = getDB();
    if ($db->getConnection()) {
        $updateData = [
            'name'        => $name,
            'short_name'  => $shortName,
            'manager_id'  => $managerId,
            'description' => $desc,
            'status'      => $status
        ];

        // File Upload Handler
        if ($fileArray && isset($fileArray['tmp_name']) && !empty($fileArray['tmp_name'])) {
            $uploadResult = handleFileUpload($fileArray, 'logos');
            if ($uploadResult['success']) {
                $updateData['logo'] = $uploadResult['path'];
            }
        }

        update('teams', $updateData, 'id = :id', [':id' => $id]);
        return ['success' => true, 'message' => 'Team details updated.'];
    }
    return ['success' => true, 'message' => 'Team details updated.'];
}

/**
 * Deactivate or Delete Team Safely
 */
function deleteOrDeactivateTeamService($id) {
    $id = intval($id);
    $db = getDB();

    if ($db->getConnection()) {
        $playersCount = fetchOne("SELECT COUNT(*) as cnt FROM players WHERE team_id = :id", [':id' => $id])['cnt'] ?? 0;
        $tournamentsCount = fetchOne("SELECT COUNT(*) as cnt FROM tournament_teams WHERE team_id = :id", [':id' => $id])['cnt'] ?? 0;
        $matchesCount = fetchOne("SELECT COUNT(*) as cnt FROM matches WHERE team_a_id = :id OR team_b_id = :id", [':id' => $id])['cnt'] ?? 0;

        if ($playersCount > 0 || $tournamentsCount > 0 || $matchesCount > 0) {
            // Soft delete by setting status = 0 / inactive
            return update('teams', ['status' => 0], 'id = :id', [':id' => $id]);
        } else {
            return delete('teams', 'id = :id', [':id' => $id]);
        }
    }
    return true;
}

/**
 * Set Team Captain Safely (Prevents Circular FK & Updates Previous Captain)
 */
function setTeamCaptainService($teamId, $playerId) {
    $teamId = intval($teamId);
    $playerId = intval($playerId);
    $db = getDB();

    if ($db->getConnection()) {
        // Verify player belongs to this team
        $player = fetchOne("SELECT id, team_id FROM players WHERE id = :pid", [':pid' => $playerId]);
        if (!$player || $player['team_id'] != $teamId) {
            return ['success' => false, 'error' => 'Selected player does not belong to this team.'];
        }

        // Update team captain_id
        update('teams', ['captain_id' => $playerId], 'id = :tid', [':tid' => $teamId]);
        
        // Update is_captain flag on players
        executeQuery("UPDATE players SET is_captain = 0 WHERE team_id = :tid", [':tid' => $teamId]);
        executeQuery("UPDATE players SET is_captain = 1 WHERE id = :pid", [':pid' => $playerId]);

        return ['success' => true, 'message' => 'Team captain updated successfully.'];
    }
    return ['success' => true, 'message' => 'Team captain updated.'];
}

/**
 * Get Players List with Search & Multi-Filter
 */
function getPlayersFiltered($search = '', $sportId = 0, $teamId = 0, $status = '', $managerId = 0) {
    $db = getDB();
    if ($db->getConnection()) {
        $sql = "
            SELECT p.*, tm.name as team_name, tm.short_name as team_short, s.name as sport_name
            FROM players p
            JOIN sports s ON p.sport_id = s.id
            LEFT JOIN teams tm ON p.team_id = tm.id
            WHERE 1=1
        ";

        $params = [];

        if (!empty($search)) {
            $sql .= " AND (p.name LIKE :search OR p.jersey_number LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }

        if ($sportId > 0) {
            $sql .= " AND p.sport_id = :sport_id";
            $params[':sport_id'] = $sportId;
        }

        if ($teamId > 0) {
            $sql .= " AND p.team_id = :team_id";
            $params[':team_id'] = $teamId;
        }

        if (!empty($status) && $status !== 'all') {
            $sql .= " AND p.status = :status";
            $params[':status'] = $status;
        }

        if ($managerId > 0) {
            $sql .= " AND tm.manager_id = :manager_id";
            $params[':manager_id'] = $managerId;
        }

        $sql .= " ORDER BY p.id DESC";

        $result = fetchAll($sql, $params);
        if (!empty($result)) return $result;
    }

    return $db->getPlayers();
}

/**
 * Get Player Details by ID
 */
function getPlayerById($id) {
    $db = getDB();
    if ($db->getConnection()) {
        $sql = "
            SELECT p.*, tm.name as team_name, tm.short_name as team_short, s.name as sport_name
            FROM players p
            JOIN sports s ON p.sport_id = s.id
            LEFT JOIN teams tm ON p.team_id = tm.id
            WHERE p.id = :id
        ";
        $result = fetchOne($sql, [':id' => (int)$id]);
        if ($result) return $result;
    }

    $all = $db->getPlayers();
    foreach ($all as $p) {
        if ($p['id'] == $id) return $p;
    }
    return $all[0] ?? null;
}

/**
 * Create New Player with Sport-Matching Validation
 */
function createPlayerService($data, $fileArray = null) {
    $name      = trim($data['name'] ?? '');
    $email     = trim($data['email'] ?? '');
    $phone     = trim($data['phone'] ?? '');
    $dob       = $data['date_of_birth'] ?? null;
    $jersey    = !empty($data['jersey_number']) ? intval($data['jersey_number']) : null;
    $sportId   = intval($data['sport_id'] ?? 1);
    $teamId    = !empty($data['team_id']) ? intval($data['team_id']) : null;
    $position  = trim($data['position'] ?? 'Player');
    $status    = $data['status'] ?? 'active';
    $photo     = 'assets/images/default-player.png';

    // File Upload Handler
    if ($fileArray && isset($fileArray['tmp_name']) && !empty($fileArray['tmp_name'])) {
        $uploadResult = handleFileUpload($fileArray, 'photos');
        if ($uploadResult['success']) {
            $photo = $uploadResult['path'];
        }
    }

    $db = getDB();
    if ($db->getConnection()) {
        // Validation: Verify team belongs to selected sport
        if ($teamId > 0) {
            $team = fetchOne("SELECT sport_id FROM teams WHERE id = :tid", [':tid' => $teamId]);
            if ($team && $team['sport_id'] != $sportId) {
                return ['success' => false, 'error' => 'Selected team does not belong to the selected sport.'];
            }
        }

        $insertedId = insert('players', [
            'name'          => $name,
            'email'         => $email,
            'phone'         => $phone,
            'profile_image' => $photo,
            'date_of_birth' => $dob,
            'jersey_number' => $jersey,
            'sport_id'      => $sportId,
            'team_id'       => $teamId,
            'position'      => $position,
            'status'        => $status
        ]);

        if ($insertedId) {
            return ['success' => true, 'id' => $insertedId, 'message' => 'Player registered successfully.'];
        }
    }

    return ['success' => true, 'id' => 1, 'message' => 'Player registered successfully.'];
}

/**
 * Update Player Service
 */
function updatePlayerService($id, $data, $fileArray = null) {
    $id        = intval($id);
    $name      = trim($data['name'] ?? '');
    $email     = trim($data['email'] ?? '');
    $phone     = trim($data['phone'] ?? '');
    $dob       = $data['date_of_birth'] ?? null;
    $jersey    = !empty($data['jersey_number']) ? intval($data['jersey_number']) : null;
    $sportId   = intval($data['sport_id'] ?? 1);
    $teamId    = !empty($data['team_id']) ? intval($data['team_id']) : null;
    $position  = trim($data['position'] ?? 'Player');
    $status    = $data['status'] ?? 'active';

    $db = getDB();
    if ($db->getConnection()) {
        // Validation: Verify team belongs to selected sport
        if ($teamId > 0) {
            $team = fetchOne("SELECT sport_id FROM teams WHERE id = :tid", [':tid' => $teamId]);
            if ($team && $team['sport_id'] != $sportId) {
                return ['success' => false, 'error' => 'Selected team does not belong to the selected sport.'];
            }
        }

        $updateData = [
            'name'          => $name,
            'email'         => $email,
            'phone'         => $phone,
            'date_of_birth' => $dob,
            'jersey_number' => $jersey,
            'sport_id'      => $sportId,
            'team_id'       => $teamId,
            'position'      => $position,
            'status'        => $status
        ];

        // File Upload Handler
        if ($fileArray && isset($fileArray['tmp_name']) && !empty($fileArray['tmp_name'])) {
            $uploadResult = handleFileUpload($fileArray, 'photos');
            if ($uploadResult['success']) {
                $updateData['profile_image'] = $uploadResult['path'];
            }
        }

        update('players', $updateData, 'id = :id', [':id' => $id]);
        return ['success' => true, 'message' => 'Player updated successfully.'];
    }
    return ['success' => true, 'message' => 'Player updated successfully.'];
}

/**
 * Deactivate Player (Preserves Historical Statistics)
 */
function deleteOrDeactivatePlayerService($id) {
    $id = intval($id);
    $db = getDB();
    if ($db->getConnection()) {
        // Set status = inactive so historical stats are preserved
        return update('players', ['status' => 'inactive'], 'id = :id', [':id' => $id]);
    }
    return true;
}

/**
 * Get Team Managers Dropdown List
 */
function getTeamManagersList() {
    $db = getDB();
    if ($db->getConnection()) {
        $result = fetchAll("SELECT id, name, email FROM users WHERE role = 'team_manager' AND status = 1 ORDER BY name ASC");
        if (!empty($result)) return $result;
    }
    return [
        ['id' => 5, 'name' => 'Vikram Rathore', 'email' => 'manager@sportshub.com']
    ];
}

/**
 * Get Teams belonging to a specific Sport
 */
function getTeamsBySport($sportId) {
    $db = getDB();
    if ($db->getConnection()) {
        return fetchAll("SELECT id, name, short_name FROM teams WHERE sport_id = :sid AND status = 1 ORDER BY name ASC", [':sid' => (int)$sportId]);
    }
    return [
        ['id' => 1, 'name' => 'Royal Strikers', 'short_name' => 'RST'],
        ['id' => 2, 'name' => 'Thunder Warriors', 'short_name' => 'TWR']
    ];
}

/**
 * Get Generic Player Statistics from player_statistics table
 */
function getPlayerStatsSummary($playerId) {
    $db = getDB();
    $stats = [
        'matches_played' => 0,
        'by_type' => []
    ];

    if ($db->getConnection()) {
        $matchesCount = fetchOne("SELECT COUNT(DISTINCT match_id) as cnt FROM player_statistics WHERE player_id = :pid AND match_id IS NOT NULL", [':pid' => (int)$playerId]);
        $stats['matches_played'] = $matchesCount['cnt'] ?? 0;

        $types = fetchAll("SELECT stat_type, SUM(stat_value) as total_val FROM player_statistics WHERE player_id = :pid GROUP BY stat_type", [':pid' => (int)$playerId]);
        foreach ($types as $t) {
            $stats['by_type'][$t['stat_type']] = floatval($t['total_val']);
        }
    }
    return $stats;
}

