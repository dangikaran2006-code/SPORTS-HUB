<?php
/**
 * SportsHub - Admin Fixtures & Schedule Management Console
 */
$currentPage = 'matches';
$pageTitle = 'Fixtures & Schedule Management';

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/match-functions.php';

requireAdminAccess();

$search = trim($_GET['search'] ?? '');
$sportId = intval($_GET['sport_id'] ?? 0);
$venueId = intval($_GET['venue_id'] ?? 0);
$status  = trim($_GET['status'] ?? '');
$date    = trim($_GET['date'] ?? '');

$matchesList = getMatchesFiltered($search, 0, $sportId, $date, $venueId, $status);
$sportsList  = getSportsList();
$venuesList  = getVenuesList();

$msg = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $matchId = intval($_POST['match_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($action === 'reschedule') {
        $newDate = $_POST['scheduled_date'] ?? '';
        $newTime = $_POST['scheduled_time'] ?? '';
        $venue   = intval($_POST['venue_id'] ?? 0);
        $res = updateMatchService($matchId, [
            'scheduled_date' => $newDate,
            'scheduled_time' => $newTime,
            'venue_id' => $venue,
            'status' => 'scheduled'
        ]);
        if ($res['success']) {
            $msg = "Match #{$matchId} rescheduled to " . htmlspecialchars($newDate) . " at " . htmlspecialchars($newTime) . " successfully.";
        } else {
            $error = $res['error'] ?? 'Conflict detected during reschedule.';
        }
    } elseif ($action === 'postpone') {
        $reason = trim($_POST['reason'] ?? 'Weather / Ground Unfit');
        $res = updateMatchService($matchId, [
            'status' => 'postponed',
            'postponement_reason' => $reason
        ]);
        $msg = "Match #{$matchId} marked as POSTPONED (Reason: " . htmlspecialchars($reason) . ").";
    } elseif ($action === 'cancel') {
        $reason = trim($_POST['reason'] ?? 'Cancelled by Admin');
        $res = updateMatchService($matchId, [
            'status' => 'cancelled',
            'cancellation_reason' => $reason
        ]);
        $msg = "Match #{$matchId} marked as CANCELLED.";
    }
    $matchesList = getMatchesFiltered($search, 0, $sportId, $date, $venueId, $status);
}

include_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-header">
  <div class="dashboard-title-group">
    <h1>Fixtures & Match Schedule Control</h1>
    <p>Manage, reschedule, postpone, or cancel championship match fixtures with automatic conflict detection.</p>
  </div>
  <div style="display: flex; gap: 10px;">
    <a href="<?php echo BASE_URL; ?>/organizer/create-match.php" class="btn btn-primary">
      + Schedule Match
    </a>
    <a href="<?php echo BASE_URL; ?>/organizer/generate-fixtures.php" class="btn btn-secondary">
      ⚡ Auto-Generate Fixtures
    </a>
  </div>
</div>

<?php if (!empty($msg)): ?>
  <div class="auth-alert auth-alert-success" style="margin-bottom: 24px; background: rgba(0, 230, 118, 0.15); border: 1px solid rgba(0, 230, 118, 0.3); color: var(--accent-green); padding: 12px 16px; border-radius: 8px;">
    <span><?php echo $msg; ?></span>
  </div>
<?php endif; ?>

<?php if (!empty($error)): ?>
  <div class="auth-alert auth-alert-danger" style="margin-bottom: 24px; background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.3); color: var(--accent-red); padding: 12px 16px; border-radius: 8px;">
    <span>⚠️ <?php echo htmlspecialchars($error); ?></span>
  </div>
<?php endif; ?>

