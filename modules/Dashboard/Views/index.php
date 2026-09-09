<?php
/** Dashboard → Views/index.php — KPI cards, charts (charts.js), recovery, payments */

use Modules\Dashboard\Models\Stats;

$statusColors = [
    'leased'            => '#0d9488',
    'vacant'            => '#1d4f91',
    'under_maintenance' => '#b97f14',
    'reserved'          => '#5c6b77',
    'out_of_service'    => '#b3261e',
];
$donutData = [];
foreach ($statusColors as $s => $color) {
    if (!empty($status[$s])) {
        $donutData[] = ['label' => t('status.' . $s), 'value' => (int) $status[$s], 'color' => $color];
    }
}

$channelColors = [
    'mtn_momo'      => '#f2b203',
    'orange_money'  => '#ff7900',
    'bank_transfer' => '#1d4f91',
    'card'          => '#5c6b77',
    'cash'          => '#0d9488',
    'cheque'        => '#8a9aa6',
];
$channelData = [];
foreach ($channels as $c) {
    $channelData[] = [
        'label' => t('channel.' . $c['channel']),
        'value' => (int) $c['total'],
        'color' => $channelColors[$c['channel']] ?? '#8a9aa6',
        'title' => t('channel.' . $c['channel']) . ': ' . fmt_xaf($c['total']) . ' · ' . (int) $c['n'] . ' ' . t('dash.tx'),
    ];
}

$agingColors = ['#0d9488', '#1d4f91', '#b97f14', '#b3261e'];
$agingLabels = ['dash.aging_current', 'dash.aging_1_30', 'dash.aging_31_60', 'dash.aging_60_plus'];
$agingKeys   = ['current', 'd1_30', 'd31_60', 'd60_plus'];
$agingData   = [];
foreach ($agingKeys as $i => $key) {
    $agingData[] = [
        'label' => t($agingLabels[$i]),
        'value' => (int) $aging[$key],
        'color' => $agingColors[$i],
        'title' => t($agingLabels[$i]) . ': ' . fmt_xaf($aging[$key]),
    ];
}

$lineCfg = [
    'labels' => array_column($collections, 'label'),
    'series' => [
        ['name' => t('dash.series_billed'),    'color' => '#8a9aa6', 'dashed' => true, 'values' => array_column($collections, 'billed')],
        ['name' => t('dash.series_collected'), 'color' => '#0d9488', 'area' => true,  'values' => array_column($collections, 'collected')],
    ],
];
$donutCfg = [
    'data' => $donutData,
    'center' => ['value' => number_format($occupancy, 1) . '%', 'label' => t('dash.occupancy')],
];
?>

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

<section class="panel">
  <div class="panel-head"><h2><?= e(t('dash.chart_collections')) ?></h2></div>
  <div class="chart" data-chart="line" data-chart-data="<?= e(json_encode($lineCfg, JSON_UNESCAPED_UNICODE)) ?>"></div>
</section>

<div class="chart-grid">
  <section class="panel">
    <div class="panel-head"><h2><?= e(t('dash.chart_occupancy')) ?></h2></div>
    <div class="chart" data-chart="donut" data-chart-data="<?= e(json_encode($donutCfg, JSON_UNESCAPED_UNICODE)) ?>"></div>
  </section>

  <section class="panel">
    <div class="panel-head"><h2><?= e(t('dash.chart_channels')) ?></h2></div>
    <div class="chart" data-chart="bars" data-chart-data="<?= e(json_encode(['data' => $channelData], JSON_UNESCAPED_UNICODE)) ?>"></div>
    <?php if (!$channelData): ?><p class="empty"><?= e(t('common.none')) ?></p><?php endif; ?>
  </section>

  <section class="panel">
    <div class="panel-head"><h2><?= e(t('dash.chart_aging')) ?></h2></div>
    <div class="chart" data-chart="bars" data-chart-data="<?= e(json_encode(['data' => $agingData], JSON_UNESCAPED_UNICODE)) ?>"></div>
  </section>
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

<section class="panel">
  <h2><?= e(t('dash.recent_payments')) ?></h2>
  <table class="table">
    <thead><tr>
      <th><?= e(t('common.date')) ?></th><th><?= e(t('tenant.name')) ?></th>
      <th><?= e(t('bill.channel')) ?></th><th><?= e(t('bill.reference')) ?></th>
      <th class="num"><?= e(t('common.amount')) ?></th>
    </tr></thead>
    <tbody>
      <?php foreach ($payments as $p): ?>
      <tr>
        <td><?= e(date('d M H:i', strtotime($p['paid_at']))) ?></td>
        <td><?= e($p['tenant_name'] ?? '—') ?></td>
        <td><span class="pill pill-<?= e($p['channel']) ?>"><?= e(t('channel.' . $p['channel'])) ?></span></td>
        <td class="mono"><?= e($p['reference'] ?? '—') ?></td>
        <td class="num"><?= e(fmt_xaf($p['amount_xaf'])) ?></td>
      </tr>
      <?php endforeach; ?>
      <?php if (!$payments): ?><tr><td colspan="5" class="empty"><?= e(t('common.none')) ?></td></tr><?php endif; ?>
    </tbody>
  </table>
</section>
