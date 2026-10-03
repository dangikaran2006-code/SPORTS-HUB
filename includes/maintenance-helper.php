<?php
/**
 * SportsHub - Database Backup, Recovery & System Maintenance Engine
 * Provides system diagnostics, automated SQL backup generation, safe recovery workflows,
 * integrity audits, points/statistics recalculation, and maintenance mode controls.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/statistics-helper.php';

define('BACKUP_DIR', __DIR__ . '/../storage/backups');
define('MAINTENANCE_CONFIG', __DIR__ . '/../storage/maintenance.json');

/**
 * Ensure storage directories exist and are protected
 */
function ensureStorageDirectories() {
    $storageDir = __DIR__ . '/../storage';
    if (!file_exists($storageDir)) {
        @mkdir($storageDir, 0755, true);
    }
    if (!file_exists(BACKUP_DIR)) {
        @mkdir(BACKUP_DIR, 0755, true);
    }
    // Protect backup directory with .htaccess
    $htaccessFile = BACKUP_DIR . '/.htaccess';
    if (!file_exists($htaccessFile)) {
        @file_put_contents($htaccessFile, "Require all denied\nDeny from all\n");
    }
}

/**
 * Get Comprehensive System Health Information
 */
function getSystemHealthInfo() {
    ensureStorageDirectories();
    $db = getDB();
    $pdo = $db->getConnection();

    $dbConnected = (bool)$pdo;
    $dbVersion = 'Unknown';
    $dbName = DB_NAME;
    $tableCount = 0;
    $totalRecords = 0;
    $dbSizeMb = 0.0;

    if ($dbConnected) {
        try {
            $verStmt = $pdo->query("SELECT VERSION() AS ver");
            $dbVersion = $verStmt->fetchColumn() ?: 'MySQL Server';

            $tablesStmt = $pdo->query("SHOW TABLES");
            $tables = $tablesStmt->fetchAll(PDO::FETCH_COLUMN);
            $tableCount = count($tables);

            foreach ($tables as $t) {
                $countStmt = $pdo->query("SELECT COUNT(*) FROM `$t`");
                $totalRecords += (int)$countStmt->fetchColumn();
            }

            $sizeStmt = $pdo->query("
                SELECT SUM(data_length + index_length) / 1024 / 1024 AS size_mb 
                FROM information_schema.TABLES 
                WHERE table_schema = " . $pdo->quote(DB_NAME)
            );
            $dbSizeMb = round((float)$sizeStmt->fetchColumn(), 2);
        } catch (Exception $e) {
            error_log("System Health DB Query Error: " . $e->getMessage());
        }
    }

    // Storage info
    $uploadDir = __DIR__ . '/../uploads';
    $uploadFiles = file_exists($uploadDir) ? glob($uploadDir . '/*.*') : [];
    $uploadSize = 0;
    foreach ($uploadFiles as $f) {
        $uploadSize += @filesize($f);
    }

    $backupFiles = glob(BACKUP_DIR . '/*.sql');
    $backupSize = 0;
    foreach ($backupFiles as $f) {
        $backupSize += @filesize($f);
    }

    $diskFree = @disk_free_space(__DIR__);
    $diskTotal = @disk_total_space(__DIR__);

    return [
        'database' => [
            'connected'     => $dbConnected,
            'version'       => $dbVersion,
            'name'          => $dbName,
            'table_count'   => $tableCount,
            'total_records' => $totalRecords,
            'size_mb'       => $dbSizeMb
        ],
        'storage' => [
            'upload_files'  => count($uploadFiles),
            'upload_size'   => formatBytes($uploadSize),
            'backup_files'  => count($backupFiles),
            'backup_size'   => formatBytes($backupSize),
            'disk_free'     => $diskFree !== false ? formatBytes($diskFree) : 'N/A',
            'disk_total'    => $diskTotal !== false ? formatBytes($diskTotal) : 'N/A'
        ],
        'session' => [
            'status'        => (session_status() === PHP_SESSION_ACTIVE) ? 'Active' : 'Inactive',
            'id'            => session_id() ?: 'None',
            'save_path'     => session_save_path() ?: 'Default'
        ],
        'php' => [
            'version'         => PHP_VERSION,
            'max_execution'   => ini_get('max_execution_time') . 's',
            'memory_limit'    => ini_get('memory_limit'),
            'upload_max_files' => ini_get('upload_max_filesize'),
            'post_max_size'   => ini_get('post_max_size')
        ],
        'maintenance' => getMaintenanceSettings()
    ];
}

