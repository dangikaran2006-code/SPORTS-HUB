<?php
/**
 * SportsHub - Platform & College Branding Settings
 */
$currentPage = 'settings';
$pageTitle = 'Championship & College Settings';

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';

// Enforce Admin Access
requireRole('admin');

$message = '';
$error = '';
$csrfToken = generateCsrfToken();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!validateCsrfToken($token)) {
        $error = 'Security validation failed (Invalid CSRF Token).';
    } else {
        $settingsToSave = [
            'college_name'          => trim($_POST['college_name'] ?? ''),
            'championship_title'    => trim($_POST['championship_title'] ?? ''),
            'academic_year'         => trim($_POST['academic_year'] ?? ''),
            'college_logo'          => trim($_POST['college_logo'] ?? ''),
            'championship_banner'   => trim($_POST['championship_banner'] ?? ''),
            'primary_contact_email' => trim($_POST['primary_contact_email'] ?? ''),
            'primary_contact_phone' => trim($_POST['primary_contact_phone'] ?? ''),
            'default_timezone'      => trim($_POST['default_timezone'] ?? 'Asia/Kolkata'),
            'date_format'           => trim($_POST['date_format'] ?? 'Y-m-d H:i:s'),
            'footer_text'           => trim($_POST['footer_text'] ?? ''),
            'public_visibility'     => isset($_POST['public_visibility']) ? '1' : '0',
            'notify_upcoming'       => isset($_POST['notify_upcoming']) ? '1' : '0',
            'notify_live'           => isset($_POST['notify_live']) ? '1' : '0',
            'notify_results'        => isset($_POST['notify_results']) ? '1' : '0'
        ];

        foreach ($settingsToSave as $key => $val) {
            updateSetting($key, $val);
        }

        logAuditAction('Settings Updated', 'Settings', null, "Updated championship branding & system configuration");
        $message = 'Championship & College Branding settings updated successfully!';
    }
}

include_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-header">
  <div class="dashboard-title-group">
    <h1>Championship & College Branding Settings</h1>
    <p>Configure college identity, championship titles, contact details, and system preferences.</p>
  </div>
</div>

<?php if (!empty($message)): ?>
  <div class="auth-alert auth-alert-success" style="margin-bottom:20px;">
    <span><?php echo htmlspecialchars($message); ?></span>
  </div>
<?php endif; ?>

<?php if (!empty($error)): ?>
  <div class="auth-alert auth-alert-danger" style="margin-bottom:20px;">
    <span><?php echo htmlspecialchars($error); ?></span>
  </div>
<?php endif; ?>

<form action="settings.php" method="POST">
  <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">

  <div class="grid grid-2" style="gap: 20px;">
    <!-- College & Championship Identity -->
    <div class="card">
      <h3 style="font-size: 1.15rem; color: var(--text-main); margin-bottom: 16px; font-weight: 700; display:flex; align-items:center; gap:8px;">
        🏛️ College & Championship Identity
      </h3>

      <div class="form-group">
        <label>College Name *</label>
        <input type="text" name="college_name" class="form-control" value="<?php echo htmlspecialchars(getSetting('college_name')); ?>" required>
      </div>

      <div class="form-group">
        <label>Championship Title *</label>
        <input type="text" name="championship_title" class="form-control" value="<?php echo htmlspecialchars(getSetting('championship_title')); ?>" required>
      </div>

      <div class="form-group">
        <label>Academic Year *</label>
        <input type="text" name="academic_year" class="form-control" value="<?php echo htmlspecialchars(getSetting('academic_year')); ?>" placeholder="2025-2026" required>
      </div>

      <div class="form-group">
        <label>College Logo Asset Path / URL</label>
        <input type="text" name="college_logo" class="form-control" value="<?php echo htmlspecialchars(getSetting('college_logo')); ?>">
      </div>

      <div class="form-group">
        <label>Championship Banner Asset Path / URL</label>
        <input type="text" name="championship_banner" class="form-control" value="<?php echo htmlspecialchars(getSetting('championship_banner')); ?>">
      </div>

      <div class="form-group">
        <label>Footer Copyright Text</label>
        <input type="text" name="footer_text" class="form-control" value="<?php echo htmlspecialchars(getSetting('footer_text')); ?>">
      </div>
    </div>

    <!-- System & Notification Preferences -->
    <div class="card">
      <h3 style="font-size: 1.15rem; color: var(--text-main); margin-bottom: 16px; font-weight: 700; display:flex; align-items:center; gap:8px;">
        ⚙️ System & Notification Controls
      </h3>

      <div class="form-group">
        <label>Primary Contact Email</label>
        <input type="email" name="primary_contact_email" class="form-control" value="<?php echo htmlspecialchars(getSetting('primary_contact_email')); ?>">
      </div>

      <div class="form-group">
        <label>Primary Contact Phone</label>
        <input type="text" name="primary_contact_phone" class="form-control" value="<?php echo htmlspecialchars(getSetting('primary_contact_phone')); ?>">
      </div>

      <div class="form-group">
        <label>Default Timezone</label>
        <select name="default_timezone" class="form-control">
          <option value="Asia/Kolkata" <?php echo getSetting('default_timezone') === 'Asia/Kolkata' ? 'selected' : ''; ?>>Asia/Kolkata (IST)</option>
          <option value="UTC" <?php echo getSetting('default_timezone') === 'UTC' ? 'selected' : ''; ?>>UTC</option>
        </select>
      </div>

      <hr style="border-color: var(--border-color); margin: 20px 0;">

      <h4 style="font-size: 1rem; color: var(--text-main); margin-bottom: 12px; font-weight: 600;">Public Visibility & Notifications</h4>

      <div style="display: flex; flex-direction: column; gap: 12px;">
        <label style="display: flex; align-items: center; gap: 10px; color: var(--text-main); cursor: pointer;">
          <input type="checkbox" name="public_visibility" value="1" <?php echo getSetting('public_visibility') === '1' ? 'checked' : ''; ?> style="accent-color: var(--accent-green);">
          Enable Public Portal Read-Only Spectator Access
        </label>

        <label style="display: flex; align-items: center; gap: 10px; color: var(--text-main); cursor: pointer;">
          <input type="checkbox" name="notify_upcoming" value="1" <?php echo getSetting('notify_upcoming') === '1' ? 'checked' : ''; ?> style="accent-color: var(--accent-green);">
          Broadcast Notifications for Upcoming Matches
        </label>

        <label style="display: flex; align-items: center; gap: 10px; color: var(--text-main); cursor: pointer;">
          <input type="checkbox" name="notify_live" value="1" <?php echo getSetting('notify_live') === '1' ? 'checked' : ''; ?> style="accent-color: var(--accent-green);">
          Broadcast Alerts for Live Score Milestone Changes
        </label>

        <label style="display: flex; align-items: center; gap: 10px; color: var(--text-main); cursor: pointer;">
          <input type="checkbox" name="notify_results" value="1" <?php echo getSetting('notify_results') === '1' ? 'checked' : ''; ?> style="accent-color: var(--accent-green);">
          Broadcast Alerts when Match Results are Verified
        </label>
      </div>
    </div>
  </div>

  <div style="margin-top: 24px; display: flex; justify-content: flex-end;">
    <button type="submit" class="btn btn-primary" style="padding: 12px 32px; font-size: 1rem;">
      💾 Save All Settings
    </button>
  </div>
</form>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
