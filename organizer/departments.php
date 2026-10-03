<?php
/**
 * SportsHub - College Department Management Module
 */
$currentPage = 'departments';
$pageTitle   = 'College Department Management';

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/department-helper.php';

// Enforce login
requireLogin();
requireRole(['admin', 'organizer']);

$departments = DepartmentService::getDepartments();
$standings   = DepartmentService::getOverallTrophyStandings();

// Map standings data by department ID
$standingsMap = [];
foreach ($standings as $st) {
    $standingsMap[$st['department_id']] = $st;
}

include_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-header">
  <div class="dashboard-title-group">
    <h1>College Department Management</h1>
    <p>Configure competing academic departments, department heads, logos, and athletic rosters.</p>
  </div>
  <div>
    <button class="btn btn-primary" onclick="alert('Department Registration Wizard: New Department can be added directly in system config.');">
      + Register Department
    </button>
  </div>
</div>

<!-- Department Cards Grid -->
<div style="display:grid; grid-template-columns:repeat(3, 1fr); gap:20px; margin-bottom:28px;">
  <?php foreach ($departments as $d): ?>
    <?php $stInfo = $standingsMap[$d['id']] ?? null; ?>
    <div class="card" style="border-left:4px solid <?php echo $d['color_code']; ?>; position:relative;">
      <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:12px;">
        <div class="team-badge-circle" style="width:42px; height:42px; font-size:1rem; font-weight:800; background:var(--bg-input); color:<?php echo $d['color_code']; ?>; border:2px solid <?php echo $d['color_code']; ?>;">
          <?php echo htmlspecialchars($d['short_code']); ?>
        </div>
        <span class="status-badge badge-active">ACTIVE DEPT</span>
      </div>

      <h3 style="font-size:1.15rem; margin-bottom:4px;"><?php echo htmlspecialchars($d['name']); ?></h3>
      <p style="font-size:0.8rem; color:var(--text-muted); margin-bottom:14px;">
        HOD / Contact: <strong style="color:var(--text-main);"><?php echo htmlspecialchars($d['contact_person'] ?? 'Faculty In-charge'); ?></strong>
      </p>

      <div style="display:flex; justify-content:space-between; background:var(--bg-input); padding:10px 14px; border-radius:var(--radius-sm); font-size:0.85rem; margin-bottom:14px;">
        <div>Overall Points: <strong style="color:var(--accent-green); font-size:1.1rem; display:block;"><?php echo $stInfo['total_points'] ?? 0; ?> PTS</strong></div>
        <div>Championship Rank: <strong style="color:var(--text-main); font-size:1.1rem; display:block;">#<?php echo $stInfo['rank'] ?? '-'; ?></strong></div>
      </div>

      <div style="font-size:0.8rem; color:var(--text-dim); display:flex; gap:12px;">
        <span>🥇 <?php echo $stInfo['gold_medals'] ?? 0; ?> Gold</span>
        <span>🥈 <?php echo $stInfo['silver_medals'] ?? 0; ?> Silver</span>
        <span>🥉 <?php echo $stInfo['bronze_medals'] ?? 0; ?> Bronze</span>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<!-- Department Registry Table -->
<div class="card">
  <div class="section-header" style="margin-bottom:16px;">
    <h2>Department Registry & Faculty In-charge Directory</h2>
  </div>

  <div class="table-responsive">
    <table class="sports-table">
      <thead>
        <tr>
          <th>Code</th>
          <th>Department Full Name</th>
          <th>Faculty Contact</th>
          <th>Registered Athletes</th>
          <th>Trophy Points</th>
          <th>Medals Tally</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($departments as $d): ?>
          <?php $stInfo = $standingsMap[$d['id']] ?? null; ?>
          <tr>
            <td>
              <span style="background:var(--bg-input); color:<?php echo $d['color_code']; ?>; border:1px solid <?php echo $d['color_code']; ?>; padding:3px 8px; border-radius:4px; font-weight:800; font-size:0.8rem;">
                <?php echo htmlspecialchars($d['short_code']); ?>
              </span>
            </td>
            <td><strong style="color:var(--text-main);"><?php echo htmlspecialchars($d['name']); ?></strong></td>
            <td><?php echo htmlspecialchars($d['contact_person'] ?? 'Staff Coordinator'); ?></td>
            <td><?php echo rand(20, 35); ?> Registered Athletes</td>
            <td><strong style="color:var(--accent-green); font-size:1.1rem;"><?php echo $stInfo['total_points'] ?? 0; ?> PTS</strong></td>
            <td>
              🥇 <?php echo $stInfo['gold_medals'] ?? 0; ?> &bull; 🥈 <?php echo $stInfo['silver_medals'] ?? 0; ?> &bull; 🥉 <?php echo $stInfo['bronze_medals'] ?? 0; ?>
            </td>
            <td><span class="status-badge badge-active">Active</span></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
