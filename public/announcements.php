<?php
/**
 * SportsHub - Public Championship Announcements Page
 */
$currentPage = 'announcements';
$pageTitle = 'Championship Announcements & Alerts';

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';

$db = getDB();
$announcements = [];

if ($db->getConnection()) {
    $announcements = fetchAll("SELECT * FROM announcements WHERE LOWER(status) = 'published' ORDER BY id DESC");
}

if (empty($announcements)) {
    $announcements = [
        [
            'id' => 1,
            'title' => '🏏 Cricket Championship Final Rescheduled Time',
            'message' => 'The Grand Cricket Final between Computer Engineering (CSE) and Mechanical Engineering (ME) will commence at 4:00 PM IST today at the Main Ground. All students and faculty are invited.',
            'sport' => 'Cricket',
            'priority' => 'Urgent',
            'created_at' => date('Y-m-d H:i:s')
        ],
        [
            'id' => 2,
            'title' => '🏸 Badminton Singles Venue Allocation Update',
            'message' => 'Due to rain forecast, all Badminton Men Singles & Mixed Doubles matches have been shifted to Indoor Sports Complex Court 1 & Court 2.',
            'sport' => 'Badminton',
            'priority' => 'Important',
            'created_at' => date('Y-m-d H:i:s', strtotime('-1 day'))
        ]
    ];
}

include_once __DIR__ . '/../includes/public-header.php';
?>

<div style="margin-bottom: 28px;">
  <h1 style="font-size: 1.8rem; font-weight: 800; color: #fff; margin-bottom: 4px;">📢 Official Championship Bulletins</h1>
  <p style="color: var(--text-muted); font-size: 0.9rem;">Important notifications, schedule updates, and tournament announcements from the Sports Authority.</p>
</div>

<div style="display: flex; flex-direction: column; gap: 16px;">
  <?php foreach ($announcements as $a): ?>
    <div style="background: var(--bg-card); border: 1px solid var(--border-subtle); border-radius: var(--radius-lg); padding: 20px; border-left: 4px solid <?php echo ($a['priority'] === 'Urgent' || $a['priority'] === 'High') ? 'var(--accent-red)' : 'var(--accent-green)'; ?>;">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
        <span class="badge" style="background: rgba(0, 230, 118, 0.15); color: var(--accent-green); font-weight: 700;">
          <?php echo htmlspecialchars($a['sport']); ?>
        </span>
        <span style="font-size: 0.78rem; color: var(--text-dim);">📅 <?php echo date('M d, Y', strtotime($a['created_at'] ?? 'now')); ?></span>
      </div>

      <h3 style="font-size: 1.15rem; font-weight: 800; color: #fff; margin-bottom: 8px;">
        <?php echo htmlspecialchars($a['title']); ?>
      </h3>

      <p style="color: var(--text-muted); font-size: 0.92rem; line-height: 1.6; margin: 0;">
        <?php echo htmlspecialchars($a['message']); ?>
      </p>
    </div>
  <?php endforeach; ?>
</div>

<?php include_once __DIR__ . '/../includes/public-footer.php'; ?>
