<?php
/**
 * SportsHub - 403 Access Denied Error Page
 */
$currentPage = '403';
$pageTitle = '403 Access Denied';

require_once __DIR__ . '/../includes/config.php';
include_once __DIR__ . '/../includes/public-header.php';
?>

<div style="text-align: center; padding: 60px 20px; max-width: 600px; margin: 0 auto;">
  <div style="font-size: 4rem; margin-bottom: 12px;">🔒</div>
  <h1 style="font-size: 2.25rem; font-weight: 800; color: #ef4444; margin-bottom: 12px;">403 Forbidden Access</h1>
  <p style="color: var(--text-muted); font-size: 1rem; line-height: 1.6; margin-bottom: 28px;">
    You do not have administrative authority to access this protected console page. Public spectators have read-only access to championship scores and standings.
  </p>
  <div style="display: flex; gap: 12px; justify-content: center;">
    <a href="<?php echo BASE_URL; ?>/public/home.php" class="btn btn-primary" style="padding: 12px 24px;">
      🏠 Return to Public Homepage
    </a>
    <a href="<?php echo BASE_URL; ?>/auth/login.php" class="btn btn-secondary" style="padding: 12px 20px;">
      🔑 Admin Login Portal
    </a>
  </div>
</div>

<?php include_once __DIR__ . '/../includes/public-footer.php'; ?>
