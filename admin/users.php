<?php
/**
 * SportsHub - Admin User Management Console
 */
$currentPage = 'users';
$pageTitle = 'User Management & Role Permissions';

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';

// Enforce Admin Role access only
requireRole('admin');

$db = getDB();
$message = '';
$error = '';
$csrfToken = generateCsrfToken();

// Handle Form Submissions (Create, Edit Role, Change Status, Reset Password, Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $token  = $_POST['csrf_token'] ?? '';

    if (!validateCsrfToken($token)) {
        $error = 'Security validation failed (Invalid CSRF Token).';
    } else {
        if ($action === 'create_user') {
            $name     = trim($_POST['name'] ?? '');
            $email    = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $role     = strtolower(trim($_POST['role'] ?? 'player'));

            if (empty($name) || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8) {
                $error = 'Please provide valid user details (Password min 8 chars).';
            } else {
                $existing = fetchOne("SELECT id FROM users WHERE email = :email", [':email' => $email]);
                if ($existing) {
                    $error = 'A user with this email already exists.';
                } else {
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    insert('users', [
                        'name'     => $name,
                        'email'    => $email,
                        'password' => $hashedPassword,
                        'role'     => $role,
                        'status'   => 1
                    ]);
                    $message = "User '{$name}' created successfully as {$role}.";
                }
            }
        } elseif ($action === 'change_status') {
            $userId    = intval($_POST['user_id'] ?? 0);
            $newStatus = intval($_POST['status'] ?? 1);
            if ($userId > 0) {
                update('users', ['status' => $newStatus], 'id = :id', [':id' => $userId]);
                $message = "User status updated successfully.";
            }
        } elseif ($action === 'change_role') {
            $userId  = intval($_POST['user_id'] ?? 0);
            $newRole = strtolower(trim($_POST['role'] ?? 'player'));
            if ($userId > 0) {
                update('users', ['role' => $newRole], 'id = :id', [':id' => $userId]);
                $message = "User role updated to {$newRole}.";
            }
        } elseif ($action === 'reset_password') {
            $userId      = intval($_POST['user_id'] ?? 0);
            $newPassword = $_POST['new_password'] ?? '';
            if ($userId > 0 && strlen($newPassword) >= 8) {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                update('users', ['password' => $hashedPassword], 'id = :id', [':id' => $userId]);
                $message = "Password reset successfully for user ID {$userId}.";
            } else {
                $error = "Password must be at least 8 characters.";
            }
        } elseif ($action === 'delete_user') {
            $userId = intval($_POST['user_id'] ?? 0);
            if ($userId > 1) { // Protect super admin ID 1
                delete('users', 'id = :id', [':id' => $userId]);
                $message = "User ID {$userId} deleted.";
            } else {
                $error = "Super Admin account cannot be deleted.";
            }
        }
    }
}

// Fetch users from DB or fallback
$usersList = [];
if ($db->getConnection()) {
    $usersList = fetchAll("SELECT id, name, email, role, status, created_at FROM users ORDER BY id ASC");
}

if (empty($usersList)) {
    $usersList = [
        ['id' => 1, 'name' => 'Alex Mercer', 'email' => 'admin@sportshub.com', 'role' => 'admin', 'status' => 1, 'created_at' => '2026-10-01 10:00:00'],
        ['id' => 2, 'name' => 'Sarah Jenkins', 'email' => 'organizer@sportshub.com', 'role' => 'organizer', 'status' => 1, 'created_at' => '2026-10-01 11:30:00'],
        ['id' => 3, 'name' => 'Nitin Menon', 'email' => 'scorer@sportshub.com', 'role' => 'scorer', 'status' => 1, 'created_at' => '2026-10-02 09:15:00'],
        ['id' => 4, 'name' => 'Pranjal Banerjee', 'email' => 'official@sportshub.com', 'role' => 'official', 'status' => 1, 'created_at' => '2026-10-02 14:20:00'],
        ['id' => 5, 'name' => 'Vikram Rathore', 'email' => 'manager@sportshub.com', 'role' => 'team_manager', 'status' => 1, 'created_at' => '2026-10-03 08:00:00'],
        ['id' => 6, 'name' => 'Rohit Sharma', 'email' => 'player@sportshub.com', 'role' => 'player', 'status' => 1, 'created_at' => '2026-10-03 12:45:00']
    ];
}

include_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-header">
  <div class="dashboard-title-group">
    <h1>User Management & Permissions</h1>
    <p>Manage platform accounts, role privileges, account statuses, and passwords.</p>
  </div>
  <div class="quick-actions-bar">
    <button onclick="openModal('addUserModal')" class="btn btn-primary">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
      <span>+ Add User</span>
    </button>
  </div>
</div>

<?php if (!empty($message)): ?>
  <div class="auth-alert auth-alert-success" style="margin-bottom:20px;">
    <span><?php echo htmlspecialchars($message); ?></span>
  </div>
<?php endif; ?>

<?php if (!empty($error)): ?>
  <div class="auth-alert auth-alert-danger" style="margin-bottom:20px;">
    <span><?php echo htmlspecialchars($error); ?></span>
  </div>
