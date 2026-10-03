<?php
/**
 * SportsHub - Edit Tournament Console
 */
$currentPage = 'tournaments';
$pageTitle = 'Edit Tournament';

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/tournament-functions.php';

// Server-side RBAC Enforcement
requireRole(['admin', 'organizer']);

$id = intval($_GET['id'] ?? 0);
$tournament = getTournamentById($id);

if (!$tournament) {
    header('Location: ' . BASE_URL . '/organizer/tournaments.php');
    exit;
}

$error = '';
$success = '';
$csrfToken = generateCsrfToken();

$sportsList = getSportsList();
$venuesList = getVenuesList();

// Check if teams or matches exist to prevent orphaned sport changes
$registeredTeams = getTournamentTeamsService($tournament['id']);
$hasTeamsOrMatches = count($registeredTeams) > 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token           = $_POST['csrf_token'] ?? '';
    $name            = trim($_POST['name'] ?? '');
    $startDate       = $_POST['start_date'] ?? '';
    $endDate         = $_POST['end_date'] ?? '';
    $venueId         = intval($_POST['venue_id'] ?? 0);
    $format          = trim($_POST['format'] ?? '');
    $maxTeams        = intval($_POST['max_teams'] ?? 16);
    $regDeadline     = !empty($_POST['registration_deadline']) ? $_POST['registration_deadline'] : null;
    $status          = trim($_POST['status'] ?? 'upcoming');
    $description     = trim($_POST['description'] ?? '');

    if (!validateCsrfToken($token)) {
        $error = 'Invalid security token. Please try again.';
    } elseif (empty($name)) {
        $error = 'Tournament name is required.';
    } elseif (empty($startDate) || empty($endDate)) {
        $error = 'Start and end dates are required.';
    } elseif (strtotime($endDate) < strtotime($startDate)) {
        $error = 'End date cannot be earlier than start date.';
    } elseif ($venueId <= 0) {
        $error = 'Please select a valid venue.';
    } else {
        $updated = updateTournamentService($tournament['id'], [
            'name'                  => $name,
            'start_date'            => $startDate,
            'end_date'              => $endDate,
            'venue_id'              => $venueId,
            'format'                => $format,
            'max_teams'             => $maxTeams,
            'registration_deadline' => $regDeadline,
            'status'                => $status,
            'description'           => $description
        ]);

        if ($updated) {
            $success = 'Tournament details updated successfully!';
            $tournament = getTournamentById($tournament['id']);
        } else {
            $error = 'Failed to update tournament.';
        }
    }
}

include_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-header">
  <div class="dashboard-title-group">
    <h1>Edit Tournament: <?php echo htmlspecialchars($tournament['name']); ?></h1>
    <p>Modify dates, status, venue, format settings, and team limits.</p>
  </div>
  <div class="quick-actions-bar">
    <a href="<?php echo BASE_URL; ?>/organizer/tournament-details.php?id=<?php echo $tournament['id']; ?>" class="btn btn-secondary">
      Back to Details
    </a>
  </div>
</div>

<?php if (!empty($error)): ?>
  <div class="auth-alert auth-alert-danger" style="margin-bottom:20px;">
    <span><?php echo htmlspecialchars($error); ?></span>
  </div>
<?php endif; ?>

<?php if (!empty($success)): ?>
  <div class="auth-alert auth-alert-success" style="margin-bottom:20px;">
    <span><?php echo htmlspecialchars($success); ?></span>
  </div>
<?php endif; ?>

<div class="card" style="max-width: 760px; margin: 0 auto;">
  <form action="" method="POST">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">

    <div class="form-group">
      <label>Tournament Name *</label>
      <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($tournament['name']); ?>" required>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label>Sport (Sport is Locked when teams exist)</label>
        <input type="text" class="form-control" value="<?php echo htmlspecialchars($tournament['sport_name']); ?>" disabled style="opacity:0.7;">
      </div>

      <div class="form-group">
        <label>Tournament Format *</label>
        <select name="format" class="form-control" required>
          <option value="league" <?php echo ($tournament['format']==='league')?'selected':''; ?>>League</option>
          <option value="knockout" <?php echo ($tournament['format']==='knockout')?'selected':''; ?>>Knockout</option>
          <option value="round_robin" <?php echo ($tournament['format']==='round_robin')?'selected':''; ?>>Round Robin</option>
          <option value="group_knockout" <?php echo ($tournament['format']==='group_knockout')?'selected':''; ?>>Group Stage + Knockout</option>
        </select>
      </div>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label>Start Date *</label>
        <input type="date" name="start_date" class="form-control" required value="<?php echo htmlspecialchars($tournament['start_date']); ?>">
      </div>

      <div class="form-group">
        <label>End Date *</label>
        <input type="date" name="end_date" class="form-control" required value="<?php echo htmlspecialchars($tournament['end_date']); ?>">
      </div>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label>Venue *</label>
        <select name="venue_id" class="form-control" required>
          <?php foreach ($venuesList as $v): ?>
            <option value="<?php echo $v['id']; ?>" <?php echo (($tournament['venue_id'] ?? 1) == $v['id']) ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($v['name']) . " (" . htmlspecialchars($v['city']) . ")"; ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label>Maximum Teams Limit</label>
        <input type="number" name="max_teams" class="form-control" value="<?php echo htmlspecialchars($tournament['max_teams'] ?? 16); ?>" min="2" max="64">
      </div>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label>Registration Deadline</label>
        <input type="date" name="registration_deadline" class="form-control" value="<?php echo htmlspecialchars($tournament['registration_deadline'] ?? ''); ?>">
      </div>

      <div class="form-group">
        <label>Status</label>
        <select name="status" class="form-control">
          <option value="draft" <?php echo (strtolower($tournament['status'])==='draft')?'selected':''; ?>>Draft</option>
          <option value="upcoming" <?php echo (strtolower($tournament['status'])==='upcoming')?'selected':''; ?>>Upcoming</option>
          <option value="active" <?php echo (strtolower($tournament['status'])==='active')?'selected':''; ?>>Active</option>
          <option value="completed" <?php echo (strtolower($tournament['status'])==='completed')?'selected':''; ?>>Completed</option>
          <option value="cancelled" <?php echo (strtolower($tournament['status'])==='cancelled')?'selected':''; ?>>Cancelled</option>
        </select>
      </div>
    </div>

    <div class="form-group">
      <label>Description</label>
      <textarea name="description" class="form-control" rows="4"><?php echo htmlspecialchars($tournament['description'] ?? ''); ?></textarea>
    </div>

    <div style="display:flex; justify-content:flex-end; gap:12px; margin-top:24px;">
      <a href="<?php echo BASE_URL; ?>/organizer/tournament-details.php?id=<?php echo $tournament['id']; ?>" class="btn btn-secondary">Cancel</a>
      <button type="submit" class="btn btn-primary">Save Changes</button>
    </div>
  </form>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
