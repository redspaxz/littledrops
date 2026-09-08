<?php /** Dashboard → Views/404.php */ ?>
<section class="panel">
  <h2>404 — <?= e($title ?? 'Not found') ?></h2>
  <p><a class="btn" href="<?= e(url('dashboard')) ?>"><?= e(t('nav.dashboard')) ?></a></p>
</section>
