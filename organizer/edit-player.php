<?php
/**
 * SportsHub - Edit Player Profile Form & Upload Handler
 */
$currentPage = 'players';
$pageTitle = 'Edit Player Profile';

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/team-player-functions.php';
require_once __DIR__ . '/../includes/tournament-functions.php';

// Server-side RBAC Enforcement
requireRole(['admin', 'organizer', 'team_manager']);

$playerId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$player = getPlayerById($playerId);

if (!$player) {
    header('Location: ' . BASE_URL . '/organizer/players.php');
    exit;
}

$currentUser = getCurrentUser();

// Scope enforcement: if Team Manager, verify player belongs to their assigned team
if ($currentUser['role'] === 'team_manager') {
    $db = getDB();
    if ($db->getConnection()) {
        $team = fetchOne("SELECT manager_id FROM teams WHERE id = :tid", [':tid' => (int)($player['team_id'] ?? 0)]);
        if ($team && $team['manager_id'] != $currentUser['id']) {
            die("Access Denied: You can only edit players belonging to your assigned team.");
        }
    }
}

$error = '';
$success = '';
$csrfToken = generateCsrfToken();

$sportsList = getSportsList();
$managerId  = ($currentUser['role'] === 'team_manager') ? $currentUser['id'] : 0;
$allTeams   = getTeamsFiltered('', 0, 'all', $managerId);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token    = $_POST['csrf_token'] ?? '';
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $dob      = $_POST['date_of_birth'] ?? null;
    $jersey   = !empty($_POST['jersey_number']) ? intval($_POST['jersey_number']) : null;
    $sportId  = intval($_POST['sport_id'] ?? 0);
    $teamId   = !empty($_POST['team_id']) ? intval($_POST['team_id']) : null;
    $position = trim($_POST['position'] ?? 'Player');
    $status   = $_POST['status'] ?? 'active';
    $photoFile= $_FILES['profile_image'] ?? null;

    if (!validateCsrfToken($token)) {
        $error = 'Invalid security token. Please try again.';
    } elseif (empty($name)) {
        $error = 'Full Name is required.';
    } elseif ($sportId <= 0) {
        $error = 'Please select a valid sport.';
    } else {
        $result = updatePlayerService($playerId, [
            'name'          => $name,
            'email'         => $email,
            'phone'         => $phone,
            'date_of_birth' => $dob,
            'jersey_number' => $jersey,
            'sport_id'      => $sportId,
            'team_id'       => $teamId,
            'position'      => $position,
            'status'        => $status
        ], $photoFile);

        if ($result['success']) {
            $success = 'Player profile updated successfully.';
            $player = getPlayerById($playerId); // Refresh details
        } else {
            $error = $result['error'] ?? 'Failed to update player.';
        }
    }
}

$photoUrl = !empty($player['profile_image']) ? BASE_URL . '/' . htmlspecialchars($player['profile_image']) : BASE_URL . '/assets/images/default-player.png';

