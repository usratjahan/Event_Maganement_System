<?php
require_once 'auth.php';
require_once 'db.php';
require_role('participant');

$id  = (int)($_GET['id'] ?? 0);   // event ID
$uid = current_user_id();

$stmt = $conn->prepare("UPDATE registrations SET status='cancelled' WHERE event_id=? AND participant_id=?");
$stmt->execute([$id, $uid]);

header("Location: view_event.php?id=$id&cancelled=1");
exit;
