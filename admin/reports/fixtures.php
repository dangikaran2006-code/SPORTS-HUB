<?php
/**
 * SportsHub - Admin Fixture Schedule Report
 */
$currentPage = 'statistics';
$pageTitle   = 'Fixtures & Schedule Report';

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/match-functions.php';
require_once __DIR__ . '/../../includes/report-helper.php';

requireAdminAccess();

$sportId = intval($_GET['sport_id'] ?? 0);
$venueId = intval($_GET['venue_id'] ?? 0);
$status  = trim($_GET['status'] ?? '');
$search  = trim($_GET['search'] ?? '');

$matchesList = getMatchesFiltered($search, 0, $sportId, '', $venueId, $status);

// CSV Export Handler
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $csvHeaders = ['Match ID', 'Date', 'Time', 'Sport', 'Match Fixture', 'Venue', 'Official', 'Status'];
    $csvRows = [];
    foreach ($matchesList as $m) {
        $csvRows[] = [
            $m['id'],
            $m['scheduled_date'],
            $m['scheduled_time'],
            $m['sport_name'],
            $m['team_a_name'] . ' vs ' . $m['team_b_name'],
            $m['venue_name'] ?? 'TBD',
            $m['official_name'] ?? 'Unassigned',
            strtoupper($m['status'])
        ];
    }
    exportCsvReport('Fixture_Schedule_Report.csv', $csvHeaders, $csvRows);
}

$sportsList = getSportsList();
$venuesList = getVenuesList();

include_once __DIR__ . '/../../includes/header.php';
?>

<?php echo renderPrintHeader('Official Fixture Schedule & Match Assignments Report'); ?>

<div class="dashboard-header no-print">
  <div class="dashboard-title-group">
    <h1>Official Fixtures & Schedule Report</h1>
    <p>Filtered match schedules, assigned grounds, officials, and status track across all sports.</p>
  </div>
  <div class="quick-actions-bar" style="display:flex; gap:10px;">
    <a href="?export=csv<?php echo $sportId ? "&sport_id={$sportId}" : ''; ?><?php echo $venueId ? "&venue_id={$venueId}" : ''; ?><?php echo $status ? "&status={$status}" : ''; ?>" class="btn btn-secondary">
      📥 Export CSV
    </a>
    <button onclick="window.print()" class="btn btn-primary">
      🖨️ Print Schedule Report
    </button>
  </div>
</div>

<!-- Filter Bar -->
<div class="card no-print" style="margin-bottom: 24px;">
  <form method="GET" action="" style="display: grid; grid-template-columns: 2fr 1fr 1fr 1fr auto; gap: 12px; align-items: end;">
    <div>
      <label style="font-size: 0.75rem; color: var(--text-muted); font-weight: 700;">SEARCH MATCHES</label>
      <input type="text" name="search" class="form-control" placeholder="Search team or tournament..." value="<?php echo htmlspecialchars($search); ?>">
    </div>
    <div>
      <label style="font-size: 0.75rem; color: var(--text-muted); font-weight: 700;">SPORT</label>
      <select name="sport_id" class="form-control">
        <option value="0">All Sports</option>
        <?php foreach ($sportsList as $s): ?>
          <option value="<?php echo $s['id']; ?>" <?php echo $sportId == $s['id'] ? 'selected' : ''; ?>>
            <?php echo htmlspecialchars($s['name']); ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label style="font-size: 0.75rem; color: var(--text-muted); font-weight: 700;">VENUE</label>
      <select name="venue_id" class="form-control">
        <option value="0">All Venues</option>
        <?php foreach ($venuesList as $v): ?>
          <option value="<?php echo $v['id']; ?>" <?php echo $venueId == $v['id'] ? 'selected' : ''; ?>>
            <?php echo htmlspecialchars($v['name']); ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label style="font-size: 0.75rem; color: var(--text-muted); font-weight: 700;">STATUS</label>
      <select name="status" class="form-control">
        <option value="">All Statuses</option>
        <option value="scheduled" <?php echo $status === 'scheduled' ? 'selected' : ''; ?>>Scheduled</option>
        <option value="live" <?php echo $status === 'live' ? 'selected' : ''; ?>>Live</option>
        <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>Completed</option>
      </select>
    </div>
    <div>
      <button type="submit" class="btn btn-primary" style="height: 42px;">Filter</button>
    </div>
  </form>
</div>

<div class="card">
  <div class="table-responsive">
    <table class="sports-table">
      <thead>
        <tr>
          <th>Date & Time</th>
          <th>Sport</th>
          <th>Match Fixture</th>
          <th>Venue</th>
          <th>Official In-charge</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($matchesList as $m): ?>
          <tr>
            <td style="font-size:0.85rem; color:var(--text-muted);">
              <strong><?php echo date('M d, Y', strtotime($m['scheduled_date'])); ?></strong><br>
              <?php echo date('h:i A', strtotime($m['scheduled_time'])); ?>
            </td>
            <td><?php echo getSportBadge($m['sport_name']); ?></td>
            <td>
              <strong style="color:#fff;"><?php echo htmlspecialchars($m['team_a_name']); ?> vs <?php echo htmlspecialchars($m['team_b_name']); ?></strong>
            </td>
            <td><?php echo htmlspecialchars($m['venue_name'] ?? 'TBD'); ?></td>
            <td><?php echo htmlspecialchars($m['official_name'] ?? 'Unassigned'); ?></td>
            <td><?php echo getStatusBadge($m['status']); ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include_once __DIR__ . '/../../includes/footer.php'; ?>
