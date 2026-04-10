<?php
// ── events.php ────────────────────────────────────────────
require_once 'auth.php';
require_once 'db.php';

$uid  = current_user_id();
$role = $_SESSION['role'];

// Filters
$type   = $_GET['type']   ?? '';
$status = $_GET['status'] ?? 'upcoming';
$search = trim($_GET['q'] ?? '');

$where  = ['1=1'];
$params = [];

if ($type) {
    $where[] = 'e.type = ?';
    $params[] = $type;
}
if ($status) {
    $where[] = 'e.status = ?';
    $params[] = $status;
}
if ($search) {
    $where[] = '(e.title LIKE ? OR e.description LIKE ? OR e.location LIKE ?)';
    $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%";
}

$sql = "SELECT e.*,
          u.username AS organizer_name,
          (SELECT COUNT(*) FROM registrations r WHERE r.event_id=e.id AND r.status='registered') AS reg_count,
          (SELECT COUNT(*) FROM registrations r WHERE r.event_id=e.id AND r.participant_id=? AND r.status='registered') AS is_registered
        FROM events e
        JOIN users u ON u.id = e.organizer_id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY e.event_date ASC";

array_unshift($params, $uid);
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$events = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Browse Events — EventMS</title>
<link rel="stylesheet" href="style.css">
</head>
<body>

<nav class="navbar">
  <a href="dashboard.php" class="navbar-brand">Event<span>MS</span></a>
  <div class="navbar-nav">
    <a href="dashboard.php">Dashboard</a>
    <a href="events.php" class="active">Events</a>
    <?php if (is_organizer()): ?>
      <a href="create_event.php">+ New Event</a>
      <a href="my_events.php">My Events</a>
    <?php else: ?>
      <a href="my_registrations.php">My Registrations</a>
    <?php endif; ?>
    <a href="reports.php">Reports</a>
    <a href="profile.php">Profile</a>
    <a href="logout.php" style="color:#f87171;">Logout</a>
  </div>
</nav>

<div class="page-header">
  <div class="container">
    <div>
      <h1>Browse Events</h1>
      <p>Discover seminars, workshops, meetings and more</p>
    </div>
    <?php if (is_organizer()): ?>
      <a href="create_event.php" class="btn btn-primary">+ Create Event</a>
    <?php endif; ?>
  </div>
</div>

<div class="container" style="padding-bottom:48px;">

  <!-- Filter Bar -->
  <form method="GET" class="filter-bar">
    <input type="text" name="q" class="form-control search-input"
           placeholder="Search events…" value="<?= htmlspecialchars($search) ?>">
    <select name="type" class="form-control">
      <option value="">All Types</option>
      <?php foreach (['seminar','workshop','meeting','conference','webinar','other'] as $t): ?>
        <option value="<?= $t ?>" <?= $type === $t ? 'selected' : '' ?>><?= ucfirst($t) ?></option>
      <?php endforeach; ?>
    </select>
    <select name="status" class="form-control">
      <option value="">All Statuses</option>
      <?php foreach (['upcoming','ongoing','completed','cancelled'] as $st): ?>
        <option value="<?= $st ?>" <?= $status === $st ? 'selected' : '' ?>><?= ucfirst($st) ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-dark">Filter</button>
    <a href="events.php" class="btn btn-outline">Reset</a>
  </form>

  <?php if ($events): ?>
  <p class="text-muted text-sm mb-16"><?= count($events) ?> event<?= count($events) !== 1 ? 's' : '' ?> found</p>
  <div class="events-grid">
    <?php foreach ($events as $ev):
      $seats_left = $ev['capacity'] - $ev['reg_count'];
      $full       = $seats_left <= 0;
    ?>
    <div class="event-card">
      <div class="event-card-top">
        <div class="event-type-badge"><?= htmlspecialchars($ev['type']) ?></div>
        <h3><?= htmlspecialchars($ev['title']) ?></h3>
      </div>
      <div class="event-card-body">
        <div class="event-meta">
          <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
          <?= date('D, M j, Y', strtotime($ev['event_date'])) ?> &middot; <?= date('g:i A', strtotime($ev['event_time'])) ?>
        </div>
        <?php if ($ev['location']): ?>
        <div class="event-meta">
          <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
          <?= htmlspecialchars($ev['location']) ?>
        </div>
        <?php endif; ?>
        <div class="event-meta">
          <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
          <?= htmlspecialchars($ev['organizer_name']) ?>
        </div>
        <?php if ($ev['description']): ?>
        <p class="event-desc"><?= htmlspecialchars(mb_substr($ev['description'], 0, 120)) ?>…</p>
        <?php endif; ?>
      </div>
      <div class="event-card-footer">
        <div>
          <span class="badge badge-<?= $ev['status'] ?>"><?= ucfirst($ev['status']) ?></span>
          <span class="seats-info" style="margin-left:8px;">
            <?php if ($full): ?>
              <span style="color:var(--red);">Full</span>
            <?php else: ?>
              <strong><?= $seats_left ?></strong> seats left
            <?php endif; ?>
          </span>
        </div>
        <div class="flex gap-8">
          <a href="view_event.php?id=<?= $ev['id'] ?>" class="btn btn-sm btn-outline">Details</a>
          <?php if (is_participant()): ?>
            <?php if ($ev['is_registered']): ?>
              <span class="btn btn-sm btn-success" style="cursor:default;">✓ Registered</span>
            <?php elseif (!$full && $ev['status'] === 'upcoming'): ?>
              <a href="register_event.php?id=<?= $ev['id'] ?>" class="btn btn-sm btn-primary">Register</a>
            <?php endif; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <?php else: ?>
  <div class="empty-state">
    <svg width="56" height="56" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
    <h3>No events found</h3>
    <p>Try adjusting your filters or search term.</p>
  </div>
  <?php endif; ?>
</div>
</body>
</html>
