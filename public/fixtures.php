<?php
/**
 * SportsHub - Public Spectator Fixture Directory & Live Scoreboard
 */
$currentPage = 'fixtures';
$pageTitle = 'Public Fixtures & Results';

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/match-functions.php';
require_once __DIR__ . '/../includes/tournament-functions.php';

$tournamentId = isset($_GET['tournament_id']) ? intval($_GET['tournament_id']) : 0;
$activeTab    = $_GET['tab'] ?? 'all';

$tournamentsList = getTournamentsList();
$selectedTournament = null;

if ($tournamentId > 0) {
    $selectedTournament = getTournamentById($tournamentId);
} else {
    $selectedTournament = $tournamentsList[0] ?? null;
    if ($selectedTournament) $tournamentId = $selectedTournament['id'];
}

$allMatches = getMatchesFiltered('', $tournamentId, 0, '', 0, 'all');

// Filter matches based on public tab choice
$filteredMatches = [];
$todayDate = date('Y-m-d');

foreach ($allMatches as $m) {
    $mStatus = strtolower($m['status'] ?? 'scheduled');
    $mDate   = $m['scheduled_date'] ?? '';

    if ($activeTab === 'today') {
        if ($mDate === $todayDate || $mStatus === 'live') {
            $filteredMatches[] = $m;
        }
    } elseif ($activeTab === 'upcoming') {
        if ($mStatus === 'scheduled' || $mStatus === 'postponed') {
            $filteredMatches[] = $m;
        }
    } elseif ($activeTab === 'results') {
        if ($mStatus === 'completed') {
            $filteredMatches[] = $m;
        }
    } else {
        $filteredMatches[] = $m;
    }
}

include_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-header">
  <div class="dashboard-title-group">
    <h1>Public Match Fixtures & Scores</h1>
    <p>Spectator hub for match schedules, live score updates, and historical results across all tournaments.</p>
  </div>
</div>

<!-- Tournament Selector Bar -->
<div class="card" style="margin-bottom:20px;">
  <form method="GET" action="" style="display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
    <label style="font-weight:700; color:var(--text-main); font-size:0.95rem;">Choose Tournament:</label>
    <select name="tournament_id" class="form-control" style="max-width:320px;" onchange="this.form.submit()">
      <?php foreach ($tournamentsList as $t): ?>
        <option value="<?php echo $t['id']; ?>" <?php echo ($tournamentId == $t['id']) ? 'selected' : ''; ?>>
          <?php echo htmlspecialchars($t['name']) . " (" . htmlspecialchars($t['sport_name']) . ")"; ?>
        </option>
      <?php endforeach; ?>
    </select>
  </form>
</div>

