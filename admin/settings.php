<?php
/**
 * SportsHub - Platform Settings
 */
$currentPage = 'settings';
$pageTitle = 'Platform Settings';

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';

// Enforce Admin Access
requireRole('admin');

$db = getDB();

include_once __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-header">
  <div class="dashboard-title-group">
    <h1>Platform & Scoring Rules Settings</h1>
    <p>Configure sport-specific points systems, tie-breaker policies, and notification channels.</p>
  </div>
</div>

<div class="card" style="max-width:600px;">
  <form onsubmit="event.preventDefault(); showToast('Settings Saved', 'Platform configuration successfully updated!', 'success');">
    <div class="form-group">
      <label>Platform Name</label>
      <input type="text" class="form-control" value="SportsHub Platform">
    </div>

    <div class="form-group">
      <label>Default Currency / Locale</label>
      <input type="text" class="form-control" value="INR (₹) / en-IN">
    </div>

    <div class="form-group">
      <label>Default Points for Match Win (Football/Kabaddi)</label>
      <input type="number" class="form-control" value="3">
    </div>

    <div class="form-group">
      <label>Default Points for Draw/Tie</label>
      <input type="number" class="form-control" value="1">
    </div>

    <button type="submit" class="btn btn-primary">Save Settings</button>
  </form>
</div>
<?php include_once __DIR__ . '/../includes/footer.php'; ?>
