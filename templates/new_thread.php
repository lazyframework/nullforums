<?php use NF\{Security, Csrf}; ?>
<?php $isGlobal = empty($forum); ?>

<section class="hero">
  <div>
    <h1>New <b>Thread</b></h1>
    <?php if ($isGlobal): ?>
      <p>Choose a category, then write your thread.</p>
    <?php else: ?>
      <p>Forum: <?= Security::e($forum['name']) ?> (<?= Security::e($forum['category_name']) ?>)</p>
    <?php endif; ?>
  </div>
</section>

<?php if (!empty($error)): ?>
  <div class="alert error"><?= Security::e($error) ?></div>
<?php endif; ?>

<div class="cat boxed">
  <div class="cat-head">
    <div class="icon">✏️</div>
    <h3>New Thread</h3>
  </div>

  <form method="post"
        action="<?= $isGlobal ? '/new' : '/f/' . Security::e($forum['slug']) . '/new' ?>"
        style="padding:16px">
    <?= Csrf::field() ?>

    <?php if ($isGlobal): ?>
      <div class="form-group">
        <label for="catSelect">Category</label>
        <select id="catSelect" name="category" class="form-select" required>
          <option value="">— Select a category —</option>
          <?php foreach ($cats as $c): ?>
            <option value="<?= Security::e($c['slug']) ?>"
              <?= (($_POST['category'] ?? '') === $c['slug']) ? 'selected' : '' ?>>
              <?= Security::e($c['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    <?php endif; ?>

    <div class="form-group">
      <label for="title">Thread Title</label>
      <input type="text" id="title" name="title" class="form-input" required minlength="5" maxlength="180"
             value="<?= Security::e($_POST['title'] ?? '') ?>"
             placeholder="Write a clear and specific title...">
    </div>

    <div class="form-group">
      <label for="body">Thread Content</label>
      <textarea id="body" name="body" class="form-textarea" required minlength="10" maxlength="20000"
                placeholder="Write the details of your post here..."><?= Security::e($_POST['body'] ?? '') ?></textarea>
    </div>

    <div class="form-actions">
      <a href="javascript:history.back()" class="btn">Cancel</a>
      <button class="btn btn-primary" type="submit">Publish</button>
    </div>
  </form>
</div>
