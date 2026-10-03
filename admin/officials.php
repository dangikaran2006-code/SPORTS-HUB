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

$officials = [
    ['id' => 1, 'name' => 'Prof. Rajesh Sharma', 'role' => 'Umpire', 'sport' => 'Cricket', 'contact' => '+91 98765 43210', 'status' => 'Available', 'assignments' => 4],
    ['id' => 2, 'name' => 'Dr. Suresh Patel', 'role' => 'Referee', 'sport' => 'Football', 'contact' => '+91 98765 43211', 'status' => 'Assigned', 'assignments' => 3],
    ['id' => 3, 'name' => 'Vikram Singh', 'role' => 'Scorer', 'sport' => 'Kabaddi', 'contact' => '+91 98765 43212', 'status' => 'Assigned', 'assignments' => 5],
    ['id' => 4, 'name' => 'Amit Kumar', 'role' => 'Judge', 'sport' => 'Athletics', 'contact' => '+91 98765 43213', 'status' => 'Available', 'assignments' => 2],
    ['id' => 5, 'name' => 'Priya Nair', 'role' => 'Timekeeper', 'sport' => 'Swimming', 'contact' => '+91 98765 43214', 'status' => 'Available', 'assignments' => 2],
];

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $role = $_POST['role'] ?? 'Umpire';
    $sport = $_POST['sport'] ?? 'Cricket';
    $contact = trim($_POST['contact'] ?? '');
    if (!empty($name)) {
        $officials[] = [
            'id' => count($officials) + 1,
            'name' => $name,
            'role' => $role,
            'sport' => $sport,
            'contact' => $contact,
            'status' => 'Available',
            'assignments' => 0
        ];
        $msg = "Official <strong>" . htmlspecialchars($name) . "</strong> (" . htmlspecialchars($role) . ") registered successfully.";
    }
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
  <div class="auth-alert auth-alert-success" style="margin-bottom: 24px; background: rgba(0, 230, 118, 0.15); border: 1px solid rgba(0, 230, 118, 0.3); color: var(--accent-green); padding: 12px 16px; border-radius: 8px;">
    <span><?php echo $msg; ?></span>
  </div>
<?php endif; ?>

<!-- Register Official Form -->
<div class="card" style="margin-bottom: 28px;">
  <div class="section-header" style="margin-bottom: 14px;">
    <h2>+ Register Championship Official</h2>
  </div>
  <form action="" method="POST" style="display: grid; grid-template-columns: 2fr 1fr 1fr 1fr 1fr; gap: 16px; align-items: end;">
    <div class="form-group" style="margin: 0;">
      <label>Official Name</label>
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
      <select name="sport" class="form-control">
        <option value="Cricket">Cricket</option>
        <option value="Football">Football</option>
        <option value="Kabaddi">Kabaddi</option>
        <option value="Basketball">Basketball</option>
        <option value="Athletics">Athletics</option>
      </select>
    </div>
    <div class="form-group" style="margin: 0;">
      <label>Contact Phone</label>
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
            <td><?php echo htmlspecialchars($o['sport']); ?></td>
            <td><?php echo htmlspecialchars($o['contact']); ?></td>
            <td><strong><?php echo $o['assignments']; ?> Matches</strong></td>
            <td>
              <span class="status-badge <?php echo $o['status'] === 'Available' ? 'badge-active' : 'badge-scheduled'; ?>">
                <?php echo htmlspecialchars($o['status']); ?>
              </span>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
