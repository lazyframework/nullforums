<?php use NF\Security; ?>

<section class="hero">
  <div>
    <h1><?= Security::e($cat['name']) ?> <b>Threads</b></h1>
    <p><?= Security::e($cat['description'] ?? '') ?></p>
  </div>
</section>

<div class="cat boxed">
  <div class="cat-head">
    <div class="icon"><?= Security::icon($cat['icon'] ?? '') ?></div>
    <h3>All Threads</h3>
    <span>Page <?= (int)$page ?> of <?= (int)$totalPages ?></span>
  </div>

  

  <?php if (empty($threads)): ?>
    <div class="empty-state">No threads yet. Be the first to post.</div>
  <?php endif; ?>

  <?php foreach ($threads as $t): ?>
    <div class="forum-row">
      <div class="f-icon"><?= Security::icon($t['icon'] ?? '') ?></div>
      <div>
        <div class="f-name">
          <a href="/t/<?= Security::e($t['slug']) ?>"><?= Security::e($t['title']) ?></a>
        </div>
        <div class="f-desc"><?= Security::e($t['forum_name']) ?> &middot; by <?= Security::e($t['username']) ?></div>
      </div>
      <div class="f-stat"><div class="n"><?= (int)$t['reply_count'] ?></div><div class="l">Replies</div></div>
      <div class="f-stat"><div class="n"><?= (int)$t['views'] ?></div><div class="l">Views</div></div>
    </div>
  <?php endforeach; ?>

  <?php if ($totalPages > 1): ?>
    <div class="pagination">
      <?php if ($page > 1): ?>
        <a href="?range=<?= Security::e($range) ?>&page=<?= $page - 1 ?>">‹ Prev</a>
      <?php else: ?>
        <span class="disabled">‹ Prev</span>
      <?php endif; ?>

      <?php
      $start = max(1, $page - 3);
      $end   = min($totalPages, $page + 3);

      if ($start > 1) {
          echo '<a href="?range=' . Security::e($range) . '&page=1">1</a>';
          if ($start > 2) echo '<span class="disabled">…</span>';
      }
      for ($i = $start; $i <= $end; $i++):
      ?>
        <?php if ($i === $page): ?>
          <span class="active"><?= $i ?></span>
        <?php else: ?>
          <a href="?range=<?= Security::e($range) ?>&page=<?= $i ?>"><?= $i ?></a>
        <?php endif; ?>
      <?php endfor; ?>

      <?php if ($end < $totalPages): ?>
        <?php if ($end < $totalPages - 1) echo '<span class="disabled">…</span>'; ?>
        <a href="?range=<?= Security::e($range) ?>&page=<?= $totalPages ?>"><?= $totalPages ?></a>
      <?php endif; ?>

      <?php if ($page < $totalPages): ?>
        <a href="?range=<?= Security::e($range) ?>&page=<?= $page + 1 ?>">Next ›</a>
      <?php else: ?>
        <span class="disabled">Next ›</span>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</div>
