<?php
session_start();
require_once __DIR__ . '/../config/app.php';

$error   = '';
$success = '';
$tab     = $_GET['tab'] ?? 'files';

if (isset($_GET['logout'])) { session_destroy(); header('Location: index.php'); exit; }

// LOGIN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_key'])) {
    if (trim($_POST['admin_key']) === ADMIN_KEY) {
        $_SESSION['admin_auth'] = true;
        header('Location: index.php?tab=files'); exit;
    }
    $error = 'Invalid key. Access denied.';
}

// UPLOAD
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'upload') {
    requireAdmin();
    $name    = trim($_POST['fname']    ?? '');
    $version = trim($_POST['fversion'] ?? '1.0.0');
    $icon    = trim($_POST['ficon']    ?? '📦');
    $desc    = trim($_POST['fdesc']    ?? '');
    if (!$name) {
        $error = 'File name is required.';
    } elseif (empty($_FILES['ffile']['tmp_name'])) {
        $error = 'Please select a file.';
    } else {
        $origName = $_FILES['ffile']['name'];
        $ext      = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        $size     = $_FILES['ffile']['size'];
        if (!in_array($ext, ALLOWED_EXTS)) {
            $error = 'Type not allowed. Allowed: ' . implode(', ', ALLOWED_EXTS);
        } elseif ($size > MAX_FILE_SIZE) {
            $error = 'File too large. Max: ' . formatBytes(MAX_FILE_SIZE);
        } else {
            $id       = generateId();
            $safeName = $id . '.' . $ext;
            if (move_uploaded_file($_FILES['ffile']['tmp_name'], UPLOADS_DIR . $safeName)) {
                insertFile([
                    'id'            => $id,
                    'name'          => $name,
                    'version'       => $version,
                    'icon'          => $icon,
                    'description'   => $desc,
                    'filename'      => $safeName,
                    'original_name' => $origName,
                    'size'          => $size,
                    'size_formatted'=> formatBytes($size),
                    'downloads'     => 0,
                    'uploaded_at'   => time(),
                ]);
                $success = '"' . htmlspecialchars($name) . '" uploaded successfully!';
                $tab = 'files';
            } else {
                $error = 'Upload failed. Check uploads/ folder has 755 permission.';
            }
        }
    }
}

// DELETE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    requireAdmin();
    deleteFile($_POST['del_id'] ?? '');
    $success = 'File deleted.';
}

$files      = loadFiles();
$isLoggedIn = isAdminLoggedIn();
$totalDl    = array_sum(array_column($files, 'downloads'));
usort($files, fn($a,$b) => ($b['uploaded_at'] ?? 0) - ($a['uploaded_at'] ?? 0));
$storageLabel = STORAGE_MODE === 'mysql' ? 'MySQL' : 'JSON';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<meta name="theme-color" content="#f7f7f5">
<title><?= htmlspecialchars(APP_NAME) ?> Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Figtree:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
/* ── TOKENS ── */
:root {
  --white:#fff;
  --off:#f7f7f5;
  --off2:#f0f0ec;
  --line:#e2e2db;
  --line2:#d4d4cb;
  --lime:#84cc16;
  --lime-d:#65a30d;
  --lime-bg:#f3fae5;
  --lime-br:#d9f59e;
  --ink:#111110;
  --ink2:#44443e;
  --ink3:#7c7c72;
  --ink4:#b0b0a4;
  --red:#ef4444;
  --red-bg:#fef2f2;
  --red-br:#fecaca;
  --grn:#16a34a;
  --grn-bg:#f0fdf4;
  --grn-br:#bbf7d0;
  --r:10px;
  --r-sm:7px;
  --r-lg:14px;
  --max:860px;
}

/* ── RESET ── */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
html { -webkit-text-size-adjust: 100%; }
body {
  font-family: 'Figtree', sans-serif;
  background: var(--off);
  color: var(--ink);
  min-height: 100vh;
  -webkit-font-smoothing: antialiased;
}
a { text-decoration: none; color: inherit; }