<?php endif; ?>

<div class="card">
  <div class="table-responsive">
    <table class="sports-table">
      <thead>
        <tr>
          <th>User & Name</th>
          <th>Email Address</th>
          <th>Role</th>
          <th>Account Status</th>
          <th>Created Date</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($usersList as $u): ?>
          <tr data-searchable>
            <td>
              <div style="display:flex; align-items:center; gap:10px;">
                <div style="width:34px; height:34px; border-radius:50%; background:linear-gradient(135deg, #00e676, #3b82f6); display:flex; align-items:center; justify-content:center; font-weight:700; color:#000;">
                  <?php echo strtoupper(substr($u['name'], 0, 1)); ?>
                </div>
                <strong style="color:var(--text-main); font-size:0.95rem;"><?php echo htmlspecialchars($u['name']); ?></strong>
              </div>
            </td>
            <td><?php echo htmlspecialchars($u['email']); ?></td>
            <td>
              <span class="sport-badge badge-generic" style="font-size:0.75rem; text-transform:uppercase; font-weight:700;">
                <?php echo htmlspecialchars($u['role']); ?>
              </span>
            </td>
            <td>
              <?php if ($u['status'] == 1): ?>
                <span class="status-badge badge-active">Active</span>
              <?php else: ?>
                <span class="status-badge badge-danger">Disabled</span>
              <?php endif; ?>
            </td>
            <td><?php echo date('M d, Y', strtotime($u['created_at'])); ?></td>
            <td>
              <div style="display:flex; gap:6px;">
                <!-- Toggle Status Form -->
                <form action="" method="POST" style="display:inline;">
                  <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                  <input type="hidden" name="action" value="change_status">
                  <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                  <input type="hidden" name="status" value="<?php echo ($u['status'] == 1) ? 0 : 1; ?>">
                  <button type="submit" class="btn btn-secondary btn-sm">
                    <?php echo ($u['status'] == 1) ? 'Deactivate' : 'Activate'; ?>
                  </button>
                </form>

                <!-- Reset Password Trigger -->
                <button class="btn btn-secondary btn-sm" onclick="promptResetPassword(<?php echo $u['id']; ?>, '<?php echo htmlspecialchars(addslashes($u['name'])); ?>')">
                  Reset Password
                </button>

                <!-- Delete User Form -->
                <?php if ($u['id'] > 1): ?>
                  <form action="" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete user <?php echo htmlspecialchars(addslashes($u['name'])); ?>?');">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                    <input type="hidden" name="action" value="delete_user">
                    <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                    <button type="submit" class="btn btn-secondary btn-sm" style="color:var(--accent-red);">Delete</button>
                  </form>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal: Add New User -->
<div class="modal-overlay" id="addUserModal">
  <div class="modal-container">
    <div class="modal-header">
      <h2>Add New User</h2>
      <button class="modal-close-btn" onclick="closeModal('addUserModal')">&times;</button>
    </div>
    
    <form action="users.php" method="POST">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
      <input type="hidden" name="action" value="create_user">

      <div class="form-group">
        <label>Full Name *</label>
        <input type="text" name="name" class="form-control" placeholder="e.g. Nitin Menon" required>
      </div>

      <div class="form-group">
        <label>Email Address *</label>
        <input type="email" name="email" class="form-control" placeholder="user@sportshub.com" required>
      </div>

      <div class="form-group">
        <label>Password (Min 8 Characters) *</label>
        <input type="password" name="password" class="form-control" placeholder="••••••••" required>
      </div>

      <div class="form-group">
        <label>User Role *</label>
        <select name="role" class="form-control" required>
          <option value="admin">Administrator</option>
          <option value="organizer">Tournament Organizer</option>
          <option value="scorer">Official Match Scorer</option>
          <option value="official">Match Official / Referee</option>
          <option value="team_manager">Team Manager</option>
          <option value="player" selected>Athlete / Player</option>
        </select>
      </div>

      <div style="display:flex; justify-content:flex-end; gap:12px; margin-top:20px;">
        <button type="button" class="btn btn-secondary" onclick="closeModal('addUserModal')">Cancel</button>
        <button type="submit" class="btn btn-primary">Create User</button>
      </div>
    </form>
  </div>
</div>

<!-- Hidden Reset Password Form -->
<form id="resetPasswordForm" action="users.php" method="POST" style="display:none;">
  <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
  <input type="hidden" name="action" value="reset_password">
  <input type="hidden" name="user_id" id="resetUserId">
  <input type="hidden" name="new_password" id="resetNewPassword">
</form>

<script>
function openModal(id) { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }

function promptResetPassword(userId, userName) {
  const newPwd = prompt(`Enter new password for ${userName} (Min 8 characters):`);
  if (newPwd && newPwd.length >= 8) {
    document.getElementById('resetUserId').value = userId;
    document.getElementById('resetNewPassword').value = newPwd;
    document.getElementById('resetPasswordForm').submit();
  } else if (newPwd !== null) {
    alert('Password must be at least 8 characters.');
  }
}
</script>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
