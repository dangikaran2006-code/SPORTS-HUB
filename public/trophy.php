<?php
/**
 * SportsHub - Overall Department Championship Trophy & Leaderboard
 * Aggregates points earned across ALL sports to crown the Overall College Department Champion!
 */
$currentPage = 'trophy';
$pageTitle   = 'Overall Department Championship Trophy 2026';

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/department-helper.php';

$championship = DepartmentService::getMasterChampionship();
$standings    = DepartmentService::getOverallTrophyStandings();

include_once __DIR__ . '/../includes/public-header.php';
?>

<!-- Header Banner -->
<div class="dashboard-header">
  <div class="dashboard-title-group">
    <div style="display:flex; align-items:center; gap:8px; margin-bottom:6px;">
      <span class="status-badge badge-active">🏆 MASTER CHAMPIONSHIP 2026</span>
      <span class="status-badge badge-scheduled"><?php echo htmlspecialchars($championship['college_name']); ?></span>
    </div>
    <h1>🏆 Overall Department Championship Trophy</h1>
    <p>Accumulated points leaderboard across all 10 sports events (Cricket, Football, Kabaddi, Basketball, Volleyball, Badminton, Athletics, Chess)</p>
  </div>
</div>

<!-- Podium Display Card (Top 3 Departments) -->
<div style="display:grid; grid-template-columns:repeat(3, 1fr); gap:18px; margin-bottom:28px;">
  <!-- Rank 2: Silver -->
  <div class="card" style="border-color:rgba(148, 163, 184, 0.4); text-align:center; position:relative; overflow:hidden;">
    <div style="font-size:2.5rem; margin-bottom:8px;">🥈</div>
    <span style="font-size:0.75rem; background:rgba(148, 163, 184, 0.2); color:#cbd5e1; padding:4px 10px; border-radius:12px; font-weight:700;">2ND PLACE &bull; RUNNER UP</span>
    <h3 style="font-size:1.3rem; margin-top:10px; color:var(--text-main);"><?php echo htmlspecialchars($standings[1]['name'] ?? 'Computer'); ?></h3>
    <div style="font-size:2.4rem; font-weight:800; color:var(--accent-blue); margin-top:6px;">
      <?php echo $standings[1]['total_points'] ?? 0; ?> <span style="font-size:1rem; font-weight:600;">PTS</span>
    </div>
    <div style="font-size:0.85rem; color:var(--text-muted); margin-top:8px;">
      🥇 <?php echo $standings[1]['gold_medals'] ?? 0; ?> Gold &bull; 🥈 <?php echo $standings[1]['silver_medals'] ?? 0; ?> Silver &bull; 🥉 <?php echo $standings[1]['bronze_medals'] ?? 0; ?> Bronze
    </div>
  </div>

  <!-- Rank 1: Gold Champion -->
  <div class="card" style="border-color:rgba(0, 230, 118, 0.6); text-align:center; position:relative; overflow:hidden; background:linear-gradient(180deg, rgba(0,230,118,0.1) 0%, var(--bg-card) 100%); transform:scale(1.03);">
    <div style="font-size:3rem; margin-bottom:6px;">🥇</div>
    <span style="font-size:0.8rem; background:var(--accent-green-bg); color:var(--accent-green); padding:4px 12px; border-radius:12px; font-weight:800; border:1px solid rgba(0,230,118,0.4);">🏆 OVERALL CHAMPION</span>
    <h2 style="font-size:1.5rem; margin-top:10px; color:var(--accent-green);"><?php echo htmlspecialchars($standings[0]['name'] ?? 'Mechanical'); ?></h2>
    <div style="font-size:3rem; font-weight:800; color:var(--accent-green); margin-top:6px; line-height:1;">
      <?php echo $standings[0]['total_points'] ?? 0; ?> <span style="font-size:1.1rem; font-weight:600;">PTS</span>
    </div>
    <div style="font-size:0.85rem; color:var(--text-main); font-weight:600; margin-top:8px;">
      🥇 <?php echo $standings[0]['gold_medals'] ?? 0; ?> Gold &bull; 🥈 <?php echo $standings[0]['silver_medals'] ?? 0; ?> Silver &bull; 🥉 <?php echo $standings[0]['bronze_medals'] ?? 0; ?> Bronze
    </div>
  </div>

  <!-- Rank 3: Bronze -->
  <div class="card" style="border-color:rgba(245, 158, 11, 0.4); text-align:center; position:relative; overflow:hidden;">
    <div style="font-size:2.5rem; margin-bottom:8px;">🥉</div>
    <span style="font-size:0.75rem; background:rgba(245, 158, 11, 0.2); color:#fbbf24; padding:4px 10px; border-radius:12px; font-weight:700;">3RD PLACE</span>
    <h3 style="font-size:1.3rem; margin-top:10px; color:var(--text-main);"><?php echo htmlspecialchars($standings[2]['name'] ?? 'Civil'); ?></h3>
    <div style="font-size:2.4rem; font-weight:800; color:var(--accent-amber); margin-top:6px;">
      <?php echo $standings[2]['total_points'] ?? 0; ?> <span style="font-size:1rem; font-weight:600;">PTS</span>
    </div>
    <div style="font-size:0.85rem; color:var(--text-muted); margin-top:8px;">
      🥇 <?php echo $standings[2]['gold_medals'] ?? 0; ?> Gold &bull; 🥈 <?php echo $standings[2]['silver_medals'] ?? 0; ?> Silver &bull; 🥉 <?php echo $standings[2]['bronze_medals'] ?? 0; ?> Bronze
    </div>
  </div>
