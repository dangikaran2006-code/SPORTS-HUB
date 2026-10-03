<?php
/**
 * SportsHub - Team Registration Form & Upload Handler
 */
$currentPage = 'teams';
$pageTitle = 'Register Team';

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/team-player-functions.php';
require_once __DIR__ . '/../includes/tournament-functions.php';

// Server-side RBAC Enforcement
requireRole(['admin', 'organizer']);

$error = '';
$csrfToken = generateCsrfToken();

$sportsList   = getSportsList();
$managersList = getTeamManagersList();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token     = $_POST['csrf_token'] ?? '';
    $name      = trim($_POST['name'] ?? '');
    $shortName = strtoupper(trim($_POST['short_name'] ?? ''));
    $sportId   = intval($_POST['sport_id'] ?? 0);
    $managerId = intval($_POST['manager_id'] ?? 0);
    $status    = $_POST['status'] ?? 1;
    $desc      = trim($_POST['description'] ?? '');
    $logoFile  = $_FILES['logo'] ?? null;

    if (!validateCsrfToken($token)) {
        $error = 'Invalid security token. Please try again.';
    } elseif (empty($name)) {
        $error = 'Team Name is required.';
    } elseif (empty($shortName)) {
        $error = 'Short Name code is required.';
    } elseif ($sportId <= 0) {
        $error = 'Please select a valid sport.';
    } else {
        $result = createTeamService([
            'name'        => $name,
            'short_name'  => $shortName,
            'sport_id'    => $sportId,
            'manager_id'  => $managerId,
            'status'      => $status,
            'description' => $desc
        ], $logoFile);

        if ($result['success']) {
            header('Location: ' . BASE_URL . '/organizer/teams.php');
            exit;
        } else {
            $error = $result['error'] ?? 'Failed to register team.';
        }
    }
}

include_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-header">
  <div class="dashboard-title-group">
    <h1>Register New Team Franchise</h1>
    <p>Configure team details, logo, manager assignment, and sport classification.</p>
  </div>
  <div class="quick-actions-bar">
    <a href="<?php echo BASE_URL; ?>/organizer/teams.php" class="btn btn-secondary">
      Cancel & Back
    </a>
  </div>
</div>

<?php if (!empty($error)): ?>
  <div class="auth-alert auth-alert-danger" style="margin-bottom:20px;">
    <span><?php echo htmlspecialchars($error); ?></span>
  </div>
<?php endif; ?>

<div class="card" style="max-width: 720px; margin: 0 auto;">
  <form action="" method="POST" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">

    <div class="form-group">
      <label>Team Name *</label>
      <input type="text" name="name" class="form-control" placeholder="e.g. Royal Strikers" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label>Short Code (3-4 Letters) *</label>
        <input type="text" name="short_name" class="form-control" placeholder="e.g. RST" value="<?php echo htmlspecialchars($_POST['short_name'] ?? ''); ?>" required maxlength="5">
      </div>

      <div class="form-group">
        <label>Sport *</label>
        <select name="sport_id" class="form-control" required>
          <option value="">-- Select Sport --</option>
          <?php foreach ($sportsList as $sp): ?>
            <option value="<?php echo $sp['id']; ?>" <?php echo (($_POST['sport_id'] ?? '') == $sp['id']) ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($sp['name']); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label>Assign Team Manager</label>
        <select name="manager_id" class="form-control">
          <option value="0">-- Unassigned --</option>
          <?php foreach ($managersList as $mgr): ?>
            <option value="<?php echo $mgr['id']; ?>" <?php echo (($_POST['manager_id'] ?? '') == $mgr['id']) ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($mgr['name']) . " (" . htmlspecialchars($mgr['email']) . ")"; ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label>Registration Status</label>
        <select name="status" class="form-control">
          <option value="1" selected>Active</option>
          <option value="0">Inactive / Pending</option>
        </select>
      </div>
    </div>

    <!-- Section 17 Image Upload with JS Preview -->
    <div class="form-group">
      <label>Team Logo Image (JPG, PNG, WEBP &bull; Max 2MB)</label>
      <input type="file" name="logo" class="form-control" accept="image/jpeg,image/png,image/webp" onchange="previewImage(this, 'logoPreview')">
      <div style="margin-top:10px;">
        <img id="logoPreview" src="#" alt="Logo Preview" style="max-width:100px; max-height:100px; display:none; border-radius:var(--radius-md); border:1px solid var(--border-subtle);">
      </div>
    </div>

    <div class="form-group">
      <label>Description / Home City Details</label>
      <textarea name="description" class="form-control" rows="3" placeholder="Enter home city, team background..."><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
    </div>

    <div style="display:flex; justify-content:flex-end; gap:12px; margin-top:24px;">
      <a href="<?php echo BASE_URL; ?>/organizer/teams.php" class="btn btn-secondary">Cancel</a>
      <button type="submit" class="btn btn-primary">Save Team</button>
    </div>
  </form>
</div>

<script>
function previewImage(input, previewId) {
  const preview = document.getElementById(previewId);
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = function(e) {
      preview.src = e.target.result;
      preview.style.display = 'block';
    }
    reader.readAsDataURL(input.files[0]);
  }
}
</script>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
