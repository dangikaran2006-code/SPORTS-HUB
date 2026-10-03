<?php
/**
 * SportsHub - Team Details Console
 */
$currentPage = 'teams';
$pageTitle = 'Team Details';

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/team-player-functions.php';

// Server-side Access Enforcement
requireRole(['admin', 'organizer', 'team_manager', 'player']);

$id = intval($_GET['id'] ?? 1);
$team = getTeamById($id);

if (!$team) {
    header('Location: ' . BASE_URL . '/organizer/teams.php');
    exit;
}

$currentUser = currentUser();
$userRole = strtolower($currentUser['role'] ?? 'player');

// Team Manager Scope Security Check
if ($userRole === 'team_manager' && ($team['manager_id'] ?? 0) != $currentUser['id']) {
    $_SESSION['flash_error'] = "Access Denied: You can only view your assigned team details.";
    header('Location: ' . BASE_URL . '/organizer/teams.php');
    exit;
}

$activeTab = $_GET['tab'] ?? 'overview';
$teamPlayers = getPlayersFiltered('', 0, $team['id'], 'all');

$csrfToken = generateCsrfToken();
$message = '';
$error = '';

// Handle Captain Assignment POST Action (Section 10)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'set_captain') {
    $token = $_POST['csrf_token'] ?? '';
    $playerId = intval($_POST['player_id'] ?? 0);

    if (!validateCsrfToken($token)) {
        $error = 'Security validation failed (Invalid CSRF Token).';
    } elseif ($playerId > 0) {
        $result = setTeamCaptainService($team['id'], $playerId);
        if ($result['success']) {
            $message = $result['message'];
            $team = getTeamById($team['id']);
        } else {
            $error = $result['error'];
        }
    }
}

include_once __DIR__ . '/../includes/header.php';
?>

<!-- Team Banner Header -->
<div class="dashboard-header" style="margin-bottom:16px;">
  <div class="dashboard-title-group">
    <div style="display:flex; align-items:center; gap:12px; margin-bottom:8px;">
      <span class="team-badge-circle" style="width:36px; height:36px; font-weight:800; font-size:0.9rem; color:var(--accent-green);">
        <?php echo htmlspecialchars($team['short_name']); ?>
      </span>
      <?php echo getSportBadge($team['sport_name']); ?>
      <?php echo getStatusBadge(($team['status'] == 1 || strtolower($team['status']) === 'active') ? 'active' : 'inactive'); ?>
    </div>
    <h1><?php echo htmlspecialchars($team['name']); ?></h1>
    <p>Manager: <strong><?php echo htmlspecialchars($team['manager_name'] ?? 'Unassigned'); ?></strong> &bull; Captain: <strong style="color:var(--accent-green);"><?php echo htmlspecialchars($team['captain_name'] ?? 'Unassigned'); ?></strong></p>
  </div>
  <div class="quick-actions-bar">
    <a href="<?php echo BASE_URL; ?>/organizer/teams.php" class="btn btn-secondary">
      Back to Teams
    </a>
    <?php if (in_array($userRole, ['admin', 'organizer']) || ($userRole === 'team_manager' && ($team['manager_id'] ?? 0) == $currentUser['id'])): ?>
      <a href="<?php echo BASE_URL; ?>/organizer/edit-team.php?id=<?php echo $team['id']; ?>" class="btn btn-secondary">
        Edit Team
      </a>
      <a href="<?php echo BASE_URL; ?>/organizer/create-player.php?team_id=<?php echo $team['id']; ?>&sport_id=<?php echo $team['sport_id']; ?>" class="btn btn-primary">
        + Add Player
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

