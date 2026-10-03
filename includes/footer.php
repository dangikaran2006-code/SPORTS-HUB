<?php
/**
 * SportsHub - Footer Component
 */
?>
    </main> <!-- End Page Content -->

    <!-- App Footer -->
    <footer class="app-footer">
      <div>
        &copy; <?php echo date('Y'); ?> <strong>SportsHub Platform</strong>. Multi-Sport Tournament Engine.
      </div>
      <div style="display:flex; gap:16px;">
        <a href="#" style="color:var(--text-muted);">Documentation</a>
        <a href="#" style="color:var(--text-muted);">API Reference</a>
        <a href="#" style="color:var(--text-muted);">Support</a>
      </div>
    </footer>
  </div> <!-- End Main Wrapper -->
</div> <!-- End App Layout -->

<!-- Toast Container -->
<div id="toastContainer"></div>

<!-- JavaScript Controllers -->
<script src="<?php echo BASE_URL; ?>/assets/js/main.js"></script>
<script src="<?php echo BASE_URL; ?>/assets/js/dashboard.js"></script>
<script src="<?php echo BASE_URL; ?>/assets/js/tournament.js"></script>

</body>
</html>
