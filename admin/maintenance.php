<?php
/**
 * SportsHub - Admin Maintenance, Backup & Diagnostics Control Center
 */
$currentPage = 'maintenance';
$pageTitle = 'Admin Maintenance Control';

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/maintenance-helper.php';

requireAdminAccess();

$msg = '';
$error = '';
$csrfToken = generateCsrfToken();
$activeTab = $_GET['tab'] ?? 'health';

// Handle POST Maintenance Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    $action = $_POST['action'] ?? '';

    if (!validateCsrfToken($token)) {
        $error = 'Security validation failed (Invalid CSRF Token).';
    } else {
        if ($action === 'create_backup') {
            $result = createDatabaseBackup(currentUser()['name'] ?? 'Admin');
            if ($result['success']) {
                $msg = "Database backup created successfully: <strong>" . htmlspecialchars($result['filename']) . "</strong> ({$result['size']}).";
                $activeTab = 'backups';
            } else {
                $error = htmlspecialchars($result['error']);
                $activeTab = 'backups';
            }
        } elseif ($action === 'delete_backup') {
            $filename = trim($_POST['filename'] ?? '');
            $result = deleteBackupFile($filename);
            if ($result['success']) {
                $msg = htmlspecialchars($result['message']);
            } else {
                $error = htmlspecialchars($result['error']);
            }
            $activeTab = 'backups';
        } elseif ($action === 'restore_backup') {
            $filename = trim($_POST['filename'] ?? '');
            $result = restoreDatabaseBackup($filename);
            if ($result['success']) {
                $msg = "<strong>Restore Successful!</strong> " . htmlspecialchars($result['message']);
            } else {
                $error = "<strong>Restore Failed:</strong> " . htmlspecialchars($result['error']);
            }
            $activeTab = 'backups';
        } elseif ($action === 'recalculate_points') {
            $result = recalculatePointsConsistency();
            if ($result['success']) {
                $msg = htmlspecialchars($result['message']);
            } else {
                $error = htmlspecialchars($result['error']);
            }
            $activeTab = 'integrity';
        } elseif ($action === 'recalculate_stats') {
            $result = recalculateAllStatistics();
            if ($result['success']) {
                $msg = htmlspecialchars($result['message']);
            } else {
                $error = htmlspecialchars($result['error']);
            }
            $activeTab = 'integrity';
        } elseif ($action === 'delete_orphan_upload') {
            $filename = trim($_POST['filename'] ?? '');
            $result = deleteOrphanedUploadFile($filename);
            if ($result['success']) {
                $msg = htmlspecialchars($result['message']);
            } else {
                $error = htmlspecialchars($result['error']);
            }
            $activeTab = 'storage';
        } elseif ($action === 'update_maintenance_mode') {
            $mActive = isset($_POST['maintenance_active']) && $_POST['maintenance_active'] === '1';
            $mMessage = trim($_POST['maintenance_message'] ?? '');
            $mStart = trim($_POST['start_time'] ?? '');
            $mEnd = trim($_POST['end_time'] ?? '');

            $result = setMaintenanceMode($mActive, $mMessage, $mStart, $mEnd);
            if ($result['success']) {
                $msg = htmlspecialchars($result['message']);
            } else {
                $error = htmlspecialchars($result['error']);
            }
            $activeTab = 'settings';
        }
    }
}

// Fetch Diagnostics & Metrics
$health = getSystemHealthInfo();
$summaryCounts = getDatabaseSummaryCounts();
$backupHistory = getBackupHistory();
$integrityIssues = checkDataIntegrity();
$pointsCheck = checkPointsConsistency();
$uploadCheck = checkUploadMaintenance();
$maintenanceConfig = getMaintenanceSettings();

// Fetch Audit Logs for System Logs tab
$auditLogs = fetchAll("SELECT a.*, u.name as user_name FROM audit_logs a LEFT JOIN users u ON a.user_id = u.id ORDER BY a.id DESC LIMIT 50");

