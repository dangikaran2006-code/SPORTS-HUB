<?php
/**
 * SportsHub - Scorer Live Match Forwarder
 */
require_once __DIR__ . '/../includes/config.php';
$matchId = intval($_GET['match_id'] ?? 1);
header('Location: ' . BASE_URL . '/organizer/live-scoring.php?match_id=' . $matchId);
exit;
