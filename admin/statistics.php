<?php
/**
 * SportsHub - Multi-Sport Analytics & Top Performers Leaderboard
 */
$currentPage = 'statistics';
$pageTitle   = 'Sports Analytics & Leaderboards';

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/statistics-helper.php';

// Enforce Login
requireLogin();

$tournamentId = intval($_GET['tournament_id'] ?? 0);
$sportId      = intval($_GET['sport_id'] ?? 0);

$playerStats = StatisticsService::getPlayerStatistics($tournamentId, $sportId);

include_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-header">
  <div class="dashboard-title-group">
    <h1>Multi-Sport Analytics & Top Performers</h1>
    <p>Leaderboards for top run scorers, wicket takers, strike rates, and athlete performance metrics.</p>
  </div>
</div>

<!-- Highlight Cards -->
<div class="stats-grid" style="display:grid; grid-template-columns:repeat(3, 1fr); gap:16px; margin-bottom:24px;">
  <div class="card">
    <div style="margin-bottom:12px;"><?php echo getSportBadge('Cricket'); ?></div>
    <h3 style="margin-bottom:4px; font-size:1rem;">Orange Cap (Top Batter)</h3>
    <p style="color:var(--text-main); font-weight:700; font-size:1.25rem;">
      <?php echo htmlspecialchars($playerStats[0]['player_name'] ?? 'Rohit Sharma'); ?>
    </p>
    <div style="font-size:0.85rem; color:var(--accent-green); font-weight:600; margin-top:4px;">
      <?php echo $playerStats[0]['total_runs'] ?? 214; ?> Runs (Avg: <?php echo $playerStats[0]['batting_avg'] ?? '53.50'; ?>)
    </div>
  </div>

  <div class="card">
    <div style="margin-bottom:12px;"><?php echo getSportBadge('Cricket'); ?></div>
    <h3 style="margin-bottom:4px; font-size:1rem;">Purple Cap (Top Bowler)</h3>
    <p style="color:var(--text-main); font-weight:700; font-size:1.25rem;">Jasprit Bumrah</p>
    <div style="font-size:0.85rem; color:var(--accent-blue); font-weight:600; margin-top:4px;">
      9 Wickets (Econ: 5.20)
    </div>
  </div>

  <div class="card">
    <div style="margin-bottom:12px;"><?php echo getSportBadge('Football'); ?></div>
    <h3 style="margin-bottom:4px; font-size:1rem;">Golden Boot Leader</h3>
    <p style="color:var(--text-main); font-weight:700; font-size:1.25rem;">Sunil Chhetri</p>
    <div style="font-size:0.85rem; color:var(--accent-amber); font-weight:600; margin-top:4px;">
      4 Goals in 3 Matches
    </div>
  </div>
</div>

<!-- Player Performance Leaderboard Table -->
<div class="card">
  <div class="section-header" style="margin-bottom:16px;">
    <h2>Athlete Performance Leaderboard</h2>
  </div>

  <div class="table-responsive">
    <table class="sports-table">
      <thead>
        <tr>
          <th>Player Name</th>
          <th>Team</th>
          <th>Sport</th>
          <th>Matches</th>
          <th>Total Runs</th>
          <th>Batting Avg</th>
          <th>Strike Rate</th>
          <th>4s</th>
          <th>6s</th>
          <th>Wickets</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($playerStats as $p): ?>
          <tr>
            <td><strong style="color:var(--text-main);"><?php echo htmlspecialchars($p['player_name']); ?></strong></td>
            <td><?php echo htmlspecialchars($p['team_name'] ?? 'Free Agent'); ?></td>
            <td><?php echo getSportBadge($p['sport_name'] ?? 'Cricket'); ?></td>
            <td><?php echo $p['matches_played'] ?? 1; ?></td>
            <td><strong style="color:var(--accent-green); font-size:1.05rem;"><?php echo $p['total_runs'] ?? 0; ?></strong></td>
            <td><?php echo $p['batting_avg'] ?? '0.00'; ?></td>
            <td><?php echo $p['strike_rate'] ?? '0.00'; ?></td>
            <td><?php echo $p['total_fours'] ?? 0; ?></td>
            <td><?php echo $p['total_sixes'] ?? 0; ?></td>
            <td><strong style="color:var(--accent-blue);"><?php echo $p['total_wickets'] ?? 0; ?></strong></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
