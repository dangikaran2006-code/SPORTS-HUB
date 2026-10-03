<?php
/**
 * SportsHub - Tournament Details Console & Dashboard
 */
$currentPage = 'tournaments';
$pageTitle = 'Tournament Details';

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/tournament-functions.php';

// Server-side RBAC Enforcement
requireRole(['admin', 'organizer', 'scorer', 'official', 'team_manager', 'player']);

$id = intval($_GET['id'] ?? 1);
$tournament = getTournamentById($id);

if (!$tournament) {
    header('Location: ' . BASE_URL . '/organizer/tournaments.php');
    exit;
}

$activeTab = $_GET['tab'] ?? 'overview';
$stats = getTournamentStatsService($tournament['id']);
$tournamentTeams = getTournamentTeamsService($tournament['id']);
$dbMatches = getDB()->getMatches('all');

include_once __DIR__ . '/../includes/header.php';
?>

<!-- Tournament Summary Banner Header -->
<div class="dashboard-header" style="margin-bottom:16px;">
  <div class="dashboard-title-group">
    <div style="display:flex; align-items:center; gap:12px; margin-bottom:8px;">
      <?php echo getSportBadge($tournament['sport_name']); ?>
      <?php echo getStatusBadge($tournament['status']); ?>
      <span style="font-size:0.8rem; color:var(--text-muted);">Slug: <code><?php echo htmlspecialchars($tournament['slug']); ?></code></span>
    </div>
    <h1><?php echo htmlspecialchars($tournament['name']); ?></h1>
    <p>Venue: <strong><?php echo htmlspecialchars($tournament['venue_name'] ?? 'TBD'); ?></strong> &bull; Format: <strong><?php echo htmlspecialchars($tournament['format']); ?></strong> &bull; Deadline: <?php echo !empty($tournament['registration_deadline']) ? date('M d, Y', strtotime($tournament['registration_deadline'])) : 'N/A'; ?></p>
  </div>
  <div class="quick-actions-bar">
    <?php if (in_array(strtolower($currentUser['role']), ['admin', 'organizer'])): ?>
      <a href="<?php echo BASE_URL; ?>/organizer/edit-tournament.php?id=<?php echo $tournament['id']; ?>" class="btn btn-secondary">
        Edit Tournament
      </a>
      <a href="<?php echo BASE_URL; ?>/organizer/tournament-teams.php?tournament_id=<?php echo $tournament['id']; ?>" class="btn btn-primary">
        + Manage Teams
      </a>
    <?php endif; ?>
  </div>
</div>

<!-- Section 9: Tournament Dashboard Metrics Bar (Live DB Metrics) -->
<div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap:14px; margin-bottom:24px;">
  <div class="stat-card green-accent" style="padding:14px;">
    <div class="stat-details">
      <h3><?php echo $stats['total_teams']; ?></h3>
      <span>Total Teams</span>
    </div>
  </div>
  <div class="stat-card green-accent" style="padding:14px;">
    <div class="stat-details">
      <h3><?php echo $stats['approved_teams']; ?></h3>
      <span>Approved</span>
    </div>
  </div>
  <div class="stat-card amber-accent" style="padding:14px;">
    <div class="stat-details">
      <h3><?php echo $stats['pending_teams']; ?></h3>
      <span>Pending</span>
    </div>
  </div>
  <div class="stat-card purple-accent" style="padding:14px;">
    <div class="stat-details">
      <h3><?php echo $stats['total_matches']; ?></h3>
      <span>Total Matches</span>
    </div>
  </div>
  <div class="stat-card green-accent" style="padding:14px;">
    <div class="stat-details">
      <h3><?php echo $stats['completed_matches']; ?></h3>
      <span>Completed</span>
    </div>
  </div>
  <div class="stat-card red-accent" style="padding:14px;">
    <div class="stat-details">
      <h3><?php echo $stats['live_matches']; ?></h3>
      <span>Live Now</span>
    </div>
  </div>
  <div class="stat-card amber-accent" style="padding:14px;">
    <div class="stat-details">
      <h3><?php echo $stats['upcoming_matches']; ?></h3>
      <span>Upcoming</span>
    </div>
  </div>
</div>

