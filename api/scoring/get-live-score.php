<?php
/**
 * SportsHub API - Get Live Match Score (Read-Only JSON)
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/scoring-helper.php';

$matchId = intval($_GET['match_id'] ?? 1);
$state = ScoringService::getLiveState($matchId);

if (!$state) {
    echo json_encode(['success' => false, 'error' => 'Match not found.']);
    exit;
}

echo json_encode([
    'success'    => true,
    'match'      => $state['match'],
    'live_state' => $state['live_state'],
    'events'     => $state['events'],
    'timestamp'  => $state['timestamp'],
]);
exit;
