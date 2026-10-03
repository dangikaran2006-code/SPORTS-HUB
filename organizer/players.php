<?php
/**
 * SportsHub - Player Management Console
 */
$currentPage = 'players';
$pageTitle = 'Player Directory & Roster';

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/team-player-functions.php';
require_once __DIR__ . '/../includes/tournament-functions.php';

// Access: Admin, Organizer, Team Manager, Player
requireRole(['admin', 'organizer', 'team_manager', 'player']);

$currentUser = getCurrentUser();
$csrfToken   = generateCsrfToken();

// Handle Delete/Deactivate Action via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'deactivate_player') {
    if (validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $playerId = intval($_POST['player_id'] ?? 0);
        // Only Admin, Organizer, or the Team Manager of that player's team can deactivate
        deleteOrDeactivatePlayerService($playerId);
        header('Location: ' . BASE_URL . '/organizer/players.php?msg=deactivated');
        exit;
    }
}

// Search and Filter parameters
$search  = trim($_GET['search'] ?? '');
$sportId = intval($_GET['sport_id'] ?? 0);
$teamId  = intval($_GET['team_id'] ?? 0);
$status  = trim($_GET['status'] ?? '');

// Scope enforcement: if user is Team Manager, restrict to their assigned team
$managerId = ($currentUser['role'] === 'team_manager') ? $currentUser['id'] : 0;

$playersList = getPlayersFiltered($search, $sportId, $teamId, $status, $managerId);
$sportsList  = getSportsList();
$teamsList   = getTeamsFiltered('', 0, 'all', $managerId);

include_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-header">
  <div class="dashboard-title-group">
    <h1>Athlete & Player Directory</h1>
    <p>Manage player rosters, jersey numbers, sport specializations, and positions across franchises.</p>
  </div>
  <div class="quick-actions-bar">
    <?php if (in_array(strtolower($currentUser['role']), ['admin', 'organizer', 'team_manager'])): ?>
      <a href="<?php echo BASE_URL; ?>/organizer/create-player.php" class="btn btn-primary">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        <span>+ Add Player</span>
      </a>
    <?php endif; ?>
  </div>
</div>

<?php if (isset($_GET['msg']) && $_GET['msg'] === 'deactivated'): ?>
  <div class="auth-alert auth-alert-success" style="margin-bottom:20px; background: rgba(34, 197, 94, 0.15); border: 1px solid var(--accent-emerald); color: var(--accent-emerald); padding: 12px 16px; border-radius: var(--radius-md);">
    <span>Player status updated to inactive. Historical statistics preserved.</span>
  </div>
<?php endif; ?>

