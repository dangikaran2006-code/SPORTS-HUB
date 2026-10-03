<?php
/**
 * SportsHub - Venues Management Page
 */
$currentPage = 'venues';
$pageTitle = 'Venues Registry & Capacity Control';

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdminAccess();

$db = getDB();
$msg = '';
$error = '';
$csrfToken = generateCsrfToken();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!validateCsrfToken($token)) {
        $error = 'Security validation failed (Invalid CSRF Token).';
    } else {
        $name     = trim($_POST['name'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $capacity = intval($_POST['capacity'] ?? 0);

        if (!empty($name) && !empty($location)) {
            $newId = insert('venues', [
                'name'     => $name,
                'location' => $location,
                'capacity' => $capacity
            ]);
            logAuditAction('Venue Created', 'Venue', $newId, "Added venue '{$name}' at {$location}");
            $msg = "Venue <strong>" . htmlspecialchars($name) . "</strong> registered successfully.";
        } else {
            $error = 'Please enter both venue name and location.';
        }
    }
}

// Fetch active venues from MySQL DB
$venues = [];
if ($db->getConnection()) {
    $venues = fetchAll("SELECT * FROM venues ORDER BY id ASC");
}

if (empty($venues)) {
    $venues = [
        ['id' => 1, 'name' => 'Apex Sports Complex', 'location' => 'Main College Campus, Field A', 'capacity' => 5000],
        ['id' => 2, 'name' => 'Grand National Arena', 'location' => 'Outdoor Track & Stadium', 'capacity' => 10000],
        ['id' => 3, 'name' => 'Metro Indoor Stadium', 'location' => 'Indoor Gymnasium Complex', 'capacity' => 3000],
    ];
}

include_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-header">
  <div class="dashboard-title-group">
    <h1>Venues & Stadiums Registry</h1>
    <p>Manage championship grounds, indoor courts, capacities, and venue schedules.</p>
  </div>
</div>

<?php if (!empty($msg)): ?>
  <div class="auth-alert auth-alert-success" style="margin-bottom: 24px;">
    <span><?php echo $msg; ?></span>
  </div>
<?php endif; ?>

<?php if (!empty($error)): ?>
  <div class="auth-alert auth-alert-danger" style="margin-bottom: 24px;">
    <span><?php echo htmlspecialchars($error); ?></span>
  </div>
<?php endif; ?>

<!-- Register New Venue Form -->
<div class="card" style="margin-bottom: 28px;">
  <div class="section-header" style="margin-bottom: 14px;">
    <h2>+ Add New Championship Venue</h2>
  </div>
  <form action="" method="POST" style="display: grid; grid-template-columns: 2fr 2fr 1fr 1fr; gap: 16px; align-items: end;">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">

    <div class="form-group" style="margin: 0;">
      <label>Venue / Ground Name *</label>
      <input type="text" name="name" class="form-control" placeholder="e.g. Indoor Complex Court 2" required>
    </div>

    <div class="form-group" style="margin: 0;">
      <label>Location Details *</label>
      <input type="text" name="location" class="form-control" placeholder="e.g. Main Campus Block B" required>
    </div>

    <div class="form-group" style="margin: 0;">
      <label>Spectator Capacity</label>
      <input type="number" name="capacity" class="form-control" placeholder="2500" value="1000">
    </div>

    <div>
      <button type="submit" class="btn btn-primary" style="width: 100%;">
        + Add Venue
      </button>
    </div>
  </form>
</div>

<!-- Venues Cards Grid -->
<div class="stats-grid" style="grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;">
  <?php foreach ($venues as $v): ?>
    <div class="card" style="display:flex; flex-direction:column; justify-space-between;">
      <div>
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
          <h3 style="font-size:1.15rem; margin:0; color:#fff;"><?php echo htmlspecialchars($v['name']); ?></h3>
          <span class="status-badge badge-active">Active</span>
        </div>
        <p style="color:var(--text-muted); font-size:0.85rem; margin-bottom:12px;">📍 <?php echo htmlspecialchars($v['location']); ?></p>
      </div>
      <div style="display:flex; justify-content:space-between; align-items:center; border-top:1px solid var(--border-subtle); padding-top:10px; margin-top:10px;">
        <span style="font-size:0.82rem; color:var(--accent-green); font-weight:700;">Capacity: <?php echo number_format($v['capacity'] ?? 0); ?> Seats</span>
        <a href="<?php echo BASE_URL; ?>/public/venues.php" class="btn btn-secondary btn-sm">View Schedule →</a>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