</div>

<!-- Complete Department Leaderboard Table -->
<div class="card">
  <div class="section-header" style="margin-bottom:16px;">
    <h2>Official Department Standings & Points Tally</h2>
    <span style="font-size:0.8rem; color:var(--text-muted);">Points updated dynamically from finalized sport events</span>
  </div>

  <div class="table-responsive">
    <table class="sports-table">
      <thead>
        <tr>
          <th>Rank</th>
          <th>Department Name</th>
          <th>Code</th>
          <th>Gold 🥇</th>
          <th>Silver 🥈</th>
          <th>Bronze 🥉</th>
          <th>Sport Performance Breakdown</th>
          <th>Total Trophy Points</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($standings as $st): ?>
          <tr>
            <td>
              <strong style="font-size:1.1rem; color:<?php echo $st['rank'] === 1 ? 'var(--accent-green)' : ($st['rank'] === 2 ? 'var(--accent-blue)' : ($st['rank'] === 3 ? 'var(--accent-amber)' : 'var(--text-dim)')); ?>;">
                #<?php echo $st['rank']; ?>
              </strong>
            </td>
            <td>
              <div style="display:flex; align-items:center; gap:10px;">
                <div class="team-badge-circle" style="width:32px;height:32px;font-size:0.8rem; font-weight:800; background:var(--bg-input); color:<?php echo $st['color_code']; ?>; border:1px solid <?php echo $st['color_code']; ?>;">
                  <?php echo htmlspecialchars($st['short_code']); ?>
                </div>
                <div>
                  <strong style="color:var(--text-main); display:block;"><?php echo htmlspecialchars($st['name']); ?></strong>
                  <span style="font-size:0.75rem; color:var(--accent-green); font-weight:700;"><?php echo $st['medal_badge']; ?></span>
                </div>
              </div>
            </td>
            <td><code><?php echo htmlspecialchars($st['short_code']); ?></code></td>
            <td><strong style="color:#fbbf24; font-size:1.05rem;"><?php echo $st['gold_medals']; ?></strong></td>
            <td><strong style="color:#cbd5e1; font-size:1.05rem;"><?php echo $st['silver_medals']; ?></strong></td>
            <td><strong style="color:#f97316; font-size:1.05rem;"><?php echo $st['bronze_medals']; ?></strong></td>
            <td>
              <div style="display:flex; gap:6px; flex-wrap:wrap;">
                <?php foreach ($st['sport_breakdown'] as $sportName => $sPts): ?>
                  <span style="background:var(--bg-input); padding:3px 8px; border-radius:4px; font-size:0.75rem; color:var(--text-muted); border:1px solid var(--border-subtle);">
                    <?php echo htmlspecialchars($sportName); ?>: <strong style="color:var(--text-main);"><?php echo $sPts; ?>pt</strong>
                  </span>
                <?php endforeach; ?>
              </div>
            </td>
            <td>
              <strong style="font-size:1.3rem; color:var(--accent-green); font-family:var(--font-heading);">
                <?php echo $st['total_points']; ?> PTS
              </strong>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include_once __DIR__ . '/../includes/public-footer.php'; ?>
