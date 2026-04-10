<?php
// ── edit_event.php ────────────────────────────────────────
require_once 'auth.php';
require_once 'db.php';
require_role('organizer');

$id    = (int)($_GET['id'] ?? 0);
$error = '';

// Load event — must belong to this organizer
$stmt = $conn->prepare("SELECT * FROM events WHERE id=? AND organizer_id=?");
$stmt->execute([$id, current_user_id()]);
$ev = $stmt->fetch();
if (!$ev) { header('Location: my_events.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['title']       ?? '');
    $type        =      $_POST['type']        ?? '';
    $description = trim($_POST['description'] ?? '');
    $location    = trim($_POST['location']    ?? '');
    $event_date  =      $_POST['event_date']  ?? '';
    $event_time  =      $_POST['event_time']  ?? '';
    $capacity    = (int)($_POST['capacity']   ?? 0);
    $status      =      $_POST['status']      ?? 'upcoming';

    if (!$title || !$type || !$event_date || !$event_time || $capacity < 1) {
        $error = 'Please fill in all required fields.';
    } else {
        $stmt = $conn->prepare("UPDATE events SET title=?,type=?,description=?,location=?,event_date=?,event_time=?,capacity=?,status=? WHERE id=? AND organizer_id=?");
        $stmt->execute([$title,$type,$description,$location,$event_date,$event_time,$capacity,$status,$id,current_user_id()]);
        header("Location: view_event.php?id=$id&updated=1");
        exit;
    }
    // Re-populate form with submitted values on error
    $ev = array_merge($ev, $_POST);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Event — EventMS</title>
<link rel="stylesheet" href="style.css">
</head>
<body>

<nav class="navbar">
  <a href="dashboard.php" class="navbar-brand">Event<span>MS</span></a>
  <div class="navbar-nav">
    <a href="dashboard.php">Dashboard</a>
    <a href="events.php">Events</a>
    <a href="my_events.php" class="active">My Events</a>
    <a href="reports.php">Reports</a>
    <a href="profile.php">Profile</a>
    <a href="logout.php" style="color:#f87171;">Logout</a>
  </div>
</nav>

<div class="page-header">
  <div class="container">
    <div>
      <h1>Edit Event</h1>
      <p><?= htmlspecialchars($ev['title']) ?></p>
    </div>
    <a href="view_event.php?id=<?= $id ?>" class="btn btn-outline" style="color:#fff;border-color:rgba(255,255,255,.3);">← Back</a>
  </div>
</div>

<div class="container" style="padding-bottom:48px; max-width:720px;">
  <?php if ($error): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <div class="card">
    <div class="card-body">
      <form method="POST" novalidate>
        <div class="form-group">
          <label class="form-label">Event Title <span style="color:var(--red)">*</span></label>
          <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($ev['title']) ?>" required>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Event Type</label>
            <select name="type" class="form-control">
              <?php foreach (['seminar','workshop','meeting','conference','webinar','other'] as $t): ?>
                <option value="<?= $t ?>" <?= $ev['type'] === $t ? 'selected' : '' ?>><?= ucfirst($t) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Status</label>
            <select name="status" class="form-control">
              <?php foreach (['upcoming','ongoing','completed','cancelled'] as $st): ?>
                <option value="<?= $st ?>" <?= $ev['status'] === $st ? 'selected' : '' ?>><?= ucfirst($st) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Description</label>
          <textarea name="description" class="form-control" rows="4"><?= htmlspecialchars($ev['description'] ?? '') ?></textarea>
        </div>
        <div class="form-group">
          <label class="form-label">Location</label>
          <input type="text" name="location" class="form-control" value="<?= htmlspecialchars($ev['location'] ?? '') ?>">
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Date</label>
            <input type="date" name="event_date" class="form-control" value="<?= htmlspecialchars($ev['event_date']) ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Time</label>
            <input type="time" name="event_time" class="form-control" value="<?= htmlspecialchars(substr($ev['event_time'],0,5)) ?>">
          </div>
        </div>
        <div class="form-group" style="max-width:200px;">
          <label class="form-label">Capacity</label>
          <input type="number" name="capacity" class="form-control" value="<?= (int)$ev['capacity'] ?>" min="1">
        </div>
        <hr class="divider">
        <div class="flex gap-8">
          <button type="submit" class="btn btn-primary">Save Changes</button>
          <a href="view_event.php?id=<?= $id ?>" class="btn btn-outline">Cancel</a>
        </div>
      </form>
    </div>
  </div>
</div>
</body>
</html>
