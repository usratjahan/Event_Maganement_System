<?php
// ── auth.php ──────────────────────────────────────────────
// Include at the top of every protected page.
// Usage:
//   require_once 'auth.php';            // any logged-in user
//   require_once 'auth.php'; require_role('organizer');   // organizers only
// ──────────────────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

function require_role(string $role): void {
    if ($_SESSION['role'] !== $role) {
        http_response_code(403);
        die('<!DOCTYPE html><html><head><link rel="stylesheet" href="style.css"></head><body>
             <div class="auth-wrap"><div class="auth-card"><div class="auth-header"><h1>403</h1><p>Access Denied</p></div>
             <div class="auth-body"><div class="alert alert-error">You do not have permission to view this page.</div>
             <a href="dashboard.php" class="btn btn-dark" style="width:100%;justify-content:center;">Back to Dashboard</a>
             </div></div></div></body></html>');
    }
}

function is_organizer(): bool { return $_SESSION['role'] === 'organizer'; }
function is_participant(): bool { return $_SESSION['role'] === 'participant'; }
function current_user_id(): int { return (int)$_SESSION['user_id']; }
