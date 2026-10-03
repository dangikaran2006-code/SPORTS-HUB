<?php
/**
 * SportsHub - College Inter-Department Sports Championship Dashboard Overview
 */
$currentPage = 'dashboard';
$pageTitle = 'Dashboard Overview';

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/department-helper.php';

// Enforce admin authority access for dashboard
requireAdminAccess();

$user = currentUser();
$userRole = ucfirst($user['role'] ?? 'Admin');

$db = getDB();
$stats = $db->getDashboardStats();
$tournaments = $db->getTournaments(6);
$liveMatches = $db->getMatches('live');
$allMatches = $db->getMatches('all');

// Master Championship Info & Standings
$championship = DepartmentService::getMasterChampionship();
$overallStandings = DepartmentService::getOverallTrophyStandings();

include_once __DIR__ . '/../includes/header.php';
?>

<!-- Dashboard Header / Championship Banner -->
<div class="dashboard-header" style="background: linear-gradient(135deg, rgba(14, 21, 38, 0.95), rgba(10, 15, 26, 0.95)), url('<?php echo BASE_URL; ?>/assets/images/stadium-bg.jpg') center/cover; border: 1px solid var(--border-subtle); padding: 24px; border-radius: var(--radius-lg); margin-bottom: 24px;">
  <div class="dashboard-title-group">
    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 6px;">
      <span class="badge" style="background: rgba(0, 230, 118, 0.15); color: var(--accent-green); border: 1px solid rgba(0, 230, 118, 0.3); padding: 4px 10px; font-weight: 700; font-size: 0.75rem;">
        COLLEGE SPORTS HUB
      </span>
      <span class="badge" style="background: rgba(139, 92, 246, 0.15); color: var(--accent-purple); border: 1px solid rgba(139, 92, 246, 0.3); padding: 4px 10px; font-weight: 700; font-size: 0.75rem;">
        AY 2025-2026
      </span>
    </div>
    <h1 style="font-size: 1.75rem; font-weight: 800; color: #fff; margin-bottom: 4px;">
      <?php echo htmlspecialchars($championship['name']); ?>
    </h1>
    <p style="color: var(--text-muted); font-size: 0.9rem;">
      <?php echo htmlspecialchars($championship['college_name']); ?> &bull; 6 Departments &bull; 10 Sports &bull; Overall Championship Trophy Race
    </p>
  </div>
  <div class="quick-actions-bar" style="display:flex; gap:10px; flex-wrap:wrap;">
    <a href="<?php echo BASE_URL; ?>/public/trophy.php" class="btn btn-primary" style="background: linear-gradient(135deg, #ffd700, #ff9800); color: #000; font-weight: 700;">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="7"/><polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"/></svg>
      <span>🏆 Overall Department Standings</span>
    </a>
    <?php if (in_array(strtolower($user['role']), ['admin', 'organizer'])): ?>
      <a href="<?php echo BASE_URL; ?>/admin/tournaments.php?action=create" class="btn btn-secondary">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        <span>+ Add Championship Sport</span>
      </a>
    <?php endif; ?>
  </div>
</div>

<!-- Metrics Stats Grid -->
<div class="stats-grid" style="margin-bottom: 24px;">
  <div class="stat-card green-accent">
    <div class="stat-icon-wrapper">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6"/><path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18"/><path d="M4 22h16"/><path d="M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22"/><path d="M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22"/><path d="M18 2H6v7a6 6 0 0 0 12 0V2z"/></svg>
    </div>
    <div class="stat-details">
      <h3>6</h3>
      <span>Competing Departments</span>
    </div>
  </div>

  <div class="stat-card red-accent">
    <div class="stat-icon-wrapper" style="color:var(--accent-red);">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
    </div>
    <div class="stat-details">
      <h3><?php echo count($liveMatches); ?></h3>
      <span>Live Matches Now</span>
    </div>
  </div>

  <div class="stat-card amber-accent">
    <div class="stat-icon-wrapper" style="color:var(--accent-amber);">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
    </div>
    <div class="stat-details">
      <h3>10</h3>
      <span>Championship Sports</span>
    </div>
  </div>

  <div class="stat-card purple-accent">
    <div class="stat-icon-wrapper" style="color:var(--accent-purple);">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
    </div>
    <div class="stat-details">
      <h3><?php echo $stats['total_players']; ?></h3>
      <span>Department Athletes</span>
    </div>
  </div>
</div>

