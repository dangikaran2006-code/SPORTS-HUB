<?php
/**
 * SportsHub - Championship Setup Wizard & First-Use Experience
 */
$currentPage = 'setup';
$pageTitle = 'Championship Setup Wizard';

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/department-helper.php';
require_once __DIR__ . '/../includes/setup-helper.php';

requireAdminAccess();

$msg = '';
$error = '';
$csrfToken = generateCsrfToken();
$step = intval($_GET['step'] ?? 1);
if ($step < 1 || $step > 7) $step = 1;

// Fetch setup progress
$progress = getChampionshipSetupProgress();
$championship = DepartmentService::getMasterChampionship();

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    $action = $_POST['action'] ?? '';

    if (!validateCsrfToken($token)) {
        $error = 'Security validation failed (Invalid CSRF Token).';
    } else {
        if ($action === 'save_step1') {
            $collegeName  = trim($_POST['college_name'] ?? '');
            $champName    = trim($_POST['championship_name'] ?? '');
            $acadYear     = trim($_POST['academic_year'] ?? '');
            $startDate    = trim($_POST['start_date'] ?? '');
            $endDate      = trim($_POST['end_date'] ?? '');

            if (!empty($collegeName) && !empty($champName)) {
                updateSetting('college_name', $collegeName);
                updateSetting('championship_title', $champName);
                updateSetting('academic_year', $acadYear);
                updateSetting('championship_start_date', $startDate);
                updateSetting('championship_end_date', $endDate);

                logAuditAction('Championship Info Saved', 'Championship', 0, "Updated college '{$collegeName}' and title '{$champName}'");
                $msg = "Step 1 saved successfully. Proceeding to Step 2.";
                header("Location: " . BASE_URL . "/admin/setup.php?step=2");
                exit;
            } else {
                $error = 'College Name and Championship Title are required.';
            }
        } elseif ($action === 'add_department') {
            $dName = trim($_POST['dept_name'] ?? '');
            $dCode = trim($_POST['dept_code'] ?? '');
            $dShort = trim($_POST['dept_short'] ?? '');

            if (!empty($dName)) {
                insert('departments', [
                    'name'       => $dName,
                    'short_code' => strtoupper($dShort ?: $dCode),
                    'status'     => 1,
                    'created_at' => date('Y-m-d H:i:s')
                ]);
                logAuditAction('Department Added', 'Department', 0, "Added department '{$dName}'");
                $msg = "Department '{$dName}' added successfully.";
            } else {
                $error = 'Department Name is required.';
            }
        } elseif ($action === 'add_venue') {
            $vName = trim($_POST['venue_name'] ?? '');
            $vLoc  = trim($_POST['venue_location'] ?? '');
            $vCap  = intval($_POST['venue_capacity'] ?? 200);

            if (!empty($vName)) {
                insert('venues', [
                    'name'       => $vName,
                    'location'   => $vLoc ?: 'Main Campus',
                    'capacity'   => $vCap,
                    'status'     => 1,
                    'created_at' => date('Y-m-d H:i:s')
                ]);
                logAuditAction('Venue Added', 'Venue', 0, "Added venue '{$vName}'");
                $msg = "Venue '{$vName}' added successfully.";
            } else {
                $error = 'Venue Name is required.';
            }
        } elseif ($action === 'add_official') {
            $oName = trim($_POST['official_name'] ?? '');
            $oRole = trim($_POST['official_role'] ?? 'referee');
            $oSport= intval($_POST['sport_id'] ?? 1);

            if (!empty($oName)) {
                insert('officials', [
                    'name'       => $oName,
                    'role'       => strtolower($oRole),
                    'sport_id'   => $oSport,
                    'status'     => 1,
                    'created_at' => date('Y-m-d H:i:s')
                ]);
                logAuditAction('Official Added', 'Official', 0, "Added official '{$oName}'");
                $msg = "Official '{$oName}' added successfully.";
            } else {
                $error = 'Official Name is required.';
            }
        } elseif ($action === 'save_point_rules') {
            updateSetting('points_win', intval($_POST['points_win'] ?? 2));
            updateSetting('points_draw', intval($_POST['points_draw'] ?? 1));
            updateSetting('points_loss', intval($_POST['points_loss'] ?? 0));
            updateSetting('points_gold', intval($_POST['points_gold'] ?? 10));
            updateSetting('points_silver', intval($_POST['points_silver'] ?? 5));
            updateSetting('points_bronze', intval($_POST['points_bronze'] ?? 2));

            logAuditAction('Point Rules Saved', 'System', 0, 'Updated championship point scoring rules');
            $msg = 'Point scoring rules saved successfully.';
            header("Location: " . BASE_URL . "/admin/setup.php?step=7");
            exit;
        } elseif ($action === 'activate_championship') {
            $targetStatus = $_POST['target_status'] ?? 'ACTIVE';
            $res = activateChampionship($targetStatus);
            if ($res['success']) {
                $msg = $res['message'];
            } else {
                $error = $res['error'];
            }
        } elseif ($action === 'enable_demo') {
            $res = initializeDemoMode();
            if ($res['success']) $msg = $res['message'];
            else $error = $res['error'];
        } elseif ($action === 'reset_demo') {
            $res = resetDemoData();
            if ($res['success']) $msg = $res['message'];
            else $error = $res['error'];
        }
    }

    // Refresh setup progress
    $progress = getChampionshipSetupProgress();
    $championship = DepartmentService::getMasterChampionship();
}

