<? compressed_code ?>
<?php
/**
 * SportsHub - Organizer Tournament List & Management Console
 */
$currentPage = 'tournaments';
$pageTitle = 'Tournament Management';

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/tournament-functions.php';

// Server-side RBAC Protection
requireRole(['admin', 'organizer']);

$csrfToken = generateCsrfToken();
$message = '';
$error = '';

// Handle POST Cancellation / Deletion Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel_tournament') {
    $token = $_POST['csrf_token'] ?? '';
    $id = intval($_POST['tournament_id'] ?? 0);

    if (!validateCsrfToken($token)) {
        $error = 'Security validation failed (Invalid CSRF Token).';
    } elseif ($id > 0) {
        cancelOrDeleteTournamentService($id);
        $message = "Tournament ID {$id} status updated to Cancelled / Removed.";
    }
}

// Search, Filters & Sorting URL parameters
$search  = trim($_GET['search'] ?? '');
$sportId = intval($_GET['sport_id'] ?? 0);
$status  = trim($_GET['status'] ?? 'all');
$format  = trim($_GET['format'] ?? 'all');
$sortBy  = trim($_GET['sort_by'] ?? 'start_date');

$tournaments = getTournamentsFiltered($search, $sportId, $status, $format, $sortBy);
$sportsList  = getSportsList();

include_once __DIR__ . '/../includes/header.php';
?>

<!-- Page Header -->
<div class="dashboard-header">
  <div class="dashboard-title-group">
    <h1>Tournament Registry Console</h1>
    <p>Manage multi-sport tournaments, configure formats, register teams, and schedule match fixtures.</p>
  </div>
  <div class="quick-actions-bar">
    <a href="<?php echo BASE_URL; ?>/organizer/create-tournament.php" class="btn btn-primary">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
      <span>+ Create Tournament</span>
    </a>
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

<!-- Search, Multi-Filter & Sorting Toolbar -->
<div class="card" style="margin-bottom:24px; padding:16px 20px;">
  <form action="" method="GET" style="display:flex; flex-wrap:wrap; gap:12px; align-items:center;">
    
    <!-- Search Box -->
    <div style="flex:1; min-width:220px;">
      <input type="text" name="search" class="form-control" placeholder="Search tournament name..." value="<?php echo htmlspecialchars($search); ?>">
    </div>

    <!-- Filter by Sport -->
    <div style="width:160px;">
      <select name="sport_id" class="form-control" onchange="this.form.submit()">
        <option value="0">All Sports</option>
        <?php foreach ($sportsList as $sp): ?>
          <option value="<?php echo $sp['id']; ?>" <?php echo ($sportId == $sp['id']) ? 'selected' : ''; ?>>
            <?php echo htmlspecialchars($sp['name']); ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <!-- Filter by Status -->
    <div style="width:140px;">
      <select name="status" class="form-control" onchange="this.form.submit()">
        <option value="all" <?php echo ($status==='all')?'selected':''; ?>>All Statuses</option>
        <option value="draft" <?php echo ($status==='draft')?'selected':''; ?>>Draft</option>
        <option value="upcoming" <?php echo ($status==='upcoming')?'selected':''; ?>>Upcoming</option>
        <option value="active" <?php echo ($status==='active')?'selected':''; ?>>Active</option>
        <option value="completed" <?php echo ($status==='completed')?'selected':''; ?>>Completed</option>
        <option value="cancelled" <?php echo ($status==='cancelled')?'selected':''; ?>>Cancelled</option>
      </select>
    </div>

    <!-- Filter by Format -->
    <div style="width:160px;">
      <select name="format" class="form-control" onchange="this.form.submit()">
        <option value="all" <?php echo ($format==='all')?'selected':''; ?>>All Formats</option>
        <option value="league" <?php echo ($format==='league')?'selected':''; ?>>League</option>
        <option value="knockout" <?php echo ($format==='knockout')?'selected':''; ?>>Knockout</option>
        <option value="round_robin" <?php echo ($format==='round_robin')?'selected':''; ?>>Round Robin</option>
        <option value="group_knockout" <?php echo ($format==='group_knockout')?'selected':''; ?>>Group + Knockout</option>
      </select>
    </div>

    <!-- Sort By -->
    <div style="width:150px;">
      <select name="sort_by" class="form-control" onchange="this.form.submit()">
        <option value="start_date" <?php echo ($sortBy==='start_date')?'selected':''; ?>>Sort: Start Date</option>
        <option value="name" <?php echo ($sortBy==='name')?'selected':''; ?>>Sort: Name</option>
        <option value="status" <?php echo ($sortBy==='status')?'selected':''; ?>>Sort: Status</option>
      </select>
    </div>

    <button type="submit" class="btn btn-secondary btn-sm">Apply</button>
    <a href="tournaments.php" class="btn btn-secondary btn-sm" style="color:var(--text-dim);">Reset</a>
  </form>
