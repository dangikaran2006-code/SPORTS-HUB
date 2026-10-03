<?php
/**
 * SportsHub - Player Details & Generic Sport Statistics Profile
 */
$currentPage = 'players';
$pageTitle = 'Player Profile Details';

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/team-player-functions.php';
require_once __DIR__ . '/../includes/tournament-functions.php';

// Access: Admin, Organizer, Team Manager, Player
requireRole(['admin', 'organizer', 'team_manager', 'player']);

$playerId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$player = getPlayerById($playerId);

if (!$player) {
    header('Location: ' . BASE_URL . '/organizer/players.php');
    exit;
}

$currentUser = getCurrentUser();

// Scope enforcement: if Team Manager, check if player belongs to their assigned team
if ($currentUser['role'] === 'team_manager') {
    $db = getDB();
    if ($db->getConnection()) {
        $team = fetchOne("SELECT manager_id FROM teams WHERE id = :tid", [':tid' => (int)($player['team_id'] ?? 0)]);
        if ($team && $team['manager_id'] != $currentUser['id']) {
            die("Access Denied: You can only view details of players in your assigned team.");
        }
    }
}

$playerStatsSummary = getPlayerStatsSummary($playerId);
$byType = $playerStatsSummary['by_type'];

$photoUrl = !empty($player['profile_image']) ? BASE_URL . '/' . htmlspecialchars($player['profile_image']) : BASE_URL . '/assets/images/default-player.png';
$statusStr = strtolower($player['status'] ?? 'active');
$badgeClass = 'badge-active';
if ($statusStr === 'inactive') $badgeClass = 'badge-upcoming';
elseif ($statusStr === 'injured') $badgeClass = 'badge-live';
elseif ($statusStr === 'suspended') $badgeClass = 'badge-live';

include_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-header">
  <div class="dashboard-title-group">
    <h1>Athlete Profile: <?php echo htmlspecialchars($player['name']); ?></h1>
    <p>Comprehensive overview of player attributes, team affiliation, and performance statistics.</p>
  </div>
  <div class="quick-actions-bar">
    <?php if (in_array(strtolower($currentUser['role']), ['admin', 'organizer', 'team_manager'])): ?>
      <a href="<?php echo BASE_URL; ?>/organizer/edit-player.php?id=<?php echo $player['id']; ?>" class="btn btn-primary">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
        <span>Edit Player</span>
      </a>
    <?php endif; ?>
    <a href="<?php echo BASE_URL; ?>/organizer/players.php" class="btn btn-secondary">
      Back to Players
    </a>
  </div>
</div>

<!-- Header Card / Profile Banner -->
<div class="card" style="margin-bottom:24px; padding:24px;">
  <div style="display:flex; flex-wrap:wrap; gap:24px; align-items:center;">
    <div style="position:relative;">
      <img src="<?php echo $photoUrl; ?>" alt="Player Photo" style="width:110px; height:110px; border-radius:50%; object-fit:cover; border:3px solid var(--accent-green);" onerror="this.src='<?php echo BASE_URL; ?>/assets/images/default-player.png'">
      <div style="position:absolute; bottom:0; right:0; background:var(--accent-green); color:#000; font-family:var(--font-heading); font-weight:900; font-size:0.85rem; padding:4px 8px; border-radius:12px; border:2px solid var(--bg-card);">
        #<?php echo htmlspecialchars($player['jersey_number'] ?? '-'); ?>
      </div>
    </div>

    <div style="flex:1; min-width:260px;">
      <div style="display:flex; align-items:center; gap:12px; margin-bottom:8px;">
        <h2 style="font-size:1.6rem; font-weight:800; color:var(--text-main); margin:0;">
          <?php echo htmlspecialchars($player['name']); ?>
        </h2>
        <?php if (!empty($player['is_captain'])): ?>
          <span style="background:var(--accent-orange); color:#000; font-size:0.75rem; font-weight:800; padding:3px 8px; border-radius:6px; text-transform:uppercase;">
            Team Captain
          </span>
        <?php endif; ?>
        <span class="status-badge <?php echo $badgeClass; ?>">
          <?php echo ucfirst(htmlspecialchars($statusStr)); ?>
        </span>
      </div>

      <div style="display:flex; flex-wrap:wrap; gap:16px; color:var(--text-muted); font-size:0.9rem;">
        <div><strong>Sport:</strong> <?php echo getSportBadge($player['sport_name'] ?? 'Sport'); ?></div>
        <div><strong>Team:</strong> <a href="<?php echo BASE_URL; ?>/organizer/team-details.php?id=<?php echo $player['team_id'] ?? 0; ?>" style="color:var(--accent-blue); text-decoration:none; font-weight:600;"><?php echo htmlspecialchars($player['team_name'] ?? 'Free Agent'); ?></a></div>
        <div><strong>Position:</strong> <?php echo htmlspecialchars($player['position'] ?? ($player['primary_role'] ?? 'Player')); ?></div>
        <?php if (!empty($player['date_of_birth'])): ?>
          <div><strong>DOB:</strong> <?php echo date('M d, Y', strtotime($player['date_of_birth'])); ?></div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- Performance & Multi-Sport Statistics -->
<div class="card-header" style="margin-bottom:16px;">
  <h3 style="font-size:1.2rem; font-weight:700; color:var(--text-main); margin:0;">
    Player Statistics (<?php echo htmlspecialchars($player['sport_name'] ?? 'All Sports'); ?>)
  </h3>
</div>

<div class="stats-grid" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap:16px; margin-bottom:24px;">
  <div class="stat-card">
    <div class="stat-header">
      <span class="stat-title">Matches Played</span>
    </div>
    <div class="stat-value"><?php echo intval($playerStatsSummary['matches_played']); ?></div>
  </div>

  <?php if (!empty($byType)): ?>
    <?php foreach ($byType as $statKey => $statVal): ?>
      <div class="stat-card">
        <div class="stat-header">
          <span class="stat-title"><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $statKey))); ?></span>
        </div>
        <div class="stat-value"><?php echo (floor($statVal) == $statVal) ? intval($statVal) : number_format($statVal, 2); ?></div>
      </div>
    <?php endforeach; ?>
  <?php else: ?>
    <!-- Fallback default generic indicators if no player_statistics rows yet -->
    <div class="stat-card">
      <div class="stat-header"><span class="stat-title">Total Points / Runs / Goals</span></div>
      <div class="stat-value">0</div>
    </div>
    <div class="stat-card">
      <div class="stat-header"><span class="stat-title">Assists / Wickets / Steals</span></div>
      <div class="stat-value">0</div>
    </div>
    <div class="stat-card">
      <div class="stat-header"><span class="stat-title">Player Rating</span></div>
      <div class="stat-value">N/A</div>
    </div>
  <?php endif; ?>
</div>

<!-- Contact & Additional Details -->
<div class="card">
  <h4 style="font-size:1.05rem; font-weight:700; color:var(--text-main); margin-bottom:16px;">Contact & Athlete Information</h4>
  <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:16px; color:var(--text-muted); font-size:0.9rem;">
    <div><strong>Email:</strong> <?php echo htmlspecialchars($player['email'] ?? 'Not provided'); ?></div>
    <div><strong>Phone:</strong> <?php echo htmlspecialchars($player['phone'] ?? 'Not provided'); ?></div>
    <div><strong>Registration Date:</strong> <?php echo !empty($player['created_at']) ? date('M d, Y', strtotime($player['created_at'])) : 'N/A'; ?></div>
    <div><strong>Team Jersey Number:</strong> #<?php echo htmlspecialchars($player['jersey_number'] ?? 'Unassigned'); ?></div>
  </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
