<?php
/**
 * SportsHub - Championship Initialization, Setup & Demo Management Engine
 * Provides multi-step wizard logic, setup validation, status state transitions,
 * demo mode isolation, and quick setup shortcuts.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/department-helper.php';

/**
 * Get Championship Setup Progress & Checklist from DB state
 */
function getChampionshipSetupProgress() {
    $db = getDB();
    $pdo = $db->getConnection();

    $championship = DepartmentService::getMasterChampionship();
    $deptCount = 0;
    $sportCount = 0;
    $venueCount = 0;
    $officialCount = 0;
    $teamCount = 0;

    if ($pdo) {
        $deptCount     = (int)fetchOne("SELECT COUNT(*) as cnt FROM departments WHERE status = 1")['cnt'];
        $sportCount    = (int)fetchOne("SELECT COUNT(*) as cnt FROM sports WHERE status = 1")['cnt'];
        $venueCount    = (int)fetchOne("SELECT COUNT(*) as cnt FROM venues WHERE status = 1")['cnt'];
        $officialCount = (int)fetchOne("SELECT COUNT(*) as cnt FROM officials WHERE status = 1")['cnt'];
        $teamCount     = (int)fetchOne("SELECT COUNT(*) as cnt FROM teams WHERE status = 1")['cnt'];
    }

    $cComplete = !empty($championship['name']) && !empty($championship['college_name']);
    $dComplete = ($deptCount >= 1);
    $sComplete = ($sportCount >= 1);
    $vComplete = ($venueCount >= 1);
    $oComplete = ($officialCount >= 1);
    $pComplete = true; // Point rules configured in system settings

    $ready = $cComplete && $dComplete && $sComplete && $vComplete && $oComplete && $pComplete;

    return [
        'championship'  => [
            'title'       => 'College / Institute Information',
            'is_complete' => $cComplete,
            'details'     => $championship['name'] . ' (' . $championship['college_name'] . ')'
        ],
        'departments'   => [
            'title'       => 'Departments Configuration',
            'is_complete' => $dComplete,
            'count'       => $deptCount,
            'details'     => "{$deptCount} Active Department(s)"
        ],
        'sports'        => [
            'title'       => 'Sports Master List',
            'is_complete' => $sComplete,
            'count'       => $sportCount,
            'details'     => "{$sportCount} Configured Sport(s)"
        ],
        'venues'        => [
            'title'       => 'Venues & Courts',
            'is_complete' => $vComplete,
            'count'       => $venueCount,
            'details'     => "{$venueCount} Active Venue(s)"
        ],
        'officials'     => [
            'title'       => 'Match Officials & Umpires',
            'is_complete' => $oComplete,
            'count'       => $officialCount,
            'details'     => "{$officialCount} Registered Official(s)"
        ],
        'point_rules'   => [
            'title'       => 'Point & Trophy Scoring Rules',
            'is_complete' => $pComplete,
            'details'     => 'Standard Scoring Engine Active'
        ],
        'ready_to_start'=> $ready,
        'status'        => getSetting('championship_status', 'ACTIVE')
    ];
}

/**
 * Validate & Activate Championship
 */
function activateChampionship($targetStatus = 'ACTIVE') {
    $progress = getChampionshipSetupProgress();
    
    $allowedStatuses = ['DRAFT', 'READY', 'ACTIVE', 'COMPLETED', 'ARCHIVED'];
    $targetStatus = strtoupper(trim($targetStatus));

    if (!in_array($targetStatus, $allowedStatuses)) {
        return ['success' => false, 'error' => 'Invalid championship status specified.'];
    }

    if ($targetStatus === 'ACTIVE' && !$progress['ready_to_start']) {
        $missing = [];
        if (!$progress['championship']['is_complete']) $missing[] = 'College Information';
        if (!$progress['departments']['is_complete']) $missing[] = 'At least 1 Department';
        if (!$progress['sports']['is_complete']) $missing[] = 'At least 1 Sport';
        if (!$progress['venues']['is_complete']) $missing[] = 'At least 1 Venue';
        if (!$progress['officials']['is_complete']) $missing[] = 'At least 1 Official';

        return [
            'success' => false,
            'error'   => 'Cannot activate championship. Missing required setup items: ' . implode(', ', $missing) . '.'
        ];
    }

    updateSetting('championship_status', $targetStatus);

    if (isset($_SESSION['master_championship'])) {
        $_SESSION['master_championship']['status'] = $targetStatus;
    }

    logAuditAction('Championship Status Changed', 'Championship', 0, "Championship status updated to '{$targetStatus}'");

    return ['success' => true, 'message' => "Championship successfully set to <strong>{$targetStatus}</strong>."];
}

/**
 * Check if active championship exists
 */
function isChampionshipActive() {
    $status = getSetting('championship_status', 'ACTIVE');
    return (strtoupper($status) === 'ACTIVE' || strtoupper($status) === 'LIVE');
}

/**
 * Check if Demo Mode is enabled
 */