include_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
  <div class="dashboard-title-group">
    <h1>🛡️ Championship Maintenance & Backup Control</h1>
    <p>Monitor database health, generate SQL backups, execute safety restores, audit data integrity, and manage maintenance mode.</p>
  </div>
  <div style="display: flex; gap: 12px; align-items: center;">
    <?php if (!empty($maintenanceConfig['active'])): ?>
      <span class="status-badge badge-danger" style="font-size: 0.85rem; padding: 6px 14px; font-weight: 800;">
        ⚠️ MAINTENANCE MODE ACTIVE
      </span>
    <?php else: ?>
      <span class="status-badge badge-completed" style="font-size: 0.85rem; padding: 6px 14px; font-weight: 800;">
        🟢 SYSTEM ONLINE
      </span>
    <?php endif; ?>
  </div>
</div>

<?php if (!empty($msg)): ?>
  <div class="auth-alert auth-alert-success" style="margin-bottom: 24px;">
    <span><?php echo $msg; ?></span>
  </div>
<?php endif; ?>

<?php if (!empty($error)): ?>
  <div class="auth-alert auth-alert-danger" style="margin-bottom: 24px;">
    <span><?php echo $error; ?></span>
  </div>
<?php endif; ?>

<!-- Navigation Tabs -->
<div style="display: flex; gap: 8px; border-bottom: 1px solid var(--border-subtle); margin-bottom: 24px; overflow-x: auto;">
  <a href="admin/maintenance.php?tab=health" class="btn btn-secondary btn-sm" style="<?php echo ($activeTab === 'health') ? 'background: var(--accent-green); color: #000; font-weight: 800;' : ''; ?>">
    🩺 System Health & Metrics
  </a>
  <a href="admin/maintenance.php?tab=backups" class="btn btn-secondary btn-sm" style="<?php echo ($activeTab === 'backups') ? 'background: var(--accent-green); color: #000; font-weight: 800;' : ''; ?>">
    💾 Database Backups (<?php echo count($backupHistory); ?>)
  </a>
  <a href="admin/maintenance.php?tab=integrity" class="btn btn-secondary btn-sm" style="<?php echo ($activeTab === 'integrity') ? 'background: var(--accent-green); color: #000; font-weight: 800;' : ''; ?>">
    🔍 Data Integrity & Points (<?php echo count($integrityIssues); ?>)
  </a>
  <a href="admin/maintenance.php?tab=storage" class="btn btn-secondary btn-sm" style="<?php echo ($activeTab === 'storage') ? 'background: var(--accent-green); color: #000; font-weight: 800;' : ''; ?>">
    📁 Storage & Uploads
  </a>
  <a href="admin/maintenance.php?tab=logs" class="btn btn-secondary btn-sm" style="<?php echo ($activeTab === 'logs') ? 'background: var(--accent-green); color: #000; font-weight: 800;' : ''; ?>">
    📋 System Audit Logs
  </a>
  <a href="admin/maintenance.php?tab=settings" class="btn btn-secondary btn-sm" style="<?php echo ($activeTab === 'settings') ? 'background: var(--accent-green); color: #000; font-weight: 800;' : ''; ?>">
    ⚙️ Maintenance Mode
  </a>
</div>