/**
 * Get Real Database Record Summary Counts
 */
function getDatabaseSummaryCounts() {
    $db = getDB();
    $pdo = $db->getConnection();
    if (!$pdo) return [];

    $tables = [
        'championships' => 'tournaments',
        'departments'   => 'departments',
        'sports'        => 'sports',
        'teams'         => 'teams',
        'players'       => 'players',
        'fixtures'      => 'matches',
        'results'       => 'match_events',
        'notifications' => 'notifications',
        'announcements' => 'announcements',
        'audit_logs'    => 'audit_logs'
    ];

    $counts = [];
    foreach ($tables as $label => $t) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) FROM `$t`");
            $counts[$label] = (int)$stmt->fetchColumn();
        } catch (Exception $e) {
            $counts[$label] = 0;
        }
    }
    return $counts;
}

/**
 * Format bytes to human readable string
 */
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow];
}

/**
 * Generate Real Database Backup (.sql)
 */
function createDatabaseBackup($createdByName = 'Admin') {
    ensureStorageDirectories();
    $db = getDB();
    $pdo = $db->getConnection();

    if (!$pdo) {
        return ['success' => false, 'error' => 'Database connection unavailable. Backup failed.'];
    }

    try {
        $tables = fetchAll("SHOW TABLES");
        if (empty($tables)) {
            return ['success' => false, 'error' => 'No tables found in database. Backup failed.'];
        }

        $sqlDump = "-- ==================================================\n";
        $sqlDump .= "-- SportsHub Database Dump\n";
        $sqlDump .= "-- Created: " . date('Y-m-d H:i:s') . "\n";
        $sqlDump .= "-- Database: " . DB_NAME . "\n";
        $sqlDump .= "-- Created By: " . addslashes($createdByName) . "\n";
        $sqlDump .= "-- Application Version: " . APP_VERSION . "\n";
        $sqlDump .= "-- ==================================================\n\n";
        $sqlDump .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

        foreach ($tables as $tRow) {
            $tableName = reset($tRow);

            // Create Table SQL
            $createStmt = $pdo->query("SHOW CREATE TABLE `$tableName`")->fetch();
            $createSql = $createStmt['Create Table'] ?? $createStmt[1] ?? '';

            $sqlDump .= "-- --------------------------------------------------\n";
            $sqlDump .= "-- Table structure for `$tableName`\n";
            $sqlDump .= "-- --------------------------------------------------\n";
            $sqlDump .= "DROP TABLE IF EXISTS `$tableName`;\n";
            $sqlDump .= $createSql . ";\n\n";

            // Table Data Inserts
            $rows = fetchAll("SELECT * FROM `$tableName`");
            if (!empty($rows)) {
                $sqlDump .= "-- Dumping data for `$tableName` (" . count($rows) . " records)\n";
                $columns = array_keys($rows[0]);
                $colList = '`' . implode('`, `', $columns) . '`';

                foreach (array_chunk($rows, 50) as $chunk) {
                    $valSqls = [];
                    foreach ($chunk as $row) {
                        $escapedVals = array_map(function($val) use ($pdo) {
                            if ($val === null) return 'NULL';
                            return $pdo->quote($val);
                        }, array_values($row));
                        $valSqls[] = '(' . implode(', ', $escapedVals) . ')';
                    }
                    $sqlDump .= "INSERT INTO `$tableName` ($colList) VALUES\n" . implode(",\n", $valSqls) . ";\n";
                }
                $sqlDump .= "\n";
            }
        }

        $sqlDump .= "SET FOREIGN_KEY_CHECKS=1;\n";

        $filename = 'sportshub_backup_' . date('Y-m-d_His') . '_' . substr(md5(uniqid(rand(), true)), 0, 6) . '.sql';
        $filepath = BACKUP_DIR . '/' . $filename;

        $bytesWritten = file_put_contents($filepath, $sqlDump);
        if ($bytesWritten === false) {
            return ['success' => false, 'error' => 'Failed to write backup file to storage directory.'];
        }

        $formattedSize = formatBytes($bytesWritten);
        logAuditAction('Backup Created', 'Database', 0, "Created database backup '{$filename}' ({$formattedSize})");

        return [
            'success'   => true,
            'filename'  => $filename,
            'filepath'  => $filepath,
            'size'      => $formattedSize,
            'bytes'     => $bytesWritten,
            'created_at'=> date('Y-m-d H:i:s')
        ];
    } catch (Exception $e) {
        error_log("Backup creation error: " . $e->getMessage());
        return ['success' => false, 'error' => 'Backup failed: ' . $e->getMessage()];
    }
}