function isDemoModeEnabled() {
    return (int)getSetting('demo_mode_active', '0') === 1;
}

/**
 * Initialize Safe Sample Demo Mode Data
 */
function initializeDemoMode() {
    $db = getDB();
    $pdo = $db->getConnection();
    if (!$pdo) return ['success' => false, 'error' => 'Database connection unavailable.'];

    try {
        updateSetting('demo_mode_active', '1');

        // Create Demo Departments if none exist with demo prefix
        $demoDepts = [
            ['name' => '[DEMO] Computer Science & Engg', 'short_code' => 'DEMO-CSE', 'color_code' => '#00e676'],
            ['name' => '[DEMO] Mechanical Engg', 'short_code' => 'DEMO-ME', 'color_code' => '#ef4444'],
            ['name' => '[DEMO] Electronics & Comm Engg', 'short_code' => 'DEMO-ECE', 'color_code' => '#3b82f6'],
        ];

        foreach ($demoDepts as $d) {
            $exists = fetchOne("SELECT id FROM departments WHERE short_code = :c", [':c' => $d['short_code']]);
            if (!$exists) {
                insert('departments', [
                    'name'       => $d['name'],
                    'short_code' => $d['short_code'],
                    'color_code' => $d['color_code'],
                    'status'     => 1,
                    'created_at' => date('Y-m-d H:i:s')
                ]);
            }
        }

        // Create Demo Venue
        $existsVenue = fetchOne("SELECT id FROM venues WHERE name LIKE '%[DEMO]%'");
        if (!$existsVenue) {
            insert('venues', [
                'name'       => '[DEMO] Main Sports Arena',
                'location'   => 'North Campus Complex',
                'capacity'   => 500,
                'status'     => 1,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        }

        logAuditAction('Demo Mode Enabled', 'System', 0, 'Initialized sample demonstration records marked with [DEMO]');

        return ['success' => true, 'message' => 'Demo Mode enabled with sample demonstration records. Demo data is clearly labeled [DEMO].'];
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Failed to initialize demo data: ' . $e->getMessage()];
    }
}

/**
 * Safely Reset ONLY Demo Data
 */
function resetDemoData() {
    $db = getDB();
    $pdo = $db->getConnection();
    if (!$pdo) return ['success' => false, 'error' => 'Database connection unavailable.'];

    try {
        // Delete only records starting with [DEMO] or is_demo
        delete('departments', "name LIKE '[DEMO]%' OR short_code LIKE 'DEMO%'");
        delete('venues', "name LIKE '[DEMO]%'");
        delete('teams', "name LIKE '[DEMO]%' OR short_name LIKE 'DEMO%'");
        delete('players', "name LIKE '[DEMO]%'");

        updateSetting('demo_mode_active', '0');

        logAuditAction('Demo Data Reset', 'System', 0, 'Safely removed all demonstration records while preserving real championship data');

        return ['success' => true, 'message' => 'Demo data reset completed safely. All real championship records and users were preserved intact.'];
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Failed to reset demo data: ' . $e->getMessage()];
    }
}

/**
 * Entity Creation Pre-Validation (Sport, Venue, Official, Department, Championship)
 */
function validateEntityForMatchCreation($sportId, $teamAId, $teamBId, $venueId = null, $officialId = null) {
    $db = getDB();
    if (!$db->getConnection()) return ['valid' => false, 'error' => 'Database unavailable.'];

    // 1. Championship Status
    if (!isChampionshipActive()) {
        return ['valid' => false, 'error' => 'Championship is currently not active. Please activate championship first.'];
    }

    // 2. Active Sport Check
    $sport = fetchOne("SELECT id, status FROM sports WHERE id = :id", [':id' => intval($sportId)]);
    if (!$sport || empty($sport['status'])) {
        return ['valid' => false, 'error' => 'Selected sport is inactive or invalid.'];
    }

    // 3. Active Teams Check
    $teamA = fetchOne("SELECT id, status FROM teams WHERE id = :id", [':id' => intval($teamAId)]);
    $teamB = fetchOne("SELECT id, status FROM teams WHERE id = :id", [':id' => intval($teamBId)]);
    if (!$teamA || empty($teamA['status']) || !$teamB || empty($teamB['status'])) {
        return ['valid' => false, 'error' => 'One or both selected teams are inactive or invalid.'];
    }

    // 4. Active Venue Check
    if ($venueId) {
        $venue = fetchOne("SELECT id, status FROM venues WHERE id = :id", [':id' => intval($venueId)]);
        if (!$venue || empty($venue['status'])) {
            return ['valid' => false, 'error' => 'Selected venue is inactive or invalid.'];
        }
    }

    // 5. Active Official Check
    if ($officialId) {
        $official = fetchOne("SELECT id, status FROM officials WHERE id = :id", [':id' => intval($officialId)]);
        if (!$official || empty($official['status'])) {
            return ['valid' => false, 'error' => 'Selected match official is inactive or invalid.'];
        }
    }

    return ['valid' => true];
}
