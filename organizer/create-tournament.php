<?php
/**
 * SportsHub - Tournament Creation Form Page
 */
$currentPage = 'tournaments';
$pageTitle = 'Create Tournament';

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/tournament-functions.php';

// Server-side RBAC Enforcement
requireRole(['admin', 'organizer']);

$error = '';
$csrfToken = generateCsrfToken();

$sportsList = getSportsList();
$venuesList = getVenuesList();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token           = $_POST['csrf_token'] ?? '';
    $name            = trim($_POST['name'] ?? '');
    $sportId         = intval($_POST['sport_id'] ?? 0);
    $startDate       = $_POST['start_date'] ?? '';
    $endDate         = $_POST['end_date'] ?? '';
    $venueId         = intval($_POST['venue_id'] ?? 0);
    $format          = trim($_POST['format'] ?? '');
    $maxTeams        = intval($_POST['max_teams'] ?? 16);
    $regDeadline     = !empty($_POST['registration_deadline']) ? $_POST['registration_deadline'] : null;
    $status          = trim($_POST['status'] ?? 'upcoming');
    $description     = trim($_POST['description'] ?? '');

    // Form Validation Rules
    if (!validateCsrfToken($token)) {
        $error = 'Invalid security token. Please try again.';
    } elseif (empty($name)) {
        $error = 'Tournament name is required.';
    } elseif ($sportId <= 0) {
        $error = 'Please select a valid sport.';
    } elseif (empty($startDate)) {
        $error = 'Start date is required.';
    } elseif (empty($endDate)) {
        $error = 'End date is required.';
    } elseif (strtotime($endDate) < strtotime($startDate)) {
        $error = 'End date cannot be earlier than start date.';
    } elseif ($venueId <= 0) {
        $error = 'Please select a valid venue.';
    } elseif (empty($format)) {
        $error = 'Tournament format is required.';
    } else {
        $createdId = createTournamentService([
            'name'                  => $name,
            'sport_id'              => $sportId,
            'start_date'            => $startDate,
            'end_date'              => $endDate,
            'venue_id'              => $venueId,
            'format'                => $format,
            'max_teams'             => $maxTeams,
            'registration_deadline' => $regDeadline,
            'status'                => $status,
            'description'           => $description
        ]);

        if ($createdId) {
            header('Location: ' . BASE_URL . '/organizer/tournaments.php');
            exit;
        } else {
            $error = 'Failed to create tournament. Please try again.';
        }
    }
}

include_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-header">
  <div class="dashboard-title-group">
    <h1>Create New Tournament</h1>
    <p>Configure multi-sport competition details, format, dates, venue, and team limits.</p>
  </div>
  <div class="quick-actions-bar">
    <a href="<?php echo BASE_URL; ?>/organizer/tournaments.php" class="btn btn-secondary">
      Cancel & Back
    </a>
  </div>
</div>

<?php if (!empty($error)): ?>
  <div class="auth-alert auth-alert-danger" style="margin-bottom:20px;">
    <span><?php echo htmlspecialchars($error); ?></span>
  </div>
<?php endif; ?>

<div class="card" style="max-width: 760px; margin: 0 auto;">
  <form action="" method="POST">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">

    <div class="form-group">
      <label>Tournament Name *</label>
      <input type="text" name="name" class="form-control" placeholder="e.g. SSIT Sports Fest 2026" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label>Select Sport *</label>
        <select name="sport_id" class="form-control" required>
          <option value="">-- Choose Sport --</option>
          <?php foreach ($sportsList as $sp): ?>
            <option value="<?php echo $sp['id']; ?>" <?php echo (($_POST['sport_id'] ?? '') == $sp['id']) ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($sp['name']); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label>Tournament Format *</label>
        <select name="format" class="form-control" required>
          <option value="league">League</option>
          <option value="knockout">Knockout</option>
          <option value="round_robin">Round Robin</option>
          <option value="group_knockout" selected>Group Stage + Knockout</option>
        </select>
      </div>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label>Start Date *</label>
        <input type="date" name="start_date" class="form-control" required value="<?php echo htmlspecialchars($_POST['start_date'] ?? date('Y-m-d')); ?>">
      </div>

      <div class="form-group">
        <label>End Date *</label>
        <input type="date" name="end_date" class="form-control" required value="<?php echo htmlspecialchars($_POST['end_date'] ?? date('Y-m-d', strtotime('+15 days'))); ?>">
      </div>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label>Venue *</label>
        <select name="venue_id" class="form-control" required>
          <option value="">-- Choose Venue --</option>
          <?php foreach ($venuesList as $v): ?>
            <option value="<?php echo $v['id']; ?>" <?php echo (($_POST['venue_id'] ?? '') == $v['id']) ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($v['name']) . " (" . htmlspecialchars($v['city']) . ")"; ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label>Maximum Teams Limit</label>
        <input type="number" name="max_teams" class="form-control" value="<?php echo htmlspecialchars($_POST['max_teams'] ?? '16'); ?>" min="2" max="64">
      </div>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label>Registration Deadline</label>
        <input type="date" name="registration_deadline" class="form-control" value="<?php echo htmlspecialchars($_POST['registration_deadline'] ?? date('Y-m-d', strtotime('+7 days'))); ?>">
      </div>

      <div class="form-group">
        <label>Initial Status</label>
        <select name="status" class="form-control">
          <option value="upcoming" selected>Upcoming</option>
          <option value="draft">Draft</option>
        </select>
      </div>
    </div>

    <div class="form-group">
      <label>Tournament Description</label>
      <textarea name="description" class="form-control" rows="4" placeholder="Enter tournament ground rules, eligibility, schedule details..."><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
    </div>

    <div style="display:flex; justify-content:flex-end; gap:12px; margin-top:24px;">
      <a href="<?php echo BASE_URL; ?>/organizer/tournaments.php" class="btn btn-secondary">Cancel</a>
      <button type="submit" class="btn btn-primary">Create Tournament</button>
    </div>
  </form>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
