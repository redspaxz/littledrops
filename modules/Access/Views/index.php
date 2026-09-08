<?php /** Access → Views/index.php — visitor gate passes + amenity bookings */ ?>

<section class="panel">
  <div class="panel-head">
    <h2><?= e(t('access.visitors')) ?></h2>
    <details class="dropdown">
      <summary class="btn btn-primary">+ Gate pass</summary>
      <form method="post" action="<?= e(url('access/visitors')) ?>" class="form-grid panel-body">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <label class="field"><span>Visitor name</span><input name="visitor_name" required></label>
        <label class="field"><span><?= e(t('common.phone')) ?></span><input name="visitor_phone" placeholder="+237 …"></label>
        <label class="field"><span><?= e(t('lease.unit')) ?></span>
          <select name="unit_id">
            <option value="">— building-wide —</option>
            <?php foreach ($units as $u): ?><option value="<?= (int) $u['id'] ?>"><?= e($u['building_name']) ?> · <?= e($u['code']) ?></option><?php endforeach; ?>
          </select>
        </label>
        <label class="field"><span>Valid on</span><input type="date" name="valid_on" value="<?= e(date('Y-m-d')) ?>"></label>
        <div class="form-actions"><button class="btn btn-primary" type="submit"><?= e(t('common.save')) ?></button></div>
      </form>
    </details>
  </div>

  <table class="table">
    <thead><tr>
      <th>Visitor</th><th><?= e(t('lease.unit')) ?></th><th>Pass token</th>
      <th>Valid on</th><th><?= e(t('common.status')) ?></th><th></th>
    </tr></thead>
    <tbody>
      <?php foreach ($visitors as $v): ?>
      <tr>
        <td><strong><?= e($v['visitor_name']) ?></strong>
          <?php if ($v['visitor_phone']): ?><div class="muted"><?= e($v['visitor_phone']) ?></div><?php endif; ?></td>
        <td><?= e($v['unit_code'] ? $v['unit_code'] . ' · ' . $v['building_name'] : ($v['building_name'] ?? '—')) ?></td>
        <td class="mono">…<?= e(substr($v['qr_token'], -8)) ?></td>
        <td><?= e(fmt_date($v['valid_on'])) ?></td>
        <td><span class="<?= e(badge($v['status'])) ?>"><?= e($v['status']) ?></span></td>
        <td>
          <?php if ($v['status'] === 'approved'): ?>
          <form method="post" action="<?= e(url('access/visitors/' . $v['id'] . '/use')) ?>" class="inline-form">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <button class="btn btn-sm" type="submit">✓ Check in</button>
          </form>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (!$visitors): ?><tr><td colspan="6" class="empty"><?= e(t('common.none')) ?></td></tr><?php endif; ?>
    </tbody>
  </table>
</section>

<div class="two-col">
  <section class="panel">
    <h2><?= e(t('access.amenities')) ?></h2>
    <table class="table">
      <thead><tr><th>Name</th><th><?= e(t('prop.building')) ?></th><th>Rate / hour</th><th class="num"><?= e(t('access.bookings')) ?></th></tr></thead>
      <tbody>
        <?php foreach ($amenities as $a): ?>
        <tr>
          <td><strong><?= e($a['name']) ?></strong><div class="muted"><?= e(str_replace('_', ' ', $a['kind'])) ?></div></td>
          <td><?= e($a['building_name']) ?></td>
          <td class="num"><?= e(fmt_xaf($a['hourly_rate_xaf'])) ?></td>
          <td class="num"><?= (int) $a['bookings'] ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$amenities): ?><tr><td colspan="4" class="empty"><?= e(t('common.none')) ?></td></tr><?php endif; ?>
      </tbody>
    </table>
  </section>

  <section class="panel">
    <h2><?= e(t('access.bookings')) ?></h2>
    <table class="table">
      <thead><tr><th>Amenity</th><th><?= e(t('lease.tenant')) ?></th><th>When</th><th class="num"><?= e(t('common.amount')) ?></th></tr></thead>
      <tbody>
        <?php foreach ($bookings as $bk): ?>
        <tr>
          <td><?= e($bk['amenity_name']) ?></td>
          <td><?= e($bk['tenant_name']) ?></td>
          <td><?= e(date('d M H:i', strtotime($bk['start_at']))) ?> → <?= e(date('H:i', strtotime($bk['end_at']))) ?></td>
          <td class="num"><?= e(fmt_xaf($bk['total_xaf'])) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$bookings): ?><tr><td colspan="4" class="empty"><?= e(t('common.none')) ?></td></tr><?php endif; ?>
      </tbody>
    </table>
  </section>
</div>