<!-- TAB 1: SYSTEM HEALTH & METRICS -->
<?php if ($activeTab === 'health'): ?>
<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px;">

  <!-- Left Diagnostics Grid -->
  <div>
    <div class="card" style="margin-bottom: 24px;">
      <div class="section-header" style="margin-bottom: 16px;">
        <h2>🖥️ Server & Database Diagnostics</h2>
      </div>

      <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px;">
        <div style="background: var(--bg-dark-surface); padding: 16px; border-radius: var(--radius-md); border: 1px solid var(--border-subtle);">
          <div style="font-size: 0.78rem; color: var(--text-muted); font-weight: 700;">DATABASE STATUS</div>
          <div style="font-size: 1.2rem; font-weight: 800; color: var(--accent-green); margin-top: 4px;">
            <?php echo $health['database']['connected'] ? '🟢 Connected' : '🔴 Disconnected'; ?>
          </div>
          <div style="font-size: 0.78rem; color: var(--text-dim); margin-top: 4px;">
            Schema: <?php echo htmlspecialchars($health['database']['name']); ?> (<?php echo $health['database']['size_mb']; ?> MB)
          </div>
        </div>

        <div style="background: var(--bg-dark-surface); padding: 16px; border-radius: var(--radius-md); border: 1px solid var(--border-subtle);">
          <div style="font-size: 0.78rem; color: var(--text-muted); font-weight: 700;">DATABASE ENGINE</div>
          <div style="font-size: 1.1rem; font-weight: 800; color: #fff; margin-top: 4px;">
            <?php echo htmlspecialchars($health['database']['version']); ?>
          </div>
          <div style="font-size: 0.78rem; color: var(--text-dim); margin-top: 4px;">
            <?php echo $health['database']['table_count']; ?> Tables | <?php echo number_format($health['database']['total_records']); ?> Records
          </div>
        </div>

        <div style="background: var(--bg-dark-surface); padding: 16px; border-radius: var(--radius-md); border: 1px solid var(--border-subtle);">
          <div style="font-size: 0.78rem; color: var(--text-muted); font-weight: 700;">PHP ENVIRONMENT</div>
          <div style="font-size: 1.1rem; font-weight: 800; color: #fff; margin-top: 4px;">
            PHP v<?php echo $health['php']['version']; ?>
          </div>
          <div style="font-size: 0.78rem; color: var(--text-dim); margin-top: 4px;">
            Mem: <?php echo $health['php']['memory_limit']; ?> | Max Upload: <?php echo $health['php']['upload_max_files']; ?>
          </div>
        </div>

        <div style="background: var(--bg-dark-surface); padding: 16px; border-radius: var(--radius-md); border: 1px solid var(--border-subtle);">
          <div style="font-size: 0.78rem; color: var(--text-muted); font-weight: 700;">DISK SPACE AVAILABLE</div>
          <div style="font-size: 1.1rem; font-weight: 800; color: var(--accent-green); margin-top: 4px;">
            <?php echo $health['storage']['disk_free']; ?>
          </div>
          <div style="font-size: 0.78rem; color: var(--text-dim); margin-top: 4px;">
            Total Volume: <?php echo $health['storage']['disk_total']; ?>
          </div>
        </div>

        <div style="background: var(--bg-dark-surface); padding: 16px; border-radius: var(--radius-md); border: 1px solid var(--border-subtle);">
          <div style="font-size: 0.78rem; color: var(--text-muted); font-weight: 700;">BACKUP STORAGE</div>
          <div style="font-size: 1.1rem; font-weight: 800; color: #fff; margin-top: 4px;">
            <?php echo $health['storage']['backup_size']; ?>
          </div>
          <div style="font-size: 0.78rem; color: var(--text-dim); margin-top: 4px;">
            <?php echo $health['storage']['backup_files']; ?> SQL Dump Files Saved
          </div>
        </div>

        <div style="background: var(--bg-dark-surface); padding: 16px; border-radius: var(--radius-md); border: 1px solid var(--border-subtle);">
          <div style="font-size: 0.78rem; color: var(--text-muted); font-weight: 700;">PHP SESSION STATUS</div>
          <div style="font-size: 1.1rem; font-weight: 800; color: var(--accent-green); margin-top: 4px;">
            <?php echo $health['session']['status']; ?>
          </div>
          <div style="font-size: 0.78rem; color: var(--text-dim); margin-top: 4px; overflow: hidden; text-overflow: ellipsis;">
            Save Path: <?php echo htmlspecialchars($health['session']['save_path']); ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Right Real Database Record Summary Panel -->
  <div>
    <div class="card">
      <div class="section-header" style="margin-bottom: 14px;">
        <h2>📊 Real Database Record Counts</h2>
      </div>

      <div style="display: flex; flex-direction: column; gap: 10px;">
        <?php foreach ($summaryCounts as $entity => $count): ?>
          <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px 14px; background: var(--bg-dark-surface); border-radius: var(--radius-md); border: 1px solid var(--border-subtle);">
            <span style="font-weight: 700; color: var(--text-main); font-size: 0.9rem; text-transform: capitalize;">
              <?php echo str_replace('_', ' ', $entity); ?>
            </span>
            <span class="status-badge badge-live" style="font-weight: 800; font-size: 0.85rem;">
              <?php echo number_format($count); ?>
            </span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

