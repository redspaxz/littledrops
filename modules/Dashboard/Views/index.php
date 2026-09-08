<?php /** Dashboard → Views/index.php — KPI cards, payments, recovery watchlist */ ?>

<div class="kpi-grid">
  <div class="kpi-card">
    <div class="kpi-label"><?= e(t('dash.occupancy')) ?></div>
    <div class="kpi-value"><?= number_format($occupancy, 1) ?>%</div>
    <div class="kpi-sub"><?= (int) ($status['leased'] ?? 0) ?> <?= e(t('dash.leased')) ?> ·
      <?= (int) ($status['vacant'] ?? 0) ?> <?= e(t('dash.vacant')) ?> ·
      <?= array_sum($status) ?> <?= e(t('dash.units')) ?></div>
  </div>
  <div class="kpi-card">
    <div class="kpi-label"><?= e(t('dash.rent_roll')) ?></div>
    <div class="kpi-value"><?= e(fmt_xaf($rentRoll)) ?></div>
    <div class="kpi-sub"><?= e(t('dash.units')) ?>: <?= array_sum($status) ?></div>
  </div>
  <div class="kpi-card">
    <div class="kpi-label"><?= e(t('dash.collected')) ?></div>
    <div class="kpi-value"><?= e(fmt_xaf($collected)) ?></div>
    <div class="kpi-sub"><?= e(t('dash.arrears')) ?>: <strong><?= e(fmt_xaf($arrears)) ?></strong></div>
  </div>
  <div class="kpi-card">
    <div class="kpi-label"><?= e(t('dash.open_tickets')) ?></div>
    <div class="kpi-value"><?php
      $open = 0;
      foreach ($tickets as $row) { $open += (int) $row['n']; }
      echo $open;
    ?></div>
    <div class="kpi-sub"><?php foreach ($tickets as $row): ?>
      <span class="<?= e(badge($row['severity'])) ?>"><?= e(t('maint.severity')) ?> <?= e($row['severity']) ?>: <?= (int) $row['n'] ?></span>
    <?php endforeach; ?></div>
  </div>
</div>

<?php if ($recovery): ?>
<section class="panel panel-danger">
  <h2><?= e(t('dash.recovery')) ?> — <?= e(t('lease.recovery')) ?></h2>
  <table class="table">
    <thead><tr>
      <th><?= e(t('lease.code')) ?></th><th><?= e(t('tenant.name')) ?></th>
      <th><?= e(t('common.status')) ?></th><th><?= e(t('common.actions')) ?></th>
    </tr></thead>
    <tbody>
      <?php foreach ($recovery as $rc): ?>
      <tr>
        <td><a href="<?= e(url('leases/' . $rc['lease_id'])) ?>"><?= e($rc['lease_code']) ?></a></td>
        <td><?= e($rc['tenant_name']) ?></td>
        <td><span class="<?= e(badge($rc['stage'])) ?>"><?= e(str_replace('_', ' ', $rc['stage'])) ?></span></td>
        <td><a class="btn btn-sm" href="<?= e(url('leases/' . $rc['lease_id'])) ?>"><?= e(t('common.details')) ?></a></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <p class="hint">Common Law — recovery of premises proceeds notice → court order; self-help eviction is never available.</p>
</section>
<?php endif; ?>

<div class="two-col">
  <section class="panel">
    <h2><?= e(t('dash.recent_payments')) ?></h2>
    <table class="table">
      <thead><tr>
        <th><?= e(t('common.date')) ?></th><th><?= e(t('tenant.name')) ?></th>
        <th><?= e(t('bill.channel')) ?></th><th class="num"><?= e(t('common.amount')) ?></th>
      </tr></thead>
      <tbody>
        <?php foreach ($payments as $p): ?>
        <tr>
          <td><?= e(date('d M H:i', strtotime($p['paid_at']))) ?></td>
          <td><?= e($p['tenant_name'] ?? '—') ?></td>
          <td><span class="pill pill-<?= e($p['channel']) ?>"><?= e(str_replace('_', ' ', $p['channel'])) ?></span></td>
          <td class="num"><?= e(fmt_xaf($p['amount_xaf'])) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$payments): ?><tr><td colspan="4" class="empty"><?= e(t('common.none')) ?></td></tr><?php endif; ?>
      </tbody>
    </table>
  </section>

  <section class="panel">
    <h2><?= e(t('dash.payment_channels')) ?></h2>
    <table class="table">
      <thead><tr><th><?= e(t('bill.channel')) ?></th><th class="num">Nº</th><th class="num"><?= e(t('common.amount')) ?></th></tr></thead>
      <tbody>
        <?php foreach ($channels as $c): ?>
        <tr>
          <td><span class="pill pill-<?= e($c['channel']) ?>"><?= e(str_replace('_', ' ', $c['channel'])) ?></span></td>
          <td class="num"><?= (int) $c['n'] ?></td>
          <td class="num"><?= e(fmt_xaf($c['total'])) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$channels): ?><tr><td colspan="3" class="empty"><?= e(t('common.none')) ?></td></tr><?php endif; ?>
      </tbody>
    </table>
  </section>
</div>
