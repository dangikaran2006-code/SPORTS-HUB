<?php
/**
 * SportsHub - Admin & User Notification Center
 */
$currentPage = 'notifications';
$pageTitle = 'Notification Center';

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/notification-helper.php';

requireLogin();

$user = getCurrentUser();
$userId = $user['id'];
$isAdmin = ($user['role'] === 'admin');

$msg = '';
$error = '';
$csrfToken = generateCsrfToken();

// Handle POST actions (mark read, mark all read, delete, broadcast)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    $action = $_POST['action'] ?? '';

    if (!validateCsrfToken($token)) {
        $error = 'Security validation failed (Invalid CSRF token).';
    } else {
        if ($action === 'mark_read') {
            $notifId = intval($_POST['notification_id'] ?? 0);
            if (markNotificationRead($notifId, $userId)) {
                $msg = 'Notification marked as read.';
            }
        } elseif ($action === 'mark_all_read') {
            if (markAllNotificationsRead($userId)) {
                $msg = 'All notifications marked as read.';
            }
        } elseif ($action === 'delete') {
            $notifId = intval($_POST['notification_id'] ?? 0);
            if (deleteNotification($notifId, $userId)) {
                $msg = 'Notification deleted.';
            } else {
                $error = 'Unable to delete notification or permission denied.';
            }
        } elseif ($action === 'broadcast' && $isAdmin) {
            $bTitle = trim($_POST['title'] ?? '');
            $bMessage = trim($_POST['message'] ?? '');
            $bType = $_POST['type'] ?? 'ANNOUNCEMENT';
            $bPriority = $_POST['priority'] ?? 'Normal';
            $bTarget = $_POST['target_group'] ?? 'Everyone';

            if (!empty($bTitle) && !empty($bMessage)) {
                $count = broadcastNotification($bTitle, $bMessage, $bType, null, $bPriority, $bTarget);
                logAuditAction('Notification Broadcast', 'Notification', 0, "Broadcasted '{$bTitle}' to {$bTarget}");
                $msg = "Successfully broadcasted notification <strong>" . htmlspecialchars($bTitle) . "</strong> to {$bTarget}!";
            } else {
                $error = 'Please fill out both headline and message for broadcast.';
            }
        }
    }
}

// Filtering
$filterType = $_GET['type'] ?? 'all';
$filterStatus = $_GET['status'] ?? 'all';

// Fetch notifications
$notifications = getUserNotifications($userId, $filterType, $filterStatus, 100);
$unreadCount = getUnreadNotificationCount($userId);

include_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
  <div class="dashboard-title-group">
    <h1>🔔 Championship Notification Center</h1>
    <p>View real-time match alerts, schedule updates, result publications, and official bulletins.</p>
  </div>
  <div style="display: flex; gap: 12px; align-items: center;">
    <?php if ($unreadCount > 0): ?>
      <form action="" method="POST" style="margin: 0;">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
        <input type="hidden" name="action" value="mark_all_read">
        <button type="submit" class="btn btn-secondary">
          ✓ Mark All as Read (<?php echo $unreadCount; ?>)
        </button>
      </form>
    <?php endif; ?>

    <?php if ($isAdmin): ?>
      <button class="btn btn-primary" onclick="document.getElementById('broadcastModal').style.display='flex';">
        📢 Broadcast Alert
      </button>
    <?php endif; ?>
  </div>
</div>

<?php if (!empty($msg)): ?>
  <div class="auth-alert auth-alert-success" style="margin-bottom: 20px;">
    <span><?php echo $msg; ?></span>
  </div>
<?php endif; ?>

<?php if (!empty($error)): ?>
  <div class="auth-alert auth-alert-danger" style="margin-bottom: 20px;">
    <span><?php echo htmlspecialchars($error); ?></span>
  </div>
<?php endif; ?>

