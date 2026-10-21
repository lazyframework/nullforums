<?php use NF\{Security, Csrf}; ?>

<section class="hero">
  <div><h1>Register a <b>New Account</b></h1><p>Join the NullForums community.</p></div>
</section>

<?php if (!empty($errors)): ?>
  <div class="alert error">
    <ul style="margin-left: 16px;">
      <?php foreach ($errors as $err): ?>
        <li><?= Security::e($err) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<div class="cat boxed" style="padding:16px">
  <form method="post" action="/register" autocomplete="off">
    <?= Csrf::field() ?>

    <div class="form-group">
      <label for="username">Username</label>
      <input type="text" id="username" name="username" class="form-input" placeholder="Change username (3-32 karakter)" required minlength="3" maxlength="32"
        value="<?= Security::e($_POST['username'] ?? '') ?>">
    </div>

    <div class="form-group">
      <label for="email">Email</label>
      <input type="email" id="email" name="email" class="form-input" placeholder="Email" required maxlength="190"
        value="<?= Security::e($_POST['email'] ?? '') ?>">
    </div>

    <div class="form-group">
      <label for="password">Pasword</label>
      <input type="password" id="password" name="password" class="form-input" placeholder="Minimal 10 karakter (huruf besar, kecil, angka)" required minlength="10">
    </div>

    <div class="form-group">
      <label for="password2">Confirm Password</label>
      <input type="password" id="password2" name="password2" class="form-input" placeholder="Ulangi kata sandi" required minlength="10">
    </div>

    <!-- CLOUDFLARE TURNSTILE WIDGET -->
    <div class="cf-turnstile" data-sitekey="<?= Security::e($_ENV['TURNSTILE_SITE_KEY'] ?? '') ?>" data-theme="dark" data-appearance="always" style="margin-bottom: 14px;"></div>

    <button class="btn btn-primary" type="submit" style="width:100%">Daftar</button>
  </form>

  <p style="text-align:center;margin-top:16px;font-size:12px;color:var(--text-dim)">
    Sudah punya akun? <a href="/login">Masuk</a>
  </p>
</div>
