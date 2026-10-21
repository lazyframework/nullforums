<?php use NF\Security; ?>
<header>
  <div class="topbar">
    <button class="menu-toggle" id="menuToggle" aria-label="Buka menu">
      <span></span><span></span><span></span>
    </button>
    <div class="logo"><span>Null</span><span class="f">Forums</span></div>

    <nav class="main">
      <a href="/" class="<?= $active === 'general' ? 'active' : '' ?>">Beranda</a>
      <a href="#">Forum</a>
      <a href="/members" class="<?= $tpl === 'members' ? 'active' : '' ?>">Anggota</a>
      <a href="#">Aturan</a>
      <?php if ($user && in_array($user['role'], ['admin', 'mod'], true)): ?>
        <a href="/admin" style="color: var(--red);">Panel Admin</a>
      <?php endif; ?>
    </nav>

    <div class="user-area">
      <div class="search-bar">
        <span class="search-icon">🔍</span>
        <input type="text" id="searchInputNav" placeholder="Cari diskusi...">
      </div>

      <?php if ($user): ?>
        <!-- Jika user sudah login -->
        <a class="btn btn-primary desk-only" href="/new">+ Thread</a>
        <a class="btn desk-only" href="/u/<?= Security::e($user['username']) ?>"><?= Security::e($user['username']) ?></a>
        
        <a class="btn" href="/logout">Keluar</a>
        
        <!-- Avatar User (Dengan Dukungan Upload Foto) -->
        <div class="avatar" title="<?= Security::e($user['username']) ?>" style="overflow: hidden; padding: 0;">
          <?php if (!empty($user['avatar_path'])): ?>
            <img src="<?= Security::e($user['avatar_path']) ?>" alt="Avatar" style="width: 100%; height: 100%; object-fit: cover;">
          <?php else: ?>
            <?= Security::e($user['avatar_initial'] ?? strtoupper(substr($user['username'], 0, 1))) ?>
          <?php endif; ?>
        </div>
      <?php else: ?>
        <!-- Jika user belum login (Tamu) -->
        <a class="btn" href="/login">Masuk</a>
        <a class="btn btn-primary" href="/register">Daftar</a>
        <div class="avatar" title="Tamu">?</div>
      <?php endif; ?>
    </div>
  </div>
</header>
