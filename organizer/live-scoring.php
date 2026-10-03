<?php
/**
 * SportsHub - Official Live Scoring Console & Match Control Engine
 */
$currentPage = 'live-score';
$pageTitle   = 'Live Scoring Console';

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/match-functions.php';
require_once __DIR__ . '/../includes/scoring-helper.php';

// Enforce login and permission check
requireLogin();
requireRole(['admin', 'organizer', 'scorer']);

$matchId = intval($_GET['match_id'] ?? 1);
$state   = ScoringService::getLiveState($matchId);

if (!$state || empty($state['match'])) {
    header('Location: ' . BASE_URL . '/admin/matches.php');
    exit;
}

$match     = $state['match'];
$liveState = $state['live_state'];
$csrfToken = generateCsrfToken();
$userRole  = strtolower(currentUser()['role'] ?? 'scorer');

include_once __DIR__ . '/../includes/header.php';
?>

<!-- Top Page Banner -->
<div class="dashboard-header">
  <div class="dashboard-title-group">
    <h1>
      <span class="pulse-dot" style="display:inline-block; margin-right:8px;"></span>
      Official Live Scoring Console
    </h1>
    <p>Tournament Match Control, Ball-by-Ball Score Entry & Live Event Logging</p>
  </div>
  <div style="display:flex; gap:10px;">
    <a href="<?php echo BASE_URL; ?>/public/live-score.php?match_id=<?php echo $matchId; ?>" target="_blank" class="btn btn-secondary btn-sm">
      👁️ Open Public Spectator View
    </a>
    <a href="<?php echo BASE_URL; ?>/admin/matches.php" class="btn btn-secondary btn-sm">
      &larr; Back to Matches
    </a>
  </div>
</div>

