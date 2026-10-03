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
$sportsList = [
    ['id' => 1, 'name' => 'Cricket', 'type' => 'TEAM', 'category' => 'Men', 'format' => 'MATCH', 'scoring' => 'Runs / Wickets / Overs', 'status' => 'Active'],
    ['id' => 2, 'name' => 'Football', 'type' => 'TEAM', 'category' => 'Men', 'format' => 'MATCH', 'scoring' => 'Goals / Half / Penalties', 'status' => 'Active'],
    ['id' => 3, 'name' => 'Kabaddi', 'type' => 'TEAM', 'category' => 'Men', 'format' => 'MATCH', 'scoring' => 'Raid / Tackle / Super Points', 'status' => 'Active'],
    ['id' => 4, 'name' => 'Basketball', 'type' => 'TEAM', 'category' => 'Men & Women', 'format' => 'MATCH', 'scoring' => 'Quarter Points (1/2/3)', 'status' => 'Active'],
    ['id' => 5, 'name' => 'Volleyball', 'type' => 'TEAM', 'category' => 'Open', 'format' => 'MATCH', 'scoring' => 'Set Scores (Best of 3)', 'status' => 'Active'],
    ['id' => 6, 'name' => 'Badminton', 'type' => 'INDIVIDUAL', 'category' => 'Singles & Doubles', 'format' => 'ROUND', 'scoring' => 'Rally Points / Games', 'status' => 'Active'],
    ['id' => 7, 'name' => 'Tennis', 'type' => 'INDIVIDUAL', 'category' => 'Open', 'format' => 'ROUND', 'scoring' => 'Games / Sets', 'status' => 'Active'],
    ['id' => 8, 'name' => 'Table Tennis', 'type' => 'INDIVIDUAL', 'category' => 'Open', 'format' => 'ROUND', 'scoring' => '11-Point Sets', 'status' => 'Active'],
    ['id' => 9, 'name' => 'Athletics (100m, Relay)', 'type' => 'RELAY', 'category' => 'Individual & Relay', 'format' => 'HEAT', 'scoring' => 'Time (sec) / Distance (m)', 'status' => 'Active'],
    ['id' => 10, 'name' => 'Chess', 'type' => 'INDIVIDUAL', 'category' => 'Open', 'format' => 'ROUND', 'scoring' => 'Win (1) / Draw (0.5) / Loss (0)', 'status' => 'Active'],
];

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['sport_name'] ?? '');
    $type = $_POST['sport_type'] ?? 'TEAM';
    $cat  = $_POST['category'] ?? 'Open';
    if (!empty($name)) {
        $sportsList[] = [
            'id' => count($sportsList) + 1,
            'name' => $name,
            'type' => $type,
            'category' => $cat,
            'format' => 'MATCH',
            'scoring' => 'Custom Points',
            'status' => 'Active'
        ];
        $msg = "New sport <strong>" . htmlspecialchars($name) . "</strong> registered successfully.";
    }
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
  <div class="auth-alert auth-alert-success" style="margin-bottom: 24px; background: rgba(0, 230, 118, 0.15); border: 1px solid rgba(0, 230, 118, 0.3); color: var(--accent-green); padding: 12px 16px; border-radius: 8px;">
    <span><?php echo $msg; ?></span>
  </div>
<?php endif; ?>

<!-- Register New Sport Form -->
<div class="card" style="margin-bottom: 28px;">
  <div class="section-header" style="margin-bottom: 14px;">
    <h2>+ Add New Sport Event</h2>
  </div>
  <form action="" method="POST" style="display: grid; grid-template-columns: 2fr 1fr 1fr 1fr; gap: 16px; align-items: end;">
    <div class="form-group" style="margin: 0;">
      <label>Sport Name</label>
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
          <th>Event Format</th>
          <th>Scoring Engine Mode</th>
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
                <?php echo htmlspecialchars($s['type']); ?>
              </span>
            </td>
            <td><?php echo htmlspecialchars($s['category']); ?></td>
            <td><code><?php echo htmlspecialchars($s['format']); ?></code></td>
            <td><span style="font-size: 0.85rem; color: var(--text-muted);"><?php echo htmlspecialchars($s['scoring']); ?></span></td>
            <td><span class="status-badge badge-active">Active</span></td>
            <td>
              <a href="<?php echo BASE_URL; ?>/admin/points.php" class="btn btn-secondary btn-sm">
                Point Rules
              </a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
