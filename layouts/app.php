<?php
/**
 * Layouts/app.php — authenticated shell: sidebar nav, top bar (locale + user),
 * flash messages, content. $content is the rendered module view.
 */
$user = current_user();
$nav = [
    'dashboard'   => ['📊', t('nav.dashboard')],
    'properties'  => ['🏢', t('nav.properties')],
    'units'       => ['🚪', t('prop.units')],
    'tenants'     => ['👥', t('nav.tenants')],
    'leases'      => ['📜', t('nav.leases')],
    'billing'     => ['💰', t('nav.billing')],
    'maintenance' => ['🔧', t('nav.maintenance')],
    'utilities'   => ['💡', t('nav.utilities')],
    'access'      => ['🔐', t('nav.access')],
];
$current = \Core\Request::route();
$locale  = \Core\Auth::locale();
?>
<!DOCTYPE html>
<html lang="<?= e($locale) ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title ?? t('app.name')) ?> · <?= e(t('app.name')) ?></title>
<link rel="stylesheet" href="<?= e(asset('assets/css/app.css')) ?>">
</head>
<body>
<div class="shell">
  <aside class="sidebar">
    <div class="brand">
      <span class="brand-mark">PB</span>
      <div>
        <div class="brand-name"><?= e(t('app.name')) ?></div>
        <div class="brand-tag"><?= e(t('app.tagline')) ?></div>
      </div>
    </div>
    <nav class="nav">
      <?php foreach ($nav as $route => [$icon, $label]): ?>
        <a class="nav-link <?= str_starts_with($current, $route) ? 'active' : '' ?>" href="<?= e(url($route)) ?>">
          <span class="nav-icon"><?= $icon ?></span><?= e($label) ?>
        </a>
      <?php endforeach; ?>
    </nav>
    <div class="sidebar-foot">
      <span class="pill pill-en"><?= $locale === 'en' ? 'EN' : 'FR' ?></span>
      <span class="muted">XAF · <?= e(date('d M Y')) ?></span>
    </div>
  </aside>

  <div class="main">
    <header class="topbar">
      <h1 class="page-title"><?= e($title ?? '') ?></h1>
      <div class="topbar-actions">
        <form method="post" action="<?= e(url('locale')) ?>" class="inline-form">
          <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="back" value="<?= e($current) ?>">
          <button class="btn btn-ghost" name="locale" value="<?= $locale === 'en' ? 'fr' : 'en' ?>" type="submit"
                  title="Français / English"><?= $locale === 'en' ? '🇫🇷 FR' : '🇬🇧 EN' ?></button>
        </form>
        <span class="user-chip">
          <strong><?= e($user['full_name']) ?></strong>
          <span class="muted"><?= e(\Core\Auth::ROLES[$user['role']] ?? $user['role']) ?></span>
        </span>
        <form method="post" action="<?= e(url('logout')) ?>" class="inline-form">
          <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
          <button class="btn" type="submit"><?= e(t('nav.logout')) ?></button>
        </form>
      </div>
    </header>

    <?php if (!empty($_SESSION['flash'])): $f = $_SESSION['flash']; unset($_SESSION['flash']); ?>
      <div class="flash flash-<?= e($f['type']) ?>"><?= e($f['msg']) ?></div>
    <?php endif; ?>

    <main class="content"><?= $content ?></main>

    <footer class="footer">
      <?= e(t('app.name')) ?> - Common Law edition for the North-West Region, Cameroon.
    </footer>
  </div>
</div>
<script src="<?= e(asset('assets/js/charts.js')) ?>"></script>
<script src="<?= e(asset('assets/js/app.js')) ?>"></script>
</body>
</html>
