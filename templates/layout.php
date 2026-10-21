<?php use NF\Security; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="<?= Security::e($csrf ?? '') ?>">
<title>NullForums — <?= Security::e(ucfirst($active)) ?></title>

<!-- GOOGLE FONTS -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&family=Space+Grotesk:wght@600;700&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&display=swap" rel="stylesheet">

<!-- TABLER ICONS (lokal) -->
<link rel="stylesheet" href="/vendor/tabler/tabler-icons.min.css">

<!-- APP STYLE -->
<link rel="stylesheet" href="/css/style.css">

<!-- CLOUDFLARE TURNSTILE -->
<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
</head>
<body>

<?php require __DIR__ . '/header.php'; ?>
<div class="drawer-backdrop" id="drawerBackdrop"></div>
<?php require __DIR__ . '/drawer.php'; ?>

<div class="wrap">

  <!-- CATEGORY TABS & SUBNAV (hidden on home, 404, new thread, and thread pages) -->
  <?php if (empty($hide_nav) && !in_array($tpl, ['home', '404', 'new_thread', 'thread'], true)): ?>
    <div class="nav-container" style="grid-column:1/-1">
      <div class="tabs-wrap">
        <button class="tabs-arrow left" id="arrowLeft" aria-label="Scroll tabs left">‹</button>
        <div class="tabs" id="tabs">
          <?php foreach ($tabs as $t): ?>
            <a class="tab<?= $t['slug'] === $active ? ' active' : '' ?>"
               href="/<?= Security::e($t['slug']) ?>"
               <?= $t['slug'] === $active ? 'aria-current="page"' : '' ?>>
              <i class="<?= Security::e($t['icon']) ?>"></i>
              <?= Security::e($t['name']) ?>
            </a>
          <?php endforeach; ?>
        </div>
        <button class="tabs-arrow right" id="arrowRight" aria-label="Scroll tabs right">›</button>
      </div>

      <?php $subnav = ['all' => 'All', 'today' => 'Today', 'week' => 'This Week', 'month' => 'This Month']; ?>
      <nav class="subnav">
        <?php foreach ($subnav as $k => $label): ?>
          <a href="?range=<?= $k ?>" class="<?= $range === $k ? 'active' : '' ?>"><?= $label ?></a>
        <?php endforeach; ?>
      </nav>
    </div>
  <?php endif; ?>

  <!-- FLASH MESSAGE (shown after login, register, or upload) -->
  <?php
  $flash = $_SESSION['flash'] ?? null;
  unset($_SESSION['flash']);
  ?>
  <?php if ($flash): ?>
    <div class="alert <?= Security::e($flash['type']) ?>" style="grid-column: 1/-1; margin-bottom: 0;">
      <?= Security::e($flash['msg']) ?>
    </div>
  <?php endif; ?>

  <!-- MAIN GRID -->
  <?php
  // Sidebar tampil di halaman biasa, tetapi tidak di halaman buat thread (satu kolom)
    $showSide = empty($hide_nav) && !in_array($tpl, ['new_thread', 'thread'], true);
  ?>
  <div class="<?= $showSide ? 'grid-2' : 'grid-1' ?>">
    <main>
      <?php require __DIR__ . '/' . $tpl . '.php'; ?>
    </main>

    <?php if ($showSide): ?>
    <aside class="sidebar-desktop">
      <?php require __DIR__ . '/sidebar.php'; ?>
    </aside>
    <?php endif; ?>
  </div>
</div>

<!-- DIALOG MODAL (delete confirmation and error messages) -->
<div class="modal-overlay" id="appDialog" aria-hidden="true">
  <div class="modal" role="dialog" aria-modal="true" aria-labelledby="dialogTitle">
    <h3 id="dialogTitle">Confirm</h3>
    <p id="dialogMsg" style="color:var(--text-dim); margin:8px 0 18px; line-height:1.5;"></p>
    <div class="dialog-actions">
      <button type="button" class="btn" id="dialogCancel">Cancel</button>
      <button type="button" class="btn btn-primary" id="dialogOk">OK</button>
    </div>
  </div>
</div>

<?php require __DIR__ . '/footer.php'; ?>
<script src="/js/script.js"></script>
</body>
</html>
