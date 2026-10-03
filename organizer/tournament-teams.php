<?php
/**
 * SportsHub - Tournament Teams & Roster Manager
 */
$currentPage = 'tournaments';
$pageTitle = 'Tournament Teams Roster';

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/tournament-functions.php';

// Server-side RBAC Enforcement
requireRole(['admin', 'organizer']);

$tournamentId = intval($_GET['tournament_id'] ?? 1);
$tournament = getTournamentById($tournamentId);

if (!$tournament) {
    header('Location: ' . BASE_URL . '/organizer/tournaments.php');
    exit;
}

$error = '';
$message = '';
$csrfToken = generateCsrfToken();

// Handle Form POST Actions (Add Team, Change Status, Remove Team)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $token  = $_POST['csrf_token'] ?? '';

    if (!validateCsrfToken($token)) {
        $error = 'Security validation failed (Invalid CSRF Token).';
    } else {
        if ($action === 'add_team') {
            $teamId = intval($_POST['team_id'] ?? 0);
            $regStatus = trim($_POST['registration_status'] ?? 'approved');

            if ($teamId <= 0) {
                $error = 'Please select a team to add.';
            } else {
                $result = addTeamToTournamentService($tournament['id'], $teamId, $regStatus);
                if ($result['success']) {
                    $message = $result['message'];
                } else {
                    $error = $result['error'];
                }
            }
        } elseif ($action === 'update_status') {
            $ttId = intval($_POST['tournament_team_id'] ?? 0);
            $newStatus = trim($_POST['status'] ?? 'approved');
            if ($ttId > 0) {
                updateTournamentTeamStatusService($ttId, $newStatus);
                $message = "Registration status updated to {$newStatus}.";
            }
        } elseif ($action === 'remove_team') {
            $ttId = intval($_POST['tournament_team_id'] ?? 0);
            if ($ttId > 0) {
                removeTeamFromTournamentService($ttId);
                $message = "Team removed from tournament roster.";
            }
        }
    }
}

$registeredTeams = getTournamentTeamsService($tournament['id']);
$availableTeams  = getAvailableTeamsForTournament($tournament['id'], $tournament['sport_id']);
$approvedCount   = count(array_filter($registeredTeams, function($t) { return $t['registration_status'] === 'approved'; }));
$maxTeams        = intval($tournament['max_teams'] ?? 16);

include_once __DIR__ . '/../includes/header.php';
?>

<!-- Header -->
<div class="dashboard-header">
  <div class="dashboard-title-group">
    <h1>Roster Teams: <?php echo htmlspecialchars($tournament['name']); ?></h1>
    <p>Sport: <strong><?php echo htmlspecialchars($tournament['sport_name']); ?></strong> &bull; Approved Teams: <strong><?php echo $approvedCount; ?> / <?php echo $maxTeams; ?> Max</strong></p>
  </div>
  <div class="quick-actions-bar">
    <a href="<?php echo BASE_URL; ?>/organizer/tournament-details.php?id=<?php echo $tournament['id']; ?>" class="btn btn-secondary">
      Back to Details
    </a>
    <?php if ($approvedCount < $maxTeams): ?>
      <button onclick="openModal('addTeamModal')" class="btn btn-primary">
        + Add Team to Tournament
      </button>
    <?php else: ?>
      <button class="btn btn-secondary" disabled style="opacity:0.6; cursor:not-allowed;">
        Max Team Limit Reached
      </button>
    <?php endif; ?>
  </div>
</div>

<?php if (!empty($message)): ?>
  <div class="auth-alert auth-alert-success" style="margin-bottom:20px;">
    <span><?php echo htmlspecialchars($message); ?></span>
  </div>
<?php endif; ?>

<?php if (!empty($error)): ?>
  <div class="auth-alert auth-alert-danger" style="margin-bottom:20px;">
    <span><?php echo htmlspecialchars($error); ?></span>
  </div>
<?php endif; ?>