// Fetch DB lists for steps
$departmentsList = fetchAll("SELECT * FROM departments ORDER BY name ASC");
$sportsList      = fetchAll("SELECT * FROM sports ORDER BY name ASC");
$venuesList      = fetchAll("SELECT * FROM venues ORDER BY name ASC");
$officialsList   = fetchAll("SELECT o.*, s.name as sport_name FROM officials o LEFT JOIN sports s ON o.sport_id = s.id ORDER BY o.name ASC");

include_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
  <div class="dashboard-title-group">
    <h1>🚀 Championship Initialization & Setup Wizard</h1>
    <p>Configure your college sports championship, setup departments, sports, venues, officials, and point rules in 7 guided steps.</p>
  </div>

  <div style="display: flex; gap: 10px;">
    <?php if (isDemoModeEnabled()): ?>
      <span class="status-badge badge-warning" style="font-size: 0.82rem; padding: 6px 12px; font-weight: 800;">
        ⚠️ DEMO MODE ACTIVE
      </span>
      <form action="" method="POST" style="margin: 0;" onsubmit="return confirm('Reset ONLY demo records? Real data will be preserved.');">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
        <input type="hidden" name="action" value="reset_demo">
        <button type="submit" class="btn btn-secondary btn-sm" style="color: var(--accent-red);">Reset Demo Data</button>
      </form>
    <?php else: ?>
      <form action="" method="POST" style="margin: 0;">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
        <input type="hidden" name="action" value="enable_demo">
        <button type="submit" class="btn btn-secondary btn-sm">🧪 Enable Sample Demo Mode</button>
      </form>
    <?php endif; ?>
  </div>
</div>

<?php if (!empty($msg)): ?>
  <div class="auth-alert auth-alert-success" style="margin-bottom: 24px;">
    <span><?php echo $msg; ?></span>
  </div>
<?php endif; ?>

<?php if (!empty($error)): ?>
  <div class="auth-alert auth-alert-danger" style="margin-bottom: 24px;">
    <span><?php echo htmlspecialchars($error); ?></span>
  </div>
<?php endif; ?>

