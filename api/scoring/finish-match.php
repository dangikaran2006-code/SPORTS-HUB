<?php
/**
 * SportsHub API - Finish Match & Record Winner
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
    echo json_encode(['success' => false, 'error' => 'Unauthorized access.']);
    exit;
}

$token = $_POST['csrf_token'] ?? '';
if (!validateCsrfToken($token)) {
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token.']);
    exit;
}

$matchId       = intval($_POST['match_id'] ?? 0);
$winnerTeamId  = !empty($_POST['winner_team_id']) ? intval($_POST['winner_team_id']) : null;
$resultSummary = trim($_POST['result_summary'] ?? 'Match Completed');

$state = ScoringService::finishMatch($matchId, $winnerTeamId, $resultSummary);

echo json_encode([
    'success'    => true,
    'message'    => 'Match officially marked as Completed.',
    'match'      => $state['match'] ?? null,
    'live_state' => $state['live_state'] ?? null,
    'timestamp'  => time(),
]);
exit;