/* ── ALERTS ── */
.alert {
  border-radius: var(--r);
  padding: 11px 14px;
  font-size: .84rem;
  font-weight: 600;
  margin-bottom: 16px;
  line-height: 1.5;
}
.err  { background: var(--red-bg); border: 1px solid var(--red-br); color: var(--red); }
.ok   { background: var(--grn-bg); border: 1px solid var(--grn-br); color: var(--grn); }

/* ════════════════════════════════
   LOGIN PAGE
════════════════════════════════ */
.login-pg {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 24px 16px;
  padding-bottom: calc(24px + env(safe-area-inset-bottom));
}

.login-box {
  width: 100%;
  max-width: 360px;
  background: var(--white);
  border: 1px solid var(--line);
  border-radius: var(--r-lg);
  padding: 28px 24px;
  box-shadow: 0 4px 24px rgba(0,0,0,.06);
}

.l-top {
  text-align: center;
  margin-bottom: 24px;
}

.l-mark {
  width: 48px; height: 48px;
  background: var(--lime);
  border-radius: 12px;
  display: grid; place-items: center;
  font-size: 1.3rem;
  margin: 0 auto 12px;
  box-shadow: 0 4px 12px rgba(132,204,22,.25);
}

.l-title { font-size: 1.15rem; font-weight: 900; letter-spacing: -.03em; }
.l-sub   { font-size: .68rem; font-weight: 700; color: var(--ink4); letter-spacing: .06em; text-transform: uppercase; margin-top: 3px; }

.fg { margin-bottom: 13px; }
.fl { display: block; font-size: .67rem; font-weight: 700; color: var(--ink3); text-transform: uppercase; letter-spacing: .07em; margin-bottom: 6px; }

.fi {
  width: 100%;
  background: var(--off);
  border: 1px solid var(--line);
  border-radius: var(--r-sm);
  padding: 11px 13px;
  font-family: 'Figtree', sans-serif;
  font-size: .9rem; font-weight: 500;
  color: var(--ink);
  outline: none;
  -webkit-appearance: none;
  min-height: 44px;
  transition: border-color .15s, box-shadow .15s;
}
.fi:focus { border-color: var(--lime); box-shadow: 0 0 0 3px rgba(132,204,22,.15); }
.fi::placeholder { color: var(--ink4); }

.btn-login {
  width: 100%;
  background: var(--lime);
  color: var(--white);
  font-family: 'Figtree', sans-serif;
  font-size: .92rem; font-weight: 800;
  padding: 12px;
  border: none; border-radius: var(--r-sm);
  cursor: pointer;
  min-height: 46px;
  box-shadow: 0 1px 0 var(--lime-d), 0 3px 10px rgba(101,163,13,.2);
  transition: background .15s, transform .12s;
  -webkit-tap-highlight-color: transparent;
}
.btn-login:hover   { background: var(--lime-d); }
.btn-login:active  { transform: scale(.97); }

.l-back { text-align: center; margin-top: 14px; }
.l-back a { font-size: .78rem; font-weight: 600; color: var(--ink4); transition: color .15s; }
.l-back a:hover { color: var(--lime-d); }

/* ════════════════════════════════
   NAVBAR — the only navigation
   No sidebar. Zero. Gone.
════════════════════════════════ */
.navbar {
  position: sticky;
  top: 0;
  z-index: 50;
  background: rgba(247,247,245,.96);
  backdrop-filter: blur(14px);
  -webkit-backdrop-filter: blur(14px);
  border-bottom: 1px solid var(--line);
}

.navbar-inner {
  max-width: var(--max);
  margin: 0 auto;
  padding: 0 16px;
  height: 54px;
  display: flex;
  align-items: center;
  gap: 8px;
}

