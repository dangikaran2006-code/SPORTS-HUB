<?php
/**
 * SportsHub - Championship Control Center
 */
$currentPage = 'championship';
$pageTitle = 'Championship Control Center';

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/department-helper.php';

requireAdminAccess();

$db = getDB();
$championship = DepartmentService::getMasterChampionship();
$allMatches = $db->getMatches('all');
$liveMatches = $db->getMatches('live');

$totalMatches = count($allMatches);
$completedMatches = count(array_filter($allMatches, function($m) {
    return strtolower($m['status']) === 'completed' || strtolower($m['status']) === 'finished';
}));
$upcomingMatches = count(array_filter($allMatches, function($m) {
    return strtolower($m['status']) === 'scheduled' || strtolower($m['status']) === 'upcoming';
}));
$progressPercent = $totalMatches > 0 ? round(($completedMatches / $totalMatches) * 100) : 0;

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['championship_action'] ?? '';
    if ($action === 'update_status') {
        $newStatus = $_POST['status'] ?? 'Live';
        $_SESSION['master_championship']['status'] = $newStatus;
        $msg = "Championship status successfully updated to: <strong>" . htmlspecialchars($newStatus) . "</strong>";
    } elseif ($action === 'update_settings') {
        $_SESSION['master_championship']['name'] = $_POST['name'] ?? $championship['name'];
        $_SESSION['master_championship']['college_name'] = $_POST['college_name'] ?? $championship['college_name'];
        $_SESSION['master_championship']['academic_year'] = $_POST['academic_year'] ?? $championship['academic_year'];
        $msg = "Championship configuration settings saved successfully.";
    }
    $championship = DepartmentService::getMasterChampionship();
}

include_once __DIR__ . '/../includes/header.php';
?>

<!-- Championship Header Card -->
<div class="card" style="margin-bottom: 24px; background: linear-gradient(135deg, rgba(14, 21, 38, 0.95), rgba(10, 15, 26, 0.95)); border: 1px solid var(--border-subtle); padding: 24px;">
  <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px;">
    <div>
      <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
        <span class="badge" style="background: rgba(0, 230, 118, 0.15); color: var(--accent-green); border: 1px solid rgba(0, 230, 118, 0.3); padding: 4px 10px; font-weight: 700; font-size: 0.75rem;">
          AY <?php echo htmlspecialchars($championship['academic_year']); ?>
        </span>
        <span class="badge" style="background: rgba(245, 158, 11, 0.15); color: var(--accent-amber); font-weight: 700;">
          STATUS: <?php echo strtoupper(htmlspecialchars($championship['status'])); ?>
        </span>
      </div>
      <h1 style="font-size: 1.8rem; font-weight: 800; color: #fff; margin-bottom: 4px;">
        <?php echo htmlspecialchars($championship['name']); ?>
      </h1>
      <p style="color: var(--text-muted); font-size: 0.92rem; margin: 0;">
        College: <strong><?php echo htmlspecialchars($championship['college_name']); ?></strong> &bull; Phase: <strong>Active League Stage & Knockouts</strong>
      </p>
    </div>

    <!-- Status Change Action Buttons -->
    <form action="" method="POST" style="display: flex; gap: 8px; flex-wrap: wrap;" onsubmit="return confirm('Confirm championship status change?');">
      <input type="hidden" name="championship_action" value="update_status">
      <button type="submit" name="status" value="Upcoming" class="btn btn-secondary btn-sm">Set Upcoming</button>
      <button type="submit" name="status" value="Live" class="btn btn-primary btn-sm" style="background: var(--accent-green); color: #000; font-weight: 700;">⚡ Set LIVE</button>
      <button type="submit" name="status" value="Completed" class="btn btn-secondary btn-sm" style="color: var(--accent-gold);">🏆 Complete</button>
    </form>
  </div>
</div>

<?php if (!empty($msg)): ?>
  <div class="auth-alert auth-alert-success" style="margin-bottom: 24px; background: rgba(0, 230, 118, 0.15); border: 1px solid rgba(0, 230, 118, 0.3); color: var(--accent-green); padding: 12px 16px; border-radius: 8px;">
    <span><?php echo $msg; ?></span>
  </div>
<?php endif; ?>

<!-- Overall Championship Progress Bar -->
<div class="card" style="margin-bottom: 24px;">
  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
    <h3 style="font-size: 1rem; margin: 0; color: #fff;">Championship Match Execution Progress</h3>
    <span style="font-size: 1.1rem; font-weight: 800; color: var(--accent-green);"><?php echo $progressPercent; ?>% Completed</span>
  </div>
  <div style="width: 100%; height: 12px; background: var(--bg-dark-surface); border-radius: 6px; overflow: hidden; border: 1px solid var(--border-subtle);">
    <div style="width: <?php echo $progressPercent; ?>%; height: 100%; background: linear-gradient(90deg, var(--accent-green), #059669); transition: width 0.4s ease;"></div>
  </div>
  <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 8px;">
    Completed <?php echo $completedMatches; ?> of <?php echo $totalMatches; ?> scheduled matches. <?php echo count($liveMatches); ?> currently live.
  </div>
</div>

<!-- Key Performance Indicators Grid -->
<div class="stats-grid" style="margin-bottom: 24px;">
  <div class="stat-card green-accent">
    <div class="stat-details">
      <h3>6</h3>
      <span>Departments</span>
    </div>
  </div>
  <div class="stat-card amber-accent">
    <div class="stat-details">
      <h3>10</h3>
      <span>Sports Events</span>
    </div>
  </div>
  <div class="stat-card red-accent">
    <div class="stat-details">
      <h3><?php echo count($liveMatches); ?></h3>
      <span>Live Matches Now</span>
    </div>
  </div>
  <div class="stat-card purple-accent">
    <div class="stat-details">
      <h3><?php echo $totalMatches; ?></h3>
      <span>Total Fixtures</span>
    </div>
  </div>
</div>

<!-- Settings & Control Form -->
<div class="card">
  <div class="section-header" style="margin-bottom: 16px;">
    <h2>⚙️ Championship Configuration Settings</h2>
  </div>

  <form action="" method="POST" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
    <input type="hidden" name="championship_action" value="update_settings">

    <div class="form-group">
      <label>Championship Title</label>
      <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($championship['name']); ?>" required>
    </div>

    <div class="form-group">
      <label>Host College Name</label>
      <input type="text" name="college_name" class="form-control" value="<?php echo htmlspecialchars($championship['college_name']); ?>" required>
    </div>

    <div class="form-group">
      <label>Academic Year</label>
      <input type="text" name="academic_year" class="form-control" value="<?php echo htmlspecialchars($championship['academic_year']); ?>" required>
    </div>

    <div class="form-group">
      <label>Public Visibility</label>
      <select name="visibility" class="form-control">
        <option value="public" selected>Public Spectators Allowed</option>
        <option value="internal">Internal Campus Only</option>
      </select>
    </div>

    <div style="grid-column: span 2;">
      <button type="submit" class="btn btn-primary" style="padding: 10px 24px;">
        💾 Save Championship Configurations
      </button>
    </div>
  </form>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
