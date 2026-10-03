<?php
/**
 * SportsHub - Tournament Specific Matches & Grouped Fixtures View
 */
$currentPage = 'tournaments';
$pageTitle = 'Tournament Fixtures & Schedule';

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/match-functions.php';
require_once __DIR__ . '/../includes/tournament-functions.php';

$tournamentId = intval($_GET['tournament_id'] ?? 1);
$tournament   = getTournamentById($tournamentId);

if (!$tournament) {
    header('Location: ' . BASE_URL . '/organizer/tournaments.php');
    exit;
}

$currentUser = getCurrentUser();
$matches = getMatchesFiltered('', $tournament['id'], 0, '', 0, 'all');

// Group matches by Round Name
$groupedMatches = [];
foreach ($matches as $m) {
    $rName = !empty($m['round_name']) ? $m['round_name'] : 'League Stage';
    $groupedMatches[$rName][] = $m;
}

include_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-header">
  <div class="dashboard-title-group">
    <h1>Matches & Fixtures: <?php echo htmlspecialchars($tournament['name']); ?></h1>
    <p>Sport: <strong><?php echo htmlspecialchars($tournament['sport_name']); ?></strong> &bull; Format: <strong><?php echo htmlspecialchars($tournament['format']); ?></strong> &bull; Total Matches: <strong><?php echo count($matches); ?></strong></p>
  </div>
  <div class="quick-actions-bar">
    <a href="<?php echo BASE_URL; ?>/organizer/tournament-details.php?id=<?php echo $tournament['id']; ?>" class="btn btn-secondary">
      Back to Tournament Details
    </a>
    <?php if (in_array(strtolower($currentUser['role']), ['admin', 'organizer'])): ?>
      <a href="<?php echo BASE_URL; ?>/organizer/generate-fixtures.php?tournament_id=<?php echo $tournament['id']; ?>" class="btn btn-secondary">
        ⚡ Auto-Generate Fixtures
      </a>
      <a href="<?php echo BASE_URL; ?>/organizer/create-match.php?tournament_id=<?php echo $tournament['id']; ?>" class="btn btn-primary">
        + Schedule Match
      </a>
    <?php endif; ?>
  </div>
</div>

<?php if (empty($groupedMatches)): ?>
  <div class="card" style="text-align:center; padding:48px 24px;">
    <div style="font-size:3rem; margin-bottom:12px;">📅</div>
    <h3 style="font-size:1.3rem; margin-bottom:8px;">No Matches Scheduled Yet</h3>
    <p style="color:var(--text-muted); font-size:0.9rem; max-width:480px; margin:0 auto 20px auto;">
      No fixtures have been scheduled for this tournament. Use the automatic fixture generator or manually add matches.
    </p>
    <?php if (in_array(strtolower($currentUser['role']), ['admin', 'organizer'])): ?>
      <a href="<?php echo BASE_URL; ?>/organizer/generate-fixtures.php?tournament_id=<?php echo $tournament['id']; ?>" class="btn btn-primary">
        ⚡ Generate Tournament Fixtures Now
      </a>
    <?php endif; ?>
  </div>
<?php else: ?>
  <?php foreach ($groupedMatches as $roundTitle => $roundMatches): ?>
    <div class="card" style="margin-bottom:24px;">
      <div class="section-header" style="margin-bottom:16px; border-bottom:1px solid var(--border-subtle); padding-bottom:10px;">
        <h2 style="color:var(--accent-green); font-size:1.2rem; margin:0; text-transform:uppercase; letter-spacing:0.5px;">
          <?php echo htmlspecialchars($roundTitle); ?> (<?php echo count($roundMatches); ?> Matches)
        </h2>
      </div>

      <div class="table-responsive">
        <table class="sports-table">
          <thead>
            <tr>
              <th>Match #</th>
              <th>Teams</th>
              <th>Date & Time</th>
              <th>Venue</th>
              <th>Official</th>
              <th>Status</th>
              <th>Result / Summary</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($roundMatches as $m): ?>
              <?php
                $mStatus = strtolower($m['status'] ?? 'scheduled');
                $statusBadge = 'badge-upcoming';
                if ($mStatus === 'live') $statusBadge = 'badge-live';
                elseif ($mStatus === 'completed') $statusBadge = 'badge-active';
                elseif ($mStatus === 'postponed') $statusBadge = 'badge-upcoming';
                elseif ($mStatus === 'cancelled') $statusBadge = 'badge-danger';
              ?>
              <tr>
                <td><strong style="color:var(--text-muted);">#<?php echo $m['match_number'] ?? $m['id']; ?></strong></td>
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
                <td>📍 <?php echo htmlspecialchars($m['venue_name'] ?? 'TBD Ground'); ?></td>
                <td>👨‍⚖️ <?php echo htmlspecialchars($m['official_name'] ?? 'Unassigned'); ?></td>
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
                    <a href="<?php echo BASE_URL; ?>/organizer/match-details.php?id=<?php echo $m['id']; ?>" class="btn btn-secondary btn-sm">View</a>
                    <?php if (in_array(strtolower($currentUser['role']), ['admin', 'organizer'])): ?>
                      <a href="<?php echo BASE_URL; ?>/organizer/edit-match.php?id=<?php echo $m['id']; ?>" class="btn btn-secondary btn-sm">Edit</a>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php endforeach; ?>
<?php endif; ?>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
