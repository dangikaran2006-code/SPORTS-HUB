<?php
/**
 * SportsHub API - Get Player Performance Statistics
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/statistics-helper.php';

$tournamentId = intval($_GET['tournament_id'] ?? 0);
$sportId      = intval($_GET['sport_id'] ?? 0);

$players = StatisticsService::getPlayerStatistics($tournamentId, $sportId);

echo json_encode([
    'success'   => true,
    'count'     => count($players),
    'players'   => $players,
    'timestamp' => time(),
]);
exit;
