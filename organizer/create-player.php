<?php
/**
 * SportsHub - Register New Player & Dynamic Sport-Team Filter Form
 */
$currentPage = 'players';
$pageTitle = 'Register New Athlete';

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/team-player-functions.php';
require_once __DIR__ . '/../includes/tournament-functions.php';

// Server-side RBAC Enforcement
requireRole(['admin', 'organizer', 'team_manager']);

$currentUser = getCurrentUser();
$error = '';
$csrfToken = generateCsrfToken();

$sportsList = getSportsList();

// Scope restriction for Team Manager
$managerId = ($currentUser['role'] === 'team_manager') ? $currentUser['id'] : 0;
$allTeams  = getTeamsFiltered('', 0, 'all', $managerId);

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
        $result = createPlayerService([
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
            header('Location: ' . BASE_URL . '/organizer/players.php');
            exit;
        } else {
            $error = $result['error'] ?? 'Failed to register player.';
        }
    }
}

include_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-header">
  <div class="dashboard-title-group">
    <h1>Register New Athlete</h1>
    <p>Add a player profile, assign jersey number, sport specialization, and team roster.</p>
  </div>
  <div class="quick-actions-bar">
    <a href="<?php echo BASE_URL; ?>/organizer/players.php" class="btn btn-secondary">
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

    <div class="form-row">
      <div class="form-group">
        <label>Full Name *</label>
        <input type="text" name="name" class="form-control" placeholder="e.g. Virat Kohli" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
      </div>

      <div class="form-group">
        <label>Jersey Number</label>
        <input type="number" name="jersey_number" class="form-control" placeholder="e.g. 18" value="<?php echo htmlspecialchars($_POST['jersey_number'] ?? ''); ?>" min="1" max="999">
      </div>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label>Email Address</label>
        <input type="email" name="email" class="form-control" placeholder="player@sportshub.com" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
      </div>

      <div class="form-group">
        <label>Phone Number</label>
        <input type="text" name="phone" class="form-control" placeholder="+91 9876543210" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
      </div>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label>Sport *</label>
        <select name="sport_id" id="sportSelect" class="form-control" required onchange="filterTeamsBySport()">
          <option value="">-- Select Sport --</option>
          <?php foreach ($sportsList as $sp): ?>
            <option value="<?php echo $sp['id']; ?>" <?php echo (($_POST['sport_id'] ?? '') == $sp['id']) ? 'selected' : ''; ?>>
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
            <option value="<?php echo $tm['id']; ?>" data-sport-id="<?php echo $tm['sport_id']; ?>" <?php echo (($_POST['team_id'] ?? '') == $tm['id']) ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($tm['name']) . " (" . htmlspecialchars($tm['sport_name'] ?? '') . ")"; ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label>Position / Playing Role</label>
        <input type="text" name="position" class="form-control" placeholder="e.g. Batsman, Forward, Raider, Guard" value="<?php echo htmlspecialchars($_POST['position'] ?? ''); ?>">
      </div>

      <div class="form-group">
        <label>Date of Birth</label>
        <input type="date" name="date_of_birth" class="form-control" value="<?php echo htmlspecialchars($_POST['date_of_birth'] ?? ''); ?>">
      </div>
    </div>

    <div class="form-group">
      <label>Status</label>
      <select name="status" class="form-control">
        <option value="active" selected>Active</option>
        <option value="inactive">Inactive</option>
        <option value="injured">Injured</option>
        <option value="suspended">Suspended</option>
      </select>
    </div>

    <!-- Section 17 Image Upload with JS Preview -->
    <div class="form-group">
      <label>Profile Photo (JPG, PNG, WEBP &bull; Max 2MB)</label>
      <input type="file" name="profile_image" class="form-control" accept="image/jpeg,image/png,image/webp" onchange="previewImage(this, 'photoPreview')">
      <div style="margin-top:10px;">
        <img id="photoPreview" src="#" alt="Photo Preview" style="max-width:100px; max-height:100px; display:none; border-radius:50%; border:1px solid var(--border-subtle); object-fit:cover;">
      </div>
    </div>

    <div style="display:flex; justify-content:flex-end; gap:12px; margin-top:24px;">
      <a href="<?php echo BASE_URL; ?>/organizer/players.php" class="btn btn-secondary">Cancel</a>
      <button type="submit" class="btn btn-primary">Save Player Profile</button>
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

// Run initial filter on page load
document.addEventListener('DOMContentLoaded', filterTeamsBySport);
</script>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
