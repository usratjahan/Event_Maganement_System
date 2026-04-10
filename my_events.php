<?php
// ── my_events.php ─────────────────────────────────────────
require_once 'auth.php';
require_once 'db.php';
require_role('organizer');

$uid    = current_user_id();
$filter = $_GET['status'] ?? '';

$where  = ['e.organizer_id = ?'];
$params = [$uid];
if ($filter) { $where[] = 'e.status = ?'; $params[] = $filter; }

$stmt = $conn->prepare("
    SELECT e.*,
      (SELECT COUNT(*) FROM registrations r WHERE r.event_id=e.id AND r.status='registered') AS reg_count
    FROM events e
    WHERE " . implode(' AND ', $where) . "
    ORDER BY e.event_date DESC
");
$stmt->execute($params);
$events = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Events — EventMS</title>
<link rel="stylesheet" href="style.css">
</head>
<body>

<nav class="navbar">
  <a href="dashboard.php" class="navbar-brand">Event<span>MS</span></a>
  <div class="navbar-nav">
    <a href="dashboard.php">Dashboard</a>
    <a href="events.php">Events</a>
    <a href="create_event.php">+ New Event</a>
    <a href="my_events.php" class="active">My Events</a>
    <a href="reports.php">Reports</a>
    <a href="profile.php">Profile</a>
    <a href="logout.php" style="color:#f87171;">Logout</a>
  </div>
</nav>

<div class="page-header">
  <div class="container">
    <div>
      <h1>My Events</h1>
      <p>Manage all events you have created</p>
    </div>
    <a href="create_event.php" class="btn btn-primary">+ New Event</a>
  </div>
</div>

<div class="container" style="padding-bottom:48px;">

  <!-- Status Filter Tabs -->
  <div class="filter-bar mb-16">
    <?php $statuses = ['' => 'All', 'upcoming' => 'Upcoming', 'ongoing' => 'Ongoing', 'completed' => 'Completed', 'cancelled' => 'Cancelled']; ?>
    <?php foreach ($statuses as $val => $label): ?>
      <a href="my_events.php?status=<?= $val ?>"
         class="btn btn-sm <?= $filter === $val ? 'btn-dark' : 'btn-outline' ?>">
        <?= $label ?>
      </a>
    <?php endforeach; ?>
  </div>

  <div class="card">
    <div class="table-wrap">
      <?php if ($events): ?>
      <table>
        <thead>
          <tr>
            <th>Title</th><th>Type</th><th>Date</th><th>Registrations</th><th>Capacity</th><th>Status</th><th>Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($events as $ev): ?>
          <tr>
            <td class="fw-600"><?= htmlspecialchars($ev['title']) ?></td>
            <td><?= ucfirst($ev['type']) ?></td>
            <td><?= date('M j, Y', strtotime($ev['event_date'])) ?><br><span class="text-xs text-muted"><?= date('g:i A', strtotime($ev['event_time'])) ?></span></td>
            <td><?= $ev['reg_count'] ?></td>
            <td><?= $ev['capacity'] ?></td>
            <td><span class="badge badge-<?= $ev['status'] ?>"><?= ucfirst($ev['status']) ?></span></td>
            <td class="td-actions">
              <a href="view_event.php?id=<?= $ev['id'] ?>"  class="btn btn-xs btn-outline">View</a>
              <a href="edit_event.php?id=<?= $ev['id'] ?>"  class="btn btn-xs btn-warning">Edit</a>
              <a href="delete_event.php?id=<?= $ev['id'] ?>"
                 class="btn btn-xs btn-danger"
                 onclick="return confirm('Delete this event and all its registrations?')">Delete</a>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <?php else: ?>
        <div class="empty-state">
          <svg width="48" height="48" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
          <h3>No events found</h3>
          <p>You haven't created any events yet.</p>
          <a href="create_event.php" class="btn btn-primary mt-16">+ Create Your First Event</a>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>
</body>
</html>
