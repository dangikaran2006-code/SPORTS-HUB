<?php
/**
 * SportsHub - Automated Fixture Generator Wizard & Scheduler Engine
 */
$currentPage = 'matches';
$pageTitle = 'Generate Tournament Fixtures';

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/fixture-generator.php';
require_once __DIR__ . '/../includes/tournament-functions.php';

// Server-side RBAC Enforcement
requireRole(['admin', 'organizer']);

$tournamentId = intval($_GET['tournament_id'] ?? 0);
$tournamentsList = getTournamentsList();

$selectedTournament = null;
if ($tournamentId > 0) {
    $selectedTournament = getTournamentById($tournamentId);
}

$error = '';
$success = '';
$csrfToken = generateCsrfToken();

$approvedTeams = [];
$completedMatchesCount = 0;

if ($selectedTournament) {
    $approvedTeams = getTournamentApprovedTeams($selectedTournament['id']);
    $db = getDB();
    if ($db->getConnection()) {
        $completedMatchesCount = fetchOne("SELECT COUNT(*) as cnt FROM matches WHERE tournament_id = :tid AND status IN ('completed', 'live')", [':tid' => $selectedTournament['id']])['cnt'] ?? 0;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token        = $_POST['csrf_token'] ?? '';
    $tId          = intval($_POST['tournament_id'] ?? 0);
    $formatType   = $_POST['format_type'] ?? 'round_robin';
    $isDouble     = isset($_POST['is_double']) && $_POST['is_double'] === '1';
    $startDate    = $_POST['start_date'] ?? date('Y-m-d');
    $dailyMatches = intval($_POST['daily_matches'] ?? 4);
    $startTime    = $_POST['start_time'] ?? '09:00';
    $duration     = intval($_POST['duration_minutes'] ?? 90);
    $breakMins    = intval($_POST['break_minutes'] ?? 30);

    if (!validateCsrfToken($token)) {
        $error = 'Security token validation failed.';
    } elseif ($tId <= 0) {
        $error = 'Please select a tournament to generate fixtures for.';
    } elseif ($completedMatchesCount > 0) {
        $error = 'Cannot regenerate fixtures because this tournament already has live or completed matches.';
    } else {
        // Execute Fixture Generation
        $genResult = ['success' => false, 'error' => 'Unknown format type.'];

        if ($formatType === 'round_robin') {
            $genResult = generateRoundRobinFixtures($tId, $isDouble);
        } elseif ($formatType === 'knockout') {
            $genResult = generateKnockoutFixtures($tId);
        } elseif ($formatType === 'group_knockout') {
            $numGroups = intval($_POST['num_groups'] ?? 2);
            $genResult = generateGroupKnockoutFixtures($tId, $numGroups);
        }

        if ($genResult['success']) {
            // Apply Automatic Time Slot & Venue Scheduler
            assignFixtureSchedule($tId, $startDate, $dailyMatches, $startTime, $duration, $breakMins);
            $success = $genResult['message'] ?? 'Fixtures generated successfully!';
            header('Location: ' . BASE_URL . '/organizer/tournament-matches.php?tournament_id=' . $tId);
            exit;
        } else {
            $error = $genResult['error'] ?? 'Failed to generate fixtures.';
        }
    }
}

include_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-header">
  <div class="dashboard-title-group">
    <h1>Automated Fixture Generator</h1>
    <p>Generate Round-Robin, Knockout, or Group Stage match brackets with automated conflict-free scheduling.</p>
  </div>
  <div class="quick-actions-bar">
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

<div class="card" style="max-width: 780px; margin: 0 auto;">
  <!-- Tournament Selection Header -->
  <form method="GET" action="" style="margin-bottom:24px; border-bottom:1px solid var(--border-subtle); padding-bottom:20px;">
    <div class="form-group" style="margin:0;">
      <label>Select Tournament *</label>
      <div style="display:flex; gap:12px;">
        <select name="tournament_id" class="form-control" onchange="this.form.submit()">
          <option value="0">-- Choose Tournament --</option>
          <?php foreach ($tournamentsList as $t): ?>
            <option value="<?php echo $t['id']; ?>" <?php echo ($tournamentId == $t['id']) ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($t['name']) . " (" . htmlspecialchars($t['sport_name']) . ")"; ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
  </form>

  <?php if ($selectedTournament): ?>
    <!-- Tournament Specs Overview Banner -->
    <div style="background:var(--bg-card-hover); border:1px solid var(--border-subtle); border-radius:var(--radius-md); padding:16px; margin-bottom:24px; display:grid; grid-template-columns:repeat(auto-fit, minmax(160px, 1fr)); gap:16px;">
      <div>
        <span style="font-size:0.75rem; color:var(--text-muted); text-transform:uppercase; font-weight:700;">Tournament</span>
        <strong style="display:block; color:var(--text-main); font-size:0.95rem;"><?php echo htmlspecialchars($selectedTournament['name']); ?></strong>
      </div>
      <div>
        <span style="font-size:0.75rem; color:var(--text-muted); text-transform:uppercase; font-weight:700;">Sport</span>
        <strong style="display:block; color:var(--text-main); font-size:0.95rem;"><?php echo htmlspecialchars($selectedTournament['sport_name']); ?></strong>
      </div>
      <div>
        <span style="font-size:0.75rem; color:var(--text-muted); text-transform:uppercase; font-weight:700;">Approved Teams</span>
        <strong style="display:block; color:var(--accent-green); font-size:1.1rem; font-weight:800;"><?php echo count($approvedTeams); ?> Teams</strong>
      </div>
      <div>
        <span style="font-size:0.75rem; color:var(--text-muted); text-transform:uppercase; font-weight:700;">Format Spec</span>
        <strong style="display:block; color:var(--accent-amber); font-size:0.95rem; text-transform:capitalize;"><?php echo htmlspecialchars($selectedTournament['format']); ?></strong>
      </div>
    </div>

    <?php if (count($approvedTeams) < 2): ?>
      <div class="auth-alert auth-alert-danger">
        <span>A minimum of 2 approved teams is required to generate tournament fixtures. Currently approved: <?php echo count($approvedTeams); ?>.</span>
      </div>
    <?php else: ?>
      <!-- Fixture Generation Configuration Form -->
      <form action="" method="POST" onsubmit="return confirm('Are you sure you want to generate new fixtures? Existing scheduled matches will be reset.');">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
        <input type="hidden" name="tournament_id" value="<?php echo $selectedTournament['id']; ?>">

        <h3 style="font-size:1.05rem; font-weight:700; color:var(--text-main); margin-bottom:16px;">1. Fixture Algorithm & Structure</h3>

        <div class="form-row">
          <div class="form-group">
            <label>Tournament Format *</label>
            <select name="format_type" id="formatTypeSelect" class="form-control" onchange="updateMatchPreview()">
              <option value="round_robin" selected>Round Robin (League Stage)</option>
              <option value="knockout">Knockout Brackets (Single Elimination)</option>
              <option value="group_knockout">Group Stage + Knockout</option>
            </select>
          </div>

          <div class="form-group" id="roundRobinOptionGroup">
            <label>Leg Count</label>
            <select name="is_double" id="isDoubleSelect" class="form-control" onchange="updateMatchPreview()">
              <option value="0" selected>Single Round Robin (N × (N-1) / 2)</option>
              <option value="1">Double Round Robin (N × (N-1))</option>
            </select>
          </div>

          <div class="form-group" id="groupStageOptionGroup" style="display:none;">
            <label>Number of Groups</label>
            <select name="num_groups" class="form-control">
              <option value="2" selected>2 Groups (Group A & Group B)</option>
              <option value="4">4 Groups (Group A - D)</option>
            </select>
          </div>
        </div>

        <!-- Real-time Mathematical Calculation Box -->
        <div style="background:rgba(59, 130, 246, 0.1); border:1px solid rgba(59, 130, 246, 0.3); border-radius:var(--radius-md); padding:14px; margin-bottom:20px; color:var(--accent-blue);">
          <strong>Calculated Fixture Summary:</strong>
          <span id="fixtureMathPreview">Loading match calculation...</span>
        </div>

        <h3 style="font-size:1.05rem; font-weight:700; color:var(--text-main); margin-bottom:16px;">2. Automatic Date, Time & Venue Scheduler</h3>

        <div class="form-row">
          <div class="form-group">
            <label>First Match Start Date *</label>
            <input type="date" name="start_date" class="form-control" value="<?php echo !empty($selectedTournament['start_date']) ? $selectedTournament['start_date'] : date('Y-m-d'); ?>" required>
          </div>

          <div class="form-group">
            <label>Matches Per Day *</label>
            <input type="number" name="daily_matches" class="form-control" value="4" min="1" max="10" required>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>Daily Preferred Start Time *</label>
            <input type="time" name="start_time" class="form-control" value="09:00" required>
          </div>

          <div class="form-group">
            <label>Match Duration (Minutes)</label>
            <input type="number" name="duration_minutes" class="form-control" value="90" min="30" max="300" required>
          </div>

          <div class="form-group">
            <label>Break Between Matches (Mins)</label>
            <input type="number" name="break_minutes" class="form-control" value="30" min="0" max="120" required>
          </div>
        </div>

        <div style="display:flex; justify-content:flex-end; gap:12px; margin-top:24px;">
          <a href="<?php echo BASE_URL; ?>/organizer/matches.php" class="btn btn-secondary">Cancel</a>
          <button type="submit" class="btn btn-primary">
            ⚡ Generate Fixtures Transactionally
          </button>
        </div>
      </form>
    <?php endif; ?>
  <?php else: ?>
    <div style="text-align:center; padding:32px; color:var(--text-muted);">
      Please select a tournament from the dropdown above to configure fixture generation options.
    </div>
  <?php endif; ?>
</div>

<script>
const teamCount = <?php echo count($approvedTeams); ?>;

function updateMatchPreview() {
  const format = document.getElementById('formatTypeSelect').value;
  const isDouble = document.getElementById('isDoubleSelect').value === '1';
  const rrGroup = document.getElementById('roundRobinOptionGroup');
  const grpGroup = document.getElementById('groupStageOptionGroup');
  const preview = document.getElementById('fixtureMathPreview');

  if (format === 'round_robin') {
    rrGroup.style.display = 'block';
    grpGroup.style.display = 'none';
    const totalMatches = isDouble ? (teamCount * (teamCount - 1)) : Math.floor((teamCount * (teamCount - 1)) / 2);
    const perTeam = isDouble ? 2 * (teamCount - 1) : (teamCount - 1);
    preview.innerHTML = `Single/Double Round-Robin with ${teamCount} teams will generate <strong>${totalMatches} Total Matches</strong> (${perTeam} matches per team).`;
  } else if (format === 'knockout') {
    rrGroup.style.display = 'none';
    grpGroup.style.display = 'none';
    preview.innerHTML = `Knockout Single Elimination with ${teamCount} teams will generate first round brackets with BYEs automatically assigned to top seeds.`;
  } else if (format === 'group_knockout') {
    rrGroup.style.display = 'none';
    grpGroup.style.display = 'block';
    preview.innerHTML = `Group Stage + Knockout will divide ${teamCount} teams across groups and generate intra-group round robin fixtures.`;
  }
}

document.addEventListener('DOMContentLoaded', updateMatchPreview);
</script>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
