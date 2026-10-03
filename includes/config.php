<?php
/**
 * SportsHub - Multi-Sport Tournament Management Platform
 * Application Configuration & Environment Constants
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Application Constants
define('APP_NAME', 'SportsHub');
define('APP_TAGLINE', 'Next-Gen Multi-Sport Tournament Engine');
define('APP_VERSION', '1.0.0');

// Base URL detection (Consistently resolves to application root across all subfolders)
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || ($_SERVER['SERVER_PORT'] ?? 80) == 443) ? "https://" : "http://";
$domainName = $_SERVER['HTTP_HOST'] ?? 'localhost:8000';

$docRoot = isset($_SERVER['DOCUMENT_ROOT']) ? str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'])) : '';
$projRoot = str_replace('\\', '/', realpath(__DIR__ . '/..'));

$subDir = '';
if (!empty($docRoot) && !empty($projRoot) && strpos($projRoot, $docRoot) === 0) {
    $subDir = substr($projRoot, strlen($docRoot));
}
$subDir = rtrim(str_replace('\\', '/', $subDir), '/');

define('BASE_URL', $protocol . $domainName . $subDir);

// Database Environment Configuration
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'sportshub');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_PORT', '3306');
define('DB_CHARSET', 'utf8mb4');

/**
 * Format Sports Badge
 */
function getSportBadge($sportName) {
    $colors = [
        'Cricket'      => 'badge-cricket',
        'Football'     => 'badge-football',
        'Kabaddi'      => 'badge-kabaddi',
        'Basketball'   => 'badge-basketball',
        'Volleyball'   => 'badge-volleyball',
        'Badminton'    => 'badge-badminton',
        'Tennis'       => 'badge-tennis',
        'Table Tennis' => 'badge-table-tennis',
    ];
    $class = $colors[$sportName] ?? 'badge-generic';
    return "<span class=\"sport-badge {$class}\">" . htmlspecialchars($sportName) . "</span>";
}

/**
 * Format Status Badge
 */
function getStatusBadge($status) {
    $statusMap = [
        'live'      => ['class' => 'badge-live', 'label' => '<span class="pulse-dot"></span> LIVE'],
        'scheduled' => ['class' => 'badge-scheduled', 'label' => 'Scheduled'],
        'completed' => ['class' => 'badge-completed', 'label' => 'Completed'],
        'upcoming'  => ['class' => 'badge-upcoming', 'label' => 'Upcoming'],
        'active'    => ['class' => 'badge-active', 'label' => 'Active'],
        'draft'     => ['class' => 'badge-generic', 'label' => 'Draft'],
        'postponed' => ['class' => 'badge-warning', 'label' => 'Postponed'],
        'cancelled' => ['class' => 'badge-danger', 'label' => 'Cancelled']
    ];
    
    $info = $statusMap[strtolower($status)] ?? ['class' => 'badge-generic', 'label' => ucfirst($status)];
    return "<span class=\"status-badge {$info['class']}\">{$info['label']}</span>";
}

/**
 * Navigation Active Route Helper
 */
function isNavActive($pageKey, $currentPage) {
    return ($pageKey === $currentPage) ? 'active' : '';
}