</div>

<!-- Tournaments Card Grid -->
<?php if (empty($tournaments)): ?>
  <!-- Section 10: Professional Empty State -->
  <div class="card" style="text-align:center; padding:48px 24px;">
    <div style="font-size:3rem; margin-bottom:12px;">🏆</div>
    <h3 style="font-size:1.3rem; margin-bottom:8px;">No Tournaments Found</h3>
    <p style="color:var(--text-muted); font-size:0.9rem; max-width:400px; margin:0 auto 20px auto;">
      No tournaments match your filter criteria or search query. Click below to create a new tournament.
    </p>
    <a href="<?php echo BASE_URL; ?>/organizer/create-tournament.php" class="btn btn-primary">+ Create Tournament</a>
  </div>
<?php else: ?>
  <div class="tournament-grid">
    <?php foreach ($tournaments as $t): ?>
      <div class="tournament-card" data-searchable>
        <div class="tournament-card-header">
          <div class="tournament-sport-badge">
            <?php echo getSportBadge($t['sport_name']); ?>
          </div>
          <div style="position:relative; z-index:2; align-self:flex-end;">
            <?php echo getStatusBadge($t['status']); ?>
          </div>
        </div>

        <div class="tournament-card-body">
          <div>
            <h3 class="tournament-title"><?php echo htmlspecialchars($t['name']); ?></h3>
            <div class="tournament-meta">
              <div class="tournament-meta-row">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                <span><?php echo date('M d', strtotime($t['start_date'])); ?> &ndash; <?php echo date('M d, Y', strtotime($t['end_date'])); ?></span>
              </div>
              <div class="tournament-meta-row">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                <span><?php echo htmlspecialchars($t['venue_name'] ?? 'TBD'); ?></span>
              </div>
              <div class="tournament-meta-row">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                <span>Format: <strong><?php echo htmlspecialchars($t['format']); ?></strong></span>
              </div>
            </div>
          </div>

          <div style="margin-top:14px; font-size:0.8rem; color:var(--text-dim); display:flex; justify-content:space-between; align-items:center;">
            <span>Organizer: <strong><?php echo htmlspecialchars($t['organizer_name'] ?? 'Admin'); ?></strong></span>
            <span style="color:var(--accent-green); font-weight:600;"><?php echo $t['teams_count']; ?> Registered Teams</span>
          </div>
        </div>

        <!-- Section 1 Actions: View, Edit, Manage Teams, Manage Matches, Cancel -->
        <div class="tournament-card-footer" style="flex-wrap:wrap; gap:6px; padding:10px 14px;">
          <a href="<?php echo BASE_URL; ?>/organizer/tournament-details.php?id=<?php echo $t['id']; ?>" class="btn btn-secondary btn-sm">View</a>
          <a href="<?php echo BASE_URL; ?>/organizer/edit-tournament.php?id=<?php echo $t['id']; ?>" class="btn btn-secondary btn-sm">Edit</a>
          <a href="<?php echo BASE_URL; ?>/organizer/tournament-teams.php?tournament_id=<?php echo $t['id']; ?>" class="btn btn-secondary btn-sm">Teams</a>
          <a href="<?php echo BASE_URL; ?>/admin/matches.php?tournament_id=<?php echo $t['id']; ?>" class="btn btn-secondary btn-sm">Matches</a>

          <?php if (strtolower($t['status']) !== 'cancelled'): ?>
            <form action="" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to cancel tournament <?php echo htmlspecialchars(addslashes($t['name'])); ?>?');">
              <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
              <input type="hidden" name="action" value="cancel_tournament">
              <input type="hidden" name="tournament_id" value="<?php echo $t['id']; ?>">
              <button type="submit" class="btn btn-secondary btn-sm" style="color:var(--accent-red); border-color:rgba(239,68,68,0.3);">Cancel</button>
            </form>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