<!-- Section 3 Statistics Banner -->
<div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap:14px; margin-bottom:24px;">
  <div class="stat-card green-accent" style="padding:14px;">
    <div class="stat-details">
      <h3><?php echo count($teamPlayers); ?></h3>
      <span>Total Players</span>
    </div>
  </div>
  <div class="stat-card purple-accent" style="padding:14px;">
    <div class="stat-details">
      <h3>12</h3>
      <span>Matches Played</span>
    </div>
  </div>
  <div class="stat-card green-accent" style="padding:14px;">
    <div class="stat-details">
      <h3 style="color:var(--accent-green);">8</h3>
      <span>Wins</span>
    </div>
  </div>
  <div class="stat-card red-accent" style="padding:14px;">
    <div class="stat-details">
      <h3 style="color:var(--accent-red);">3</h3>
      <span>Losses</span>
    </div>
  </div>
  <div class="stat-card amber-accent" style="padding:14px;">
    <div class="stat-details">
      <h3>1</h3>
      <span>Draws</span>
    </div>
  </div>
  <div class="stat-card green-accent" style="padding:14px;">
    <div class="stat-details">
      <h3>2</h3>
      <span>Tournaments</span>
    </div>
  </div>
</div>

<!-- Tabs Bar -->
<div class="tournament-controls-bar" style="margin-bottom:20px;">
  <div class="filter-pills">
    <a href="team-details.php?id=<?php echo $team['id']; ?>&tab=overview" class="filter-pill-btn <?php echo ($activeTab==='overview')?'active':''; ?>">Overview</a>
    <a href="team-details.php?id=<?php echo $team['id']; ?>&tab=players" class="filter-pill-btn <?php echo ($activeTab==='players')?'active':''; ?>">Roster Players (<?php echo count($teamPlayers); ?>)</a>
    <a href="team-details.php?id=<?php echo $team['id']; ?>&tab=matches" class="filter-pill-btn <?php echo ($activeTab==='matches')?'active':''; ?>">Matches</a>
    <a href="team-details.php?id=<?php echo $team['id']; ?>&tab=stats" class="filter-pill-btn <?php echo ($activeTab==='stats')?'active':''; ?>">Team Statistics</a>
  </div>
</div>

<!-- Tab Content 1: Overview -->
<?php if ($activeTab === 'overview'): ?>
<div class="dashboard-main-grid">
  <div class="grid-left-col">
    <div class="card" style="margin-bottom:24px;">
      <div class="section-header">
        <h2>Team Overview</h2>
      </div>
      <p style="color:var(--text-muted); font-size:0.95rem; line-height:1.6; margin-bottom:16px;">
        <?php echo !empty($team['description']) ? htmlspecialchars($team['description']) : 'Official franchise roster and details for ' . htmlspecialchars($team['name']) . '.'; ?>
      </p>

      <div class="section-header" style="margin-top:24px;">
        <h2>Key Athletes Overview</h2>
      </div>
      <div class="table-responsive">
        <table class="sports-table">
          <thead>
            <tr>
              <th>Athlete</th>
              <th>Jersey #</th>
              <th>Primary Role</th>
              <th>Captain Status</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach (array_slice($teamPlayers, 0, 5) as $p): ?>
              <tr>
                <td><strong style="color:var(--text-main);"><?php echo htmlspecialchars($p['name']); ?></strong></td>
                <td><strong style="color:var(--accent-green);">#<?php echo htmlspecialchars($p['jersey_number']); ?></strong></td>
                <td><?php echo htmlspecialchars($p['position'] ?? $p['primary_role']); ?></td>
                <td>
                  <?php if (($team['captain_id'] ?? 0) == $p['id'] || ($p['is_captain'] ?? 0) == 1): ?>
                    <span class="status-badge badge-active">Captain</span>
                  <?php else: ?>
                    <span class="status-badge badge-completed">Member</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="grid-right-col">
    <div class="card">
      <div class="section-header">
        <h2>Franchise Specs</h2>
      </div>
      <div style="display:flex; flex-direction:column; gap:14px; font-size:0.875rem;">
        <div>
          <span style="color:var(--text-muted);">Short Code:</span>
          <strong style="display:block; color:var(--accent-green); font-size:1.1rem;"><?php echo htmlspecialchars($team['short_name']); ?></strong>
        </div>
        <div>
          <span style="color:var(--text-muted);">Sport Discipline:</span>
          <strong style="display:block; color:var(--text-main);"><?php echo htmlspecialchars($team['sport_name']); ?></strong>
        </div>
        <div>
          <span style="color:var(--text-muted);">Team Manager:</span>
          <strong style="display:block; color:var(--text-main);"><?php echo htmlspecialchars($team['manager_name'] ?? 'Unassigned'); ?></strong>
        </div>
        <div>
          <span style="color:var(--text-muted);">Current Captain:</span>
          <strong style="display:block; color:var(--accent-green);"><?php echo htmlspecialchars($team['captain_name'] ?? 'Unassigned'); ?></strong>
        </div>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- Tab Content 2: Players & Captain Control -->