<!-- Search & Filter Controls -->
<div class="card" style="margin-bottom: 24px;">
  <form method="GET" action="" style="display:flex; flex-wrap:wrap; gap:16px; align-items:flex-end;">
    <div style="flex: 1; min-width: 220px;">
      <label style="font-size:0.85rem; color:var(--text-muted); font-weight:600; margin-bottom:6px; display:block;">Search Player / Jersey</label>
      <input type="text" name="search" class="form-control" placeholder="Search player name or jersey #" value="<?php echo htmlspecialchars($search); ?>">
    </div>

    <div style="width: 180px;">
      <label style="font-size:0.85rem; color:var(--text-muted); font-weight:600; margin-bottom:6px; display:block;">Filter by Sport</label>
      <select name="sport_id" class="form-control">
        <option value="0">All Sports</option>
        <?php foreach ($sportsList as $sp): ?>
          <option value="<?php echo $sp['id']; ?>" <?php echo ($sportId == $sp['id']) ? 'selected' : ''; ?>>
            <?php echo htmlspecialchars($sp['name']); ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div style="width: 200px;">
      <label style="font-size:0.85rem; color:var(--text-muted); font-weight:600; margin-bottom:6px; display:block;">Filter by Team</label>
      <select name="team_id" class="form-control">
        <option value="0">All Teams</option>
        <?php foreach ($teamsList as $tm): ?>
          <option value="<?php echo $tm['id']; ?>" <?php echo ($teamId == $tm['id']) ? 'selected' : ''; ?>>
            <?php echo htmlspecialchars($tm['name']); ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div style="width: 160px;">
      <label style="font-size:0.85rem; color:var(--text-muted); font-weight:600; margin-bottom:6px; display:block;">Status</label>
      <select name="status" class="form-control">
        <option value="all">All Statuses</option>
        <option value="active" <?php echo ($status === 'active') ? 'selected' : ''; ?>>Active</option>
        <option value="inactive" <?php echo ($status === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
        <option value="injured" <?php echo ($status === 'injured') ? 'selected' : ''; ?>>Injured</option>
        <option value="suspended" <?php echo ($status === 'suspended') ? 'selected' : ''; ?>>Suspended</option>
      </select>
    </div>

    <div style="display:flex; gap:8px;">
      <button type="submit" class="btn btn-primary">Filter</button>
      <a href="<?php echo BASE_URL; ?>/organizer/players.php" class="btn btn-secondary">Reset</a>
    </div>
  </form>
</div>

<!-- Players Data Table -->
<div class="card">
  <div class="table-responsive">
    <table class="sports-table">
      <thead>
        <tr>
          <th>Player Photo & Name</th>
          <th>Sport</th>
          <th>Team</th>
          <th>Jersey #</th>
          <th>Position</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($playersList)): ?>
          <tr>
            <td colspan="7" style="text-align:center; padding: 32px; color:var(--text-muted);">
              No players found matching the selected criteria.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($playersList as $pl): ?>
            <?php
              $photoUrl = !empty($pl['profile_image']) ? BASE_URL . '/' . htmlspecialchars($pl['profile_image']) : BASE_URL . '/assets/images/default-player.png';
              $statusStr = strtolower($pl['status'] ?? 'active');
              $badgeClass = 'badge-active';
              if ($statusStr === 'inactive') $badgeClass = 'badge-upcoming';
              elseif ($statusStr === 'injured') $badgeClass = 'badge-live';
              elseif ($statusStr === 'suspended') $badgeClass = 'badge-live';
            ?>
            <tr>
              <td>
                <div style="display:flex; align-items:center; gap:12px;">
                  <img src="<?php echo $photoUrl; ?>" alt="Player Photo" style="width:40px; height:40px; border-radius:50%; object-fit:cover; border:1px solid var(--border-subtle);" onerror="this.src='<?php echo BASE_URL; ?>/assets/images/default-player.png'">
                  <div>
                    <strong style="color:var(--text-main); font-size:0.95rem; display:block;">
                      <?php echo htmlspecialchars($pl['name']); ?>
                      <?php if (!empty($pl['is_captain'])): ?>
                        <span style="background:var(--accent-orange); color:#000; font-size:0.65rem; font-weight:800; padding:2px 6px; border-radius:4px; margin-left:4px;">C</span>
                      <?php endif; ?>
                    </strong>
                    <span style="font-size:0.75rem; color:var(--text-muted);"><?php echo htmlspecialchars($pl['email'] ?? ''); ?></span>
                  </div>
                </div>
              </td>
              <td><?php echo getSportBadge($pl['sport_name'] ?? 'Sport'); ?></td>
              <td>
                <strong style="color:var(--text-main);">
                  <?php echo htmlspecialchars($pl['team_name'] ?? 'Free Agent'); ?>
                </strong>
              </td>
              <td>
                <span style="font-family:var(--font-heading); font-weight:800; color:var(--accent-green);">
                  #<?php echo htmlspecialchars($pl['jersey_number'] ?? '-'); ?>
                </span>
              </td>
              <td><?php echo htmlspecialchars($pl['position'] ?? ($pl['primary_role'] ?? 'Player')); ?></td>
              <td>
                <span class="status-badge <?php echo $badgeClass; ?>">
                  <?php echo ucfirst(htmlspecialchars($statusStr)); ?>
                </span>
              </td>
              <td>
                <div style="display:flex; gap:6px; align-items:center;">
                  <a href="<?php echo BASE_URL; ?>/organizer/player-details.php?id=<?php echo $pl['id']; ?>" class="btn btn-secondary btn-sm">
                    View
                  </a>

                  <?php if (in_array(strtolower($currentUser['role']), ['admin', 'organizer', 'team_manager'])): ?>
                    <a href="<?php echo BASE_URL; ?>/organizer/edit-player.php?id=<?php echo $pl['id']; ?>" class="btn btn-secondary btn-sm">
                      Edit
                    </a>
                    
                    <form action="" method="POST" style="display:inline;" onsubmit="return confirm('Deactivate player <?php echo htmlspecialchars(addslashes($pl['name'])); ?>?');">
                      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                      <input type="hidden" name="action" value="deactivate_player">
                      <input type="hidden" name="player_id" value="<?php echo $pl['id']; ?>">
                      <button type="submit" class="btn btn-secondary btn-sm" style="color:var(--accent-red);">
                        Deactivate
                      </button>
                    </form>
                  <?php endif; ?>
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
