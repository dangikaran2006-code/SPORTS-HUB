<?php
/**
 * SportsHub API - Add Live Scoring Event
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
    echo json_encode(['success' => false, 'error' => 'Unauthorized access. Only official scorers can add scoring events.']);
    exit;
}

$token = $_POST['csrf_token'] ?? '';
if (!validateCsrfToken($token)) {
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF security token.']);
    exit;
}

$matchId    = intval($_POST['match_id'] ?? 0);
$eventType  = strtolower(trim($_POST['event_type'] ?? ''));
$eventValue = intval($_POST['event_value'] ?? 0);
$teamId     = !empty($_POST['team_id']) ? intval($_POST['team_id']) : null;
$playerId   = !empty($_POST['player_id']) ? intval($_POST['player_id']) : null;

$eventData = [];
if (!empty($_POST['event_data'])) {
    if (is_array($_POST['event_data'])) {
        $eventData = $_POST['event_data'];
    } else {
        $eventData = json_decode($_POST['event_data'], true) ?: [];
    }
}

if (!empty($_POST['new_batsman'])) {
    $eventData['new_batsman'] = trim($_POST['new_batsman']);
}
if (!empty($_POST['bowler'])) {
    $eventData['bowler'] = trim($_POST['bowler']);
}
if (!empty($_POST['striker'])) {
    $eventData['striker'] = trim($_POST['striker']);
}
if (!empty($_POST['non_striker'])) {
    $eventData['non_striker'] = trim($_POST['non_striker']);
}

$state = ScoringService::addEvent($matchId, $eventType, $eventValue, $eventData, $teamId, $playerId);

if (is_array($state) && isset($state['error'])) {
    echo json_encode(['success' => false, 'error' => $state['error']]);
    exit;
}

echo json_encode([
    'success'    => true,
    'match'      => $state['match'] ?? null,
    'live_state' => $state['live_state'] ?? null,
    'events'     => $state['events'] ?? [],
    'timestamp'  => time(),
]);
exit;
