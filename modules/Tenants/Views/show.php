<?php /** Tenants → Views/show.php — profile + lease history */ ?>

<section class="panel">
  <div class="panel-head">
    <h2><?= e($tenant['name']) ?>
      <span class="<?= e(badge($tenant['kind'] === 'corporate' ? 'leased' : 'vacant')) ?>">
        <?= e($tenant['kind'] === 'corporate' ? t('tenant.corporate') : t('tenant.individual')) ?>
      </span>
    </h2>
    <a class="btn" href="<?= e(url('tenants')) ?>">← <?= e(t('common.back')) ?></a>
  </div>

  <div class="detail-grid">
    <div><span class="muted"><?= e(t('common.phone')) ?></span><strong><?= e($tenant['phone'] ?? '—') ?></strong></div>
    <div><span class="muted"><?= e(t('common.email')) ?></span><strong><?= e($tenant['email'] ?? '—') ?></strong></div>
    <div><span class="muted"><?= e(t('tenant.id_doc')) ?></span>
      <strong><?= e($tenant['id_type'] ? str_replace('_', ' ', $tenant['id_type']) : '—') ?>
      <?= $tenant['id_number'] ? '· ' . e($tenant['id_number']) : '' ?></strong></div>
    <div><span class="muted"><?= e(t('tenant.emergency')) ?></span>
      <strong><?= e(trim(($tenant['emergency_name'] ?? '') . ' ' . ($tenant['emergency_phone'] ?? '')) ?: '—') ?></strong></div>
  </div>
  <?php if ($tenant['notes']): ?><p class="muted"><?= e($tenant['notes']) ?></p><?php endif; ?>
</section>

<section class="panel">
  <h2><?= e(t('tenant.leases')) ?></h2>
  <table class="table">
    <thead><tr>
      <th><?= e(t('lease.code')) ?></th><th><?= e(t('lease.unit')) ?></th>
      <th><?= e(t('lease.rent')) ?></th><th><?= e(t('lease.start')) ?> → <?= e(t('lease.end')) ?></th>
      <th><?= e(t('common.status')) ?></th>
    </tr></thead>
    <tbody>
      <?php foreach ($leases as $l): ?>
      <tr>
        <td><a href="<?= e(url('leases/' . $l['id'])) ?>"><?= e($l['code']) ?></a></td>
        <td><?= e($l['unit_code']) ?> · <?= e($l['building_name']) ?></td>
        <td class="num"><?= e(fmt_xaf($l['rent_xaf'])) ?></td>
        <td><?= e(fmt_date($l['start_date'])) ?> → <?= e(fmt_date($l['end_date'])) ?></td>
        <td><span class="<?= e(badge($l['status'])) ?>"><?= e(str_replace('_', ' ', $l['status'])) ?></span></td>
      </tr>
      <?php endforeach; ?>
      <?php if (!$leases): ?><tr><td colspan="5" class="empty"><?= e(t('common.none')) ?></td></tr><?php endif; ?>
    </tbody>
  </table>
</section>
