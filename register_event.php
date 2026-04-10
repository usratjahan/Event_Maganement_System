<?php
// ── register_event.php ────────────────────────────────────
require_once 'auth.php';
require_once 'db.php';
require_role('participant');

$id  = (int)($_GET['id'] ?? 0);
$uid = current_user_id();

// Fetch event
$stmt = $conn->prepare("SELECT * FROM events WHERE id=?");
$stmt->execute([$id]);
$ev = $stmt->fetch();
if (!$ev) { header('Location: events.php'); exit; }

// Check already registered
$chk = $conn->prepare("SELECT id FROM registrations WHERE event_id=? AND participant_id=?");
$chk->execute([$id, $uid]);
if ($chk->fetch()) {
    header("Location: view_event.php?id=$id&registered=1");
    exit;
}

// Check capacity
$cnt = $conn->prepare("SELECT COUNT(*) FROM registrations WHERE event_id=? AND status='registered'");
$cnt->execute([$id]);
if ($cnt->fetchColumn() >= $ev['capacity']) {
    header("Location: view_event.php?id=$id");
    exit;
}

// Check event is upcoming
if ($ev['status'] !== 'upcoming') {
    header("Location: view_event.php?id=$id");
    exit;
}

// Insert registration (handle duplicate gracefully)
try {
    $ins = $conn->prepare("INSERT INTO registrations(event_id, participant_id, status) VALUES(?,?,'registered')");
    $ins->execute([$id, $uid]);
} catch (PDOException $e) {
    // Unique key violation — already registered (edge case)
    $upd = $conn->prepare("UPDATE registrations SET status='registered' WHERE event_id=? AND participant_id=?");
    $upd->execute([$id, $uid]);
}

header("Location: view_event.php?id=$id&registered=1");
exit;
