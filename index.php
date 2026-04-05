<?php
require_once __DIR__ . '/config/app.php';
$files          = loadFiles();
$totalFiles     = count($files);
$totalDownloads = array_sum(array_column($files, 'downloads'));
// already sorted by loadFiles for mysql; sort for json
usort($files, fn($a,$b) => ($b['uploaded_at'] ?? 0) - ($a['uploaded_at'] ?? 0));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<meta name="theme-color" content="#f7f7f5">
<title><?= htmlspecialchars(APP_NAME) ?> — Software Downloads</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Figtree:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
:root {
  --white:#ffffff; --off:#f7f7f5; --off2:#f0f0ec; --off3:#e8e8e2;
  --line:#e2e2db; --line2:#d4d4cb;
  --lime:#84cc16; --lime-d:#65a30d; --lime-bg:#f3fae5; --lime-br:#d9f59e;
  --ink:#111110; --ink2:#44443e; --ink3:#7c7c72; --ink4:#b0b0a4;
  --red:#ef4444; --r:12px; --r-sm:8px; --r-lg:16px;
  --max:1080px; --g:20px;
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html{-webkit-text-size-adjust:100%;scroll-behavior:smooth}
body{font-family:'Figtree',sans-serif;background:var(--off);color:var(--ink);min-height:100vh;-webkit-font-smoothing:antialiased}
a{text-decoration:none}

/* NAV */
.nav{position:sticky;top:0;z-index:100;background:rgba(247,247,245,.94);backdrop-filter:blur(14px);-webkit-backdrop-filter:blur(14px);border-bottom:1px solid var(--line)}
.nav-i{max-width:var(--max);margin:0 auto;padding:0 var(--g);height:56px;display:flex;align-items:center;justify-content:space-between;gap:12px}
.logo{display:flex;align-items:center;gap:9px}
.logo-m{width:32px;height:32px;background:var(--lime);border-radius:8px;display:grid;place-items:center;font-size:.85rem;flex-shrink:0}
.logo-t{font-size:1rem;font-weight:800;letter-spacing:-.03em;color:var(--ink)}
.logo-t span{color:var(--lime-d)}
.nav-r{display:flex;align-items:center;gap:8px}
.nav-live{font-size:.65rem;font-weight:700;color:var(--lime-d);background:var(--lime-bg);border:1px solid var(--lime-br);border-radius:100px;padding:3px 10px;display:flex;align-items:center;gap:5px}
.ldot{width:6px;height:6px;background:var(--lime);border-radius:50%;animation:lpulse 2s ease-in-out infinite}
@keyframes lpulse{0%{box-shadow:0 0 0 0 rgba(132,204,22,.5)}70%{box-shadow:0 0 0 7px rgba(132,204,22,0)}100%{box-shadow:0 0 0 0 rgba(132,204,22,0)}}
.nav-admin{display:flex;align-items:center;gap:6px;font-size:.78rem;font-weight:700;color:var(--ink3);padding:7px 12px;border:1px solid var(--line2);border-radius:var(--r-sm);background:var(--white);transition:border-color .15s,color .15s;-webkit-tap-highlight-color:transparent}
.nav-admin:hover{border-color:var(--lime);color:var(--lime-d)}
.nav-admin svg{width:13px;height:13px}

/* HERO */
.hero{max-width:var(--max);margin:0 auto;padding:52px var(--g) 40px}
.hero-eye{display:inline-flex;align-items:center;gap:7px;font-size:.7rem;font-weight:700;color:var(--lime-d);letter-spacing:.06em;text-transform:uppercase;margin-bottom:18px}
.hero h1{font-size:clamp(1.9rem,5.5vw,3.5rem);font-weight:900;letter-spacing:-.04em;line-height:1.06;color:var(--ink);margin-bottom:14px;max-width:600px}
.hero h1 mark{background:none;color:var(--lime-d)}
.hero-sub{font-size:.97rem;font-weight:500;color:var(--ink3);line-height:1.7;max-width:460px;margin-bottom:32px}

/* STATS */
.stats{display:inline-flex;background:var(--white);border:1px solid var(--line);border-radius:var(--r-lg);overflow:hidden}
.st{padding:13px 22px;border-right:1px solid var(--line);text-align:center}
.st:last-child{border-right:none}
.st-v{font-size:1.35rem;font-weight:900;letter-spacing:-.04em;color:var(--ink);line-height:1}
.st-v.lime{color:var(--lime-d)}
.st-l{font-size:.6rem;font-weight:700;color:var(--ink4);text-transform:uppercase;letter-spacing:.06em;margin-top:4px}

/* SECTION HEAD */
.sec-head{max-width:var(--max);margin:0 auto;padding:0 var(--g) 16px;display:flex;align-items:center;gap:12px}
.sec-t{font-size:.7rem;font-weight:700;color:var(--ink3);letter-spacing:.07em;text-transform:uppercase;white-space:nowrap}
.sec-line{flex:1;height:1px;background:var(--line)}
.sec-c{font-size:.65rem;font-weight:600;color:var(--ink4);white-space:nowrap}

/* GRID */
.grid-wrap{max-width:var(--max);margin:0 auto;padding:0 var(--g) 64px}
.fgrid{display:grid;grid-template-columns:1fr;gap:11px}
@media(min-width:560px){.fgrid{grid-template-columns:repeat(2,1fr)}}
@media(min-width:900px){.fgrid{grid-template-columns:repeat(3,1fr)}}

/* CARD */
.card{background:var(--white);border:1px solid var(--line);border-radius:var(--r-lg);padding:18px;display:flex;flex-direction:column;gap:13px;transition:border-color .18s,box-shadow .18s,transform .18s;animation:fin .35s ease both;-webkit-tap-highlight-color:transparent}
.card:nth-child(1){animation-delay:.04s}.card:nth-child(2){animation-delay:.08s}.card:nth-child(3){animation-delay:.12s}.card:nth-child(4){animation-delay:.16s}.card:nth-child(5){animation-delay:.2s}.card:nth-child(6){animation-delay:.24s}
@keyframes fin{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:none}}
.card:hover{border-color:var(--lime-br);box-shadow:0 2px 18px rgba(0,0,0,.07),0 0 0 1px var(--lime-br);transform:translateY(-2px)}

