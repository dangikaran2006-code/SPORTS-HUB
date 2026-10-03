<?php
/**
 * SportsHub - Central Sports Master & Configuration Module
 */
$currentPage = 'sports';
$pageTitle = 'Sports Master & Configuration';

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
        $name = trim($_POST['sport_name'] ?? '');
        $type = $_POST['sport_type'] ?? 'TEAM';
        $cat  = $_POST['category'] ?? 'Open';
        
        if (!empty($name)) {
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));
            $newId = insert('sports', [
                'name'     => $name,
                'slug'     => $slug,
                'type'     => $type,
                'category' => $cat,
                'icon'     => '🏆',
                'status'   => 'active'
            ]);
            logAuditAction('Sport Created', 'Sport', $newId, "Registered new sport '{$name}' ({$type})");
            $msg = "New sport <strong>" . htmlspecialchars($name) . "</strong> registered successfully.";
        } else {
            $error = 'Please enter a valid sport name.';
        }
    }
}

// Fetch configured sports from MySQL DB
$sportsList = [];
if ($db->getConnection()) {
    $sportsList = fetchAll("SELECT * FROM sports ORDER BY id ASC");
}

if (empty($sportsList)) {
    $sportsList = [
        ['id' => 1, 'name' => 'Cricket', 'type' => 'TEAM', 'category' => 'Men', 'format' => 'MATCH', 'scoring' => 'Runs / Wickets / Overs', 'status' => 'active'],
        ['id' => 2, 'name' => 'Football', 'type' => 'TEAM', 'category' => 'Men', 'format' => 'MATCH', 'scoring' => 'Goals / Half / Penalties', 'status' => 'active'],
        ['id' => 3, 'name' => 'Kabaddi', 'type' => 'TEAM', 'category' => 'Men', 'format' => 'MATCH', 'scoring' => 'Raid / Tackle / Super Points', 'status' => 'active'],
        ['id' => 4, 'name' => 'Basketball', 'type' => 'TEAM', 'category' => 'Men & Women', 'format' => 'MATCH', 'scoring' => 'Quarter Points (1/2/3)', 'status' => 'active'],
        ['id' => 5, 'name' => 'Volleyball', 'type' => 'TEAM', 'category' => 'Open', 'format' => 'MATCH', 'scoring' => 'Set Scores (Best of 3)', 'status' => 'active'],
    ];
}

include_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-header">
  <div class="dashboard-title-group">
    <h1>Sports Master & Event Configuration</h1>
    <p>Configure championship sports, event formats (MATCH, RACE, HEAT, ROUND), categories, and scoring engines.</p>
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

<!-- Register New Sport Form -->
<div class="card" style="margin-bottom: 28px;">
  <div class="section-header" style="margin-bottom: 14px;">
    <h2>+ Add New Sport Event</h2>
  </div>
  <form action="" method="POST" style="display: grid; grid-template-columns: 2fr 1fr 1fr 1fr; gap: 16px; align-items: end;">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">

    <div class="form-group" style="margin: 0;">
      <label>Sport Name *</label>
      <input type="text" name="sport_name" class="form-control" placeholder="e.g. Swimming, Archery, Hockey" required>
    </div>

    <div class="form-group" style="margin: 0;">
      <label>Sport Type</label>
      <select name="sport_type" class="form-control">
        <option value="TEAM">TEAM</option>
        <option value="INDIVIDUAL">INDIVIDUAL</option>
        <option value="RELAY">RELAY</option>
        <option value="EVENT">EVENT</option>
      </select>
    </div>

    <div class="form-group" style="margin: 0;">
      <label>Category</label>
      <select name="category" class="form-control">
        <option value="Men">Men</option>
        <option value="Women">Women</option>
        <option value="Mixed">Mixed</option>
        <option value="Open" selected>Open</option>
      </select>
    </div>

    <div>
      <button type="submit" class="btn btn-primary" style="width: 100%;">
        + Register Sport
      </button>
    </div>
  </form>
</div>

<!-- Sports Directory Master Table -->
<div class="card">
  <div class="section-header" style="margin-bottom: 16px;">
    <h2>Configured Championship Sports Registry</h2>
  </div>
  <div class="table-responsive">
    <table class="sports-table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Sport Name</th>
          <th>Type</th>
          <th>Category</th>
          <th>Status</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($sportsList as $s): ?>
          <tr>
            <td><code>#<?php echo $s['id']; ?></code></td>
            <td><strong style="color: #fff;"><?php echo htmlspecialchars($s['name']); ?></strong></td>
            <td>
              <span class="badge" style="background: rgba(0, 230, 118, 0.15); color: var(--accent-green); font-weight: 700;">
                <?php echo htmlspecialchars($s['type'] ?? 'TEAM'); ?>
              </span>
            </td>
            <td><?php echo htmlspecialchars($s['category'] ?? 'Open'); ?></td>
            <td><span class="status-badge badge-active"><?php echo ucfirst(htmlspecialchars($s['status'] ?? 'active')); ?></span></td>
            <td>
              <a href="<?php echo BASE_URL; ?>/public/sport-detail.php?slug=<?php echo urlencode($s['slug'] ?? strtolower($s['name'])); ?>" class="btn btn-secondary btn-sm">
                View Detail →
              </a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
