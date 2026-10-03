<?php
/**
 * SportsHub - Tournament Detail View
 */
$currentPage = 'tournaments';
$pageTitle = 'Tournament Details';

require_once __DIR__ . '/../includes/database.php';

$db = getDB();
$id = $_GET['id'] ?? 1;
$tournaments = $db->getTournaments();
$tournament = $tournaments[0];

foreach ($tournaments as $t) {
    if ($t['id'] == $id) {
        $tournament = $t;
        break;
    }
}

$matches = $db->getMatches();

include_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-header">
  <div class="dashboard-title-group">
    <div style="display:flex; align-items:center; gap:12px; margin-bottom:8px;">
      <?php echo getSportBadge($tournament['sport_name']); ?>
      <?php echo getStatusBadge($tournament['status']); ?>
    </div>
    <h1><?php echo htmlspecialchars($tournament['name']); ?></h1>
    <p>Venue: <?php echo htmlspecialchars($tournament['venue_name']); ?> &bull; Format: <?php echo htmlspecialchars($tournament['format']); ?></p>
  </div>
  <div class="quick-actions-bar">
    <a href="<?php echo BASE_URL; ?>/admin/tournaments.php" class="btn btn-secondary">
      Back to Tournaments
    </a>
  </div>
</div>

<div class="dashboard-main-grid">
  <div class="grid-left-col">
    <!-- Tournament Overview & Matches -->
    <div class="card" style="margin-bottom:24px;">
      <div class="section-header">
        <h2>Tournament Fixtures & Live Matches</h2>
      </div>

      <div class="table-responsive">
        <table class="sports-table">
          <thead>
            <tr>
              <th>Teams</th>
              <th>Date & Time</th>
              <th>Status</th>
              <th>Score / Summary</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($matches as $m): ?>
              <tr>
                <td>
                  <strong style="color:var(--text-main);"><?php echo htmlspecialchars($m['team_a_name']); ?></strong> vs <strong style="color:var(--text-main);"><?php echo htmlspecialchars($m['team_b_name']); ?></strong>
                </td>
                <td><?php echo date('M d, Y', strtotime($m['match_date'])); ?> @ <?php echo $m['start_time']; ?></td>
                <td><?php echo getStatusBadge($m['status']); ?></td>
                <td style="color:var(--accent-green); font-weight:600;"><?php echo htmlspecialchars($m['result_summary']); ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Official Tournament Standings Table -->
    <?php 
      require_once __DIR__ . '/../includes/statistics-helper.php';
      $pubStandings = StatisticsService::getPointsTable($tournament['id'] ?? 1, $tournament['sport_id'] ?? 1);
    ?>
    <div class="card" style="margin-bottom:24px;">
      <div class="section-header" style="margin-bottom:16px;">
        <h2>Official Tournament Standings</h2>
      </div>
      <div class="table-responsive">
        <table class="sports-table">
          <thead>
            <tr>
              <th>Pos</th>
              <th>Team</th>
              <th>P</th>
              <th>W</th>
              <th>L</th>
              <th>D</th>
              <th>Points</th>
              <th>NRR / Diff</th>
              <th>Form</th>
            </tr>
          </thead>
          <tbody>
            <?php $pPos = 1; foreach ($pubStandings as $st): ?>
              <tr>
                <td><strong style="color:var(--accent-green); font-size:1.05rem;"><?php echo $pPos++; ?></strong></td>
                <td><strong style="color:var(--text-main);"><?php echo htmlspecialchars($st['team_name']); ?></strong></td>
                <td><?php echo $st['played']; ?></td>
                <td><?php echo $st['won']; ?></td>
                <td><?php echo $st['lost']; ?></td>
                <td><?php echo $st['drawn']; ?></td>
                <td><strong style="color:var(--accent-green); font-size:1.05rem;"><?php echo $st['points']; ?></strong></td>
                <td>
                  <?php if (strtolower($tournament['sport_name'] ?? '') === 'cricket'): ?>
                    <strong style="color:<?php echo ($st['net_run_rate'] >= 0) ? 'var(--accent-green)' : 'var(--accent-red)'; ?>;">
                      <?php echo sprintf("%+.3f", $st['net_run_rate']); ?>
                    </strong>
                  <?php else: ?>
                    <strong style="color:<?php echo (($st['score_difference'] ?? 0) >= 0) ? 'var(--accent-green)' : 'var(--accent-red)'; ?>;">
                      <?php echo sprintf("%+d", $st['score_difference'] ?? 0); ?>
                    </strong>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if (!empty($st['form'])): ?>
                    <?php foreach (str_split($st['form']) as $char): ?>
                      <span style="background:<?php echo $char === 'W' ? 'rgba(0,230,118,0.2)' : ($char === 'L' ? 'rgba(239,68,68,0.2)' : 'rgba(245,158,11,0.2)'); ?>; color:<?php echo $char === 'W' ? 'var(--accent-green)' : ($char === 'L' ? 'var(--accent-red)' : 'var(--accent-amber)'); ?>; padding:2px 6px; border-radius:4px; font-weight:700; font-size:0.75rem; margin-right:2px;">
                        <?php echo $char; ?>
                      </span>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <span style="color:var(--text-muted); font-size:0.75rem;">-</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="grid-right-col">
    <div class="card">
      <div class="section-header">
        <h2>Tournament Info</h2>
      </div>
      <div style="display:flex; flex-direction:column; gap:14px; font-size:0.875rem;">
        <div>
          <span style="color:var(--text-muted);">Organizer:</span>
          <strong style="display:block; color:var(--text-main);"><?php echo htmlspecialchars($tournament['organizer_name']); ?></strong>
        </div>
        <div>
          <span style="color:var(--text-muted);">Duration:</span>
          <strong style="display:block; color:var(--text-main);"><?php echo date('M d, Y', strtotime($tournament['start_date'])); ?> - <?php echo date('M d, Y', strtotime($tournament['end_date'])); ?></strong>
        </div>
        <div>
          <span style="color:var(--text-muted);">Participating Teams:</span>
          <strong style="display:block; color:var(--text-main);"><?php echo $tournament['teams_count']; ?> Registered Clubs</strong>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
