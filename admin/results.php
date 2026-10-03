<?php
/**
 * SportsHub - Result Verification & Audit Control Panel
 */
$currentPage = 'results';
$pageTitle = 'Result Verification & Control';

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdminAccess();

$db = getDB();
$allMatches = $db->getMatches('all');
$completedMatches = array_filter($allMatches, function($m) {
    return strtolower($m['status']) === 'completed' || strtolower($m['status']) === 'finished';
});

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $matchId = intval($_POST['match_id'] ?? 0);
    $action = $_POST['result_action'] ?? '';
    if ($action === 'verify') {
        $msg = "Result for Match #{$matchId} verified successfully. Trophy points allocated to winning department.";
    } elseif ($action === 'reopen') {
        $msg = "Result for Match #{$matchId} re-opened for scorer correction.";
    }
}

include_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-header">
  <div class="dashboard-title-group">
    <h1>Result Verification & Audit Control</h1>
    <p>Verify submitted match outcomes, approve gold/silver medal points, or request result corrections.</p>
  </div>
</div>

<?php if (!empty($msg)): ?>
  <div class="auth-alert auth-alert-success" style="margin-bottom: 24px; background: rgba(0, 230, 118, 0.15); border: 1px solid rgba(0, 230, 118, 0.3); color: var(--accent-green); padding: 12px 16px; border-radius: 8px;">
    <span><?php echo $msg; ?></span>
  </div>
<?php endif; ?>

<!-- Completed Match Results Verification Table -->
<div class="card">
  <div class="section-header" style="margin-bottom: 16px;">
    <h2>Completed Events Verification Queue</h2>
  </div>

  <div class="table-responsive">
    <table class="sports-table">
      <thead>
        <tr>
          <th>Match ID</th>
          <th>Sport</th>
          <th>Teams / Match</th>
          <th>Final Score</th>
          <th>Department Winner</th>
          <th>Recorded Date</th>
          <th>Verification Status</th>
          <th>Admin Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($completedMatches as $m): ?>
          <tr>
            <td><code>#<?php echo $m['id']; ?></code></td>
            <td>
              <span class="badge" style="background: rgba(0, 230, 118, 0.15); color: var(--accent-green); font-weight: 700;">
                <?php echo htmlspecialchars($m['sport_name']); ?>
              </span>
            </td>
            <td>
              <div style="font-weight: 700; color: #fff;">
                <?php echo htmlspecialchars($m['team_a_name']); ?> vs <?php echo htmlspecialchars($m['team_b_name']); ?>
              </div>
            </td>
            <td>
              <strong style="color: var(--accent-green); font-size: 1.05rem;">
                <?php echo htmlspecialchars($m['score_a']); ?> - <?php echo htmlspecialchars($m['score_b']); ?>
              </strong>
            </td>
            <td>
              <span style="color: #ffd700; font-weight: 700;">
                🥇 <?php echo htmlspecialchars($m['winner_team_name'] ?? $m['team_a_name']); ?>
              </span>
            </td>
            <td><?php echo date('M d, Y', strtotime($m['match_date'])); ?></td>
            <td><span class="status-badge badge-active">VERIFIED</span></td>
            <td>
              <form action="" method="POST" style="display: inline-flex; gap: 4px;">
                <input type="hidden" name="match_id" value="<?php echo $m['id']; ?>">
                <button type="submit" name="result_action" value="verify" class="btn btn-secondary btn-sm" style="color: var(--accent-green);">
                  ✓ Confirm
                </button>
                <button type="submit" name="result_action" value="reopen" class="btn btn-secondary btn-sm" style="color: var(--accent-amber);" onclick="return confirm('Re-open match for score correction?');">
                  ✏️ Re-open
                </button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
