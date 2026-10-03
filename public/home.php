<?php
/**
 * SportsHub - Public Spectator Homepage
 */
$currentPage = 'home';
$pageTitle = 'College Championship Home';

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/department-helper.php';
require_once __DIR__ . '/../includes/scoring-helper.php';

$db = getDB();
$championship = DepartmentService::getMasterChampionship();
$overallStandings = DepartmentService::getOverallTrophyStandings();
$liveMatches = $db->getMatches('live');
$allMatches = $db->getMatches('all');
$upcomingMatches = array_filter($allMatches, function($m) {
    return strtolower($m['status']) === 'scheduled' || strtolower($m['status']) === 'upcoming';
});
$completedMatches = array_filter($allMatches, function($m) {
    return strtolower($m['status']) === 'completed' || strtolower($m['status']) === 'finished';
});

include_once __DIR__ . '/../includes/public-header.php';
?>

<!-- Public Championship Banner -->
<div style="background: linear-gradient(135deg, rgba(10, 15, 26, 0.95), rgba(7, 11, 20, 0.95)), url('<?php echo BASE_URL; ?>/assets/images/stadium-bg.jpg') center/cover; border: 1px solid var(--border-subtle); padding: 36px 28px; border-radius: var(--radius-lg); margin-bottom: 32px; box-shadow: 0 10px 30px rgba(0,0,0,0.5);">
  <div style="max-width: 800px;">
    <div style="display: flex; gap: 8px; margin-bottom: 12px; flex-wrap: wrap;">
      <span style="background: rgba(0, 230, 118, 0.15); color: var(--accent-green); border: 1px solid rgba(0, 230, 118, 0.3); padding: 4px 12px; font-weight: 700; font-size: 0.78rem; border-radius: 4px; text-transform: uppercase;">
        COLLEGE SPORTS CHAMPIONSHIP 2026
      </span>
      <span style="background: rgba(245, 158, 11, 0.15); color: var(--accent-amber); border: 1px solid rgba(245, 158, 11, 0.3); padding: 4px 12px; font-weight: 700; font-size: 0.78rem; border-radius: 4px; text-transform: uppercase;">
        OFFICIAL LEAGUE PORTAL
      </span>
    </div>
    <h1 style="font-size: 2.25rem; font-weight: 800; color: #fff; line-height: 1.2; margin-bottom: 10px;">
      <?php echo htmlspecialchars($championship['name']); ?>
    </h1>
    <p style="color: var(--text-muted); font-size: 1rem; line-height: 1.6; margin-bottom: 24px;">
      Welcome to the official spectators hub for <?php echo htmlspecialchars($championship['college_name']); ?>. Track real-time department scores, gold medal counts, and overall championship standings across 10 sports events.
    </p>

    <div style="display: flex; gap: 12px; flex-wrap: wrap;">
      <a href="<?php echo BASE_URL; ?>/public/trophy.php" class="btn btn-primary" style="background: linear-gradient(135deg, #ffd700, #ff9800); color: #000; font-weight: 800; padding: 12px 24px;">
        🏆 View Department Trophy Standings
      </a>
      <a href="<?php echo BASE_URL; ?>/public/live-score.php" class="btn btn-secondary" style="padding: 12px 20px;">
        🔴 Live Matches Engine
      </a>
    </div>
  </div>
</div>

