<?php
/**
 * SportsHub - Admin Official Results Report
 */
$currentPage = 'statistics';
$pageTitle   = 'Completed Results Report';

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/match-functions.php';
require_once __DIR__ . '/../../includes/report-helper.php';

requireAdminAccess();

$sportId = intval($_GET['sport_id'] ?? 0);
$db = getDB();

$matchesList = getMatchesFiltered('', 0, $sportId, '', 0, 'completed');

// CSV Export Handler
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $csvHeaders = ['Match ID', 'Date', 'Sport', 'Match Fixture', 'Final Score', 'Winner', 'Venue', 'Result Summary'];
    $csvRows = [];
    foreach ($matchesList as $m) {
        $csvRows[] = [
            $m['id'],
            $m['scheduled_date'],
            $m['sport_name'],
            $m['team_a_name'] . ' vs ' . $m['team_b_name'],
            ($m['score_a'] ?? '-') . ' - ' . ($m['score_b'] ?? '-'),
            $m['winner_name'] ?? 'Draw/Tie',
            $m['venue_name'] ?? 'TBD',
            $m['result_summary'] ?? 'Completed'
        ];
    }
    exportCsvReport('Championship_Results_Report.csv', $csvHeaders, $csvRows);
}

$sportsList = getSportsList();

include_once __DIR__ . '/../../includes/header.php';
?>

<?php echo renderPrintHeader('Official Verified Match Results Report'); ?>

<div class="dashboard-header no-print">
  <div class="dashboard-title-group">
    <h1>Official Completed Results Report</h1>
    <p>Verified match outcomes, final scorecards, and winner declarations.</p>
  </div>
  <div class="quick-actions-bar" style="display:flex; gap:10px;">
    <a href="?export=csv<?php echo $sportId ? "&sport_id={$sportId}" : ''; ?>" class="btn btn-secondary">
      📥 Export CSV
    </a>
    <button onclick="window.print()" class="btn btn-primary">
      🖨️ Print Results Report
    </button>
  </div>
</div>

<!-- Filter Bar -->
<div class="card no-print" style="margin-bottom: 24px;">
  <form method="GET" action="" style="display: flex; gap: 16px; align-items: center;">
    <label style="font-size: 0.85rem; font-weight: 700; color: var(--text-muted);">FILTER SPORT:</label>
    <select name="sport_id" class="form-control" style="max-width: 220px;" onchange="this.form.submit()">
      <option value="0">All Sports</option>
      <?php foreach ($sportsList as $s): ?>
        <option value="<?php echo $s['id']; ?>" <?php echo $sportId == $s['id'] ? 'selected' : ''; ?>>
          <?php echo htmlspecialchars($s['name']); ?>
        </option>
      <?php endforeach; ?>
    </select>
  </form>
</div>

<div class="card">
  <div class="table-responsive">
    <table class="sports-table">
      <thead>
        <tr>
          <th>Date</th>
          <th>Sport</th>
          <th>Match Fixture</th>
          <th>Final Score</th>
          <th>Department Winner</th>
          <th>Venue</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($matchesList as $m): ?>
          <tr>
            <td style="font-size:0.85rem; color:var(--text-muted);"><?php echo date('M d, Y', strtotime($m['scheduled_date'])); ?></td>
            <td><?php echo getSportBadge($m['sport_name']); ?></td>
            <td><strong style="color:#fff;"><?php echo htmlspecialchars($m['team_a_name']); ?> vs <?php echo htmlspecialchars($m['team_b_name']); ?></strong></td>
            <td><strong style="color:var(--accent-green); font-size:1.05rem;"><?php echo htmlspecialchars($m['score_a'] ?? '-'); ?> - <?php echo htmlspecialchars($m['score_b'] ?? '-'); ?></strong></td>
            <td><strong style="color:#ffd700;">🥇 <?php echo htmlspecialchars($m['winner_name'] ?? 'Draw / Tie'); ?></strong></td>
            <td><?php echo htmlspecialchars($m['venue_name'] ?? 'TBD'); ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include_once __DIR__ . '/../../includes/footer.php'; ?>
