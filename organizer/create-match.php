<?php
/**
 * SportsHub - Schedule Individual Match Form & Validation
 */
$currentPage = 'matches';
$pageTitle = 'Schedule Match';

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/match-functions.php';
require_once __DIR__ . '/../includes/tournament-functions.php';
require_once __DIR__ . '/../includes/team-player-functions.php';

// Server-side RBAC Enforcement
requireRole(['admin', 'organizer']);

$error = '';
$csrfToken = generateCsrfToken();

$tournamentsList = getTournamentsList();
$sportsList      = getSportsList();
$venuesList      = getVenuesList();
$officialsList   = fetchAll("SELECT id, name, role, sport_id FROM officials WHERE status = 1 ORDER BY name ASC");
$allTeams        = getTeamsFiltered('', 0, 'all');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token        = $_POST['csrf_token'] ?? '';
    $tournamentId = intval($_POST['tournament_id'] ?? 0);
    $sportId      = intval($_POST['sport_id'] ?? 0);
    $roundName    = trim($_POST['round_name'] ?? 'League');
    $teamAId      = intval($_POST['team_a_id'] ?? 0);
    $teamBId      = intval($_POST['team_b_id'] ?? 0);
    $sDate        = $_POST['scheduled_date'] ?? '';
    $sTime        = $_POST['scheduled_time'] ?? '';
    $duration     = intval($_POST['duration_minutes'] ?? 90);
    $venueId      = intval($_POST['venue_id'] ?? 0);
    $officialId   = intval($_POST['official_id'] ?? 0);
    $status       = $_POST['status'] ?? 'scheduled';

    if (!validateCsrfToken($token)) {
        $error = 'Invalid security token. Please try again.';
    } elseif ($tournamentId <= 0 || $sportId <= 0) {
        $error = 'Tournament and Sport selection are required.';
    } elseif ($teamAId <= 0 || $teamBId <= 0) {
        $error = 'Both Team A and Team B are required.';
    } elseif ($teamAId === $teamBId) {
        $error = 'Team A and Team B cannot be the same team.';
    } elseif (empty($sDate) || empty($sTime)) {
        $error = 'Scheduled Date and Time are required.';
    } elseif ($venueId <= 0) {
        $error = 'Please select a valid venue.';
    } else {
        $result = createMatchService([
            'tournament_id'    => $tournamentId,
            'sport_id'         => $sportId,
            'round_name'       => $roundName,
            'team_a_id'        => $teamAId,
            'team_b_id'        => $teamBId,
            'scheduled_date'   => $sDate,
            'scheduled_time'   => $sTime,
            'duration_minutes' => $duration,
            'venue_id'         => $venueId,
            'official_id'      => $officialId,
            'status'           => $status
        ]);

        if ($result['success']) {
            header('Location: ' . BASE_URL . '/organizer/matches.php');
            exit;
        } else {
            $error = $result['error'] ?? 'Failed to schedule match.';
        }
    }
}

