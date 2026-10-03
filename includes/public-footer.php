</div> <!-- End public-body-wrap -->

<footer style="background: #070b14; border-top: 1px solid var(--border-subtle); padding: 32px 20px; text-align: center; color: var(--text-muted); font-size: 0.85rem; margin-top: 40px;">
  <div style="max-width: 1280px; margin: 0 auto; display: flex; flex-direction: column; align-items: center; gap: 12px;">
    <div style="display: flex; align-items: center; gap: 8px; font-weight: 800; color: #fff; font-size: 1.1rem;">
      <span style="color: var(--accent-green);"><?php echo htmlspecialchars(getSetting('college_name')); ?></span> &bull; <?php echo htmlspecialchars(getSetting('championship_title')); ?> (AY <?php echo htmlspecialchars(getSetting('academic_year')); ?>)
    </div>
    <p style="margin: 0; max-width: 600px; color: var(--text-dim);">
      Official public championship portal powering college inter-department sports competitions. Real-time live scores, points standings, and venue schedules.
    </p>
    <div style="display: flex; gap: 16px; margin-top: 8px; font-weight: 600;">
      <a href="<?php echo BASE_URL; ?>/public/home.php" style="color: var(--text-muted); text-decoration: none;">Home</a> &bull;
      <a href="<?php echo BASE_URL; ?>/public/upcoming.php" style="color: var(--text-muted); text-decoration: none;">Upcoming Matches</a> &bull;
      <a href="<?php echo BASE_URL; ?>/public/live-score.php" style="color: var(--text-muted); text-decoration: none;">Live Scores</a> &bull;
      <a href="<?php echo BASE_URL; ?>/public/trophy.php" style="color: var(--text-muted); text-decoration: none;">Department Trophy</a> &bull;
      <a href="<?php echo BASE_URL; ?>/public/points-table.php" style="color: var(--text-muted); text-decoration: none;">Points Table</a> &bull;
      <a href="<?php echo BASE_URL; ?>/auth/login.php" style="color: var(--accent-green); text-decoration: none;">Login</a>
    </div>
    <div style="font-size: 0.78rem; color: var(--text-dim); margin-top: 12px;">
      <?php echo htmlspecialchars(getSetting('footer_text')); ?>
    </div>
  </div>
</footer>

<script src="<?php echo BASE_URL; ?>/assets/js/main.js"></script>
</body>
</html>

