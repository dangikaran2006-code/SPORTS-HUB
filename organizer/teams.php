<?php
/**
 * SportsHub - Team Registry & Roster Console
 */
$currentPage = 'teams';
$pageTitle = 'Manage Teams';

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/team-player-functions.php';
require_once __DIR__ . '/../includes/tournament-functions.php';

// Server-side Access Control
requireRole(['admin', 'organizer', 'team_manager', 'player']);

$currentUser = currentUser();
$userRole = strtolower($currentUser['role'] ?? 'player');

// Team Manager Scope Enforcement: If role is team_manager, only show their assigned team
$managerScopeId = ($userRole === 'team_manager') ? intval($currentUser['id']) : 0;

$search  = trim($_GET['search'] ?? '');
$sportId = intval($_GET['sport_id'] ?? 0);
$status  = trim($_GET['status'] ?? 'all');

$csrfToken = generateCsrfToken();
$message = '';
$error = '';

// Handle POST Actions (Deactivate/Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_team') {
    $token = $_POST['csrf_token'] ?? '';
    $id = intval($_POST['team_id'] ?? 0);

    if (!validateCsrfToken($token)) {
        $error = 'Security validation failed (Invalid CSRF Token).';
    } elseif ($id > 0) {
        if ($userRole === 'team_manager') {
            $error = 'Team Managers cannot delete teams.';
        } else {
            deleteOrDeactivateTeamService($id);
            $message = "Team ID {$id} deactivated/removed.";
        }
    }
}

$teamsList  = getTeamsFiltered($search, $sportId, $status, $managerScopeId);
$sportsList = getSportsList();

include_once __DIR__ . '/../includes/header.php';
?>

<!-- Header -->
<div class="dashboard-header">
  <div class="dashboard-title-group">
    <h1>Team Registry Console</h1>
    <p>Manage multi-sport franchises, team logos, managers, and roster player assignments.</p>
  </div>
  <div class="quick-actions-bar">
    <?php if (in_array($userRole, ['admin', 'organizer'])): ?>
      <a href="<?php echo BASE_URL; ?>/organizer/create-team.php" class="btn btn-primary">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        <span>+ Add Team</span>
      </a>
    <?php endif; ?>
  </div>
</div>

<?php if (!empty($message)): ?>
  <div class="auth-alert auth-alert-success" style="margin-bottom:20px;">
    <span><?php echo htmlspecialchars($message); ?></span>
  </div>
<?php endif; ?>

<?php if (!empty($error)): ?>
  <div class="auth-alert auth-alert-danger" style="margin-bottom:20px;">
    <span><?php echo htmlspecialchars($error); ?></span>
  </div>
<?php endif; ?>

<!-- Search & Multi-Filter Bar -->
<div class="card" style="margin-bottom:24px; padding:16px 20px;">
  <form action="" method="GET" style="display:flex; flex-wrap:wrap; gap:12px; align-items:center;">
    <!-- Search Input -->
    <div style="flex:1; min-width:220px;">
      <input type="text" name="search" class="form-control" placeholder="Search team name or short code..." value="<?php echo htmlspecialchars($search); ?>">
    </div>

    <!-- Filter by Sport -->
    <div style="width:160px;">
      <select name="sport_id" class="form-control" onchange="this.form.submit()">
        <option value="0">All Sports</option>
        <?php foreach ($sportsList as $sp): ?>
          <option value="<?php echo $sp['id']; ?>" <?php echo ($sportId == $sp['id']) ? 'selected' : ''; ?>>
            <?php echo htmlspecialchars($sp['name']); ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <!-- Filter by Status -->
    <div style="width:140px;">
      <select name="status" class="form-control" onchange="this.form.submit()">
        <option value="all" <?php echo ($status==='all')?'selected':''; ?>>All Statuses</option>
        <option value="1" <?php echo ($status==='1')?'selected':''; ?>>Active</option>
        <option value="0" <?php echo ($status==='0')?'selected':''; ?>>Inactive</option>
      </select>
    </div>

    <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
    <a href="teams.php" class="btn btn-secondary btn-sm" style="color:var(--text-dim);">Reset</a>
  </form>
