<?php
/**
 * SportsHub - Session Authentication & Role-Based Access Control (RBAC)
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if a user is currently logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Enforce login on protected pages
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . '/auth/login.php');
        exit;
    }
}

/**
 * Get current logged in user record from DB or session fallback
 */
function currentUser() {
    if (!isLoggedIn()) {
        return null;
    }

    $db = getDB();
    if ($db->getConnection()) {
        $user = fetchOne("SELECT id, name, email, role, profile_image, status FROM users WHERE id = :id", [
            ':id' => $_SESSION['user_id']
        ]);
        if ($user) {
            return $user;
        }
    }

    // Session fallback if DB is in demo mode
    return [
        'id'            => $_SESSION['user_id'] ?? 1,
        'name'          => $_SESSION['user_name'] ?? 'Alex Mercer',
        'email'         => $_SESSION['user_email'] ?? 'admin@sportshub.com',
        'role'          => strtolower($_SESSION['user_role'] ?? 'admin'),
        'profile_image' => 'assets/images/default-avatar.png',
        'status'        => 1
    ];
}

/**
 * Enforce Role-Based Access Control (RBAC)
 * @param string|array $allowedRoles
 */
function requireRole($allowedRoles) {
    requireLogin();
    
    $user = currentUser();
    $userRole = strtolower($user['role'] ?? 'player');

    if (is_string($allowedRoles)) {
        $allowedRoles = [$allowedRoles];
    }

    $allowedRoles = array_map('strtolower', $allowedRoles);

    if (!in_array($userRole, $allowedRoles)) {
        // Redirect unauthorized access to dashboard or show alert
        $_SESSION['flash_error'] = "Access Denied: You do not have permission to access this area.";
        header('Location: ' . BASE_URL . '/admin/dashboard.php');
        exit;
    }
}

/**
 * Enforce strict server-side Admin / Authority access
 */
function requireAdminAccess() {
    requireLogin();
    
    $user = currentUser();
    $userRole = strtolower($user['role'] ?? 'public_user');

    $allowedRoles = ['admin', 'organizer', 'scorer', 'official', 'team_manager'];

    if (!in_array($userRole, $allowedRoles)) {
        http_response_code(403);
        $_SESSION['flash_error'] = "403 Forbidden: Administrative access required.";
        header('Location: ' . BASE_URL . '/public/403.php');
        exit;
    }
}

/**
 * Securely log out current user
 */
