<?php
/**
 * SportsHub - Public Sport Detail View
 */
$currentPage = 'sports';

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';

$db = getDB();
$sportSlug = trim($_GET['slug'] ?? 'cricket');
$sports = $db->getSports();

$currentSport = null;
foreach ($sports as $s) {
    if (strtolower($s['slug'] ?? '') === strtolower($sportSlug) || strtolower($s['name'] ?? '') === strtolower($sportSlug)) {
        $currentSport = $s;
        break;
    }
}

if (!$currentSport && !empty($sports)) {
    $currentSport = $sports[0];
}

$pageTitle = htmlspecialchars($currentSport['name'] ?? 'Sport Details');
$allMatches = $db->getMatches('all');
$sportMatches = array_filter($allMatches, function($m) use ($currentSport) {
    return strtolower($m['sport_name'] ?? '') === strtolower($currentSport['name'] ?? '');
});

$liveMatches = array_filter($sportMatches, function($m) { return strtolower($m['status']) === 'live'; });
$upcomingMatches = array_filter($sportMatches, function($m) { return strtolower($m['status']) === 'scheduled' || strtolower($m['status']) === 'upcoming'; });
$completedMatches = array_filter($sportMatches, function($m) { return strtolower($m['status']) === 'completed'; });

include_once __DIR__ . '/../includes/public-header.php';
?>

<div style="margin-bottom: 20px;">
  <a href="<?php echo BASE_URL; ?>/public/sports.php" class="btn btn-secondary btn-sm">← Back to Sports Directory</a>
</div>

<div class="card" style="margin-bottom: 24px; background: linear-gradient(135deg, rgba(14, 21, 38, 0.95), rgba(10, 15, 26, 0.95)); border: 1px solid var(--border-subtle); padding: 24px;">
  <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px;">
    <div>
      <span class="badge" style="background: rgba(0, 230, 118, 0.15); color: var(--accent-green); font-weight: 700; margin-bottom: 8px; display: inline-block;">
        CHAMPIONSHIP SPORT
      </span>
      <h1 style="font-size: 2rem; font-weight: 800; color: #fff; margin-bottom: 4px;">
        <?php echo htmlspecialchars($currentSport['name'] ?? 'Sport'); ?>
      </h1>
      <p style="color: var(--text-muted); font-size: 0.92rem; margin: 0;">
        Category: <strong>Open / Inter-Department</strong> &bull; Total Fixtures: <strong><?php echo count($sportMatches); ?> Matches</strong>
      </p>
    </div>

    <div>
      <a href="<?php echo BASE_URL; ?>/public/points-table.php" class="btn btn-primary" style="padding: 10px 20px;">
        📊 View Sport Standings
      </a>
    </div>
  </div>
</div>

<!-- Live Matches Stream if any -->
<?php if (!empty($liveMatches)): ?>
  <div style="margin-bottom: 24px;">
    <h2 style="font-size: 1.2rem; color: var(--accent-red); margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
      <span style="width: 10px; height: 10px; background: #ef4444; border-radius: 50%; display: inline-block; animation: pulse 1.5s infinite;"></span>
      🔴 Currently Live <?php echo htmlspecialchars($currentSport['name']); ?> Matches
    </h2>
    <?php foreach ($liveMatches as $lm): ?>
      <div class="card" style="border-color: rgba(239, 68, 68, 0.4); margin-bottom: 12px;">
        <div style="display: flex; justify-content: space-between; align-items: center;">
          <div style="font-weight: 700; color: #fff; font-size: 1.1rem;">
            <?php echo htmlspecialchars($lm['team_a_name']); ?> vs <?php echo htmlspecialchars($lm['team_b_name']); ?>
          </div>
          <a href="<?php echo BASE_URL; ?>/public/live-score.php?match_id=<?php echo $lm['id']; ?>" class="btn btn-primary btn-sm" style="background: var(--accent-red); border: none;">
            Watch Live →
          </a>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<!-- Upcoming Fixtures for this sport -->
<div class="card" style="margin-bottom: 24px;">
  <div class="section-header" style="margin-bottom: 14px;">
    <h2>📅 Upcoming <?php echo htmlspecialchars($currentSport['name']); ?> Fixtures</h2>
  </div>
  <div class="table-responsive">
    <table class="sports-table">
      <thead>
        <tr>
          <th>Round</th>
          <th>Match Fixture</th>
          <th>Date & Time</th>
          <th>Venue</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($upcomingMatches)): ?>
          <tr><td colspan="5" style="text-align: center; color: var(--text-muted); padding: 24px;">No upcoming fixtures for this sport.</td></tr>
        <?php else: ?>
          <?php foreach ($upcomingMatches as $um): ?>
            <tr>
              <td><span style="font-weight: 700; color: var(--accent-amber);"><?php echo htmlspecialchars($um['tournament_name']); ?></span></td>
              <td><strong style="color: #fff;"><?php echo htmlspecialchars($um['team_a_name']); ?> vs <?php echo htmlspecialchars($um['team_b_name']); ?></strong></td>
              <td><?php echo date('M d, Y', strtotime($um['match_date'])); ?> @ <?php echo htmlspecialchars($um['start_time']); ?></td>
              <td>📍 <?php echo htmlspecialchars($um['venue_name']); ?></td>
              <td><span class="badge" style="background: rgba(245, 158, 11, 0.15); color: var(--accent-amber);">UPCOMING</span></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Completed Results for this sport -->
<div class="card">
  <div class="section-header" style="margin-bottom: 14px;">
    <h2>🏁 Finalized <?php echo htmlspecialchars($currentSport['name']); ?> Results</h2>
  </div>
  <div class="table-responsive">
    <table class="sports-table">
      <thead>
        <tr>
          <th>Event</th>
          <th>Match Outcome</th>
          <th>Winner</th>
          <th>Date</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($completedMatches)): ?>
          <tr><td colspan="4" style="text-align: center; color: var(--text-muted); padding: 24px;">No completed results yet for this sport.</td></tr>
        <?php else: ?>
          <?php foreach ($completedMatches as $cm): ?>
            <tr>
              <td><strong style="color: #fff;"><?php echo htmlspecialchars($cm['tournament_name']); ?></strong></td>
              <td><?php echo htmlspecialchars($cm['team_a_name']); ?> (<?php echo $cm['score_a']; ?>) vs <?php echo htmlspecialchars($cm['team_b_name']); ?> (<?php echo $cm['score_b']; ?>)</td>
              <td><span style="color: #ffd700; font-weight: 700;">🥇 <?php echo htmlspecialchars($cm['winner_team_name'] ?? $cm['team_a_name']); ?></span></td>
              <td><?php echo date('M d, Y', strtotime($cm['match_date'])); ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include_once __DIR__ . '/../includes/public-footer.php'; ?>
