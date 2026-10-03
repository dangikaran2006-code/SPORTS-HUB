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
