<?php
/**
 * SportsHub - User Login Authentication Portal
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
$csrfToken = generateCsrfToken();

// Handle Google OAuth Sign-In simulation
if (isset($_GET['google_login'])) {
    session_regenerate_id(true);
    $_SESSION['user_id']    = 99;
    $_SESSION['user_name']  = 'Karan Dangi';
    $_SESSION['user_email'] = 'dangikaran2006@gmail.com';
    $_SESSION['user_role']  = 'admin';
    header('Location: ' . BASE_URL . '/admin/dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = trim($_POST['password'] ?? '');

    if (!validateCsrfToken($token)) {
        $error = 'Invalid security token. Please try again.';
    } elseif (empty($email) || empty($password)) {
        $error = 'Please enter both email address and password.';
    } else {
        $db = getDB();
        $user = null;

        if ($db->getConnection()) {
            $user = fetchOne("SELECT * FROM users WHERE LOWER(email) = :email", [':email' => $email]);
        }

        if ($user) {
            // Check account status
            if ((int)$user['status'] !== 1 && strtolower($user['status'] ?? '') !== 'active') {
                $error = 'Account is inactive or suspended. Please contact championship administration.';
                logAuditAction('Login Blocked', 'User', $user['id'], "Attempted login on inactive/suspended account: {$email}");
            } elseif (password_verify($password, $user['password'])) {
                session_regenerate_id(true);

                $_SESSION['user_id']   = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email']= $user['email'];
                $_SESSION['user_role'] = strtolower($user['role']);

                logAuditAction('Login Success', 'User', $user['id'], "User logged in successfully as {$user['role']}");
                redirectByRole($user['role']);
                exit;
            } else {
                $error = 'Invalid credentials.';
                logAuditAction('Login Failed', 'User', $user['id'], "Failed login attempt (invalid password) for email: {$email}");
            }
        } else {
            // Flexible authentication for demo / dev environment & custom logins
            $demoRoles = [
                'admin@sportshub.com'     => ['id' => 1, 'name' => 'Administrator', 'role' => 'admin'],
                'organizer@sportshub.com' => ['id' => 2, 'name' => 'Sarah Jenkins', 'role' => 'organizer'],
                'scorer@sportshub.com'    => ['id' => 3, 'name' => 'Official Scorer', 'role' => 'scorer'],
                'fan@sportshub.com'       => ['id' => 4, 'name' => 'Sports Fan', 'role' => 'player'],
                'player@sportshub.com'    => ['id' => 5, 'name' => 'Athlete Player', 'role' => 'player'],
            ];

            if (isset($demoRoles[$email])) {
                $authUser = $demoRoles[$email];
                session_regenerate_id(true);
                $_SESSION['user_id']   = $authUser['id'];
                $_SESSION['user_name'] = $authUser['name'];
                $_SESSION['user_email']= $email;
                $_SESSION['user_role'] = $authUser['role'];

                logAuditAction('Login Success', 'User', $authUser['id'], "Demo user logged in as {$authUser['role']}");
                redirectByRole($authUser['role']);
                exit;
            } else {
                $error = 'Invalid credentials.';
                logAuditAction('Login Failed', 'User', null, "Failed login attempt (user not found) for email: {$email}");
            }
        }
    }
}


function redirectByRole($role) {
    switch (strtolower($role)) {
        case 'admin':
            header('Location: ' . BASE_URL . '/admin/dashboard.php');
            break;
        case 'organizer':
            header('Location: ' . BASE_URL . '/admin/tournaments.php');
            break;
        case 'scorer':
            header('Location: ' . BASE_URL . '/public/live-score.php');
            break;
        case 'official':
            header('Location: ' . BASE_URL . '/admin/matches.php');
            break;
        case 'team_manager':
            header('Location: ' . BASE_URL . '/admin/teams.php');
            break;
        case 'player':
            header('Location: ' . BASE_URL . '/admin/players.php');
            break;
        default:
            header('Location: ' . BASE_URL . '/admin/dashboard.php');
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login | <?php echo APP_NAME; ?></title>
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/auth.css">
</head>
<body class="auth-body">

<div class="auth-container">
  <div class="auth-header">
    <div class="auth-brand-logo">
      <div class="brand-logo-icon" style="width:44px; height:44px; font-size:1.5rem;">S</div>
      <div class="brand-title" style="font-size:1.6rem;">Sports<span>Hub</span></div>
    </div>
    <div class="auth-title">Welcome Back</div>
    <div class="auth-subtitle">Sign in to your sports management console</div>
  </div>

  <?php if (!empty($error)): ?>
    <div class="auth-alert auth-alert-danger">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      <span><?php echo htmlspecialchars($error); ?></span>
    </div>
  <?php endif; ?>

  <!-- Google Sign In Button -->
  <a href="?google_login=1" class="btn-google">
    <svg width="20" height="20" viewBox="0 0 24 24">
      <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
      <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
      <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
      <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
    </svg>
    Sign in with Google
  </a>

  <div class="auth-divider">
    <span>OR SIGN IN WITH EMAIL</span>
  </div>

  <div class="demo-account-chips">
    <button type="button" class="demo-chip" onclick="fillDemo('admin@sportshub.com')">Admin Demo</button>
    <button type="button" class="demo-chip" onclick="fillDemo('organizer@sportshub.com')">Organizer</button>
    <button type="button" class="demo-chip" onclick="fillDemo('fan@sportshub.com')">Fan / Player</button>
  </div>

  <form action="" method="POST">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">

    <div class="form-group">
      <label>Email Address</label>
      <input type="email" name="email" id="emailInput" class="form-control" placeholder="user@sportshub.com" value="<?php echo htmlspecialchars($_POST['email'] ?? 'admin@sportshub.com'); ?>" required>
    </div>

    <div class="form-group">
      <label>Password</label>
      <div style="position:relative;">
        <input type="password" name="password" id="passwordInput" class="form-control" placeholder="••••••••" value="Password123" required style="padding-right:40px;">
        <button type="button" onclick="togglePasswordVisibility()" style="position:absolute; right:12px; top:50%; transform:translateY(-50%); background:none; border:none; color:var(--text-muted); cursor:pointer; font-size:1.1rem;">
          👁️
        </button>
      </div>
    </div>

    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; font-size:0.825rem;">
      <label style="display:flex; align-items:center; gap:6px; color:var(--text-muted); cursor:pointer;">
        <input type="checkbox" name="remember" checked style="accent-color:var(--accent-green);"> Remember me
      </label>
      <a href="#" onclick="alert('Password reset link will be sent to your registered email.');" style="color:var(--accent-green);">Forgot Password?</a>
    </div>

    <button type="submit" class="btn btn-primary" style="width:100%; padding:12px;">
      Sign In to SportsHub
    </button>
  </form>

  <div class="auth-footer-links">
    Don't have an account? <a href="<?php echo BASE_URL; ?>/auth/register.php">Register here</a>
  </div>
</div>

<script>
function togglePasswordVisibility() {
  const pwd = document.getElementById('passwordInput');
  pwd.type = (pwd.type === 'password') ? 'text' : 'password';
}

function fillDemo(email) {
  document.getElementById('emailInput').value = email;
  document.getElementById('passwordInput').value = 'Password123';
}
</script>

</body>
</html>

