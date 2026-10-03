<?php
/**
 * SportsHub - Officials Management & Assignment Module
 */
$currentPage = 'officials';
$pageTitle = 'Officials Management';

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
        $name    = trim($_POST['name'] ?? '');
        $role    = $_POST['role'] ?? 'Umpire';
        $sportId = intval($_POST['sport_id'] ?? 1);
        $phone   = trim($_POST['contact'] ?? '');

        if (!empty($name) && !empty($phone)) {
            $newId = insert('officials', [
                'name'     => $name,
                'role'     => $role,
                'sport_id' => $sportId,
                'phone'    => $phone,
                'status'   => 'Available'
            ]);
            logAuditAction('Official Registered', 'Official', $newId, "Registered official '{$name}' ({$role})");
            $msg = "Official <strong>" . htmlspecialchars($name) . "</strong> (" . htmlspecialchars($role) . ") registered successfully.";
        } else {
            $error = 'Please fill in official name and contact number.';
        }
    }
}

// Fetch active officials from MySQL DB
$officials = [];
if ($db->getConnection()) {
    $officials = fetchAll("
        SELECT o.*, s.name as sport_name,
               (SELECT COUNT(*) FROM matches m WHERE m.official_id = o.id) as match_count
        FROM officials o
        LEFT JOIN sports s ON o.sport_id = s.id
        ORDER BY o.id ASC
    ");
}

if (empty($officials)) {
    $officials = [
        ['id' => 1, 'name' => 'Prof. Rajesh Sharma', 'role' => 'Umpire', 'sport_name' => 'Cricket', 'phone' => '+91 98765 43210', 'status' => 'Available', 'match_count' => 4],
        ['id' => 2, 'name' => 'Dr. Suresh Patel', 'role' => 'Referee', 'sport_name' => 'Football', 'phone' => '+91 98765 43211', 'status' => 'Assigned', 'match_count' => 3],
        ['id' => 3, 'name' => 'Vikram Singh', 'role' => 'Scorer', 'sport_name' => 'Kabaddi', 'phone' => '+91 98765 43212', 'status' => 'Assigned', 'match_count' => 5],
    ];
}

// Fetch configured sports for dropdown
$sportsList = [];
if ($db->getConnection()) {
    $sportsList = fetchAll("SELECT id, name FROM sports ORDER BY name ASC");
}

include_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-header">
  <div class="dashboard-title-group">
    <h1>Officials Management & Assignments</h1>
    <p>Manage referees, umpires, scorers, judges, timekeepers, and prevent overlapping event assignments.</p>
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

<!-- Register Official Form -->
<div class="card" style="margin-bottom: 28px;">
  <div class="section-header" style="margin-bottom: 14px;">
    <h2>+ Register Championship Official</h2>
  </div>
  <form action="" method="POST" style="display: grid; grid-template-columns: 2fr 1fr 1fr 1fr 1fr; gap: 16px; align-items: end;">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">

    <div class="form-group" style="margin: 0;">
      <label>Official Name *</label>
      <input type="text" name="name" class="form-control" placeholder="e.g. Prof. Rajesh Sharma" required>
    </div>
    <div class="form-group" style="margin: 0;">
      <label>Official Role</label>
      <select name="role" class="form-control">
        <option value="Umpire">Umpire</option>
        <option value="Referee">Referee</option>
        <option value="Scorer">Scorer</option>
        <option value="Judge">Judge</option>
        <option value="Timekeeper">Timekeeper</option>
      </select>
    </div>
    <div class="form-group" style="margin: 0;">
      <label>Primary Sport</label>
      <select name="sport_id" class="form-control">
        <?php foreach ($sportsList as $sp): ?>
          <option value="<?php echo $sp['id']; ?>"><?php echo htmlspecialchars($sp['name']); ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group" style="margin: 0;">
      <label>Contact Phone *</label>
      <input type="text" name="contact" class="form-control" placeholder="+91 98765 00000" required>
    </div>
    <div>
      <button type="submit" class="btn btn-primary" style="width: 100%;">
        + Add Official
      </button>
    </div>
  </form>
</div>

<!-- Officials Registry Table -->
<div class="card">
  <div class="section-header" style="margin-bottom: 16px;">
    <h2>Official Roster & Assignments</h2>
  </div>
  <div class="table-responsive">
    <table class="sports-table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Official Name</th>
          <th>Role</th>
          <th>Assigned Sport</th>
          <th>Contact Number</th>
          <th>Total Assignments</th>
          <th>Availability Status</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($officials as $o): ?>
          <tr>
            <td><code>#<?php echo $o['id']; ?></code></td>
            <td><strong style="color: #fff;"><?php echo htmlspecialchars($o['name']); ?></strong></td>
            <td>
              <span class="badge" style="background: rgba(0, 230, 118, 0.15); color: var(--accent-green); font-weight: 700;">
                <?php echo htmlspecialchars($o['role']); ?>
              </span>
            </td>
            <td><?php echo getSportBadge($o['sport_name'] ?? 'General'); ?></td>
            <td><?php echo htmlspecialchars($o['phone'] ?? $o['contact'] ?? '-'); ?></td>
            <td><strong><?php echo (int)($o['match_count'] ?? $o['assignments'] ?? 0); ?> Matches</strong></td>
            <td>
              <span class="status-badge <?php echo ($o['status'] ?? 'Available') === 'Available' ? 'badge-active' : 'badge-scheduled'; ?>">
                <?php echo htmlspecialchars($o['status'] ?? 'Available'); ?>
              </span>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
