<?php
/**
 * SportsHub - Notification & Alert Engine
 * Central helper for notification dispatch, retrieval, targeting, and alert rules.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/auth.php';

/**
 * Send a notification to a specific user (or system-wide if userId is null)
 */
function sendNotification($userId, $title, $message, $type = 'SYSTEM', $linkUrl = null, $priority = 'Normal') {
    $db = getDB();
    if (!$db->getConnection()) {
        return false;
    }

    $userId = ($userId !== null && $userId !== '') ? intval($userId) : null;
    $title = trim($title);
    $message = trim($message);
    $type = strtoupper(trim($type));
    $priority = ucfirst(strtolower(trim($priority)));

    // Prevent duplicate exact notification within last 30 seconds for same user
    if ($userId !== null) {
        $recent = fetchOne(
            "SELECT id FROM notifications 
             WHERE user_id = :uid AND title = :title AND type = :type 
             AND created_at >= DATE_SUB(NOW(), INTERVAL 30 SECOND) 
             LIMIT 1",
            [':uid' => $userId, ':title' => $title, ':type' => $type]
        );
        if ($recent) {
            return $recent['id']; // Duplicate prevented
        }
    } else {
        $recent = fetchOne(
            "SELECT id FROM notifications 
             WHERE user_id IS NULL AND title = :title AND type = :type 
             AND created_at >= DATE_SUB(NOW(), INTERVAL 30 SECOND) 
             LIMIT 1",
            [':title' => $title, ':type' => $type]
        );
        if ($recent) {
            return $recent['id']; // Duplicate prevented
        }
    }

    try {
        $newId = insert('notifications', [
            'user_id'    => $userId,
            'title'      => $title,
            'message'    => $message,
            'type'       => $type,
            'link_url'   => $linkUrl,
            'priority'   => $priority,
            'is_read'    => 0,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        return $newId;
    } catch (Exception $e) {
        error_log("Failed to create notification: " . $e->getMessage());
        return false;
    }
}

/**
 * Broadcast notification to a list of user IDs or a role group
 */
function broadcastNotification($title, $message, $type = 'ANNOUNCEMENT', $linkUrl = null, $priority = 'Normal', $targetGroup = 'Everyone', $deptId = null) {
    $db = getDB();
    if (!$db->getConnection()) return false;

    // Send null user_id notification for global broadcast if target is Everyone
    if ($targetGroup === 'Everyone' || empty($targetGroup)) {
        return sendNotification(null, $title, $message, $type, $linkUrl, $priority);
    }

    // Otherwise, fetch target user IDs
    $sql = "SELECT id FROM users WHERE status = 1";
    $params = [];

    if (in_array(strtolower($targetGroup), ['admin', 'organizer', 'scorer', 'official', 'team_manager', 'player'])) {
        $sql .= " AND LOWER(role) = :role";
        $params[':role'] = strtolower($targetGroup);
    } elseif ($targetGroup === 'Officials' || $targetGroup === 'scorers') {
        $sql .= " AND LOWER(role) IN ('official', 'scorer')";
    }

    $users = fetchAll($sql, $params);
    $sentCount = 0;
    foreach ($users as $u) {
        if (sendNotification($u['id'], $title, $message, $type, $linkUrl, $priority)) {
            $sentCount++;
        }
    }
    return $sentCount;
}

/**
 * Fetch notifications for an authenticated user with filtering
 */
function getUserNotifications($userId, $filterType = null, $filterStatus = null, $limit = 50, $offset = 0) {
    $db = getDB();
    if (!$db->getConnection()) return [];

    $sql = "SELECT * FROM notifications WHERE (user_id = :uid OR user_id IS NULL)";
    $params = [':uid' => intval($userId)];

    if (!empty($filterType) && $filterType !== 'all') {
        $sql .= " AND type = :type";
        $params[':type'] = strtoupper($filterType);
    }

    if ($filterStatus === 'unread') {
        $sql .= " AND is_read = 0";
    } elseif ($filterStatus === 'read') {
        $sql .= " AND is_read = 1";
    }

    $sql .= " ORDER BY id DESC LIMIT " . intval($limit) . " OFFSET " . intval($offset);
    return fetchAll($sql, $params);
}

/**
 * Get unread count for header badge
 */
function getUnreadNotificationCount($userId) {
    if (!$userId) return 0;
    $db = getDB();
    if (!$db->getConnection()) return 0;

    $row = fetchOne(
        "SELECT COUNT(*) as cnt FROM notifications 
         WHERE (user_id = :uid OR user_id IS NULL) AND is_read = 0",
        [':uid' => intval($userId)]
    );
    return intval($row['cnt'] ?? 0);
}

/**
 * Mark a single notification as read (with ownership check)
 */
function markNotificationRead($notificationId, $userId) {
    $db = getDB();
    if (!$db->getConnection()) return false;

    // Check ownership
    $notif = fetchOne(
        "SELECT id FROM notifications WHERE id = :id AND (user_id = :uid OR user_id IS NULL)",
        [':id' => intval($notificationId), ':uid' => intval($userId)]
    );
    if (!$notif) return false;

    return update(
        'notifications',
        ['is_read' => 1],
        'id = :id',
        [':id' => intval($notificationId)]
    );
}

/**
 * Mark all notifications as read for a user
 */
function markAllNotificationsRead($userId) {
    $db = getDB();
    if (!$db->getConnection()) return false;

    return update(
        'notifications',
        ['is_read' => 1],
        '(user_id = :uid OR user_id IS NULL) AND is_read = 0',
        [':uid' => intval($userId)]
    );
}

/**
 * Delete a notification safely for current user
 */
function deleteNotification($notificationId, $userId) {
    $db = getDB();
    if (!$db->getConnection()) return false;

    // Verify ownership
    $notif = fetchOne(
        "SELECT id FROM notifications WHERE id = :id AND (user_id = :uid OR user_id IS NULL)",
        [':id' => intval($notificationId), ':uid' => intval($userId)]
    );
    if (!$notif) return false;

    return delete(
        'notifications',
        'id = :id',
        [':id' => intval($notificationId)]
    );
}

/**
 * Trigger match/event state notifications
 */
function triggerEventNotification($matchId, $eventType, $customMsg = '') {
    $db = getDB();
    if (!$db->getConnection()) return false;

    $match = fetchOne("
        SELECT m.*, 
               s.name AS sport_name, 
               t1.name AS team_a_name, t1.department_id AS dept_a,
               t2.name AS team_b_name, t2.department_id AS dept_b,
               v.name AS venue_name
        FROM matches m
        JOIN sports s ON m.sport_id = s.id
        JOIN teams t1 ON m.team_a_id = t1.id
        JOIN teams t2 ON m.team_b_id = t2.id
        LEFT JOIN venues v ON m.venue_id = v.id
        WHERE m.id = :id
    ", [':id' => intval($matchId)]);

    if (!$match) return false;

    $sport = $match['sport_name'];
    $teamA = $match['team_a_name'];
    $teamB = $match['team_b_name'];
    $matchTitle = "{$sport} — {$teamA} vs {$teamB}";
    $linkUrl = "public/match-detail.php?id=" . $match['id'];

    $title = '';
    $message = '';
    $type = strtoupper($eventType);
    $priority = 'Normal';

    switch ($type) {
        case 'EVENT_CREATED':
            $title = "📅 New Fixture Scheduled: {$matchTitle}";
            $message = "Match scheduled for " . date('M d, Y', strtotime($match['scheduled_date'])) . " at " . date('h:i A', strtotime($match['scheduled_time'])) . ($match['venue_name'] ? " ({$match['venue_name']})" : "") . ".";
            break;

        case 'EVENT_UPDATED':
            $title = "📝 Match Details Updated: {$matchTitle}";
            $message = !empty($customMsg) ? $customMsg : "The details for {$matchTitle} have been updated by match officials.";
            break;

        case 'EVENT_RESCHEDULED':
            $title = "🕒 Match Rescheduled: {$matchTitle}";
            $message = "The match is now rescheduled to " . date('M d, Y', strtotime($match['scheduled_date'])) . " at " . date('h:i A', strtotime($match['scheduled_time'])) . ".";
            $priority = 'Important';
            break;

        case 'EVENT_POSTPONED':
            $title = "⏸️ Match Postponed: {$matchTitle}";
            $message = "Notice: {$matchTitle} has been postponed. Revised timing will be announced shortly.";
            $priority = 'Urgent';
            break;

        case 'EVENT_CANCELLED':
            $title = "❌ Match Cancelled: {$matchTitle}";
            $message = "Notice: {$matchTitle} has been cancelled by the Sports Authority.";
            $priority = 'Urgent';
            break;

        case 'EVENT_STARTED':
            $title = "🔴 LIVE NOW: {$matchTitle}";
            $message = "The {$sport} match between {$teamA} and {$teamB} is now LIVE!";
            $priority = 'Important';
            $linkUrl = "public/live.php?match_id=" . $match['id'];
            break;

        case 'EVENT_FINISHED':
            $title = "🏁 Match Completed: {$matchTitle}";
            $summary = $match['result_summary'] ? $match['result_summary'] : 'Match finished.';
            $message = "{$matchTitle} has finished. Result: {$summary}";
            $linkUrl = "public/match-detail.php?id=" . $match['id'];
            break;

        case 'RESULT_PUBLISHED':
            $title = "🏆 Official Result Published: {$matchTitle}";
            $summary = $match['result_summary'] ? $match['result_summary'] : 'Result confirmed.';
            $message = "Official result published for {$matchTitle}. Result: {$summary}";
            $priority = 'Important';
            $linkUrl = "public/results.php?match_id=" . $match['id'];
            break;

        case 'RESULT_CORRECTED':
            $title = "⚠️ Official Result Updated: {$matchTitle}";
            $message = "An official result correction has been posted for {$matchTitle}. " . ($customMsg ?: "Please view the updated result page.");
            $priority = 'Urgent';
            $linkUrl = "public/results.php?match_id=" . $match['id'];
            break;

        case 'POINTS_UPDATED':
            $title = "📊 Championship Points Table Updated";
            $message = "The official standings and points table have been updated following recent match results.";
            $linkUrl = "public/points-table.php";
            break;

        default:
            $title = "Notice: {$matchTitle}";
            $message = $customMsg ?: "Status changed for {$matchTitle}.";
            break;
    }

    return sendNotification(null, $title, $message, $type, $linkUrl, $priority);
}

/**
 * Check and create automated event reminders (e.g. 24h & 1h prior to match start)
 */
function checkAndSendEventReminders() {
    $db = getDB();
    if (!$db->getConnection()) return 0;

    // Fetch upcoming scheduled matches in next 24 hours that haven't been reminded
    $upcomingMatches = fetchAll("
        SELECT m.*, s.name AS sport_name, t1.name AS team_a, t2.name AS team_b, v.name AS venue_name
        FROM matches m
        JOIN sports s ON m.sport_id = s.id
        JOIN teams t1 ON m.team_a_id = t1.id
        JOIN teams t2 ON m.team_b_id = t2.id
        LEFT JOIN venues v ON m.venue_id = v.id
        WHERE LOWER(m.status) = 'scheduled'
        AND TIMESTAMP(m.scheduled_date, m.scheduled_time) BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 24 HOUR)
    ");

    $count = 0;
    foreach ($upcomingMatches as $m) {
        $matchTime = strtotime($m['scheduled_date'] . ' ' . $m['scheduled_time']);
        $diffHours = ($matchTime - time()) / 3600;

        $reminderType = null;
        if ($diffHours <= 1.5 && $diffHours >= 0.5) {
            $reminderType = 'EVENT_REMINDER_1H';
        } elseif ($diffHours <= 24.5 && $diffHours >= 23.0) {
            $reminderType = 'EVENT_REMINDER_24H';
        }

        if ($reminderType) {
            $matchTitle = "{$m['sport_name']} — {$m['team_a']} vs {$m['team_b']}";
            $timeString = date('h:i A', $matchTime);
            $hoursText = ($reminderType === 'EVENT_REMINDER_1H') ? "1 hour" : "24 hours";

            $sent = sendNotification(
                null,
                "⏰ Upcoming Match Reminder ({$hoursText}): {$matchTitle}",
                "Reminder: {$matchTitle} starts in approx {$hoursText} at {$timeString}" . ($m['venue_name'] ? " ({$m['venue_name']})" : "") . ".",
                'EVENT_REMINDER',
                "public/match-detail.php?id=" . $m['id'],
                'Normal'
            );
            if ($sent) $count++;
        }
    }
    return $count;
}
