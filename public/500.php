<?php
/**
 * SportsHub - 500 Server Error Page
 */
$currentPage = '500';
$pageTitle = '500 Server Error';

require_once __DIR__ . '/../includes/config.php';
include_once __DIR__ . '/../includes/public-header.php';
?>

<div style="text-align: center; padding: 60px 20px; max-width: 600px; margin: 0 auto;">
  <div style="font-size: 4rem; margin-bottom: 12px;">⚠️</div>
  <h1 style="font-size: 2.25rem; font-weight: 800; color: #f59e0b; margin-bottom: 12px;">500 Internal Server Error</h1>
  <p style="color: var(--text-muted); font-size: 1rem; line-height: 1.6; margin-bottom: 28px;">
    Something went wrong while processing your request. Please try again later or contact championship officials if the issue persists.
  </p>
  <a href="<?php echo BASE_URL; ?>/public/home.php" class="btn btn-primary" style="padding: 12px 24px;">
    🏠 Return to Public Homepage
  </a>
</div>

<?php include_once __DIR__ . '/../includes/public-footer.php'; ?>
