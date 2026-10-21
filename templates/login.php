<?php use NF\{Security, Csrf}; ?>

<section class="hero">
  <div><h1>Login <b>NullForums</b></h1><p>Use an already registered account.</p></div>
</section>

<?php if (!empty($error)): ?>
  <div class="alert error">
    <?= Security::e($error) ?>
  </div>
<?php endif; ?>

<div class="cat boxed" style="padding:16px">
  <form method="post" action="/login" autocomplete="off">
    <?= Csrf::field() ?>
    
    <div class="form-group">
      <label for="login">Username or Email</label>
      <input type="text" id="login" name="login" class="form-input" placeholder="Masukkan username atau email" required maxlength="190"
        value="<?= Security::e($_POST['login'] ?? '') ?>">
    </div>

    <div class="form-group">
      <label for="password">Password</label>
      <input type="password" id="password" name="password" class="form-input" placeholder="Masukkan kata sandi" required minlength="8">
    </div>

    <!-- CLOUDFLARE TURNSTILE WIDGET -->
    <div class="cf-turnstile" data-sitekey="<?= Security::e($_ENV['TURNSTILE_SITE_KEY'] ?? '') ?>" data-theme="dark" data-appearance="always" style="margin-bottom: 14px;"></div>
    <button class="btn btn-primary" type="submit" style="width:100%">Masuk</button>
  </form>
  
  <p style="text-align:center;margin-top:16px;font-size:12px;color:var(--text-dim)">
    Belum punya akun? <a href="/register">Daftar</a>
  </p>
</div>
