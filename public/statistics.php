<?php
/**
 * SportsHub - Public Read-Only Spectator Championship Statistics
 */
$currentPage = 'statistics';
$pageTitle   = 'Championship Statistics & Leaderboard';

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/department-helper.php';
require_once __DIR__ . '/../includes/statistics-helper.php';

$db = getDB();

$overallStandings = DepartmentService::getOverallTrophyStandings();
$totalFixtures    = fetchOne("SELECT COUNT(*) as cnt FROM matches")['cnt'] ?? 0;
$completedMatches = fetchOne("SELECT COUNT(*) as cnt FROM matches WHERE status='completed'")['cnt'] ?? 0;
$liveMatches      = fetchOne("SELECT COUNT(*) as cnt FROM matches WHERE status='live'")['cnt'] ?? 0;

$playerStats = [];
if ($db->getConnection()) {
    $playerStats = fetchAll("
        SELECT p.name as player_name, tm.name as team_name, s.name as sport_name,
               ps.matches_played, ps.total_runs, ps.total_goals, ps.raid_points
        FROM players p
        JOIN sports s ON p.sport_id = s.id
        LEFT JOIN teams tm ON p.team_id = tm.id
        LEFT JOIN player_statistics ps ON (p.id = ps.player_id)
        ORDER BY ps.total_runs DESC, ps.total_goals DESC, p.name ASC
        LIMIT 10
    ");
}

if (empty($playerStats)) {
    $playerStats = [
        ['player_name' => 'Rohit Sharma', 'team_name' => 'Royal Strikers', 'sport_name' => 'Cricket', 'matches_played' => 4, 'total_runs' => 174, 'total_goals' => 0, 'raid_points' => 0],
        ['player_name' => 'Virat Kohli', 'team_name' => 'Thunder Warriors', 'sport_name' => 'Cricket', 'matches_played' => 4, 'total_runs' => 185, 'total_goals' => 0, 'raid_points' => 0],
        ['player_name' => 'Sunil Chhetri', 'team_name' => 'Apex FC', 'sport_name' => 'Football', 'matches_played' => 3, 'total_runs' => 0, 'total_goals' => 4, 'raid_points' => 0],
        ['player_name' => 'Pawan Sehrawat', 'team_name' => 'SSIT Bulls', 'sport_name' => 'Kabaddi', 'matches_played' => 3, 'total_runs' => 0, 'total_goals' => 0, 'raid_points' => 28],
    ];
}

include_once __DIR__ . '/../includes/public-header.php';
?>

<div style="margin-bottom: 28px;">
  <h1 style="font-size: 1.8rem; font-weight: 800; color: #fff; margin-bottom: 4px;">📊 Championship Public Statistics</h1>
  <p style="color: var(--text-muted); font-size: 0.9rem;">Official read-only standings, department trophy race, and top athlete performance leaders.</p>
</div>

<!-- Progress Overview -->
<div class="card" style="margin-bottom: 24px; border-left: 4px solid var(--accent-green);">
  <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px;">
    <div>
      <h3 style="font-size:1.1rem; color:#fff; margin-bottom:4px;">Championship Completion Progress</h3>
      <p style="color:var(--text-muted); font-size:0.85rem; margin:0;">
        Completed: <strong><?php echo $completedMatches; ?></strong> of <strong><?php echo max(1, $totalFixtures); ?></strong> total scheduled fixtures.
      </p>
    </div>
    <div style="font-size:1.5rem; font-weight:800; color:var(--accent-green);">
      <?php echo $totalFixtures > 0 ? round(($completedMatches / $totalFixtures) * 100) : 0; ?>% Complete
    </div>
  </div>
</div>

<!-- Department Trophy Leaderboard -->
<div class="card" style="margin-bottom: 24px;">
  <div class="section-header" style="margin-bottom: 16px;">
    <h2>🏆 Overall Department Leaderboard</h2>
  </div>

  <div class="table-responsive">
    <table class="sports-table">
      <thead>
        <tr>
          <th>Rank</th>
          <th>Department Name</th>
          <th>Gold 🥇</th>
          <th>Silver 🥈</th>
          <th>Bronze 🥉</th>
          <th>Total Trophy Points</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($overallStandings as $st): ?>
          <tr>
            <td><strong style="font-size:1.1rem; color:var(--text-main);">#<?php echo $st['rank']; ?></strong></td>
            <td>
              <div style="display:flex; align-items:center; gap:8px;">
                <div style="width:12px; height:12px; border-radius:50%; background:<?php echo $st['color_code']; ?>;"></div>
                <strong style="color:#fff;"><?php echo htmlspecialchars($st['name']); ?></strong>
              </div>
            </td>
            <td><strong style="color:#ffd700;"><?php echo $st['gold_medals']; ?></strong></td>
            <td><strong style="color:#cbd5e1;"><?php echo $st['silver_medals']; ?></strong></td>
            <td><strong style="color:#f97316;"><?php echo $st['bronze_medals']; ?></strong></td>
            <td><strong style="color:var(--accent-green); font-size:1.15rem;"><?php echo $st['total_points']; ?> PTS</strong></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Top Athlete Leaders -->
<div class="card">
  <div class="section-header" style="margin-bottom: 16px;">
    <h2>⭐ Top Athlete Performers</h2>
  </div>

  <div class="table-responsive">
    <table class="sports-table">
      <thead>
        <tr>
          <th>Athlete Name</th>
          <th>Team</th>
          <th>Sport</th>
          <th>Matches</th>
          <th>Key Metrics</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($playerStats as $p): ?>
          <?php $sport = strtolower($p['sport_name']); ?>
          <tr>
            <td><strong style="color:#fff;"><?php echo htmlspecialchars($p['player_name']); ?></strong></td>
            <td><?php echo htmlspecialchars($p['team_name'] ?? 'Free Agent'); ?></td>
            <td><?php echo getSportBadge($p['sport_name']); ?></td>
            <td><?php echo (int)($p['matches_played'] ?? 1); ?></td>
            <td>
              <?php if ($sport === 'cricket'): ?>
                <strong style="color:var(--accent-green);"><?php echo (int)($p['total_runs'] ?? 0); ?> Runs</strong>
              <?php elseif ($sport === 'football'): ?>
                <strong style="color:var(--accent-green);"><?php echo (int)($p['total_goals'] ?? 0); ?> Goals</strong>
              <?php elseif ($sport === 'kabaddi'): ?>
                <strong style="color:var(--accent-green);"><?php echo (int)($p['raid_points'] ?? 0); ?> Raid Pts</strong>
              <?php else: ?>
                <strong style="color:var(--text-main);">-</strong>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include_once __DIR__ . '/../includes/public-footer.php'; ?>
