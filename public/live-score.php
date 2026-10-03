<?php
/**
 * SportsHub - Public Spectator Live Score Center
 * Auto-refreshing read-only live score display with real-time event log
 */
$currentPage = 'live-score';
$pageTitle   = 'Live Score Center';

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/match-functions.php';
require_once __DIR__ . '/../includes/scoring-helper.php';

$matchId = intval($_GET['match_id'] ?? 1);
$state   = ScoringService::getLiveState($matchId);

if (!$state || empty($state['match'])) {
    $matchId = 1;
    $state   = ScoringService::getLiveState($matchId);
}

$match     = $state['match'];
$liveState = $state['live_state'];
$userRole  = strtolower(currentUser()['role'] ?? 'guest');

include_once __DIR__ . '/../includes/public-header.php';
?>

<!-- Header -->
<div class="dashboard-header" style="margin-bottom:24px;">
  <div class="dashboard-title-group">
    <h1>
      <span class="pulse-dot" style="display:inline-block; margin-right:8px;"></span>
      🔴 Live Scores Center
    </h1>
    <p style="color:var(--text-muted);">Real-time live scoreboard & ball-by-ball spectator stream</p>
  </div>

  <?php if (in_array($userRole, ['admin', 'organizer', 'scorer'])): ?>
    <div>
      <a href="<?php echo BASE_URL; ?>/organizer/live-scoring.php?match_id=<?php echo $matchId; ?>" class="btn btn-primary btn-sm">
        ⚡ Open Official Scorer Console
      </a>
    </div>
  <?php endif; ?>
</div>

