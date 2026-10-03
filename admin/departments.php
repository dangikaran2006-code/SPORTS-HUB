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

$db = getDB();
$message = '';
$error = '';
$csrfToken = generateCsrfToken();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    $action = $_POST['action'] ?? '';

    if (!validateCsrfToken($token)) {
        $error = 'Security validation failed (Invalid CSRF Token).';
    } else {
        if ($action === 'create_department') {
            $name = trim($_POST['name'] ?? '');
            $code = strtoupper(trim($_POST['short_code'] ?? ''));
            $color = trim($_POST['color_code'] ?? '#00e676');
            $contact = trim($_POST['contact_person'] ?? '');

            if (!empty($name) && !empty($code)) {
                $newId = insert('departments', [
                    'name' => $name,
                    'short_code' => $code,
                    'color_code' => $color,
                    'contact_person' => $contact,
                    'status' => 1
                ]);
                logAuditAction('Department Created', 'Department', $newId, "Created department '{$name}' ({$code})");
                $message = "Department <strong>" . htmlspecialchars($name) . "</strong> created successfully.";
            } else {
                $error = 'Please enter both department name and short code.';
            }
        } elseif ($action === 'edit_department') {
            $dId = intval($_POST['department_id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $code = strtoupper(trim($_POST['short_code'] ?? ''));
            $color = trim($_POST['color_code'] ?? '#00e676');
            $contact = trim($_POST['contact_person'] ?? '');

            if ($dId > 0 && !empty($name) && !empty($code)) {
                update('departments', [
                    'name' => $name,
                    'short_code' => $code,
                    'color_code' => $color,
                    'contact_person' => $contact
                ], 'id = :id', [':id' => $dId]);
                logAuditAction('Department Updated', 'Department', $dId, "Updated department '{$name}' ({$code})");
                $message = "Department <strong>" . htmlspecialchars($name) . "</strong> updated successfully.";
            } else {
                $error = 'Please provide valid department details.';
            }
        }
    }
}

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

<?php if (!empty($message)): ?>
  <div class="auth-alert auth-alert-success" style="margin-bottom: 20px;">
    <span><?php echo $message; ?></span>
  </div>
<?php endif; ?>

<?php if (!empty($error)): ?>
  <div class="auth-alert auth-alert-danger" style="margin-bottom: 20px;">
    <span><?php echo htmlspecialchars($error); ?></span>
  </div>
<?php endif; ?>

<?php if ($activeDept): ?>
  <!-- Department Detail View -->
  <?php $stInfo = $standingsMap[$activeDept['id']] ?? null; ?>
  <div style="margin-bottom: 20px; display:flex; justify-content:space-between; align-items:center;">
    <a href="<?php echo BASE_URL; ?>/admin/departments.php" class="btn btn-secondary btn-sm">← Back to All Departments</a>
    <button class="btn btn-primary btn-sm" onclick="openEditDeptModal(<?php echo htmlspecialchars(json_encode($activeDept)); ?>)">
      ✏️ Edit Department Details
    </button>
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
    <div class="quick-actions-bar">
      <button onclick="openModal('addDeptModal')" class="btn btn-primary">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        <span>+ Add Department</span>
      </button>
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
          <div style="display:flex; gap:6px;">
            <button class="btn btn-secondary btn-sm" onclick="openEditDeptModal(<?php echo htmlspecialchars(json_encode($d)); ?>)">
              Edit
            </button>
            <a href="<?php echo BASE_URL; ?>/admin/departments.php?id=<?php echo $d['id']; ?>" class="btn btn-primary btn-sm">
              View →
            </a>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

<?php endif; ?>

<!-- Modal: Add Department -->
<div class="modal-overlay" id="addDeptModal">
  <div class="modal-container">
    <div class="modal-header">
      <h2>Add New Department</h2>
      <button class="modal-close-btn" onclick="closeModal('addDeptModal')">&times;</button>
    </div>
    
    <form action="departments.php" method="POST">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
      <input type="hidden" name="action" value="create_department">

      <div class="form-group">
        <label>Department Name *</label>
        <input type="text" name="name" class="form-control" placeholder="e.g. Chemical Engineering" required>
      </div>

      <div class="form-group">
        <label>Short Code *</label>
        <input type="text" name="short_code" class="form-control" placeholder="e.g. CHE" required style="text-transform:uppercase;">
      </div>

      <div class="form-group">
        <label>Faculty In-charge / HOD</label>
        <input type="text" name="contact_person" class="form-control" placeholder="e.g. Dr. Marie Curie">
      </div>

      <div class="form-group">
        <label>Badge Color Code</label>
        <input type="color" name="color_code" class="form-control" value="#00e676" style="height:45px; padding:4px;">
      </div>

      <div style="display:flex; justify-content:flex-end; gap:12px; margin-top:20px;">
        <button type="button" class="btn btn-secondary" onclick="closeModal('addDeptModal')">Cancel</button>
        <button type="submit" class="btn btn-primary">Create Department</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal: Edit Department -->
<div class="modal-overlay" id="editDeptModal">
  <div class="modal-container">
    <div class="modal-header">
      <h2>Edit Department Details</h2>
      <button class="modal-close-btn" onclick="closeModal('editDeptModal')">&times;</button>
    </div>
    
    <form action="departments.php" method="POST">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
      <input type="hidden" name="action" value="edit_department">
      <input type="hidden" name="department_id" id="editDeptId">

      <div class="form-group">
        <label>Department Name *</label>
        <input type="text" name="name" id="editDeptName" class="form-control" required>
      </div>

      <div class="form-group">
        <label>Short Code *</label>
        <input type="text" name="short_code" id="editDeptCode" class="form-control" required style="text-transform:uppercase;">
      </div>

      <div class="form-group">
        <label>Faculty In-charge / HOD</label>
        <input type="text" name="contact_person" id="editDeptContact" class="form-control">
      </div>

      <div class="form-group">
        <label>Badge Color Code</label>
        <input type="color" name="color_code" id="editDeptColor" class="form-control" style="height:45px; padding:4px;">
      </div>

      <div style="display:flex; justify-content:flex-end; gap:12px; margin-top:20px;">
        <button type="button" class="btn btn-secondary" onclick="closeModal('editDeptModal')">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Changes</button>
      </div>
    </form>
  </div>
</div>

<script>
function openModal(id) { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }

function openEditDeptModal(d) {
  document.getElementById('editDeptId').value = d.id;
  document.getElementById('editDeptName').value = d.name;
  document.getElementById('editDeptCode').value = d.short_code;
  document.getElementById('editDeptContact').value = d.contact_person || '';
  document.getElementById('editDeptColor').value = d.color_code || '#00e676';
  openModal('editDeptModal');
}
</script>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
