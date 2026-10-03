<?php
/**
 * SportsHub - Public Spectator Interface Header
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

$user = currentUser();
$currentPage = $currentPage ?? 'home';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . " | " . APP_NAME : "College Inter-Department Sports Championship 2026"; ?></title>
  
  <!-- CSS Stylesheets -->
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/dashboard.css">
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/tournament.css">

  <style>
    .public-navbar {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 14px 28px;
      background: rgba(10, 15, 26, 0.95);
      backdrop-filter: blur(12px);
      border-bottom: 1px solid var(--border-subtle);
      position: sticky;
      top: 0;
      z-index: 1000;
    }
    .public-logo {
      display: flex;
      align-items: center;
      gap: 12px;
      text-decoration: none;
    }
    .public-logo-badge {
      width: 40px;
      height: 40px;
      background: linear-gradient(135deg, var(--accent-green), #059669);
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 800;
      font-size: 1.3rem;
      color: #000;
      box-shadow: 0 4px 15px rgba(0, 230, 118, 0.3);
    }
    .public-logo-text {
      font-size: 1.25rem;
      font-weight: 800;
      color: #fff;
      letter-spacing: -0.5px;
    }
    .public-logo-text span {
      color: var(--accent-green);
    }
    .public-nav-links {
      display: flex;
      align-items: center;
      gap: 18px;
      list-style: none;
      margin: 0;
      padding: 0;
    }
    .public-nav-links a {
      color: var(--text-muted);
      text-decoration: none;
      font-size: 0.88rem;
      font-weight: 600;
      padding: 6px 12px;
      border-radius: var(--radius-sm);
      transition: all 0.2s ease;
      display: flex;
      align-items: center;
      gap: 6px;
    }
    .public-nav-links a:hover,
    .public-nav-links a.active {
      color: var(--accent-green);
      background: rgba(0, 230, 118, 0.08);
    }
    .public-live-tag {
      background: rgba(239, 68, 68, 0.15);
      color: #ef4444;
      border: 1px solid rgba(239, 68, 68, 0.3);
      padding: 2px 6px;
      border-radius: 4px;
      font-size: 0.72rem;
      font-weight: 700;
      display: inline-flex;
      align-items: center;
      gap: 4px;
    }
    .public-live-tag .dot {
      width: 6px;
      height: 6px;
      background: #ef4444;
      border-radius: 50%;
      animation: pulse 1.5s infinite;
    }
    @keyframes pulse {
      0% { opacity: 1; transform: scale(1); }
      50% { opacity: 0.4; transform: scale(1.2); }
      100% { opacity: 1; transform: scale(1); }
    }
    .public-body-wrap {
      max-width: 1280px;
      margin: 0 auto;
      padding: 28px 20px 60px 20px;
    }
    @media (max-width: 1024px) {
      .public-nav-links {
        display: none;
      }
      .public-nav-links.mobile-open {
        display: flex;
        flex-direction: column;
        position: absolute;
        top: 68px;
        left: 0;
        right: 0;
        background: #0a0f1a;
        padding: 16px;
        border-bottom: 1px solid var(--border-subtle);
      }
    }
  </style>
</head>
<body style="background: var(--bg-dark-main); color: var(--text-main); font-family: 'Inter', system-ui, -apple-system, sans-serif;">

<!-- Public Header Navbar -->
<header class="public-navbar">
  <a href="<?php echo BASE_URL; ?>/public/home.php" class="public-logo">
    <div class="public-logo-badge">S</div>
    <div>
      <div class="public-logo-text">Sports<span>Hub</span></div>
      <div style="font-size:0.68rem; color:var(--text-muted); font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">College Championship</div>
    </div>
  </a>

  <button onclick="document.querySelector('.public-nav-links').classList.toggle('mobile-open')" style="display:none; background:none; border:none; color:#fff; font-size:1.5rem; cursor:pointer;" class="mobile-toggle">
    ☰
  </button>

  <ul class="public-nav-links">
    <li><a href="<?php echo BASE_URL; ?>/public/home.php" class="<?php echo $currentPage === 'home' ? 'active' : ''; ?>">🏠 Home</a></li>
    <li><a href="<?php echo BASE_URL; ?>/public/upcoming.php" class="<?php echo $currentPage === 'upcoming' ? 'active' : ''; ?>">📅 Upcoming</a></li>
    <li>
      <a href="<?php echo BASE_URL; ?>/public/live-score.php" class="<?php echo $currentPage === 'live' ? 'active' : ''; ?>">
        🔴 Live Scores <span class="public-live-tag"><span class="dot"></span>LIVE</span>
      </a>
    </li>
    <li><a href="<?php echo BASE_URL; ?>/public/trophy.php" class="<?php echo $currentPage === 'trophy' ? 'active' : ''; ?>">🏆 Trophy</a></li>
    <li><a href="<?php echo BASE_URL; ?>/public/points-table.php" class="<?php echo $currentPage === 'points' ? 'active' : ''; ?>">📊 Points Table</a></li>
    <li><a href="<?php echo BASE_URL; ?>/public/sports.php" class="<?php echo $currentPage === 'sports' ? 'active' : ''; ?>">⚽ Sports</a></li>
    <li><a href="<?php echo BASE_URL; ?>/public/results.php" class="<?php echo $currentPage === 'results' ? 'active' : ''; ?>">🏁 Results</a></li>
    <li><a href="<?php echo BASE_URL; ?>/public/venues.php" class="<?php echo $currentPage === 'venues' ? 'active' : ''; ?>">🏟️ Venues</a></li>
    <li><a href="<?php echo BASE_URL; ?>/public/announcements.php" class="<?php echo $currentPage === 'announcements' ? 'active' : ''; ?>">📢 Alerts</a></li>
    
    <?php if (isLoggedIn() && in_array(strtolower($user['role'] ?? ''), ['admin', 'organizer', 'scorer', 'official'])): ?>
      <li>
        <a href="<?php echo BASE_URL; ?>/admin/dashboard.php" style="background: var(--accent-green); color: #000; font-weight: 700; padding: 6px 14px; border-radius: var(--radius-sm);">
          ⚙️ Admin Console
        </a>
      </li>
    <?php else: ?>
      <li>
        <a href="<?php echo BASE_URL; ?>/auth/login.php" style="border: 1px solid var(--accent-green); color: var(--accent-green); padding: 5px 14px; border-radius: var(--radius-sm);">
          🔑 Login
        </a>
      </li>
    <?php endif; ?>
  </ul>
</header>

<div class="public-body-wrap">
  <?php if (isset($_SESSION['flash_error'])): ?>
    <div class="auth-alert auth-alert-danger" style="margin-bottom:24px; background:rgba(239,68,68,0.15); border:1px solid rgba(239,68,68,0.3); color:#f87171; padding:12px 16px; border-radius:8px;">
      <span><?php echo htmlspecialchars($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?></span>
    </div>
  <?php endif; ?>