include_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-header">
  <div class="dashboard-title-group">
    <h1>Schedule New Match</h1>
    <p>Configure match fixture parameters, select competing teams, assign venue and official with instant conflict checking.</p>
  </div>
  <div class="quick-actions-bar">
    <a href="<?php echo BASE_URL; ?>/organizer/matches.php" class="btn btn-secondary">
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

    <div class="form-row">
      <div class="form-group">
        <label>Tournament *</label>
        <select name="tournament_id" id="tournamentSelect" class="form-control" required onchange="onTournamentChange()">
          <option value="">-- Select Tournament --</option>
          <?php foreach ($tournamentsList as $t): ?>
            <option value="<?php echo $t['id']; ?>" data-sport-id="<?php echo $t['sport_id']; ?>" <?php echo (($_POST['tournament_id'] ?? '') == $t['id']) ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($t['name']) . " (" . htmlspecialchars($t['sport_name']) . ")"; ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label>Sport Category *</label>
        <select name="sport_id" id="sportSelect" class="form-control" required onchange="filterTeamsAndOfficials()">
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
        <label>Team A *</label>
        <select name="team_a_id" id="teamASelect" class="form-control" required>
          <option value="">-- Select Team A --</option>
          <?php foreach ($allTeams as $tm): ?>
            <option value="<?php echo $tm['id']; ?>" data-sport-id="<?php echo $tm['sport_id']; ?>" <?php echo (($_POST['team_a_id'] ?? '') == $tm['id']) ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($tm['name']); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label>Team B *</label>
        <select name="team_b_id" id="teamBSelect" class="form-control" required>
          <option value="">-- Select Team B --</option>
          <?php foreach ($allTeams as $tm): ?>
            <option value="<?php echo $tm['id']; ?>" data-sport-id="<?php echo $tm['sport_id']; ?>" <?php echo (($_POST['team_b_id'] ?? '') == $tm['id']) ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($tm['name']); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label>Tournament Round Stage</label>
        <input type="text" name="round_name" class="form-control" placeholder="e.g. League, Group A, Quarter Final, Semi Final" value="<?php echo htmlspecialchars($_POST['round_name'] ?? 'League'); ?>">
      </div>

      <div class="form-group">
        <label>Match Duration (Minutes)</label>
        <input type="number" name="duration_minutes" class="form-control" value="<?php echo htmlspecialchars($_POST['duration_minutes'] ?? '90'); ?>" min="30" max="300">
      </div>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label>Scheduled Date *</label>
        <input type="date" name="scheduled_date" class="form-control" value="<?php echo htmlspecialchars($_POST['scheduled_date'] ?? date('Y-m-d')); ?>" required>
      </div>

      <div class="form-group">
        <label>Scheduled Time *</label>
        <input type="time" name="scheduled_time" class="form-control" value="<?php echo htmlspecialchars($_POST['scheduled_time'] ?? '10:00'); ?>" required>
      </div>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label>Venue Ground *</label>
        <select name="venue_id" class="form-control" required>
          <option value="">-- Select Venue --</option>
          <?php foreach ($venuesList as $v): ?>
            <option value="<?php echo $v['id']; ?>" <?php echo (($_POST['venue_id'] ?? '') == $v['id']) ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($v['name']) . " (" . htmlspecialchars($v['location']) . ")"; ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label>Match Official / Referee</label>
        <select name="official_id" id="officialSelect" class="form-control">
          <option value="0">-- Unassigned --</option>
          <?php foreach ($officialsList as $off): ?>
            <option value="<?php echo $off['id']; ?>" data-sport-id="<?php echo $off['sport_id']; ?>" <?php echo (($_POST['official_id'] ?? '') == $off['id']) ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($off['name']) . " (" . ucfirst($off['role']) . ")"; ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="form-group">
      <label>Initial Match Status</label>
      <select name="status" class="form-control">
        <option value="scheduled" selected>Scheduled</option>
        <option value="live">Live</option>
        <option value="postponed">Postponed</option>
      </select>
    </div>

    <div style="display:flex; justify-content:flex-end; gap:12px; margin-top:24px;">
      <a href="<?php echo BASE_URL; ?>/organizer/matches.php" class="btn btn-secondary">Cancel</a>
      <button type="submit" class="btn btn-primary">Save & Schedule Match</button>
    </div>
  </form>
</div>

<script>
function onTournamentChange() {
  const tSelect = document.getElementById('tournamentSelect');
  const selectedOpt = tSelect.options[tSelect.selectedIndex];
  if (selectedOpt && selectedOpt.getAttribute('data-sport-id')) {
    const sportId = selectedOpt.getAttribute('data-sport-id');
    document.getElementById('sportSelect').value = sportId;
    filterTeamsAndOfficials();
  }
}

function filterTeamsAndOfficials() {
  const sportId = document.getElementById('sportSelect').value;

  ['teamASelect', 'teamBSelect'].forEach(selectId => {
    const select = document.getElementById(selectId);
    select.querySelectorAll('option').forEach(opt => {
      if (opt.value === '') {
        opt.style.display = 'block';
      } else {
        const teamSportId = opt.getAttribute('data-sport-id');
        if (sportId === '' || teamSportId === sportId) {
          opt.style.display = 'block';
        } else {
          opt.style.display = 'none';
          if (opt.selected) select.value = '';
        }
      }
    });
  });

  const offSelect = document.getElementById('officialSelect');
  offSelect.querySelectorAll('option').forEach(opt => {
    if (opt.value === '0' || opt.value === '') {
      opt.style.display = 'block';
    } else {
      const offSportId = opt.getAttribute('data-sport-id');
      if (sportId === '' || offSportId === sportId) {
        opt.style.display = 'block';
      } else {
        opt.style.display = 'none';
        if (opt.selected) offSelect.value = '0';
      }
    }
  });
}

document.addEventListener('DOMContentLoaded', filterTeamsAndOfficials);
</script>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
