<?php
/**
 * SportsHub - Match Management Console & Fixture Overview
 */
$currentPage = 'matches';
$pageTitle = 'Match Scheduling & Fixtures';

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/match-functions.php';
require_once __DIR__ . '/../includes/tournament-functions.php';

// Access: Admin, Organizer, Scorer, Official, Team Manager, Player
requireRole(['admin', 'organizer', 'scorer', 'official', 'team_manager', 'player']);

$currentUser = getCurrentUser();

// Filters & Search
$search       = trim($_GET['search'] ?? '');
$tournamentId = intval($_GET['tournament_id'] ?? 0);
$sportId      = intval($_GET['sport_id'] ?? 0);
$date         = trim($_GET['date'] ?? '');
$venueId      = intval($_GET['venue_id'] ?? 0);
$status       = trim($_GET['status'] ?? '');

$matchesList     = getMatchesFiltered($search, $tournamentId, $sportId, $date, $venueId, $status);
$tournamentsList = getTournamentsList();
$sportsList      = getSportsList();
$venuesList      = getVenuesList();

include_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-header">
  <div class="dashboard-title-group">
    <h1>Match Schedule & Fixture Management</h1>
    <p>Schedule individual fixtures, generate tournament brackets, assign venues/officials, and track live matches.</p>
  </div>
  <div class="quick-actions-bar">
    <?php if (in_array(strtolower($currentUser['role']), ['admin', 'organizer'])): ?>
      <a href="<?php echo BASE_URL; ?>/organizer/generate-fixtures.php" class="btn btn-secondary">
        ⚡ Auto-Generate Fixtures
      </a>
      <a href="<?php echo BASE_URL; ?>/organizer/create-match.php" class="btn btn-primary">
        + Schedule Match
      </a>
    <?php endif; ?>
  </div>
</div>

