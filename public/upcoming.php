<?php
/**
 * SportsHub - Public Spectator Upcoming Matches Directory
 * Dynamically displays scheduled events from ALL sports configured in the database
 */
$currentPage = 'upcoming';
$pageTitle = 'Upcoming Championship Matches';

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/department-helper.php';

$db = getDB();
$allMatches = $db->getMatches('all');

// Filter only scheduled & upcoming matches (exclude completed)
$upcomingMatches = array_filter($allMatches, function($m) {
    $st = strtolower($m['status'] ?? '');
    return $st === 'scheduled' || $st === 'upcoming';
});

// Dynamic Sports List & Departments List from database
$sportsList = $db->getSports();
if (empty($sportsList)) {
    $sportsList = [
        ['id' => 1, 'name' => 'Cricket'],
        ['id' => 2, 'name' => 'Football'],
        ['id' => 3, 'name' => 'Kabaddi'],
        ['id' => 4, 'name' => 'Basketball'],
        ['id' => 5, 'name' => 'Volleyball'],
        ['id' => 6, 'name' => 'Badminton'],
        ['id' => 7, 'name' => 'Tennis'],
        ['id' => 8, 'name' => 'Table Tennis'],
        ['id' => 9, 'name' => 'Athletics'],
        ['id' => 10, 'name' => 'Chess'],
        ['id' => 11, 'name' => 'Hockey'],
    ];
}

$departments = DepartmentService::getDepartments();
$venues = $db->getVenues();

// Unique sports represented in upcoming matches
$sportsRepresented = array_unique(array_column($upcomingMatches, 'sport_name'));
$totalUpcoming = count($upcomingMatches);

include_once __DIR__ . '/../includes/public-header.php';
?>

<!-- Header Banner -->
<div style="margin-bottom: 24px;">
  <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px;">
    <div>
      <h1 style="font-size: 1.8rem; font-weight: 800; color: #fff; margin-bottom: 6px;">📅 All Sports Upcoming Matches</h1>
      <p style="color: var(--text-muted); font-size: 0.92rem; margin: 0;">
        Live schedule and upcoming inter-department sports fixtures across all college grounds.
      </p>
    </div>
    <a href="<?php echo BASE_URL; ?>/public/live-score.php" class="btn btn-secondary" style="border-color: rgba(239,68,68,0.4); color: #ef4444;">
      🔴 Check Live Matches
    </a>
  </div>
</div>

<!-- Summary Metrics Card -->
<div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 28px; background: var(--bg-card); border: 1px solid var(--border-subtle); padding: 20px; border-radius: var(--radius-lg);">
  <div>
    <div style="font-size: 0.78rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">UPCOMING EVENTS</div>
    <div style="font-size: 1.8rem; font-weight: 800; color: var(--accent-green); line-height: 1.2; margin-top: 4px;">
      <?php echo $totalUpcoming; ?> <span style="font-size: 0.9rem; font-weight: 500; color: var(--text-dim);">Fixtures</span>
    </div>
  </div>

  <div>
    <div style="font-size: 0.78rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">SPORTS FEATURED</div>
    <div style="font-size: 1.8rem; font-weight: 800; color: var(--accent-amber); line-height: 1.2; margin-top: 4px;">
      <?php echo count($sportsRepresented); ?> <span style="font-size: 0.9rem; font-weight: 500; color: var(--text-dim);">Sports</span>
    </div>
  </div>

  <div>
    <div style="font-size: 0.78rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">DEPARTMENTS COMPETING</div>
    <div style="font-size: 1.8rem; font-weight: 800; color: var(--accent-purple); line-height: 1.2; margin-top: 4px;">
      <?php echo count($departments); ?> <span style="font-size: 0.9rem; font-weight: 500; color: var(--text-dim);">Departments</span>
    </div>
  </div>
</div>

<!-- Search & Filters Control Panel -->
<div style="background: var(--bg-card); border: 1px solid var(--border-subtle); border-radius: var(--radius-lg); padding: 18px; margin-bottom: 28px;">
  <div style="display: grid; grid-template-columns: 2fr 1fr 1fr 1fr; gap: 14px;">
    <!-- Live Search -->
    <div>
      <label style="font-size: 0.75rem; color: var(--text-muted); font-weight: 700; display: block; margin-bottom: 4px;">SEARCH FIXTURES</label>
      <input type="text" id="publicSearchInput" onkeyup="filterUpcomingMatches()" class="form-control" placeholder="Search team, player, sport, venue...">
    </div>

    <!-- Sport Filter -->
    <div>
      <label style="font-size: 0.75rem; color: var(--text-muted); font-weight: 700; display: block; margin-bottom: 4px;">SPORT</label>
      <select id="sportFilter" onchange="filterUpcomingMatches()" class="form-control">
        <option value="">ALL SPORTS</option>
        <?php foreach ($sportsList as $s): ?>
          <option value="<?php echo htmlspecialchars(strtolower($s['name'])); ?>"><?php echo htmlspecialchars($s['name']); ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <!-- Date Filter -->
    <div>
      <label style="font-size: 0.75rem; color: var(--text-muted); font-weight: 700; display: block; margin-bottom: 4px;">DATE</label>
      <select id="dateFilter" onchange="filterUpcomingMatches()" class="form-control">
        <option value="">ALL DATES</option>
        <option value="today">TODAY</option>
        <option value="tomorrow">TOMORROW</option>
        <option value="week">THIS WEEK</option>
      </select>
    </div>

    <!-- Venue Filter -->
    <div>
      <label style="font-size: 0.75rem; color: var(--text-muted); font-weight: 700; display: block; margin-bottom: 4px;">VENUE</label>
      <select id="venueFilter" onchange="filterUpcomingMatches()" class="form-control">
        <option value="">ALL VENUES</option>
        <?php foreach ($venues as $v): ?>
          <option value="<?php echo htmlspecialchars(strtolower($v['name'])); ?>"><?php echo htmlspecialchars($v['name']); ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>