/**
 * Get List of Backup Files
 */
function getBackupHistory() {
    ensureStorageDirectories();
    $files = glob(BACKUP_DIR . '/*.sql');
    $history = [];

    foreach ($files as $f) {
        $filename = basename($f);
        $size = filesize($f);
        $mtime = filemtime($f);

        $history[] = [
            'filename'   => $filename,
            'filepath'   => $f,
            'size'       => formatBytes($size),
            'raw_size'   => $size,
            'mtime'      => $mtime,
            'date'       => date('Y-m-d H:i:s', $mtime),
            'status'     => 'Valid Backup'
        ];
    }

    usort($history, function($a, $b) {
        return $b['mtime'] <=> $a['mtime'];
    });

    return $history;
}

/**
 * Delete Backup File safely
 */
function deleteBackupFile($filename) {
    ensureStorageDirectories();
    if (!preg_match('/^[a-zA-Z0-9_\-\.]+\.sql$/', $filename)) {
        return ['success' => false, 'error' => 'Invalid backup filename parameter.'];
    }

    $filepath = BACKUP_DIR . '/' . $filename;
    if (!file_exists($filepath)) {
        return ['success' => false, 'error' => 'Backup file not found.'];
    }

    if (@unlink($filepath)) {
        logAuditAction('Backup Deleted', 'Database', 0, "Deleted backup file '{$filename}'");
        return ['success' => true, 'message' => "Backup file {$filename} deleted successfully."];
    }

    return ['success' => false, 'error' => 'Failed to delete backup file from disk.'];
}

/**
 * Restore Database Backup with Safety Checks & Pre-Restore Backup
 */