function logoutUser() {
    logAuditAction('Logout Success', 'User', $_SESSION['user_id'] ?? null, "User logged out");
    $_SESSION = array();

    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    session_destroy();
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

/**
 * CSRF Protection Helpers
 */
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCsrfToken($token) {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Permission Matrix Definition
 */
function getRolePermissions($role) {
    $role = strtolower($role);
    $matrix = [
        'admin' => ['*'],
        'organizer' => [
            'championship', 'departments', 'sports', 'players', 'teams',
            'fixtures', 'venues', 'officials', 'results', 'announcements', 'statistics'
        ],
        'scorer' => [
            'live_scoring', 'match_events', 'submit_result'
        ],
        'official' => [
            'assigned_events', 'match_info', 'verify_result'
        ],
        'team_manager' => [
            'manage_team', 'view_fixtures', 'view_results'
        ],
        'player' => [
            'view_fixtures', 'view_results', 'view_profile'
        ],
        'public_user' => [
            'view_public'
        ]
    ];
    return $matrix[$role] ?? ['view_public'];
}

/**
 * Check if current logged in user has a specific permission
 */
function hasPermission($permissionKey) {
    $user = currentUser();
    if (!$user) return false;
    $role = strtolower($user['role'] ?? 'public_user');
    if ($role === 'admin') return true;
    
    $permissions = getRolePermissions($role);
    return in_array('*', $permissions) || in_array($permissionKey, $permissions);
}

/**
 * Enforce a required permission server-side
 */
function requirePermission($permissionKey) {
    requireLogin();
    if (!hasPermission($permissionKey)) {
        logAuditAction('Permission Denied', 'Security', null, "Attempted unauthorized access to permission: {$permissionKey}");
        http_response_code(403);
        $_SESSION['flash_error'] = "403 Forbidden: Insufficient permissions for action [{$permissionKey}].";
        header('Location: ' . BASE_URL . '/public/403.php');
        exit;
    }
}

/**
 * Verify if Scorer is assigned to a specific match
 */
function canScorerAccessMatch($userId, $matchId) {
    $user = currentUser();
    if (!$user) return false;
    if (strtolower($user['role']) === 'admin') return true;
    if (strtolower($user['role']) !== 'scorer') return false;

    $db = getDB();
    if ($db->getConnection()) {
        $match = fetchOne("SELECT id, scorer_id FROM matches WHERE id = :id", [':id' => $matchId]);
        if ($match) {
            return (int)($match['scorer_id'] ?? 0) === (int)$userId || empty($match['scorer_id']);
        }
    }
    return true;
}

/**
 * Verify if Official is assigned to a specific match
 */
function canOfficialAccessMatch($userId, $matchId) {
    $user = currentUser();
    if (!$user) return false;
    if (strtolower($user['role']) === 'admin') return true;
    if (strtolower($user['role']) !== 'official') return false;

    $db = getDB();
    if ($db->getConnection()) {
        $match = fetchOne("SELECT id, official_id FROM matches WHERE id = :id", [':id' => $matchId]);
        if ($match) {
            return (int)($match['official_id'] ?? 0) === (int)$userId || empty($match['official_id']);
        }
    }
    return true;
}

/**
 * Last Admin Protection Check
 * Returns true if the specified user is the ONLY active admin left
 */
function isLastAdmin($userId) {
    $db = getDB();
    if ($db->getConnection()) {
        $user = fetchOne("SELECT role FROM users WHERE id = :id", [':id' => $userId]);
        if (!$user || strtolower($user['role']) !== 'admin') {
            return false;
        }
        $adminCount = fetchOne("SELECT COUNT(*) as cnt FROM users WHERE LOWER(role) = 'admin' AND status = 1 AND id != :id", [':id' => $userId]);
        return (int)($adminCount['cnt'] ?? 0) === 0;
    }
    return false;
}

/**
 * Audit Logging Helper
 */
function logAuditAction($action, $entity = null, $entityId = null, $description = null) {
    $user = currentUser();
    $userId = $user['id'] ?? ($_SESSION['user_id'] ?? null);
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

    $db = getDB();
    if ($db->getConnection()) {
        try {
            insert('audit_logs', [
                'user_id'     => $userId,
                'action'      => $action,
                'entity_type' => $entity ?: 'System',
                'entity_id'   => $entityId,
                'description' => $description,
                'ip_address'  => $ip
            ]);
        } catch (Exception $e) {
            error_log("Audit Log Failure: " . $e->getMessage());
        }
    }
}

/**
 * Dynamic System Settings Helpers
 */
function getSetting($key, $default = '') {
    $db = getDB();
    if ($db->getConnection()) {
        $row = fetchOne("SELECT setting_value FROM settings WHERE setting_key = :k", [':k' => $key]);
        if ($row && $row['setting_value'] !== null) {
            return $row['setting_value'];
        }
    }

    $defaults = [
        'college_name' => 'Siddaganga Institute of Technology',
        'championship_title' => 'Inter-Department Sports Championship 2026',
        'academic_year' => '2025-2026',
        'college_logo' => 'assets/images/college-logo.png',
        'championship_banner' => 'assets/images/hero-bg.jpg',
        'primary_contact_email' => 'sports@sit.ac.in',
        'primary_contact_phone' => '+91 98765 43210',
        'default_timezone' => 'Asia/Kolkata',
        'date_format' => 'Y-m-d H:i:s',
        'footer_text' => '© Siddaganga Institute of Technology — Inter-Department Sports Championship',
        'public_visibility' => '1',
        'notify_upcoming' => '1',
        'notify_live' => '1',
        'notify_results' => '1'
    ];
    return $defaults[$key] ?? $default;
}

function updateSetting($key, $value) {
    $db = getDB();
    if ($db->getConnection()) {
        $existing = fetchOne("SELECT setting_key FROM settings WHERE setting_key = :k", [':k' => $key]);
        if ($existing) {
            update('settings', ['setting_value' => $value], 'setting_key = :k', [':k' => $key]);
        } else {
            insert('settings', ['setting_key' => $key, 'setting_value' => $value]);
        }
        return true;
    }
    return false;
}

