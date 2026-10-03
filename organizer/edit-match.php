<?php
/**
 * SportsHub - Edit & Reschedule Match Form with Conflict Checking
 */
$currentPage = 'matches';
$pageTitle = 'Edit / Reschedule Match';

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/match-functions.php';
require_once __DIR__ . '/../includes/tournament-functions.php';

// Server-side RBAC Enforcement
requireRole(['admin', 'organizer']);

$matchId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$match   = getMatchById($matchId);

if (!$match) {
    header('Location: ' . BASE_URL . '/organizer/matches.php');
    exit;
}

$error = '';
$success = '';
$csrfToken = generateCsrfToken();

$venuesList    = getVenuesList();
$officialsList = getOfficialsForSport($match['sport_id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token        = $_POST['csrf_token'] ?? '';
    $sDate        = $_POST['scheduled_date'] ?? '';
    $sTime        = $_POST['scheduled_time'] ?? '';
    $duration     = intval($_POST['duration_minutes'] ?? 90);
    $venueId      = intval($_POST['venue_id'] ?? 0);
    $officialId   = intval($_POST['official_id'] ?? 0);
    $status       = $_POST['status'] ?? 'scheduled';
    $postponeMsg  = trim($_POST['postponement_reason'] ?? '');
    $cancelMsg    = trim($_POST['cancellation_reason'] ?? '');

    if (!validateCsrfToken($token)) {
        $error = 'Invalid security token. Please try again.';
    } elseif (empty($sDate) || empty($sTime)) {
        $error = 'Scheduled Date and Time are required.';
    } elseif ($status === 'postponed' && empty($postponeMsg)) {
        $error = 'Please provide a reason for postponing the match.';
    } elseif ($status === 'cancelled' && empty($cancelMsg)) {
        $error = 'Please provide a reason for cancelling the match.';
    } else {
        $result = updateMatchService($matchId, [
            'scheduled_date'      => $sDate,
            'scheduled_time'      => $sTime,
            'duration_minutes'    => $duration,
            'venue_id'            => $venueId,
            'official_id'         => $officialId,
            'status'              => $status,
            'postponement_reason' => $postponeMsg,
            'cancellation_reason' => $cancelMsg
        ]);

        if ($result['success']) {
            $success = $result['message'];
            $match = getMatchById($matchId); // Refresh details
        } else {
            $error = $result['error'] ?? 'Failed to update match schedule.';
        }
    }
}

include_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-header">
  <div class="dashboard-title-group">
    <h1>Reschedule / Edit Match #<?php echo $match['id']; ?></h1>
    <p>Update match schedule, venue, referee, or set postponement/cancellation status with conflict protection.</p>
  </div>
  <div class="quick-actions-bar">
    <a href="<?php echo BASE_URL; ?>/organizer/match-details.php?id=<?php echo $match['id']; ?>" class="btn btn-secondary">
      View Match Profile
    </a>
    <a href="<?php echo BASE_URL; ?>/organizer/matches.php" class="btn btn-secondary">
      Back to Matches
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
  <div style="background:var(--bg-card-hover); padding:16px; border-radius:var(--radius-md); margin-bottom:20px; text-align:center;">
    <h3 style="font-size:1.1rem; color:var(--text-main); margin-bottom:4px;">
      <?php echo htmlspecialchars($match['team_a_name']); ?> vs <?php echo htmlspecialchars($match['team_b_name']); ?>
    </h3>
    <span style="font-size:0.85rem; color:var(--text-muted);">
      Tournament: <?php echo htmlspecialchars($match['tournament_name']); ?> &bull; Round: <?php echo htmlspecialchars($match['round_name'] ?? 'League'); ?>
    </span>
  </div>

  <form action="" method="POST">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">

    <div class="form-row">
      <div class="form-group">
        <label>Scheduled Date *</label>
        <input type="date" name="scheduled_date" class="form-control" value="<?php echo htmlspecialchars($_POST['scheduled_date'] ?? $match['scheduled_date']); ?>" required>
      </div>

      <div class="form-group">
        <label>Scheduled Start Time *</label>
        <input type="time" name="scheduled_time" class="form-control" value="<?php echo htmlspecialchars($_POST['scheduled_time'] ?? $match['scheduled_time']); ?>" required>
      </div>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label>Venue Ground *</label>
        <select name="venue_id" class="form-control" required>
          <option value="">-- Select Venue --</option>
          <?php foreach ($venuesList as $v): ?>
            <option value="<?php echo $v['id']; ?>" <?php echo (($match['venue_id'] == $v['id'])) ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($v['name']) . " (" . htmlspecialchars($v['location']) . ")"; ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label>Match Official / Referee</label>
        <select name="official_id" class="form-control">
          <option value="0">-- Unassigned --</option>
          <?php foreach ($officialsList as $off): ?>
            <option value="<?php echo $off['id']; ?>" <?php echo (($match['official_id'] == $off['id'])) ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($off['name']) . " (" . ucfirst($off['role']) . ")"; ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="form-group">
      <label>Match Status *</label>
      <select name="status" id="statusSelect" class="form-control" onchange="toggleReasonFields()">
        <?php $mStat = strtolower($match['status'] ?? 'scheduled'); ?>
        <option value="scheduled" <?php echo ($mStat === 'scheduled') ? 'selected' : ''; ?>>Scheduled</option>
        <option value="live" <?php echo ($mStat === 'live') ? 'selected' : ''; ?>>Live</option>
        <option value="completed" <?php echo ($mStat === 'completed') ? 'selected' : ''; ?>>Completed</option>
        <option value="postponed" <?php echo ($mStat === 'postponed') ? 'selected' : ''; ?>>Postponed</option>
        <option value="cancelled" <?php echo ($mStat === 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
      </select>
    </div>

    <div class="form-group" id="postponeReasonGroup" style="display:none;">
      <label>Reason for Postponement *</label>
      <textarea name="postponement_reason" class="form-control" rows="2" placeholder="e.g. Heavy rain, unplayable pitch conditions..."><?php echo htmlspecialchars($_POST['postponement_reason'] ?? ($match['postponement_reason'] ?? '')); ?></textarea>
    </div>

    <div class="form-group" id="cancelReasonGroup" style="display:none;">
      <label>Reason for Cancellation *</label>
      <textarea name="cancellation_reason" class="form-control" rows="2" placeholder="e.g. Team forfeiture, security advisory..."><?php echo htmlspecialchars($_POST['cancellation_reason'] ?? ($match['cancellation_reason'] ?? '')); ?></textarea>
    </div>

    <div style="display:flex; justify-content:flex-end; gap:12px; margin-top:24px;">
      <a href="<?php echo BASE_URL; ?>/organizer/matches.php" class="btn btn-secondary">Cancel</a>
      <button type="submit" class="btn btn-primary">Update Match Schedule</button>
    </div>
  </form>
</div>

<script>
function toggleReasonFields() {
  const status = document.getElementById('statusSelect').value;
  const pGroup = document.getElementById('postponeReasonGroup');
  const cGroup = document.getElementById('cancelReasonGroup');

  pGroup.style.display = (status === 'postponed') ? 'block' : 'none';
  cGroup.style.display = (status === 'cancelled') ? 'block' : 'none';
}

document.addEventListener('DOMContentLoaded', toggleReasonFields);
</script>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