<!-- Filter & Search Controls -->
<div class="card" style="margin-bottom: 24px;">
  <form method="GET" action="" style="display: grid; grid-template-columns: 2fr 1fr 1fr 1fr 1fr auto; gap: 12px; align-items: end;">
    <div>
      <label style="font-size: 0.75rem; color: var(--text-muted); font-weight: 700;">SEARCH FIXTURES</label>
      <input type="text" name="search" class="form-control" placeholder="Search team, tournament..." value="<?php echo htmlspecialchars($search); ?>">
    </div>

    <div>
      <label style="font-size: 0.75rem; color: var(--text-muted); font-weight: 700;">SPORT</label>
      <select name="sport_id" class="form-control">
        <option value="0">All Sports</option>
        <?php foreach ($sportsList as $sp): ?>
          <option value="<?php echo $sp['id']; ?>" <?php echo ($sportId == $sp['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($sp['name']); ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div>
      <label style="font-size: 0.75rem; color: var(--text-muted); font-weight: 700;">VENUE</label>
      <select name="venue_id" class="form-control">
        <option value="0">All Venues</option>
        <?php foreach ($venuesList as $v): ?>
          <option value="<?php echo $v['id']; ?>" <?php echo ($venueId == $v['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($v['name']); ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div>
      <label style="font-size: 0.75rem; color: var(--text-muted); font-weight: 700;">STATUS</label>
      <select name="status" class="form-control">
        <option value="all">All Statuses</option>
        <option value="scheduled" <?php echo ($status === 'scheduled') ? 'selected' : ''; ?>>Scheduled</option>
        <option value="live" <?php echo ($status === 'live') ? 'selected' : ''; ?>>Live</option>
        <option value="completed" <?php echo ($status === 'completed') ? 'selected' : ''; ?>>Completed</option>
        <option value="postponed" <?php echo ($status === 'postponed') ? 'selected' : ''; ?>>Postponed</option>
        <option value="cancelled" <?php echo ($status === 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
      </select>
    </div>

    <div>
      <label style="font-size: 0.75rem; color: var(--text-muted); font-weight: 700;">DATE</label>
      <input type="date" name="date" class="form-control" value="<?php echo htmlspecialchars($date); ?>">
    </div>

    <div style="display: flex; gap: 6px;">
      <button type="submit" class="btn btn-primary" style="padding: 10px 16px;">Filter</button>
      <a href="<?php echo BASE_URL; ?>/admin/fixtures.php" class="btn btn-secondary" style="padding: 10px 14px;">Reset</a>
    </div>
  </form>
</div>

<!-- Fixtures Management Table -->
<div class="card">
  <div class="table-responsive">
    <table class="sports-table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Sport</th>
          <th>Event / Match</th>
          <th>Teams / Department</th>
          <th>Date & Time</th>
          <th>Venue & Official</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($matchesList)): ?>
          <tr>
            <td colspan="8" style="text-align: center; color: var(--text-muted); padding: 32px;">
              No match fixtures found. Click "+ Schedule Match" to add one.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($matchesList as $m): 
            $st = strtolower($m['status'] ?? 'scheduled');
          ?>
            <tr>
              <td><code>#<?php echo $m['id']; ?></code></td>
              <td>
                <span class="badge" style="background: rgba(0, 230, 118, 0.15); color: var(--accent-green); font-weight: 700;">
                  <?php echo htmlspecialchars($m['sport_name']); ?>
                </span>
              </td>
              <td>
                <strong style="color: #fff; display: block;"><?php echo htmlspecialchars($m['tournament_name']); ?></strong>
                <span style="font-size: 0.78rem; color: var(--accent-amber); font-weight: 600;"><?php echo htmlspecialchars($m['round_name'] ?? 'League'); ?></span>
              </td>
              <td>
                <strong style="color: #fff;"><?php echo htmlspecialchars($m['team_a_name']); ?></strong>
                <span style="color: var(--accent-green); font-weight: 800;"> vs </span>
                <strong style="color: #fff;"><?php echo htmlspecialchars($m['team_b_name']); ?></strong>
              </td>
              <td>
                <div style="font-weight: 600; color: #fff;"><?php echo !empty($m['scheduled_date']) ? date('M d, Y', strtotime($m['scheduled_date'])) : 'TBD'; ?></div>
                <div style="font-size: 0.78rem; color: var(--text-muted);"><?php echo !empty($m['scheduled_time']) ? date('h:i A', strtotime($m['scheduled_time'])) : 'TBD'; ?></div>
              </td>
              <td>
                <div style="font-weight: 600; color: var(--text-main);">📍 <?php echo htmlspecialchars($m['venue_name'] ?? 'TBD'); ?></div>
                <div style="font-size: 0.75rem; color: var(--text-muted);">👨‍⚖️ <?php echo htmlspecialchars($m['official_name'] ?? 'Unassigned'); ?></div>
              </td>
              <td><?php echo getStatusBadge($st); ?></td>
              <td>
                <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                  <a href="<?php echo BASE_URL; ?>/organizer/edit-match.php?id=<?php echo $m['id']; ?>" class="btn btn-secondary btn-sm">
                    Edit
                  </a>

                  <button class="btn btn-secondary btn-sm" style="color: var(--accent-amber);" onclick="openRescheduleModal(<?php echo $m['id']; ?>, '<?php echo $m['scheduled_date']; ?>', '<?php echo $m['scheduled_time']; ?>', <?php echo $m['venue_id'] ?? 0; ?>)">
                    Reschedule
                  </button>

                  <form action="" method="POST" style="display: inline;" onsubmit="return confirm('Mark match as POSTPONED?');">
                    <input type="hidden" name="match_id" value="<?php echo $m['id']; ?>">
                    <input type="hidden" name="action" value="postpone">
                    <input type="hidden" name="reason" value="Weather / Ground Conditions">
                    <button type="submit" class="btn btn-secondary btn-sm" style="color: var(--accent-amber);">Postpone</button>
                  </form>

                  <form action="" method="POST" style="display: inline;" onsubmit="return confirm('Cancel this match fixture?');">
                    <input type="hidden" name="match_id" value="<?php echo $m['id']; ?>">
                    <input type="hidden" name="action" value="cancel">
                    <input type="hidden" name="reason" value="Cancelled by Organizer">
                    <button type="submit" class="btn btn-secondary btn-sm" style="color: var(--accent-red);">Cancel</button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Reschedule Modal -->
<div id="rescheduleModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.8); backdrop-filter: blur(4px); z-index: 2000; align-items: center; justify-content: center;">
  <div style="background: var(--bg-card); border: 1px solid var(--border-subtle); border-radius: var(--radius-lg); padding: 24px; max-width: 480px; width: 100%;">
    <h3 style="font-size: 1.2rem; color: #fff; margin-bottom: 16px;">🔄 Reschedule Match Fixture</h3>
    <form action="" method="POST">
      <input type="hidden" name="action" value="reschedule">
      <input type="hidden" name="match_id" id="rescheduleMatchId">

      <div class="form-group">
        <label>New Scheduled Date</label>
        <input type="date" name="scheduled_date" id="rescheduleDate" class="form-control" required>
      </div>

      <div class="form-group">
        <label>New Scheduled Time</label>
        <input type="time" name="scheduled_time" id="rescheduleTime" class="form-control" required>
      </div>

      <div class="form-group">
        <label>Venue Allocation</label>
        <select name="venue_id" id="rescheduleVenue" class="form-control">
          <?php foreach ($venuesList as $v): ?>
            <option value="<?php echo $v['id']; ?>"><?php echo htmlspecialchars($v['name']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
        <button type="button" class="btn btn-secondary" onclick="closeRescheduleModal()">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Rescheduled Fixture</button>
      </div>
    </form>
  </div>
</div>

<script>
function openRescheduleModal(id, date, time, venueId) {
  document.getElementById('rescheduleMatchId').value = id;
  document.getElementById('rescheduleDate').value = date;
  document.getElementById('rescheduleTime').value = time;
  document.getElementById('rescheduleVenue').value = venueId;
  const modal = document.getElementById('rescheduleModal');
  modal.style.display = 'flex';
}
function closeRescheduleModal() {
  document.getElementById('rescheduleModal').style.display = 'none';
}
</script>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
