<?php
/**
 * SportsHub - Notification Polling & Actions API
 * Lightweight AJAX endpoint for notification count & live updates
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/notification-helper.php';

$user = getCurrentUser();
$userId = $user['id'] ?? null;

$action = $_REQUEST['action'] ?? 'poll';
$response = ['success' => false, 'unread_count' => 0, 'notifications' => []];

if ($action === 'poll') {
    // Check reminders periodically during poll
    checkAndSendEventReminders();

    $unreadCount = getUnreadNotificationCount($userId);
    $notifications = getUserNotifications($userId, $_GET['type'] ?? null, $_GET['status'] ?? null, 20);

    // Format created_at for friendly relative display
    foreach ($notifications as &$n) {
        $n['time_formatted'] = date('M d, Y h:i A', strtotime($n['created_at']));
    }

    $response = [
        'success'        => true,
        'unread_count'   => $unreadCount,
        'notifications'  => $notifications
    ];
} elseif ($action === 'mark_read') {
    $notifId = intval($_REQUEST['id'] ?? 0);
    if ($userId && $notifId > 0) {
        $ok = markNotificationRead($notifId, $userId);
        $response['success'] = (bool)$ok;
    }
    $response['unread_count'] = getUnreadNotificationCount($userId);
} elseif ($action === 'mark_all_read') {
    if ($userId) {
        $ok = markAllNotificationsRead($userId);
        $response['success'] = (bool)$ok;
    }
    $response['unread_count'] = 0;
} elseif ($action === 'delete') {
    $notifId = intval($_REQUEST['id'] ?? 0);
    if ($userId && $notifId > 0) {
        $ok = deleteNotification($notifId, $userId);
        $response['success'] = (bool)$ok;
    }
    $response['unread_count'] = getUnreadNotificationCount($userId);
}

echo json_encode($response);
exit;
