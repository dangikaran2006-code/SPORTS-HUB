<?php
/**
 * SportsHub - Dynamic Standings & Points Table View
 */
$currentPage = 'points-table';
$pageTitle   = 'Tournament Points Table & Standings';

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/statistics-helper.php';

$tournamentId = intval($_GET['tournament_id'] ?? 0);
$sportId      = intval($_GET['sport_id'] ?? 0);

$standings = StatisticsService::getPointsTable($tournamentId, $sportId);

// Group Standings by Tournament Name
$groupedStandings = [];
foreach ($standings as $row) {
    $tName = $row['tournament_name'] ?? 'Tournament Standings';
    if (!isset($groupedStandings[$tName])) {
        $groupedStandings[$tName] = [
            'sport_name' => $row['sport_name'] ?? 'Cricket',
            'rows'       => [],
        ];
    }
    $groupedStandings[$tName]['rows'][] = $row;
}

include_once __DIR__ . '/../includes/public-header.php';
?>

<div class="dashboard-header">
  <div class="dashboard-title-group">
    <h1>Points Table & Standings Console</h1>
    <p>Automatic calculation engine for Net Run Rate (NRR), Goal Difference (GD), Points, and Form guides.</p>
  </div>
</div>

<?php if (!empty($groupedStandings)): ?>
  <?php foreach ($groupedStandings as $tName => $tGroup): ?>
    <div class="card" style="margin-bottom:28px;">
      <div class="section-header">
        <div style="display:flex; align-items:center; gap:12px;">
          <?php echo getSportBadge($tGroup['sport_name']); ?>
          <h2><?php echo htmlspecialchars($tName); ?> Standings</h2>
        </div>
      </div>

      <div class="table-responsive">
        <table class="sports-table">
          <thead>
            <tr>
              <th>Pos</th>
              <th>Team Name</th>
              <th>Played</th>
              <th>Won</th>
              <th>Lost</th>
              <th>Draw</th>
              <th>For</th>
              <th>Against</th>
              <th><?php echo strtolower($tGroup['sport_name']) === 'cricket' ? 'NRR' : 'Diff'; ?></th>
              <th>Points</th>
              <th>Form</th>
            </tr>
          </thead>
          <tbody>
            <?php $pos = 1; foreach ($tGroup['rows'] as $st): ?>
              <tr>
                <td><strong style="color:var(--accent-green); font-size:1.1rem;"><?php echo $pos++; ?></strong></td>
                <td>
                  <div style="display:flex; align-items:center; gap:10px;">
                    <div class="team-badge-circle" style="width:28px;height:28px;font-size:0.75rem; background:var(--bg-input);">
                      <?php echo htmlspecialchars($st['short_name'] ?? 'TM'); ?>
                    </div>
                    <strong style="color:var(--text-main);"><?php echo htmlspecialchars($st['team_name']); ?></strong>
                  </div>
                </td>
                <td><?php echo $st['played']; ?></td>
                <td><?php echo $st['won']; ?></td>
                <td><?php echo $st['lost']; ?></td>
                <td><?php echo $st['drawn']; ?></td>
                <td><?php echo $st['score_for'] ?? 0; ?></td>
                <td><?php echo $st['score_against'] ?? 0; ?></td>
                <td>
                  <?php if (strtolower($tGroup['sport_name']) === 'cricket'): ?>
                    <strong style="color:<?php echo ($st['net_run_rate'] >= 0) ? 'var(--accent-green)' : 'var(--accent-red)'; ?>;">
                      <?php echo sprintf("%+.3f", $st['net_run_rate']); ?>
                    </strong>
                  <?php else: ?>
                    <strong style="color:<?php echo (($st['score_difference'] ?? 0) >= 0) ? 'var(--accent-green)' : 'var(--accent-red)'; ?>;">
                      <?php echo sprintf("%+d", $st['score_difference'] ?? 0); ?>
                    </strong>
                  <?php endif; ?>
                </td>
                <td><strong style="color:var(--accent-green); font-size:1.1rem;"><?php echo $st['points']; ?></strong></td>
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
  <?php endforeach; ?>
<?php else: ?>
  <div class="card" style="text-align:center; padding:32px;">
    <p style="color:var(--text-muted);">No standings data recorded for selected filters.</p>
  </div>
<?php endif; ?>

<?php include_once __DIR__ . '/../includes/public-footer.php'; ?>
