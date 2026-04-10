<?php
// ── create_event.php ──────────────────────────────────────
require_once 'auth.php';
require_once 'db.php';
require_role('organizer');

$error   = '';
$success = '';

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
    } elseif (!in_array($type, ['seminar','workshop','meeting','conference','webinar','other'])) {
        $error = 'Invalid event type.';
    } else {
        $stmt = $conn->prepare("INSERT INTO events(organizer_id,title,type,description,location,event_date,event_time,capacity,status) VALUES(?,?,?,?,?,?,?,?,?)");
        $stmt->execute([current_user_id(), $title, $type, $description, $location, $event_date, $event_time, $capacity, $status]);
        $new_id  = $conn->lastInsertId();
        header("Location: view_event.php?id=$new_id&created=1");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Create Event — EventMS</title>
<link rel="stylesheet" href="style.css">
</head>
<body>

<nav class="navbar">
  <a href="dashboard.php" class="navbar-brand">Event<span>MS</span></a>
  <div class="navbar-nav">
    <a href="dashboard.php">Dashboard</a>
    <a href="events.php">Events</a>
    <a href="create_event.php" class="active">+ New Event</a>
    <a href="my_events.php">My Events</a>
    <a href="reports.php">Reports</a>
    <a href="profile.php">Profile</a>
    <a href="logout.php" style="color:#f87171;">Logout</a>
  </div>
</nav>

<div class="page-header">
  <div class="container">
    <div>
      <h1>Create New Event</h1>
      <p>Fill in the details to publish your event</p>
    </div>
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
          <input type="text" name="title" class="form-control"
                 placeholder="e.g. Introduction to Machine Learning" maxlength="160"
                 value="<?= htmlspecialchars($_POST['title'] ?? '') ?>" required>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Event Type <span style="color:var(--red)">*</span></label>
            <select name="type" class="form-control" required>
              <option value="">— Select Type —</option>
              <?php foreach (['seminar','workshop','meeting','conference','webinar','other'] as $t): ?>
                <option value="<?= $t ?>" <?= (($_POST['type'] ?? '') === $t) ? 'selected' : '' ?>><?= ucfirst($t) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Status</label>
            <select name="status" class="form-control">
              <?php foreach (['upcoming','ongoing','completed','cancelled'] as $st): ?>
                <option value="<?= $st ?>" <?= (($_POST['status'] ?? 'upcoming') === $st) ? 'selected' : '' ?>><?= ucfirst($st) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Description</label>
          <textarea name="description" class="form-control" rows="4"
                    placeholder="What will attendees learn or experience?"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
        </div>

        <div class="form-group">
          <label class="form-label">Location</label>
          <input type="text" name="location" class="form-control"
                 placeholder="e.g. Room 302, Tech Campus or Online via Zoom"
                 value="<?= htmlspecialchars($_POST['location'] ?? '') ?>">
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Date <span style="color:var(--red)">*</span></label>
            <input type="date" name="event_date" class="form-control"
                   value="<?= htmlspecialchars($_POST['event_date'] ?? '') ?>"
                   min="<?= date('Y-m-d') ?>" required>
          </div>
          <div class="form-group">
            <label class="form-label">Time <span style="color:var(--red)">*</span></label>
            <input type="time" name="event_time" class="form-control"
                   value="<?= htmlspecialchars($_POST['event_time'] ?? '') ?>" required>
          </div>
        </div>

        <div class="form-group" style="max-width:200px;">
          <label class="form-label">Capacity <span style="color:var(--red)">*</span></label>
          <input type="number" name="capacity" class="form-control"
                 placeholder="e.g. 50" min="1" max="10000"
                 value="<?= htmlspecialchars($_POST['capacity'] ?? '') ?>" required>
          <p class="form-hint">Maximum number of participants</p>
        </div>

        <hr class="divider">
        <div class="flex gap-8">
          <button type="submit" class="btn btn-primary">Publish Event</button>
          <a href="my_events.php" class="btn btn-outline">Cancel</a>
        </div>
      </form>
    </div>
  </div>
</div>
</body>
</html>
