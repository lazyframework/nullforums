<?php use NF\Security; ?>

<section class="hero">
  <div>
    <h1>Hasil Pencarian: <b><?= Security::e($q) ?></b></h1>
    <p><?= count($results) ?> thread ditemukan.</p>
  </div>
</section>

<div class="cat boxed">
  <?php if (!$results): ?>
    <div class="forum-row" style="grid-template-columns:1fr"><div class="f-desc" style="padding:8px 0">Tidak ada hasil.</div></div>
  <?php endif; ?>
  <?php foreach ($results as $r): ?>
    <a class="forum-row" href="/t/<?= Security::e($r['slug']) ?>" style="text-decoration:none;color:inherit">
      <div class="f-icon"><?= Security::e($r['icon']) ?></div>
      <div>
        <div class="f-name"><?= Security::e($r['title']) ?></div>
        <div class="f-desc"><?= Security::e($r['cat_name']) ?> &middot; <?= Security::e(relative_time($r['last_post_at'])) ?></div>
      </div>
      <div class="f-stat"><div class="n"><?= (int)$r['reply_count'] ?></div><div class="l">Balasan</div></div>
      <div class="f-stat"><div class="n"><?= number_format((int)$r['views'], 0, ',', '.') ?></div><div class="l">Dilihat</div></div>
      <div class="last-post"></div>
    </a>
  <?php endforeach; ?>
</div>
