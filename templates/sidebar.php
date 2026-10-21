<?php
use NF\{Category, Database, Security};

// Halaman buat thread dan halaman thread tidak memakai sidebar ini
if (in_array($tpl, ['new_thread', 'thread'], true)) return;

if ($tpl === 'home'):
    $latest = Category::latestThreads(5);
    $online = Category::onlineUsers(15, 7);
?>
  <div class="side-card">
    <h4><span class="ico">🔥</span> Latest Threads</h4>
    <?php foreach ($latest as $t): ?>
      <div class="thread-mini">
        <span class="ti"><?= Security::icon($t['icon']) ?></span>
        <span class="tag gray"><?= Security::e(ucfirst($t['cat_slug'])) ?></span>
        <a class="n" href="/t/<?= Security::e($t['slug']) ?>"><?= Security::e($t['title']) ?></a>
        <span class="t"><?= Security::e(relative_time($t['last_post_at'])) ?></span>
      </div>
    <?php endforeach; ?>
    <?php if (!$latest): ?>
      <div class="thread-mini"><span class="n">No threads yet</span></div>
    <?php endif; ?>
  </div>

  <div class="side-card">
    <h4><span class="ico">🟢</span> Online Now</h4>
    <div class="online-list">
      <?php foreach ($online as $o): ?>
        <div class="online-dot" title="<?= Security::e($o['username']) ?>">
          <?= Security::e($o['avatar_initial'] ?? strtoupper(substr($o['username'], 0, 1))) ?>
        </div>
      <?php endforeach; ?>
      <?php if (count($online) === 0): ?>
        <span style="color:var(--text-dim);font-size:12px">No one online</span>
      <?php endif; ?>
    </div>
  </div>

<?php elseif ($tpl === 'category'):
    $catStats = Database::q(
        'SELECT COUNT(t.id) AS threads, COALESCE(SUM(t.reply_count), 0) AS replies
           FROM threads t
           JOIN forums f ON f.id = t.forum_id
          WHERE f.category_id = ? AND t.is_deleted = 0',
        [(int)$cat['id']]
    )->fetch() ?: ['threads' => 0, 'replies' => 0];

    $topThreads = Database::q(
        'SELECT t.title, t.slug, t.reply_count, f.icon
           FROM threads t
           JOIN forums f ON f.id = t.forum_id
          WHERE f.category_id = ? AND t.is_deleted = 0
       ORDER BY t.reply_count DESC, t.views DESC
          LIMIT 5',
        [(int)$cat['id']]
    )->fetchAll();
?>
  <div class="side-card">
    <h4><span class="ico"><?= Security::icon($cat['icon'] ?? '') ?></span> <?= Security::e($cat['name']) ?></h4>
    <p style="font-size:12px; color:var(--text-dim); margin-bottom:12px; line-height:1.5">
      <?= Security::e($cat['description'] ?? '') ?>
    </p>
    <div class="stat-line">
      <span class="si">🧵</span>
      <span class="n">Threads</span>
      <span class="t"><?= number_format((int)$catStats['threads'], 0, ',', '.') ?></span>
    </div>
    <div class="stat-line">
      <span class="si">💬</span>
      <span class="n">Replies</span>
      <span class="t"><?= number_format((int)$catStats['replies'], 0, ',', '.') ?></span>
    </div>
  </div>

  <div class="side-card sidebar-desktop-only">
    <h4><span class="ico">🏆</span> Most Replied</h4>
    <?php foreach ($topThreads as $t): ?>
      <div class="thread-mini">
        <span class="ti"><?= Security::icon($t['icon']) ?></span>
        <a class="n" href="/t/<?= Security::e($t['slug']) ?>"><?= Security::e($t['title']) ?></a>
        <span class="t"><?= (int)$t['reply_count'] ?></span>
      </div>
    <?php endforeach; ?>
    <?php if (!$topThreads): ?>
      <div class="thread-mini"><span class="n">No threads yet</span></div>
    <?php endif; ?>
  </div>
<?php endif; ?>
