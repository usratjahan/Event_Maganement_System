<?php
// ── my_registrations.php ──────────────────────────────────
require_once 'auth.php';
require_once 'db.php';
require_role('participant');

$uid    = current_user_id();
$filter = $_GET['status'] ?? 'registered';

$where  = ['r.participant_id = ?'];
$params = [$uid];
if ($filter) { $where[] = 'r.status = ?'; $params[] = $filter; }

$stmt = $conn->prepare("
    SELECT e.*, r.registered_at, r.status AS reg_status,
      u.username AS organizer_name
    FROM registrations r
    JOIN events e ON e.id = r.event_id
    JOIN users  u ON u.id = e.organizer_id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY e.event_date ASC
");
$stmt->execute($params);
$regs = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Registrations — EventMS</title>
<link rel="stylesheet" href="style.css">
</head>
<body>

<nav class="navbar">
  <a href="dashboard.php" class="navbar-brand">Event<span>MS</span></a>
  <div class="navbar-nav">
    <a href="dashboard.php">Dashboard</a>
    <a href="events.php">Events</a>
    <a href="my_registrations.php" class="active">My Registrations</a>
    <a href="profile.php">Profile</a>
    <a href="logout.php" style="color:#f87171;">Logout</a>
  </div>
</nav>

<div class="page-header">
  <div class="container">
    <div>
      <h1>My Registrations</h1>
      <p>Track all the events you've signed up for</p>
    </div>
    <a href="events.php" class="btn btn-primary">Browse Events</a>
  </div>
</div>

<div class="container" style="padding-bottom:48px;">

  <div class="filter-bar mb-16">
    <?php foreach (['' => 'All', 'registered' => 'Active', 'cancelled' => 'Cancelled'] as $val => $label): ?>
      <a href="my_registrations.php?status=<?= $val ?>"
         class="btn btn-sm <?= $filter === $val ? 'btn-dark' : 'btn-outline' ?>">
        <?= $label ?>
      </a>
    <?php endforeach; ?>
  </div>

  <div class="card">
    <div class="table-wrap">
      <?php if ($regs): ?>
      <table>
        <thead>
          <tr><th>Event</th><th>Type</th><th>Date & Time</th><th>Organizer</th><th>Registered On</th><th>Status</th><th>Actions</th></tr>
        </thead>
        <tbody>
        <?php foreach ($regs as $r): ?>
          <tr>
            <td class="fw-600"><?= htmlspecialchars($r['title']) ?></td>
            <td><?= ucfirst($r['type']) ?></td>
            <td>
              <?= date('M j, Y', strtotime($r['event_date'])) ?><br>
              <span class="text-xs text-muted"><?= date('g:i A', strtotime($r['event_time'])) ?></span>
            </td>
            <td><?= htmlspecialchars($r['organizer_name']) ?></td>
            <td class="text-sm"><?= date('M j, Y', strtotime($r['registered_at'])) ?></td>
            <td>
              <?php if ($r['reg_status'] === 'registered'): ?>
                <span class="badge badge-upcoming">Active</span>
              <?php else: ?>
                <span class="badge badge-cancelled">Cancelled</span>
              <?php endif; ?>
            </td>
            <td class="td-actions">
              <a href="view_event.php?id=<?= $r['id'] ?>" class="btn btn-xs btn-outline">View</a>
              <?php if ($r['reg_status'] === 'registered' && $r['status'] === 'upcoming'): ?>
                <a href="cancel_registration.php?id=<?= $r['id'] ?>"
                   class="btn btn-xs btn-danger"
                   onclick="return confirm('Cancel your registration for this event?')">Cancel</a>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <?php else: ?>
        <div class="empty-state">
          <svg width="48" height="48" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
          <h3>No registrations found</h3>
          <p>You haven't registered for any events yet.</p>
          <a href="events.php" class="btn btn-primary mt-16">Browse Events</a>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>
</body>
</html>
