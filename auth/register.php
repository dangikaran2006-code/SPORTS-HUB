<?php
/**
 * SportsHub - User Registration Portal
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: ' . BASE_URL . '/admin/dashboard.php');
    exit;
}

$error = '';
$success = '';
$csrfToken = generateCsrfToken();

// Allowed self-registration roles (ADMIN role is strictly prohibited from public self-registration)
$allowedRoles = ['organizer', 'scorer', 'official', 'team_manager', 'player'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token           = $_POST['csrf_token'] ?? '';
    $name            = trim($_POST['name'] ?? '');
    $email           = trim($_POST['email'] ?? '');
    $password        = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $role            = strtolower(trim($_POST['role'] ?? 'player'));

    if (!validateCsrfToken($token)) {
        $error = 'Invalid security token. Please try again.';
    } elseif (empty($name)) {
        $error = 'Full Name is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Password confirmation does not match.';
    } elseif (!in_array($role, $allowedRoles)) {
        $error = 'Invalid role selected. Admin accounts cannot be self-registered.';
    } else {
        $db = getDB();
        
        if ($db->getConnection()) {
            $existing = fetchOne("SELECT id FROM users WHERE email = :email", [':email' => $email]);
            if ($existing) {
                $error = 'An account with this email address already exists.';
            } else {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $insertedId = insert('users', [
                    'name'     => $name,
                    'email'    => $email,
                    'password' => $hashedPassword,
                    'role'     => $role,
                    'status'   => 1
                ]);

                if ($insertedId) {
                    $success = 'Account created successfully! You can now log in.';
                } else {
                    $error = 'Failed to create account. Please try again.';
                }
            }
        } else {
            $success = 'Demo Mode: Account initialized! You can log in using your credentials.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register Account | <?php echo APP_NAME; ?></title>
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/auth.css">
</head>
<body class="auth-body">

<div class="auth-container" style="max-width:480px;">
  <div class="auth-header">
    <div class="auth-brand-logo">
      <div class="brand-logo-icon" style="width:44px; height:44px; font-size:1.5rem;">S</div>
      <div class="brand-title" style="font-size:1.6rem;">Sports<span>Hub</span></div>
    </div>
    <div class="auth-title">Create an Account</div>
    <div class="auth-subtitle">Register as Organizer, Scorer, Official, Manager or Player</div>
  </div>

  <?php if (!empty($error)): ?>
    <div class="auth-alert auth-alert-danger">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      <span><?php echo htmlspecialchars($error); ?></span>
    </div>
  <?php endif; ?>

  <?php if (!empty($success)): ?>
    <div class="auth-alert auth-alert-success">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
      <span><?php echo htmlspecialchars($success); ?></span>
    </div>
  <?php endif; ?>

  <!-- Google Sign In Button -->
  <a href="<?php echo BASE_URL; ?>/auth/login.php?google_login=1" class="btn-google">
    <svg width="20" height="20" viewBox="0 0 24 24">
      <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
      <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
      <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
      <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
    </svg>
    Sign up with Google
  </a>

  <div class="auth-divider">
    <span>OR REGISTER WITH EMAIL</span>
  </div>

  <form action="" method="POST">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">

    <div class="form-group">
      <label>Full Name *</label>
      <input type="text" name="name" class="form-control" placeholder="e.g. Sarah Jenkins" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
    </div>

    <div class="form-group">
      <label>Email Address *</label>
      <input type="email" name="email" class="form-control" placeholder="name@example.com" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label>Password (Min 8 Chars) *</label>
        <input type="password" name="password" class="form-control" placeholder="••••••••" required>
      </div>

      <div class="form-group">
        <label>Confirm Password *</label>
        <input type="password" name="confirm_password" class="form-control" placeholder="••••••••" required>
      </div>
    </div>

    <div class="form-group">
      <label>Register As (Role) *</label>
      <select name="role" class="form-control" required>
        <option value="organizer">Tournament Organizer</option>
        <option value="scorer">Official Match Scorer</option>
        <option value="official">Match Official / Referee</option>
        <option value="team_manager">Team Manager</option>
        <option value="player" selected>Athlete / Player</option>
      </select>
    </div>

    <button type="submit" class="btn btn-primary" style="width:100%; padding:12px; margin-top:8px;">
      Register Account
    </button>
  </form>

  <div class="auth-footer-links">
    Already have an account? <a href="<?php echo BASE_URL; ?>/auth/login.php">Sign In here</a>
  </div>
</div>

</body>
</html>