<!-- Step Navigation Bar -->
<div style="display: flex; gap: 6px; border-bottom: 1px solid var(--border-subtle); margin-bottom: 24px; overflow-x: auto; padding-bottom: 8px;">
  <?php
    $stepsNav = [
        1 => '1. College Info ' . ($progress['championship']['is_complete'] ? '✓' : ''),
        2 => '2. Departments ' . ($progress['departments']['is_complete'] ? '✓' : ''),
        3 => '3. Sports ' . ($progress['sports']['is_complete'] ? '✓' : ''),
        4 => '4. Venues ' . ($progress['venues']['is_complete'] ? '✓' : ''),
        5 => '5. Officials ' . ($progress['officials']['is_complete'] ? '✓' : ''),
        6 => '6. Point Rules ' . ($progress['point_rules']['is_complete'] ? '✓' : ''),
        7 => '7. Review & Activate ' . ($progress['ready_to_start'] ? '🚀' : '')
    ];
    foreach ($stepsNav as $sNum => $sTitle):
      $isActive = ($step === $sNum);
      $btnStyle = $isActive ? 'background: var(--accent-green); color: #000; font-weight: 800;' : 'background: var(--bg-card); border: 1px solid var(--border-subtle);';
  ?>
    <a href="admin/setup.php?step=<?php echo $sNum; ?>" class="btn btn-secondary btn-sm" style="<?php echo $btnStyle; ?>">
      <?php echo $sTitle; ?>
    </a>
  <?php endforeach; ?>
</div>

<!-- STEP 1: College & Championship Info -->
<?php if ($step === 1): ?>
<div class="card" style="max-width: 680px;">
  <div class="section-header" style="margin-bottom: 16px;">
    <h2>Step 1: College & Championship Details</h2>
  </div>

  <form action="" method="POST">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
    <input type="hidden" name="action" value="save_step1">

    <div class="form-group">
      <label>College / Institute Name *</label>
      <input type="text" name="college_name" class="form-control" value="<?php echo htmlspecialchars($championship['college_name']); ?>" required>
    </div>

    <div class="form-group">
      <label>Championship Title *</label>
      <input type="text" name="championship_name" class="form-control" value="<?php echo htmlspecialchars($championship['name']); ?>" required>
    </div>

    <div class="form-group">
      <label>Academic Season / Year *</label>
      <input type="text" name="academic_year" class="form-control" value="<?php echo htmlspecialchars($championship['academic_year']); ?>" required>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;" class="form-group">
      <div>
        <label>Championship Start Date</label>
        <input type="date" name="start_date" class="form-control" value="<?php echo htmlspecialchars(getSetting('championship_start_date', date('Y-m-d'))); ?>">
      </div>
      <div>
        <label>Championship End Date</label>
        <input type="date" name="end_date" class="form-control" value="<?php echo htmlspecialchars(getSetting('championship_end_date', date('Y-m-d', strtotime('+30 days')))); ?>">
      </div>
    </div>

    <div style="display: flex; justify-content: flex-end; margin-top: 20px;">
      <button type="submit" class="btn btn-primary">Save & Continue to Step 2 →</button>
    </div>
  </form>
</div>
<?php endif; ?>

<!-- STEP 2: Departments -->
<?php if ($step === 2): ?>
<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px;">
  <div class="card">
    <div class="section-header" style="margin-bottom: 14px;">
      <h2>Step 2: Configured Departments (<?php echo count($departmentsList); ?>)</h2>
    </div>

    <div class="table-responsive">
      <table class="sports-table">
        <thead>
          <tr>
            <th>Department Name</th>
            <th>Short Code</th>
            <th>Code</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($departmentsList as $d): ?>
            <tr>
              <td><strong style="color: #fff;"><?php echo htmlspecialchars($d['name']); ?></strong></td>
              <td><span class="badge badge-generic"><?php echo htmlspecialchars($d['short_code']); ?></span></td>
              <td><code><?php echo htmlspecialchars($d['code']); ?></code></td>
              <td><span class="status-badge badge-completed">Active</span></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div style="display: flex; justify-content: space-between; margin-top: 20px;">
      <a href="admin/setup.php?step=1" class="btn btn-secondary">← Back to Step 1</a>
      <a href="admin/setup.php?step=3" class="btn btn-primary">Continue to Step 3 →</a>
    </div>
  </div>

  <div class="card">
    <div class="section-header" style="margin-bottom: 14px;">
      <h2>➕ Add New Department</h2>
    </div>
    <form action="" method="POST">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
      <input type="hidden" name="action" value="add_department">

      <div class="form-group">
        <label>Department Name *</label>
        <input type="text" name="dept_name" class="form-control" placeholder="e.g. Civil Engineering" required>
      </div>
      <div class="form-group">
        <label>Department Code *</label>
        <input type="text" name="dept_code" class="form-control" placeholder="e.g. CIVIL" required>
      </div>
      <div class="form-group">
        <label>Short Code</label>
        <input type="text" name="dept_short" class="form-control" placeholder="e.g. CE">
      </div>

      <button type="submit" class="btn btn-primary" style="width: 100%;">Add Department</button>
    </form>
  </div>