<!-- Main Scoring Console Grid -->
<div class="dashboard-main-grid">

  <!-- Left Column: Match Status & Interactive Scoring Buttons -->
  <div class="grid-left-col">

    <!-- Match Status Banner & Lifecycle Controls -->
    <div class="card" style="margin-bottom:20px; border-color:rgba(0, 230, 118, 0.3);">
      <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
        <div>
          <span style="font-size:0.8rem; color:var(--text-muted); font-weight:600;">
            <?php echo htmlspecialchars($match['tournament_name'] ?? 'Tournament'); ?> &bull; <?php echo htmlspecialchars($match['venue_name'] ?? 'Main Arena'); ?>
          </span>
          <div style="margin-top:4px;">
            <?php echo getSportBadge($match['sport_name'] ?? 'Cricket'); ?>
            <span id="lifecycleStatusBadge"><?php echo getStatusBadge($match['status'] ?? 'scheduled'); ?></span>
          </div>
        </div>

        <!-- Lifecycle Control Action Buttons -->
        <div style="display:flex; gap:8px; flex-wrap:wrap;">
          <?php if (($match['status'] ?? 'scheduled') === 'scheduled'): ?>
            <button class="btn btn-primary btn-sm" onclick="changeMatchStatus('live')">▶ Start Match</button>
          <?php elseif (($match['status'] ?? 'scheduled') === 'live'): ?>
            <button class="btn btn-secondary btn-sm" onclick="changeMatchStatus('paused')" style="color:var(--accent-amber);">⏸ Pause</button>
            <button class="btn btn-secondary btn-sm" onclick="finishMatchPrompt()" style="color:var(--accent-green);">🏁 Finish Match</button>
          <?php elseif (($match['status'] ?? 'scheduled') === 'paused'): ?>
            <button class="btn btn-primary btn-sm" onclick="changeMatchStatus('live')">▶ Resume Match</button>
          <?php endif; ?>

          <button class="btn btn-secondary btn-sm" onclick="changeMatchStatus('postponed')" style="color:var(--accent-amber);">Postpone</button>
          <button class="btn btn-secondary btn-sm" onclick="changeMatchStatus('cancelled')" style="color:var(--accent-red);">Cancel</button>
        </div>
      </div>
    </div>

    <!-- Live Score Summary Display -->
    <div class="card" style="margin-bottom:20px; background:var(--bg-dark-surface);">
      <div style="display:flex; align-items:center; justify-content:space-between; text-align:center; padding:16px 8px;">
        
        <!-- Batting Team (Innings 1 or 2) -->
        <div style="flex:1;">
          <div style="font-size:0.75rem; color:var(--accent-green); font-weight:700; text-transform:uppercase;">
            <?php echo ($liveState['current_innings'] ?? 1) === 1 ? '1ST INNINGS BATTING' : '2ND INNINGS BATTING'; ?>
          </div>
          <h2 style="font-size:1.35rem; margin-top:4px;" id="dispBattingTeam">
            <?php 
              $innKey = 'innings_' . ($liveState['current_innings'] ?? 1);
              echo htmlspecialchars($liveState[$innKey]['team_name'] ?? $match['team_a_name']);
            ?>
          </h2>
          <div style="font-size:2.8rem; font-weight:800; color:var(--accent-green); line-height:1; margin-top:6px;">
            <span id="dispRuns"><?php echo $liveState[$innKey]['runs'] ?? 0; ?></span>/<span id="dispWickets"><?php echo $liveState[$innKey]['wickets'] ?? 0; ?></span>
          </div>
          <div style="font-size:0.85rem; color:var(--text-muted); margin-top:6px;">
            Overs: <strong id="dispOvers" style="color:var(--text-main);"><?php echo $liveState[$innKey]['overs_formatted'] ?? '0.0'; ?></strong> / <?php echo $liveState['max_overs'] ?? 20; ?>.0
          </div>
        </div>

        <div style="padding:0 16px; border-left:1px dashed var(--border-subtle); border-right:1px dashed var(--border-subtle);">
          <div style="font-size:1.2rem; font-weight:800; color:var(--text-dim);">VS</div>
          <div id="dispTargetBox" style="font-size:0.75rem; color:var(--accent-amber); font-weight:700; margin-top:6px; display:<?php echo !empty($liveState['target']) ? 'block' : 'none'; ?>;">
            TARGET: <span id="dispTargetVal"><?php echo $liveState['target'] ?? ''; ?></span>
          </div>
        </div>

        <!-- Bowling / Other Team -->
        <div style="flex:1;">
          <div style="font-size:0.75rem; color:var(--text-muted); font-weight:700; text-transform:uppercase;">
            <?php echo ($liveState['current_innings'] ?? 1) === 1 ? '2ND INNINGS BATTING' : '1ST INNINGS TOTAL'; ?>
          </div>
          <h2 style="font-size:1.35rem; margin-top:4px;" id="dispBowlingTeam">
            <?php 
              $otherKey = ($liveState['current_innings'] ?? 1) === 1 ? 'innings_2' : 'innings_1';
              echo htmlspecialchars($liveState[$otherKey]['team_name'] ?? $match['team_b_name']);
            ?>
          </h2>
          <div style="font-size:2.2rem; font-weight:800; color:var(--text-main); line-height:1; margin-top:6px;">
            <span id="dispOtherRuns"><?php echo $liveState[$otherKey]['runs'] ?? 0; ?></span>/<span id="dispOtherWickets"><?php echo $liveState[$otherKey]['wickets'] ?? 0; ?></span>
          </div>
          <div style="font-size:0.85rem; color:var(--text-muted); margin-top:6px;">
            (<?php echo $liveState[$otherKey]['overs_formatted'] ?? '0.0'; ?> Overs)
          </div>
        </div>

      </div>

      <!-- Live Commentary & Rate Strip -->
      <div style="display:flex; justify-content:space-around; padding-top:12px; margin-top:12px; border-top:1px solid var(--border-subtle); font-size:0.825rem; color:var(--text-muted);">
        <div>CRR: <strong id="dispCRR" style="color:var(--accent-green);"><?php echo $liveState['crr'] ?? '0.00'; ?></strong></div>
        <div id="boxRRR" style="display:<?php echo !empty($liveState['rrr']) ? 'block' : 'none'; ?>;">RRR: <strong id="dispRRR" style="color:var(--accent-amber);"><?php echo $liveState['rrr'] ?? '0.00'; ?></strong></div>
        <div id="dispCommentary" style="color:var(--text-main); font-weight:600;"><?php echo htmlspecialchars($liveState['match_commentary'] ?? ''); ?></div>
      </div>
    </div>

    <!-- Active Players Management Card -->
    <div class="card" style="margin-bottom:20px;">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
        <h3 style="font-size:0.95rem;">Active Players On Field</h3>
        <button class="btn btn-secondary btn-sm" onclick="promptPlayerChange()">✏ Edit Players</button>
      </div>
      <div style="display:grid; grid-template-columns:repeat(3, 1fr); gap:12px; font-size:0.85rem;">
        <div style="background:var(--bg-input); padding:10px; border-radius:var(--radius-sm);">
          <div style="font-size:0.7rem; color:var(--accent-green); font-weight:700;">STRIKER 🏏</div>
          <div style="font-weight:700; color:var(--text-main); margin-top:2px;" id="dispStriker"><?php echo htmlspecialchars($liveState['striker'] ?? 'Striker'); ?></div>
        </div>
        <div style="background:var(--bg-input); padding:10px; border-radius:var(--radius-sm);">
          <div style="font-size:0.7rem; color:var(--text-muted); font-weight:700;">NON-STRIKER</div>
          <div style="font-weight:700; color:var(--text-main); margin-top:2px;" id="dispNonStriker"><?php echo htmlspecialchars($liveState['non_striker'] ?? 'Non-Striker'); ?></div>
        </div>
        <div style="background:var(--bg-input); padding:10px; border-radius:var(--radius-sm);">
          <div style="font-size:0.7rem; color:var(--accent-blue); font-weight:700;">BOWLER ⚾</div>
          <div style="font-weight:700; color:var(--text-main); margin-top:2px;" id="dispBowler"><?php echo htmlspecialchars($liveState['bowler'] ?? 'Bowler'); ?></div>
        </div>
      </div>
    </div>

    <!-- Official Scorer Keypad Controls -->
    <div class="card">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
        <h2>Cricket Official Scorer Control Keypad</h2>
        <span style="font-size:0.75rem; color:var(--accent-green); font-weight:700; text-transform:uppercase;">Live Session Active</span>
      </div>

      <!-- Primary Run Buttons -->
      <div style="display:grid; grid-template-columns:repeat(3, 1fr); gap:12px; margin-bottom:14px;">
        <button class="btn btn-secondary" onclick="addEvent('run', 0)" style="font-size:1.2rem; font-weight:800;">0 Dot</button>
        <button class="btn btn-secondary" onclick="addEvent('run', 1)" style="font-size:1.2rem; font-weight:800;">+1 Run</button>
        <button class="btn btn-secondary" onclick="addEvent('run', 2)" style="font-size:1.2rem; font-weight:800;">+2 Runs</button>
        <button class="btn btn-secondary" onclick="addEvent('run', 3)" style="font-size:1.2rem; font-weight:800;">+3 Runs</button>
        <button class="btn btn-secondary" onclick="addEvent('boundary_4', 4)" style="font-size:1.2rem; font-weight:800; color:var(--accent-blue); border-color:rgba(59,130,246,0.4);">+4 BOUNDARY</button>
        <button class="btn btn-secondary" onclick="addEvent('boundary_6', 6)" style="font-size:1.2rem; font-weight:800; color:var(--accent-green); border-color:rgba(0,230,118,0.4);">+6 SIX!</button>
      </div>

      <!-- Extras & Wicket Buttons -->
      <div style="display:grid; grid-template-columns:repeat(4, 1fr); gap:10px; margin-bottom:14px;">
        <button class="btn btn-secondary btn-sm" onclick="addEvent('wide', 0)">+1 Wide</button>
        <button class="btn btn-secondary btn-sm" onclick="addEvent('no_ball', 0)">+1 No Ball</button>
        <button class="btn btn-secondary btn-sm" onclick="addEvent('bye', 1)">+1 Bye</button>
        <button class="btn btn-secondary btn-sm" onclick="addEvent('leg_bye', 1)">+1 Leg Bye</button>
      </div>

      <div style="display:grid; grid-template-columns:repeat(2, 1fr); gap:12px; margin-bottom:14px;">
        <button class="btn btn-secondary" onclick="addWicketPrompt()" style="font-size:1.1rem; font-weight:800; color:var(--accent-red); border-color:rgba(239,68,68,0.4);">🔴 WICKET!</button>
        <button class="btn btn-secondary" onclick="addEvent('swap_striker', 0)" style="font-size:0.9rem; font-weight:700;">🔄 Swap Strike</button>
      </div>

      <!-- Over & Innings Management Controls -->
      <div style="display:grid; grid-template-columns:repeat(3, 1fr); gap:10px;">
        <button class="btn btn-secondary btn-sm" onclick="undoEvent()" style="color:var(--accent-amber);">↺ Undo Last</button>
        <button class="btn btn-secondary btn-sm" onclick="addEvent('end_over', 0)">End Over</button>
        <button class="btn btn-secondary btn-sm" onclick="addEvent('end_innings', 0)" style="color:var(--accent-green);">End Innings</button>
      </div>
    </div>

  </div> <!-- End Left Col -->

  <!-- Right Column: Live Event Log Stream -->
  <div class="grid-right-col">
    <div class="card" style="position:sticky; top:90px;">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
        <h2>Live Match Event Stream</h2>
        <span style="font-size:0.75rem; color:var(--text-dim);" id="lastUpdateTime">Just now</span>
      </div>

      <!-- Event Stream Container -->
      <div id="eventStreamLog" style="display:flex; flex-direction:column; gap:10px; max-height:480px; overflow-y:auto;">
        <?php if (!empty($state['events'])): ?>
          <?php foreach (array_reverse($state['events']) as $ev): ?>
            <div class="event-item" style="border-left:3px solid var(--accent-green); padding:8px 12px; background:var(--bg-input); border-radius:var(--radius-sm); font-size:0.85rem;">
              <strong style="color:var(--accent-green);"><?php echo htmlspecialchars($ev['event_time'] ?? ''); ?>:</strong>
              <?php echo htmlspecialchars(strtoupper($ev['event_type']) . " (Value: {$ev['event_value']})"); ?>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div style="text-align:center; color:var(--text-dim); padding:20px; font-size:0.85rem;">
            No scoring events logged yet. Use the keypad to add match events.
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

