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

$db = getDB();
$msg = '';
$error = '';
$csrfToken = generateCsrfToken();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    $action = $_POST['action'] ?? 'create';

    if (!validateCsrfToken($token)) {
        $error = 'Security validation failed (Invalid CSRF Token).';
    } else {
        if ($action === 'create') {
            $title = trim($_POST['title'] ?? '');
            $message = trim($_POST['message'] ?? '');
            $priority = $_POST['priority'] ?? 'Normal';
            $sport = $_POST['sport'] ?? 'General';

            if (!empty($title) && !empty($message)) {
                $newId = insert('announcements', [
                    'title' => $title,
                    'message' => $message,
                    'sport' => $sport,
                    'priority' => $priority,
                    'status' => 'Published'
                ]);
                logAuditAction('Announcement Published', 'Announcement', $newId, "Published announcement '{$title}'");
                $msg = "Announcement <strong>" . htmlspecialchars($title) . "</strong> published to public website successfully.";
            } else {
                $error = 'Please fill out both headline and message body.';
            }
        } elseif ($action === 'delete') {
            $annId = intval($_POST['announcement_id'] ?? 0);
            if ($annId > 0) {
                delete('announcements', 'id = :id', [':id' => $annId]);
                logAuditAction('Announcement Deleted', 'Announcement', $annId, "Deleted announcement ID {$annId}");
                $msg = "Announcement ID #{$annId} deleted successfully.";
            }
        }
    }
}

// Fetch announcements from MySQL DB
$announcements = [];
if ($db->getConnection()) {
    $announcements = fetchAll("SELECT * FROM announcements ORDER BY id DESC");
}

if (empty($announcements)) {
    $announcements = [
        [
            'id' => 1,
            'title' => '🏏 Cricket Championship Final Rescheduled Time',
            'message' => 'The Grand Cricket Final between Computer Engineering (CSE) and Mechanical Engineering (ME) will commence at 4:00 PM IST today at the Main Ground.',
            'sport' => 'Cricket',
            'priority' => 'Urgent',
            'status' => 'Published',
            'created_at' => date('Y-m-d H:i:s')
        ],
        [
            'id' => 2,
            'title' => '🏸 Badminton Singles Venue Allocation Update',
            'message' => 'Due to rain forecast, all Badminton Men Singles & Mixed Doubles matches have been shifted to Indoor Sports Complex Court 1 & Court 2.',
            'sport' => 'Badminton',
            'priority' => 'Important',
            'status' => 'Published',
            'created_at' => date('Y-m-d H:i:s', strtotime('-1 day'))
        ]
    ];
}

// Fetch active sports for dropdown
$sportsList = [];
if ($db->getConnection()) {
    $sportsList = fetchAll("SELECT name FROM sports ORDER BY name ASC");
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
  <div class="auth-alert auth-alert-success" style="margin-bottom: 24px;">
    <span><?php echo $msg; ?></span>
  </div>
<?php endif; ?>

<?php if (!empty($error)): ?>
  <div class="auth-alert auth-alert-danger" style="margin-bottom: 24px;">
    <span><?php echo htmlspecialchars($error); ?></span>
  </div>
<?php endif; ?>

<!-- Create Announcement Form -->
<div class="card" style="margin-bottom: 28px;">
  <div class="section-header" style="margin-bottom: 14px;">
    <h2>📢 Publish New Announcement</h2>
  </div>
  <form action="" method="POST" style="display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 16px;">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
    <input type="hidden" name="action" value="create">

    <div class="form-group" style="grid-column: span 3; margin: 0;">
      <label>Announcement Headline / Title *</label>
      <input type="text" name="title" class="form-control" placeholder="e.g. Cricket Final Starts at 4:00 PM" required>
    </div>
    
    <div class="form-group" style="grid-column: span 3; margin: 0;">
      <label>Announcement Message Body *</label>
      <textarea name="message" class="form-control" rows="3" placeholder="Enter bulletin content for spectators..." required></textarea>
    </div>

    <div class="form-group" style="margin: 0;">
      <label>Sport Focus</label>
      <select name="sport" class="form-control">
        <option value="General">General / Championship Wide</option>
        <?php foreach ($sportsList as $sp): ?>
          <option value="<?php echo htmlspecialchars($sp['name']); ?>"><?php echo htmlspecialchars($sp['name']); ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="form-group" style="margin: 0;">
      <label>Notice Priority</label>
      <select name="priority" class="form-control">
        <option value="Normal">Normal Notice</option>
        <option value="Important">Important Update</option>
        <option value="Urgent">Urgent / Alert</option>
      </select>
    </div>

    <div class="form-group" style="margin: 0; display: flex; align-items: flex-end;">
      <button type="submit" class="btn btn-primary" style="width: 100%; height: 42px;">
        Publish Notice
      </button>
    </div>
  </form>
</div>

<!-- Announcements Table -->
<div class="card">
  <div class="section-header" style="margin-bottom: 14px;">
    <h2>Published Bulletins</h2>
  </div>

  <div class="table-responsive">
    <table class="sports-table">
      <thead>
        <tr>
          <th>Headline & Details</th>
          <th>Sport Category</th>
          <th>Priority</th>
          <th>Date</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($announcements as $ann): ?>
          <tr>
            <td>
              <strong style="color: var(--text-main); font-size: 0.98rem; display: block; margin-bottom: 4px;">
                <?php echo htmlspecialchars($ann['title']); ?>
              </strong>
              <div style="font-size: 0.85rem; color: var(--text-muted); line-height: 1.5;">
                <?php echo htmlspecialchars($ann['message']); ?>
              </div>
            </td>
            <td><?php echo getSportBadge($ann['sport']); ?></td>
            <td>
              <?php 
                $pClass = 'badge-generic';
                if ($ann['priority'] === 'Urgent') $pClass = 'badge-danger';
                elseif ($ann['priority'] === 'Important') $pClass = 'badge-warning';
              ?>
              <span class="status-badge <?php echo $pClass; ?>"><?php echo htmlspecialchars($ann['priority']); ?></span>
            </td>
            <td style="font-size: 0.85rem; color: var(--text-muted); white-space: nowrap;">
              <?php echo date('M d, Y', strtotime($ann['created_at'] ?? 'now')); ?>
            </td>
            <td>
              <form action="" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this announcement?');">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="announcement_id" value="<?php echo $ann['id']; ?>">
                <button type="submit" class="btn btn-secondary btn-sm" style="color: var(--accent-red);">
                  Delete
                </button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