/* Logo */
.nav-logo {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-shrink: 0;
  margin-right: 4px;
}
.nav-mark {
  width: 28px; height: 28px;
  background: var(--lime);
  border-radius: 7px;
  display: grid; place-items: center;
  font-size: .8rem;
  flex-shrink: 0;
}
.nav-name {
  font-size: .9rem;
  font-weight: 800;
  letter-spacing: -.025em;
  white-space: nowrap;
}

/* Tab buttons */
.nav-tabs {
  display: flex;
  align-items: center;
  gap: 4px;
  flex: 1;
}

.nav-tab {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 6px 12px;
  border-radius: var(--r-sm);
  font-size: .82rem;
  font-weight: 700;
  color: var(--ink3);
  border: 1px solid transparent;
  background: transparent;
  cursor: pointer;
  font-family: 'Figtree', sans-serif;
  transition: background .12s, color .12s, border-color .12s;
  -webkit-tap-highlight-color: transparent;
  white-space: nowrap;
  min-height: 34px;
  text-decoration: none;
}
.nav-tab:hover  { background: var(--off2); color: var(--ink); }
.nav-tab.active { background: var(--lime-bg); color: var(--lime-d); border-color: var(--lime-br); }
.nav-tab svg    { width: 14px; height: 14px; flex-shrink: 0; }

.nav-tab .tab-count {
  font-size: .6rem;
  font-weight: 700;
  background: var(--line);
  color: var(--ink3);
  border-radius: 100px;
  padding: 1px 6px;
  margin-left: 2px;
}
.nav-tab.active .tab-count {
  background: var(--lime-br);
  color: var(--lime-d);
}

/* Right side */
.nav-right {
  display: flex;
  align-items: center;
  gap: 6px;
  margin-left: auto;
  flex-shrink: 0;
}

.nav-ext {
  display: flex;
  align-items: center;
  gap: 5px;
  font-size: .75rem;
  font-weight: 700;
  color: var(--ink3);
  padding: 6px 10px;
  border: 1px solid var(--line);
  border-radius: var(--r-sm);
  background: var(--white);
  -webkit-tap-highlight-color: transparent;
  white-space: nowrap;
  min-height: 34px;
  transition: border-color .15s, color .15s;
}
.nav-ext:hover { border-color: var(--line2); color: var(--ink); }
.nav-ext svg   { width: 13px; height: 13px; flex-shrink: 0; }

.nav-logout {
  display: flex;
  align-items: center;
  gap: 5px;
  font-size: .75rem;
  font-weight: 700;
  color: var(--ink3);
  padding: 6px 10px;
  border: 1px solid var(--line);
  border-radius: var(--r-sm);
  background: var(--white);
  cursor: pointer;
  font-family: 'Figtree', sans-serif;
  -webkit-tap-highlight-color: transparent;
  white-space: nowrap;
  min-height: 34px;
  transition: border-color .15s, color .15s, background .15s;
}
.nav-logout:hover { border-color: var(--red-br); color: var(--red); background: var(--red-bg); }
.nav-logout svg   { width: 13px; height: 13px; flex-shrink: 0; }

/* Hide text labels on very small screens, keep icons */
@media (max-width: 400px) {
  .nav-tab-label { display: none; }
  .nav-ext-label { display: none; }
  .nav-logout-label { display: none; }
  .nav-tab { padding: 6px 9px; }
  .nav-ext { padding: 6px 9px; }
  .nav-logout { padding: 6px 9px; }
}

/* ════════════════════════════════
   MAIN CONTENT
   Dead simple — just a centered block.
   No sidebar. No flex tricks. No margins.
════════════════════════════════ */
.main {
  max-width: var(--max);
  margin: 0 auto;
  padding: 22px 16px 60px;
  width: 100%;
}

/* ── Page head ── */
.ph   { margin-bottom: 18px; }
.ph h1 { font-size: 1.3rem; font-weight: 900; letter-spacing: -.035em; }
.ph p  { font-size: .82rem; font-weight: 500; color: var(--ink3); margin-top: 3px; }

