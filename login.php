<?php
// ── login.php ─────────────────────────────────────────────
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}
require 'db.php';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password =      $_POST['password'] ?? '';

    if (!$email || !$password) {
        $error = 'Both fields are required.';
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email=?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role']     = $user['role'];
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Invalid email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login — EventMS</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="auth-wrap">
  <div class="auth-card">
    <div class="auth-header">
      <h1>Event<span style="color:rgba(255,255,255,.7)">MS</span></h1>
      <p>Sign in to your account</p>
    </div>
    <div class="auth-body">
      <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <!-- Demo credentials hint -->
      <div class="alert alert-info" style="font-size:.82rem;">
        <strong>Demo accounts (password: <code>password</code>)</strong><br>
        Organizer: <code>organizer@demo.com</code><br>
        Participant: <code>participant@demo.com</code>
      </div>

      <form method="POST" novalidate>
        <div class="form-group">
          <label class="form-label">Email Address</label>
          <input type="email" name="email" class="form-control"
                 placeholder="you@example.com"
                 value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required autofocus>
        </div>
        <div class="form-group">
          <label class="form-label">Password</label>
          <input type="password" name="password" class="form-control" placeholder="Your password" required>
        </div>
        <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">Sign In</button>
      </form>
    </div>
    <div class="auth-footer">
      Don't have an account? <a href="register.php">Create one →</a>
    </div>
  </div>
</div>
</body>
</html>
