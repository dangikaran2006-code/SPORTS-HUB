<?php
/**
 * SportsHub - Public Sports Directory Page
 */
$currentPage = 'sports';
$pageTitle = 'Championship Sports Directory';

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';

$db = getDB();
$sportsList = [
    ['name' => 'Cricket', 'type' => 'Team', 'icon' => '🏏', 'teams' => 6, 'category' => 'Men & Women', 'status' => 'Ongoing'],
    ['name' => 'Football', 'type' => 'Team', 'icon' => '⚽', 'teams' => 6, 'category' => 'Men', 'status' => 'Ongoing'],
    ['name' => 'Kabaddi', 'type' => 'Team', 'icon' => '🤼', 'teams' => 6, 'category' => 'Men', 'status' => 'Ongoing'],
    ['name' => 'Basketball', 'type' => 'Team', 'icon' => '🏀', 'teams' => 6, 'category' => 'Men & Women', 'status' => 'Ongoing'],
    ['name' => 'Volleyball', 'type' => 'Team', 'icon' => '🏐', 'teams' => 6, 'category' => 'Open', 'status' => 'Upcoming'],
    ['name' => 'Badminton', 'type' => 'Individual / Pair', 'icon' => '🏸', 'teams' => 12, 'category' => 'Singles & Doubles', 'status' => 'Ongoing'],
    ['name' => 'Tennis', 'type' => 'Racket', 'icon' => '🎾', 'teams' => 8, 'category' => 'Singles', 'status' => 'Upcoming'],
    ['name' => 'Table Tennis', 'type' => 'Racket', 'icon' => '🏓', 'teams' => 10, 'category' => 'Open', 'status' => 'Completed'],
    ['name' => 'Athletics (100m, Relay, Jump)', 'type' => 'Track & Field', 'icon' => '🏃', 'teams' => 24, 'category' => 'Individual & Relay', 'status' => 'Ongoing'],
    ['name' => 'Chess', 'type' => 'Board', 'icon' => '♟️', 'teams' => 12, 'category' => 'Open', 'status' => 'Ongoing'],
];

include_once __DIR__ . '/../includes/public-header.php';
?>

<div style="margin-bottom: 28px;">
  <h1 style="font-size: 1.8rem; font-weight: 800; color: #fff; margin-bottom: 4px;">⚽ Championship Sports Directory</h1>
  <p style="color: var(--text-muted); font-size: 0.9rem;">Explore all 10 sports events featured in the College Inter-Department Championship 2026.</p>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px;">
  <?php foreach ($sportsList as $s): ?>
    <div style="background: var(--bg-card); border: 1px solid var(--border-subtle); border-radius: var(--radius-lg); padding: 20px; transition: transform 0.2s ease;" onmouseover="this.style.transform='translateY(-4px)'" onmouseout="this.style.transform='none'">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
        <span style="font-size: 2.5rem;"><?php echo $s['icon']; ?></span>
        <span class="badge" style="background: rgba(0, 230, 118, 0.15); color: var(--accent-green); font-weight: 700;">
          <?php echo htmlspecialchars($s['type']); ?>
        </span>
      </div>

      <h3 style="font-size: 1.2rem; font-weight: 800; color: #fff; margin-bottom: 6px;">
        <?php echo htmlspecialchars($s['name']); ?>
      </h3>
      <div style="font-size: 0.82rem; color: var(--text-muted); margin-bottom: 16px;">
        Category: <strong style="color: var(--text-main);"><?php echo htmlspecialchars($s['category']); ?></strong> &bull; <?php echo $s['teams']; ?> Participants
      </div>

      <div style="display: flex; gap: 8px;">
        <a href="<?php echo BASE_URL; ?>/public/points-table.php" class="btn btn-secondary btn-sm" style="flex: 1; text-align: center;">
          📊 Standings
        </a>
        <a href="<?php echo BASE_URL; ?>/public/upcoming.php" class="btn btn-secondary btn-sm" style="flex: 1; text-align: center;">
          📅 Fixtures
        </a>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<?php include_once __DIR__ . '/../includes/public-footer.php'; ?>
