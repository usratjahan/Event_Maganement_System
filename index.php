<?php
// ── index.php ─────────────────────────────────────────────
// Landing page — redirect logged-in users straight to dashboard
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>EventMS — Professional Event Management</title>
<link rel="stylesheet" href="style.css">
<style>
/* ── Landing-specific styles ── */
.hero {
  min-height: 100vh;
  background: linear-gradient(135deg, #1a1714 0%, #2d2520 45%, #1a1714 100%);
  display: flex;
  flex-direction: column;
  position: relative;
  overflow: hidden;
}
.hero::before {
  content: '';
  position: absolute;
  inset: 0;
  background:
    radial-gradient(ellipse 60% 50% at 70% 40%, rgba(201,81,26,.18) 0%, transparent 70%),
    radial-gradient(ellipse 40% 60% at 20% 80%, rgba(201,81,26,.08) 0%, transparent 70%);
}
.hero-nav {
  position: relative;
  z-index: 10;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 20px 48px;
  border-bottom: 1px solid rgba(255,255,255,.07);
}
.hero-logo {
  font-family: 'Playfair Display', serif;
  font-size: 1.5rem;
  color: #fff;
  letter-spacing: .5px;
}
.hero-logo span { color: var(--accent); }
.hero-body {
  position: relative;
  z-index: 10;
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
  padding: 60px 24px;
}
.hero-eyebrow {
  display: inline-block;
  background: rgba(201,81,26,.2);
  border: 1px solid rgba(201,81,26,.4);
  color: #f4a47a;
  font-size: .75rem;
  font-weight: 700;
  letter-spacing: 2px;
  text-transform: uppercase;
  padding: 5px 16px;
  border-radius: 20px;
  margin-bottom: 28px;
}
.hero-title {
  font-family: 'Playfair Display', serif;
  font-size: clamp(2.6rem, 6vw, 4.2rem);
  color: #fff;
  line-height: 1.15;
  max-width: 780px;
  margin-bottom: 22px;
}
.hero-title em { color: var(--accent); font-style: normal; }
.hero-sub {
  font-size: 1.05rem;
  color: #a09892;
  max-width: 520px;
  line-height: 1.7;
  margin-bottom: 42px;
}
.hero-cta { display: flex; gap: 14px; justify-content: center; flex-wrap: wrap; }
.btn-hero-primary {
  background: var(--accent);
  color: #fff;
  font-size: 1rem;
  padding: 14px 34px;
  border-radius: var(--radius);
  font-weight: 700;
  transition: background .18s, transform .18s;
  border: none;
}
.btn-hero-primary:hover { background: var(--accent-dark); color: #fff; transform: translateY(-1px); }
.btn-hero-ghost {
  background: transparent;
  color: #fff;
  font-size: 1rem;
  padding: 13px 34px;
  border-radius: var(--radius);
  font-weight: 600;
  border: 1.5px solid rgba(255,255,255,.25);
  transition: border-color .18s, background .18s;
}
.btn-hero-ghost:hover { border-color: rgba(255,255,255,.6); background: rgba(255,255,255,.05); color: #fff; }

/* Features */
.features {
  padding: 80px 24px;
  background: var(--surface);
}
.section-label {
  text-align: center;
  font-size: .75rem;
  font-weight: 700;
  letter-spacing: 2px;
  text-transform: uppercase;
  color: var(--accent);
  margin-bottom: 12px;
}
.section-title {
  text-align: center;
  font-size: clamp(1.8rem, 4vw, 2.4rem);
  margin-bottom: 56px;
}
.features-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
  gap: 24px;
  max-width: 1060px;
  margin: 0 auto;
}
.feature-card {
  padding: 28px;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  background: var(--bg);
  transition: box-shadow .18s, transform .18s;
}
.feature-card:hover { box-shadow: var(--shadow-lg); transform: translateY(-3px); }
.feature-icon {
  width: 48px; height: 48px;
  background: var(--accent-soft);
  border-radius: 12px;
  display: flex; align-items: center; justify-content: center;
  margin-bottom: 18px;
  color: var(--accent);
}
.feature-card h3 { font-size: 1.05rem; margin-bottom: 8px; }
.feature-card p  { font-size: .88rem; color: var(--text-muted); line-height: 1.65; }

/* Roles */
.roles {
  padding: 80px 24px;
  background: var(--bg);
}
.roles-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 24px;
  max-width: 860px;
  margin: 0 auto;
}
.role-card {
  padding: 36px;
  border-radius: var(--radius);
  border: 1px solid var(--border);
  background: var(--surface);
  box-shadow: var(--shadow);
}
.role-card h3 { font-size: 1.3rem; margin-bottom: 14px; }
.role-card ul { list-style: none; display: flex; flex-direction: column; gap: 10px; }
.role-card ul li { display: flex; align-items: flex-start; gap: 10px; font-size: .9rem; color: var(--text-muted); }
.role-card ul li::before { content: '✓'; color: var(--green); font-weight: 700; flex-shrink: 0; }

/* CTA Strip */
.cta-strip {
  background: linear-gradient(135deg, #1a1714, #2d2520);
  padding: 64px 24px;
  text-align: center;
  color: #fff;
}
.cta-strip h2 { font-size: clamp(1.6rem, 4vw, 2.2rem); margin-bottom: 12px; }
.cta-strip p  { color: #a09892; margin-bottom: 32px; }

/* Footer */
footer {
  background: #111;
  color: #666;
  text-align: center;
  padding: 20px;
  font-size: .82rem;
}

@media (max-width: 640px) {
  .hero-nav { padding: 16px 20px; }
  .roles-grid { grid-template-columns: 1fr; }
}
</style>
</head>
<body>

<!-- ── Hero ── -->
<section class="hero">
  <nav class="hero-nav">
    <div class="hero-logo">Event<span>MS</span></div>
    <div class="flex gap-8">
      <a href="login.php"    class="btn-hero-ghost" style="padding:9px 22px;font-size:.9rem;">Login</a>
      <a href="register.php" class="btn-hero-primary" style="padding:9px 22px;font-size:.9rem;">Get Started</a>
    </div>
  </nav>

  <div class="hero-body">
    <div class="hero-eyebrow">Professional Event Management</div>
    <h1 class="hero-title">
      Create, manage &amp; attend<br><em>professional events</em> with ease
    </h1>
    <p class="hero-sub">
      EventMS brings organizers and participants together. Host seminars, workshops,
      and meetings — or discover and register for events happening near you.
    </p>
    <div class="hero-cta">
      <a href="register.php" class="btn-hero-primary">Create Free Account</a>
      <a href="login.php"    class="btn-hero-ghost">Sign In →</a>
    </div>
  </div>
</section>

<!-- ── Features ── -->
<section class="features">
  <p class="section-label">Platform Features</p>
  <h2 class="section-title">Everything you need to run great events</h2>

  <div class="features-grid">
    <div class="feature-card">
      <div class="feature-icon">
        <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
      </div>
      <h3>Event Creation</h3>
      <p>Create seminars, workshops, meetings, conferences and more with full detail control — dates, times, location, capacity.</p>
    </div>
    <div class="feature-card">
      <div class="feature-icon">
        <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
      </div>
      <h3>Role-Based Access</h3>
      <p>Distinct Organizer and Participant roles with tailored dashboards, permissions, and workflows for each user type.</p>
    </div>
    <div class="feature-card">
      <div class="feature-icon">
        <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
      </div>
      <h3>Seamless Registration</h3>
      <p>One-click event registration with real-time seat tracking. Participants can manage and cancel registrations anytime.</p>
    </div>
    <div class="feature-card">
      <div class="feature-icon">
        <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z"/></svg>
      </div>
      <h3>Smart Filtering</h3>
      <p>Filter events by type, status, or keyword search. Quickly find exactly the event you're looking for.</p>
    </div>
    <div class="feature-card">
      <div class="feature-icon">
        <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
      </div>
      <h3>Organizer Dashboard</h3>
      <p>Visual stats, registrant lists per event, and full CRUD management — all in one clean interface.</p>
    </div>
    <div class="feature-card">
      <div class="feature-icon">
        <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
      </div>
      <h3>Secure Authentication</h3>
      <p>bcrypt password hashing, prepared statements throughout, session-based auth, and CSRF-safe form handling.</p>
    </div>
  </div>
</section>

<!-- ── Roles ── -->
<section class="roles">
  <p class="section-label">Two User Roles</p>
  <h2 class="section-title" style="text-align:center;">Built for both sides of every event</h2>

  <div class="roles-grid">
    <div class="role-card" style="border-top:3px solid #5b21b6;">
      <span class="badge badge-organizer" style="margin-bottom:14px;">Organizer</span>
      <h3>Event Organizers</h3>
      <ul>
        <li>Create and publish professional events</li>
        <li>Edit event details, dates, and capacity</li>
        <li>Delete events (cascades registrations)</li>
        <li>View full registrant list per event</li>
        <li>Dashboard with stats &amp; recent events</li>
        <li>Manage event statuses (upcoming → completed)</li>
      </ul>
    </div>
    <div class="role-card" style="border-top:3px solid var(--yellow);">
      <span class="badge badge-participant" style="margin-bottom:14px;">Participant</span>
      <h3>Event Participants</h3>
      <ul>
        <li>Browse and search all available events</li>
        <li>Register for events with one click</li>
        <li>Real-time capacity &amp; seat availability</li>
        <li>Cancel registrations when needed</li>
        <li>Personal registration history dashboard</li>
        <li>Filter by event type and status</li>
      </ul>
    </div>
  </div>
</section>

<!-- ── CTA Strip ── -->
<section class="cta-strip">
  <h2>Ready to manage your next event?</h2>
  <p>Join EventMS today — free for organizers and participants.</p>
  <div class="flex gap-8" style="justify-content:center;flex-wrap:wrap;">
    <a href="register.php?role=organizer"   class="btn-hero-primary">Start as Organizer</a>
    <a href="register.php?role=participant" class="btn-hero-ghost">Join as Participant</a>
  </div>
</section>

<footer>
  &copy; <?= date('Y') ?> EventMS. Built with PHP &amp; MySQL.
</footer>

</body>
</html>
