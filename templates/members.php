<?php use NF\Security; ?>

<section class="hero">
  <div>
    <h1>List of <b>Members</b></h1>
    <p>Total <?= number_format($total, 0, ',', '.') ?> a registered member of the NullForums community.</p>
  </div>
</section>

<div class="cat boxed">
  <div class="cat-head">
    <div class="icon">👥</div>
    <h3>All Members</h3>
    <span>Pages <?= $page ?> dari <?= $totalPages ?></span>
  </div>

  <?php if (empty($users)): ?>
    <div class="empty-state">Belum ada anggota terdaftar.</div>
  <?php endif; ?>

  <?php foreach ($users as $u): ?>
    <a class="profile-thread-item" href="/u/<?= Security::e($u['username']) ?>" style="align-items: center;">

      <!-- Avatar -->
      <div class="pti-icon" style="border-radius: 50%; background: linear-gradient(135deg, var(--red-dark), #3a0606); color: #fff; border: 1px solid var(--red-dark);">
        <?= Security::e($u['avatar_initial'] ?? strtoupper(substr($u['username'], 0, 1))) ?>
      </div>

      <!-- Info User -->
      <div class="pti-body">
        <div class="pti-title">
          <?= Security::e($u['username']) ?>
          <?php if (($u['role'] ?? 'user') !== 'user'): ?>
            <span class="tag red" style="margin-left: 6px; font-size: 9px; padding: 1px 6px; vertical-align: middle;"><?= Security::e(ucfirst($u['role'])) ?></span>
          <?php endif; ?>
        </div>
        <div class="pti-meta">
          Bergabung <?= Security::e(relative_time($u['created_at'])) ?>
        </div>
      </div>

      <!-- Statistik -->
      <div class="pti-stats" style="background: transparent; border: none; padding: 0;">
        <div class="stat-item">
          <span class="n"><?= (int)$u['thread_count'] ?></span>
          <span class="l">Thread</span>
        </div>
        <div class="stat-item">
          <span class="n"><?= (int)$u['post_count'] ?></span>
          <span class="l">Balasan</span>
        </div>
      </div>

    </a>
  <?php endforeach; ?>

  <!-- NAVIGATION PAGINATION (selalu tampil) -->
  <div class="slider-nav">
    <?php if ($page > 1): ?>
      <a href="/members?page=<?= $page - 1 ?>" class="slider-btn" aria-label="Halaman Sebelumnya">‹</a>
    <?php else: ?>
      <span class="slider-btn" style="opacity:.35; cursor:default;">‹</span>
    <?php endif; ?>

    <span class="slider-count"><?= $page ?> / <?= $totalPages ?></span>

    <?php if ($page < $totalPages): ?>
      <a href="/members?page=<?= $page + 1 ?>" class="slider-btn" aria-label="Halaman Berikutnya">›</a>
    <?php else: ?>
      <span class="slider-btn" style="opacity:.35; cursor:default;">›</span>
    <?php endif; ?>
  </div>
</div>