</div>

<!-- Dynamic Upcoming Events Grid -->
<div style="background: var(--bg-card); border: 1px solid var(--border-subtle); border-radius: var(--radius-lg); overflow: hidden;">
  <table class="sports-table" style="margin: 0;" id="upcomingTable">
    <thead>
      <tr>
        <th>Sport</th>
        <th>Event / Match Name</th>
        <th>Department / Participants</th>
        <th>Date & Time</th>
        <th>Venue & Officials</th>
        <th>Status</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($upcomingMatches)): ?>
        <tr>
          <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 36px;">
            No upcoming matches currently scheduled. Check back soon for new fixtures!
          </td>
        </tr>
      <?php else: ?>
        <?php foreach ($upcomingMatches as $m): 
          $sportLower = strtolower($m['sport_name'] ?? '');
          $venueLower = strtolower($m['venue_name'] ?? '');
          $matchDate = date('Y-m-d', strtotime($m['match_date']));
          $today = date('Y-m-d');
          $tomorrow = date('Y-m-d', strtotime('+1 day'));
          
          $dateCat = 'future';
          if ($matchDate === $today) $dateCat = 'today';
          elseif ($matchDate === $tomorrow) $dateCat = 'tomorrow';
        ?>
          <tr class="upcoming-row" data-sport="<?php echo $sportLower; ?>" data-venue="<?php echo $venueLower; ?>" data-datecat="<?php echo $dateCat; ?>" data-search="<?php echo htmlspecialchars(strtolower($m['sport_name'] . ' ' . $m['tournament_name'] . ' ' . $m['team_a_name'] . ' ' . $m['team_b_name'] . ' ' . $m['venue_name'])); ?>">
            <td>
              <span class="badge" style="background: rgba(0, 230, 118, 0.15); color: var(--accent-green); font-weight: 800;">
                <?php echo htmlspecialchars($m['sport_name']); ?>
              </span>
            </td>
            <td>
              <div style="font-weight: 700; color: #fff;"><?php echo htmlspecialchars($m['tournament_name']); ?></div>
              <div style="font-size: 0.78rem; color: var(--text-dim);">Round / Group Stage</div>
            </td>
            <td>
              <div style="font-weight: 700; color: #fff; font-size: 0.95rem;">
                <?php echo htmlspecialchars($m['team_a_name']); ?>
                <span style="color: var(--accent-green); font-weight: 800;"> vs </span>
                <?php echo htmlspecialchars($m['team_b_name']); ?>
              </div>
            </td>
            <td>
              <div style="font-weight: 700; color: #fff;"><?php echo date('D, M d, Y', strtotime($m['match_date'])); ?></div>
              <div style="font-size: 0.78rem; color: var(--accent-amber); font-weight: 600;"><?php echo htmlspecialchars($m['start_time']); ?> IST</div>
            </td>
            <td>
              <div style="color: var(--text-main); font-weight: 600;">📍 <?php echo htmlspecialchars($m['venue_name']); ?></div>
              <div style="font-size: 0.75rem; color: var(--text-muted);">Umpire / Referee: Official Assigned</div>
            </td>
            <td>
              <span class="badge" style="background: rgba(245, 158, 11, 0.15); color: var(--accent-amber); font-weight: 800;">
                UPCOMING
              </span>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<script>
function filterUpcomingMatches() {
  const searchVal = document.getElementById('publicSearchInput').value.toLowerCase().trim();
  const sportVal  = document.getElementById('sportFilter').value.toLowerCase().trim();
  const dateVal   = document.getElementById('dateFilter').value.toLowerCase().trim();
  const venueVal  = document.getElementById('venueFilter').value.toLowerCase().trim();

  const rows = document.querySelectorAll('.upcoming-row');
  rows.forEach(row => {
    const rSport   = row.getAttribute('data-sport') || '';
    const rVenue   = row.getAttribute('data-venue') || '';
    const rDateCat = row.getAttribute('data-datecat') || '';
    const rSearch  = row.getAttribute('data-search') || '';

    const matchSearch = !searchVal || rSearch.includes(searchVal);
    const matchSport  = !sportVal || rSport.includes(sportVal);
    const matchVenue  = !venueVal || rVenue.includes(venueVal);
    const matchDate   = !dateVal || (dateVal === 'today' && rDateCat === 'today') || (dateVal === 'tomorrow' && rDateCat === 'tomorrow') || dateVal === 'week';

    if (matchSearch && matchSport && matchVenue && matchDate) {
      row.style.display = '';
    } else {
      row.style.display = 'none';
    }
  });
}
</script>

<?php include_once __DIR__ . '/../includes/public-footer.php'; ?>