<!-- Filters Toolbar -->
<div class="card" style="margin-bottom: 24px; padding: 16px;">
  <form method="GET" action="" style="display: flex; flex-wrap: wrap; gap: 16px; align-items: center;">
    <div style="display: flex; align-items: center; gap: 8px;">
      <label style="font-weight: 700; font-size: 0.85rem; color: var(--text-muted);">Type Filter:</label>
      <select name="type" class="form-control" style="padding: 6px 12px; width: auto;" onchange="this.form.submit();">
        <option value="all" <?php echo ($filterType === 'all') ? 'selected' : ''; ?>>All Notification Types</option>
        <option value="EVENT_CREATED" <?php echo ($filterType === 'EVENT_CREATED') ? 'selected' : ''; ?>>Event Created</option>
        <option value="EVENT_RESCHEDULED" <?php echo ($filterType === 'EVENT_RESCHEDULED') ? 'selected' : ''; ?>>Event Rescheduled</option>
        <option value="EVENT_POSTPONED" <?php echo ($filterType === 'EVENT_POSTPONED') ? 'selected' : ''; ?>>Event Postponed</option>
        <option value="EVENT_CANCELLED" <?php echo ($filterType === 'EVENT_CANCELLED') ? 'selected' : ''; ?>>Event Cancelled</option>
        <option value="EVENT_STARTED" <?php echo ($filterType === 'EVENT_STARTED') ? 'selected' : ''; ?>>Event Started (LIVE)</option>
        <option value="EVENT_FINISHED" <?php echo ($filterType === 'EVENT_FINISHED') ? 'selected' : ''; ?>>Event Finished</option>
        <option value="RESULT_PUBLISHED" <?php echo ($filterType === 'RESULT_PUBLISHED') ? 'selected' : ''; ?>>Result Published</option>
        <option value="RESULT_CORRECTED" <?php echo ($filterType === 'RESULT_CORRECTED') ? 'selected' : ''; ?>>Result Corrected</option>
        <option value="POINTS_UPDATED" <?php echo ($filterType === 'POINTS_UPDATED') ? 'selected' : ''; ?>>Points Table Updated</option>
        <option value="ANNOUNCEMENT" <?php echo ($filterType === 'ANNOUNCEMENT') ? 'selected' : ''; ?>>Announcement</option>
        <option value="SYSTEM" <?php echo ($filterType === 'SYSTEM') ? 'selected' : ''; ?>>System Alert</option>
      </select>
    </div>

    <div style="display: flex; align-items: center; gap: 8px;">
      <label style="font-weight: 700; font-size: 0.85rem; color: var(--text-muted);">Read Status:</label>
      <select name="status" class="form-control" style="padding: 6px 12px; width: auto;" onchange="this.form.submit();">
        <option value="all" <?php echo ($filterStatus === 'all') ? 'selected' : ''; ?>>All Statuses</option>
        <option value="unread" <?php echo ($filterStatus === 'unread') ? 'selected' : ''; ?>>Unread Only</option>
        <option value="read" <?php echo ($filterStatus === 'read') ? 'selected' : ''; ?>>Read Only</option>
      </select>
    </div>

    <?php if ($filterType !== 'all' || $filterStatus !== 'all'): ?>
      <a href="admin/notifications.php" class="btn btn-secondary btn-sm" style="margin-left: auto;">Reset Filters</a>
    <?php endif; ?>
  </form>
</div>

