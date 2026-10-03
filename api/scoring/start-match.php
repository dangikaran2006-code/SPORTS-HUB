<?php
/**
 * SportsHub API - Start / Change Match Lifecycle Status
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/scoring-helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method. POST required.']);
    exit;
}

$user = currentUser();
$userRole = strtolower($user['role'] ?? 'player');

if (!isLoggedIn() || !in_array($userRole, ['admin', 'organizer', 'scorer'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized. Only authorized scorers/organizers can control match lifecycle.']);
    exit;
}

$token = $_POST['csrf_token'] ?? '';
if (!validateCsrfToken($token)) {
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token.']);
    exit;
}

$matchId = intval($_POST['match_id'] ?? 0);
$status  = strtolower(trim($_POST['status'] ?? 'live'));

$result = ScoringService::updateMatchStatus($matchId, $status);
$state  = ScoringService::getLiveState($matchId);

echo json_encode([
    'success'    => $result['success'],
    'message'    => $result['message'] ?? '',
    'error'      => $result['error'] ?? null,
    'match'      => $state['match'] ?? null,
    'live_state' => $state['live_state'] ?? null,
]);
exit;