</div>

<!-- Teams List / Empty State -->
<?php if (empty($teamsList)): ?>
  <div class="card" style="text-align:center; padding:48px 24px;">
    <div style="font-size:3rem; margin-bottom:12px;">🛡️</div>
    <h3 style="font-size:1.3rem; margin-bottom:8px;">No Teams Found</h3>
    <p style="color:var(--text-muted); font-size:0.9rem; max-width:400px; margin:0 auto 20px auto;">
      No teams match your search or filter parameters.
    </p>
    <?php if (in_array($userRole, ['admin', 'organizer'])): ?>
      <a href="<?php echo BASE_URL; ?>/organizer/create-team.php" class="btn btn-primary">+ Add Team</a>
    <?php endif; ?>
  </div>
<?php else: ?>
  <div class="card">
    <div class="table-responsive">
      <table class="sports-table">
        <thead>
          <tr>
            <th>Team & Badge</th>
            <th>Short Code</th>
            <th>Sport</th>
            <th>Captain</th>
            <th>Manager</th>
            <th>Roster Players</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($teamsList as $team): ?>
            <tr data-searchable>
              <td>
                <div style="display:flex; align-items:center; gap:12px;">
                  <div class="team-badge-circle" style="width:36px; height:36px; font-weight:800; font-size:0.85rem; background:var(--bg-dark-surface); border:1px solid var(--border-subtle); color:var(--accent-green);">
                    <?php echo htmlspecialchars($team['short_name']); ?>
                  </div>
                  <div>
                    <strong style="color:var(--text-main); font-size:0.95rem;"><?php echo htmlspecialchars($team['name']); ?></strong>
                    <div style="font-size:0.75rem; color:var(--text-muted);"><?php echo htmlspecialchars($team['city'] ?? 'Regional'); ?></div>
                  </div>
                </div>
              </td>
              <td><span style="font-family:var(--font-heading); font-weight:700; color:var(--accent-green);"><?php echo htmlspecialchars($team['short_name']); ?></span></td>
              <td><?php echo getSportBadge($team['sport_name']); ?></td>
              <td><strong><?php echo htmlspecialchars($team['captain_name'] ?? 'Unassigned'); ?></strong></td>
              <td><?php echo htmlspecialchars($team['manager_name'] ?? 'Unassigned'); ?></td>
              <td><span style="font-weight:700; color:var(--text-main);"><?php echo $team['players_count']; ?> Athletes</span></td>
              <td>
                <?php if ($team['status'] == 1 || strtolower($team['status']) === 'active'): ?>
                  <span class="status-badge badge-active">Active</span>
                <?php else: ?>
                  <span class="status-badge badge-danger">Inactive</span>
                <?php endif; ?>
              </td>
              <td>
                <div style="display:flex; gap:6px;">
                  <a href="<?php echo BASE_URL; ?>/organizer/team-details.php?id=<?php echo $team['id']; ?>" class="btn btn-secondary btn-sm">View</a>
                  
                  <?php if (in_array($userRole, ['admin', 'organizer']) || ($userRole === 'team_manager' && ($team['manager_id'] ?? 0) == $currentUser['id'])): ?>
                    <a href="<?php echo BASE_URL; ?>/organizer/edit-team.php?id=<?php echo $team['id']; ?>" class="btn btn-secondary btn-sm">Edit</a>
                    <a href="<?php echo BASE_URL; ?>/organizer/players.php?team_id=<?php echo $team['id']; ?>" class="btn btn-secondary btn-sm">Players</a>
                  <?php endif; ?>

                  <?php if (in_array($userRole, ['admin', 'organizer'])): ?>
                    <form action="" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to deactivate/remove team <?php echo htmlspecialchars(addslashes($team['name'])); ?>?');">
                      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                      <input type="hidden" name="action" value="delete_team">
                      <input type="hidden" name="team_id" value="<?php echo $team['id']; ?>">
                      <button type="submit" class="btn btn-secondary btn-sm" style="color:var(--accent-red); border-color:rgba(239,68,68,0.3);">Delete</button>
                    </form>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
