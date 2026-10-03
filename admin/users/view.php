<?php
/**
 * SportsHub - User Detail & Permission View Console
 */
$currentPage = 'users';
$pageTitle = 'User Profile & Permissions';

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/auth.php';

requireRole('admin');

$userId = intval($_GET['id'] ?? 0);
$db = getDB();
$user = null;

if ($db->getConnection() && $userId > 0) {
    $user = fetchOne("SELECT id, name, email, role, status, created_at, updated_at FROM users WHERE id = :id", [':id' => $userId]);
}

if (!$user) {
    // Demo fallback for previewing
    $demoUsers = [
        1 => ['id' => 1, 'name' => 'Alex Mercer', 'email' => 'admin@sportshub.com', 'role' => 'admin', 'status' => 1, 'created_at' => '2026-10-01 10:00:00'],
        2 => ['id' => 2, 'name' => 'Sarah Jenkins', 'email' => 'organizer@sportshub.com', 'role' => 'organizer', 'status' => 1, 'created_at' => '2026-10-01 11:30:00'],
        3 => ['id' => 3, 'name' => 'Nitin Menon', 'email' => 'scorer@sportshub.com', 'role' => 'scorer', 'status' => 1, 'created_at' => '2026-10-02 09:15:00'],
        4 => ['id' => 4, 'name' => 'Pranjal Banerjee', 'email' => 'official@sportshub.com', 'role' => 'official', 'status' => 1, 'created_at' => '2026-10-02 14:20:00'],
    ];
    $user = $demoUsers[$userId] ?? [
        'id' => $userId ?: 1,
        'name' => 'User ID #' . $userId,
        'email' => 'user' . $userId . '@sportshub.com',
        'role' => 'player',
        'status' => 1,
        'created_at' => date('Y-m-d H:i:s')
    ];
}

$permissions = getRolePermissions($user['role']);

// Fetch recent activity / audit logs for this user
$userAuditLogs = [];
if ($db->getConnection()) {
    $userAuditLogs = fetchAll("SELECT * FROM audit_logs WHERE user_id = :id ORDER BY id DESC LIMIT 10", [':id' => $user['id']]);
}

