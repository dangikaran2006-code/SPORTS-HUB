<?php
/**
 * SportsHub - Venues Management Page
 */
$currentPage = 'venues';
$pageTitle = 'Venues Registry';

require_once __DIR__ . '/../includes/database.php';
$db = getDB();

include_once __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-header">
  <div class="dashboard-title-group">
    <h1>Venues & Stadiums</h1>
    <p>Manage arena capacities, surface types, and geographical locations.</p>
  </div>
</div>

<div class="stats-grid">
  <div class="card">
    <h3 style="font-size:1.1rem; margin-bottom:8px;">Apex Sports Complex</h3>
    <p style="color:var(--text-muted); font-size:0.85rem; margin-bottom:12px;">Mumbai, Maharashtra &bull; Turf & Hardcourt</p>
    <div style="font-size:0.8rem; color:var(--accent-green); font-weight:600;">Capacity: 25,000 Seats</div>
  </div>
  <div class="card">
    <h3 style="font-size:1.1rem; margin-bottom:8px;">Grand National Arena</h3>
    <p style="color:var(--text-muted); font-size:0.85rem; margin-bottom:12px;">Bengaluru, Karnataka &bull; Natural Grass</p>
    <div style="font-size:0.8rem; color:var(--accent-green); font-weight:600;">Capacity: 35,000 Seats</div>
  </div>
  <div class="card">
    <h3 style="font-size:1.1rem; margin-bottom:8px;">Metro Indoor Stadium</h3>
    <p style="color:var(--text-muted); font-size:0.85rem; margin-bottom:12px;">New Delhi, Delhi &bull; Wooden Flooring</p>
    <div style="font-size:0.8rem; color:var(--accent-green); font-weight:600;">Capacity: 12,000 Seats</div>
  </div>
</div>
<?php include_once __DIR__ . '/../includes/footer.php'; ?>
