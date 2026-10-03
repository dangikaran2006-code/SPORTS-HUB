<?php
/**
 * SportsHub - Today's & Master Championship Sports Schedule
 */
$currentPage = 'schedule';
$pageTitle   = "Today's & Master Sports Schedule";

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/match-functions.php';

// Enforce login
requireLogin();

$search       = trim($_GET['search'] ?? '');
$sportId      = intval($_GET['sport_id'] ?? 0);
$venueId      = intval($_GET['venue_id'] ?? 0);
$statusFilter = trim($_GET['status'] ?? '');
$dateFilter   = trim($_GET['date'] ?? '');

$matches = getMatchesFiltered($search, 0, $sportId, $dateFilter, $venueId, $statusFilter);
$venues  = getVenuesList();

include_once __DIR__ . '/../includes/header.php';
?>

<!-- Header -->
<div class="dashboard-header">
  <div class="dashboard-title-group">
    <h1>Today's & Championship Sports Schedule</h1>
    <p>Comprehensive timetable for all inter-department sports matches, events, venues, and live tickers.</p>
  </div>
</div>

<!-- Filter Bar -->
<div class="card" style="margin-bottom:24px;">
  <form action="" method="GET" style="display:flex; flex-wrap:wrap; gap:12px; align-items:center;">
    <div style="flex:1; min-width:200px;">
      <input type="text" name="search" class="form-control" placeholder="Search department, team or venue..." value="<?php echo htmlspecialchars($search); ?>">
    </div>

    <div style="width:180px;">
      <select name="status" class="form-control">
        <option value="">All Statuses</option>
        <option value="live" <?php echo $statusFilter === 'live' ? 'selected' : ''; ?>>🔴 LIVE Now</option>
        <option value="scheduled" <?php echo $statusFilter === 'scheduled' ? 'selected' : ''; ?>>📅 Scheduled</option>
        <option value="completed" <?php echo $statusFilter === 'completed' ? 'selected' : ''; ?>>✅ Completed</option>
        <option value="postponed" <?php echo $statusFilter === 'postponed' ? 'selected' : ''; ?>>⏳ Postponed</option>
      </select>
    </div>

    <div style="width:180px;">
      <input type="date" name="date" class="form-control" value="<?php echo htmlspecialchars($dateFilter); ?>">
    </div>

    <button type="submit" class="btn btn-primary btn-sm">Filter Schedule</button>
    <a href="<?php echo BASE_URL; ?>/public/schedule.php" class="btn btn-secondary btn-sm" style="color:var(--text-muted);">Reset</a>
  </form>
</div>

<!-- Schedule Table -->
<div class="card">
  <div class="section-header" style="margin-bottom:16px;">
    <h2>Championship Match & Event Timetable (<?php echo count($matches); ?> Matches)</h2>
  </div>

  <div class="table-responsive">
    <table class="sports-table">
      <thead>
        <tr>
          <th>Time & Date</th>
          <th>Sport Category</th>
          <th>Competing Departments / Teams</th>
          <th>Venue Arena</th>
          <th>Official / Referee</th>
          <th>Match Status</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($matches)): ?>
          <?php foreach ($matches as $m): ?>
            <tr>
              <td>
                <strong style="color:var(--text-main); display:block;"><?php echo date('h:i A', strtotime($m['scheduled_time'] ?? $m['start_time'] ?? '10:00:00')); ?></strong>
                <span style="font-size:0.75rem; color:var(--text-muted);"><?php echo date('M d, Y', strtotime($m['scheduled_date'] ?? $m['match_date'] ?? 'now')); ?></span>
              </td>
              <td><?php echo getSportBadge($m['sport_name'] ?? 'Cricket'); ?></td>
              <td>
                <div style="display:flex; align-items:center; gap:8px;">
                  <strong style="color:var(--text-main);"><?php echo htmlspecialchars($m['team_a_name']); ?></strong>
                  <span style="color:var(--text-dim); font-size:0.8rem; font-weight:700;">VS</span>
                  <strong style="color:var(--text-main);"><?php echo htmlspecialchars($m['team_b_name']); ?></strong>
                </div>
              </td>
              <td>
                <span style="font-size:0.85rem; color:var(--text-muted);"><?php echo htmlspecialchars($m['venue_name'] ?? 'Main Ground'); ?></span>
              </td>
              <td>
                <span style="font-size:0.8rem; color:var(--text-dim);"><?php echo htmlspecialchars($m['official_name'] ?? 'Assigned Referee'); ?></span>
              </td>
              <td><?php echo getStatusBadge($m['status'] ?? 'scheduled'); ?></td>
              <td>
                <?php if (($m['status'] ?? 'scheduled') === 'live'): ?>
                  <a href="<?php echo BASE_URL; ?>/public/live-score.php?match_id=<?php echo $m['id']; ?>" class="btn btn-primary btn-sm" style="background:var(--accent-red); border-color:var(--accent-red);">
                    🔴 View Live
                  </a>
                <?php else: ?>
                  <a href="<?php echo BASE_URL; ?>/public/live-score.php?match_id=<?php echo $m['id']; ?>" class="btn btn-secondary btn-sm">
                    View Details
                  </a>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="7" style="text-align:center; padding:32px; color:var(--text-muted);">
              No matches or events found matching selected schedule filters.
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
