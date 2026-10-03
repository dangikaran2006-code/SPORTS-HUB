<?php
/**
 * SportsHub - Public Venues & Facilities Page
 */
$currentPage = 'venues';
$pageTitle = 'Championship Venues & Locations';

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';

$db = getDB();
$venues = $db->getVenues();

include_once __DIR__ . '/../includes/public-header.php';
?>

<div style="margin-bottom: 28px;">
  <h1 style="font-size: 1.8rem; font-weight: 800; color: #fff; margin-bottom: 4px;">🏟️ Venues & Grounds Directory</h1>
  <p style="color: var(--text-muted); font-size: 0.9rem;">College sports facilities, pitch allocations, and today's venue match schedules.</p>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px;">
  <?php foreach ($venues as $v): ?>
    <div style="background: var(--bg-card); border: 1px solid var(--border-subtle); border-radius: var(--radius-lg); padding: 20px;">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
        <span style="font-size: 1.5rem;">🏟️</span>
        <span class="badge" style="background: rgba(0, 230, 118, 0.15); color: var(--accent-green); font-weight: 700;">
          <?php echo htmlspecialchars($v['status']); ?>
        </span>
      </div>

      <h3 style="font-size: 1.2rem; font-weight: 800; color: #fff; margin-bottom: 4px;">
        <?php echo htmlspecialchars($v['name']); ?>
      </h3>
      <div style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 12px;">
        📍 <?php echo htmlspecialchars($v['location']); ?> &bull; Capacity: <?php echo number_format($v['capacity']); ?> Spectators
      </div>

      <div style="background: var(--bg-dark-surface); padding: 12px; border-radius: var(--radius-md); border: 1px solid var(--border-subtle);">
        <div style="font-size: 0.78rem; font-weight: 700; color: var(--accent-green); margin-bottom: 4px;">SUPPORTED SPORTS</div>
        <div style="font-size: 0.85rem; color: var(--text-main);">
          <?php echo htmlspecialchars($v['supported_sports']); ?>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<?php include_once __DIR__ . '/../includes/public-footer.php'; ?>
