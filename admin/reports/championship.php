<?php
/**
 * SportsHub - Admin Official Championship Summary Report
 */
$currentPage = 'statistics';
$pageTitle   = 'Championship Master Report';

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/department-helper.php';
require_once __DIR__ . '/../../includes/report-helper.php';

requireAdminAccess();

$db = getDB();

// Aggregated Summary Statistics
$masterChamp        = DepartmentService::getMasterChampionship();
$overallStandings   = DepartmentService::getOverallTrophyStandings();
$totalDepts         = fetchOne("SELECT COUNT(*) as cnt FROM departments WHERE status=1")['cnt'] ?? 6;
$totalSports        = fetchOne("SELECT COUNT(*) as cnt FROM sports")['cnt'] ?? 0;
$totalTeams         = fetchOne("SELECT COUNT(*) as cnt FROM teams")['cnt'] ?? 0;
$totalPlayers       = fetchOne("SELECT COUNT(*) as cnt FROM players")['cnt'] ?? 0;
$totalOfficials     = fetchOne("SELECT COUNT(*) as cnt FROM officials")['cnt'] ?? 0;
$totalMatches       = fetchOne("SELECT COUNT(*) as cnt FROM matches")['cnt'] ?? 0;
$completedMatches   = fetchOne("SELECT COUNT(*) as cnt FROM matches WHERE status='completed'")['cnt'] ?? 0;
$upcomingMatches    = fetchOne("SELECT COUNT(*) as cnt FROM matches WHERE status IN ('scheduled', 'upcoming')")['cnt'] ?? 0;
$liveMatches        = fetchOne("SELECT COUNT(*) as cnt FROM matches WHERE status='live'")['cnt'] ?? 0;

// CSV Export Handler
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $csvHeaders = ['Rank', 'Department', 'Short Code', 'Gold Medals', 'Silver Medals', 'Bronze Medals', 'Total Trophy Points'];
    $csvRows = [];
    foreach ($overallStandings as $st) {
        $csvRows[] = [
            $st['rank'],
            $st['name'],
            $st['short_code'],
            $st['gold_medals'],
            $st['silver_medals'],
            $st['bronze_medals'],
            $st['total_points']
        ];
    }
    exportCsvReport('Championship_Summary_Report.csv', $csvHeaders, $csvRows);
}

include_once __DIR__ . '/../../includes/header.php';
?>

<?php echo renderPrintHeader('Official Master Championship Summary Report'); ?>

<div class="dashboard-header no-print">
  <div class="dashboard-title-group">
    <h1>Official Championship Summary Report</h1>
    <p>Complete executive summary of participation, events, standings, and overall trophy leaderboard.</p>
  </div>
  <div class="quick-actions-bar" style="display:flex; gap:10px;">
    <a href="?export=csv" class="btn btn-secondary">
      📥 Export CSV
    </a>
    <button onclick="window.print()" class="btn btn-primary">
      🖨️ Print Report
    </button>
  </div>
</div>

<!-- Championship Info Summary Grid -->
<div class="grid grid-2" style="gap:20px; margin-bottom:24px;">
  <div class="card">
    <h3 style="font-size:1.1rem; color:var(--text-main); margin-bottom:14px; font-weight:700;">Championship Metadata</h3>
    <div style="font-size:0.9rem; line-height:1.8;">
      <div><strong style="color:#fff;">Championship Name:</strong> <?php echo htmlspecialchars($masterChamp['name']); ?></div>
      <div><strong style="color:#fff;">Host Institution:</strong> <?php echo htmlspecialchars(getSetting('college_name')); ?></div>
      <div><strong style="color:#fff;">Academic Year:</strong> <?php echo htmlspecialchars(getSetting('academic_year')); ?></div>
      <div><strong style="color:#fff;">Start & End Dates:</strong> <?php echo htmlspecialchars($masterChamp['start_date']); ?> to <?php echo htmlspecialchars($masterChamp['end_date']); ?></div>
      <div><strong style="color:#fff;">Championship Status:</strong> <span class="status-badge badge-active"><?php echo htmlspecialchars($masterChamp['status']); ?></span></div>
    </div>
  </div>

  <div class="card">
    <h3 style="font-size:1.1rem; color:var(--text-main); margin-bottom:14px; font-weight:700;">Participation & Fixture Metrics</h3>
    <div style="font-size:0.9rem; line-height:1.8;">
      <div><strong style="color:#fff;">Competing Departments:</strong> <?php echo $totalDepts; ?> Academic Departments</div>
      <div><strong style="color:#fff;">Configured Sports Events:</strong> <?php echo $totalSports; ?> Active Sports</div>
      <div><strong style="color:#fff;">Total Registered Teams:</strong> <?php echo $totalTeams; ?> Teams</div>
      <div><strong style="color:#fff;">Total Registered Athletes:</strong> <?php echo $totalPlayers; ?> Players</div>
      <div><strong style="color:#fff;">Total Fixtures Scheduled:</strong> <?php echo $totalMatches; ?> Matches (Completed: <?php echo $completedMatches; ?>, Upcoming: <?php echo $upcomingMatches; ?>, Live: <?php echo $liveMatches; ?>)</div>
    </div>
  </div>
</div>

<!-- Department Trophy Standings -->
<div class="card">
  <div class="section-header" style="margin-bottom:16px;">
    <h2>🏆 Official Department Trophy Standings</h2>
  </div>

  <div class="table-responsive">
    <table class="sports-table">
      <thead>
        <tr>
          <th>Rank</th>
          <th>Department Name</th>
          <th>Code</th>
          <th>Gold 🥇</th>
          <th>Silver 🥈</th>
          <th>Bronze 🥉</th>
          <th>Total Trophy Points</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($overallStandings as $st): ?>
          <tr>
            <td><strong>#<?php echo $st['rank']; ?></strong></td>
            <td><strong style="color:#fff;"><?php echo htmlspecialchars($st['name']); ?></strong></td>
            <td><code><?php echo htmlspecialchars($st['short_code']); ?></code></td>
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

<?php include_once __DIR__ . '/../../includes/footer.php'; ?>