<!-- Main Home Grid -->
<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 28px;">

  <!-- Left Main Column -->
  <div>

    <!-- Section: Live Now -->
    <div style="margin-bottom: 32px;">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
        <h2 style="font-size: 1.3rem; font-weight: 800; color: #fff; display: flex; align-items: center; gap: 8px;">
          <span style="width: 10px; height: 10px; background: #ef4444; border-radius: 50%; display: inline-block; animation: pulse 1.5s infinite;"></span>
          🔴 Live Matches Now
        </h2>
        <a href="<?php echo BASE_URL; ?>/public/live-score.php" style="color: var(--accent-green); font-size: 0.88rem; font-weight: 600; text-decoration: none;">View All Live →</a>
      </div>

      <?php if (empty($liveMatches)): ?>
        <div style="background: var(--bg-card); padding: 24px; border-radius: var(--radius-md); border: 1px dashed var(--border-subtle); color: var(--text-muted); text-align: center; font-size: 0.92rem;">
          No matches currently live. Check today's schedule and upcoming matches below!
        </div>
      <?php else: ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px;">
          <?php foreach ($liveMatches as $match): ?>
            <?php
              $scoreA = $match['score_a'] ?? $match['score_team_a'] ?? '-';
              $scoreB = $match['score_b'] ?? $match['score_team_b'] ?? '-';
              $venueName = $match['venue_name'] ?? $match['venue'] ?? 'Sports Arena';

              if (class_exists('ScoringService') && isset($match['id'])) {
                  $liveState = ScoringService::getLiveState($match['id']);
                  if ($liveState && !empty($liveState['live_state'])) {
                      $ls = $liveState['live_state'];
                      if (isset($ls['team_a']['display_score'])) $scoreA = $ls['team_a']['display_score'];
                      elseif (isset($ls['team_a']['score'])) $scoreA = $ls['team_a']['score'];
                      if (isset($ls['team_b']['display_score'])) $scoreB = $ls['team_b']['display_score'];
                      elseif (isset($ls['team_b']['score'])) $scoreB = $ls['team_b']['score'];
                  }
              }
            ?>
            <div style="background: var(--bg-card); border: 1px solid rgba(239, 68, 68, 0.4); padding: 18px; border-radius: var(--radius-md); position: relative;">
              <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                <span style="font-size: 0.78rem; font-weight: 700; color: var(--accent-green); text-transform: uppercase;">
                  <?php echo htmlspecialchars($match['sport_name'] ?? 'Sport'); ?>
                </span>
                <span style="background: rgba(239, 68, 68, 0.15); color: #ef4444; font-size: 0.72rem; font-weight: 700; padding: 2px 8px; border-radius: 4px;">
                  LIVE
                </span>
              </div>

              <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                <div style="font-weight: 800; font-size: 1.05rem; color: #fff;">
                  <?php echo htmlspecialchars($match['team_a_name'] ?? 'Team A'); ?>
                </div>
                <div style="font-size: 1.3rem; font-weight: 800; color: var(--accent-green);">
                  <?php echo htmlspecialchars((string)$scoreA); ?>
                </div>
              </div>

              <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                <div style="font-weight: 800; font-size: 1.05rem; color: #fff;">
                  <?php echo htmlspecialchars($match['team_b_name'] ?? 'Team B'); ?>
                </div>
                <div style="font-size: 1.3rem; font-weight: 800; color: var(--accent-green);">
                  <?php echo htmlspecialchars((string)$scoreB); ?>
                </div>
              </div>

              <div style="font-size: 0.8rem; color: var(--text-muted); border-top: 1px solid var(--border-subtle); padding-top: 8px; margin-top: 8px; display: flex; justify-content: space-between;">
                <span>📍 <?php echo htmlspecialchars((string)$venueName); ?></span>
                <a href="<?php echo BASE_URL; ?>/public/live-score.php?id=<?php echo $match['id']; ?>" style="color: var(--accent-green); font-weight: 600; text-decoration: none;">Watch Live →</a>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- Section: Upcoming Matches -->
    <div style="margin-bottom: 32px;">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
        <h2 style="font-size: 1.3rem; font-weight: 800; color: #fff;">📅 Upcoming Matches</h2>
        <a href="<?php echo BASE_URL; ?>/public/upcoming.php" style="color: var(--accent-green); font-size: 0.88rem; font-weight: 600; text-decoration: none;">Full Fixtures →</a>
      </div>

      <div style="background: var(--bg-card); border: 1px solid var(--border-subtle); border-radius: var(--radius-md); overflow: hidden;">
        <table class="sports-table" style="margin: 0;">
          <thead>
            <tr>
              <th>Sport</th>
              <th>Teams / Match</th>
              <th>Date & Time</th>
              <th>Venue</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach (array_slice($upcomingMatches, 0, 4) as $m): ?>
              <tr>
                <td><strong style="color: var(--accent-green);"><?php echo htmlspecialchars($m['sport_name']); ?></strong></td>
                <td>
                  <span style="font-weight: 700; color: #fff;"><?php echo htmlspecialchars($m['team_a_name']); ?></span>
                  <span style="color: var(--accent-green); font-weight: 700;"> vs </span>
                  <span style="font-weight: 700; color: #fff;"><?php echo htmlspecialchars($m['team_b_name']); ?></span>
                </td>
                <td>
                  <div><?php echo date('M d, Y', strtotime($m['match_date'])); ?></div>
                  <div style="font-size: 0.78rem; color: var(--text-dim);"><?php echo htmlspecialchars($m['start_time']); ?> IST</div>
                </td>
                <td><?php echo htmlspecialchars($m['venue_name']); ?></td>
                <td><span class="badge" style="background: rgba(245, 158, 11, 0.15); color: var(--accent-amber); font-weight: 700;">UPCOMING</span></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Section: Recent Results -->
    <div style="margin-bottom: 32px;">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
        <h2 style="font-size: 1.3rem; font-weight: 800; color: #fff;">🏁 Recent Results</h2>
        <a href="<?php echo BASE_URL; ?>/public/results.php" style="color: var(--accent-green); font-size: 0.88rem; font-weight: 600; text-decoration: none;">View All Results →</a>
      </div>

      <div style="background: var(--bg-card); border: 1px solid var(--border-subtle); border-radius: var(--radius-md); overflow: hidden;">
        <table class="sports-table" style="margin: 0;">
          <thead>
            <tr>
              <th>Sport</th>
              <th>Match Summary</th>
              <th>Winner</th>
              <th>Date</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach (array_slice($completedMatches, 0, 4) as $m): ?>
              <tr>
                <td><strong style="color: var(--text-main);"><?php echo htmlspecialchars($m['sport_name']); ?></strong></td>
                <td>
                  <div style="font-weight: 600; color: #fff;"><?php echo htmlspecialchars($m['team_a_name']); ?> vs <?php echo htmlspecialchars($m['team_b_name']); ?></div>
                  <div style="font-size: 0.78rem; color: var(--text-muted);"><?php echo htmlspecialchars($m['result_summary']); ?></div>
                </td>
                <td><span style="color: var(--accent-gold); font-weight: 700;">🏆 <?php echo htmlspecialchars($m['winner_team_name'] ?? $m['team_a_name']); ?></span></td>
                <td><?php echo date('M d', strtotime($m['match_date'])); ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div> <!-- End Left Column -->

  <!-- Right Sidebar Column -->
  <div>

    <!-- Department Trophy Standings Summary Widget -->
    <div style="background: radial-gradient(circle at top right, rgba(255, 215, 0, 0.08), transparent 70%), var(--bg-card); border: 1px solid rgba(255, 215, 0, 0.3); border-radius: var(--radius-lg); padding: 20px; margin-bottom: 28px;">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
        <h3 style="font-size: 1.1rem; font-weight: 800; color: #ffd700; margin: 0; display: flex; align-items: center; gap: 6px;">
          🏆 DEPARTMENT TROPHY
        </h3>
        <a href="<?php echo BASE_URL; ?>/public/trophy.php" style="font-size: 0.8rem; color: var(--accent-gold); font-weight: 700; text-decoration: none;">Full Leaderboard →</a>
      </div>

      <div style="display: flex; flex-direction: column; gap: 10px;">
        <?php foreach (array_slice($overallStandings, 0, 6) as $index => $dept): 
          $rank = $index + 1;
          $badge = $rank === 1 ? '🥇' : ($rank === 2 ? '🥈' : ($rank === 3 ? '🥉' : '#' . $rank));
          $rowBg = $rank === 1 ? 'background: rgba(255, 215, 0, 0.12); border: 1px solid rgba(255, 215, 0, 0.4);' : 'background: var(--bg-dark-surface); border: 1px solid var(--border-subtle);';
        ?>
          <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px 14px; border-radius: var(--radius-md); <?php echo $rowBg; ?>">
            <div style="display: flex; align-items: center; gap: 12px;">
              <span style="font-size: 1.1rem; font-weight: 800; min-width: 24px; text-align: center; color: <?php echo $rank === 1 ? '#ffd700' : 'var(--text-main)'; ?>;">
                <?php echo $badge; ?>
              </span>
              <div>
                <div style="font-weight: 800; font-size: 0.95rem; color: #fff;">
                  <?php echo htmlspecialchars($dept['short_code']); ?>
                </div>
                <div style="font-size: 0.72rem; color: var(--text-muted);">
                  🥇 <?php echo $dept['golds'] ?? $dept['gold_medals'] ?? 0; ?> | 🥈 <?php echo $dept['silvers'] ?? $dept['silver_medals'] ?? 0; ?> | 🥉 <?php echo $dept['bronzes'] ?? $dept['bronze_medals'] ?? 0; ?>
                </div>
              </div>
            </div>
            <div style="font-size: 1.25rem; font-weight: 800; color: var(--accent-green);">
              <?php echo $dept['total_points'] ?? $dept['points'] ?? 0; ?> <span style="font-size: 0.75rem; font-weight: 500; color: var(--text-dim);">pts</span>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Official Announcements Widget -->
    <div style="background: var(--bg-card); border: 1px solid var(--border-subtle); border-radius: var(--radius-lg); padding: 20px; margin-bottom: 28px;">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
        <h3 style="font-size: 1.05rem; font-weight: 800; color: #fff; margin: 0;">📢 Championship Alerts</h3>
        <a href="<?php echo BASE_URL; ?>/public/announcements.php" style="font-size: 0.8rem; color: var(--accent-green); font-weight: 600; text-decoration: none;">View All</a>
      </div>

      <div style="display: flex; flex-direction: column; gap: 12px;">
        <div style="background: var(--bg-dark-surface); padding: 12px 14px; border-radius: var(--radius-md); border-left: 3px solid var(--accent-green);">
          <div style="font-size: 0.78rem; color: var(--accent-green); font-weight: 700; margin-bottom: 2px;">CRICKET FINAL</div>
          <div style="font-weight: 700; font-size: 0.88rem; color: #fff; margin-bottom: 4px;">Cricket Championship Final starts at 4:00 PM today at Main Ground.</div>
          <div style="font-size: 0.72rem; color: var(--text-dim);">Posted today &bull; Priority High</div>
        </div>

        <div style="background: var(--bg-dark-surface); padding: 12px 14px; border-radius: var(--radius-md); border-left: 3px solid var(--accent-amber);">
          <div style="font-size: 0.78rem; color: var(--accent-amber); font-weight: 700; margin-bottom: 2px;">VENUE CHANGE</div>
          <div style="font-weight: 700; font-size: 0.88rem; color: #fff; margin-bottom: 4px;">Badminton Singles matches moved to Indoor Sports Complex Court 1.</div>
          <div style="font-size: 0.72rem; color: var(--text-dim);">Posted yesterday</div>
        </div>
      </div>
    </div>

  </div> <!-- End Right Sidebar -->

</div> <!-- End Main Home Grid -->

<?php include_once __DIR__ . '/../includes/public-footer.php'; ?>
