<?php
/**
 * SportsHub - Admin Championship Statistics & Analytics Dashboard
 */
$currentPage = 'statistics';
$pageTitle   = 'Championship Analytics & Statistics';

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/department-helper.php';
require_once __DIR__ . '/../includes/statistics-helper.php';

requireAdminAccess();

$db = getDB();

// Real Database Metrics Aggregation
$totalChampionships = fetchOne("SELECT COUNT(*) as cnt FROM tournaments")['cnt'] ?? 0;
$activeChampionships = fetchOne("SELECT COUNT(*) as cnt FROM tournaments WHERE status='active'")['cnt'] ?? 0;
$totalDepartments   = fetchOne("SELECT COUNT(*) as cnt FROM departments WHERE status=1")['cnt'] ?? 6;
$totalSports         = fetchOne("SELECT COUNT(*) as cnt FROM sports")['cnt'] ?? 0;
$totalTeams          = fetchOne("SELECT COUNT(*) as cnt FROM teams")['cnt'] ?? 0;
$totalPlayers        = fetchOne("SELECT COUNT(*) as cnt FROM players")['cnt'] ?? 0;
$totalOfficials      = fetchOne("SELECT COUNT(*) as cnt FROM officials")['cnt'] ?? 0;
$totalVenues         = fetchOne("SELECT COUNT(*) as cnt FROM venues")['cnt'] ?? 0;
$totalFixtures       = fetchOne("SELECT COUNT(*) as cnt FROM matches")['cnt'] ?? 0;
$completedMatches    = fetchOne("SELECT COUNT(*) as cnt FROM matches WHERE status='completed'")['cnt'] ?? 0;
$upcomingMatches     = fetchOne("SELECT COUNT(*) as cnt FROM matches WHERE status IN ('scheduled', 'upcoming')")['cnt'] ?? 0;
$liveMatches         = fetchOne("SELECT COUNT(*) as cnt FROM matches WHERE status='live'")['cnt'] ?? 0;
$cancelledMatches    = fetchOne("SELECT COUNT(*) as cnt FROM matches WHERE status IN ('cancelled', 'postponed')")['cnt'] ?? 0;

$overallStandings = DepartmentService::getOverallTrophyStandings();

include_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-header">
  <div class="dashboard-title-group">
    <h1>Championship Analytics & Real-Time Statistics</h1>
    <p>Comprehensive system metrics, department standings breakdown, match outcomes, and report links.</p>
  </div>
  <div class="quick-actions-bar" style="display:flex; gap:10px; flex-wrap:wrap;">
    <a href="<?php echo BASE_URL; ?>/admin/reports/championship.php" class="btn btn-primary">
      📑 Championship Reports
    </a>
    <a href="<?php echo BASE_URL; ?>/admin/statistics/players.php" class="btn btn-secondary">
      🏃 Player Statistics
    </a>
    <a href="<?php echo BASE_URL; ?>/admin/statistics/teams.php" class="btn btn-secondary">
      🛡️ Team Statistics
    </a>
  </div>
</div>

<!-- Key System Metrics Counter Grid -->
<div class="stats-grid" style="grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px;">
  <div class="stat-card green-accent">
    <div class="stat-details">
      <h3><?php echo $totalDepartments; ?></h3>
      <span>Departments</span>
    </div>
  </div>

  <div class="stat-card blue-accent">
    <div class="stat-details">
      <h3><?php echo $totalSports; ?></h3>
      <span>Sports Configured</span>
    </div>
  </div>

  <div class="stat-card purple-accent">
    <div class="stat-details">
      <h3><?php echo $totalTeams; ?></h3>
      <span>Competing Teams</span>
    </div>
  </div>

  <div class="stat-card amber-accent">
    <div class="stat-details">
      <h3><?php echo $totalPlayers; ?></h3>
      <span>Registered Athletes</span>
    </div>
  </div>

  <div class="stat-card green-accent">
    <div class="stat-details">
      <h3><?php echo $totalFixtures; ?></h3>
      <span>Total Fixtures</span>
    </div>
  </div>

  <div class="stat-card red-accent">
    <div class="stat-details">
      <h3 style="color:var(--accent-red);"><?php echo $liveMatches; ?></h3>
      <span>Live Matches Now</span>
    </div>
  </div>

  <div class="stat-card blue-accent">
    <div class="stat-details">
      <h3><?php echo $completedMatches; ?></h3>
      <span>Completed Events</span>
    </div>
  </div>

  <div class="stat-card amber-accent">
    <div class="stat-details">
      <h3><?php echo $upcomingMatches; ?></h3>
      <span>Upcoming Fixtures</span>
    </div>
  </div>
</div>

