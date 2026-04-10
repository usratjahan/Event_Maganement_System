<?php
// ── view_event.php ────────────────────────────────────────
require_once 'auth.php';
require_once 'db.php';

$id  = (int)($_GET['id'] ?? 0);
$uid = current_user_id();

// Fetch event with organizer name + reg counts
$stmt = $conn->prepare("
    SELECT e.*, u.username AS organizer_name,
      (SELECT COUNT(*) FROM registrations r WHERE r.event_id=e.id AND r.status='registered') AS reg_count
    FROM events e
    JOIN users u ON u.id = e.organizer_id
    WHERE e.id = ?
");
$stmt->execute([$id]);
$ev = $stmt->fetch();
if (!$ev) { header('Location: events.php'); exit; }

// Is current user registered?
$chk = $conn->prepare("SELECT id FROM registrations WHERE event_id=? AND participant_id=? AND status='registered'");
$chk->execute([$id, $uid]);
$is_registered = (bool)$chk->fetch();

$seats_left    = $ev['capacity'] - $ev['reg_count'];
$is_owner      = (is_organizer() && (int)$ev['organizer_id'] === $uid);

// Registrant list (organizer only)
$registrants = [];
if ($is_owner) {
    $rs = $conn->prepare("SELECT u.id, u.username, u.email, r.registered_at, r.status FROM registrations r JOIN users u ON u.id=r.participant_id WHERE r.event_id=? ORDER BY r.registered_at ASC");
    $rs->execute([$id]);
    $registrants = $rs->fetchAll();
}

// Flash messages
$flash = '';
if (isset($_GET['created']))  $flash = 'alert-success|Event created successfully!';
if (isset($_GET['updated']))  $flash = 'alert-success|Event updated successfully.';
if (isset($_GET['registered'])) $flash = 'alert-success|You have registered for this event.';
if (isset($_GET['cancelled']))  $flash = 'alert-info|Your registration has been cancelled.';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($ev['title']) ?> — EventMS</title>
<link rel="stylesheet" href="style.css">
</head>
<body>

<nav class="navbar">
  <a href="dashboard.php" class="navbar-brand">Event<span>MS</span></a>
  <div class="navbar-nav">
    <a href="dashboard.php">Dashboard</a>
    <a href="events.php">Events</a>
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

<!-- Hero -->
<div class="page-header">
  <div class="container">
    <div>
      <div style="margin-bottom:10px;">
        <span class="badge badge-<?= $ev['status'] ?>"><?= ucfirst($ev['status']) ?></span>
        &nbsp;<span style="background:var(--accent);color:#fff;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:1px;padding:3px 10px;border-radius:20px;"><?= htmlspecialchars($ev['type']) ?></span>
      </div>
      <h1><?= htmlspecialchars($ev['title']) ?></h1>
      <p style="margin-top:8px;">Organised by <?= htmlspecialchars($ev['organizer_name']) ?></p>
    </div>
    <div class="flex gap-8" style="flex-wrap:wrap;">
      <?php if ($is_owner): ?>
        <a href="edit_event.php?id=<?= $id ?>" class="btn btn-warning">Edit Event</a>
        <a href="delete_event.php?id=<?= $id ?>"
           class="btn btn-danger"
           onclick="return confirm('Delete this event and all registrations? This cannot be undone.')">Delete</a>
      <?php endif; ?>
      <a href="events.php" class="btn btn-outline" style="color:#fff;border-color:rgba(255,255,255,.3);">← All Events</a>
    </div>
  </div>
</div>

<div class="container" style="padding-bottom:48px;">

  <?php if ($flash): [$ftype, $fmsg] = explode('|', $flash, 2); ?>
    <div class="alert <?= $ftype ?>"><?= htmlspecialchars($fmsg) ?></div>
  <?php endif; ?>

  <div style="display:grid;grid-template-columns:1fr 320px;gap:24px;align-items:start;">

    <!-- Main Column -->
    <div>
      <!-- Description -->
      <div class="card mb-24">
        <div class="card-header"><h3>About This Event</h3></div>
        <div class="card-body">
          <p style="line-height:1.75;white-space:pre-line;"><?= nl2br(htmlspecialchars($ev['description'] ?? 'No description provided.')) ?></p>
        </div>
      </div>

      <!-- Registrants (organizer only) -->
      <?php if ($is_owner): ?>
      <div class="card">
        <div class="card-header">
          <h3>Registrants <span class="nav-badge"><?= $ev['reg_count'] ?></span></h3>
        </div>
        <div class="table-wrap">
          <?php if ($registrants): ?>
          <table>
            <thead><tr><th>#</th><th>Username</th><th>Email</th><th>Registered</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($registrants as $i => $r): ?>
              <tr>
                <td><?= $i+1 ?></td>
                <td><?= htmlspecialchars($r['username']) ?></td>
                <td><?= htmlspecialchars($r['email']) ?></td>
                <td><?= date('M j, Y g:i A', strtotime($r['registered_at'])) ?></td>
                <td>
                  <?php if ($r['status'] === 'registered'): ?>
                    <span class="badge badge-upcoming">Registered</span>
                  <?php else: ?>
                    <span class="badge badge-cancelled">Cancelled</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
          <?php else: ?>
            <div class="empty-state" style="padding:32px 20px;">
              <p>No registrations yet.</p>
            </div>
          <?php endif; ?>
        </div>
      </div>
      <?php endif; ?>
    </div>

    <!-- Sidebar -->
    <div>
      <div class="card">
        <div class="card-header"><h3>Event Details</h3></div>
        <div class="card-body" style="display:flex;flex-direction:column;gap:16px;">
          <div>
            <p class="text-xs text-muted fw-600" style="text-transform:uppercase;letter-spacing:.5px;">Date & Time</p>
            <p class="fw-600"><?= date('l, F j, Y', strtotime($ev['event_date'])) ?></p>
            <p class="text-muted"><?= date('g:i A', strtotime($ev['event_time'])) ?></p>
          </div>
          <?php if ($ev['location']): ?>
          <div>
            <p class="text-xs text-muted fw-600" style="text-transform:uppercase;letter-spacing:.5px;">Location</p>
            <p class="fw-600"><?= htmlspecialchars($ev['location']) ?></p>
          </div>
          <?php endif; ?>
          <div>
            <p class="text-xs text-muted fw-600" style="text-transform:uppercase;letter-spacing:.5px;">Capacity</p>
            <p class="fw-600"><?= $ev['reg_count'] ?> / <?= $ev['capacity'] ?> registered</p>
            <div style="background:var(--surface-2);border-radius:20px;height:6px;margin-top:6px;">
              <div style="background:var(--accent);height:6px;border-radius:20px;width:<?= min(100, round($ev['reg_count']/$ev['capacity']*100)) ?>%;"></div>
            </div>
            <p class="text-xs text-muted mt-8"><?= max(0,$seats_left) ?> seats remaining</p>
          </div>

          <?php if (is_participant()): ?>
            <hr class="divider" style="margin:4px 0;">
            <?php if ($is_registered): ?>
              <div class="alert alert-success" style="margin-bottom:0;">✓ You are registered for this event.</div>
              <a href="cancel_registration.php?id=<?= $id ?>"
                 class="btn btn-danger"
                 style="width:100%;justify-content:center;"
                 onclick="return confirm('Cancel your registration?')">Cancel Registration</a>
            <?php elseif ($seats_left > 0 && $ev['status'] === 'upcoming'): ?>
              <a href="register_event.php?id=<?= $id ?>"
                 class="btn btn-primary"
                 style="width:100%;justify-content:center;">Register for Event</a>
            <?php elseif ($seats_left <= 0): ?>
              <div class="alert alert-warning" style="margin-bottom:0;">This event is fully booked.</div>
            <?php else: ?>
              <div class="alert alert-info" style="margin-bottom:0;">Registration is not available for this event.</div>
            <?php endif; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>

  </div>
</div>

<style>
@media(max-width:768px){
  .container > div[style*="grid-template-columns"]{grid-template-columns:1fr!important}
}
</style>
</body>
</html>