</div>
<?php endif; ?>

<!-- TAB 2: DATABASE BACKUP & RECOVERY -->
<?php if ($activeTab === 'backups'): ?>
<div class="card" style="margin-bottom: 24px;">
  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
    <div>
      <h2 style="margin: 0; color: #fff; font-weight: 800;">💾 Database Backup & Recovery Center</h2>
      <p style="color: var(--text-muted); font-size: 0.88rem; margin-top: 4px;">Create full SQL backups of database structure, indexes, and records or restore from previous safety backups.</p>
    </div>

    <form action="" method="POST" style="margin: 0;">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
      <input type="hidden" name="action" value="create_backup">
      <button type="submit" class="btn btn-primary" style="background: linear-gradient(135deg, var(--accent-green), #059669); font-weight: 800;">
        ⚡ Create New Database Backup
      </button>
    </form>
  </div>

  <div class="table-responsive">
    <table class="sports-table">
      <thead>
        <tr>
          <th>Backup Filename</th>
          <th>Created Date / Time</th>
          <th>File Size</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($backupHistory)): ?>
          <tr>
            <td colspan="5" style="text-align: center; color: var(--text-muted); padding: 32px;">
              No backup files found. Click "Create New Database Backup" above to generate your first SQL snapshot.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($backupHistory as $b): ?>
            <tr>
              <td>
                <strong style="color: var(--accent-green); font-family: monospace; font-size: 0.9rem;">
                  <?php echo htmlspecialchars($b['filename']); ?>
                </strong>
              </td>
              <td style="font-size: 0.85rem; color: var(--text-muted);">
                📅 <?php echo $b['date']; ?>
              </td>
              <td style="font-weight: 700; color: #fff; font-size: 0.88rem;">
                <?php echo $b['size']; ?>
              </td>
              <td>
                <span class="status-badge badge-completed" style="font-size: 0.72rem;"><?php echo $b['status']; ?></span>
              </td>
              <td>
                <div style="display: flex; gap: 8px; align-items: center;">
                  <a href="<?php echo BASE_URL; ?>/api/maintenance/download-backup.php?file=<?php echo urlencode($b['filename']); ?>" class="btn btn-secondary btn-sm" title="Download SQL File">
                    ⬇️ Download
                  </a>

                  <button class="btn btn-secondary btn-sm" style="color: var(--accent-amber);" onclick="confirmRestore('<?php echo htmlspecialchars($b['filename']); ?>', '<?php echo $b['date']; ?>');">
                    🔄 Restore
                  </button>

                  <form action="" method="POST" style="display: inline;" onsubmit="return confirm('Delete backup file <?php echo htmlspecialchars($b['filename']); ?>?');">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                    <input type="hidden" name="action" value="delete_backup">
                    <input type="hidden" name="filename" value="<?php echo htmlspecialchars($b['filename']); ?>">
                    <button type="submit" class="btn btn-secondary btn-sm" style="color: var(--accent-red); font-size: 0.75rem;">
                      🗑 Delete
                    </button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Restore Modal -->
