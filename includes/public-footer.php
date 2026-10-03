</div> <!-- End public-body-wrap -->

<footer style="background: #070b14; border-top: 1px solid var(--border-subtle); padding: 32px 20px; text-align: center; color: var(--text-muted); font-size: 0.85rem; margin-top: 40px;">
  <div style="max-width: 1280px; margin: 0 auto; display: flex; flex-direction: column; align-items: center; gap: 12px;">
    <div style="display: flex; align-items: center; gap: 8px; font-weight: 800; color: #fff; font-size: 1.1rem;">
      <span style="color: var(--accent-green);">SportsHub</span> &bull; College Inter-Department Championship 2026
    </div>
    <p style="margin: 0; max-width: 600px; color: var(--text-dim);">
      Official public championship platform powering 6 college departments across 10 sports events. Real-time scores, standings, and venue schedules.
    </p>
    <div style="display: flex; gap: 16px; margin-top: 8px; font-weight: 600;">
      <a href="<?php echo BASE_URL; ?>/public/home.php" style="color: var(--text-muted); text-decoration: none;">Home</a> &bull;
      <a href="<?php echo BASE_URL; ?>/public/trophy.php" style="color: var(--text-muted); text-decoration: none;">Department Trophy</a> &bull;
      <a href="<?php echo BASE_URL; ?>/public/schedule.php" style="color: var(--text-muted); text-decoration: none;">Schedule</a> &bull;
      <a href="<?php echo BASE_URL; ?>/auth/login.php" style="color: var(--accent-green); text-decoration: none;">Admin Login</a>
    </div>
    <div style="font-size: 0.78rem; color: var(--text-dim); margin-top: 12px;">
      &copy; <?php echo date('Y'); ?> College Sports Championship Hub. All rights reserved.
    </div>
  </div>
</footer>

<script src="<?php echo BASE_URL; ?>/assets/js/main.js"></script>
</body>
</html>