</div>

<!-- JavaScript Async Scoring Engine Client -->
<script>
const MATCH_ID = <?php echo $matchId; ?>;
const CSRF_TOKEN = "<?php echo $csrfToken; ?>";
const API_BASE = "<?php echo BASE_URL; ?>/api/scoring";

function updateUI(data) {
  if (!data || !data.live_state) return;
  const ls = data.live_state;
  const innKey = 'innings_' + (ls.current_innings || 1);
  const inn = ls[innKey] || {};
  const otherKey = (ls.current_innings || 1) === 1 ? 'innings_2' : 'innings_1';
  const otherInn = ls[otherKey] || {};

  document.getElementById('dispBattingTeam').textContent = inn.team_name || 'Team';
  document.getElementById('dispRuns').textContent = inn.runs || 0;
  document.getElementById('dispWickets').textContent = inn.wickets || 0;
  document.getElementById('dispOvers').textContent = inn.overs_formatted || '0.0';

  document.getElementById('dispBowlingTeam').textContent = otherInn.team_name || 'Team';
  document.getElementById('dispOtherRuns').textContent = otherInn.runs || 0;
  document.getElementById('dispOtherWickets').textContent = otherInn.wickets || 0;

  document.getElementById('dispStriker').textContent = ls.striker || 'Striker';
  document.getElementById('dispNonStriker').textContent = ls.non_striker || 'Non-Striker';
  document.getElementById('dispBowler').textContent = ls.bowler || 'Bowler';

  document.getElementById('dispCRR').textContent = ls.crr || '0.00';
  if (ls.rrr) {
    document.getElementById('boxRRR').style.display = 'block';
    document.getElementById('dispRRR').textContent = ls.rrr;
  } else {
    document.getElementById('boxRRR').style.display = 'none';
  }

  if (ls.target) {
    document.getElementById('dispTargetBox').style.display = 'block';
    document.getElementById('dispTargetVal').textContent = ls.target;
  } else {
    document.getElementById('dispTargetBox').style.display = 'none';
  }

  document.getElementById('dispCommentary').textContent = ls.match_commentary || '';
  document.getElementById('lastUpdateTime').textContent = new Date().toLocaleTimeString();

  // Render Event Stream
  if (data.events && Array.isArray(data.events)) {
    const container = document.getElementById('eventStreamLog');
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

async function addEvent(type, val, extraData = {}) {
  const formData = new FormData();
  formData.append('csrf_token', CSRF_TOKEN);
  formData.append('match_id', MATCH_ID);
  formData.append('event_type', type);
  formData.append('event_value', val);
  if (Object.keys(extraData).length > 0) {
    formData.append('event_data', JSON.stringify(extraData));
  }

  try {
    const res = await fetch(`${API_BASE}/add-event.php`, { method: 'POST', body: formData });
    const data = await res.json();
    if (data.success) {
      updateUI(data);
    } else {
      alert(data.error || 'Failed to record event.');
    }
  } catch (err) {
    console.error(err);
  }
}

async function undoEvent() {
  const formData = new FormData();
  formData.append('csrf_token', CSRF_TOKEN);
  formData.append('match_id', MATCH_ID);

  try {
    const res = await fetch(`${API_BASE}/undo-event.php`, { method: 'POST', body: formData });
    const data = await res.json();
    if (data.success) {
      updateUI(data);
    } else {
      alert(data.error || 'Failed to undo.');
    }
  } catch (err) { console.error(err); }
}

async function changeMatchStatus(status) {
  const formData = new FormData();
  formData.append('csrf_token', CSRF_TOKEN);
  formData.append('match_id', MATCH_ID);
  formData.append('status', status);

  try {
    const res = await fetch(`${API_BASE}/start-match.php`, { method: 'POST', body: formData });
    const data = await res.json();
    if (data.success) {
      location.reload();
    } else {
      alert(data.error || 'Status update failed.');
    }
  } catch (err) { console.error(err); }
}

async function finishMatchPrompt() {
  if (!confirm("Are you sure you want to finish this match and complete it?")) return;
  const formData = new FormData();
  formData.append('csrf_token', CSRF_TOKEN);
  formData.append('match_id', MATCH_ID);

  try {
    const res = await fetch(`${API_BASE}/finish-match.php`, { method: 'POST', body: formData });
    const data = await res.json();
    if (data.success) {
      alert("Match completed!");
      location.reload();
    }
  } catch (err) { console.error(err); }
}

function addWicketPrompt() {
  const newBat = prompt("WICKET! Enter new batsman name:");
  addEvent('wicket', 1, { new_batsman: newBat || 'Next Batsman' });
}

function promptPlayerChange() {
  const str = prompt("Enter Striker Name:", document.getElementById('dispStriker').textContent);
  const nstr = prompt("Enter Non-Striker Name:", document.getElementById('dispNonStriker').textContent);
  const bowl = prompt("Enter Bowler Name:", document.getElementById('dispBowler').textContent);
  addEvent('change_players', 0, { striker: str, non_striker: nstr, bowler: bowl });
}

// Auto Refresh Polling Every 4 Seconds
setInterval(async () => {
  try {
    const res = await fetch(`${API_BASE}/get-live-score.php?match_id=${MATCH_ID}`);
    const data = await res.json();
    if (data.success) updateUI(data);
  } catch (e) {}
}, 4000);
</script>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
