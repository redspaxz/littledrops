<?php /** Properties → Views/show.php — one building and its units */ ?>

<section class="panel">
  <div class="panel-head">
    <h2><?= e($building['name']) ?> <span class="muted">· <?= e($building['quarter']) ?>, <?= e($building['city']) ?></span></h2>
    <a class="btn" href="<?= e(url('properties')) ?>">← <?= e(t('common.back')) ?></a>
  </div>
  <p class="muted"><?= e($building['portfolio_name']) ?> · <?= e($building['address'] ?? '') ?></p>

  <table class="table">
    <thead><tr>
      <th><?= e(t('prop.unit_code')) ?></th>
      <th><?= e(t('prop.unit_type')) ?></th>
      <th class="num"><?= e(t('prop.sqm')) ?></th>
      <th class="num"><?= e(t('prop.market_rent')) ?></th>
      <th><?= e(t('common.status')) ?></th>
      <th><?= e(t('lease.tenant')) ?></th>
    </tr></thead>
    <tbody>
      <?php foreach ($units as $u): ?>
      <tr>
        <td><strong><?= e($u['code']) ?></strong></td>
        <td><?= e(str_replace('_', ' ', $u['unit_type'])) ?><?= $u['bedrooms'] ? ' · ' . (int) $u['bedrooms'] . 'BR' : '' ?></td>
        <td class="num"><?= $u['sqm'] ? e(rtrim(rtrim((string) $u['sqm'], '0'), '.')) : '—' ?></td>
        <td class="num"><?= e(fmt_xaf($u['market_rent_xaf'])) ?></td>
        <td><span class="<?= e(badge($u['status'])) ?>"><?= e(str_replace('_', ' ', $u['status'])) ?></span></td>
        <td>
          <?php if ($u['lease_id']): ?>
            <a href="<?= e(url('leases/' . $u['lease_id'])) ?>"><?= e($u['lease_code']) ?></a> — <?= e($u['tenant_name']) ?>
          <?php else: ?>—<?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (!$units): ?><tr><td colspan="6" class="empty"><?= e(t('common.none')) ?></td></tr><?php endif; ?>
    </tbody>
  </table>
</section>