<div id="restoreModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.8); z-index: 9999; justify-content: center; align-items: center; padding: 20px;">
  <div style="background: var(--bg-card); border: 2px solid var(--accent-red); border-radius: var(--radius-lg); width: 100%; max-width: 540px; padding: 24px; box-shadow: 0 10px 30px rgba(0,0,0,0.6);">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
      <h3 style="margin: 0; color: var(--accent-red); font-weight: 800;">⚠️ Confirm Database Restoration</h3>
      <button onclick="document.getElementById('restoreModal').style.display='none';" style="background: none; border: none; color: var(--text-muted); font-size: 1.5rem; cursor: pointer;">&times;</button>
    </div>

    <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); padding: 14px; border-radius: var(--radius-md); margin-bottom: 16px; font-size: 0.88rem; color: var(--text-main); line-height: 1.5;">
      <strong>WARNING:</strong> Restoring this backup will replace existing database records with the selected backup snapshot!
      <br><br>
      <span style="color: var(--accent-green); font-weight: 700;">🛡️ PRE-RESTORE GUARD:</span> A mandatory safety backup of the current database will be generated automatically before restoration starts.
    </div>

    <div style="margin-bottom: 16px; font-size: 0.9rem; color: #fff;">
      Target Backup: <strong id="modalBackupName" style="color: var(--accent-green); font-family: monospace;"></strong><br>
      Backup Date: <span id="modalBackupDate" style="color: var(--text-muted);"></span>
    </div>

    <form action="" method="POST">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
      <input type="hidden" name="action" value="restore_backup">
      <input type="hidden" name="filename" id="modalFilenameInput" value="">

      <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 20px;">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('restoreModal').style.display='none';">Cancel</button>
        <button type="submit" class="btn btn-primary" style="background: var(--accent-red); border-color: var(--accent-red); font-weight: 800;">
          ⚠️ Confirm & Restore Database
        </button>
      </div>
    </form>
  </div>
</div>

<script>
function confirmRestore(filename, date) {
  document.getElementById('modalBackupName').textContent = filename;
  document.getElementById('modalBackupDate').textContent = date;
  document.getElementById('modalFilenameInput').value = filename;
  document.getElementById('restoreModal').style.display = 'flex';
}
</script>
<?php endif; ?>

