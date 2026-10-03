<?php
/**
 * SportsHub - Admin Department Management & Detail Control Center
 */
$currentPage = 'departments';
$pageTitle = 'Department Management';

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/department-helper.php';

requireAdminAccess();

$departments = DepartmentService::getDepartments();
$standings   = DepartmentService::getOverallTrophyStandings();

$standingsMap = [];
foreach ($standings as $st) {
    $standingsMap[$st['department_id']] = $st;
}

$deptId = intval($_GET['id'] ?? 0);
$activeDept = null;
if ($deptId > 0) {
    foreach ($departments as $d) {
        if ($d['id'] === $deptId) {
            $activeDept = $d;
            break;
        }
    }
}

include_once __DIR__ . '/../includes/header.php';
?>

<?php if ($activeDept): ?>
  <!-- Department Detail View -->
  <?php $stInfo = $standingsMap[$activeDept['id']] ?? null; ?>
  <div style="margin-bottom: 20px;">
    <a href="<?php echo BASE_URL; ?>/admin/departments.php" class="btn btn-secondary btn-sm">← Back to All Departments</a>
  </div>

  <div class="card" style="margin-bottom: 24px; border-left: 4px solid <?php echo $activeDept['color_code']; ?>;">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px;">
      <div>
        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
          <div class="team-badge-circle" style="width: 44px; height: 44px; font-size: 1.1rem; font-weight: 800; background: var(--bg-input); color: <?php echo $activeDept['color_code']; ?>; border: 2px solid <?php echo $activeDept['color_code']; ?>;">
            <?php echo htmlspecialchars($activeDept['short_code']); ?>
          </div>
          <div>
            <h1 style="font-size: 1.8rem; font-weight: 800; color: #fff; margin: 0;"><?php echo htmlspecialchars($activeDept['name']); ?></h1>
            <div style="font-size: 0.85rem; color: var(--text-muted);">Faculty Head: <strong><?php echo htmlspecialchars($activeDept['contact_person'] ?? 'HOD'); ?></strong></div>
          </div>
        </div>
      </div>

      <div style="text-align: right;">
        <div style="font-size: 2.2rem; font-weight: 800; color: var(--accent-green);"><?php echo $stInfo['total_points'] ?? 0; ?> <span style="font-size: 0.9rem; font-weight: 600;">PTS</span></div>
        <div style="font-size: 0.85rem; color: var(--accent-gold); font-weight: 700;">Championship Rank #<?php echo $stInfo['rank'] ?? '-'; ?></div>
      </div>
    </div>

    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-top: 20px; background: var(--bg-dark-surface); padding: 16px; border-radius: var(--radius-md);">
      <div>
        <div style="font-size: 0.78rem; color: var(--text-muted); font-weight: 700;">GOLD MEDALS</div>
        <div style="font-size: 1.5rem; font-weight: 800; color: #ffd700;">🥇 <?php echo $stInfo['gold_medals'] ?? 0; ?></div>
      </div>
      <div>
        <div style="font-size: 0.78rem; color: var(--text-muted); font-weight: 700;">SILVER MEDALS</div>
        <div style="font-size: 1.5rem; font-weight: 800; color: #cbd5e1;">🥈 <?php echo $stInfo['silver_medals'] ?? 0; ?></div>
      </div>
      <div>
        <div style="font-size: 0.78rem; color: var(--text-muted); font-weight: 700;">BRONZE MEDALS</div>
        <div style="font-size: 1.5rem; font-weight: 800; color: #f97316;">🥉 <?php echo $stInfo['bronze_medals'] ?? 0; ?></div>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="section-header" style="margin-bottom: 16px;">
      <h2>Sport Performance Breakdown</h2>
    </div>
    <div class="table-responsive">
      <table class="sports-table">
        <thead>
          <tr>
            <th>Sport Event</th>
            <th>Points Earned</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($stInfo['sport_breakdown'])): ?>
            <?php foreach ($stInfo['sport_breakdown'] as $sName => $pts): ?>
              <tr>
                <td><strong style="color: #fff;"><?php echo htmlspecialchars($sName); ?></strong></td>
                <td><strong style="color: var(--accent-green); font-size: 1.1rem;"><?php echo $pts; ?> PTS</strong></td>
                <td><span class="badge" style="background: rgba(0, 230, 118, 0.15); color: var(--accent-green); font-weight: 700;">Active</span></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

<?php else: ?>

  <!-- Main Department Overview Grid -->
  <div class="dashboard-header">
    <div class="dashboard-title-group">
      <h1>College Department Management</h1>
      <p>Configure academic departments, short codes, faculty coordinators, and overall points contribution.</p>
    </div>
  </div>

  <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; margin-bottom: 28px;">
    <?php foreach ($departments as $d): ?>
      <?php $stInfo = $standingsMap[$d['id']] ?? null; ?>
      <div class="card" style="border-left: 4px solid <?php echo $d['color_code']; ?>;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
          <div class="team-badge-circle" style="width: 42px; height: 42px; font-size: 1rem; font-weight: 800; background: var(--bg-input); color: <?php echo $d['color_code']; ?>; border: 2px solid <?php echo $d['color_code']; ?>;">
            <?php echo htmlspecialchars($d['short_code']); ?>
          </div>
          <span class="status-badge badge-active">ACTIVE DEPT</span>
        </div>

        <h3 style="font-size: 1.15rem; margin-bottom: 4px; color: #fff;"><?php echo htmlspecialchars($d['name']); ?></h3>
        <p style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 14px;">
          HOD In-charge: <strong style="color: var(--text-main);"><?php echo htmlspecialchars($d['contact_person'] ?? 'Faculty Coordinator'); ?></strong>
        </p>

        <div style="display: flex; justify-content: space-between; background: var(--bg-input); padding: 10px 14px; border-radius: var(--radius-sm); font-size: 0.85rem; margin-bottom: 14px;">
          <div>Trophy Points: <strong style="color: var(--accent-green); font-size: 1.1rem; display: block;"><?php echo $stInfo['total_points'] ?? 0; ?> PTS</strong></div>
          <div>Rank: <strong style="color: var(--text-main); font-size: 1.1rem; display: block;">#<?php echo $stInfo['rank'] ?? '-'; ?></strong></div>
        </div>

        <div style="display: flex; justify-content: space-between; align-items: center;">
          <div style="font-size: 0.78rem; color: var(--text-dim);">
            🥇 <?php echo $stInfo['gold_medals'] ?? 0; ?> &bull; 🥈 <?php echo $stInfo['silver_medals'] ?? 0; ?> &bull; 🥉 <?php echo $stInfo['bronze_medals'] ?? 0; ?>
          </div>
          <a href="<?php echo BASE_URL; ?>/admin/departments.php?id=<?php echo $d['id']; ?>" class="btn btn-secondary btn-sm">
            View Details →
          </a>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

<?php endif; ?>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