</div>
<?php endif; ?>

<!-- STEP 3: Sports -->
<?php if ($step === 3): ?>
<div class="card">
  <div class="section-header" style="margin-bottom: 14px; display: flex; justify-content: space-between; align-items: center;">
    <div>
      <h2>Step 3: Configured Sports Master List (<?php echo count($sportsList); ?> Sports)</h2>
      <p style="color: var(--text-muted); font-size: 0.88rem; margin-top: 4px;">Sports available for fixtures and live scoring engine.</p>
    </div>
    <a href="admin/sports.php" class="btn btn-secondary btn-sm">Manage Sports Master →</a>
  </div>

  <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 14px; margin-bottom: 24px;">
    <?php foreach ($sportsList as $s): ?>
      <div style="background: var(--bg-dark-surface); padding: 14px; border-radius: var(--radius-md); border: 1px solid var(--border-subtle); display: flex; justify-content: space-between; align-items: center;">
        <span style="font-weight: 700; color: #fff; font-size: 0.95rem;"><?php echo getSportBadge($s['name']); ?></span>
        <span class="status-badge badge-live" style="font-size: 0.7rem;">Active</span>
      </div>
    <?php endforeach; ?>
  </div>

  <div style="display: flex; justify-content: space-between;">
    <a href="admin/setup.php?step=2" class="btn btn-secondary">← Back to Step 2</a>
    <a href="admin/setup.php?step=4" class="btn btn-primary">Continue to Step 4 →</a>
  </div>
</div>
<?php endif; ?>

<!-- STEP 4: Venues -->
<?php if ($step === 4): ?>
<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px;">
  <div class="card">
    <div class="section-header" style="margin-bottom: 14px;">
      <h2>Step 4: Active Championship Venues (<?php echo count($venuesList); ?>)</h2>
    </div>

    <div class="table-responsive">
      <table class="sports-table">
        <thead>
          <tr>
            <th>Venue Name</th>
            <th>Location</th>
            <th>Capacity</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($venuesList as $v): ?>
            <tr>
              <td><strong style="color: #fff;"><?php echo htmlspecialchars($v['name']); ?></strong></td>
              <td style="color: var(--text-muted);"><?php echo htmlspecialchars($v['location'] ?? 'Campus'); ?></td>
              <td><?php echo number_format($v['capacity'] ?? 0); ?> seats</td>
              <td><span class="status-badge badge-completed">Active</span></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div style="display: flex; justify-content: space-between; margin-top: 20px;">
      <a href="admin/setup.php?step=3" class="btn btn-secondary">← Back to Step 3</a>
      <a href="admin/setup.php?step=5" class="btn btn-primary">Continue to Step 5 →</a>
    </div>
  </div>

  <div class="card">
    <div class="section-header" style="margin-bottom: 14px;">
      <h2>➕ Add New Venue</h2>
    </div>
    <form action="" method="POST">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
      <input type="hidden" name="action" value="add_venue">

      <div class="form-group">
        <label>Venue Name *</label>
        <input type="text" name="venue_name" class="form-control" placeholder="e.g. Main Ground" required>
      </div>
      <div class="form-group">
        <label>Location</label>
        <input type="text" name="venue_location" class="form-control" placeholder="e.g. Outdoor Sports Complex">
      </div>
      <div class="form-group">
        <label>Spectator Capacity</label>
        <input type="number" name="venue_capacity" class="form-control" value="500">
      </div>

      <button type="submit" class="btn btn-primary" style="width: 100%;">Add Venue</button>
    </form>
  </div>
