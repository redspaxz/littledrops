<?php /** Utilities → Views/index.php — meters + shared-bill allocation */ ?>

<section class="panel">
  <h2><?= e(t('util.meters')) ?></h2>
  <table class="table">
    <thead><tr>
      <th><?= e(t('util.utility')) ?></th><th>Location</th><th>Serial</th>
      <th>IoT</th><th class="num"><?= e(t('util.reading')) ?></th><th><?= e(t('common.date')) ?></th>
    </tr></thead>
    <tbody>
      <?php foreach ($meters as $m): ?>
      <tr>
        <td><?= e(ucfirst(str_replace('_', ' ', $m['utility']))) ?></td>
        <td><?= e($m['location'] ?? '—') ?></td>
        <td class="mono"><?= e($m['serial']) ?></td>
        <td><?= $m['is_iot'] ? '<span class="badge badge-green">IoT</span>' : '<span class="muted">manual</span>' ?></td>
        <td class="num"><?= $m['last_value'] !== null ? number_format((float) $m['last_value']) : '—' ?></td>
        <td><?= e(fmt_date($m['last_read'])) ?></td>
      </tr>
      <?php endforeach; ?>
      <?php if (!$meters): ?><tr><td colspan="6" class="empty"><?= e(t('common.none')) ?></td></tr><?php endif; ?>
    </tbody>
  </table>
</section>

<section class="panel">
  <h2><?= e(t('util.bills')) ?></h2>
  <table class="table">
    <thead><tr>
      <th><?= e(t('prop.building')) ?></th><th><?= e(t('util.utility')) ?></th>
      <th>Period</th><th class="num"><?= e(t('common.amount')) ?></th>
      <th><?= e(t('util.split')) ?></th><th><?= e(t('common.status')) ?></th>
    </tr></thead>
    <tbody>
      <?php foreach ($bills as $b): ?>
      <tr>
        <td><?= e($b['building_name']) ?></td>
        <td><?= e(ucfirst(str_replace('_', ' ', $b['utility']))) ?></td>
        <td><?= e(fmt_date($b['period_start'])) ?> → <?= e(fmt_date($b['period_end'])) ?></td>
        <td class="num"><?= e(fmt_xaf($b['total_xaf'])) ?></td>
        <td><?= e($b['split_method'] === 'sqm' ? 'm² share' : 'occupancy') ?></td>
        <td><span class="badge badge-blue"><?= e($b['status']) ?></span></td>
      </tr>
      <?php endforeach; ?>
      <?php if (!$bills): ?><tr><td colspan="6" class="empty"><?= e(t('common.none')) ?></td></tr><?php endif; ?>
    </tbody>
  </table>
</section>

<section class="panel">
  <h2>Allocations</h2>
  <table class="table">
    <thead><tr>
      <th><?= e(t('prop.building')) ?></th><th><?= e(t('lease.unit')) ?></th>
      <th><?= e(t('util.utility')) ?></th><th class="num">Share</th><th><?= e(t('common.notes')) ?></th>
    </tr></thead>
    <tbody>
      <?php foreach ($allocations as $a): ?>
      <tr>
        <td><?= e($a['building_name']) ?></td>
        <td><?= e($a['unit_code']) ?></td>
        <td><?= e(ucfirst(str_replace('_', ' ', $a['utility']))) ?></td>
        <td class="num"><?= e(fmt_xaf($a['share_xaf'])) ?></td>
        <td class="muted"><?= e($a['detail'] ?? '') ?></td>
      </tr>
      <?php endforeach; ?>
      <?php if (!$allocations): ?><tr><td colspan="5" class="empty"><?= e(t('common.none')) ?></td></tr><?php endif; ?>
    </tbody>
  </table>
</section>