// Fetch assigned matches if Scorer or Official
$assignedMatches = [];
if ($db->getConnection()) {
    if (strtolower($user['role']) === 'scorer') {
        $assignedMatches = fetchAll("
            SELECT m.*, s.name as sport_name, ta.name as team_a_name, tb.name as team_b_name 
            FROM matches m 
            JOIN sports s ON m.sport_id = s.id 
            JOIN teams ta ON m.team_a_id = ta.id 
            JOIN teams tb ON m.team_b_id = tb.id 
            WHERE m.scorer_id = :id ORDER BY m.scheduled_date DESC", [':id' => $user['id']]);
    } elseif (strtolower($user['role']) === 'official') {
        $assignedMatches = fetchAll("
            SELECT m.*, s.name as sport_name, ta.name as team_a_name, tb.name as team_b_name 
            FROM matches m 
            JOIN sports s ON m.sport_id = s.id 
            JOIN teams ta ON m.team_a_id = ta.id 
            JOIN teams tb ON m.team_b_id = tb.id 
            WHERE m.official_id = :id ORDER BY m.scheduled_date DESC", [':id' => $user['id']]);
    }
}

include_once __DIR__ . '/../../includes/header.php';
?>

<div class="dashboard-header">
  <div class="dashboard-title-group">
    <h1>User Profile: <?php echo htmlspecialchars($user['name']); ?></h1>
    <p>Detailed role privileges, activity log, and assigned matches.</p>
  </div>
  <div class="quick-actions-bar">
    <a href="<?php echo BASE_URL; ?>/admin/users.php" class="btn btn-secondary">
      &larr; Back to User List
    </a>
  </div>
</div>

<div class="grid grid-2" style="grid-template-columns: 350px 1fr; gap: 20px;">
  <!-- User Profile Summary Card -->
  <div class="card">
    <div style="text-align: center; padding: 20px 0;">
      <div style="width: 72px; height: 72px; border-radius: 50%; background: linear-gradient(135deg, var(--accent-green), #3b82f6); margin: 0 auto 16px; display: flex; align-items: center; justify-content: center; font-size: 2rem; font-weight: 800; color: #000;">
        <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
      </div>
      <h2 style="font-size: 1.25rem; font-weight: 700; color: var(--text-main); margin-bottom: 4px;"><?php echo htmlspecialchars($user['name']); ?></h2>
      <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 12px;"><?php echo htmlspecialchars($user['email']); ?></p>
      
      <div style="display: flex; gap: 8px; justify-content: center; align-items: center;">
        <span class="sport-badge badge-generic" style="text-transform: uppercase; font-weight: 700; font-size: 0.8rem;">
          <?php echo htmlspecialchars($user['role']); ?>
        </span>
        <?php if ($user['status'] == 1): ?>
          <span class="status-badge badge-active">Active Account</span>
        <?php else: ?>
          <span class="status-badge badge-danger">Disabled / Suspended</span>
        <?php endif; ?>
      </div>
    </div>

    <hr style="border-color: var(--border-color); margin: 16px 0;">

    <div style="font-size: 0.9rem; line-height: 1.8;">
      <div><strong style="color:var(--text-main);">User ID:</strong> #<?php echo $user['id']; ?></div>
      <div><strong style="color:var(--text-main);">Registration Date:</strong> <?php echo date('M d, Y h:i A', strtotime($user['created_at'])); ?></div>
      <div><strong style="color:var(--text-main);">Security Role:</strong> <?php echo strtoupper(htmlspecialchars($user['role'])); ?></div>
    </div>
  </div>

  <!-- Permissions & Activity Details -->
  <div style="display: flex; flex-direction: column; gap: 20px;">
    <!-- Assigned Permissions Matrix -->
    <div class="card">
      <h3 style="font-size: 1.1rem; color: var(--text-main); margin-bottom: 12px; font-weight: 700;">Granted Security Permissions</h3>
      <div style="display: flex; flex-wrap: wrap; gap: 8px;">
        <?php foreach ($permissions as $perm): ?>
          <span class="badge-active" style="padding: 6px 12px; border-radius: 6px; font-size: 0.85rem; background: rgba(0, 230, 118, 0.15); color: var(--accent-green); border: 1px solid rgba(0, 230, 118, 0.3);">
            🔑 <?php echo htmlspecialchars($perm); ?>
          </span>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Assigned Matches for Scorer / Official -->
    <?php if (in_array(strtolower($user['role']), ['scorer', 'official'])): ?>
      <div class="card">
        <h3 style="font-size: 1.1rem; color: var(--text-main); margin-bottom: 12px; font-weight: 700;">
          Assigned Championship Matches (<?php echo count($assignedMatches); ?>)
        </h3>
        <?php if (!empty($assignedMatches)): ?>
          <div class="table-responsive">
            <table class="sports-table">
              <thead>
                <tr>
                  <th>Sport</th>
                  <th>Teams</th>
                  <th>Date & Time</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($assignedMatches as $m): ?>
                  <tr>
                    <td><?php echo getSportBadge($m['sport_name']); ?></td>
                    <td><strong><?php echo htmlspecialchars($m['team_a_name']); ?> vs <?php echo htmlspecialchars($m['team_b_name']); ?></strong></td>
                    <td><?php echo date('M d, Y', strtotime($m['scheduled_date'])); ?> <?php echo date('h:i A', strtotime($m['scheduled_time'])); ?></td>
                    <td><?php echo getStatusBadge($m['status']); ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <p style="color: var(--text-muted); font-size: 0.9rem;">No specific matches currently assigned to this <?php echo htmlspecialchars($user['role']); ?>.</p>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <!-- User Audit Trail -->
    <div class="card">
      <h3 style="font-size: 1.1rem; color: var(--text-main); margin-bottom: 12px; font-weight: 700;">Recent User Activity & Audit Trail</h3>
      <?php if (!empty($userAuditLogs)): ?>
        <div class="table-responsive">
          <table class="sports-table">
            <thead>
              <tr>
                <th>Time</th>
                <th>Action</th>
                <th>Entity</th>
                <th>Description</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($userAuditLogs as $log): ?>
                <tr>
                  <td style="font-size:0.85rem; color:var(--text-muted);"><?php echo date('M d, H:i:s', strtotime($log['created_at'])); ?></td>
                  <td><strong style="color:var(--text-main);"><?php echo htmlspecialchars($log['action']); ?></strong></td>
                  <td><span class="sport-badge badge-generic" style="font-size:0.75rem;"><?php echo htmlspecialchars($log['entity'] ?? 'System'); ?></span></td>
                  <td style="font-size:0.85rem; color:var(--text-muted);"><?php echo htmlspecialchars($log['description'] ?? ''); ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <p style="color: var(--text-muted); font-size: 0.9rem;">No recorded activity logs for this account yet.</p>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php include_once __DIR__ . '/../../includes/footer.php'; ?>
