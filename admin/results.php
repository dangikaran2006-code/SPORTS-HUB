<?php
/**
 * SportsHub - Result Verification & Audit Control Panel
 */
$currentPage = 'results';
$pageTitle = 'Result Verification & Control';

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/statistics-helper.php';
require_once __DIR__ . '/../includes/notification-helper.php';

requireAdminAccess();

$db = getDB();
$msg = '';
$error = '';
$csrfToken = generateCsrfToken();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token   = $_POST['csrf_token'] ?? '';
    $matchId = intval($_POST['match_id'] ?? 0);
    $action  = $_POST['result_action'] ?? '';

    if (!validateCsrfToken($token)) {
        $error = 'Security validation failed (Invalid CSRF Token).';
    } elseif ($matchId > 0) {
        $match = getMatchById($matchId);
        if ($match) {
            if ($action === 'verify') {
                StatisticsService::processMatchResult($matchId, $match['winner_team_id'], $match['result_summary'] ?? 'Result Verified');
                logAuditAction('Result Verified', 'Match', $matchId, "Admin verified match result for Match #{$matchId}");
                triggerEventNotification($matchId, 'RESULT_PUBLISHED');
                triggerEventNotification($matchId, 'POINTS_UPDATED');
                $msg = "Result for Match #{$matchId} verified successfully. Trophy standings updated.";
            } elseif ($action === 'reopen') {
                if ($db->getConnection()) {
                    update('matches', ['status' => 'live'], 'id = :id', [':id' => $matchId]);
                    StatisticsService::recalculateTournamentStandings($match['tournament_id'], $db->getConnection());
                }
                logAuditAction('Result Re-opened', 'Match', $matchId, "Admin re-opened match #{$matchId} for score correction");
                triggerEventNotification($matchId, 'RESULT_CORRECTED', 'Match result re-opened for official score correction.');
                triggerEventNotification($matchId, 'POINTS_UPDATED');
                $msg = "Result for Match #{$matchId} re-opened for score correction.";
            }
        }
    }
}

$allMatches = $db->getMatches('all');
$completedMatches = array_filter($allMatches, function($m) {
    return strtolower($m['status']) === 'completed' || strtolower($m['status']) === 'finished';
});

include_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-header">
  <div class="dashboard-title-group">
    <h1>Result Verification & Audit Control</h1>
    <p>Verify submitted match outcomes, approve gold/silver medal points, or request result corrections.</p>
  </div>
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
          <th>Recorded Date</th>
          <th>Verification Status</th>
          <th>Admin Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($completedMatches)): ?>
          <?php foreach ($completedMatches as $m): ?>
            <tr>
              <td><code>#<?php echo $m['id']; ?></code></td>
              <td><?php echo getSportBadge($m['sport_name']); ?></td>
              <td>
                <div style="font-weight: 700; color: #fff;">
                  <?php echo htmlspecialchars($m['team_a_name']); ?> vs <?php echo htmlspecialchars($m['team_b_name']); ?>
                </div>
              </td>
              <td>
                <strong style="color: var(--accent-green); font-size: 1.05rem;">
                  <?php echo htmlspecialchars($m['score_a'] ?? '-'); ?> - <?php echo htmlspecialchars($m['score_b'] ?? '-'); ?>
                </strong>
              </td>
              <td><?php echo date('M d, Y', strtotime($m['match_date'] ?? 'now')); ?></td>
              <td><span class="status-badge badge-active">VERIFIED</span></td>
              <td>
                <form action="" method="POST" style="display: inline-flex; gap: 6px;">
                  <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
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
        <?php else: ?>
          <tr>
            <td colspan="7" style="text-align:center; color:var(--text-muted); padding:30px;">
              No completed matches pending verification currently.
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
