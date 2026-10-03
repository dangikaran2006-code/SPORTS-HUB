<?php
/**
 * SportsHub - Public Upcoming Matches Page
 */
$currentPage = 'upcoming';
$pageTitle = 'Upcoming Championship Matches';

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';

$db = getDB();
$allMatches = $db->getMatches('all');
$upcomingMatches = array_filter($allMatches, function($m) {
    return strtolower($m['status']) === 'scheduled' || strtolower($m['status']) === 'upcoming';
});

include_once __DIR__ . '/../includes/public-header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
  <div>
    <h1 style="font-size: 1.8rem; font-weight: 800; color: #fff; margin-bottom: 4px;">📅 Upcoming Matches</h1>
    <p style="color: var(--text-muted); font-size: 0.9rem;">Scheduled inter-department sports fixtures and match timings across all grounds.</p>
  </div>
  <a href="<?php echo BASE_URL; ?>/public/live-score.php" class="btn btn-secondary">🔴 Check Live Scores</a>
</div>

<div style="background: var(--bg-card); border: 1px solid var(--border-subtle); border-radius: var(--radius-lg); overflow: hidden;">
  <table class="sports-table" style="margin: 0;">
    <thead>
      <tr>
        <th>Sport</th>
        <th>Match / Event</th>
        <th>Department Teams</th>
        <th>Date & Start Time</th>
        <th>Venue</th>
        <th>Status</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($upcomingMatches)): ?>
        <tr>
          <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 32px;">
            No upcoming matches currently scheduled.
          </td>
        </tr>
      <?php else: ?>
        <?php foreach ($upcomingMatches as $m): ?>
          <tr>
            <td>
              <span class="badge" style="background: rgba(0, 230, 118, 0.15); color: var(--accent-green); font-weight: 700;">
                <?php echo htmlspecialchars($m['sport_name']); ?>
              </span>
            </td>
            <td>
              <div style="font-weight: 700; color: #fff;"><?php echo htmlspecialchars($m['tournament_name']); ?></div>
              <div style="font-size: 0.78rem; color: var(--text-dim);">Stage: Round 1 / Group Stage</div>
            </td>
            <td>
              <span style="font-weight: 700; color: #fff;"><?php echo htmlspecialchars($m['team_a_name']); ?></span>
              <span style="color: var(--accent-green); font-weight: 700;"> vs </span>
              <span style="font-weight: 700; color: #fff;"><?php echo htmlspecialchars($m['team_b_name']); ?></span>
            </td>
            <td>
              <div style="font-weight: 600; color: #fff;"><?php echo date('D, M d, Y', strtotime($m['match_date'])); ?></div>
              <div style="font-size: 0.78rem; color: var(--text-muted);"><?php echo htmlspecialchars($m['start_time']); ?> IST</div>
            </td>
            <td>
              <div style="color: var(--text-main); font-weight: 600;">📍 <?php echo htmlspecialchars($m['venue_name']); ?></div>
            </td>
            <td>
              <span class="badge" style="background: rgba(245, 158, 11, 0.15); color: var(--accent-amber); font-weight: 700;">
                UPCOMING
              </span>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php include_once __DIR__ . '/../includes/public-footer.php'; ?>