<!-- Visual Analytics & Charts Section -->
<div class="grid grid-2" style="gap: 20px; margin-bottom: 28px;">
  <!-- Department Points Chart -->
  <div class="card">
    <div class="section-header" style="margin-bottom: 16px;">
      <h2>🏆 Department Points Breakdown</h2>
    </div>
    <div style="display:flex; flex-direction:column; gap:14px;">
      <?php 
        $maxPts = max(array_column($overallStandings, 'total_points') ?: [1]);
        if ($maxPts <= 0) $maxPts = 1;
      ?>
      <?php foreach ($overallStandings as $st): ?>
        <?php $pct = round(($st['total_points'] / $maxPts) * 100); ?>
        <div>
          <div style="display:flex; justify-content:space-between; font-size:0.88rem; margin-bottom:4px;">
            <span><strong style="color:#fff;"><?php echo htmlspecialchars($st['name']); ?></strong> (<?php echo htmlspecialchars($st['short_code']); ?>)</span>
            <strong style="color:var(--accent-green);"><?php echo $st['total_points']; ?> PTS</strong>
          </div>
          <div style="background:var(--bg-input); height:10px; border-radius:5px; overflow:hidden;">
            <div style="background:<?php echo $st['color_code']; ?>; width:<?php echo max(5, $pct); ?>%; height:100%; border-radius:5px; transition:width 0.5s ease;"></div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Match Status Breakdown -->
  <div class="card">
    <div class="section-header" style="margin-bottom: 16px;">
      <h2>📊 Fixture Status Breakdown</h2>
    </div>

    <div style="display:flex; flex-direction:column; gap:16px; justify-content:center; height:calc(100% - 40px);">
      <div>
        <div style="display:flex; justify-content:space-between; font-size:0.9rem; margin-bottom:6px;">
          <span>Completed Events</span>
          <strong><?php echo $completedMatches; ?> / <?php echo max(1, $totalFixtures); ?></strong>
        </div>
        <div style="background:var(--bg-input); height:12px; border-radius:6px; overflow:hidden;">
          <div style="background:var(--accent-green); width:<?php echo $totalFixtures > 0 ? round(($completedMatches / $totalFixtures) * 100) : 0; ?>%; height:100%;"></div>
        </div>
      </div>

      <div>
        <div style="display:flex; justify-content:space-between; font-size:0.9rem; margin-bottom:6px;">
          <span>Upcoming Scheduled Matches</span>
          <strong><?php echo $upcomingMatches; ?> / <?php echo max(1, $totalFixtures); ?></strong>
        </div>
        <div style="background:var(--bg-input); height:12px; border-radius:6px; overflow:hidden;">
          <div style="background:var(--accent-amber); width:<?php echo $totalFixtures > 0 ? round(($upcomingMatches / $totalFixtures) * 100) : 0; ?>%; height:100%;"></div>
        </div>
      </div>

      <div>
        <div style="display:flex; justify-content:space-between; font-size:0.9rem; margin-bottom:6px;">
          <span>Live Ongoing Matches</span>
          <strong><?php echo $liveMatches; ?> / <?php echo max(1, $totalFixtures); ?></strong>
        </div>
        <div style="background:var(--bg-input); height:12px; border-radius:6px; overflow:hidden;">
          <div style="background:var(--accent-red); width:<?php echo $totalFixtures > 0 ? round(($liveMatches / $totalFixtures) * 100) : 0; ?>%; height:100%;"></div>
        </div>
      </div>

      <div>
        <div style="display:flex; justify-content:space-between; font-size:0.9rem; margin-bottom:6px;">
          <span>Cancelled / Postponed Events</span>
          <strong><?php echo $cancelledMatches; ?> / <?php echo max(1, $totalFixtures); ?></strong>
        </div>
        <div style="background:var(--bg-input); height:12px; border-radius:6px; overflow:hidden;">
          <div style="background:#64748b; width:<?php echo $totalFixtures > 0 ? round(($cancelledMatches / $totalFixtures) * 100) : 0; ?>%; height:100%;"></div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Official Department Standings Summary Table -->
<div class="card">
  <div class="section-header" style="margin-bottom: 16px;">
    <h2>🏆 Department Overall Trophy Standings</h2>
  </div>

  <div class="table-responsive">
    <table class="sports-table">
      <thead>
        <tr>
          <th>Rank</th>
          <th>Department</th>
          <th>Gold 🥇</th>
          <th>Silver 🥈</th>
          <th>Bronze 🥉</th>
          <th>Total Points</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($overallStandings as $st): ?>
          <tr>
            <td><strong>#<?php echo $st['rank']; ?></strong></td>
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

<?php include_once __DIR__ . '/../includes/header.php'; ?>
