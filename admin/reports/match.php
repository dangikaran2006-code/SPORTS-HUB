<?php
/**
 * SportsHub - Admin Match / Event Detailed Audit Report
 */
$currentPage = 'statistics';
$pageTitle   = 'Match Audit Report';

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/match-functions.php';
require_once __DIR__ . '/../../includes/scoring-helper.php';
require_once __DIR__ . '/../../includes/report-helper.php';

requireAdminAccess();

$matchId = intval($_GET['id'] ?? 1);
$match = getMatchById($matchId);
$liveState = ScoringService::getLiveState($matchId);

// CSV Export Handler
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $csvHeaders = ['Match ID', 'Sport', 'Team A', 'Team B', 'Score A', 'Score B', 'Winner', 'Scheduled Date', 'Venue', 'Official'];
    $csvRows = [[
        $match['id'],
        $match['sport_name'],
        $match['team_a_name'],
        $match['team_b_name'],
        $match['score_a'] ?? '-',
        $match['score_b'] ?? '-',
        $match['winner_name'] ?? 'Draw/Pending',
        $match['scheduled_date'],
        $match['venue_name'] ?? 'TBD',
        $match['official_name'] ?? 'Unassigned'
    ]];
    exportCsvReport("Match_{$matchId}_Report.csv", $csvHeaders, $csvRows);
}

include_once __DIR__ . '/../../includes/header.php';
?>

<?php echo renderPrintHeader("Official Match Report - Match #{$matchId}"); ?>

<div class="dashboard-header no-print">
  <div class="dashboard-title-group">
    <h1>Official Match Audit Report: Match #<?php echo $matchId; ?></h1>
    <p>Detailed event timeline, scorecards, officials, and final match results.</p>
  </div>
  <div class="quick-actions-bar" style="display:flex; gap:10px;">
    <a href="?id=<?php echo $matchId; ?>&export=csv" class="btn btn-secondary">
      📥 Export CSV
    </a>
    <button onclick="window.print()" class="btn btn-primary">
      🖨️ Print Match Report
    </button>
  </div>
</div>

<!-- Match Summary Header Card -->
<div class="card" style="margin-bottom: 24px; border-left: 4px solid var(--accent-green);">
  <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:16px;">
    <div>
      <div style="display:flex; align-items:center; gap:10px; margin-bottom:8px;">
        <?php echo getSportBadge($match['sport_name']); ?>
        <?php echo getStatusBadge($match['status']); ?>
      </div>
      <h1 style="font-size:1.8rem; font-weight:800; color:#fff; margin:0 0 6px 0;">
        <?php echo htmlspecialchars($match['team_a_name']); ?> <span style="color:var(--text-muted); font-size:1.2rem;">vs</span> <?php echo htmlspecialchars($match['team_b_name']); ?>
      </h1>
      <div style="font-size:0.88rem; color:var(--text-muted);">
        📅 Scheduled Date: <strong><?php echo date('M d, Y', strtotime($match['scheduled_date'])); ?> <?php echo date('h:i A', strtotime($match['scheduled_time'])); ?></strong>
      </div>
    </div>

    <div style="text-align:right;">
      <div style="font-size:1.8rem; font-weight:800; color:var(--accent-green);">
        <?php echo htmlspecialchars($match['score_a'] ?? '-'); ?> - <?php echo htmlspecialchars($match['score_b'] ?? '-'); ?>
      </div>
      <div style="font-size:0.85rem; color:#ffd700; font-weight:700; margin-top:4px;">
        🏆 Winner: <?php echo htmlspecialchars($match['winner_name'] ?? 'Pending / Draw'); ?>
      </div>
    </div>
  </div>
</div>

<!-- Match Operational Details Grid -->
<div class="grid grid-2" style="gap:20px; margin-bottom:24px;">
  <div class="card">
    <h3 style="font-size:1.1rem; color:var(--text-main); margin-bottom:12px; font-weight:700;">Venue & Official Information</h3>
    <div style="font-size:0.9rem; line-height:1.8;">
      <div><strong style="color:#fff;">Venue Name:</strong> <?php echo htmlspecialchars($match['venue_name'] ?? 'Main Sports Complex'); ?></div>
      <div><strong style="color:#fff;">Location:</strong> <?php echo htmlspecialchars($match['venue_location'] ?? 'Campus Grounds'); ?></div>
      <div><strong style="color:#fff;">Assigned Official:</strong> <?php echo htmlspecialchars($match['official_name'] ?? 'Official Referee'); ?> (<?php echo htmlspecialchars($match['official_role'] ?? 'Umpire'); ?>)</div>
      <div><strong style="color:#fff;">Round / Phase:</strong> <?php echo htmlspecialchars($match['round_name'] ?? 'League Stage'); ?></div>
    </div>
  </div>

  <div class="card">
    <h3 style="font-size:1.1rem; color:var(--text-main); margin-bottom:12px; font-weight:700;">Result & Status Summary</h3>
    <div style="font-size:0.9rem; line-height:1.8;">
      <div><strong style="color:#fff;">Result Summary:</strong> <?php echo htmlspecialchars($match['result_summary'] ?? 'Match Completed'); ?></div>
      <div><strong style="color:#fff;">Current Period / Overs:</strong> <?php echo htmlspecialchars($match['current_period'] ?? 'Finished'); ?></div>
      <div><strong style="color:#fff;">Tournament:</strong> <?php echo htmlspecialchars($match['tournament_name'] ?? 'Championship 2026'); ?></div>
    </div>
  </div>
</div>

<!-- Recorded Match Events Timeline -->
<div class="card">
  <div class="section-header" style="margin-bottom:16px;">
    <h2>Match Events Timeline</h2>
  </div>

  <?php if (!empty($liveState['events'])): ?>
    <div class="table-responsive">
      <table class="sports-table">
        <thead>
          <tr>
            <th>Time</th>
            <th>Event Type</th>
            <th>Value / Points</th>
            <th>Event Details</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($liveState['events'] as $ev): ?>
            <tr>
              <td style="font-size:0.85rem; color:var(--text-muted);"><?php echo htmlspecialchars($ev['event_time'] ?? '-'); ?></td>
              <td><strong style="color:#fff;"><?php echo strtoupper(htmlspecialchars($ev['event_type'])); ?></strong></td>
              <td><strong style="color:var(--accent-green);"><?php echo (int)($ev['event_value'] ?? 0); ?></strong></td>
              <td style="font-size:0.85rem; color:var(--text-muted);">
                <?php 
                  if (is_array($ev['event_data'])) {
                      echo htmlspecialchars(json_encode($ev['event_data']));
                  } else {
                      echo htmlspecialchars($ev['event_data'] ?? '-');
                  }
                ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <p style="color:var(--text-muted); font-size:0.9rem;">No granular live scoring events logged for this match.</p>
  <?php endif; ?>
</div>

<?php include_once __DIR__ . '/../../includes/footer.php'; ?>
