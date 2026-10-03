<?php
/**
 * SportsHub - Admin Printable Department Trophy Report
 */
$currentPage = 'statistics';
$pageTitle   = 'Department Trophy Standings Report';

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/department-helper.php';
require_once __DIR__ . '/../../includes/report-helper.php';

requireAdminAccess();

$overallStandings = DepartmentService::getOverallTrophyStandings();

// CSV Export Handler
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $csvHeaders = ['Rank', 'Department Name', 'Short Code', 'Gold Medals', 'Silver Medals', 'Bronze Medals', 'Total Trophy Points'];
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
    exportCsvReport('Department_Trophy_Standings.csv', $csvHeaders, $csvRows);
}

include_once __DIR__ . '/../../includes/header.php';
?>

<?php echo renderPrintHeader('Official Overall Department Trophy Standings Report'); ?>

<div class="dashboard-header no-print">
  <div class="dashboard-title-group">
    <h1>Official Department Trophy Standings Report</h1>
    <p>Official championship trophy leaderboard, medal tallies, and overall department points.</p>
  </div>
  <div class="quick-actions-bar" style="display:flex; gap:10px;">
    <a href="?export=csv" class="btn btn-secondary">
      📥 Export CSV
    </a>
    <button onclick="window.print()" class="btn btn-primary">
      🖨️ Print Trophy Report
    </button>
  </div>
</div>

<div class="card">
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
            <td>
              <strong style="font-size:1.1rem; color:<?php echo $st['rank'] === 1 ? '#ffd700' : ($st['rank'] === 2 ? '#cbd5e1' : ($st['rank'] === 3 ? '#f97316' : 'var(--text-main)')); ?>;">
                #<?php echo $st['rank']; ?>
              </strong>
            </td>
            <td><strong style="color:#fff;"><?php echo htmlspecialchars($st['name']); ?></strong></td>
            <td><code><?php echo htmlspecialchars($st['short_code']); ?></code></td>
            <td><strong style="color:#ffd700;"><?php echo $st['gold_medals']; ?></strong></td>
            <td><strong style="color:#cbd5e1;"><?php echo $st['silver_medals']; ?></strong></td>
            <td><strong style="color:#f97316;"><?php echo $st['bronze_medals']; ?></strong></td>
            <td><strong style="color:var(--accent-green); font-size:1.25rem;"><?php echo $st['total_points']; ?> PTS</strong></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include_once __DIR__ . '/../../includes/footer.php'; ?>
