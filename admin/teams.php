<?php
/**
 * SportsHub - Admin Team Management Forwarder
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole(['admin', 'organizer', 'team_manager', 'player']);

header('Location: ' . BASE_URL . '/organizer/teams.php');
exit;
