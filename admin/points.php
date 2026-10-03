<?php
/**
 * SportsHub - Department Trophy Engine & Point Rules Configurator
 */
$currentPage = 'points';
$pageTitle = 'Department Trophy & Point Rules';

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/department-helper.php';
require_once __DIR__ . '/../includes/statistics-helper.php';

requireAdminAccess();

$msg = '';
$error = '';
$csrfToken = generateCsrfToken();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token  = $_POST['csrf_token'] ?? '';
    $action = $_POST['action'] ?? '';

    if (!validateCsrfToken($token)) {
        $error = 'Security validation failed (Invalid CSRF Token).';
    } else {
        if ($action === 'recalculate') {
            $db = getDB();
            if ($db->getConnection()) {
                // Fetch all active tournaments and recalculate standings
                $tournaments = fetchAll("SELECT id FROM tournaments");
                foreach ($tournaments as $t) {
                    StatisticsService::recalculateTournamentStandings($t['id'], $db->getConnection());
                }
            }
            logAuditAction('Trophy Standings Recalculated', 'Points', null, "Recalculated overall department trophy standings");
            $msg = "🏆 Overall Department Standings and Trophy Points successfully recalculated using current verified match results!";
        } elseif ($action === 'update_rules') {
            $teamWin    = intval($_POST['team_win'] ?? 10);
            $teamRunner = intval($_POST['team_runner'] ?? 7);
            $goldPts    = intval($_POST['gold_pts'] ?? 5);
            $silverPts  = intval($_POST['silver_pts'] ?? 3);
            $bronzePts  = intval($_POST['bronze_pts'] ?? 1);

            updateSetting('rule_team_win', $teamWin);
            updateSetting('rule_team_runner', $teamRunner);
            updateSetting('rule_gold_pts', $goldPts);
            updateSetting('rule_silver_pts', $silverPts);
            updateSetting('rule_bronze_pts', $bronzePts);

            logAuditAction('Point Rules Updated', 'Settings', null, "Updated sport point allocation matrix");
            $msg = "⚙️ Sport Point Allocation Rules updated and saved to system settings!";
        }
    }
}

$standings = DepartmentService::getOverallTrophyStandings();

include_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-header">
  <div class="dashboard-title-group">
    <h1>Department Trophy Engine & Point Rules</h1>
    <p>Automated trophy calculation matrix, gold/silver/bronze medal tallies, and configurable point rules.</p>
  </div>
  <form action="" method="POST" onsubmit="return confirm('Recalculate overall trophy standings from finalized match results?');">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
    <input type="hidden" name="action" value="recalculate">
    <button type="submit" class="btn btn-primary" style="background: linear-gradient(135deg, #ffd700, #ff9800); color: #000; font-weight: 700;">
      🔄 Recalculate Trophy Standings
    </button>
  </form>
</div>

<?php if (!empty($msg)): ?>
  <div class="auth-alert auth-alert-success" style="margin-bottom: 24px;">
    <span><?php echo $msg; ?></span>
  </div>
<?php endif; ?>

<?php if (!empty($error)): ?>
  <div class="auth-alert auth-alert-danger" style="margin-bottom: 24px;">
    <span><?php echo htmlspecialchars($error); ?></span>
  </div>
<?php endif; ?>

