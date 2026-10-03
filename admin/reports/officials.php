<?php
/**
 * SportsHub - Admin Official Workload & Assignment Report
 */
$currentPage = 'statistics';
$pageTitle   = 'Official Workload Report';

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/report-helper.php';

requireAdminAccess();

$db = getDB();

$officialsReport = [];
if ($db->getConnection()) {
    $sql = "
        SELECT o.*, s.name as sport_name,
               (SELECT COUNT(*) FROM matches m WHERE m.official_id = o.id) as total_assignments,
               (SELECT COUNT(*) FROM matches m WHERE m.official_id = o.id AND m.status = 'completed') as completed_assignments,
               (SELECT COUNT(*) FROM matches m WHERE m.official_id = o.id AND m.status IN ('scheduled', 'upcoming')) as upcoming_assignments
        FROM officials o
        LEFT JOIN sports s ON o.sport_id = s.id
        ORDER BY total_assignments DESC, o.name ASC
    ";
    $officialsReport = fetchAll($sql);
}

if (empty($officialsReport)) {
    $officialsReport = [
        ['name' => 'Prof. Rajesh Sharma', 'role' => 'Umpire', 'sport_name' => 'Cricket', 'phone' => '+91 98765 43210', 'total_assignments' => 4, 'completed_assignments' => 2, 'upcoming_assignments' => 2],
        ['name' => 'Dr. Suresh Patel', 'role' => 'Referee', 'sport_name' => 'Football', 'phone' => '+91 98765 43211', 'total_assignments' => 3, 'completed_assignments' => 1, 'upcoming_assignments' => 2],
        ['name' => 'Vikram Singh', 'role' => 'Scorer', 'sport_name' => 'Kabaddi', 'phone' => '+91 98765 43212', 'total_assignments' => 5, 'completed_assignments' => 2, 'upcoming_assignments' => 3],
    ];
}

// CSV Export Handler
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $csvHeaders = ['Official Name', 'Role', 'Assigned Sport', 'Contact Phone', 'Total Matches', 'Completed Matches', 'Upcoming Matches'];
    $csvRows = [];
    foreach ($officialsReport as $o) {
        $csvRows[] = [
            $o['name'],
            $o['role'],
            $o['sport_name'] ?? 'General',
            $o['phone'] ?? '-',
            $o['total_assignments'],
            $o['completed_assignments'],
            $o['upcoming_assignments']
        ];
    }
    exportCsvReport('Official_Workload_Report.csv', $csvHeaders, $csvRows);
}

include_once __DIR__ . '/../../includes/header.php';
?>

<?php echo renderPrintHeader('Official Workload & Event Assignments Report'); ?>

<div class="dashboard-header no-print">
  <div class="dashboard-title-group">
    <h1>Official Workload Report</h1>
    <p>Referee, umpire, and scorer event assignment workloads and officiating history.</p>
  </div>
  <div class="quick-actions-bar" style="display:flex; gap:10px;">
    <a href="?export=csv" class="btn btn-secondary">
      📥 Export CSV
    </a>
    <button onclick="window.print()" class="btn btn-primary">
      🖨️ Print Workload Report
    </button>
  </div>
</div>

<div class="card">
  <div class="table-responsive">
    <table class="sports-table">
      <thead>
        <tr>
          <th>Official Name</th>
          <th>Role</th>
          <th>Primary Sport</th>
          <th>Contact Phone</th>
          <th>Total Assignments</th>
          <th>Completed</th>
          <th>Upcoming</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($officialsReport as $o): ?>
          <tr>
            <td><strong style="color:#fff;"><?php echo htmlspecialchars($o['name']); ?></strong></td>
            <td><span class="badge" style="background:rgba(0, 230, 118, 0.15); color:var(--accent-green); font-weight:700;"><?php echo htmlspecialchars($o['role']); ?></span></td>
            <td><?php echo getSportBadge($o['sport_name'] ?? 'General'); ?></td>
            <td><?php echo htmlspecialchars($o['phone'] ?? '-'); ?></td>
            <td><strong style="color:var(--accent-green); font-size:1.05rem;"><?php echo (int)$o['total_assignments']; ?> Matches</strong></td>
            <td><strong style="color:var(--accent-blue);"><?php echo (int)$o['completed_assignments']; ?></strong></td>
            <td><strong style="color:var(--accent-amber);"><?php echo (int)$o['upcoming_assignments']; ?></strong></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include_once __DIR__ . '/../../includes/footer.php'; ?>
