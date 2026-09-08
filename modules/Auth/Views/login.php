<?php /** Auth → Views/login.php — standalone sign-in card (layout: auth) */ ?>
<div class="login-wrap">
  <div class="login-card">
    <div class="brand">
      <span class="brand-mark">PB</span>
      <div>
        <div class="brand-name"><?= e(t('app.name')) ?></div>
        <div class="brand-tag"><?= e(t('app.tagline')) ?></div>
      </div>
    </div>

    <?php if (!empty($_SESSION['flash'])): $f = $_SESSION['flash']; unset($_SESSION['flash']); ?>
      <div class="flash flash-<?= e($f['type']) ?>"><?= e($f['msg']) ?></div>
    <?php endif; ?>

    <form method="post" action="<?= e(url('login')) ?>">
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
      <label class="field">
        <span><?= e(t('auth.email')) ?></span>
        <input type="email" name="email" required autofocus autocomplete="username">
      </label>
      <label class="field">
        <span><?= e(t('auth.password')) ?></span>
        <input type="password" name="password" required autocomplete="current-password">
      </label>
      <button class="btn btn-primary btn-block" type="submit"><?= e(t('auth.login')) ?></button>
    </form>

    <p class="login-hint">Demo staff: admin@littledrops.cm · Demo@2026</p>
  </div>
</div>
