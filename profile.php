<?php
// ── profile.php ───────────────────────────────────────────
require_once 'auth.php';
require_once 'db.php';

$uid     = current_user_id();
$success = '';
$error   = '';

// Load user
$stmt = $conn->prepare("SELECT * FROM users WHERE id=?");
$stmt->execute([$uid]);
$user = $stmt->fetch();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $username = trim($_POST['username'] ?? '');
        $email    = trim($_POST['email']    ?? '');

        if (!$username || !$email) {
            $error = 'Username and email are required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Enter a valid email address.';
        } else {
            // Check uniqueness (excluding self)
            $chk = $conn->prepare("SELECT id FROM users WHERE (email=? OR username=?) AND id != ?");
            $chk->execute([$email, $username, $uid]);
            if ($chk->fetch()) {
                $error = 'That username or email is already taken.';
            } else {
                $conn->prepare("UPDATE users SET username=?, email=? WHERE id=?")->execute([$username, $email, $uid]);
                $_SESSION['username'] = $username;
                $user['username'] = $username;
                $user['email']    = $email;
                $success = 'Profile updated successfully.';
            }
        }

    } elseif ($action === 'change_password') {
        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password']     ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if (!$current || !$new || !$confirm) {
            $error = 'All password fields are required.';
        } elseif (!password_verify($current, $user['password'])) {
            $error = 'Current password is incorrect.';
        } elseif (strlen($new) < 6) {
            $error = 'New password must be at least 6 characters.';
        } elseif ($new !== $confirm) {
            $error = 'New passwords do not match.';
        } else {
            $hash = password_hash($new, PASSWORD_DEFAULT);
            $conn->prepare("UPDATE users SET password=? WHERE id=?")->execute([$hash, $uid]);
            $success = 'Password changed successfully.';
        }
    }
}

// Stats
if ($_SESSION['role'] === 'organizer') {
    $s = $conn->prepare("SELECT COUNT(*) FROM events WHERE organizer_id=?"); $s->execute([$uid]);
    $stat1_label = 'Events Created'; $stat1_val = $s->fetchColumn();
    $s = $conn->prepare("SELECT COUNT(*) FROM registrations r JOIN events e ON e.id=r.event_id WHERE e.organizer_id=? AND r.status='registered'"); $s->execute([$uid]);
    $stat2_label = 'Total Registrations'; $stat2_val = $s->fetchColumn();
} else {
    $s = $conn->prepare("SELECT COUNT(*) FROM registrations WHERE participant_id=? AND status='registered'"); $s->execute([$uid]);
    $stat1_label = 'Events Registered'; $stat1_val = $s->fetchColumn();
    $s = $conn->prepare("SELECT COUNT(*) FROM registrations WHERE participant_id=? AND status='cancelled'"); $s->execute([$uid]);
    $stat2_label = 'Cancelled'; $stat2_val = $s->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Profile — EventMS</title>
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
    <a href="profile.php" class="active">Profile</a>
    <a href="reports.php">Reports</a>
    <a href="profile.php">Profile</a>
    <a href="logout.php" style="color:#f87171;">Logout</a>
  </div>
</nav>

<div class="page-header">
  <div class="container">
    <div>
      <h1>My Profile</h1>
      <p>Manage your account details and security settings</p>
    </div>
  </div>
</div>

<div class="container" style="padding-bottom:48px;">
  <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
  <?php if ($error):   ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

  <div style="display:grid;grid-template-columns:280px 1fr;gap:24px;align-items:start;">

    <!-- Left: Account Card -->
    <div>
      <div class="card">
        <div class="card-body" style="text-align:center;">
          <!-- Avatar initials -->
          <div style="width:72px;height:72px;border-radius:50%;background:linear-gradient(135deg,var(--accent),var(--accent-dark));display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:1.6rem;font-weight:700;color:#fff;">
            <?= strtoupper(substr($user['username'], 0, 1)) ?>
          </div>
          <h3 style="font-size:1.1rem;"><?= htmlspecialchars($user['username']) ?></h3>
          <p class="text-muted text-sm"><?= htmlspecialchars($user['email']) ?></p>
          <div style="margin-top:10px;">
            <span class="badge <?= $user['role'] === 'organizer' ? 'badge-organizer' : 'badge-participant' ?>">
              <?= ucfirst($user['role']) ?>
            </span>
          </div>
          <hr class="divider">
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;text-align:center;">
            <div>
              <div style="font-size:1.5rem;font-weight:700;"><?= $stat1_val ?></div>
              <div class="text-xs text-muted"><?= $stat1_label ?></div>
            </div>
            <div>
              <div style="font-size:1.5rem;font-weight:700;"><?= $stat2_val ?></div>
              <div class="text-xs text-muted"><?= $stat2_label ?></div>
            </div>
          </div>
          <hr class="divider">
          <p class="text-xs text-muted">Member since <?= date('F Y', strtotime($user['created_at'])) ?></p>
        </div>
      </div>
    </div>

    <!-- Right: Forms -->
    <div style="display:flex;flex-direction:column;gap:24px;">

      <!-- Update Profile -->
      <div class="card">
        <div class="card-header"><h3>Account Information</h3></div>
        <div class="card-body">
          <form method="POST" novalidate>
            <input type="hidden" name="action" value="update_profile">
            <div class="form-row">
              <div class="form-group">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control"
                       value="<?= htmlspecialchars($user['username']) ?>" required>
              </div>
              <div class="form-group">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-control"
                       value="<?= htmlspecialchars($user['email']) ?>" required>
              </div>
            </div>
            <div class="form-group">
              <label class="form-label">Role</label>
              <input type="text" class="form-control"
                     value="<?= ucfirst($user['role']) ?>" disabled
                     style="background:var(--surface-2);color:var(--text-muted);">
              <p class="form-hint">Role cannot be changed after registration.</p>
            </div>
            <button type="submit" class="btn btn-primary">Save Changes</button>
          </form>
        </div>
      </div>

      <!-- Change Password -->
      <div class="card">
        <div class="card-header"><h3>Change Password</h3></div>
        <div class="card-body">
          <form method="POST" novalidate>
            <input type="hidden" name="action" value="change_password">
            <div class="form-group">
              <label class="form-label">Current Password</label>
              <input type="password" name="current_password" class="form-control"
                     placeholder="Enter your current password" required>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label class="form-label">New Password</label>
                <input type="password" name="new_password" class="form-control"
                       placeholder="Min. 6 characters" required>
              </div>
              <div class="form-group">
                <label class="form-label">Confirm New Password</label>
                <input type="password" name="confirm_password" class="form-control"
                       placeholder="Repeat new password" required>
              </div>
            </div>
            <button type="submit" class="btn btn-dark">Update Password</button>
          </form>
        </div>
      </div>

    </div>
  </div>
</div>

<style>
@media(max-width:768px){
  .container > div[style*="grid-template-columns:280px"]{grid-template-columns:1fr!important}
}
</style>
</body>
</html>
