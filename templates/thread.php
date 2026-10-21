<?php
use NF\{Security, Csrf, Database};

// Data pembuat thread: tanggal bergabung dan jumlah thread + balasan yang belum dihapus
$author = Database::q(
    'SELECT u.created_at,
            (SELECT COUNT(*) FROM threads t WHERE t.user_id = u.id AND t.is_deleted = 0)
          + (SELECT COUNT(*) FROM posts p  WHERE p.user_id = u.id AND p.is_deleted = 0) AS post_count
       FROM users u
      WHERE u.id = ?',
    [(int)$thread['user_id']]
)->fetch() ?: ['created_at' => $thread['created_at'], 'post_count' => 0];
?>

<!-- BREADCRUMB -->
<div class="rf-breadcrumb">
  <a href="/"><i class="ti ti-home"></i> NullForums</a>
  <span>&rsaquo;</span>
  <a href="/<?= Security::e($thread['cat_slug']) ?>"><?= Security::e($thread['cat_name']) ?></a>
  <span>&rsaquo;</span>
  <span><?= Security::e($thread['forum_name']) ?></span>
</div>

<!-- HEADER THREAD -->
<div class="rf-thread-header">
  <span class="rf-tag">THREAD</span>
  <h1><?= Security::e($thread['title']) ?></h1>
  <p>by <a href="/u/<?= Security::e($thread['username']) ?>" class="rf-author-link"><?= Security::e($thread['username']) ?></a>
     - <?= date('M j, Y', strtotime($thread['created_at'])) ?></p>
</div>

<!-- POSTINGAN UTAMA -->
<div class="rf-post-container">

  <!-- PROFIL USER -->
  <div class="rf-post-sidebar">
    <div class="rf-user-block">
      <a href="/u/<?= Security::e($thread['username']) ?>" class="rf-username"><?= Security::e($thread['username']) ?></a>

      <div class="rf-avatar-large">
        <?php if (!empty($thread['avatar_path'])): ?>
          <img src="<?= Security::e($thread['avatar_path']) ?>" alt="Avatar">
        <?php else: ?>
          <div class="rf-initial"><?= Security::e($thread['avatar_initial'] ?? strtoupper(substr($thread['username'], 0, 1))) ?></div>
        <?php endif; ?>
      </div>

      <div class="rf-member-badge">
        <div class="rf-badge-inner">MEMBER</div>
      </div>

      <div class="rf-user-stats">
        <div class="rf-stat-row"><span class="rf-lbl">Posts</span><span class="rf-val"><?= (int)$author['post_count'] ?></span></div>
        <div class="rf-stat-row"><span class="rf-lbl">Joined</span><span class="rf-val"><?= date('M Y', strtotime($author['created_at'])) ?></span></div>
      </div>
    </div>
  </div>

  <!-- ISI POSTINGAN -->
  <div class="rf-post-content">
    <div class="rf-post-date"><?= date('M j, Y', strtotime($thread['created_at'])) ?> at <?= date('g:i A', strtotime($thread['created_at'])) ?></div>

    <div class="rf-message-body">
      <?php
      $safeBody   = Security::e($thread['body']);
      $linkedBody = preg_replace(
          '/(https?:\/\/[^\s]+)/i',
          '<a href="$1" target="_blank" rel="noopener noreferrer">$1</a>',
          $safeBody
      );
      echo nl2br($linkedBody);
      ?>
    </div>
  </div>
</div>

<!-- DAFTAR BALASAN -->
<div class="rf-replies-container">
  <div class="rf-replies-header">Replies (<?= (int)$thread['reply_count'] ?>)</div>

  <?php if (!$replies): ?>
    <div class="rf-no-reply">No replies yet.</div>
  <?php endif; ?>

  <?php foreach ($replies as $r): ?>
    <div class="rf-post-container">
      <div class="rf-post-sidebar">
        <div class="rf-user-block">
          <a href="/u/<?= Security::e($r['username']) ?>" class="rf-username"><?= Security::e($r['username']) ?></a>
          <div class="rf-avatar-large" style="width:100px; height:100px;">
            <?php if (!empty($r['avatar_path'])): ?>
              <img src="<?= Security::e($r['avatar_path']) ?>" alt="Avatar">
            <?php else: ?>
              <div class="rf-initial" style="font-size:36px"><?= Security::e($r['avatar_initial'] ?? strtoupper(substr($r['username'], 0, 1))) ?></div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div class="rf-post-content">
        <div class="rf-post-date"><?= date('M j, Y', strtotime($r['created_at'])) ?> at <?= date('g:i A', strtotime($r['created_at'])) ?></div>
        <div class="rf-message-body"><?= nl2br(Security::e($r['body'])) ?></div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<!-- FORM BALASAN -->
<?php if (!empty($user) && !(int)$thread['is_locked']): ?>
<div class="rf-reply-box">
  <h3>Post Reply</h3>
  <form method="post" action="/t/<?= Security::e($thread['slug']) ?>/reply">
    <?= Csrf::field() ?>
    <textarea name="body" rows="6" required minlength="10" maxlength="20000" class="rf-textarea"
              placeholder="Write your reply here..."></textarea>
    <div style="text-align:right; margin-top:10px">
      <button class="rf-btn-submit" type="submit">Post Reply</button>
    </div>
  </form>
</div>
<?php elseif (empty($user)): ?>
<div class="rf-reply-box" style="text-align:center">
  <a href="/login" class="rf-author-link">Log in</a> to reply to this thread.
</div>
<?php endif; ?>

<div id="bottom"></div>
