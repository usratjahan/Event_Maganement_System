<?php
// ── register.php ─────────────────────────────────────────
require 'db.php';
$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email']    ?? '');
    $password =      $_POST['password'] ?? '';
    $confirm  =      $_POST['confirm']  ?? '';
    $role     =      $_POST['role']     ?? 'participant';

    // Validate
    if (!$username || !$email || !$password) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif (!in_array($role, ['organizer','participant'])) {
        $error = 'Invalid role selected.';
    } else {
        // Check duplicate
        $chk = $conn->prepare("SELECT id FROM users WHERE email=? OR username=?");
        $chk->execute([$email, $username]);
        if ($chk->fetch()) {
            $error = 'Username or email already taken.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $ins  = $conn->prepare("INSERT INTO users(username,email,password,role) VALUES(?,?,?,?)");
            $ins->execute([$username, $email, $hash, $role]);
            $success = 'Account created! <a href="login.php">Login now →</a>';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register — EventMS</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="auth-wrap">
  <div class="auth-card">
    <div class="auth-header">
      <h1>EventMS</h1>
      <p>Create your account</p>
    </div>
    <div class="auth-body">
      <?php if ($error):   ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
      <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>

      <form method="POST" novalidate>
        <div class="form-group">
          <label class="form-label">Username</label>
          <input type="text" name="username" class="form-control"
                 placeholder="e.g. john_doe"
                 value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label">Email Address</label>
          <input type="email" name="email" class="form-control"
                 placeholder="you@example.com"
                 value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" placeholder="Min. 6 chars" required>
          </div>
          <div class="form-group">
            <label class="form-label">Confirm Password</label>
            <input type="password" name="confirm" class="form-control" placeholder="Repeat password" required>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">I am registering as</label>
          <select name="role" class="form-control">
            <option value="participant" <?= (($_POST['role'] ?? '') === 'participant') ? 'selected' : '' ?>>Participant — attend events</option>
            <option value="organizer"   <?= (($_POST['role'] ?? '') === 'organizer')   ? 'selected' : '' ?>>Organizer — create & manage events</option>
          </select>
        </div>
        <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">Create Account</button>
      </form>
    </div>
    <div class="auth-footer">
      Already have an account? <a href="login.php">Sign in</a>
    </div>
  </div>
</div>
</body>
</html>
