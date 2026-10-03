<?php
/**
 * SportsHub - Match Details Console & Quick Actions
 */
$currentPage = 'matches';
$pageTitle = 'Match Details';

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/match-functions.php';

$matchId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$match   = getMatchById($matchId);

if (!$match) {
    header('Location: ' . BASE_URL . '/organizer/matches.php');
    exit;
}

$currentUser = getCurrentUser();
$mStatus     = strtolower($match['status'] ?? 'scheduled');
$statusBadge = 'badge-upcoming';
if ($mStatus === 'live') $statusBadge = 'badge-live';
elseif ($mStatus === 'completed') $statusBadge = 'badge-active';
elseif ($mStatus === 'postponed') $statusBadge = 'badge-upcoming';
elseif ($mStatus === 'cancelled') $statusBadge = 'badge-danger';

include_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-header">
  <div class="dashboard-title-group">
    <div style="display:flex; align-items:center; gap:10px; margin-bottom:8px;">
      <?php echo getSportBadge($match['sport_name']); ?>
      <span class="status-badge <?php echo $statusBadge; ?>">
        <?php if ($mStatus === 'live'): ?>🔴 LIVE MATCH<?php else: ?><?php echo ucfirst($mStatus); ?><?php endif; ?>
      </span>
      <span style="font-size:0.85rem; color:var(--accent-amber); font-weight:700;">
        <?php echo htmlspecialchars($match['round_name'] ?? 'League Stage'); ?>
      </span>
    </div>
    <h1><?php echo htmlspecialchars($match['team_a_name']); ?> vs <?php echo htmlspecialchars($match['team_b_name']); ?></h1>
    <p>Tournament: <strong><?php echo htmlspecialchars($match['tournament_name']); ?></strong> &bull; Match ID: #<?php echo $match['id']; ?></p>
  </div>
  <div class="quick-actions-bar">
    <?php if ($mStatus === 'live' || $mStatus === 'scheduled'): ?>
      <a href="<?php echo BASE_URL; ?>/admin/scoring.php?match_id=<?php echo $match['id']; ?>" class="btn btn-primary">
        ⚡ Open Live Scoring Engine
      </a>
    <?php endif; ?>

    <?php if (in_array(strtolower($currentUser['role']), ['admin', 'organizer'])): ?>
      <a href="<?php echo BASE_URL; ?>/organizer/edit-match.php?id=<?php echo $match['id']; ?>" class="btn btn-secondary">
        Edit / Reschedule Match
      </a>
    <?php endif; ?>
    <a href="<?php echo BASE_URL; ?>/organizer/matches.php" class="btn btn-secondary">
      Back to Matches
    </a>
  </div>
</div>

<!-- Scoreboard / Match Banner -->
<div class="card" style="margin-bottom:24px; padding:32px 24px;">
  <div style="display:flex; align-items:center; justify-content:space-around; flex-wrap:wrap; gap:24px; text-align:center;">
    <!-- Team A -->
    <div style="flex:1; min-width:160px;">
      <div style="width:72px; height:72px; border-radius:50%; background:var(--bg-card-hover); border:2px solid var(--accent-green); margin:0 auto 12px auto; display:flex; align-items:center; justify-content:center; font-family:var(--font-heading); font-weight:900; font-size:1.4rem; color:var(--accent-green);">
        <?php echo htmlspecialchars($match['team_a_short']); ?>
      </div>
      <h3 style="font-size:1.2rem; font-weight:800; color:var(--text-main); margin-bottom:4px;">
        <?php echo htmlspecialchars($match['team_a_name']); ?>
      </h3>
    </div>

    <!-- Match Center / Result -->
    <div style="flex:1; min-width:200px;">
      <div style="font-size:2.2rem; font-weight:900; font-family:var(--font-heading); color:var(--accent-green); margin-bottom:8px;">
        <?php if ($mStatus === 'completed'): ?>
          FINAL SCORE
        <?php elseif ($mStatus === 'live'): ?>
          🔴 LIVE IN PROGRESS
        <?php else: ?>
          VS
        <?php endif; ?>
      </div>

      <?php if (!empty($match['result_summary'])): ?>
        <div style="background:rgba(234, 179, 8, 0.15); border:1px solid var(--accent-amber); color:var(--accent-amber); padding:8px 16px; border-radius:var(--radius-md); font-weight:700; font-size:0.95rem; display:inline-block;">
          🏆 <?php echo htmlspecialchars($match['result_summary']); ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- Team B -->
    <div style="flex:1; min-width:160px;">
      <div style="width:72px; height:72px; border-radius:50%; background:var(--bg-card-hover); border:2px solid var(--accent-green); margin:0 auto 12px auto; display:flex; align-items:center; justify-content:center; font-family:var(--font-heading); font-weight:900; font-size:1.4rem; color:var(--accent-green);">
        <?php echo htmlspecialchars($match['team_b_short']); ?>
      </div>
      <h3 style="font-size:1.2rem; font-weight:800; color:var(--text-main); margin-bottom:4px;">
        <?php echo htmlspecialchars($match['team_b_name']); ?>
      </h3>
    </div>
  </div>
</div>

<!-- Match Information Specs -->
<div class="card" style="margin-bottom:24px;">
  <h3 style="font-size:1.1rem; font-weight:700; color:var(--text-main); margin-bottom:16px;">Schedule & Venue Parameters</h3>
  <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:20px; font-size:0.9rem;">
    <div>
      <span style="color:var(--text-muted); display:block;">Scheduled Date:</span>
      <strong style="color:var(--text-main); font-size:1rem;"><?php echo !empty($match['scheduled_date']) ? date('l, M d, Y', strtotime($match['scheduled_date'])) : 'TBD'; ?></strong>
    </div>

    <div>
      <span style="color:var(--text-muted); display:block;">Scheduled Start Time:</span>
      <strong style="color:var(--text-main); font-size:1rem;"><?php echo !empty($match['scheduled_time']) ? date('h:i A', strtotime($match['scheduled_time'])) : 'TBD'; ?></strong>
    </div>

    <div>
      <span style="color:var(--text-muted); display:block;">Venue Ground:</span>
      <strong style="color:var(--text-main); font-size:1rem;">📍 <?php echo htmlspecialchars($match['venue_name'] ?? 'TBD Venue'); ?></strong>
    </div>

    <div>
      <span style="color:var(--text-muted); display:block;">Match Official:</span>
      <strong style="color:var(--text-main); font-size:1rem;">👨‍⚖️ <?php echo htmlspecialchars($match['official_name'] ?? 'Unassigned'); ?></strong>
    </div>
  </div>

  <?php if ($mStatus === 'postponed' && !empty($match['postponement_reason'])): ?>
    <div style="margin-top:20px; padding:12px 16px; background:rgba(249, 115, 22, 0.15); border:1px solid var(--accent-orange); color:var(--accent-orange); border-radius:var(--radius-md);">
      <strong>Postponement Reason:</strong> <?php echo htmlspecialchars($match['postponement_reason']); ?>
    </div>
  <?php endif; ?>

  <?php if ($mStatus === 'cancelled' && !empty($match['cancellation_reason'])): ?>
    <div style="margin-top:20px; padding:12px 16px; background:rgba(239, 68, 68, 0.15); border:1px solid var(--accent-red); color:var(--accent-red); border-radius:var(--radius-md);">
      <strong>Cancellation Reason:</strong> <?php echo htmlspecialchars($match['cancellation_reason']); ?>
    </div>
  <?php endif; ?>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
