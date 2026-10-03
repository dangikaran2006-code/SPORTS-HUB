<?php
/**
 * SportsHub - Admin Announcement Control Center
 */
$currentPage = 'announcements';
$pageTitle = 'Announcement Control';

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/notification-helper.php';

requireAdminAccess();

$db = getDB();
$msg = '';
$error = '';
$csrfToken = generateCsrfToken();

// Edit state
$editAnnouncement = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    $action = $_POST['action'] ?? 'create';

    if (!validateCsrfToken($token)) {
        $error = 'Security validation failed (Invalid CSRF Token).';
    } else {
        if ($action === 'create' || $action === 'update') {
            $title = trim($_POST['title'] ?? '');
            $message = trim($_POST['message'] ?? '');
            $priority = $_POST['priority'] ?? 'Normal';
            $sport = $_POST['sport'] ?? 'General';
            $targetAudience = $_POST['target_audience'] ?? 'Everyone';
            $department = $_POST['department'] ?? 'All';
            $status = $_POST['status'] ?? 'Published';
            $annId = intval($_POST['announcement_id'] ?? 0);

            if (!empty($title) && !empty($message)) {
                if ($action === 'create') {
                    $newId = insert('announcements', [
                        'title'           => $title,
                        'message'         => $message,
                        'sport'           => $sport,
                        'target_audience' => $targetAudience,
                        'department'      => $department,
                        'priority'        => $priority,
                        'status'          => $status,
                        'created_at'      => date('Y-m-d H:i:s')
                    ]);
                    logAuditAction('Announcement Created', 'Announcement', $newId, "Created announcement '{$title}' ({$status})");

                    if ($status === 'Published') {
                        broadcastNotification($title, $message, 'ANNOUNCEMENT', 'public/announcements.php', $priority, $targetAudience);
                    }
                    $msg = "Announcement <strong>" . htmlspecialchars($title) . "</strong> created successfully.";
                } else {
                    update('announcements', [
                        'title'           => $title,
                        'message'         => $message,
                        'sport'           => $sport,
                        'target_audience' => $targetAudience,
                        'department'      => $department,
                        'priority'        => $priority,
                        'status'          => $status
                    ], 'id = :id', [':id' => $annId]);

                    logAuditAction('Announcement Updated', 'Announcement', $annId, "Updated announcement ID {$annId}");
                    $msg = "Announcement ID #{$annId} updated successfully.";
                }
            } else {
                $error = 'Please fill out both headline and message body.';
            }
        } elseif ($action === 'toggle_status') {
            $annId = intval($_POST['announcement_id'] ?? 0);
            $newStatus = $_POST['new_status'] ?? 'Published';
            if ($annId > 0) {
                update('announcements', ['status' => $newStatus], 'id = :id', [':id' => $annId]);
                logAuditAction('Announcement Status Changed', 'Announcement', $annId, "Changed status to {$newStatus}");
                $msg = "Announcement status updated to <strong>{$newStatus}</strong>.";
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

// Handle Edit Fetch
if (isset($_GET['edit_id'])) {
    $editId = intval($_GET['edit_id']);
    $editAnnouncement = fetchOne("SELECT * FROM announcements WHERE id = :id", [':id' => $editId]);
}

// Fetch announcements from MySQL DB
$announcements = fetchAll("SELECT * FROM announcements ORDER BY id DESC");

// Fetch active sports & departments for dropdowns
$sportsList = fetchAll("SELECT name FROM sports ORDER BY name ASC");
$departmentsList = fetchAll("SELECT name FROM departments ORDER BY name ASC");

include_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-header">
  <div class="dashboard-title-group">
    <h1>Championship Announcement Control Center</h1>
    <p>Publish official bulletins, priority notices, and schedule updates to targeted audiences or the public interface.</p>
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

<!-- Create / Edit Announcement Form -->
<div class="card" style="margin-bottom: 28px;">
  <div class="section-header" style="margin-bottom: 14px; display: flex; justify-content: space-between; align-items: center;">
    <h2>📢 <?php echo $editAnnouncement ? 'Edit Announcement #' . $editAnnouncement['id'] : 'Publish New Announcement'; ?></h2>
    <?php if ($editAnnouncement): ?>
      <a href="admin/announcements.php" class="btn btn-secondary btn-sm">Cancel Edit</a>
    <?php endif; ?>
  </div>

  <form action="" method="POST" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px;">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
    <input type="hidden" name="action" value="<?php echo $editAnnouncement ? 'update' : 'create'; ?>">
    <?php if ($editAnnouncement): ?>
      <input type="hidden" name="announcement_id" value="<?php echo $editAnnouncement['id']; ?>">
    <?php endif; ?>

    <div class="form-group" style="grid-column: span 3; margin: 0;">
      <label>Announcement Headline / Title *</label>
      <input type="text" name="title" class="form-control" placeholder="e.g. Cricket Final Starts at 4:00 PM" value="<?php echo htmlspecialchars($editAnnouncement['title'] ?? ''); ?>" required>
    </div>
    
    <div class="form-group" style="grid-column: span 3; margin: 0;">
      <label>Announcement Message Body *</label>
      <textarea name="message" class="form-control" rows="3" placeholder="Enter bulletin content..." required><?php echo htmlspecialchars($editAnnouncement['message'] ?? ''); ?></textarea>
    </div>

    <div class="form-group" style="margin: 0;">
      <label>Sport Focus</label>
      <select name="sport" class="form-control">
        <option value="General">General / Championship Wide</option>
        <?php foreach ($sportsList as $sp): ?>
          <option value="<?php echo htmlspecialchars($sp['name']); ?>" <?php echo (($editAnnouncement['sport'] ?? '') === $sp['name']) ? 'selected' : ''; ?>>
            <?php echo htmlspecialchars($sp['name']); ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="form-group" style="margin: 0;">
      <label>Target Audience</label>
      <select name="target_audience" class="form-control">
        <option value="Everyone" <?php echo (($editAnnouncement['target_audience'] ?? '') === 'Everyone') ? 'selected' : ''; ?>>Everyone (Public & All Roles)</option>
        <option value="Department" <?php echo (($editAnnouncement['target_audience'] ?? '') === 'Department') ? 'selected' : ''; ?>>Specific Department</option>
        <option value="officials" <?php echo (($editAnnouncement['target_audience'] ?? '') === 'officials') ? 'selected' : ''; ?>>Match Officials & Scorers</option>
        <option value="team_manager" <?php echo (($editAnnouncement['target_audience'] ?? '') === 'team_manager') ? 'selected' : ''; ?>>Team Managers</option>
        <option value="player" <?php echo (($editAnnouncement['target_audience'] ?? '') === 'player') ? 'selected' : ''; ?>>Registered Players</option>
      </select>
    </div>

    <div class="form-group" style="margin: 0;">
      <label>Department Filter</label>
      <select name="department" class="form-control">
        <option value="All">All Departments</option>
        <?php foreach ($departmentsList as $d): ?>
          <option value="<?php echo htmlspecialchars($d['name']); ?>" <?php echo (($editAnnouncement['department'] ?? '') === $d['name']) ? 'selected' : ''; ?>>
            <?php echo htmlspecialchars($d['name']); ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="form-group" style="margin: 0;">
      <label>Notice Priority</label>
      <select name="priority" class="form-control">
        <option value="Normal" <?php echo (($editAnnouncement['priority'] ?? '') === 'Normal') ? 'selected' : ''; ?>>Normal Notice</option>
        <option value="Important" <?php echo (($editAnnouncement['priority'] ?? '') === 'Important') ? 'selected' : ''; ?>>Important Update</option>
        <option value="Urgent" <?php echo (($editAnnouncement['priority'] ?? '') === 'Urgent') ? 'selected' : ''; ?>>Urgent / Alert</option>
      </select>
    </div>

    <div class="form-group" style="margin: 0;">
      <label>Publication Status</label>
      <select name="status" class="form-control">
        <option value="Published" <?php echo (($editAnnouncement['status'] ?? '') === 'Published') ? 'selected' : ''; ?>>Published</option>
        <option value="Draft" <?php echo (($editAnnouncement['status'] ?? '') === 'Draft') ? 'selected' : ''; ?>>Draft / Unpublished</option>
        <option value="Archived" <?php echo (($editAnnouncement['status'] ?? '') === 'Archived') ? 'selected' : ''; ?>>Archived</option>
      </select>
    </div>

    <div class="form-group" style="margin: 0; display: flex; align-items: flex-end;">
      <button type="submit" class="btn btn-primary" style="width: 100%; height: 42px;">
        <?php echo $editAnnouncement ? 'Save Announcement Changes' : 'Publish Announcement'; ?>
      </button>
    </div>
  </form>
</div>

<!-- Announcements Table -->
<div class="card">
  <div class="section-header" style="margin-bottom: 14px;">
    <h2>Published & Draft Bulletins</h2>
  </div>

  <div class="table-responsive">
    <table class="sports-table">
      <thead>
        <tr>
          <th>Headline & Message</th>
          <th>Audience / Sport</th>
          <th>Priority</th>
          <th>Status</th>
          <th>Created Date</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($announcements)): ?>
          <tr>
            <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 30px;">
              No announcements available. Create your first bulletin above.
            </td>
          </tr>
        <?php else: ?>
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
              <td>
                <div style="display: flex; flex-direction: column; gap: 4px;">
                  <?php echo getSportBadge($ann['sport']); ?>
                  <span style="font-size: 0.75rem; color: var(--text-dim);">🎯 <?php echo htmlspecialchars($ann['target_audience'] ?? 'Everyone'); ?></span>
                </div>
              </td>
              <td>
                <?php 
                  $pClass = 'badge-generic';
                  if ($ann['priority'] === 'Urgent') $pClass = 'badge-danger';
                  elseif ($ann['priority'] === 'Important') $pClass = 'badge-warning';
                ?>
                <span class="status-badge <?php echo $pClass; ?>"><?php echo htmlspecialchars($ann['priority']); ?></span>
              </td>
              <td>
                <?php
                  $sClass = ($ann['status'] === 'Published') ? 'badge-completed' : 'badge-generic';
                ?>
                <span class="status-badge <?php echo $sClass; ?>"><?php echo htmlspecialchars($ann['status']); ?></span>
              </td>
              <td style="font-size: 0.85rem; color: var(--text-muted); white-space: nowrap;">
                <?php echo date('M d, Y', strtotime($ann['created_at'] ?? 'now')); ?>
              </td>
              <td>
                <div style="display: flex; gap: 6px; align-items: center;">
                  <a href="admin/announcements.php?edit_id=<?php echo $ann['id']; ?>" class="btn btn-secondary btn-sm">Edit</a>
                  
                  <form action="" method="POST" style="display:inline;">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                    <input type="hidden" name="action" value="toggle_status">
                    <input type="hidden" name="announcement_id" value="<?php echo $ann['id']; ?>">
                    <input type="hidden" name="new_status" value="<?php echo ($ann['status'] === 'Published') ? 'Draft' : 'Published'; ?>">
                    <button type="submit" class="btn btn-secondary btn-sm" style="font-size: 0.75rem;">
                      <?php echo ($ann['status'] === 'Published') ? 'Unpublish' : 'Publish'; ?>
                    </button>
                  </form>

                  <form action="" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this announcement?');">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="announcement_id" value="<?php echo $ann['id']; ?>">
                    <button type="submit" class="btn btn-secondary btn-sm" style="color: var(--accent-red); padding: 4px 8px; font-size: 0.75rem;">
                      Delete
                    </button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
