<?php use NF\{Security, Csrf}; ?>

<?php
$isOwner   = !empty($user) && (int)$user['id'] === (int)$profileUser['id'];
$isStaff   = !empty($user) && in_array($user['role'] ?? '', ['admin', 'mod'], true);
$canDeleteAny = $isOwner || $isStaff;

$perPage    = 20;
$totalPages = max(1, (int)ceil($totalThreads / $perPage));
?>

<!-- HEADER PROFIL -->
<div class="cat boxed" style="padding:20px; border-top:3px solid var(--red); margin-bottom:18px">
  <div style="display:flex; align-items:center; gap:18px; flex-wrap:wrap">
    <div class="avatar" style="width:84px; height:84px; font-size:32px; overflow:hidden; padding:0; flex-shrink:0">
      <?php if (!empty($profileUser['avatar_path'])): ?>
        <img src="<?= Security::e($profileUser['avatar_path']) ?>" alt="" style="width:100%; height:100%; object-fit:cover">
      <?php else: ?>
        <?= Security::e($profileUser['avatar_initial'] ?? strtoupper(substr($profileUser['username'], 0, 1))) ?>
      <?php endif; ?>
    </div>

    <div style="min-width:0">
      <h1 style="font-family:'Space Grotesk',sans-serif; font-size:24px; color:var(--red); word-break:break-all">
        <?= Security::e($profileUser['username']) ?>
      </h1>
      <p style="font-size:13px; color:var(--text-dim); margin-top:4px">
        Bergabung <?= Security::e(relative_time($profileUser['created_at'])) ?>
        <?php if (($profileUser['role'] ?? 'user') !== 'user'): ?>
          · <span class="tag red"><?= Security::e(ucfirst($profileUser['role'])) ?></span>
        <?php endif; ?>
      </p>
    </div>
  </div>

  <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-top:20px">
    <div class="side-card" style="text-align:center; margin:0; padding:14px">
      <div style="font-size:22px; font-weight:700; color:var(--red)"><?= (int)$totalThreads ?></div>
      <div style="font-size:11px; color:var(--text-dim); letter-spacing:1px; text-transform:uppercase">Thread</div>
    </div>
    <div class="side-card" style="text-align:center; margin:0; padding:14px">
      <div style="font-size:22px; font-weight:700; color:var(--red)"><?= (int)($profileUser['post_count'] ?? 0) ?></div>
      <div style="font-size:11px; color:var(--text-dim); letter-spacing:1px; text-transform:uppercase">Balasan</div>
    </div>
  </div>
</div>

<!-- GANTI FOTO PROFIL (hanya untuk pemilik profil) -->
<?php if ($isOwner): ?>
  <div class="cat boxed" style="padding:16px; margin-bottom:18px; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px">
    <div>
      <h3 style="font-size:14px; margin-bottom:2px">Change Profile Picture</h3>
      <p style="font-size:12px; color:var(--text-dim)">Max. 2MB (JPG, PNG, WEBP)</p>
    </div>
    <form method="post" action="/u/avatar" enctype="multipart/form-data" style="display:flex; gap:8px; align-items:center; flex-wrap:wrap">
      <?= Csrf::field() ?>
      <div class="custom-file-upload">
        <input type="file" name="avatar" id="avatarInput" accept="image/jpeg, image/png, image/webp" required
               onchange="document.getElementById('fileName').textContent = this.files[0].name">
        <label for="avatarInput" class="btn" style="padding:6px 12px; font-size:12px; cursor:pointer">
          <i class="ti ti-upload"></i> Choose File
        </label>
        <span id="fileName" style="font-size:12px; color:var(--text-dim); margin-left:8px">No file chosen</span>
      </div>
      <button type="submit" class="btn btn-primary" style="padding:6px 12px; font-size:12px">Upload</button>
    </form>
  </div>
<?php endif; ?>

<!-- DAFTAR THREAD -->
<div class="cat boxed">
  <div class="cat-head">
    <div class="icon">🧵</div>
    <h3>Thread oleh <?= Security::e($profileUser['username']) ?></h3>
    <span><?= (int)$totalThreads ?> Thread</span>
  </div>

  <?php if (empty($threads)): ?>
    <div class="empty-state">User ini belum pernah membuat thread.</div>
  <?php endif; ?>

  <?php foreach ($threads as $t): ?>
    <div class="profile-thread-wrapper" id="thread-<?= (int)$t['id'] ?>">
      <a class="profile-thread-item" href="/t/<?= Security::e($t['slug']) ?>">
        <div class="pti-icon"><?= Security::icon($t['forum_icon'] ?? $t['icon'] ?? '') ?></div>
        <div class="pti-body">
          <div class="pti-title"><?= Security::e($t['title']) ?></div>
          <div class="pti-meta">
            <span class="pti-cat"><?= Security::e($t['cat_name'] ?? '') ?></span>
            <span class="pti-dot">&middot;</span>
            <span class="pti-forum"><?= Security::e($t['forum_name'] ?? '') ?></span>
            <span class="pti-dot">&middot;</span>
            <span class="pti-time"><?= Security::e(relative_time($t['created_at'])) ?></span>
          </div>
        </div>
        <div class="pti-stats">
          <div class="stat-item"><span class="n"><?= (int)$t['reply_count'] ?></span><span class="l">Balasan</span></div>
          <div class="stat-item"><span class="n"><?= number_format((int)$t['views'], 0, ',', '.') ?></span><span class="l">Dilihat</span></div>
        </div>
      </a>

      <?php if ($canDeleteAny): ?>
        <button type="button" class="btn-delete-thread" data-id="<?= (int)$t['id'] ?>" title="Hapus Thread" aria-label="Hapus Thread">
          <i class="ti ti-trash"></i>
        </button>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>

  <?php if ($totalPages > 1): ?>
    <div class="pagination">
      <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <a class="<?= $i === $page ? 'active' : '' ?>"
           href="/u/<?= Security::e($profileUser['username']) ?>?page=<?= $i ?>"><?= $i ?></a>
      <?php endfor; ?>
    </div>
  <?php endif; ?>
</div>