<!-- Main Dashboard Grid Layout -->
<div class="dashboard-main-grid">
  <!-- Left Column: Live Scores & Match Schedules -->
  <div class="grid-left-col">
    
    <!-- Section: Live Matches Carousel -->
    <div class="section-header">
      <h2>
        <span class="pulse-dot"></span>
        <span>🔴 Live Matches Widget</span>
      </h2>
      <a href="<?php echo BASE_URL; ?>/public/live-score.php" class="btn btn-secondary btn-sm">View All Live</a>
    </div>

    <div class="live-matches-carousel">
      <?php if (empty($liveMatches)): ?>
        <div style="background: var(--bg-card); padding: 18px; border-radius: var(--radius-md); border: 1px dashed var(--border-subtle); color: var(--text-muted); font-size: 0.88rem;">
          No matches currently live. Check today's schedule below.
        </div>
      <?php else: ?>
        <?php foreach ($liveMatches as $match): ?>
          <div class="match-live-card" data-searchable>
            <div class="match-card-top">
              <span class="match-tournament-name"><?php echo htmlspecialchars($match['tournament_name']); ?></span>
              <?php echo getSportBadge($match['sport_name']); ?>
            </div>

            <div class="match-teams-score">
              <div class="team-row">
                <div class="team-info">
                  <div class="team-badge-circle"><?php echo htmlspecialchars($match['team_a_short']); ?></div>
                  <span class="team-name-text"><?php echo htmlspecialchars($match['team_a_name']); ?></span>
                </div>
                <span class="team-score-num live-score-sim" data-sport="<?php echo $match['sport_name']; ?>"><?php echo htmlspecialchars($match['score_a']); ?></span>
              </div>

              <div class="team-row">
                <div class="team-info">
                  <div class="team-badge-circle"><?php echo htmlspecialchars($match['team_b_short']); ?></div>
                  <span class="team-name-text"><?php echo htmlspecialchars($match['team_b_name']); ?></span>
                </div>
                <span class="team-score-num"><?php echo htmlspecialchars($match['score_b']); ?></span>
              </div>
            </div>

            <div class="match-card-footer">
              <span>Status: <strong><?php echo htmlspecialchars($match['current_period']); ?></strong></span>
              <span class="result-note"><?php echo htmlspecialchars($match['result_summary']); ?></span>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <!-- Section: Today's Schedule & Recent Results -->
    <div class="card" style="margin-bottom: 24px;">
      <div class="section-header">
        <h2>📅 Today's Schedule & Recent Fixtures</h2>
        <a href="<?php echo BASE_URL; ?>/public/schedule.php" class="btn btn-secondary btn-sm">Full Schedule</a>
      </div>

      <div class="table-responsive">
        <table class="sports-table">
          <thead>
            <tr>
              <th>Sport & Event</th>
              <th>Departments / Teams</th>
              <th>Date & Time</th>
              <th>Venue</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach (array_slice($allMatches, 0, 5) as $m): ?>
              <tr data-searchable>
                <td>
                  <div style="font-weight:600;"><?php echo htmlspecialchars($m['tournament_name']); ?></div>
                  <div style="margin-top:2px;"><?php echo getSportBadge($m['sport_name']); ?></div>
                </td>
                <td>
                  <div style="font-weight:700; color:var(--text-main);">
                    <?php echo htmlspecialchars($m['team_a_name']); ?> <span style="color:var(--accent-green);">vs</span> <?php echo htmlspecialchars($m['team_b_name']); ?>
                  </div>
                  <div style="font-size:0.78rem; color:var(--text-muted); margin-top:2px;">
                    <?php echo htmlspecialchars($m['result_summary']); ?>
                  </div>
                </td>
                <td>
                  <div><?php echo date('M d, Y', strtotime($m['match_date'])); ?></div>
                  <div style="font-size:0.78rem; color:var(--text-dim);"><?php echo htmlspecialchars($m['start_time']); ?> IST</div>
                </td>
                <td><?php echo htmlspecialchars($m['venue_name']); ?></td>
                <td><?php echo getStatusBadge($m['status']); ?></td>
                <td>
                  <a href="<?php echo BASE_URL; ?>/public/live-score.php?id=<?php echo $m['id']; ?>" class="btn btn-secondary btn-sm">
                    Details
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div> <!-- End Left Col -->

  <!-- Right Column: Overall Department Trophy Widget & Quick Actions -->
  <div class="grid-right-col">

    <!-- Overall Department Trophy Leaderboard Widget -->
    <div class="card" style="margin-bottom: 24px; border: 1px solid rgba(255, 215, 0, 0.3); background: radial-gradient(circle at top right, rgba(255, 215, 0, 0.05), transparent 70%), var(--bg-card);">
      <div class="section-header" style="margin-bottom: 14px;">
        <h2>🏆 Department Leaderboard</h2>
        <a href="<?php echo BASE_URL; ?>/public/trophy.php" style="font-size:0.8rem; color:var(--accent-gold); font-weight:600;">Full Standings →</a>
      </div>

      <div style="display: flex; flex-direction: column; gap: 10px;">
        <?php foreach (array_slice($overallStandings, 0, 6) as $index => $dept): 
          $rank = $index + 1;
          $badge = $rank === 1 ? '🥇' : ($rank === 2 ? '🥈' : ($rank === 3 ? '🥉' : '#' . $rank));
          $rowBg = $rank === 1 ? 'background: rgba(255, 215, 0, 0.1); border: 1px solid rgba(255, 215, 0, 0.3);' : 'background: var(--bg-dark-surface); border: 1px solid var(--border-subtle);';
        ?>
          <div style="display: flex; align-items: center; justify-content: space-between; padding: 10px 14px; border-radius: var(--radius-md); <?php echo $rowBg; ?>">
            <div style="display: flex; align-items: center; gap: 12px;">
              <span style="font-size: 1.1rem; font-weight: 800; min-width: 28px; text-align: center; color: <?php echo $rank === 1 ? '#ffd700' : 'var(--text-main)'; ?>;">
                <?php echo $badge; ?>
              </span>
              <div>
                <div style="font-weight: 700; font-size: 0.92rem; color: var(--text-main);">
                  <?php echo htmlspecialchars($dept['short_code']); ?>
                </div>
                <div style="font-size: 0.75rem; color: var(--text-muted);">
                  🥇 <?php echo $dept['golds']; ?> | 🥈 <?php echo $dept['silvers']; ?> | 🥉 <?php echo $dept['bronzes']; ?>
                </div>
              </div>
            </div>
            <div style="text-align: right;">
              <div style="font-size: 1.15rem; font-weight: 800; color: var(--accent-green);">
                <?php echo $dept['total_points']; ?> <span style="font-size: 0.72rem; font-weight: 500; color: var(--text-dim);">pts</span>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
    
    <!-- Quick Actions Panel -->
    <div class="card" style="margin-bottom: 24px;">
      <div class="section-header" style="margin-bottom: 14px;">
        <h2>Championship Modules</h2>
      </div>
      <div class="quick-action-card-grid">
        <a href="<?php echo BASE_URL; ?>/public/trophy.php" class="quick-action-tile">
          <div class="quick-action-icon" style="background:rgba(255, 215, 0, 0.15);color:#ffd700;">
            🏆
          </div>
          <span>Trophy</span>
        </a>

        <a href="<?php echo BASE_URL; ?>/admin/departments.php" class="quick-action-tile">
          <div class="quick-action-icon" style="background:rgba(0, 230, 118, 0.15);color:var(--accent-green);">
            🏛️
          </div>
          <span>Departments</span>
        </a>

        <a href="<?php echo BASE_URL; ?>/admin/users.php" class="quick-action-tile">
          <div class="quick-action-icon" style="background:rgba(59, 130, 246, 0.15);color:#3b82f6;">
            👥
          </div>
          <span>Users</span>
        </a>

        <a href="<?php echo BASE_URL; ?>/admin/audit-logs.php" class="quick-action-tile">
          <div class="quick-action-icon" style="background:rgba(139,92,246,0.12);color:var(--accent-purple);">
            📜
          </div>
          <span>Audit Logs</span>
        </a>
      </div>
    </div>

    <!-- User Security & Accounts Widget -->
    <?php 
      $uCounts = [
        'total' => fetchOne("SELECT COUNT(*) as cnt FROM users")['cnt'] ?? 6,
        'active' => fetchOne("SELECT COUNT(*) as cnt FROM users WHERE status=1")['cnt'] ?? 6,
        'admins' => fetchOne("SELECT COUNT(*) as cnt FROM users WHERE LOWER(role)='admin'")['cnt'] ?? 1,
        'organizers' => fetchOne("SELECT COUNT(*) as cnt FROM users WHERE LOWER(role)='organizer'")['cnt'] ?? 1,
        'scorers' => fetchOne("SELECT COUNT(*) as cnt FROM users WHERE LOWER(role)='scorer'")['cnt'] ?? 1,
        'officials' => fetchOne("SELECT COUNT(*) as cnt FROM users WHERE LOWER(role)='official'")['cnt'] ?? 1,
        'public' => fetchOne("SELECT COUNT(*) as cnt FROM users WHERE LOWER(role) IN ('player', 'public_user', 'team_manager')")['cnt'] ?? 2,
      ];
      $recentAuditLogs = fetchAll("SELECT * FROM audit_logs ORDER BY id DESC LIMIT 5");
    ?>
    <div class="card" style="margin-bottom: 24px;">
      <div class="section-header" style="margin-bottom: 12px; display:flex; justify-content:space-between; align-items:center;">
        <h2>User System Overview</h2>
        <a href="<?php echo BASE_URL; ?>/admin/users.php" style="font-size:0.8rem; color:var(--accent-green);">Manage All &rarr;</a>
      </div>
      
      <div style="display:grid; grid-template-columns: repeat(3, 1fr); gap:10px; margin-bottom:16px; text-align:center;">
        <div style="background:var(--bg-dark-surface); padding:10px; border-radius:6px; border:1px solid var(--border-subtle);">
          <strong style="font-size:1.2rem; color:var(--accent-green); display:block;"><?php echo $uCounts['total']; ?></strong>
          <span style="font-size:0.75rem; color:var(--text-muted);">Total Users</span>
        </div>
        <div style="background:var(--bg-dark-surface); padding:10px; border-radius:6px; border:1px solid var(--border-subtle);">
          <strong style="font-size:1.2rem; color:#3b82f6; display:block;"><?php echo $uCounts['admins']; ?></strong>
          <span style="font-size:0.75rem; color:var(--text-muted);">Admins</span>
        </div>
        <div style="background:var(--bg-dark-surface); padding:10px; border-radius:6px; border:1px solid var(--border-subtle);">
          <strong style="font-size:1.2rem; color:var(--accent-purple); display:block;"><?php echo $uCounts['organizers']; ?></strong>
          <span style="font-size:0.75rem; color:var(--text-muted);">Organizers</span>
        </div>
        <div style="background:var(--bg-dark-surface); padding:10px; border-radius:6px; border:1px solid var(--border-subtle);">
          <strong style="font-size:1.2rem; color:var(--accent-amber); display:block;"><?php echo $uCounts['scorers']; ?></strong>
          <span style="font-size:0.75rem; color:var(--text-muted);">Scorers</span>
        </div>
        <div style="background:var(--bg-dark-surface); padding:10px; border-radius:6px; border:1px solid var(--border-subtle);">
          <strong style="font-size:1.2rem; color:#06b6d4; display:block;"><?php echo $uCounts['officials']; ?></strong>
          <span style="font-size:0.75rem; color:var(--text-muted);">Officials</span>
        </div>
        <div style="background:var(--bg-dark-surface); padding:10px; border-radius:6px; border:1px solid var(--border-subtle);">
          <strong style="font-size:1.2rem; color:var(--text-main); display:block;"><?php echo $uCounts['public']; ?></strong>
          <span style="font-size:0.75rem; color:var(--text-muted);">Public Users</span>
        </div>
      </div>

      <h4 style="font-size:0.85rem; color:var(--text-muted); margin-bottom:8px; text-transform:uppercase;">Recent Security Audit Logs</h4>
      <div style="display:flex; flex-direction:column; gap:6px;">
        <?php foreach (array_slice($recentAuditLogs, 0, 3) as $al): ?>
          <div style="font-size:0.8rem; display:flex; justify-content:space-between; padding:6px 0; border-bottom:1px dashed var(--border-subtle);">
            <span><strong style="color:var(--text-main);"><?php echo htmlspecialchars($al['user_name'] ?? 'User'); ?></strong>: <?php echo htmlspecialchars($al['action']); ?></span>
            <span style="color:var(--text-muted); font-size:0.75rem;"><?php echo date('H:i', strtotime($al['created_at'])); ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>


    <!-- Active Championship Sports List Card -->
    <div class="card">
      <div class="section-header" style="margin-bottom:14px;">
        <h2>Active Championship Sports</h2>
      </div>
      <div style="display:flex; flex-direction:column; gap:12px;">
        <?php foreach ($tournaments as $t): ?>
          <div style="background:var(--bg-dark-surface); padding:14px; border-radius:var(--radius-md); border:1px solid var(--border-subtle);" data-searchable>
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:6px;">
              <?php echo getSportBadge($t['sport_name']); ?>
              <?php echo getStatusBadge($t['status']); ?>
            </div>
            <h4 style="font-size:0.95rem; margin-bottom:4px;"><?php echo htmlspecialchars($t['name']); ?></h4>
            <div style="font-size:0.78rem; color:var(--text-muted);">
              Format: <strong><?php echo htmlspecialchars($t['format']); ?></strong> &bull; <?php echo $t['teams_count']; ?> Department Teams
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

  </div> <!-- End Right Col -->

</div> <!-- End Dashboard Grid -->

<?php include_once __DIR__ . '/../includes/footer.php'; ?>