<!-- Teams Table / Empty State -->
<div class="card">
  <div class="section-header" style="margin-bottom:16px;">
    <h2>Registered Tournament Teams (<?php echo count($registeredTeams); ?>)</h2>
  </div>

  <?php if (empty($registeredTeams)): ?>
    <div style="text-align:center; padding:36px 16px;">
      <div style="font-size:2.5rem; margin-bottom:8px;">🛡️</div>
      <h3 style="font-size:1.1rem; margin-bottom:6px;">No Teams Registered Yet</h3>
      <p style="color:var(--text-muted); font-size:0.85rem; margin-bottom:16px;">Click the button below to register matching <?php echo htmlspecialchars($tournament['sport_name']); ?> teams.</p>
      <?php if ($approvedCount < $maxTeams): ?>
        <button onclick="openModal('addTeamModal')" class="btn btn-primary btn-sm">+ Add Team</button>
      <?php endif; ?>
    </div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="sports-table">
        <thead>
          <tr>
            <th>Team & Badge</th>
            <th>Sport</th>
            <th>Captain</th>
            <th>Roster Size</th>
            <th>Registration Status</th>
            <th>Joined Date</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($registeredTeams as $tt): ?>
            <tr data-searchable>
              <td>
                <div style="display:flex; align-items:center; gap:10px;">
                  <div class="team-badge-circle" style="width:32px;height:32px;font-size:0.75rem; font-weight:800; color:var(--accent-green);">
                    <?php echo htmlspecialchars($tt['short_name']); ?>
                  </div>
                  <strong style="color:var(--text-main); font-size:0.95rem;"><?php echo htmlspecialchars($tt['team_name']); ?></strong>
                </div>
              </td>
              <td><?php echo getSportBadge($tt['sport_name']); ?></td>
              <td><?php echo htmlspecialchars($tt['captain_name'] ?? 'Unassigned'); ?></td>
              <td><?php echo $tt['players_count']; ?> Players</td>
              <td>
                <?php if ($tt['registration_status'] === 'approved'): ?>
                  <span class="status-badge badge-active">Approved</span>
                <?php elseif ($tt['registration_status'] === 'pending'): ?>
                  <span class="status-badge badge-upcoming">Pending</span>
                <?php else: ?>
                  <span class="status-badge badge-danger">Rejected</span>
                <?php endif; ?>
              </td>
              <td><?php echo date('M d, Y', strtotime($tt['joined_at'])); ?></td>
              <td>
                <div style="display:flex; gap:6px;">
                  <!-- Approve Button -->
                  <?php if ($tt['registration_status'] !== 'approved'): ?>
                    <form action="" method="POST" style="display:inline;">
                      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                      <input type="hidden" name="action" value="update_status">
                      <input type="hidden" name="tournament_team_id" value="<?php echo $tt['id']; ?>">
                      <input type="hidden" name="status" value="approved">
                      <button type="submit" class="btn btn-secondary btn-sm" style="color:var(--accent-green);">Approve</button>
                    </form>
                  <?php endif; ?>

                  <!-- Reject Button -->
                  <?php if ($tt['registration_status'] !== 'rejected'): ?>
                    <form action="" method="POST" style="display:inline;">
                      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                      <input type="hidden" name="action" value="update_status">
                      <input type="hidden" name="tournament_team_id" value="<?php echo $tt['id']; ?>">
                      <input type="hidden" name="status" value="rejected">
                      <button type="submit" class="btn btn-secondary btn-sm" style="color:var(--accent-amber);">Reject</button>
                    </form>
                  <?php endif; ?>

                  <!-- Remove Button -->
                  <form action="" method="POST" style="display:inline;" onsubmit="return confirm('Remove team <?php echo htmlspecialchars(addslashes($tt['team_name'])); ?> from tournament?');">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                    <input type="hidden" name="action" value="remove_team">
                    <input type="hidden" name="tournament_team_id" value="<?php echo $tt['id']; ?>">
                    <button type="submit" class="btn btn-secondary btn-sm" style="color:var(--accent-red);">Remove</button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<!-- Modal: Add Team to Tournament -->
<div class="modal-overlay" id="addTeamModal">
  <div class="modal-container">
    <div class="modal-header">
      <h2>Add Team to Tournament</h2>
      <button class="modal-close-btn" onclick="closeModal('addTeamModal')">&times;</button>
    </div>
    
    <?php if (empty($availableTeams)): ?>
      <div style="padding:20px; text-align:center; color:var(--text-muted);">
        All available <strong><?php echo htmlspecialchars($tournament['sport_name']); ?></strong> teams are already registered in this tournament.
      </div>
    <?php else: ?>
      <form action="" method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
        <input type="hidden" name="action" value="add_team">

        <div class="form-group">
          <label>Select Team (Only <?php echo htmlspecialchars($tournament['sport_name']); ?> Teams) *</label>
          <select name="team_id" class="form-control" required>
            <option value="">-- Choose Team --</option>
            <?php foreach ($availableTeams as $at): ?>
              <option value="<?php echo $at['id']; ?>">
                <?php echo htmlspecialchars($at['name']) . " (" . htmlspecialchars($at['short_name']) . ")"; ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label>Registration Status *</label>
          <select name="registration_status" class="form-control" required>
            <option value="approved" selected>Approved</option>
            <option value="pending">Pending Approval</option>
          </select>
        </div>

        <div style="display:flex; justify-content:flex-end; gap:12px; margin-top:20px;">
          <button type="button" class="btn btn-secondary" onclick="closeModal('addTeamModal')">Cancel</button>
          <button type="submit" class="btn btn-primary">Add Team</button>
        </div>
      </form>
    <?php endif; ?>
  </div>
</div>

<script>
function openModal(id) { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }
</script>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