<!-- Overall Department Trophy Standings -->
<div class="card" style="margin-bottom: 28px; border: 1px solid rgba(255, 215, 0, 0.3); background: radial-gradient(circle at top right, rgba(255, 215, 0, 0.05), transparent 70%), var(--bg-card);">
  <div class="section-header" style="margin-bottom: 16px;">
    <h2>🏆 Official Overall Department Trophy Leaderboard</h2>
  </div>

  <div class="table-responsive">
    <table class="sports-table">
      <thead>
        <tr>
          <th>Rank</th>
          <th>Department Name</th>
          <th>Code</th>
          <th>Gold 🥇</th>
          <th>Silver 🥈</th>
          <th>Bronze 🥉</th>
          <th>Sport Performance Breakdown</th>
          <th>Total Trophy Points</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($standings as $st): ?>
          <tr>
            <td>
              <strong style="font-size: 1.1rem; color: <?php echo $st['rank'] === 1 ? '#ffd700' : ($st['rank'] === 2 ? '#cbd5e1' : ($st['rank'] === 3 ? '#f97316' : 'var(--text-main)')); ?>;">
                #<?php echo $st['rank']; ?>
              </strong>
            </td>
            <td>
              <div style="display: flex; align-items: center; gap: 10px;">
                <div class="team-badge-circle" style="width: 32px; height: 32px; font-size: 0.8rem; font-weight: 800; background: var(--bg-input); color: <?php echo $st['color_code']; ?>; border: 1px solid <?php echo $st['color_code']; ?>;">
                  <?php echo htmlspecialchars($st['short_code']); ?>
                </div>
                <strong style="color: #fff;"><?php echo htmlspecialchars($st['name']); ?></strong>
              </div>
            </td>
            <td><code><?php echo htmlspecialchars($st['short_code']); ?></code></td>
            <td><strong style="color: #ffd700; font-size: 1.05rem;"><?php echo $st['gold_medals']; ?></strong></td>
            <td><strong style="color: #cbd5e1; font-size: 1.05rem;"><?php echo $st['silver_medals']; ?></strong></td>
            <td><strong style="color: #f97316; font-size: 1.05rem;"><?php echo $st['bronze_medals']; ?></strong></td>
            <td>
              <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                <?php foreach ($st['sport_breakdown'] as $sportName => $sPts): ?>
                  <span style="background: var(--bg-input); padding: 3px 8px; border-radius: 4px; font-size: 0.75rem; color: var(--text-muted); border: 1px solid var(--border-subtle);">
                    <?php echo htmlspecialchars($sportName); ?>: <strong style="color: #fff;"><?php echo $sPts; ?>pt</strong>
                  </span>
                <?php endforeach; ?>
              </div>
            </td>
            <td>
              <strong style="font-size: 1.3rem; color: var(--accent-green);">
                <?php echo $st['total_points']; ?> PTS
              </strong>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Configurable Point Rules Form -->
<div class="card">
  <div class="section-header" style="margin-bottom: 16px;">
    <h2>⚙️ Configurable Sport Point Allocation Matrix</h2>
  </div>

  <form action="" method="POST" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px;">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
    <input type="hidden" name="action" value="update_rules">

    <div class="form-group">
      <label>Team Winner Points</label>
      <input type="number" name="team_win" class="form-control" value="<?php echo htmlspecialchars(getSetting('rule_team_win', '10')); ?>" required>
    </div>

    <div class="form-group">
      <label>Team Runner-up Points</label>
      <input type="number" name="team_runner" class="form-control" value="<?php echo htmlspecialchars(getSetting('rule_team_runner', '7')); ?>" required>
    </div>

    <div class="form-group">
      <label>Individual Gold (1st)</label>
      <input type="number" name="gold_pts" class="form-control" value="<?php echo htmlspecialchars(getSetting('rule_gold_pts', '5')); ?>" required>
    </div>

    <div class="form-group">
      <label>Individual Silver (2nd)</label>
      <input type="number" name="silver_pts" class="form-control" value="<?php echo htmlspecialchars(getSetting('rule_silver_pts', '3')); ?>" required>
    </div>

    <div class="form-group">
      <label>Individual Bronze (3rd)</label>
      <input type="number" name="bronze_pts" class="form-control" value="<?php echo htmlspecialchars(getSetting('rule_bronze_pts', '1')); ?>" required>
    </div>

    <div style="grid-column: span 3; display: flex; align-items: flex-end;">
      <button type="submit" class="btn btn-secondary" style="width: 100%;">
        💾 Save Point Rules Matrix
      </button>
    </div>
  </form>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