<!-- TAB 3: DATA INTEGRITY & POINTS -->
<?php if ($activeTab === 'integrity'): ?>
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">

  <!-- Data Integrity Audit Panel -->
  <div class="card">
    <div class="section-header" style="margin-bottom: 16px;">
      <h2>🔍 Relational Integrity Audit</h2>
    </div>

    <?php if (empty($integrityIssues)): ?>
      <div style="text-align: center; padding: 36px 16px; background: rgba(0, 230, 118, 0.05); border: 1px solid rgba(0, 230, 118, 0.2); border-radius: var(--radius-md);">
        <div style="font-size: 2.5rem; margin-bottom: 8px;">✅</div>
        <h3 style="color: #fff; font-weight: 700; margin-bottom: 4px;">Zero Relational Issues Found</h3>
        <p style="color: var(--text-muted); font-size: 0.88rem;">All player, department, team, fixture, and result references are intact.</p>
      </div>
    <?php else: ?>
      <div style="display: flex; flex-direction: column; gap: 12px;">
        <?php foreach ($integrityIssues as $issue): ?>
          <div style="padding: 14px 16px; background: var(--bg-dark-surface); border-left: 4px solid var(--accent-amber); border-radius: var(--radius-md); border: 1px solid var(--border-subtle);">
            <div style="font-weight: 800; color: #fff; font-size: 0.95rem; margin-bottom: 4px;">
              ⚠️ <?php echo htmlspecialchars($issue['type']); ?> (<?php echo $issue['count']; ?>)
            </div>
            <p style="font-size: 0.85rem; color: var(--text-muted); margin: 0;">
              <?php echo htmlspecialchars($issue['description']); ?>
            </p>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <!-- Points & Statistics Recalculation Engine -->
  <div class="card">
    <div class="section-header" style="margin-bottom: 16px;">
      <h2>📊 Standings & Statistics Recalculator</h2>
    </div>

    <p style="color: var(--text-muted); font-size: 0.88rem; line-height: 1.6; margin-bottom: 20px;">
      If match results are corrected or re-opened, trigger these deterministic engines to recalculate department standings, gold medals, and player statistics without altering raw match scores.
    </p>

    <div style="display: flex; flex-direction: column; gap: 16px;">
      <div style="background: var(--bg-dark-surface); padding: 18px; border-radius: var(--radius-md); border: 1px solid var(--border-subtle); display: flex; justify-content: space-between; align-items: center;">
        <div>
          <h4 style="margin: 0 0 4px 0; color: #fff; font-weight: 800;">Department Trophy & Points Table</h4>
          <span style="font-size: 0.8rem; color: var(--text-muted);">Recalculate gold/silver/bronze medals and total points across all departments.</span>
        </div>
        <form action="" method="POST" style="margin: 0;">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
          <input type="hidden" name="action" value="recalculate_points">
          <button type="submit" class="btn btn-secondary btn-sm" onclick="return confirm('Recalculate all championship points and standings?');">
            🔄 Recalculate Points
          </button>
        </form>
      </div>

      <div style="background: var(--bg-dark-surface); padding: 18px; border-radius: var(--radius-md); border: 1px solid var(--border-subtle); display: flex; justify-content: space-between; align-items: center;">
        <div>
          <h4 style="margin: 0 0 4px 0; color: #fff; font-weight: 800;">Athlete & Team Statistics</h4>
          <span style="font-size: 0.8rem; color: var(--text-muted);">Rebuild goal, run, raid point, and match statistics for all sports.</span>
        </div>
        <form action="" method="POST" style="margin: 0;">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
          <input type="hidden" name="action" value="recalculate_stats">
          <button type="submit" class="btn btn-secondary btn-sm" onclick="return confirm('Rebuild all athlete and team statistics?');">
            ⚡ Rebuild Statistics
          </button>
        </form>
      </div>
    </div>
  </div>

</div>
<?php endif; ?>

<!-- TAB 4: STORAGE & UPLOADS -->
<?php if ($activeTab === 'storage'): ?>
<div class="card">
  <div class="section-header" style="margin-bottom: 16px; display: flex; justify-content: space-between; align-items: center;">
    <div>
      <h2>📁 Upload Directory Maintenance</h2>
      <p style="color: var(--text-muted); font-size: 0.88rem; margin-top: 4px;">Inspect uploads directory for active media files and safely clean up unreferenced orphaned assets.</p>
    </div>
    <div style="display: flex; gap: 12px;">
      <span class="status-badge badge-live">Files: <?php echo $uploadCheck['total_files']; ?></span>
      <span class="status-badge badge-scheduled">Size: <?php echo $uploadCheck['total_size']; ?></span>
    </div>
  </div>

  <?php if (empty($uploadCheck['orphaned'])): ?>
    <div style="text-align: center; padding: 36px 16px; background: rgba(0, 230, 118, 0.05); border: 1px solid rgba(0, 230, 118, 0.2); border-radius: var(--radius-md);">
      <div style="font-size: 2.5rem; margin-bottom: 8px;">🧹</div>
      <h3 style="color: #fff; font-weight: 700; margin-bottom: 4px;">Upload Directory Clean</h3>
      <p style="color: var(--text-muted); font-size: 0.88rem;">All uploaded images are actively referenced by user, player, or team profiles.</p>
    </div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="sports-table">
        <thead>
          <tr>
            <th>Orphaned Filename</th>
            <th>File Size</th>
            <th>Last Modified</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($uploadCheck['orphaned'] as $orph): ?>
            <tr>
              <td><code style="color: var(--accent-green);"><?php echo htmlspecialchars($orph['filename']); ?></code></td>
              <td><?php echo $orph['size']; ?></td>
              <td style="font-size: 0.85rem; color: var(--text-muted);"><?php echo $orph['mtime']; ?></td>
              <td>
                <form action="" method="POST" style="margin: 0;" onsubmit="return confirm('Delete orphaned upload file <?php echo htmlspecialchars($orph['filename']); ?>?');">
                  <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                  <input type="hidden" name="action" value="delete_orphan_upload">
                  <input type="hidden" name="filename" value="<?php echo htmlspecialchars($orph['filename']); ?>">
                  <button type="submit" class="btn btn-secondary btn-sm" style="color: var(--accent-red);">
                    🗑 Clean File
                  </button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
