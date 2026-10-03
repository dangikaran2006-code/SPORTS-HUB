<?php
/**
 * SportsHub - Top Navigation Header Component
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/notification-helper.php';

// Enforce Login globally across protected pages
requireLogin();

$currentUser = currentUser();
$userName = $currentUser['name'] ?? 'Alex Mercer';
$userRole = ucfirst($currentUser['role'] ?? 'Admin');
$initialUnread = getUnreadNotificationCount($currentUser['id'] ?? null);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . " | " . APP_NAME : APP_NAME . " - Multi-Sport Tournament Engine"; ?></title>
  
  <!-- CSS Stylesheets -->
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/dashboard.css">
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/tournament.css">
</head>
<body>

<div class="app-layout">
  <!-- Sidebar -->
  <?php include_once __DIR__ . '/sidebar.php'; ?>

  <!-- Main Content Wrapper -->
  <div class="main-wrapper" id="mainWrapper">
    <!-- Top Navigation Header -->
    <header class="top-header">
      <div class="header-left">
        <button class="sidebar-toggle-btn" id="sidebarToggleBtn" title="Toggle Navigation">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
        </button>
        <div class="header-search">
          <span class="search-icon">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
          </span>
          <input type="text" id="globalSearchInput" placeholder="Search tournaments, teams, players...">
        </div>
      </div>

      <div class="header-right">
        <!-- Live Matches Indicator Pill -->
        <a href="<?php echo BASE_URL; ?>/public/live-score.php" class="live-indicator-pill">
          <span class="pulse-dot"></span>
          <span>LIVE Scores</span>
        </a>

        <!-- Notifications Bell -->
        <a href="<?php echo BASE_URL; ?>/admin/notifications.php" class="icon-btn" title="Notification Center" style="position:relative; text-decoration:none;">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
          <span class="notification-badge" id="headerNotificationBadge" style="<?php echo ($initialUnread > 0) ? 'display:inline-block;' : 'display:none;'; ?>">
            <?php echo ($initialUnread > 0) ? $initialUnread : ''; ?>
          </span>
        </a>

        <!-- User Profile Dropdown Component -->
        <div class="user-profile-wrapper" style="position:relative;">
          <div class="user-profile-menu" onclick="toggleUserDropdown()" id="userProfileBtn">
            <div class="user-avatar-placeholder" style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg, #00e676, #3b82f6);display:flex;align-items:center;justify-content:center;font-weight:700;color:#000;">
              <?php echo strtoupper(substr($userName, 0, 1)); ?>
            </div>
            <div class="user-info">
              <span class="user-name"><?php echo htmlspecialchars($userName); ?></span>
              <span class="user-role" style="color:var(--accent-green); font-weight:600;"><?php echo htmlspecialchars($userRole); ?></span>
            </div>
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-left:4px;"><polyline points="6 9 12 15 18 9"/></svg>
          </div>

          <!-- Dropdown Menu -->
          <div class="user-dropdown-menu" id="userDropdownMenu" style="position:absolute; right:0; top:48px; width:200px; background:var(--bg-dark-surface); border:1px solid var(--border-highlight); border-radius:var(--radius-md); box-shadow:0 10px 25px rgba(0,0,0,0.5); display:none; flex-direction:column; z-index:200; padding:8px 0;">
            <div style="padding:10px 16px; border-bottom:1px solid var(--border-subtle);">
              <div style="font-weight:700; color:var(--text-main); font-size:0.9rem;"><?php echo htmlspecialchars($userName); ?></div>
              <div style="font-size:0.75rem; color:var(--text-muted);"><?php echo htmlspecialchars($currentUser['email'] ?? ''); ?></div>
            </div>
            <a href="<?php echo BASE_URL; ?>/admin/notifications.php" style="padding:10px 16px; color:var(--text-main); font-size:0.85rem; display:flex; align-items:center; gap:8px;">
              <span>🔔 Notifications</span>
            </a>
            <a href="<?php echo BASE_URL; ?>/admin/settings.php" style="padding:10px 16px; color:var(--text-main); font-size:0.85rem; display:flex; align-items:center; gap:8px;">
              <span>⚙️ Settings</span>
            </a>
            <div style="border-top:1px solid var(--border-subtle); margin:4px 0;"></div>
            <a href="<?php echo BASE_URL; ?>/auth/logout.php" style="padding:10px 16px; color:var(--accent-red); font-size:0.85rem; display:flex; align-items:center; gap:8px; font-weight:600;">
              <span>🚪 Logout</span>
            </a>
          </div>
        </div>
      </div>
    </header>

    <script>
    function toggleUserDropdown() {
      const menu = document.getElementById('userDropdownMenu');
      menu.style.display = (menu.style.display === 'flex') ? 'none' : 'flex';
    }
    document.addEventListener('click', function(e) {
      const btn = document.getElementById('userProfileBtn');
      const menu = document.getElementById('userDropdownMenu');
      if (btn && menu && !btn.contains(e.target) && !menu.contains(e.target)) {
        menu.style.display = 'none';
      }
    });

    // Real-time Notification Polling
    function pollNotifications() {
      fetch('<?php echo BASE_URL; ?>/api/notifications/poll.php')
        .then(res => res.json())
        .then(data => {
          if (data && data.success) {
            const count = data.unread_count || 0;
            const hBadge = document.getElementById('headerNotificationBadge');
            const sBadge = document.getElementById('sidebarUnreadBadge');
            if (hBadge) {
              hBadge.textContent = count > 0 ? count : '';
              hBadge.style.display = count > 0 ? 'inline-block' : 'none';
            }
            if (sBadge) {
              sBadge.textContent = count > 0 ? count : '';
              sBadge.style.display = count > 0 ? 'inline-block' : 'none';
            }
          }
        }).catch(err => {});
    }
    setInterval(pollNotifications, 15000);
    document.addEventListener('DOMContentLoaded', pollNotifications);
    </script>

    <!-- Page Content Container Starts -->
    <main class="page-content">
      <?php if (isset($_SESSION['flash_error'])): ?>
        <div class="auth-alert auth-alert-danger" style="margin-bottom:20px;">
          <span><?php echo htmlspecialchars($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?></span>
        </div>
      <?php endif; ?>
