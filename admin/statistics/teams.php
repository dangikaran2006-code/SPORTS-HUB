<?php
/**
 * SportsHub - Admin Team Statistics & Performance Metrics
 */
$currentPage = 'statistics';
$pageTitle   = 'Team Statistics Leaderboard';

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/department-helper.php';
require_once __DIR__ . '/../../includes/report-helper.php';

requireAdminAccess();

$sportId = intval($_GET['sport_id'] ?? 0);
$db = getDB();

$teamStats = [];
if ($db->getConnection()) {
    $sql = "
        SELECT tm.id as team_id, tm.name as team_name, tm.short_name,
               s.name as sport_name, d.name as department_name, d.short_code as dept_code,
               pt.played, pt.won, pt.lost, pt.drawn, pt.points, pt.score_difference
        FROM teams tm
        JOIN sports s ON tm.sport_id = s.id
        LEFT JOIN departments d ON tm.department_id = d.id
        LEFT JOIN points_table pt ON tm.id = pt.team_id
        WHERE 1=1
    ";
    $params = [];
    if ($sportId > 0) {
        $sql .= " AND tm.sport_id = :sid";
        $params[':sid'] = $sportId;
    }
    $sql .= " ORDER BY pt.points DESC, pt.won DESC, tm.name ASC";
    $teamStats = fetchAll($sql, $params);
}

if (empty($teamStats)) {
    $teamStats = [
        ['team_name' => 'Royal Strikers', 'short_name' => 'RST', 'sport_name' => 'Cricket', 'department_name' => 'Computer Engineering / BCA', 'dept_code' => 'CSE', 'played' => 4, 'won' => 3, 'lost' => 1, 'drawn' => 0, 'points' => 6, 'score_difference' => 45],
        ['team_name' => 'Thunder Warriors', 'short_name' => 'TWR', 'sport_name' => 'Cricket', 'department_name' => 'Mechanical Engineering', 'dept_code' => 'ME', 'played' => 4, 'won' => 2, 'lost' => 2, 'drawn' => 0, 'points' => 4, 'score_difference' => 12],
        ['team_name' => 'Apex FC', 'short_name' => 'AFC', 'sport_name' => 'Football', 'department_name' => 'Computer Engineering / BCA', 'dept_code' => 'CSE', 'played' => 3, 'won' => 2, 'lost' => 0, 'drawn' => 1, 'points' => 7, 'score_difference' => 4],
        ['team_name' => 'City Titans FC', 'short_name' => 'CTF', 'sport_name' => 'Football', 'department_name' => 'Civil Engineering', 'dept_code' => 'CE', 'played' => 3, 'won' => 1, 'lost' => 1, 'drawn' => 1, 'points' => 4, 'score_difference' => 0],
    ];
}

// Handle CSV Export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $csvHeaders = ['Team Name', 'Short Code', 'Department', 'Sport', 'Played', 'Won', 'Drawn', 'Lost', 'Points', 'Score Diff'];
    $csvRows = [];
    foreach ($teamStats as $ts) {
        $csvRows[] = [
            $ts['team_name'],
            $ts['short_name'],
            $ts['department_name'] ?? 'General',
            $ts['sport_name'],
            $ts['played'] ?? 0,
            $ts['won'] ?? 0,
            $ts['drawn'] ?? 0,
            $ts['lost'] ?? 0,
            $ts['points'] ?? 0,
            $ts['score_difference'] ?? 0
        ];
    }
    exportCsvReport('Team_Statistics_Report.csv', $csvHeaders, $csvRows);
}

$sportsList = [];
if ($db->getConnection()) {
    $sportsList = fetchAll("SELECT id, name FROM sports ORDER BY name ASC");
}

include_once __DIR__ . '/../../includes/header.php';
?>

<?php echo renderPrintHeader('Official Team Performance Report'); ?>

<div class="dashboard-header no-print">
  <div class="dashboard-title-group">
    <h1>Team Statistics & Performance Directory</h1>
    <p>Comprehensive team win/loss records, department representations, points, and score differentials.</p>
  </div>
  <div class="quick-actions-bar" style="display:flex; gap:10px;">
    <a href="?export=csv<?php echo $sportId ? "&sport_id={$sportId}" : ''; ?>" class="btn btn-secondary">
      📥 Export CSV
    </a>
    <button onclick="window.print()" class="btn btn-primary">
      🖨️ Print Report
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

<!-- Team Statistics Table -->
<div class="card">
  <div class="table-responsive">
    <table class="sports-table">
      <thead>
        <tr>
          <th>Team Name</th>
          <th>Department</th>
          <th>Sport</th>
          <th>Played</th>
          <th>Won</th>
          <th>Drawn</th>
          <th>Lost</th>
          <th>Score Diff</th>
          <th>Total Points</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($teamStats as $t): ?>
          <tr>
            <td>
              <div style="display:flex; align-items:center; gap:8px;">
                <strong style="color:var(--text-main);"><?php echo htmlspecialchars($t['team_name']); ?></strong>
                <code style="font-size:0.75rem;"><?php echo htmlspecialchars($t['short_name']); ?></code>
              </div>
            </td>
            <td><?php echo htmlspecialchars($t['department_name'] ?? 'General'); ?></td>
            <td><?php echo getSportBadge($t['sport_name']); ?></td>
            <td><?php echo (int)($t['played'] ?? 0); ?></td>
            <td><strong style="color:var(--accent-green);"><?php echo (int)($t['won'] ?? 0); ?></strong></td>
            <td><?php echo (int)($t['drawn'] ?? 0); ?></td>
            <td><strong style="color:var(--accent-red);"><?php echo (int)($t['lost'] ?? 0); ?></strong></td>
            <td><?php echo ($t['score_difference'] >= 0 ? '+' : '') . (int)($t['score_difference'] ?? 0); ?></td>
            <td><strong style="color:var(--accent-green); font-size:1.1rem;"><?php echo (int)($t['points'] ?? 0); ?> PTS</strong></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include_once __DIR__ . '/../../includes/footer.php'; ?>