<!-- Main Spectator View Grid -->
<div class="dashboard-main-grid">

  <!-- Left Column: Main Score Banner & Match Metrics -->
  <div class="grid-left-col">

    <!-- Live Score Banner Card -->
    <div class="card" style="margin-bottom:24px; border-color:rgba(239, 68, 68, 0.4); position:relative; overflow:hidden;">
      <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:16px; flex-wrap:wrap; gap:10px;">
        <div>
          <span style="font-size:0.8rem; color:var(--text-muted); font-weight:600;">
            <?php echo htmlspecialchars($match['tournament_name'] ?? 'Tournament'); ?> &bull; <?php echo htmlspecialchars($match['venue_name'] ?? 'Main Arena'); ?>
          </span>
          <div style="margin-top:4px;">
            <?php echo getSportBadge($match['sport_name'] ?? 'Cricket'); ?>
          </div>
        </div>
        <div class="live-indicator-pill" id="liveStatusBadge">
          <span class="pulse-dot"></span> <?php echo strtoupper($match['status'] ?? 'LIVE'); ?>
        </div>
      </div>

      <!-- Score Summary Banner -->
      <div style="background:var(--bg-dark-surface); padding:24px; border-radius:var(--radius-md); display:flex; align-items:center; justify-content:space-between; text-align:center;">
        
        <!-- Team A Batting Score -->
        <div style="flex:1;">
          <div style="font-size:0.8rem; color:var(--accent-green); font-weight:700; text-transform:uppercase; margin-bottom:4px;">
            <?php echo ($liveState['current_innings'] ?? 1) === 1 ? '1ST INNINGS BATTING' : '2ND INNINGS BATTING'; ?>
          </div>
          <h2 style="font-size:1.5rem; color:var(--text-main);" id="pubBattingTeam">
            <?php 
              $innKey = 'innings_' . ($liveState['current_innings'] ?? 1);
              echo htmlspecialchars($liveState[$innKey]['team_name'] ?? $match['team_a_name']);
            ?>
          </h2>
          <div style="font-size:2.8rem; font-weight:800; color:var(--accent-green); line-height:1; margin-top:8px;">
            <span id="pubRuns"><?php echo $liveState[$innKey]['runs'] ?? 0; ?></span>/<span id="pubWickets"><?php echo $liveState[$innKey]['wickets'] ?? 0; ?></span>
          </div>
          <div style="font-size:0.9rem; color:var(--text-muted); margin-top:6px;">
            Overs: <strong id="pubOvers" style="color:var(--text-main);"><?php echo $liveState[$innKey]['overs_formatted'] ?? '0.0'; ?></strong> / <?php echo $liveState['max_overs'] ?? 20; ?>.0
          </div>
        </div>

        <div style="padding:0 20px; border-left:1px dashed var(--border-subtle); border-right:1px dashed var(--border-subtle);">
          <div style="font-size:1.4rem; font-weight:800; color:var(--text-dim);">VS</div>
          <div id="pubTargetBox" style="font-size:0.75rem; color:var(--accent-amber); font-weight:700; margin-top:6px; display:<?php echo !empty($liveState['target']) ? 'block' : 'none'; ?>;">
            TARGET: <span id="pubTargetVal"><?php echo $liveState['target'] ?? ''; ?></span>
          </div>
        </div>

        <!-- Team B Bowling Score -->
        <div style="flex:1;">
          <div style="font-size:0.8rem; color:var(--text-muted); font-weight:700; text-transform:uppercase; margin-bottom:4px;">
            <?php echo ($liveState['current_innings'] ?? 1) === 1 ? '2ND INNINGS BATTING' : '1ST INNINGS TOTAL'; ?>
          </div>
          <h2 style="font-size:1.5rem; color:var(--text-main);" id="pubBowlingTeam">
            <?php 
              $otherKey = ($liveState['current_innings'] ?? 1) === 1 ? 'innings_2' : 'innings_1';
              echo htmlspecialchars($liveState[$otherKey]['team_name'] ?? $match['team_b_name']);
            ?>
          </h2>
          <div style="font-size:2.5rem; font-weight:800; color:var(--text-main); line-height:1; margin-top:8px;">
            <span id="pubOtherRuns"><?php echo $liveState[$otherKey]['runs'] ?? 0; ?></span>/<span id="pubOtherWickets"><?php echo $liveState[$otherKey]['wickets'] ?? 0; ?></span>
          </div>
          <div style="font-size:0.9rem; color:var(--text-muted); margin-top:6px;">
            (<?php echo $liveState[$otherKey]['overs_formatted'] ?? '0.0'; ?> Overs)
          </div>
        </div>

      </div>

      <!-- Live Rate Metrics & Commentary -->
      <div style="display:flex; justify-content:space-around; align-items:center; padding-top:16px; font-size:0.875rem; border-top:1px dashed var(--border-subtle); margin-top:16px; flex-wrap:wrap; gap:8px;">
        <div>CRR: <strong id="pubCRR" style="color:var(--accent-green);"><?php echo $liveState['crr'] ?? '0.00'; ?></strong></div>
        <div id="pubBoxRRR" style="display:<?php echo !empty($liveState['rrr']) ? 'block' : 'none'; ?>;">RRR: <strong id="pubRRR" style="color:var(--accent-amber);"><?php echo $liveState['rrr'] ?? '0.00'; ?></strong></div>
        <div>Match Info: <strong id="pubNeedRuns" style="color:var(--text-main);"><?php echo htmlspecialchars($liveState['match_commentary'] ?? ''); ?></strong></div>
      </div>
    </div>

    <!-- Active Players Card -->
    <div class="card" style="margin-bottom:24px;">
      <h3 style="font-size:0.95rem; margin-bottom:12px;">Active Players On Field</h3>
      <div style="display:grid; grid-template-columns:repeat(3, 1fr); gap:12px; font-size:0.85rem;">
        <div style="background:var(--bg-input); padding:12px; border-radius:var(--radius-sm);">
          <div style="font-size:0.7rem; color:var(--accent-green); font-weight:700;">STRIKER 🏏</div>
          <div style="font-weight:700; color:var(--text-main); margin-top:4px;" id="pubStriker"><?php echo htmlspecialchars($liveState['striker'] ?? 'Striker'); ?></div>
        </div>
        <div style="background:var(--bg-input); padding:12px; border-radius:var(--radius-sm);">
          <div style="font-size:0.7rem; color:var(--text-muted); font-weight:700;">NON-STRIKER</div>
          <div style="font-weight:700; color:var(--text-main); margin-top:4px;" id="pubNonStriker"><?php echo htmlspecialchars($liveState['non_striker'] ?? 'Non-Striker'); ?></div>
        </div>
        <div style="background:var(--bg-input); padding:12px; border-radius:var(--radius-sm);">
          <div style="font-size:0.7rem; color:var(--accent-blue); font-weight:700;">BOWLER ⚾</div>
          <div style="font-weight:700; color:var(--text-main); margin-top:4px;" id="pubBowler"><?php echo htmlspecialchars($liveState['bowler'] ?? 'Bowler'); ?></div>
        </div>
      </div>
    </div>

    <!-- Read-Only Spectator Notice -->
    <div class="card" style="text-align:center; padding:16px; background:rgba(255, 255, 255, 0.02);">
      <span style="color:var(--text-muted); font-size:0.85rem;">
        🔒 Live Spectator Mode Active. Scores automatically synchronize via live stream every 3 seconds.
      </span>
    </div>

  </div> <!-- End Left Col -->

  <!-- Right Column: Live Event Stream Log -->
  <div class="grid-right-col">
    <div class="card">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
        <h2>Live Event Stream</h2>
        <span style="font-size:0.75rem; color:var(--text-dim);" id="pubLastUpdate">Just now</span>
      </div>

      <div id="pubEventStreamLog" style="display:flex; flex-direction:column; gap:10px; max-height:450px; overflow-y:auto;">
        <?php if (!empty($state['events'])): ?>
          <?php foreach (array_reverse($state['events']) as $ev): ?>
            <div class="event-item" style="border-left:3px solid var(--accent-green); padding:8px 12px; background:var(--bg-input); border-radius:var(--radius-sm); font-size:0.85rem;">
              <strong style="color:var(--accent-green);"><?php echo htmlspecialchars($ev['event_time'] ?? ''); ?>:</strong>
              <?php echo htmlspecialchars(strtoupper($ev['event_type']) . " (Value: {$ev['event_value']})"); ?>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div style="text-align:center; color:var(--text-dim); padding:20px; font-size:0.85rem;">
            No live events recorded yet.
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