.card-top{display:flex;gap:12px;align-items:flex-start}
.c-icon{width:46px;height:46px;background:var(--off);border:1px solid var(--line);border-radius:12px;display:grid;place-items:center;font-size:1.4rem;flex-shrink:0}
.c-info{flex:1;min-width:0}
.c-name{font-size:.96rem;font-weight:800;letter-spacing:-.02em;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-bottom:5px}
.c-ver{font-size:.6rem;font-weight:700;color:var(--lime-d);background:var(--lime-bg);border:1px solid var(--lime-br);border-radius:100px;padding:2px 8px;display:inline-block}
.c-desc{font-size:.82rem;font-weight:500;color:var(--ink3);line-height:1.6;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}

.chips{display:flex;flex-wrap:wrap;gap:5px}
.chip{font-size:.62rem;font-weight:600;color:var(--ink3);background:var(--off);border:1px solid var(--line);border-radius:6px;padding:3px 8px}

.card-foot{display:flex;align-items:center;gap:10px;margin-top:auto}
.btn-dl{flex:1;display:flex;align-items:center;justify-content:center;gap:7px;background:var(--lime);color:var(--white);font-size:.88rem;font-weight:800;letter-spacing:-.01em;padding:11px 16px;border-radius:var(--r-sm);border:none;cursor:pointer;transition:background .15s,transform .12s,box-shadow .15s;box-shadow:0 1px 0 var(--lime-d),0 2px 8px rgba(101,163,13,.22);min-height:44px;-webkit-tap-highlight-color:transparent}
.btn-dl:hover{background:var(--lime-d);box-shadow:0 1px 0 #4d7c0f,0 4px 14px rgba(101,163,13,.32);transform:translateY(-1px)}
.btn-dl:active{transform:scale(.97);box-shadow:none}
.btn-dl svg{width:14px;height:14px;flex-shrink:0}
.dl-ct{display:flex;align-items:center;gap:4px;font-size:.67rem;font-weight:700;color:var(--ink4);white-space:nowrap}

/* EMPTY */
.empty{max-width:var(--max);margin:0 auto;padding:56px var(--g);text-align:center}
.empty-ico{font-size:2.2rem;margin-bottom:10px;opacity:.2}
.empty p{color:var(--ink3);font-size:.9rem;font-weight:500;line-height:1.7}

/* FOOTER */
footer{border-top:1px solid var(--line);background:var(--white);padding:18px var(--g);padding-bottom:calc(18px + env(safe-area-inset-bottom))}
.foot-i{max-width:var(--max);margin:0 auto;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:6px}
.foot-copy{font-size:.7rem;font-weight:600;color:var(--ink4)}
.foot-copy strong{color:var(--ink3)}
.foot-link{display:inline-flex;align-items:center;gap:5px;font-size:.7rem;font-weight:700;color:var(--ink4);transition:color .15s}
.foot-link:hover{color:var(--lime-d)}
.foot-link svg{width:12px;height:12px}

/* TOAST */
.toast{position:fixed;bottom:calc(16px + env(safe-area-inset-bottom));left:50%;transform:translateX(-50%) translateY(80px);background:var(--ink);color:var(--white);border-radius:100px;padding:10px 20px;font-size:.82rem;font-weight:700;white-space:nowrap;box-shadow:0 6px 24px rgba(0,0,0,.16);z-index:999;transition:transform .3s cubic-bezier(.34,1.56,.64,1)}
.toast.show{transform:translateX(-50%) translateY(0)}

@media(max-width:480px){
  .hero{padding:36px var(--g) 28px}
  .hero h1{font-size:1.85rem}
  .stats{width:100%}
  .st{flex:1;padding:12px 8px}
  .st-v{font-size:1.2rem}
}
</style>
</head>
<body>

<nav class="nav">
  <div class="nav-i">
    <a href="index.php" class="logo">
      <div class="logo-m">⬡</div>
      <span class="logo-t"><?= htmlspecialchars(APP_NAME) ?><span>.</span></span>
    </a>
    <div class="nav-r">
      <div class="nav-live"><div class="ldot"></div> LIVE</div>
      <a href="admin/" class="nav-admin">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
        Admin
      </a>
    </div>
  </div>
</nav>

<div class="hero">
  <div class="hero-eye"><div class="ldot"></div> Distribution Platform</div>
  <h1>Download <mark>Premium</mark><br>Software. Instantly.</h1>
  <p class="hero-sub">No account. No paywall. Just pick a file and download — that's it.</p>
  <div class="stats">
    <div class="st"><div class="st-v"><?= $totalFiles ?></div><div class="st-l">Releases</div></div>
    <div class="st"><div class="st-v"><?= $totalDownloads > 999 ? round($totalDownloads/1000,1).'k' : $totalDownloads ?></div><div class="st-l">Downloads</div></div>
    <div class="st"><div class="st-v lime">FREE</div><div class="st-l">Always</div></div>
  </div>
</div>

<div class="sec-head">
  <div class="sec-t">All Releases</div>
  <div class="sec-line"></div>
  <div class="sec-c"><?= $totalFiles ?> file<?= $totalFiles !== 1 ? 's' : '' ?></div>
</div>

<div class="grid-wrap">
<?php if (empty($files)): ?>
  <div class="empty"><div class="empty-ico">📦</div><p>No files uploaded yet.<br>Check back soon.</p></div>
<?php else: ?>
  <div class="fgrid">
  <?php foreach ($files as $f):
    $ext = strtoupper(pathinfo($f['filename'] ?? '', PATHINFO_EXTENSION));
  ?>
    <div class="card">
      <div class="card-top">
        <div class="c-icon"><?= htmlspecialchars($f['icon'] ?? '📦') ?></div>
        <div class="c-info">
          <div class="c-name"><?= htmlspecialchars($f['name'] ?? 'Unnamed') ?></div>
          <span class="c-ver">v<?= htmlspecialchars($f['version'] ?? '1.0.0') ?></span>
        </div>
      </div>
      <?php if (!empty($f['description'])): ?>
      <div class="c-desc"><?= htmlspecialchars($f['description']) ?></div>
      <?php endif; ?>
      <div class="chips">
        <?php if (!empty($f['size_formatted'])): ?><span class="chip">📁 <?= htmlspecialchars($f['size_formatted']) ?></span><?php endif; ?>
        <?php if ($ext): ?><span class="chip"><?= $ext ?></span><?php endif; ?>
        <span class="chip"><?= date('d M Y', $f['uploaded_at'] ?? time()) ?></span>
      </div>
      <div class="card-foot">
        <a href="download.php?id=<?= urlencode($f['id']) ?>" class="btn-dl">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 3v13M6 11l6 6 6-6M3 21h18"/></svg>
          Download
        </a>
        <div class="dl-ct">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3v13M6 11l6 6 6-6"/></svg>
          <?= number_format($f['downloads'] ?? 0) ?>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
  </div>
<?php endif; ?>
</div>

<footer>
  <div class="foot-i">
    <div class="foot-copy">&copy; <?= date('Y') ?> <strong><?= htmlspecialchars(APP_COPYRIGHT_NAME) ?></strong> · Built by <strong><?= htmlspecialchars(APP_DEV_NAME) ?></strong></div>
    <a href="admin/" class="foot-link">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
      Admin Panel
    </a>
  </div>
</footer>

<?php if (isset($_GET['downloaded'])): ?>
<div class="toast" id="toast">✅ Download started!</div>
<script>const t=document.getElementById('toast');setTimeout(()=>t.classList.add('show'),80);setTimeout(()=>t.classList.remove('show'),3200);</script>
<?php endif; ?>
</body>
</html>
