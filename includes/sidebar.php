<?php
/**
 * SportsHub - Dynamic Role-Based Sidebar Component
 * Grouped into Championship, Participants, Competition, Operations, and System sections
 */
require_once __DIR__ . '/auth.php';

$currentPage = $currentPage ?? 'dashboard';
$user = currentUser();
$userRole = strtolower($user['role'] ?? 'admin');
?>
<aside class="app-sidebar" id="appSidebar">
  <!-- Brand Header -->
  <div class="sidebar-brand">
    <div class="brand-logo-icon">S</div>
    <div>
      <div class="brand-title">Sports<span>Hub</span></div>
      <div style="font-size:0.65rem; color:var(--accent-green); font-weight:700; letter-spacing:0.05em; text-transform:uppercase;">College Championship</div>
    </div>
  </div>

  <!-- Navigation Links Container -->
  <div class="sidebar-nav-container">

    <!-- SECTION 1: CHAMPIONSHIP -->
    <div class="nav-section-title">CHAMPIONSHIP</div>
    <ul class="nav-list">
      <li class="nav-item <?php echo isNavActive('dashboard', $currentPage); ?>">
        <a href="<?php echo BASE_URL; ?>/admin/dashboard.php">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/></svg>
          <span>Dashboard</span>
        </a>
      </li>

      <?php if (in_array($userRole, ['admin', 'organizer'])): ?>
      <li class="nav-item <?php echo isNavActive('championship', $currentPage); ?>">
        <a href="<?php echo BASE_URL; ?>/admin/championship.php">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
          <span>Championship Control</span>
        </a>
      </li>

      <li class="nav-item <?php echo isNavActive('departments', $currentPage); ?>">
        <a href="<?php echo BASE_URL; ?>/admin/departments.php">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M3 21h18"/><path d="M3 7v14"/><path d="M21 7v14"/><path d="M6 21V11"/><path d="M10 21V11"/><path d="M14 21V11"/><path d="M18 21V11"/><path d="M12 3L2 7h20L12 3z"/></svg>
          <span>Departments</span>
        </a>
      </li>

      <li class="nav-item <?php echo isNavActive('sports', $currentPage); ?>">
        <a href="<?php echo BASE_URL; ?>/admin/sports.php">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="12" r="10"/><path d="M8 12h8"/><path d="M12 8v8"/></svg>
          <span>Sports Master</span>
        </a>
      </li>
      <?php endif; ?>
    </ul>

    <!-- SECTION 2: PARTICIPANTS -->
    <div class="nav-section-title" style="margin-top:16px;">PARTICIPANTS</div>
    <ul class="nav-list">
      <?php if (in_array($userRole, ['admin', 'organizer', 'team_manager', 'player'])): ?>
      <li class="nav-item <?php echo isNavActive('players', $currentPage); ?>">
        <a href="<?php echo BASE_URL; ?>/admin/players.php">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
          <span>Athletes</span>
        </a>
      </li>

      <li class="nav-item <?php echo isNavActive('teams', $currentPage); ?>">
        <a href="<?php echo BASE_URL; ?>/admin/teams.php">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
          <span>Teams</span>
        </a>
      </li>
      <?php endif; ?>
    </ul>

    <!-- SECTION 3: COMPETITION -->
    <div class="nav-section-title" style="margin-top:16px;">COMPETITION</div>
    <ul class="nav-list">
      <li class="nav-item <?php echo isNavActive('matches', $currentPage); ?>">
        <a href="<?php echo BASE_URL; ?>/admin/matches.php">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
          <span>Fixtures Engine</span>
        </a>
      </li>

      <?php if (in_array($userRole, ['admin', 'scorer'])): ?>
      <li class="nav-item <?php echo isNavActive('live-score', $currentPage); ?>">
        <a href="<?php echo BASE_URL; ?>/organizer/live-scoring.php">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
          <span>Live Scoring Console</span>
        </a>
      </li>
      <?php endif; ?>

      <?php if (in_array($userRole, ['admin', 'organizer'])): ?>
      <li class="nav-item <?php echo isNavActive('results', $currentPage); ?>">
        <a href="<?php echo BASE_URL; ?>/admin/results.php">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
          <span>Results Verification</span>
        </a>
      </li>
      <?php endif; ?>

      <li class="nav-item <?php echo isNavActive('points', $currentPage); ?>">
        <a href="<?php echo BASE_URL; ?>/admin/points.php">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6"/><path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18"/><path d="M4 22h16"/><path d="M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22"/><path d="M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22"/><path d="M18 2H6v7a6 6 0 0 0 12 0V2z"/></svg>
          <span>Points & Trophy</span>
        </a>
      </li>
    </ul>

    <!-- SECTION 4: OPERATIONS -->
    <div class="nav-section-title" style="margin-top:16px;">OPERATIONS</div>
    <ul class="nav-list">
      <?php if (in_array($userRole, ['admin', 'organizer'])): ?>
      <li class="nav-item <?php echo isNavActive('venues', $currentPage); ?>">
        <a href="<?php echo BASE_URL; ?>/admin/venues.php">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
          <span>Venues</span>
        </a>
      </li>

      <li class="nav-item <?php echo isNavActive('officials', $currentPage); ?>">
        <a href="<?php echo BASE_URL; ?>/admin/officials.php">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/></svg>
          <span>Officials</span>
        </a>
      </li>

      <li class="nav-item <?php echo isNavActive('announcements', $currentPage); ?>">
        <a href="<?php echo BASE_URL; ?>/admin/announcements.php">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
          <span>Announcements</span>
        </a>
      </li>

      <li class="nav-item <?php echo isNavActive('notifications', $currentPage); ?>">
        <a href="<?php echo BASE_URL; ?>/admin/notifications.php" style="display: flex; align-items: center; justify-content: space-between; width: 100%;">
          <div style="display: flex; align-items: center; gap: 10px;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
            <span>Notifications</span>
          </div>
          <span id="sidebarUnreadBadge" style="display:none; background:var(--accent-red); color:#fff; font-size:0.7rem; font-weight:800; padding:2px 7px; border-radius:10px;"></span>
        </a>
      </li>
      <?php endif; ?>
    </ul>

    <!-- SECTION 5: SYSTEM -->
    <div class="nav-section-title" style="margin-top:16px;">SYSTEM</div>
    <ul class="nav-list">
      <?php if ($userRole === 'admin'): ?>
      <li class="nav-item <?php echo isNavActive('users', $currentPage); ?>">
        <a href="<?php echo BASE_URL; ?>/admin/users.php">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="17" y1="11" x2="23" y2="11"/></svg>
          <span>Users & Roles</span>
        </a>
      </li>
      <?php endif; ?>

      <li class="nav-item <?php echo isNavActive('statistics', $currentPage); ?>">
        <a href="<?php echo BASE_URL; ?>/admin/statistics.php">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M3 3v18h18"/><path d="m19 9-5 5-4-4-3 3"/></svg>
          <span>Analytics</span>
        </a>
      </li>

      <li class="nav-item <?php echo isNavActive('settings', $currentPage); ?>">
        <a href="<?php echo BASE_URL; ?>/admin/settings.php">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
          <span>Settings</span>
        </a>
      </li>
    </ul>

  </div>

  <!-- Sidebar Footer -->
  <div class="sidebar-footer">
    <div class="quick-support-card">
      <p style="font-weight:600; color:var(--text-main); margin-bottom:4px;"><?php echo htmlspecialchars($user['name'] ?? 'User'); ?></p>
      <div style="font-size:0.75rem; color:var(--accent-green); text-transform:uppercase; font-weight:700; margin-bottom:10px;">
        <?php echo htmlspecialchars($userRole); ?>
      </div>
      <a href="<?php echo BASE_URL; ?>/auth/logout.php" class="btn btn-secondary btn-sm" style="width:100%; color:var(--accent-red); border-color:rgba(239,68,68,0.3);">
        Sign Out
      </a>
    </div>
  </div>
</aside>