/* ── Stat strip ── */
.ss {
  display: flex;
  background: var(--white);
  border: 1px solid var(--line);
  border-radius: var(--r);
  overflow: hidden;
  margin-bottom: 18px;
}
.ss-i { flex: 1; padding: 12px 10px; border-right: 1px solid var(--line); text-align: center; }
.ss-i:last-child { border-right: none; }
.ss-v { font-size: 1.25rem; font-weight: 900; letter-spacing: -.04em; line-height: 1; }
.ss-l { font-size: .58rem; font-weight: 700; color: var(--ink4); text-transform: uppercase; letter-spacing: .06em; margin-top: 4px; }

/* ── Panel ── */
.panel { background: var(--white); border: 1px solid var(--line); border-radius: var(--r-lg); overflow: hidden; margin-bottom: 16px; }
.p-hd  { padding: 11px 14px; border-bottom: 1px solid var(--line); display: flex; align-items: center; justify-content: space-between; gap: 8px; background: var(--off); }
.p-tt  { font-size: .9rem; font-weight: 800; letter-spacing: -.02em; display: flex; align-items: center; gap: 6px; }
.p-tt svg { width: 14px; height: 14px; stroke: var(--lime-d); flex-shrink: 0; }
.p-bd  { padding: 16px; }

/* ── Small button ── */
.btn-sm {
  display: inline-flex; align-items: center; gap: 5px;
  background: var(--lime); color: var(--white);
  font-family: 'Figtree', sans-serif; font-size: .76rem; font-weight: 800;
  padding: 7px 11px; border: none; border-radius: var(--r-sm);
  cursor: pointer; min-height: 32px;
  box-shadow: 0 1px 0 var(--lime-d);
  transition: background .15s;
  -webkit-tap-highlight-color: transparent;
  text-decoration: none;
}
.btn-sm:hover { background: var(--lime-d); }
.btn-sm svg   { width: 12px; height: 12px; }

/* ── Desktop table ── */
.tbl-sc { overflow-x: auto; }
table   { width: 100%; border-collapse: collapse; }
thead th {
  font-size: .62rem; font-weight: 700; color: var(--ink4);
  text-transform: uppercase; letter-spacing: .07em;
  text-align: left; padding: 9px 12px;
  border-bottom: 1px solid var(--line);
  white-space: nowrap; background: var(--off);
}
tbody td { padding: 11px 12px; border-bottom: 1px solid var(--line); font-size: .86rem; vertical-align: middle; }
tbody tr:last-child td { border-bottom: none; }
tbody tr:hover td      { background: var(--off); }

.td-ic { font-size: 1.2rem; }
.td-nm { font-weight: 700; }
.td-or { font-size: .68rem; font-weight: 500; color: var(--ink4); margin-top: 1px; }
.td-vr {
  font-size: .6rem; font-weight: 700;
  color: var(--lime-d); background: var(--lime-bg);
  border: 1px solid var(--lime-br); border-radius: 100px;
  padding: 2px 8px; display: inline-block;
}
.td-mn { font-size: .76rem; font-weight: 600; color: var(--ink3); }

.empty-td { text-align: center !important; padding: 36px !important; color: var(--ink4); font-size: .87rem; }
.empty-td a { color: var(--lime-d); font-weight: 700; }

/* ── Mobile file cards (shown <580px instead of table) ── */
.fc-list { display: none; flex-direction: column; gap: 9px; padding: 12px; }
@media (max-width: 580px) {
  .tbl-sc  { display: none; }
  .fc-list { display: flex; }
}