</div>

<!-- JavaScript Auto-Polling Spectator Client -->
<script>
const MATCH_ID = <?php echo $matchId; ?>;
const API_URL = "<?php echo BASE_URL; ?>/api/scoring/get-live-score.php?match_id=" + MATCH_ID;

function updateSpectatorUI(data) {
  if (!data || !data.live_state) return;
  const ls = data.live_state;
  const innKey = 'innings_' + (ls.current_innings || 1);
  const inn = ls[innKey] || {};
  const otherKey = (ls.current_innings || 1) === 1 ? 'innings_2' : 'innings_1';
  const otherInn = ls[otherKey] || {};

  document.getElementById('pubBattingTeam').textContent = inn.team_name || 'Team';
  document.getElementById('pubRuns').textContent = inn.runs || 0;
  document.getElementById('pubWickets').textContent = inn.wickets || 0;
  document.getElementById('pubOvers').textContent = inn.overs_formatted || '0.0';

  document.getElementById('pubBowlingTeam').textContent = otherInn.team_name || 'Team';
  document.getElementById('pubOtherRuns').textContent = otherInn.runs || 0;
  document.getElementById('pubOtherWickets').textContent = otherInn.wickets || 0;

  document.getElementById('pubStriker').textContent = ls.striker || 'Striker';
  document.getElementById('pubNonStriker').textContent = ls.non_striker || 'Non-Striker';
  document.getElementById('pubBowler').textContent = ls.bowler || 'Bowler';

  document.getElementById('pubCRR').textContent = ls.crr || '0.00';
  if (ls.rrr) {
    document.getElementById('pubBoxRRR').style.display = 'block';
    document.getElementById('pubRRR').textContent = ls.rrr;
  } else {
    document.getElementById('pubBoxRRR').style.display = 'none';
  }

  if (ls.target) {
    document.getElementById('pubTargetBox').style.display = 'block';
    document.getElementById('pubTargetVal').textContent = ls.target;
  } else {
    document.getElementById('pubTargetBox').style.display = 'none';
  }

  document.getElementById('pubNeedRuns').textContent = ls.match_commentary || '';
  document.getElementById('pubLastUpdate').textContent = new Date().toLocaleTimeString();

  // Render Event Stream
  if (data.events && Array.isArray(data.events)) {
    const container = document.getElementById('pubEventStreamLog');
    container.innerHTML = '';
    const reversed = [...data.events].reverse();
    if (reversed.length === 0) {
      container.innerHTML = '<div style="text-align:center; color:var(--text-dim); padding:20px;">No events logged.</div>';
    } else {
      reversed.slice(0, 15).forEach(ev => {
        const div = document.createElement('div');
        div.style.cssText = 'border-left:3px solid var(--accent-green); padding:8px 12px; background:var(--bg-input); border-radius:var(--radius-sm); font-size:0.85rem; margin-bottom:6px;';
        div.innerHTML = `<strong style="color:var(--accent-green);">${ev.event_time || ''}:</strong> ${ev.event_type.toUpperCase()} (Value: ${ev.event_value})`;
        container.appendChild(div);
      });
    }
  }
}

// Poll API every 3 seconds
setInterval(async () => {
  try {
    const res = await fetch(API_URL);
    const data = await res.json();
    if (data.success) updateSpectatorUI(data);
  } catch (e) {}
}, 3000);
</script>

<?php include_once __DIR__ . '/../includes/public-footer.php'; ?>
