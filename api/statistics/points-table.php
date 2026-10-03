<?php
/**
 * SportsHub API - Get Tournament Points Table & Standings
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/statistics-helper.php';

$tournamentId = intval($_GET['tournament_id'] ?? 0);
$sportId      = intval($_GET['sport_id'] ?? 0);

$standings = StatisticsService::getPointsTable($tournamentId, $sportId);

echo json_encode([
    'success'   => true,
    'count'     => count($standings),
    'standings' => $standings,
    'timestamp' => time(),
]);
exit;
