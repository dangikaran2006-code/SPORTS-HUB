<?php
/**
 * SportsHub - Main Public Entrance
 */
require_once __DIR__ . '/includes/config.php';

// Direct visitors to the Public Spectator Interface homepage
header('Location: ' . BASE_URL . '/public/home.php');
exit;