<?php if ($activeTab === 'players'): ?>
<div class="card">
  <div class="section-header" style="margin-bottom:16px;">
    <h2>Roster Players (<?php echo count($teamPlayers); ?>)</h2>
    <?php if (in_array($userRole, ['admin', 'organizer']) || ($userRole === 'team_manager' && ($team['manager_id'] ?? 0) == $currentUser['id'])): ?>
      <a href="<?php echo BASE_URL; ?>/organizer/create-player.php?team_id=<?php echo $team['id']; ?>&sport_id=<?php echo $team['sport_id']; ?>" class="btn btn-primary btn-sm">+ Register Player</a>
    <?php endif; ?>
  </div>

  <div class="table-responsive">
    <table class="sports-table">
      <thead>
        <tr>
          <th>Player & Avatar</th>
          <th>Jersey #</th>
          <th>Position / Role</th>
          <th>Status</th>
          <th>Captain Management</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($teamPlayers as $p): ?>
          <tr>
            <td>
              <div style="display:flex; align-items:center; gap:10px;">
                <div style="width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,#00e676,#3b82f6);display:flex;align-items:center;justify-content:center;font-weight:700;color:#000;">
                  <?php echo strtoupper(substr($p['name'], 0, 1)); ?>
                </div>
                <strong style="color:var(--text-main);"><?php echo htmlspecialchars($p['name']); ?></strong>
              </div>
            </td>
            <td><strong style="color:var(--accent-green);">#<?php echo htmlspecialchars($p['jersey_number']); ?></strong></td>
            <td><?php echo htmlspecialchars($p['position'] ?? $p['primary_role']); ?></td>
            <td><span class="status-badge badge-active"><?php echo htmlspecialchars($p['status']); ?></span></td>
            <td>
              <?php if (($team['captain_id'] ?? 0) == $p['id'] || ($p['is_captain'] ?? 0) == 1): ?>
                <span class="status-badge badge-active" style="background:var(--accent-green-bg); color:var(--accent-green);">👑 Captain</span>
              <?php elseif (in_array($userRole, ['admin', 'organizer']) || ($userRole === 'team_manager' && ($team['manager_id'] ?? 0) == $currentUser['id'])): ?>
                <!-- Section 10: Assign Captain Form -->
                <form action="" method="POST" style="display:inline;">
                  <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                  <input type="hidden" name="action" value="set_captain">
                  <input type="hidden" name="player_id" value="<?php echo $p['id']; ?>">
                  <button type="submit" class="btn btn-secondary btn-sm">Make Captain</button>
                </form>
              <?php else: ?>
                <span style="color:var(--text-dim); font-size:0.8rem;">Member</span>
              <?php endif; ?>
            </td>
            <td>
              <a href="<?php echo BASE_URL; ?>/organizer/player-details.php?id=<?php echo $p['id']; ?>" class="btn btn-secondary btn-sm">View Profile</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<!-- Tab Content 3 & 4: Matches / Stats Placeholder -->
<?php if (in_array($activeTab, ['matches', 'stats'])): ?>
<div class="card" style="text-align:center; padding:48px 24px;">
  <div style="font-size:2.5rem; margin-bottom:12px;">📊</div>
  <h3 style="font-size:1.2rem; margin-bottom:8px; text-transform:capitalize;"><?php echo htmlspecialchars($activeTab); ?> Module</h3>
  <p style="color:var(--text-muted); font-size:0.9rem; max-width:400px; margin:0 auto 20px auto;">
    Team <?php echo htmlspecialchars($activeTab); ?> statistics and upcoming match engine entries.
  </p>
  <a href="<?php echo BASE_URL; ?>/admin/matches.php" class="btn btn-secondary">View Match Engine</a>
</div>
<?php endif; ?>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