<!-- Navigation Tabs Header -->
<div class="tournament-controls-bar" style="margin-bottom:20px;">
  <div class="filter-pills">
    <a href="<?php echo BASE_URL; ?>/organizer/tournament-details.php?id=<?php echo $tournament['id']; ?>&tab=overview" class="filter-pill-btn <?php echo ($activeTab==='overview')?'active':''; ?>">Overview</a>
    <a href="<?php echo BASE_URL; ?>/organizer/tournament-details.php?id=<?php echo $tournament['id']; ?>&tab=teams" class="filter-pill-btn <?php echo ($activeTab==='teams')?'active':''; ?>">Registered Teams (<?php echo count($tournamentTeams); ?>)</a>
    <a href="<?php echo BASE_URL; ?>/organizer/tournament-details.php?id=<?php echo $tournament['id']; ?>&tab=matches" class="filter-pill-btn <?php echo ($activeTab==='matches')?'active':''; ?>">Matches</a>
    <a href="<?php echo BASE_URL; ?>/organizer/tournament-details.php?id=<?php echo $tournament['id']; ?>&tab=fixtures" class="filter-pill-btn <?php echo ($activeTab==='fixtures')?'active':''; ?>">Fixtures</a>
    <a href="<?php echo BASE_URL; ?>/organizer/tournament-details.php?id=<?php echo $tournament['id']; ?>&tab=points" class="filter-pill-btn <?php echo ($activeTab==='points')?'active':''; ?>">Points Table</a>
    <a href="<?php echo BASE_URL; ?>/organizer/tournament-details.php?id=<?php echo $tournament['id']; ?>&tab=stats" class="filter-pill-btn <?php echo ($activeTab==='stats')?'active':''; ?>">Statistics</a>
  </div>
</div>

<!-- Tab Content 1: Overview -->
<?php if ($activeTab === 'overview'): ?>
<div class="dashboard-main-grid">
  <div class="grid-left-col">
    <div class="card" style="margin-bottom:24px;">
      <div class="section-header">
        <h2>About Tournament</h2>
      </div>
      <p style="color:var(--text-muted); font-size:0.95rem; line-height:1.6; margin-bottom:20px;">
        <?php echo !empty($tournament['description']) ? htmlspecialchars($tournament['description']) : 'Official tournament overview and ground rules for participants.'; ?>
      </p>

      <div class="section-header" style="margin-top:20px;">
        <h2>Tournament Fixtures & Live Activity</h2>
        <a href="<?php echo BASE_URL; ?>/admin/matches.php?tournament_id=<?php echo $tournament['id']; ?>" class="btn btn-secondary btn-sm">View Schedule</a>
      </div>

      <div class="table-responsive">
        <table class="sports-table">
          <thead>
            <tr>
              <th>Match Teams</th>
              <th>Date & Time</th>
              <th>Status</th>
              <th>Result / Summary</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($dbMatches as $m): ?>
              <tr>
                <td>
                  <strong style="color:var(--text-main);"><?php echo htmlspecialchars($m['team_a_name']); ?></strong> vs <strong style="color:var(--text-main);"><?php echo htmlspecialchars($m['team_b_name']); ?></strong>
                </td>
                <td><?php echo date('M d, Y', strtotime($m['match_date'])); ?> @ <?php echo $m['start_time']; ?></td>
                <td><?php echo getStatusBadge($m['status']); ?></td>
                <td style="color:var(--accent-amber); font-weight:600;"><?php echo htmlspecialchars($m['result_summary']); ?></td>
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
        <h2>Tournament Specs</h2>
      </div>
      <div style="display:flex; flex-direction:column; gap:14px; font-size:0.875rem;">
        <div>
          <span style="color:var(--text-muted);">Sport Category:</span>
          <strong style="display:block; color:var(--text-main);"><?php echo htmlspecialchars($tournament['sport_name']); ?></strong>
        </div>
        <div>
          <span style="color:var(--text-muted);">Organizer:</span>
          <strong style="display:block; color:var(--text-main);"><?php echo htmlspecialchars($tournament['organizer_name'] ?? 'Admin'); ?></strong>
        </div>
        <div>
          <span style="color:var(--text-muted);">Start & End Dates:</span>
          <strong style="display:block; color:var(--text-main);"><?php echo date('M d, Y', strtotime($tournament['start_date'])); ?> &ndash; <?php echo date('M d, Y', strtotime($tournament['end_date'])); ?></strong>
        </div>
        <div>
          <span style="color:var(--text-muted);">Venue Ground:</span>
          <strong style="display:block; color:var(--text-main);"><?php echo htmlspecialchars($tournament['venue_name'] ?? 'TBD'); ?></strong>
        </div>
        <div>
          <span style="color:var(--text-muted);">Tournament Format:</span>
          <strong style="display:block; color:var(--text-main); text-transform:capitalize;"><?php echo htmlspecialchars($tournament['format']); ?></strong>
        </div>
        <div>
          <span style="color:var(--text-muted);">Maximum Teams Limit:</span>
          <strong style="display:block; color:var(--accent-green);"><?php echo htmlspecialchars($tournament['max_teams'] ?? 16); ?> Teams Allowed</strong>
        </div>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- Tab Content 2: Teams -->