<?php if ($selectedTournament): ?>
  <!-- Tournament Spectator Banner Header -->
  <div class="card" style="margin-bottom:24px; padding:20px;">
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px;">
      <div>
        <div style="display:flex; align-items:center; gap:8px; margin-bottom:6px;">
          <?php echo getSportBadge($selectedTournament['sport_name']); ?>
          <span style="color:var(--text-muted); font-size:0.85rem;">Format: <?php echo ucfirst(htmlspecialchars($selectedTournament['format'])); ?></span>
        </div>
        <h2 style="font-size:1.5rem; font-weight:800; color:var(--text-main); margin:0;">
          <?php echo htmlspecialchars($selectedTournament['name']); ?>
        </h2>
        <span style="color:var(--text-muted); font-size:0.85rem;">
          📍 Ground: <?php echo htmlspecialchars($selectedTournament['venue_name'] ?? 'TBD Venue'); ?>
        </span>
      </div>

      <!-- Public Spectator Tabs -->
      <div class="filter-pills">
        <a href="<?php echo BASE_URL; ?>/public/fixtures.php?tournament_id=<?php echo $tournamentId; ?>&tab=all" class="filter-pill-btn <?php echo ($activeTab==='all')?'active':''; ?>">All Fixtures</a>
        <a href="<?php echo BASE_URL; ?>/public/fixtures.php?tournament_id=<?php echo $tournamentId; ?>&tab=today" class="filter-pill-btn <?php echo ($activeTab==='today')?'active':''; ?>">Matches Today</a>
        <a href="<?php echo BASE_URL; ?>/public/fixtures.php?tournament_id=<?php echo $tournamentId; ?>&tab=upcoming" class="filter-pill-btn <?php echo ($activeTab==='upcoming')?'active':''; ?>">Upcoming</a>
        <a href="<?php echo BASE_URL; ?>/public/fixtures.php?tournament_id=<?php echo $tournamentId; ?>&tab=results" class="filter-pill-btn <?php echo ($activeTab==='results')?'active':''; ?>">Results</a>
      </div>
    </div>
  </div>

  <!-- Spectator Responsive Fixture Cards Grid -->
  <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(320px, 1fr)); gap:20px;">
    <?php if (empty($filteredMatches)): ?>
      <div class="card" style="grid-column: 1 / -1; text-align:center; padding:36px;">
        <div style="font-size:2.5rem; margin-bottom:8px;">🏆</div>
        <h3 style="font-size:1.1rem; color:var(--text-main);">No Matches Found</h3>
        <p style="color:var(--text-muted); font-size:0.85rem;">No fixtures match the selected filter category for this tournament.</p>
      </div>
    <?php else: ?>
      <?php foreach ($filteredMatches as $m): ?>
        <?php
          $mStatus = strtolower($m['status'] ?? 'scheduled');
          $statusBadge = 'badge-upcoming';
          if ($mStatus === 'live') $statusBadge = 'badge-live';
          elseif ($mStatus === 'completed') $statusBadge = 'badge-active';
          elseif ($mStatus === 'postponed') $statusBadge = 'badge-upcoming';
          elseif ($mStatus === 'cancelled') $statusBadge = 'badge-danger';
        ?>
        <div class="card" style="padding:20px; display:flex; flex-direction:column; justify-space-between;">
          <!-- Card Header -->
          <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px;">
            <span style="font-size:0.75rem; font-weight:800; color:var(--accent-amber); text-transform:uppercase;">
              <?php echo htmlspecialchars($m['round_name'] ?? 'League'); ?>
            </span>
            <span class="status-badge <?php echo $statusBadge; ?>">
              <?php if ($mStatus === 'live'): ?>🔴 LIVE NOW<?php else: ?><?php echo ucfirst($mStatus); ?><?php endif; ?>
            </span>
          </div>

          <!-- Teams & vs -->
          <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:16px; background:var(--bg-card-hover); padding:12px; border-radius:var(--radius-md);">
            <div style="text-align:center; flex:1;">
              <strong style="color:var(--text-main); font-size:1rem; display:block;"><?php echo htmlspecialchars($m['team_a_name']); ?></strong>
              <span style="font-size:0.75rem; color:var(--text-muted); font-weight:700;"><?php echo htmlspecialchars($m['team_a_short']); ?></span>
            </div>
            
            <div style="font-size:1rem; font-weight:900; color:var(--accent-green); padding:0 8px;">VS</div>

            <div style="text-align:center; flex:1;">
              <strong style="color:var(--text-main); font-size:1rem; display:block;"><?php echo htmlspecialchars($m['team_b_name']); ?></strong>
              <span style="font-size:0.75rem; color:var(--text-muted); font-weight:700;"><?php echo htmlspecialchars($m['team_b_short']); ?></span>
            </div>
          </div>

          <!-- Result / Schedule Info -->
          <?php if (!empty($m['result_summary'])): ?>
            <div style="text-align:center; color:var(--accent-amber); font-weight:700; font-size:0.9rem; margin-bottom:12px; background:rgba(234, 179, 8, 0.1); padding:6px; border-radius:4px;">
              🏆 <?php echo htmlspecialchars($m['result_summary']); ?>
            </div>
          <?php endif; ?>

          <div style="display:flex; justify-content:space-between; align-items:center; font-size:0.8rem; color:var(--text-muted); border-top:1px solid var(--border-subtle); padding-top:12px; margin-top:auto;">
            <div>
              📅 <?php echo !empty($m['scheduled_date']) ? date('M d, Y', strtotime($m['scheduled_date'])) : 'TBD'; ?>
              @ <?php echo !empty($m['scheduled_time']) ? date('h:i A', strtotime($m['scheduled_time'])) : 'TBD'; ?>
            </div>
            <div>
              📍 <?php echo htmlspecialchars($m['venue_name'] ?? 'TBD'); ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
<?php endif; ?>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