function restoreDatabaseBackup($filename) {
    ensureStorageDirectories();
    if (!preg_match('/^[a-zA-Z0-9_\-\.]+\.sql$/', $filename)) {
        return ['success' => false, 'error' => 'Invalid backup filename parameter.'];
    }

    $filepath = BACKUP_DIR . '/' . $filename;
    if (!file_exists($filepath)) {
        return ['success' => false, 'error' => 'Specified backup file does not exist.'];
    }

    $db = getDB();
    $pdo = $db->getConnection();
    if (!$pdo) {
        return ['success' => false, 'error' => 'Database connection unavailable for restore operation.'];
    }

    // 1. Mandatory Pre-Restore Safety Backup
    logAuditAction('Restore Started', 'Database', 0, "Initiating database restore from {$filename}");
    $safetyResult = createDatabaseBackup('System (Pre-Restore Safety)');
    if (!$safetyResult['success']) {
        logAuditAction('Restore Cancelled', 'Database', 0, "Restore cancelled: Pre-restore safety backup failed");
        return [
            'success' => false,
            'error'   => 'Safety backup failed. Restore operation cancelled to prevent data loss.'
        ];
    }

    // 2. Read and execute SQL statements
    $sqlContent = file_get_contents($filepath);
    if (empty($sqlContent)) {
        return ['success' => false, 'error' => 'Backup file is empty. Restore aborted.'];
    }

    try {
        $pdo->exec("SET FOREIGN_KEY_CHECKS=0;");
        
        // Execute SQL statements in chunk batches
        $pdo->exec($sqlContent);

        $pdo->exec("SET FOREIGN_KEY_CHECKS=1;");

        logAuditAction('Restore Completed', 'Database', 0, "Successfully restored database from {$filename}");

        return [
            'success'        => true,
            'message'        => "Database restored successfully from '{$filename}'. Pre-restore safety backup saved as '{$safetyResult['filename']}'.",
            'safety_backup' => $safetyResult['filename']
        ];
    } catch (Exception $e) {
        $pdo->exec("SET FOREIGN_KEY_CHECKS=1;");
        logAuditAction('Restore Failed', 'Database', 0, "Restore failed for {$filename}: " . $e->getMessage());
        return [
            'success' => false,
            'error'   => 'Database restore failed: ' . $e->getMessage() . '. You can revert using pre-restore backup: ' . $safetyResult['filename']
        ];
    }
}

/**
 * Perform Comprehensive Data Integrity Audits
 */