.fc      { background: var(--off); border: 1px solid var(--line); border-radius: var(--r); padding: 12px; display: flex; flex-direction: column; gap: 9px; }
.fc-top  { display: flex; gap: 10px; align-items: flex-start; }
.fc-ico  { font-size: 1.4rem; flex-shrink: 0; line-height: 1; }
.fc-info { flex: 1; min-width: 0; }
.fc-nm   { font-size: .9rem; font-weight: 800; letter-spacing: -.02em; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.fc-tags { display: flex; flex-wrap: wrap; gap: 4px; margin-top: 5px; }
.fc-tg   { font-size: .6rem; font-weight: 700; color: var(--ink3); background: var(--white); border: 1px solid var(--line); border-radius: 5px; padding: 2px 7px; }

/* ── Delete button ── */
.btn-del {
  display: inline-flex; align-items: center; gap: 5px;
  background: var(--red-bg); border: 1px solid var(--red-br);
  color: var(--red); font-family: 'Figtree', sans-serif;
  font-size: .78rem; font-weight: 700;
  padding: 7px 11px; border-radius: var(--r-sm);
  cursor: pointer; min-height: 34px;
  transition: background .15s;
  -webkit-tap-highlight-color: transparent;
  align-self: flex-start;
}
.btn-del:hover { background: #fee2e2; }
.btn-del svg   { width: 12px; height: 12px; flex-shrink: 0; }

/* ── Upload form ── */
.fgrid { display: grid; grid-template-columns: 1fr; gap: 14px; }
@media (min-width: 480px) { .fgrid { grid-template-columns: 1fr 1fr; } }
.s2 { grid-column: 1 / -1; }

.fi-a {
  width: 100%;
  background: var(--off); border: 1px solid var(--line);
  border-radius: var(--r-sm); padding: 11px 13px;
  font-family: 'Figtree', sans-serif; font-size: .88rem; font-weight: 500;
  color: var(--ink); outline: none; -webkit-appearance: none; min-height: 44px;
  transition: border-color .15s, box-shadow .15s;
}
.fi-a:focus { border-color: var(--lime); box-shadow: 0 0 0 3px rgba(132,204,22,.14); }
.fi-a::placeholder { color: var(--ink4); }
.fta { min-height: 80px; resize: vertical; line-height: 1.55; }

.drop {
  background: var(--off); border: 2px dashed var(--line2);
  border-radius: var(--r); padding: 24px 16px;
  text-align: center; cursor: pointer; position: relative;
  transition: border-color .15s, background .15s;
  -webkit-tap-highlight-color: transparent;
}
.drop:hover, .drop.go { border-color: var(--lime); background: var(--lime-bg); }
.drop input { position: absolute; inset: 0; width: 100%; height: 100%; opacity: 0; cursor: pointer; }
.drop-ico { font-size: 1.6rem; margin-bottom: 6px; }
.drop-lb  { font-size: .84rem; font-weight: 700; color: var(--ink2); }
.drop-sb  { font-size: .67rem; font-weight: 500; color: var(--ink4); margin-top: 3px; }

.icon-rack { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 7px; }
.io {
  width: 40px; height: 40px;
  display: grid; place-items: center; font-size: 1.2rem;
  border-radius: 9px; border: 2px solid var(--line); background: var(--off);
  cursor: pointer; transition: border-color .12s, background .12s;
  -webkit-tap-highlight-color: transparent;
}
.io:hover { border-color: var(--line2); background: var(--off2); }
.io.sel   { border-color: var(--lime); background: var(--lime-bg); }

.btn-up {
  display: inline-flex; align-items: center; gap: 7px;
  background: var(--lime); color: var(--white);
  font-family: 'Figtree', sans-serif; font-size: .9rem; font-weight: 800;
  padding: 12px 22px; border: none; border-radius: var(--r-sm);
  cursor: pointer; min-height: 46px;
  box-shadow: 0 1px 0 var(--lime-d), 0 3px 10px rgba(101,163,13,.2);
  transition: background .15s, transform .12s;
  -webkit-tap-highlight-color: transparent;
}
.btn-up:hover  { background: var(--lime-d); }
.btn-up:active { transform: scale(.97); box-shadow: none; }
.btn-up svg    { width: 15px; height: 15px; }

/* ── Footer ── */
.afoot { margin-top: 14px; font-size: .67rem; font-weight: 600; color: var(--ink4); }
</style>
</head>
<body>

<?php if (!$isLoggedIn): ?>
<!-- ══ LOGIN ══ -->
<div class="login-pg">
  <div class="login-box">
    <div class="l-top">
      <div class="l-mark">⬡</div>
      <div class="l-title"><?= htmlspecialchars(APP_NAME) ?> Admin</div>
      <div class="l-sub">Offline Key Auth</div>
    </div>
    <?php if ($error): ?><div class="alert err">🚫 <?= htmlspecialchars($error) ?></div><?php endif; ?>
    <form method="POST" action="index.php">
      <div class="fg">
        <label class="fl">Admin Key</label>
        <input type="password" name="admin_key" class="fi" placeholder="Enter your secret key…" autofocus required>
      </div>
      <button type="submit" class="btn-login">🔓 Enter Admin Panel</button>
    </form>
    <div class="l-back"><a href="../index.php">← Back to <?= htmlspecialchars(APP_NAME) ?></a></div>
  </div>
</div>

<?php else: ?>
<!-- ══ ADMIN ══ -->

<!-- NAVBAR with tab buttons — no sidebar needed -->
<nav class="navbar">
  <div class="navbar-inner">

    <div class="nav-logo">
      <div class="nav-mark">⬡</div>
      <span class="nav-name"><?= htmlspecialchars(APP_NAME) ?></span>
    </div>

    <div class="nav-tabs">
      <a href="index.php?tab=files" class="nav-tab <?= $tab==='files' ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M13 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V9z"/><polyline points="13 2 13 9 20 9"/>
        </svg>
        <span class="nav-tab-label">Files</span>
        <span class="tab-count"><?= count($files) ?></span>
      </a>
      <a href="index.php?tab=upload" class="nav-tab <?= $tab==='upload' ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <polyline points="16 16 12 12 8 16"/><line x1="12" y1="12" x2="12" y2="21"/>
          <path d="M20.39 18.39A5 5 0 0018 9h-1.26A8 8 0 103 16.3"/>
        </svg>
        <span class="nav-tab-label">Upload</span>
      </a>
    </div>

    <div class="nav-right">
      <a href="../index.php" class="nav-ext" target="_blank" rel="noopener">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/>
        </svg>
        <span class="nav-ext-label">View Site</span>
      </a>
      <a href="index.php?logout=1" class="nav-logout">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>
        </svg>
        <span class="nav-logout-label">Logout</span>
      </a>
    </div>

  </div>
</nav>

<!-- MAIN — just a centered div, nothing fancy -->
<div class="main">

  <?php if ($error): ?><div class="alert err">🚫 <?= htmlspecialchars($error) ?></div><?php endif; ?>
  <?php if ($success): ?><div class="alert ok">✅ <?= htmlspecialchars($success) ?></div><?php endif; ?>

  <?php if ($tab === 'files'): ?>
  <!-- ─── FILES TAB ─── -->
  <div class="ph">
    <h1>File Library</h1>
    <p>Manage all uploaded releases</p>
  </div>

  <div class="ss">
    <div class="ss-i">
      <div class="ss-v"><?= count($files) ?></div>
      <div class="ss-l">Files</div>
    </div>
    <div class="ss-i">
      <div class="ss-v" style="color:var(--lime-d)"><?= $totalDl > 999 ? round($totalDl/1000,1).'k' : $totalDl ?></div>
      <div class="ss-l">Downloads</div>
    </div>
    <div class="ss-i">
      <div class="ss-v"><?= count($files) > 0 ? round($totalDl/count($files),1) : 0 ?></div>
      <div class="ss-l">Avg/File</div>
    </div>
  </div>

  <div class="panel">
    <div class="p-hd">
      <div class="p-tt">
        <svg viewBox="0 0 24 24" fill="none" stroke-width="1.8"><path d="M13 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V9z"/></svg>
        Releases
      </div>
      <a href="index.php?tab=upload" class="btn-sm">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
          <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
        </svg>
        New Upload
      </a>
    </div>

    <!-- Desktop table -->
    <div class="tbl-sc">
      <table>
        <thead><tr>
          <th style="width:36px">Icon</th>
          <th>Name</th>
          <th>Version</th>
          <th>Size</th>
          <th>Downloads</th>
          <th>Date</th>
          <th style="width:72px">Delete</th>
        </tr></thead>
        <tbody>
        <?php if (empty($files)): ?>
          <tr><td colspan="7" class="empty-td">No files yet. <a href="index.php?tab=upload">Upload first →</a></td></tr>
        <?php else: foreach ($files as $f): ?>
          <tr>
            <td class="td-ic"><?= htmlspecialchars($f['icon'] ?? '📦') ?></td>
            <td>
              <div class="td-nm"><?= htmlspecialchars($f['name']) ?></div>
              <div class="td-or"><?= htmlspecialchars($f['original_name'] ?? '') ?></div>
            </td>
            <td><span class="td-vr">v<?= htmlspecialchars($f['version'] ?? '1.0.0') ?></span></td>
            <td class="td-mn"><?= htmlspecialchars($f['size_formatted'] ?? '—') ?></td>
            <td class="td-mn"><?= number_format($f['downloads'] ?? 0) ?></td>
            <td class="td-mn"><?= date('d M Y', $f['uploaded_at'] ?? time()) ?></td>
            <td>
              <form method="POST" onsubmit="return confirm('Delete permanently?')">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="del_id" value="<?= htmlspecialchars($f['id']) ?>">
                <button type="submit" class="btn-del">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4h6v2"/>
                  </svg>
                  Del
                </button>
              </form>
            </td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>

    <!-- Mobile cards -->
    <div class="fc-list">
    <?php if (empty($files)): ?>
      <div style="text-align:center;padding:32px;color:var(--ink4);font-size:.87rem">
        No files. <a href="index.php?tab=upload" style="color:var(--lime-d);font-weight:700">Upload first →</a>
      </div>
    <?php else: foreach ($files as $f): ?>
      <div class="fc">
        <div class="fc-top">
          <div class="fc-ico"><?= htmlspecialchars($f['icon'] ?? '📦') ?></div>
          <div class="fc-info">
            <div class="fc-nm"><?= htmlspecialchars($f['name']) ?></div>
            <div class="fc-tags">
              <span class="fc-tg">v<?= htmlspecialchars($f['version'] ?? '1.0.0') ?></span>
              <span class="fc-tg"><?= htmlspecialchars($f['size_formatted'] ?? '—') ?></span>
              <span class="fc-tg">⬇ <?= number_format($f['downloads'] ?? 0) ?></span>
              <span class="fc-tg"><?= date('d M Y', $f['uploaded_at'] ?? time()) ?></span>
            </div>
          </div>
        </div>
        <form method="POST" onsubmit="return confirm('Delete permanently?')">
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="del_id" value="<?= htmlspecialchars($f['id']) ?>">
          <button type="submit" class="btn-del">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/>
            </svg>
            Delete File
          </button>
        </form>
      </div>
    <?php endforeach; endif; ?>
    </div>

  </div><!-- /panel -->

  <?php elseif ($tab === 'upload'): ?>
  <!-- ─── UPLOAD TAB ─── -->
  <div class="ph">
    <h1>Upload File</h1>
    <p>Add a new release to the public library</p>
  </div>

  <div class="panel">
    <div class="p-hd">
      <div class="p-tt">
        <svg viewBox="0 0 24 24" fill="none" stroke-width="1.8">
          <polyline points="16 16 12 12 8 16"/><line x1="12" y1="12" x2="12" y2="21"/>
          <path d="M20.39 18.39A5 5 0 0018 9h-1.26A8 8 0 103 16.3"/>
        </svg>
        File Details
      </div>
    </div>
    <div class="p-bd">
      <form method="POST" enctype="multipart/form-data" action="index.php?tab=upload">
        <input type="hidden" name="action" value="upload">
        <input type="hidden" name="ficon" id="sel-icon" value="📦">

        <div class="fgrid">
          <div>
            <label class="fl" style="display:block;font-size:.67rem;font-weight:700;color:var(--ink3);text-transform:uppercase;letter-spacing:.07em;margin-bottom:6px">Name *</label>
            <input type="text" name="fname" class="fi-a" placeholder="e.g. MyAwesomeTool Pro" required>
          </div>
          <div>
            <label class="fl" style="display:block;font-size:.67rem;font-weight:700;color:var(--ink3);text-transform:uppercase;letter-spacing:.07em;margin-bottom:6px">Version *</label>
            <input type="text" name="fversion" class="fi-a" placeholder="e.g. 2.4.1" value="1.0.0" required>
          </div>
          <div class="s2">
            <label class="fl" style="display:block;font-size:.67rem;font-weight:700;color:var(--ink3);text-transform:uppercase;letter-spacing:.07em;margin-bottom:6px">Description</label>
            <textarea name="fdesc" class="fi-a fta" placeholder="What does this do? Any install notes…"></textarea>
          </div>
          <div class="s2">
            <label class="fl" style="display:block;font-size:.67rem;font-weight:700;color:var(--ink3);text-transform:uppercase;letter-spacing:.07em;margin-bottom:6px">Icon</label>
            <div class="icon-rack" id="icon-rack">
              <?php foreach(['📦','⚙️','🔧','💻','🛡️','🎮','🌐','📱','🔒','🚀','⬡','🗜️','📋','🔑','💡','🎯','⚡','🧩','🖥️','🔌'] as $ic): ?>
              <div class="io <?= $ic==='📦'?'sel':'' ?>" data-icon="<?= $ic ?>"><?= $ic ?></div>
              <?php endforeach; ?>
            </div>
          </div>
          <div class="s2">
            <label class="fl" style="display:block;font-size:.67rem;font-weight:700;color:var(--ink3);text-transform:uppercase;letter-spacing:.07em;margin-bottom:6px">File *</label>
            <div class="drop" id="drop">
              <input type="file" name="ffile" id="ffile" required
                accept=".zip,.rar,.exe,.msi,.apk,.pdf,.dmg,.tar,.gz,.7z,.jar,.bin">
              <div class="drop-ico">📂</div>
              <div class="drop-lb" id="drop-lb">Tap to select file</div>
              <div class="drop-sb">ZIP RAR EXE MSI APK PDF DMG 7Z JAR BIN · Max 500 MB</div>
            </div>
          </div>
        </div>

        <div style="margin-top:18px">
          <button type="submit" class="btn-up">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <polyline points="16 16 12 12 8 16"/><line x1="12" y1="12" x2="12" y2="21"/>
            </svg>
            Upload File
          </button>
        </div>
      </form>
    </div>
  </div>

  <?php endif; ?>

  <div class="afoot">
    &copy; <?= date('Y') ?> <?= htmlspecialchars(APP_COPYRIGHT_NAME) ?> &nbsp;·&nbsp;
    Dev: <?= htmlspecialchars(APP_DEV_NAME) ?> &nbsp;·&nbsp;
    Storage: <strong><?= $storageLabel ?></strong>
  </div>

</div><!-- /main -->

<script>
document.querySelectorAll('.io').forEach(el => {
  el.addEventListener('click', () => {
    document.querySelectorAll('.io').forEach(x => x.classList.remove('sel'));
    el.classList.add('sel');
    document.getElementById('sel-icon').value = el.dataset.icon;
  });
});

document.getElementById('ffile')?.addEventListener('change', function () {
  if (!this.files.length) return;
  const f  = this.files[0];
  const mb = (f.size / 1048576).toFixed(1);
  document.getElementById('drop-lb').textContent = `✅ ${f.name} (${mb} MB)`;
  document.getElementById('drop').classList.add('go');
});
</script>

<?php endif; ?>
</body>
</html>
