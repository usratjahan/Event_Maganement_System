<?php
// ── dashboard.php ──────────
require_once 'auth.php';
require_once 'db.php';

$uid  = current_user_id();
$role = $_SESSION['role'];

if ($role === 'organizer') {
    // My events count
    $s = $conn->prepare("SELECT COUNT(*) FROM events WHERE organizer_id=?"); $s->execute([$uid]);
    $my_events = $s->fetchColumn();

    // Total registrations across my events
    $s = $conn->prepare("SELECT COUNT(*) FROM registrations r JOIN events e ON e.id=r.event_id WHERE e.organizer_id=? AND r.status='registered'"); $s->execute([$uid]);
    $total_regs = $s->fetchColumn();

    // Upcoming events
    $s = $conn->prepare("SELECT COUNT(*) FROM events WHERE organizer_id=? AND status='upcoming' AND event_date>=CURDATE()"); $s->execute([$uid]);
    $upcoming = $s->fetchColumn();

    // Latest 5 events
    $s = $conn->prepare("SELECT e.*, (SELECT COUNT(*) FROM registrations r WHERE r.event_id=e.id AND r.status='registered') AS reg_count FROM events e WHERE e.organizer_id=? ORDER BY e.created_at DESC LIMIT 5"); $s->execute([$uid]);
    $recent_events = $s->fetchAll();

} else {
    // Participant stats
    $s = $conn->prepare("SELECT COUNT(*) FROM registrations WHERE participant_id=? AND status='registered'"); $s->execute([$uid]);
    $my_regs = $s->fetchColumn();

    $s = $conn->query("SELECT COUNT(*) FROM events WHERE status='upcoming' AND event_date>=CURDATE()");
    $all_upcoming = $s->fetchColumn();

    // Upcoming events the participant is registered for
    $s = $conn->prepare("SELECT e.*, r.registered_at FROM registrations r JOIN events e ON e.id=r.event_id WHERE r.participant_id=? AND r.status='registered' AND e.event_date>=CURDATE() ORDER BY e.event_date ASC LIMIT 5"); $s->execute([$uid]);
    $my_upcoming = $s->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard — EventMS</title>
<link rel="stylesheet" href="style.css">
</head>
<body>

<!-- Navbar -->
<nav class="navbar">
  <a href="dashboard.php" class="navbar-brand">Event<span>MS</span></a>
  <div class="navbar-nav">
    <a href="dashboard.php" class="active">Dashboard</a>
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

<!-- Page Header -->
<div class="page-header">
  <div class="container">
    <div>
      <h1>Welcome back, <?= htmlspecialchars($_SESSION['username']) ?>!</h1>
      <p>
        <span class="badge <?= $role === 'organizer' ? 'badge-organizer' : 'badge-participant' ?>">
          <?= ucfirst($role) ?>
        </span>
        &nbsp;Here's your overview for today.
      </p>
    </div>
  </div>
</div>

<div class="container" style="padding-bottom:48px;">

<?php if ($role === 'organizer'): ?>

  <!-- Organizer Stats -->
  <div class="stats-grid">
    <div class="stat-card stat-accent">
      <div class="stat-number"><?= $my_events ?></div>
      <div class="stat-label">My Events</div>
    </div>
    <div class="stat-card stat-green">
      <div class="stat-number"><?= $total_regs ?></div>
      <div class="stat-label">Total Registrations</div>
    </div>
    <div class="stat-card stat-blue">
      <div class="stat-number"><?= $upcoming ?></div>
      <div class="stat-label">Upcoming</div>
    </div>
  </div>

  <!-- Quick Actions -->
  <div class="flex gap-8 mb-24">
    <a href="create_event.php" class="btn btn-primary">+ Create Event</a>
    <a href="my_events.php"    class="btn btn-outline">Manage Events</a>
    <a href="events.php"       class="btn btn-outline">Browse All Events</a>
  </div>

  <!-- Recent Events -->
  <div class="card">
    <div class="card-header">
      <h3>My Recent Events</h3>
      <a href="my_events.php" class="btn btn-sm btn-outline">View All</a>
    </div>
    <div class="table-wrap">
      <?php if ($recent_events): ?>
      <table>
        <thead>
          <tr>
            <th>Event</th><th>Type</th><th>Date</th><th>Registrations</th><th>Status</th><th>Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($recent_events as $ev): ?>
          <tr>
            <td class="fw-600"><?= htmlspecialchars($ev['title']) ?></td>
            <td><?= ucfirst($ev['type']) ?></td>
            <td><?= date('M j, Y', strtotime($ev['event_date'])) ?></td>
            <td><?= $ev['reg_count'] ?> / <?= $ev['capacity'] ?></td>
            <td><span class="badge badge-<?= $ev['status'] ?>"><?= ucfirst($ev['status']) ?></span></td>
            <td class="td-actions">
              <a href="view_event.php?id=<?= $ev['id'] ?>"  class="btn btn-xs btn-outline">View</a>
              <a href="edit_event.php?id=<?= $ev['id'] ?>"  class="btn btn-xs btn-warning">Edit</a>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <?php else: ?>
        <div class="empty-state">
          <svg width="48" height="48" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
          <h3>No events yet</h3>
          <p>Create your first event to get started.</p>
          <a href="create_event.php" class="btn btn-primary mt-16">+ Create Event</a>
        </div>
      <?php endif; ?>
    </div>
  </div>

<?php else: /* Participant */ ?>

  <!-- Participant Stats -->
  <div class="stats-grid">
    <div class="stat-card stat-accent">
      <div class="stat-number"><?= $my_regs ?></div>
      <div class="stat-label">Registered Events</div>
    </div>
    <div class="stat-card stat-blue">
      <div class="stat-number"><?= $all_upcoming ?></div>
      <div class="stat-label">Available Events</div>
    </div>
  </div>

  <!-- Quick Actions -->
  <div class="flex gap-8 mb-24">
    <a href="events.php"            class="btn btn-primary">Browse Events</a>
    <a href="my_registrations.php"  class="btn btn-outline">My Registrations</a>
  </div>

  <!-- Upcoming Registered Events -->
  <div class="card">
    <div class="card-header">
      <h3>Your Upcoming Events</h3>
      <a href="my_registrations.php" class="btn btn-sm btn-outline">View All</a>
    </div>
    <div class="table-wrap">
      <?php if ($my_upcoming): ?>
      <table>
        <thead><tr><th>Event</th><th>Type</th><th>Date & Time</th><th>Location</th><th>Action</th></tr></thead>
        <tbody>
        <?php foreach ($my_upcoming as $ev): ?>
          <tr>
            <td class="fw-600"><?= htmlspecialchars($ev['title']) ?></td>
            <td><?= ucfirst($ev['type']) ?></td>
            <td><?= date('M j, Y', strtotime($ev['event_date'])) ?> at <?= date('g:i A', strtotime($ev['event_time'])) ?></td>
            <td><?= htmlspecialchars($ev['location'] ?? '—') ?></td>
            <td><a href="view_event.php?id=<?= $ev['id'] ?>" class="btn btn-xs btn-outline">View</a></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <?php else: ?>
        <div class="empty-state">
          <svg width="48" height="48" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
          <h3>No upcoming events</h3>
          <p>Browse available events and register today.</p>
          <a href="events.php" class="btn btn-primary mt-16">Browse Events</a>
        </div>
      <?php endif; ?>
    </div>
  </div>

<?php endif; ?>
</div>
</body>
</html>
