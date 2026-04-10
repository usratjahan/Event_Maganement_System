<?php
// ── delete_event.php ──────────────────────────────────────
require_once 'auth.php';
require_once 'db.php';
require_role('organizer');

$id  = (int)($_GET['id'] ?? 0);
$uid = current_user_id();

// Only delete if this organizer owns the event
$stmt = $conn->prepare("DELETE FROM events WHERE id=? AND organizer_id=?");
$stmt->execute([$id, $uid]);

header('Location: my_events.php?deleted=1');
exit;
