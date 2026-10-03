<?php
/**
 * SportsHub - Public Match Results Page
 */
$currentPage = 'results';
$pageTitle = 'Finalized Match Results';

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';

$db = getDB();
$allMatches = $db->getMatches('all');
$completedMatches = array_filter($allMatches, function($m) {
    return strtolower($m['status']) === 'completed' || strtolower($m['status']) === 'finished';
});

include_once __DIR__ . '/../includes/public-header.php';
?>

<div style="margin-bottom: 28px;">
  <h1 style="font-size: 1.8rem; font-weight: 800; color: #fff; margin-bottom: 4px;">🏁 Finalized Championship Results</h1>
  <p style="color: var(--text-muted); font-size: 0.9rem;">Official completed event outcomes and department winners across all sports.</p>
</div>

<div style="background: var(--bg-card); border: 1px solid var(--border-subtle); border-radius: var(--radius-lg); overflow: hidden;">
  <table class="sports-table" style="margin: 0;">
    <thead>
      <tr>
        <th>Sport</th>
        <th>Match Teams</th>
        <th>Final Scores / Outcome</th>
        <th>Department Winner</th>
        <th>Venue & Date</th>
        <th>Trophy Points</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($completedMatches)): ?>
        <tr>
          <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 32px;">
            No finalized match results available yet.
          </td>
        </tr>
      <?php else: ?>
        <?php foreach ($completedMatches as $m): ?>
          <tr>
            <td>
              <span class="badge" style="background: rgba(0, 230, 118, 0.15); color: var(--accent-green); font-weight: 700;">
                <?php echo htmlspecialchars($m['sport_name']); ?>
              </span>
            </td>
            <td>
              <div style="font-weight: 700; color: #fff;">
                <?php echo htmlspecialchars($m['team_a_name']); ?> <span style="color: var(--accent-green);">vs</span> <?php echo htmlspecialchars($m['team_b_name']); ?>
              </div>
            </td>
            <td>
              <div style="font-weight: 800; font-size: 1.05rem; color: var(--accent-green);">
                <?php echo htmlspecialchars($m['score_a']); ?> - <?php echo htmlspecialchars($m['score_b']); ?>
              </div>
              <div style="font-size: 0.78rem; color: var(--text-muted);"><?php echo htmlspecialchars($m['result_summary']); ?></div>
            </td>
            <td>
              <span style="color: var(--accent-gold); font-weight: 800; font-size: 0.95rem;">
                🥇 <?php echo htmlspecialchars($m['winner_team_name'] ?? $m['team_a_name']); ?>
              </span>
            </td>
            <td>
              <div>📍 <?php echo htmlspecialchars($m['venue_name']); ?></div>
              <div style="font-size: 0.78rem; color: var(--text-dim);"><?php echo date('M d, Y', strtotime($m['match_date'])); ?></div>
            </td>
            <td>
              <span class="badge" style="background: rgba(255, 215, 0, 0.15); color: var(--accent-gold); font-weight: 800;">
                +10 PTS
              </span>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php include_once __DIR__ . '/../includes/public-footer.php'; ?>