<!-- Notification Feed -->
<div class="card">
  <?php if (empty($notifications)): ?>
    <div style="text-align: center; padding: 48px 20px;">
      <div style="font-size: 3rem; margin-bottom: 12px;">📭</div>
      <h3 style="color: #fff; font-weight: 700; margin-bottom: 8px;">No notifications found.</h3>
      <p style="color: var(--text-muted); font-size: 0.9rem;">
        <?php echo ($filterStatus === 'unread') ? 'No unread notifications.' : 'No new notifications available for your account.'; ?>
      </p>
    </div>
  <?php else: ?>
    <div style="display: flex; flex-direction: column; gap: 12px;">
      <?php foreach ($notifications as $n): ?>
        <?php
          $isUnread = ($n['is_read'] == 0);
          $bgStyle = $isUnread ? 'background: rgba(0, 230, 118, 0.04); border-left: 4px solid var(--accent-green);' : 'background: var(--bg-card-subtle); border-left: 4px solid var(--border-subtle);';
          $priorityBadge = 'badge-generic';
          if ($n['priority'] === 'Urgent' || $n['priority'] === 'High') $priorityBadge = 'badge-danger';
          elseif ($n['priority'] === 'Important') $priorityBadge = 'badge-warning';
        ?>
        <div style="padding: 16px 20px; border-radius: var(--radius-md); border: 1px solid var(--border-subtle); <?php echo $bgStyle; ?> display: flex; justify-content: space-between; align-items: flex-start; gap: 16px;">
          <div style="flex: 1;">
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 6px;">
              <span class="status-badge <?php echo $priorityBadge; ?>" style="font-size: 0.72rem;">
                <?php echo htmlspecialchars($n['type']); ?>
              </span>
              <?php if ($isUnread): ?>
                <span class="status-badge badge-live" style="font-size: 0.68rem; padding: 2px 6px;">UNREAD</span>
              <?php endif; ?>
              <span style="font-size: 0.8rem; color: var(--text-dim); margin-left: auto;">
                🕒 <?php echo date('M d, Y h:i A', strtotime($n['created_at'])); ?>
              </span>
            </div>

            <h4 style="font-size: 1.05rem; font-weight: 800; color: #fff; margin-bottom: 6px;">
              <?php echo htmlspecialchars($n['title']); ?>
            </h4>

            <p style="color: var(--text-muted); font-size: 0.9rem; line-height: 1.5; margin: 0 0 10px 0;">
              <?php echo htmlspecialchars($n['message']); ?>
            </p>

            <?php if (!empty($n['link_url'])): ?>
              <a href="<?php echo htmlspecialchars(BASE_URL . '/' . ltrim($n['link_url'], '/')); ?>" class="btn btn-secondary btn-sm" style="display: inline-flex; align-items: center; gap: 6px;">
                🔗 View Event / Detail →
              </a>
            <?php endif; ?>
          </div>

          <div style="display: flex; flex-direction: column; gap: 6px; align-items: flex-end;">
            <?php if ($isUnread): ?>
              <form action="" method="POST" style="margin: 0;">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                <input type="hidden" name="action" value="mark_read">
                <input type="hidden" name="notification_id" value="<?php echo $n['id']; ?>">
                <button type="submit" class="btn btn-secondary btn-sm" title="Mark as read">
                  ✓ Mark Read
                </button>
              </form>
            <?php endif; ?>

            <form action="" method="POST" style="margin: 0;" onsubmit="return confirm('Delete this notification?');">
              <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="notification_id" value="<?php echo $n['id']; ?>">
              <button type="submit" class="btn btn-secondary btn-sm" style="color: var(--accent-red); padding: 4px 8px; font-size: 0.75rem;" title="Delete">
                🗑 Delete
              </button>
            </form>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<!-- Admin Broadcast Modal -->
<?php if ($isAdmin): ?>
<div id="broadcastModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.75); z-index: 9999; justify-content: center; align-items: center; padding: 20px;">
  <div style="background: var(--bg-card); border: 1px solid var(--border-subtle); border-radius: var(--radius-lg); width: 100%; max-width: 540px; padding: 24px; box-shadow: 0 10px 30px rgba(0,0,0,0.5);">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
      <h3 style="margin: 0; color: #fff; font-weight: 800;">📢 Broadcast Championship Alert</h3>
      <button onclick="document.getElementById('broadcastModal').style.display='none';" style="background: none; border: none; color: var(--text-muted); font-size: 1.5rem; cursor: pointer;">&times;</button>
    </div>
    
    <form action="" method="POST" onsubmit="return confirm('Broadcast this notification to all targeted users?');">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
      <input type="hidden" name="action" value="broadcast">

      <div class="form-group">
        <label>Alert Title / Headline *</label>
        <input type="text" name="title" class="form-control" placeholder="e.g. Schedule Update: Rain Delay" required>
      </div>

      <div class="form-group">
        <label>Message Content *</label>
        <textarea name="message" class="form-control" rows="3" placeholder="Enter message body for broadcast..." required></textarea>
      </div>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;" class="form-group">
        <div>
          <label>Target Audience</label>
          <select name="target_group" class="form-control">
            <option value="Everyone">Everyone (All Users & Public)</option>
            <option value="team_manager">Team Managers</option>
            <option value="official">Officials</option>
            <option value="scorer">Scorers</option>
            <option value="player">Players</option>
          </select>
        </div>
        <div>
          <label>Priority</label>
          <select name="priority" class="form-control">
            <option value="Normal">Normal</option>
            <option value="Important">Important</option>
            <option value="Urgent">Urgent / Emergency</option>
          </select>
        </div>
      </div>

      <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('broadcastModal').style.display='none';">Cancel</button>
        <button type="submit" class="btn btn-primary">📢 Broadcast Now</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
