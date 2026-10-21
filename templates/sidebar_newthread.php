<?php
use NF\{Security, Database};

if (empty($user)) return;

$info = Database::q(
    'SELECT u.created_at, u.avatar_path, u.avatar_initial,
            (SELECT COUNT(*) FROM threads t WHERE t.user_id = u.id AND t.is_deleted = 0) AS thread_count,
            (SELECT COUNT(*) FROM posts p  WHERE p.user_id = u.id AND p.is_deleted = 0) AS post_count
       FROM users u
      WHERE u.id = ?',
    [(int)$user['id']]
)->fetch() ?: [];

$initial = $info['avatar_initial'] ?? strtoupper(substr($user['username'], 0, 1));
?>

<div class="side-card">
  <h4><span class="ico">✍️</span> Posting As</h4>

  <div style="text-align:center; padding:8px 0 12px">
    <div class="avatar" style="width:64px; height:64px; font-size:24px; margin:0 auto 10px; overflow:hidden; padding:0">
      <?php if (!empty($info['avatar_path'])): ?>
        <img src="<?= Security::e($info['avatar_path']) ?>" alt="" style="width:100%; height:100%; object-fit:cover">
      <?php else: ?>
        <?= Security::e($initial) ?>
      <?php endif; ?>
    </div>

    <a href="/u/<?= Security::e($user['username']) ?>"
       style="font-family:'Space Grotesk',sans-serif; font-weight:700; color:var(--text-bright)">
      <?= Security::e($user['username']) ?>
    </a>

    <div style="font-size:12px; color:var(--text-dim); margin-top:4px">
      <?= Security::e(ucfirst($user['role'] ?? 'user')) ?> · Joined <?= Security::e(relative_time($info['created_at'] ?? date('Y-m-d H:i:s'))) ?>
    </div>
  </div>

  <div class="stat-line">
    <span class="si">🧵</span>
    <span class="n">Threads</span>
    <span class="t"><?= (int)($info['thread_count'] ?? 0) ?></span>
  </div>
  <div class="stat-line">
    <span class="si">💬</span>
    <span class="n">Replies</span>
    <span class="t"><?= (int)($info['post_count'] ?? 0) ?></span>
  </div>
</div>