<!-- Search & Filter Controls -->
<div class="card" style="margin-bottom:24px;">
  <form method="GET" action="" style="display:flex; flex-wrap:wrap; gap:16px; align-items:flex-end;">
    <div style="flex:1; min-width:200px;">
      <label style="font-size:0.85rem; color:var(--text-muted); font-weight:600; margin-bottom:6px; display:block;">Search Match / Team</label>
      <input type="text" name="search" class="form-control" placeholder="Search team or tournament name..." value="<?php echo htmlspecialchars($search); ?>">
    </div>

    <div style="width:180px;">
      <label style="font-size:0.85rem; color:var(--text-muted); font-weight:600; margin-bottom:6px; display:block;">Tournament</label>
      <select name="tournament_id" class="form-control">
        <option value="0">All Tournaments</option>
        <?php foreach ($tournamentsList as $t): ?>
          <option value="<?php echo $t['id']; ?>" <?php echo ($tournamentId == $t['id']) ? 'selected' : ''; ?>>
            <?php echo htmlspecialchars($t['name']); ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div style="width:160px;">
      <label style="font-size:0.85rem; color:var(--text-muted); font-weight:600; margin-bottom:6px; display:block;">Sport</label>
      <select name="sport_id" class="form-control">
        <option value="0">All Sports</option>
        <?php foreach ($sportsList as $sp): ?>
          <option value="<?php echo $sp['id']; ?>" <?php echo ($sportId == $sp['id']) ? 'selected' : ''; ?>>
            <?php echo htmlspecialchars($sp['name']); ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div style="width:150px;">
      <label style="font-size:0.85rem; color:var(--text-muted); font-weight:600; margin-bottom:6px; display:block;">Date</label>
      <input type="date" name="date" class="form-control" value="<?php echo htmlspecialchars($date); ?>">
    </div>

    <div style="width:150px;">
      <label style="font-size:0.85rem; color:var(--text-muted); font-weight:600; margin-bottom:6px; display:block;">Status</label>
      <select name="status" class="form-control">
        <option value="all">All Statuses</option>
        <option value="scheduled" <?php echo ($status === 'scheduled') ? 'selected' : ''; ?>>Scheduled</option>
        <option value="live" <?php echo ($status === 'live') ? 'selected' : ''; ?>>Live</option>
        <option value="completed" <?php echo ($status === 'completed') ? 'selected' : ''; ?>>Completed</option>
        <option value="postponed" <?php echo ($status === 'postponed') ? 'selected' : ''; ?>>Postponed</option>
        <option value="cancelled" <?php echo ($status === 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
      </select>
    </div>

    <div style="display:flex; gap:8px;">
      <button type="submit" class="btn btn-primary">Filter</button>
      <a href="<?php echo BASE_URL; ?>/organizer/matches.php" class="btn btn-secondary">Reset</a>
    </div>
  </form>
</div>

<!-- Matches Data Table -->
<div class="card">
  <div class="table-responsive">
    <table class="sports-table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Tournament & Sport</th>
          <th>Round</th>
          <th>Match Fixture</th>
          <th>Date & Time</th>
          <th>Venue & Official</th>
          <th>Status</th>
          <th>Result Summary</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($matchesList)): ?>
          <tr>
            <td colspan="9" style="text-align:center; padding:32px; color:var(--text-muted);">
              No scheduled matches found matching the criteria.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($matchesList as $m): ?>
            <?php
              $mStatus = strtolower($m['status'] ?? 'scheduled');
              $statusBadge = 'badge-upcoming';
              if ($mStatus === 'live') $statusBadge = 'badge-live';
              elseif ($mStatus === 'completed') $statusBadge = 'badge-active';
              elseif ($mStatus === 'postponed') $statusBadge = 'badge-upcoming';
              elseif ($mStatus === 'cancelled') $statusBadge = 'badge-danger';
            ?>
            <tr>
              <td><strong style="color:var(--text-muted);">#<?php echo $m['id']; ?></strong></td>
              <td>
                <strong style="color:var(--text-main); display:block; font-size:0.9rem;">
                  <?php echo htmlspecialchars($m['tournament_name']); ?>
                </strong>
                <?php echo getSportBadge($m['sport_name']); ?>
              </td>
              <td>
                <span style="font-weight:700; color:var(--accent-amber); font-size:0.85rem;">
                  <?php echo htmlspecialchars($m['round_name'] ?? 'League'); ?>
                </span>
              </td>
              <td>
                <div style="display:flex; align-items:center; gap:8px;">
                  <strong style="color:var(--text-main); font-size:0.95rem;"><?php echo htmlspecialchars($m['team_a_name']); ?></strong>
                  <span style="color:var(--accent-green); font-weight:800; font-size:0.75rem;">VS</span>
                  <strong style="color:var(--text-main); font-size:0.95rem;"><?php echo htmlspecialchars($m['team_b_name']); ?></strong>
                </div>
              </td>
              <td>
                <span style="display:block; font-weight:600; color:var(--text-main);">
                  <?php echo !empty($m['scheduled_date']) ? date('M d, Y', strtotime($m['scheduled_date'])) : 'TBD'; ?>
                </span>
                <span style="font-size:0.8rem; color:var(--text-muted);">
                  <?php echo !empty($m['scheduled_time']) ? date('h:i A', strtotime($m['scheduled_time'])) : 'TBD'; ?>
                </span>
              </td>
              <td>
                <div style="font-size:0.85rem; color:var(--text-main); font-weight:600;">
                  📍 <?php echo htmlspecialchars($m['venue_name'] ?? 'TBD Venue'); ?>
                </div>
                <?php if (!empty($m['official_name'])): ?>
                  <div style="font-size:0.75rem; color:var(--text-muted);">
                    👨‍⚖️ <?php echo htmlspecialchars($m['official_name']); ?>
                  </div>
                <?php endif; ?>
              </td>
              <td>
                <span class="status-badge <?php echo $statusBadge; ?>">
                  <?php if ($mStatus === 'live'): ?>🔴 LIVE<?php else: ?><?php echo ucfirst($mStatus); ?><?php endif; ?>
                </span>
              </td>
              <td>
                <span style="font-size:0.85rem; color:var(--accent-amber); font-weight:600;">
                  <?php echo !empty($m['result_summary']) ? htmlspecialchars($m['result_summary']) : '-'; ?>
                </span>
              </td>
              <td>
                <div style="display:flex; gap:6px;">
                  <a href="<?php echo BASE_URL; ?>/organizer/match-details.php?id=<?php echo $m['id']; ?>" class="btn btn-secondary btn-sm">
                    View
                  </a>

                  <?php if (in_array(strtolower($currentUser['role']), ['admin', 'organizer'])): ?>
                    <a href="<?php echo BASE_URL; ?>/organizer/edit-match.php?id=<?php echo $m['id']; ?>" class="btn btn-secondary btn-sm">
                      Edit
                    </a>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