<?php if ($activeTab === 'teams'): ?>
<div class="card">
  <div class="section-header" style="margin-bottom:16px;">
    <h2>Registered Roster Teams (<?php echo count($tournamentTeams); ?>)</h2>
    <a href="<?php echo BASE_URL; ?>/organizer/tournament-teams.php?tournament_id=<?php echo $tournament['id']; ?>" class="btn btn-primary btn-sm">+ Manage Roster Teams</a>
  </div>

  <div class="table-responsive">
    <table class="sports-table">
      <thead>
        <tr>
          <th>Team & Badge</th>
          <th>Sport</th>
          <th>Captain</th>
          <th>Roster Size</th>
          <th>Status</th>
          <th>Joined Date</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($tournamentTeams as $tt): ?>
          <tr>
            <td>
              <div style="display:flex; align-items:center; gap:10px;">
                <div class="team-badge-circle" style="width:32px;height:32px;font-size:0.75rem; font-weight:800; color:var(--accent-green);">
                  <?php echo htmlspecialchars($tt['short_name']); ?>
                </div>
                <strong style="color:var(--text-main);"><?php echo htmlspecialchars($tt['team_name']); ?></strong>
              </div>
            </td>
            <td><?php echo getSportBadge($tt['sport_name']); ?></td>
            <td><?php echo htmlspecialchars($tt['captain_name'] ?? 'Unassigned'); ?></td>
            <td><?php echo $tt['players_count']; ?> Athletes</td>
            <td><span class="status-badge badge-active"><?php echo htmlspecialchars($tt['registration_status']); ?></span></td>
            <td><?php echo date('M d, Y', strtotime($tt['joined_at'])); ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<!-- Tab Content 3: Matches & Fixtures -->
<?php if (in_array($activeTab, ['matches', 'fixtures'])): ?>
<div class="card" style="text-align:center; padding:48px 24px;">
  <div style="font-size:2.5rem; margin-bottom:12px;">🗓️</div>
  <h3 style="font-size:1.2rem; margin-bottom:8px; text-transform:capitalize;"><?php echo htmlspecialchars($activeTab); ?> Module</h3>
  <p style="color:var(--text-muted); font-size:0.9rem; max-width:400px; margin:0 auto 20px auto;">
    Tournament <?php echo htmlspecialchars($activeTab); ?> match engine. View and manage all fixtures for this tournament.
  </p>
  <a href="<?php echo BASE_URL; ?>/admin/matches.php?tournament_id=<?php echo $tournament['id']; ?>" class="btn btn-secondary">Open Match Engine</a>
</div>
<?php endif; ?>

<!-- Tab Content 4: Points Table -->
<?php if ($activeTab === 'points'): ?>
<?php 
  require_once __DIR__ . '/../includes/statistics-helper.php';
  $tStandings = StatisticsService::getPointsTable($tournament['id'], $tournament['sport_id']);
?>
<div class="card">
  <div class="section-header" style="margin-bottom:16px;">
    <h2>Tournament Points Table</h2>
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
          <th><?php echo strtolower($tournament['sport_name'] ?? '') === 'cricket' ? 'NRR' : 'Diff'; ?></th>
          <th>Points</th>
          <th>Form</th>
        </tr>
      </thead>
      <tbody>
        <?php $pPos = 1; foreach ($tStandings as $st): ?>
          <tr>
            <td><strong style="color:var(--accent-green); font-size:1.1rem;"><?php echo $pPos++; ?></strong></td>
            <td>
              <div style="display:flex; align-items:center; gap:10px;">
                <div class="team-badge-circle" style="width:28px;height:28px;font-size:0.75rem;">
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
<?php endif; ?>

<!-- Tab Content 5: Statistics -->
<?php if ($activeTab === 'stats'): ?>
<?php 
  require_once __DIR__ . '/../includes/statistics-helper.php';
  $tPlayerStats = StatisticsService::getPlayerStatistics($tournament['id'], $tournament['sport_id']);
?>
<div class="card">
  <div class="section-header" style="margin-bottom:16px;">
    <h2>Top Tournament Performers</h2>
  </div>
  <div class="table-responsive">
    <table class="sports-table">
      <thead>
        <tr>
          <th>Player Name</th>
          <th>Team</th>
          <th>Matches</th>
          <th>Runs Scored</th>
          <th>Batting Avg</th>
          <th>Strike Rate</th>
          <th>4s</th>
          <th>6s</th>
          <th>Wickets</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($tPlayerStats as $p): ?>
          <tr>
            <td><strong style="color:var(--text-main);"><?php echo htmlspecialchars($p['player_name']); ?></strong></td>
            <td><?php echo htmlspecialchars($p['team_name'] ?? 'Team'); ?></td>
            <td><?php echo $p['matches_played'] ?? 1; ?></td>
            <td><strong style="color:var(--accent-green); font-size:1.05rem;"><?php echo $p['total_runs'] ?? 0; ?></strong></td>
            <td><?php echo $p['batting_avg'] ?? '0.00'; ?></td>
            <td><?php echo $p['strike_rate'] ?? '0.00'; ?></td>
            <td><?php echo $p['total_fours'] ?? 0; ?></td>
            <td><?php echo $p['total_sixes'] ?? 0; ?></td>
            <td><strong style="color:var(--accent-blue);"><?php echo $p['total_wickets'] ?? 0; ?></strong></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