<?php endif; ?>

<!-- TAB 5: SYSTEM LOGS -->
<?php if ($activeTab === 'logs'): ?>
<div class="card">
  <div class="section-header" style="margin-bottom: 16px;">
    <h2>📋 System & Maintenance Audit Trail</h2>
  </div>

  <div class="table-responsive">
    <table class="sports-table">
      <thead>
        <tr>
          <th>Log ID</th>
          <th>Timestamp</th>
          <th>User</th>
          <th>Action</th>
          <th>Target Entity</th>
          <th>Details</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($auditLogs as $log): ?>
          <tr>
            <td><code>#<?php echo $log['id']; ?></code></td>
            <td style="font-size: 0.82rem; color: var(--text-dim); white-space: nowrap;"><?php echo date('M d, Y h:i A', strtotime($log['created_at'])); ?></td>
            <td style="font-weight: 700; color: #fff;"><?php echo htmlspecialchars($log['user_name'] ?? 'System / Admin'); ?></td>
            <td><span class="status-badge badge-scheduled" style="font-size: 0.72rem;"><?php echo htmlspecialchars($log['action']); ?></span></td>
            <td><?php echo htmlspecialchars($log['entity_type']); ?></td>
            <td style="font-size: 0.85rem; color: var(--text-muted);"><?php echo htmlspecialchars($log['description']); ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<!-- TAB 6: MAINTENANCE MODE SETTINGS -->
<?php if ($activeTab === 'settings'): ?>
<div class="card" style="max-width: 680px;">
  <div class="section-header" style="margin-bottom: 16px;">
    <h2>⚙️ Public Maintenance Mode Settings</h2>
  </div>

  <form action="" method="POST">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
    <input type="hidden" name="action" value="update_maintenance_mode">

    <div class="form-group">
      <label>Maintenance Mode State</label>
      <select name="maintenance_active" class="form-control">
        <option value="0" <?php echo empty($maintenanceConfig['active']) ? 'selected' : ''; ?>>🔴 Disabled (System Online for Public Spectators)</option>
        <option value="1" <?php echo !empty($maintenanceConfig['active']) ? 'selected' : ''; ?>>🟢 Enabled (Public Access Paused, Admin Access Active)</option>
      </select>
    </div>

    <div class="form-group">
      <label>Public Announcement Message *</label>
      <textarea name="maintenance_message" class="form-control" rows="3" required><?php echo htmlspecialchars($maintenanceConfig['message']); ?></textarea>
      <small style="color: var(--text-dim); font-size: 0.78rem;">Displayed on public spectator portal when maintenance mode is active.</small>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;" class="form-group">
      <div>
        <label>Maintenance Start Time (Optional)</label>
        <input type="text" name="start_time" class="form-control" placeholder="e.g. Oct 04, 2026 10:00 AM" value="<?php echo htmlspecialchars($maintenanceConfig['start_time'] ?? ''); ?>">
      </div>
      <div>
        <label>Estimated End Time (Optional)</label>
        <input type="text" name="end_time" class="form-control" placeholder="e.g. Oct 04, 2026 02:00 PM" value="<?php echo htmlspecialchars($maintenanceConfig['end_time'] ?? ''); ?>">
      </div>
    </div>

    <button type="submit" class="btn btn-primary" style="margin-top: 12px;" onclick="return confirm('Update public maintenance mode settings?');">
      Save Maintenance Settings
    </button>
  </form>
</div>
<?php endif; ?>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