include_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-header">
  <div class="dashboard-title-group">
    <h1>Edit Player: <?php echo htmlspecialchars($player['name']); ?></h1>
    <p>Update athlete parameters, jersey number, sport, team affiliation, and photo.</p>
  </div>
  <div class="quick-actions-bar">
    <a href="<?php echo BASE_URL; ?>/organizer/player-details.php?id=<?php echo $player['id']; ?>" class="btn btn-secondary">
      View Profile
    </a>
    <a href="<?php echo BASE_URL; ?>/organizer/players.php" class="btn btn-secondary">
      Back to Players
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

    <div class="form-row">
      <div class="form-group">
        <label>Full Name *</label>
        <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($_POST['name'] ?? $player['name']); ?>" required>
      </div>

      <div class="form-group">
        <label>Jersey Number</label>
        <input type="number" name="jersey_number" class="form-control" value="<?php echo htmlspecialchars($_POST['jersey_number'] ?? $player['jersey_number']); ?>" min="1" max="999">
      </div>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label>Email Address</label>
        <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($_POST['email'] ?? $player['email']); ?>">
      </div>

      <div class="form-group">
        <label>Phone Number</label>
        <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($_POST['phone'] ?? $player['phone']); ?>">
      </div>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label>Sport *</label>
        <select name="sport_id" id="sportSelect" class="form-control" required onchange="filterTeamsBySport()">
          <?php foreach ($sportsList as $sp): ?>
            <option value="<?php echo $sp['id']; ?>" <?php echo (($player['sport_id'] == $sp['id'])) ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($sp['name']); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label>Assigned Team</label>
        <select name="team_id" id="teamSelect" class="form-control">
          <option value="">-- Free Agent (Unassigned) --</option>
          <?php foreach ($allTeams as $tm): ?>
            <option value="<?php echo $tm['id']; ?>" data-sport-id="<?php echo $tm['sport_id']; ?>" <?php echo (($player['team_id'] == $tm['id'])) ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($tm['name']) . " (" . htmlspecialchars($tm['sport_name'] ?? '') . ")"; ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label>Position / Playing Role</label>
        <input type="text" name="position" class="form-control" value="<?php echo htmlspecialchars($_POST['position'] ?? ($player['position'] ?? ($player['primary_role'] ?? 'Player'))); ?>">
      </div>

      <div class="form-group">
        <label>Date of Birth</label>
        <input type="date" name="date_of_birth" class="form-control" value="<?php echo htmlspecialchars($_POST['date_of_birth'] ?? $player['date_of_birth']); ?>">
      </div>
    </div>

    <div class="form-group">
      <label>Status</label>
      <select name="status" class="form-control">
        <?php $currStatus = strtolower($player['status'] ?? 'active'); ?>
        <option value="active" <?php echo ($currStatus === 'active') ? 'selected' : ''; ?>>Active</option>
        <option value="inactive" <?php echo ($currStatus === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
        <option value="injured" <?php echo ($currStatus === 'injured') ? 'selected' : ''; ?>>Injured</option>
        <option value="suspended" <?php echo ($currStatus === 'suspended') ? 'selected' : ''; ?>>Suspended</option>
      </select>
    </div>

    <!-- Image Upload with JS Preview -->
    <div class="form-group">
      <label>Change Profile Photo (JPG, PNG, WEBP &bull; Max 2MB)</label>
      <div style="display:flex; align-items:center; gap:16px; margin-bottom:10px;">
        <img id="photoPreview" src="<?php echo $photoUrl; ?>" alt="Current Photo" style="width:64px; height:64px; border-radius:50%; object-fit:cover; border:1px solid var(--border-subtle);" onerror="this.src='<?php echo BASE_URL; ?>/assets/images/default-player.png'">
        <input type="file" name="profile_image" class="form-control" accept="image/jpeg,image/png,image/webp" onchange="previewImage(this, 'photoPreview')">
      </div>
    </div>

    <div style="display:flex; justify-content:flex-end; gap:12px; margin-top:24px;">
      <a href="<?php echo BASE_URL; ?>/organizer/players.php" class="btn btn-secondary">Cancel</a>
      <button type="submit" class="btn btn-primary">Update Player Profile</button>
    </div>
  </form>
</div>

<script>
function filterTeamsBySport() {
  const sportId = document.getElementById('sportSelect').value;
  const teamSelect = document.getElementById('teamSelect');
  const options = teamSelect.querySelectorAll('option');

  options.forEach(opt => {
    if (opt.value === '') {
      opt.style.display = 'block';
    } else {
      const teamSportId = opt.getAttribute('data-sport-id');
      if (sportId === '' || teamSportId === sportId) {
        opt.style.display = 'block';
      } else {
        opt.style.display = 'none';
        if (opt.selected) {
          teamSelect.value = '';
        }
      }
    }
  });
}

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

// Run initial filter on load
document.addEventListener('DOMContentLoaded', filterTeamsBySport);
</script>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
