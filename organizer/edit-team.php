<?php
/**
 * SportsHub - Edit Team Form & Upload Handler
 */
$currentPage = 'teams';
$pageTitle = 'Edit Team Details';

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/team-player-functions.php';
require_once __DIR__ . '/../includes/tournament-functions.php';

// Server-side RBAC Enforcement
requireRole(['admin', 'organizer', 'team_manager']);

$teamId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$team = getTeamById($teamId);

if (!$team) {
    header('Location: ' . BASE_URL . '/organizer/teams.php');
    exit;
}

$currentUser = getCurrentUser();

// If user is Team Manager, enforce permission scope (only own assigned team)
if ($currentUser['role'] === 'team_manager' && $team['manager_id'] != $currentUser['id']) {
    die("Access Denied: You can only edit your assigned team.");
}

$error = '';
$success = '';
$csrfToken = generateCsrfToken();

$sportsList   = getSportsList();
$managersList = getTeamManagersList();

// Check if team is registered in any tournaments or matches to protect sport changes
$db = getDB();
$isLockedSport = false;
if ($db->getConnection()) {
    $tournamentsCount = fetchOne("SELECT COUNT(*) as cnt FROM tournament_teams WHERE team_id = :id", [':id' => $teamId])['cnt'] ?? 0;
    $matchesCount     = fetchOne("SELECT COUNT(*) as cnt FROM matches WHERE team_a_id = :id OR team_b_id = :id", [':id' => $teamId])['cnt'] ?? 0;
    if ($tournamentsCount > 0 || $matchesCount > 0) {
        $isLockedSport = true;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token     = $_POST['csrf_token'] ?? '';
    $name      = trim($_POST['name'] ?? '');
    $shortName = strtoupper(trim($_POST['short_name'] ?? ''));
    $managerId = intval($_POST['manager_id'] ?? 0);
    $status    = $_POST['status'] ?? 1;
    $desc      = trim($_POST['description'] ?? '');
    $logoFile  = $_FILES['logo'] ?? null;

    // If sport is locked, retain current sport_id, otherwise accept input
    $sportId   = $isLockedSport ? $team['sport_id'] : intval($_POST['sport_id'] ?? $team['sport_id']);

    if (!validateCsrfToken($token)) {
        $error = 'Invalid security token. Please try again.';
    } elseif (empty($name)) {
        $error = 'Team Name is required.';
    } elseif (empty($shortName)) {
        $error = 'Short Name code is required.';
    } else {
        $result = updateTeamService($teamId, [
            'name'        => $name,
            'short_name'  => $shortName,
            'sport_id'    => $sportId,
            'manager_id'  => $managerId,
            'status'      => $status,
            'description' => $desc
        ], $logoFile);

        if ($result['success']) {
            $success = 'Team updated successfully.';
            $team = getTeamById($teamId); // Refresh team details
        } else {
            $error = $result['error'] ?? 'Failed to update team.';
        }
    }
}

include_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-header">
  <div class="dashboard-title-group">
    <h1>Edit Team: <?php echo htmlspecialchars($team['name']); ?></h1>
    <p>Update team information, manager assignment, logo, and active status.</p>
  </div>
  <div class="quick-actions-bar">
    <a href="<?php echo BASE_URL; ?>/organizer/team-details.php?id=<?php echo $team['id']; ?>" class="btn btn-secondary">
      View Profile
    </a>
    <a href="<?php echo BASE_URL; ?>/organizer/teams.php" class="btn btn-secondary">
      Back to Teams List
    </a>
  </div>
</div>

<?php if (!empty($error)): ?>
  <div class="auth-alert auth-alert-danger" style="margin-bottom:20px;">
    <span><?php echo htmlspecialchars($error); ?></span>
  </div>
<?php endif; ?>

<?php if (!empty($success)): ?>
  <div class="auth-alert auth-alert-success" style="margin-bottom:20px; background: rgba(34, 197, 94, 0.15); border: 1px solid var(--accent-emerald); color: var(--accent-emerald); padding: 12px 16px; border-radius: var(--radius-md);">
    <span><?php echo htmlspecialchars($success); ?></span>
  </div>
<?php endif; ?>

<div class="card" style="max-width: 720px; margin: 0 auto;">
  <form action="" method="POST" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">

    <div class="form-group">
      <label>Team Name *</label>
      <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($_POST['name'] ?? $team['name']); ?>" required>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label>Short Code (3-4 Letters) *</label>
        <input type="text" name="short_name" class="form-control" value="<?php echo htmlspecialchars($_POST['short_name'] ?? $team['short_name']); ?>" required maxlength="5">
      </div>

      <div class="form-group">
        <label>Sport *</label>
        <?php if ($isLockedSport): ?>
          <input type="text" class="form-control" value="<?php echo htmlspecialchars($team['sport_name']); ?>" readonly style="opacity:0.7; cursor:not-allowed;">
          <input type="hidden" name="sport_id" value="<?php echo $team['sport_id']; ?>">
          <small style="color:var(--text-muted); display:block; margin-top:4px;">Sport classification locked because team is registered in active tournaments or matches.</small>
        <?php else: ?>
          <select name="sport_id" class="form-control" required>
            <?php foreach ($sportsList as $sp): ?>
              <option value="<?php echo $sp['id']; ?>" <?php echo (($team['sport_id'] == $sp['id'])) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($sp['name']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        <?php endif; ?>
      </div>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label>Assign Team Manager</label>
        <?php if ($currentUser['role'] === 'team_manager'): ?>
          <input type="text" class="form-control" value="<?php echo htmlspecialchars($currentUser['name']); ?>" readonly style="opacity:0.7;">
          <input type="hidden" name="manager_id" value="<?php echo $team['manager_id']; ?>">
        <?php else: ?>
          <select name="manager_id" class="form-control">
            <option value="0">-- Unassigned --</option>
            <?php foreach ($managersList as $mgr): ?>
              <option value="<?php echo $mgr['id']; ?>" <?php echo (($team['manager_id'] == $mgr['id'])) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($mgr['name']) . " (" . htmlspecialchars($mgr['email']) . ")"; ?>
              </option>
            <?php endforeach; ?>
          </select>
        <?php endif; ?>
      </div>

      <div class="form-group">
        <label>Status</label>
        <select name="status" class="form-control">
          <option value="1" <?php echo ($team['status'] == 1) ? 'selected' : ''; ?>>Active</option>
          <option value="0" <?php echo ($team['status'] == 0) ? 'selected' : ''; ?>>Inactive / Pending</option>
        </select>
      </div>
    </div>

    <!-- Image Upload with JS Preview -->
    <div class="form-group">
      <label>Change Team Logo (JPG, PNG, WEBP &bull; Max 2MB)</label>
      <div style="display:flex; align-items:center; gap:16px; margin-bottom:10px;">
        <img id="logoPreview" src="<?php echo BASE_URL . '/' . htmlspecialchars($team['logo'] ?? 'assets/images/default-team.png'); ?>" alt="Current Logo" style="width:64px; height:64px; object-fit:cover; border-radius:var(--radius-md); border:1px solid var(--border-subtle);">
        <input type="file" name="logo" class="form-control" accept="image/jpeg,image/png,image/webp" onchange="previewImage(this, 'logoPreview')">
      </div>
    </div>

    <div class="form-group">
      <label>Description / Home City Details</label>
      <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($_POST['description'] ?? ($team['description'] ?? '')); ?></textarea>
    </div>

    <div style="display:flex; justify-content:flex-end; gap:12px; margin-top:24px;">
      <a href="<?php echo BASE_URL; ?>/organizer/teams.php" class="btn btn-secondary">Cancel</a>
      <button type="submit" class="btn btn-primary">Update Team</button>
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
