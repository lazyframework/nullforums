<?php use NF\Security; ?>
<aside class="drawer" id="drawer" aria-hidden="true">
  <div class="drawer-header">
    <div class="logo"><span>Null</span><span class="f">Forums</span></div>
    <button class="drawer-close" id="drawerClose" aria-label="Tutup">&times;</button>
  </div>
  
  <nav class="drawer-nav">
    <?php if ($user): ?>
      <!-- Menu Khusus User Login -->
      <a href="/new" style="background: rgba(224,27,27,.15); color: var(--red); margin-bottom: 8px; font-family: 'Bebas Neue', sans-serif;">
        <span class="di" style="color: var(--red); border-color: var(--red);"><i class="ti ti-plus"></i></span> INJECT_THREAD
      </a>
      <a href="/u/<?= Security::e($user['username']) ?>" style="margin-bottom: 8px;">
        <span class="di"><i class="ti ti-user"></i></span> user@<?= Security::e($user['username']) ?>
      </a>
      <div style="height:1px; background:var(--border); margin: 4px 10px 12px;"></div>
    <?php endif; ?>

    <!-- Menu Utama Tema Terminal -->
    <a href="/" class="<?= $active === 'general' ? 'active' : '' ?>">
      <span class="di"><i class="ti ti-terminal-2"></i></span> ~/dashboard
    </a>
    <a href="#">
      <span class="di"><i class="ti ti-antenna"></i></span> ~/channels
    </a>
    <a href="/members" class="<?= $tpl === 'members' ? 'active' : '' ?>">
      <span class="di"><i class="ti ti-users"></i></span> ~/operatives
    </a>
    <a href="/search">
      <span class="di"><i class="ti ti-search"></i></span> ~/search_db
    </a>
    <a href="#">
      <span class="di"><i class="ti ti-shield-lock"></i></span> ~/protocols
    </a>
    
    <!-- Garis Pemisah Kategori -->
    <div style="height:1px; background:var(--border); margin: 12px 10px;"></div>
    <div style="font-size: 10px; text-transform: uppercase; color: var(--text-dim); margin: 0 14px 8px; letter-spacing: 1.5px; font-family: 'Bebas Neue', sans-serif;">// CATEGORIES</div>
    
    <?php foreach ($tabs as $t): ?>
      <a href="/<?= Security::e($t['slug']) ?>" class="<?= $t['slug'] === $active ? 'active' : '' ?>">
        <!-- GUNAKAN TAG <i> UNTUK ICON TABLER -->
        <span class="di"><i class="<?= Security::e($t['icon']) ?>"></i></span>
        <?= Security::e($t['name']) ?>
      </a>
    <?php endforeach; ?>
  </nav>
</aside>
