<?php
/**
 * SportsHub - Admin / Organizer Tournament List Forwarder
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole(['admin', 'organizer']);

// Forward to official organizer tournaments controller
header('Location: ' . BASE_URL . '/organizer/tournaments.php');
exit;
