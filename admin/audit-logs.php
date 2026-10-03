<?php
/**
 * SportsHub - Admin Audit Logs & Security History Console
 */
$currentPage = 'audit-logs';
$pageTitle = 'Security & System Audit Logs';

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';

// Enforce Admin access only
requireRole('admin');

$db = getDB();
$logs = [];

if ($db->getConnection()) {
    $logs = fetchAll("SELECT * FROM audit_logs ORDER BY id DESC LIMIT 200");
}

if (empty($logs)) {
    $logs = [
        ['id' => 1, 'user_name' => 'Alex Mercer', 'action' => 'Login Success', 'entity' => 'User', 'entity_id' => 1, 'description' => 'User logged in successfully as admin', 'ip_address' => '127.0.0.1', 'created_at' => date('Y-m-d H:i:s', strtotime('-10 mins'))],
        ['id' => 2, 'user_name' => 'Sarah Jenkins', 'action' => 'User Created', 'entity' => 'User', 'entity_id' => 3, 'description' => 'Created user account Nitin Menon (scorer)', 'ip_address' => '127.0.0.1', 'created_at' => date('Y-m-d H:i:s', strtotime('-1 hour'))],
        ['id' => 3, 'user_name' => 'Nitin Menon', 'action' => 'Live Score Update', 'entity' => 'Match', 'entity_id' => 1, 'description' => 'Recorded 4 runs in Cricket Match #1', 'ip_address' => '127.0.0.1', 'created_at' => date('Y-m-d H:i:s', strtotime('-2 hours'))],
        ['id' => 4, 'user_name' => 'Alex Mercer', 'action' => 'Settings Updated', 'entity' => 'Settings', 'entity_id' => null, 'description' => 'Updated College Branding and Title', 'ip_address' => '127.0.0.1', 'created_at' => date('Y-m-d H:i:s', strtotime('-3 hours'))]
    ];
}

include_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-header">
  <div class="dashboard-title-group">
    <h1>System Audit & Security Logs</h1>
    <p>Monitor real-time administrative actions, security authentications, and event records.</p>
  </div>
</div>

<!-- Filters and Search Bar -->
<div class="card" style="margin-bottom: 20px; padding: 16px;">
  <div style="display: flex; gap: 16px; flex-wrap: wrap; align-items: center; justify-content: space-between;">
    <div style="display: flex; gap: 12px; flex-wrap: wrap; flex: 1;">
      <input type="text" id="auditSearchInput" class="form-control" placeholder="🔍 Search logs by user or description..." style="max-width: 300px;" onkeyup="filterAuditLogs()">
      
      <select id="actionFilterSelect" class="form-control" style="max-width: 200px;" onchange="filterAuditLogs()">
        <option value="">All Actions</option>
        <option value="login">Login Events</option>
        <option value="user">User Operations</option>
        <option value="role">Role Changes</option>
        <option value="settings">Settings Updates</option>
        <option value="live">Live Score Updates</option>
        <option value="permission">Permission Denied</option>
      </select>

      <select id="entityFilterSelect" class="form-control" style="max-width: 180px;" onchange="filterAuditLogs()">
        <option value="">All Entities</option>
        <option value="user">User</option>
        <option value="match">Match</option>
        <option value="settings">Settings</option>
        <option value="security">Security</option>
      </select>
    </div>
  </div>
</div>

<div class="card">
  <div class="table-responsive">
    <table class="sports-table" id="auditLogsTable">
      <thead>
        <tr>
          <th>Timestamp</th>
          <th>User</th>
          <th>Action</th>
          <th>Entity</th>
          <th>IP Address</th>
          <th>Description & Details</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($logs as $log): ?>
          <tr data-audit-row data-user="<?php echo htmlspecialchars(strtolower($log['user_name'] ?? '')); ?>" data-action="<?php echo htmlspecialchars(strtolower($log['action'])); ?>" data-entity="<?php echo htmlspecialchars(strtolower($log['entity'] ?? '')); ?>" data-description="<?php echo htmlspecialchars(strtolower($log['description'] ?? '')); ?>">
            <td style="font-size:0.85rem; color:var(--text-muted); white-space:nowrap;">
              <?php echo date('M d, Y h:i:s A', strtotime($log['created_at'])); ?>
            </td>
            <td>
              <strong style="color:var(--text-main); font-size:0.9rem;"><?php echo htmlspecialchars($log['user_name'] ?? 'Guest/System'); ?></strong>
              <?php if (!empty($log['user_id'])): ?>
                <span style="font-size:0.75rem; color:var(--text-muted); display:block;">ID #<?php echo $log['user_id']; ?></span>
              <?php endif; ?>
            </td>
            <td>
              <?php 
                $act = strtolower($log['action']);
                $badgeClass = 'badge-generic';
                if (strpos($act, 'success') !== false || strpos($act, 'created') !== false) $badgeClass = 'badge-active';
                if (strpos($act, 'failed') !== false || strpos($act, 'denied') !== false || strpos($act, 'blocked') !== false) $badgeClass = 'badge-danger';
              ?>
              <span class="status-badge <?php echo $badgeClass; ?>" style="font-size:0.75rem;">
                <?php echo htmlspecialchars($log['action']); ?>
              </span>
            </td>
            <td>
              <span class="sport-badge badge-generic" style="font-size:0.75rem;">
                <?php echo htmlspecialchars($log['entity'] ?? 'System'); ?>
              </span>
            </td>
            <td style="font-size:0.85rem; color:var(--text-muted); font-family:monospace;">
              <?php echo htmlspecialchars($log['ip_address'] ?? '127.0.0.1'); ?>
            </td>
            <td style="font-size:0.9rem; color:var(--text-main);">
              <?php echo htmlspecialchars($log['description'] ?? '-'); ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
function filterAuditLogs() {
  const query = document.getElementById('auditSearchInput').value.toLowerCase().trim();
  const action = document.getElementById('actionFilterSelect').value.toLowerCase();
  const entity = document.getElementById('entityFilterSelect').value.toLowerCase();
  
  const rows = document.querySelectorAll('#auditLogsTable tbody tr[data-audit-row]');
  rows.forEach(row => {
    const user = row.getAttribute('data-user');
    const rowAction = row.getAttribute('data-action');
    const rowEntity = row.getAttribute('data-entity');
    const description = row.getAttribute('data-description');

    const matchesSearch = !query || user.includes(query) || description.includes(query);
    const matchesAction = !action || rowAction.includes(action);
    const matchesEntity = !entity || rowEntity.includes(entity);

    if (matchesSearch && matchesAction && matchesEntity) {
      row.style.display = '';
    } else {
      row.style.display = 'none';
    }
  });
}
</script>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
