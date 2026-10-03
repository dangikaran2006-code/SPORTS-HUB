<?php
/**
 * SportsHub - Secure Database Backup Downloader API
 * Authenticated Admin streamer for database SQL backups
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/maintenance-helper.php';

requireAdminAccess();

$filename = $_GET['file'] ?? '';

if (!preg_match('/^[a-zA-Z0-9_\-\.]+\.sql$/', $filename)) {
    die("Invalid backup file request.");
}

$filepath = BACKUP_DIR . '/' . $filename;

if (!file_exists($filepath)) {
    header("HTTP/1.0 404 Not Found");
    die("Backup file not found.");
}

logAuditAction('Backup Downloaded', 'Database', 0, "Downloaded backup file '{$filename}'");

header('Content-Description: File Transfer');
header('Content-Type: application/sql');
header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($filepath));
readfile($filepath);
exit;
