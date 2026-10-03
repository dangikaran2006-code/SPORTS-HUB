<?php
/**
 * SportsHub - Admin Announcement Control Center
 */
$currentPage = 'announcements';
$pageTitle = 'Announcement Control';

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdminAccess();

$announcements = [
    [
        'id' => 1,
        'title' => '🏏 Cricket Championship Final Rescheduled Time',
        'message' => 'The Grand Cricket Final between Computer Engineering (CSE) and Mechanical Engineering (ME) will commence at 4:00 PM IST today at the Main Ground.',
        'sport' => 'Cricket',
        'priority' => 'Urgent',
        'status' => 'Published',
        'date' => 'Oct 03, 2026'
    ],
    [
        'id' => 2,
        'title' => '🏸 Badminton Singles Venue Allocation Update',
        'message' => 'Due to rain forecast, all Badminton Men Singles & Mixed Doubles matches have been shifted to Indoor Sports Complex Court 1 & Court 2.',
        'sport' => 'Badminton',
        'priority' => 'Important',
        'status' => 'Published',
        'date' => 'Oct 02, 2026'
    ],
    [
        'id' => 3,
        'title' => '🏆 Overall Department Points Calculation Rules Published',
        'message' => 'The Sports Board has published the official Point Matrix for AY 2025-2026. Team Sports Gold: 10pts, Silver: 7pts, Bronze: 5pts.',
        'sport' => 'General',
        'priority' => 'Normal',
        'status' => 'Published',
        'date' => 'Oct 01, 2026'
    ],
];

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $priority = $_POST['priority'] ?? 'Normal';
    $sport = $_POST['sport'] ?? 'General';
    if (!empty($title) && !empty($message)) {
        $announcements[] = [
            'id' => count($announcements) + 1,
            'title' => $title,
            'message' => $message,
            'sport' => $sport,
            'priority' => $priority,
            'status' => 'Published',
            'date' => date('M d, Y')
        ];
        $msg = "Announcement <strong>" . htmlspecialchars($title) . "</strong> published to public website successfully.";
    }
}

include_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-header">
  <div class="dashboard-title-group">
    <h1>Championship Announcement Control Center</h1>
    <p>Publish official bulletins, priority notices, and schedule updates to the public spectator interface.</p>
  </div>
</div>

<?php if (!empty($msg)): ?>
  <div class="auth-alert auth-alert-success" style="margin-bottom: 24px; background: rgba(0, 230, 118, 0.15); border: 1px solid rgba(0, 230, 118, 0.3); color: var(--accent-green); padding: 12px 16px; border-radius: 8px;">
    <span><?php echo $msg; ?></span>
  </div>
<?php endif; ?>

<!-- Create Announcement Form -->
<div class="card" style="margin-bottom: 28px;">
  <div class="section-header" style="margin-bottom: 14px;">
    <h2>📢 Publish New Announcement</h2>
  </div>
  <form action="" method="POST" style="display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 16px;">
    <div class="form-group" style="grid-column: span 3; margin: 0;">
      <label>Announcement Headline / Title</label>
      <input type="text" name="title" class="form-control" placeholder="e.g. Cricket Final Starts at 4:00 PM" required>
    </div>
    
    <div class="form-group" style="grid-column: span 3; margin: 0;">
      <label>Announcement Message Body</label>
      <textarea name="message" class="form-control" rows="3" placeholder="Enter bulletin content for spectators..." required></textarea>
    </div>

    <div class="form-group" style="margin: 0;">
      <label>Sport Focus</label>
      <select name="sport" class="form-control">
        <option value="General">General / Championship Wide</option>
        <option value="Cricket">Cricket</option>
        <option value="Football">Football</option>
        <option value="Kabaddi">Kabaddi</option>
        <option value="Badminton">Badminton</option>
        <option value="Athletics">Athletics</option>
      </select>
    </div>

    <div class="form-group" style="margin: 0;">
      <label>Priority Level</label>
      <select name="priority" class="form-control">
        <option value="Normal">Normal Priority</option>
        <option value="Important">Important</option>
        <option value="Urgent">Urgent / Red Alert</option>
      </select>
    </div>

    <div style="display: flex; align-items: flex-end;">
      <button type="submit" class="btn btn-primary" style="width: 100%;">
        📢 Publish Announcement
      </button>
    </div>
  </form>
</div>

<!-- Published Announcements List Table -->
<div class="card">
  <div class="section-header" style="margin-bottom: 16px;">
    <h2>Published Announcements Directory</h2>
  </div>
  <div class="table-responsive">
    <table class="sports-table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Title & Content</th>
          <th>Sport</th>
          <th>Priority</th>
          <th>Date</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($announcements as $a): ?>
          <tr>
            <td><code>#<?php echo $a['id']; ?></code></td>
            <td>
              <div style="font-weight: 700; color: #fff; margin-bottom: 4px;"><?php echo htmlspecialchars($a['title']); ?></div>
              <div style="font-size: 0.82rem; color: var(--text-muted);"><?php echo htmlspecialchars($a['message']); ?></div>
            </td>
            <td>
              <span class="badge" style="background: rgba(0, 230, 118, 0.15); color: var(--accent-green); font-weight: 700;">
                <?php echo htmlspecialchars($a['sport']); ?>
              </span>
            </td>
            <td>
              <span class="badge" style="background: <?php echo $a['priority'] === 'Urgent' ? 'rgba(239, 68, 68, 0.15)' : 'rgba(245, 158, 11, 0.15)'; ?>; color: <?php echo $a['priority'] === 'Urgent' ? 'var(--accent-red)' : 'var(--accent-amber)'; ?>; font-weight: 700;">
                <?php echo htmlspecialchars($a['priority']); ?>
              </span>
            </td>
            <td><?php echo htmlspecialchars($a['date']); ?></td>
            <td><span class="status-badge badge-active">PUBLISHED</span></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