</div>
<?php endif; ?>

<!-- STEP 5: Officials -->
<?php if ($step === 5): ?>
<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px;">
  <div class="card">
    <div class="section-header" style="margin-bottom: 14px;">
      <h2>Step 5: Registered Match Officials (<?php echo count($officialsList); ?>)</h2>
    </div>

    <div class="table-responsive">
      <table class="sports-table">
        <thead>
          <tr>
            <th>Official Name</th>
            <th>Role</th>
            <th>Sport</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($officialsList as $o): ?>
            <tr>
              <td><strong style="color: #fff;"><?php echo htmlspecialchars($o['name']); ?></strong></td>
              <td><span class="badge badge-generic" style="text-transform: capitalize;"><?php echo htmlspecialchars($o['role']); ?></span></td>
              <td><?php echo htmlspecialchars($o['sport_name'] ?? 'General'); ?></td>
              <td><span class="status-badge badge-completed">Active</span></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div style="display: flex; justify-content: space-between; margin-top: 20px;">
      <a href="admin/setup.php?step=4" class="btn btn-secondary">← Back to Step 4</a>
      <a href="admin/setup.php?step=6" class="btn btn-primary">Continue to Step 6 →</a>
    </div>
  </div>

  <div class="card">
    <div class="section-header" style="margin-bottom: 14px;">
      <h2>➕ Add Official</h2>
    </div>
    <form action="" method="POST">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
      <input type="hidden" name="action" value="add_official">

      <div class="form-group">
        <label>Official Name *</label>
        <input type="text" name="official_name" class="form-control" placeholder="e.g. KUMAR DHARMSENA" required>
      </div>
      <div class="form-group">
        <label>Role</label>
        <select name="official_role" class="form-control">
          <option value="referee">Referee</option>
          <option value="umpire">Umpire</option>
          <option value="scorer">Scorer</option>
          <option value="timekeeper">Timekeeper</option>
        </select>
      </div>
      <div class="form-group">
        <label>Assigned Sport</label>
        <select name="sport_id" class="form-control">
          <?php foreach ($sportsList as $sp): ?>
            <option value="<?php echo $sp['id']; ?>"><?php echo htmlspecialchars($sp['name']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <button type="submit" class="btn btn-primary" style="width: 100%;">Add Official</button>
    </form>
  </div>
</div>
<?php endif; ?>

<!-- STEP 6: Point Rules -->
<?php if ($step === 6): ?>
<div class="card" style="max-width: 680px;">
  <div class="section-header" style="margin-bottom: 16px;">
    <h2>Step 6: Championship Point & Trophy Rules</h2>
  </div>

  <form action="" method="POST">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
    <input type="hidden" name="action" value="save_point_rules">

    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px;" class="form-group">
      <div>
        <label>Match Win Points</label>
        <input type="number" name="points_win" class="form-control" value="<?php echo htmlspecialchars(getSetting('points_win', '2')); ?>" required>
      </div>
      <div>
        <label>Match Draw Points</label>
        <input type="number" name="points_draw" class="form-control" value="<?php echo htmlspecialchars(getSetting('points_draw', '1')); ?>" required>
      </div>
      <div>
        <label>Match Loss Points</label>
        <input type="number" name="points_loss" class="form-control" value="<?php echo htmlspecialchars(getSetting('points_loss', '0')); ?>" required>
      </div>
    </div>

    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px;" class="form-group">
      <div>
        <label>🥇 Gold Medal Points</label>
        <input type="number" name="points_gold" class="form-control" value="<?php echo htmlspecialchars(getSetting('points_gold', '10')); ?>" required>
      </div>
      <div>
        <label>🥈 Silver Medal Points</label>
        <input type="number" name="points_silver" class="form-control" value="<?php echo htmlspecialchars(getSetting('points_silver', '5')); ?>" required>
      </div>
      <div>
        <label>🥉 Bronze Medal Points</label>
        <input type="number" name="points_bronze" class="form-control" value="<?php echo htmlspecialchars(getSetting('points_bronze', '2')); ?>" required>
      </div>
    </div>

    <div style="display: flex; justify-content: space-between; margin-top: 24px;">
      <a href="admin/setup.php?step=5" class="btn btn-secondary">← Back to Step 5</a>
      <button type="submit" class="btn btn-primary">Save Rules & Go to Review →</button>
    </div>
  </form>
</div>
<?php endif; ?>

<!-- STEP 7: Review & Activate -->
<?php if ($step === 7): ?>
<div class="card" style="max-width: 760px;">
  <div class="section-header" style="margin-bottom: 20px;">
    <h2>Step 7: Final Championship Review & Activation Checklist</h2>
  </div>

  <div style="display: flex; flex-direction: column; gap: 14px; margin-bottom: 24px;">
    <?php
      $items = [
          'College & Championship Details' => $progress['championship']['is_complete'],
          'Active Departments (' . $progress['departments']['count'] . ' configured)' => $progress['departments']['is_complete'],
          'Sports Master List (' . $progress['sports']['count'] . ' active)' => $progress['sports']['is_complete'],
          'Championship Venues (' . $progress['venues']['count'] . ' active)' => $progress['venues']['is_complete'],
          'Match Officials (' . $progress['officials']['count'] . ' active)' => $progress['officials']['is_complete'],
          'Scoring & Point Rules' => $progress['point_rules']['is_complete'],
      ];
      foreach ($items as $itemTitle => $isOk):
        $bg = $isOk ? 'background: rgba(0, 230, 118, 0.08); border-left: 4px solid var(--accent-green);' : 'background: rgba(239, 68, 68, 0.08); border-left: 4px solid var(--accent-red);';
    ?>
      <div style="padding: 14px 18px; border-radius: var(--radius-md); border: 1px solid var(--border-subtle); <?php echo $bg; ?> display: flex; justify-content: space-between; align-items: center;">
        <span style="font-weight: 700; color: #fff; font-size: 0.95rem;"><?php echo htmlspecialchars($itemTitle); ?></span>
        <span class="status-badge <?php echo $isOk ? 'badge-completed' : 'badge-danger'; ?>" style="font-weight: 800;">
          <?php echo $isOk ? '✓ READY' : '❌ INCOMPLETE'; ?>
        </span>
      </div>
    <?php endforeach; ?>
  </div>

  <div style="background: var(--bg-dark-surface); padding: 20px; border-radius: var(--radius-md); border: 1px solid var(--border-subtle); margin-bottom: 24px;">
    <h4 style="margin: 0 0 10px 0; color: #fff; font-weight: 800;">Championship Status Transition</h4>
    <div style="font-size: 0.88rem; color: var(--text-muted); margin-bottom: 16px;">
      Current Status: <strong style="color: var(--accent-amber);"><?php echo strtoupper(htmlspecialchars($progress['status'])); ?></strong>
    </div>

    <form action="" method="POST" style="display: flex; gap: 12px; flex-wrap: wrap;">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
      <input type="hidden" name="action" value="activate_championship">

      <button type="submit" name="target_status" value="DRAFT" class="btn btn-secondary btn-sm">Set DRAFT</button>
      <button type="submit" name="target_status" value="READY" class="btn btn-secondary btn-sm">Set READY</button>
      <button type="submit" name="target_status" value="ACTIVE" class="btn btn-primary" style="background: linear-gradient(135deg, var(--accent-green), #059669); color: #000; font-weight: 800;" <?php echo !$progress['ready_to_start'] ? 'disabled' : ''; ?>>
        🚀 ACTIVATE CHAMPIONSHIP NOW
      </button>
      <button type="submit" name="target_status" value="COMPLETED" class="btn btn-secondary btn-sm" style="color: var(--accent-gold);">🏆 Complete</button>
    </form>
  </div>
</div>
<?php endif; ?>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