function checkDataIntegrity() {
    $db = getDB();
    $pdo = $db->getConnection();
    if (!$pdo) return [];

    $issues = [];

    // 1. Orphaned Players (team_id missing in teams table)
    $orphanedPlayers = fetchAll("
        SELECT p.id, p.name, p.team_id 
        FROM players p 
        LEFT JOIN teams t ON p.team_id = t.id 
        WHERE p.team_id IS NOT NULL AND t.id IS NULL
    ");
    if (!empty($orphanedPlayers)) {
        $issues[] = [
            'type'        => 'Orphaned Players',
            'severity'    => 'Warning',
            'count'       => count($orphanedPlayers),
            'description' => count($orphanedPlayers) . " player record(s) reference non-existent teams.",
            'items'       => array_slice($orphanedPlayers, 0, 10),
            'fixable'     => true,
            'action_code' => 'fix_orphaned_players'
        ];
    }

    // 2. Orphaned Teams (department_id missing in departments table)
    $orphanedTeams = fetchAll("
        SELECT t.id, t.name, t.department_id 
        FROM teams t 
        LEFT JOIN departments d ON t.department_id = d.id 
        WHERE d.id IS NULL
    ");
    if (!empty($orphanedTeams)) {
        $issues[] = [
            'type'        => 'Orphaned Teams',
            'severity'    => 'Warning',
            'count'       => count($orphanedTeams),
            'description' => count($orphanedTeams) . " team record(s) reference non-existent departments.",
            'items'       => array_slice($orphanedTeams, 0, 10),
            'fixable'     => false,
            'action_code' => 'none'
        ];
    }

    // 3. Orphaned Fixtures (matches with non-existent teams or sports)
    $orphanedFixtures = fetchAll("
        SELECT m.id, m.scheduled_date, m.sport_id, m.team_a_id, m.team_b_id 
        FROM matches m 
        LEFT JOIN sports s ON m.sport_id = s.id 
        LEFT JOIN teams ta ON m.team_a_id = ta.id 
        LEFT JOIN teams tb ON m.team_b_id = tb.id 
        WHERE s.id IS NULL OR ta.id IS NULL OR tb.id IS NULL
    ");
    if (!empty($orphanedFixtures)) {
        $issues[] = [
            'type'        => 'Orphaned Matches',
            'severity'    => 'Danger',
            'count'       => count($orphanedFixtures),
            'description' => count($orphanedFixtures) . " match fixture(s) reference missing sports or deleted teams.",
            'items'       => array_slice($orphanedFixtures, 0, 10),
            'fixable'     => false,
            'action_code' => 'none'
        ];
    }

    // 4. Completed Matches without Winner / Summary
    $incompleteResults = fetchAll("
        SELECT id, scheduled_date, status, winner_team_id, result_summary 
        FROM matches 
        WHERE LOWER(status) = 'completed' AND (winner_team_id IS NULL AND (result_summary IS NULL OR result_summary = ''))
    ");
    if (!empty($incompleteResults)) {
        $issues[] = [
            'type'        => 'Incomplete Match Results',
            'severity'    => 'Warning',
            'count'       => count($incompleteResults),
            'description' => count($incompleteResults) . " completed match(es) missing winner or summary description.",
            'items'       => array_slice($incompleteResults, 0, 10),
            'fixable'     => true,
            'action_code' => 'fix_incomplete_results'
        ];
    }

    return $issues;
}

/**
 * Check Points & Standings Consistency
 */
function checkPointsConsistency() {
    $db = getDB();
    $pdo = $db->getConnection();
    if (!$pdo) return ['consistent' => true, 'issues' => []];

    $issues = [];

    // 1. Completed matches missing from department points
    $uncreditedMatches = fetchAll("
        SELECT m.id, m.sport_id, m.winner_team_id, m.result_summary 
        FROM matches m
        WHERE LOWER(m.status) = 'completed' AND m.winner_team_id IS NOT NULL
        AND m.id NOT IN (SELECT match_id FROM department_points WHERE match_id IS NOT NULL)
    ");
    if (!empty($uncreditedMatches)) {
        $issues[] = [
            'type'        => 'Uncredited Completed Matches',
            'description' => count($uncreditedMatches) . " completed match(es) have not generated department points.",
            'count'       => count($uncreditedMatches)
        ];
    }

    return [
        'consistent' => empty($issues),
        'issues'     => $issues
    ];
}

/**
 * Recalculate Championship Standings & Points
 */
function recalculatePointsConsistency() {
    $db = getDB();
    $pdo = $db->getConnection();
    if (!$pdo) return ['success' => false, 'error' => 'Database connection failed.'];

    try {
        $tourn = fetchOne("SELECT id FROM tournaments LIMIT 1");
        $tournId = $tourn['id'] ?? 1;

        StatisticsService::recalculateTournamentStandings($tournId, $pdo);
        logAuditAction('Points Recalculated', 'Championship', $tournId, "Recalculated department standings and trophy points");

        return ['success' => true, 'message' => 'Department standings and championship points recalculated successfully.'];
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Recalculation failed: ' . $e->getMessage()];
    }
}

/**
 * Check & Recalculate Player/Team Statistics
 */
function recalculateAllStatistics() {
    $db = getDB();
    $pdo = $db->getConnection();
    if (!$pdo) return ['success' => false, 'error' => 'Database connection failed.'];

    try {
        StatisticsService::rebuildAllStatistics();
        logAuditAction('Statistics Recalculated', 'Statistics', 0, "Rebuilt player and team statistics across all sports");

        return ['success' => true, 'message' => 'Player and team statistics rebuilt successfully across all sports.'];
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Statistics rebuild failed: ' . $e->getMessage()];
    }
}

/**
 * Check Upload Directory Maintenance & Orphan Files
 */
function checkUploadMaintenance() {
    $uploadDir = __DIR__ . '/../uploads';
    if (!file_exists($uploadDir)) return ['total_files' => 0, 'total_size' => '0 B', 'orphaned' => []];

    $files = glob($uploadDir . '/*.*');
    $orphaned = [];
    $totalSize = 0;

    $db = getDB();
    $pdo = $db->getConnection();

    // Collect all referenced image paths from DB
    $referenced = [];
    if ($pdo) {
        $uImgs = fetchAll("SELECT profile_image FROM users WHERE profile_image IS NOT NULL");
        foreach ($uImgs as $r) $referenced[] = basename($r['profile_image']);

        $pImgs = fetchAll("SELECT profile_image FROM players WHERE profile_image IS NOT NULL");
        foreach ($pImgs as $r) $referenced[] = basename($r['profile_image']);

        $tLogos = fetchAll("SELECT logo FROM teams WHERE logo IS NOT NULL");
        foreach ($tLogos as $r) $referenced[] = basename($r['logo']);

        $dLogos = fetchAll("SELECT logo FROM departments WHERE logo IS NOT NULL");
        foreach ($dLogos as $r) $referenced[] = basename($r['logo']);
    }

    foreach ($files as $f) {
        $basename = basename($f);
        $size = filesize($f);
        $totalSize += $size;

        if (!in_array($basename, $referenced) && $basename !== 'default-avatar.png' && $basename !== 'default-team.png') {
            $orphaned[] = [
                'filename' => $basename,
                'filepath' => $f,
                'size'     => formatBytes($size),
                'mtime'    => date('Y-m-d H:i:s', filemtime($f))
            ];
        }
    }

    return [
        'total_files' => count($files),
        'total_size'  => formatBytes($totalSize),
        'valid_files' => count($files) - count($orphaned),
        'orphaned'    => $orphaned
    ];
}

/**
 * Delete Orphaned Upload File Safely
 */
function deleteOrphanedUploadFile($filename) {
    if (!preg_match('/^[a-zA-Z0-9_\-\.]+\.(jpg|jpeg|png|webp|gif|svg)$/i', $filename)) {
        return ['success' => false, 'error' => 'Invalid file parameter.'];
    }

    $filepath = __DIR__ . '/../uploads/' . $filename;
    if (!file_exists($filepath)) {
        return ['success' => false, 'error' => 'File not found on disk.'];
    }

    if (@unlink($filepath)) {
        logAuditAction('Upload Cleaned', 'Maintenance', 0, "Deleted orphaned file '{$filename}'");
        return ['success' => true, 'message' => "Orphaned file {$filename} deleted."];
    }
    return ['success' => false, 'error' => 'Failed to delete file.'];
}

/**
 * Maintenance Mode Configuration
 */
function getMaintenanceSettings() {
    ensureStorageDirectories();
    if (file_exists(MAINTENANCE_CONFIG)) {
        $json = @file_get_contents(MAINTENANCE_CONFIG);
        $data = json_decode($json, true);
        if (is_array($data)) return $data;
    }
    return [
        'active'     => false,
        'message'    => 'Championship system is temporarily under maintenance for official score calculations.',
        'start_time' => '',
        'end_time'   => ''
    ];
}

/**
 * Update Maintenance Mode State
 */
function setMaintenanceMode($active, $message = '', $startTime = '', $endTime = '') {
    ensureStorageDirectories();
    $data = [
        'active'     => (bool)$active,
        'message'    => trim($message) ?: 'Championship system is temporarily under maintenance for official score calculations.',
        'start_time' => trim($startTime),
        'end_time'   => trim($endTime),
        'updated_at' => date('Y-m-d H:i:s')
    ];

    $saved = file_put_contents(MAINTENANCE_CONFIG, json_encode($data, JSON_PRETTY_PRINT));
    if ($saved !== false) {
        $statusText = $active ? 'Enabled' : 'Disabled';
        logAuditAction('Maintenance Mode ' . $statusText, 'System', 0, "Maintenance mode {$statusText}: '{$data['message']}'");
        return ['success' => true, 'message' => "Maintenance mode {$statusText} successfully."];
    }
    return ['success' => false, 'error' => 'Failed to save maintenance mode state.'];
}

/**
 * Check if Maintenance Mode is active for public users
 */
function isMaintenanceModeActive() {
    $settings = getMaintenanceSettings();
    return !empty($settings['active']);
}
