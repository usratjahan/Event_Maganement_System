<?php
// ── reports.php ───────────────────────────────────────────
require_once 'auth.php';
require_once 'db.php';
require_role('organizer');

$uid = current_user_id();

// ── Aggregate stats ──────────────────────────────────────
$s = $conn->prepare("SELECT COUNT(*) FROM events WHERE organizer_id=?"); $s->execute([$uid]);
$total_events = (int)$s->fetchColumn();

$s = $conn->prepare("SELECT COUNT(*) FROM events WHERE organizer_id=? AND status='upcoming' AND event_date>=CURDATE()"); $s->execute([$uid]);
$upcoming_events = (int)$s->fetchColumn();

$s = $conn->prepare("SELECT COUNT(*) FROM events WHERE organizer_id=? AND status='completed'"); $s->execute([$uid]);
$completed_events = (int)$s->fetchColumn();

$s = $conn->prepare("
    SELECT COUNT(*) FROM registrations r
    JOIN events e ON e.id=r.event_id
    WHERE e.organizer_id=? AND r.status='registered'
"); $s->execute([$uid]);
$total_registrations = (int)$s->fetchColumn();

$s = $conn->prepare("
    SELECT COUNT(*) FROM registrations r
    JOIN events e ON e.id=r.event_id
    WHERE e.organizer_id=? AND r.status='cancelled'
"); $s->execute([$uid]);
$cancelled_registrations = (int)$s->fetchColumn();

// ── Registrations by event type ──────────────────────────
$s = $conn->prepare("
    SELECT e.type, COUNT(r.id) AS cnt
    FROM events e
    LEFT JOIN registrations r ON r.event_id=e.id AND r.status='registered'
    WHERE e.organizer_id=?
    GROUP BY e.type
    ORDER BY cnt DESC
"); $s->execute([$uid]);
$by_type = $s->fetchAll();

// ── Events ordered by registration count ─────────────────
$s = $conn->prepare("
    SELECT e.id, e.title, e.type, e.event_date, e.capacity, e.status,
      COUNT(r.id) AS reg_count,
      ROUND(COUNT(r.id)/e.capacity*100, 0) AS fill_pct
    FROM events e
    LEFT JOIN registrations r ON r.event_id=e.id AND r.status='registered'
    WHERE e.organizer_id=?
    GROUP BY e.id
    ORDER BY reg_count DESC
    LIMIT 10
"); $s->execute([$uid]);
$top_events = $s->fetchAll();

// ── Registration trend (last 30 days) ────────────────────
$s = $conn->prepare("
    SELECT DATE(r.registered_at) AS day, COUNT(*) AS cnt
    FROM registrations r
    JOIN events e ON e.id=r.event_id
    WHERE e.organizer_id=?
      AND r.registered_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
      AND r.status='registered'
    GROUP BY day
    ORDER BY day ASC
"); $s->execute([$uid]);
$trend_rows = $s->fetchAll();

// Fill in zeros for missing days (last 14)
$trend = [];
for ($i = 13; $i >= 0; $i--) {
    $day = date('Y-m-d', strtotime("-$i days"));
    $trend[$day] = 0;
}
foreach ($trend_rows as $row) {
    if (isset($trend[$row['day']])) $trend[$row['day']] = (int)$row['cnt'];
}
$trend_max = max(array_values($trend)) ?: 1;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reports — EventMS</title>
<link rel="stylesheet" href="style.css">
<style>
/* Sparkline bar chart */
.bar-chart { display: flex; align-items: flex-end; gap: 5px; height: 90px; }
.bar-col    { display: flex; flex-direction: column; align-items: center; flex: 1; gap: 4px; }
.bar        { width: 100%; background: var(--accent); border-radius: 4px 4px 0 0; min-height: 3px; transition: opacity .15s; }
.bar:hover  { opacity: .75; }
.bar-label  { font-size: .62rem; color: var(--text-muted); white-space: nowrap; transform: rotate(-45deg); transform-origin: top right; }

/* Donut placeholder using CSS */
.type-row { display: flex; align-items: center; gap: 12px; margin-bottom: 12px; }
.type-dot  { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }
.type-bar-bg { flex: 1; height: 8px; background: var(--surface-2); border-radius: 20px; overflow: hidden; }
.type-bar-fill { height: 100%; border-radius: 20px; background: var(--accent); }
</style>
</head>
<body>

<nav class="navbar">
  <a href="dashboard.php" class="navbar-brand">Event<span>MS</span></a>
  <div class="navbar-nav">
    <a href="dashboard.php">Dashboard</a>
    <a href="events.php">Events</a>
    <a href="create_event.php">+ New Event</a>
    <a href="my_events.php">My Events</a>
    <a href="reports.php" class="active">Reports</a>
    <a href="profile.php">Profile</a>
    <a href="logout.php" style="color:#f87171;">Logout</a>
  </div>
</nav>

<div class="page-header">
  <div class="container">
    <div>
      <h1>Reports &amp; Analytics</h1>
      <p>Insights across all your events</p>
    </div>
  </div>
</div>

<div class="container" style="padding-bottom:48px;">

  <!-- ── KPI Strip ── -->
  <div class="stats-grid" style="grid-template-columns:repeat(auto-fill,minmax(160px,1fr));">
    <div class="stat-card stat-accent">
      <div class="stat-number"><?= $total_events ?></div>
      <div class="stat-label">Total Events</div>
    </div>
    <div class="stat-card stat-blue">
      <div class="stat-number"><?= $upcoming_events ?></div>
      <div class="stat-label">Upcoming</div>
    </div>
    <div class="stat-card" style="border-top:3px solid var(--text-muted);">
      <div class="stat-number"><?= $completed_events ?></div>
      <div class="stat-label">Completed</div>
    </div>
    <div class="stat-card stat-green">
      <div class="stat-number"><?= $total_registrations ?></div>
      <div class="stat-label">Active Reg.</div>
    </div>
    <div class="stat-card" style="border-top:3px solid var(--red);">
      <div class="stat-number"><?= $cancelled_registrations ?></div>
      <div class="stat-label">Cancelled Reg.</div>
    </div>
  </div>

  <!-- ── Two column: Trend + Type breakdown ── -->
  <div style="display:grid;grid-template-columns:1fr 340px;gap:24px;margin-bottom:24px;">

    <!-- Registration Trend -->
    <div class="card">
      <div class="card-header">
        <h3>Registrations — Last 14 Days</h3>
      </div>
      <div class="card-body">
        <?php if ($total_registrations > 0): ?>
        <div class="bar-chart">
          <?php foreach ($trend as $day => $cnt): ?>
          <div class="bar-col" title="<?= $day ?>: <?= $cnt ?> registrations">
            <div class="bar" style="height:<?= round($cnt / $trend_max * 80) ?>px;"></div>
            <span class="bar-label"><?= date('M j', strtotime($day)) ?></span>
          </div>
          <?php endforeach; ?>
        </div>
        <p class="text-xs text-muted mt-8">Each bar represents registrations received on that day.</p>
        <?php else: ?>
          <p class="text-muted text-sm">No registration data available yet.</p>
        <?php endif; ?>
      </div>
    </div>

    <!-- By Type -->
    <div class="card">
      <div class="card-header"><h3>Registrations by Event Type</h3></div>
      <div class="card-body">
        <?php
        $type_colors = ['seminar'=>'#c9511a','workshop'=>'#1d4ed8','meeting'=>'#15803d','conference'=>'#7c3aed','webinar'=>'#d97706','other'=>'#6b7280'];
        $type_total  = array_sum(array_column($by_type, 'cnt')) ?: 1;
        if ($by_type):
          foreach ($by_type as $row):
            $pct  = round($row['cnt'] / $type_total * 100);
            $col  = $type_colors[$row['type']] ?? '#888';
        ?>
        <div class="type-row">
          <div class="type-dot" style="background:<?= $col ?>;"></div>
          <span style="min-width:90px;font-size:.85rem;font-weight:500;"><?= ucfirst($row['type']) ?></span>
          <div class="type-bar-bg">
            <div class="type-bar-fill" style="width:<?= $pct ?>%;background:<?= $col ?>;"></div>
          </div>
          <span class="text-xs text-muted" style="min-width:32px;text-align:right;"><?= $row['cnt'] ?></span>
        </div>
        <?php endforeach; else: ?>
          <p class="text-muted text-sm">No data yet.</p>
        <?php endif; ?>
      </div>
    </div>

  </div>

  <!-- ── Top Events by Registrations ── -->
  <div class="card">
    <div class="card-header">
      <h3>Top Events by Registration</h3>
    </div>
    <div class="table-wrap">
      <?php if ($top_events): ?>
      <table>
        <thead>
          <tr><th>Event</th><th>Type</th><th>Date</th><th>Registered</th><th>Capacity</th><th>Fill Rate</th><th>Status</th></tr>
        </thead>
        <tbody>
        <?php foreach ($top_events as $ev): ?>
          <tr>
            <td><a href="view_event.php?id=<?= $ev['id'] ?>" class="fw-600"><?= htmlspecialchars($ev['title']) ?></a></td>
            <td><?= ucfirst($ev['type']) ?></td>
            <td><?= date('M j, Y', strtotime($ev['event_date'])) ?></td>
            <td><?= $ev['reg_count'] ?></td>
            <td><?= $ev['capacity'] ?></td>
            <td>
              <div style="display:flex;align-items:center;gap:8px;">
                <div style="width:70px;height:6px;background:var(--surface-2);border-radius:20px;overflow:hidden;">
                  <div style="width:<?= min(100,$ev['fill_pct']) ?>%;height:100%;background:<?= $ev['fill_pct']>=80?'var(--green)':($ev['fill_pct']>=50?'var(--yellow)':'var(--accent)') ?>;border-radius:20px;"></div>
                </div>
                <span class="text-xs"><?= $ev['fill_pct'] ?>%</span>
              </div>
            </td>
            <td><span class="badge badge-<?= $ev['status'] ?>"><?= ucfirst($ev['status']) ?></span></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <?php else: ?>
        <div class="empty-state" style="padding:32px 20px;">
          <p>Create events and get registrations to see reports here.</p>
          <a href="create_event.php" class="btn btn-primary mt-16">+ Create Event</a>
        </div>
      <?php endif; ?>
    </div>
  </div>

</div>

<style>@media(max-width:768px){.container > div[style*="grid-template-columns:1fr 340px"]{grid-template-columns:1fr!important}}</style>
</body>
</html>
