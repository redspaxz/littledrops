<?php /** Properties → Views/units.php — unit directory with status filter + add unit */ ?>

<section class="panel">
  <div class="panel-head">
    <h2><?= e(t('prop.units')) ?></h2>
    <details class="dropdown">
      <summary class="btn btn-primary">+ <?= e(t('prop.add_unit')) ?></summary>
      <form method="post" action="<?= e(url('units')) ?>" class="form-grid panel-body">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <label class="field"><span><?= e(t('prop.building')) ?></span>
          <select name="building_id" required>
            <?php foreach ($buildings as $b): ?>
              <option value="<?= (int) $b['id'] ?>"><?= e($b['name']) ?> (<?= e($b['quarter']) ?>)</option>
            <?php endforeach; ?>
          </select>
        </label>
        <label class="field"><span><?= e(t('prop.unit_code')) ?></span>
          <input name="code" required placeholder="e.g. 104 / B3"></label>
        <label class="field"><span><?= e(t('prop.unit_type')) ?></span>
          <select name="unit_type">
            <option value="apartment">Apartment</option>
            <option value="duplex">Duplex</option>
            <option value="studio">Studio</option>
            <option value="self_contain">Self-contain</option>
            <option value="shop">Shop</option>
            <option value="office">Office</option>
            <option value="store">Store</option>
          </select>
        </label>
        <label class="field"><span><?= e(t('prop.sqm')) ?></span><input name="sqm" type="number" step="0.01" min="0"></label>
        <label class="field"><span>Bedrooms</span><input name="bedrooms" type="number" min="0"></label>
        <label class="field"><span>Bathrooms</span><input name="bathrooms" type="number" min="0"></label>
        <label class="field"><span><?= e(t('prop.market_rent')) ?> (FCFA)</span>
          <input name="market_rent_xaf" type="number" min="0" required></label>
        <label class="field"><span><?= e(t('prop.unit_status')) ?></span>
          <select name="status">
            <?php foreach (\Modules\Properties\Models\Unit::STATUSES as $s): ?>
              <option value="<?= e($s) ?>"><?= e(str_replace('_', ' ', $s)) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <div class="form-actions"><button class="btn btn-primary" type="submit"><?= e(t('common.save')) ?></button></div>
      </form>
    </details>
  </div>

  <form method="get" action="<?= e(url('units')) ?>" class="filters">
    <input type="hidden" name="r" value="units">
    <input name="q" value="<?= e($q) ?>" placeholder="<?= e(t('common.search')) ?>…" class="search">
    <select name="status" onchange="this.form.submit()">
      <option value=""><?= e(t('common.status')) ?>: <?= e(t('common.search')) ?></option>
      <?php foreach (\Modules\Properties\Models\Unit::STATUSES as $s): ?>
        <option value="<?= e($s) ?>" <?= $status === $s ? 'selected' : '' ?>><?= e(str_replace('_', ' ', $s)) ?></option>
      <?php endforeach; ?>
    </select>
    <button class="btn" type="submit"><?= e(t('common.search')) ?></button>
    <a class="btn btn-ghost" href="<?= e(url('units')) ?>"><?= e(t('common.cancel')) ?></a>
  </form>

  <table class="table">
    <thead><tr>
      <th><?= e(t('prop.unit_code')) ?></th>
      <th><?= e(t('prop.building')) ?></th>
      <th><?= e(t('prop.unit_type')) ?></th>
      <th class="num"><?= e(t('prop.market_rent')) ?></th>
      <th><?= e(t('common.status')) ?></th>
      <th><?= e(t('lease.tenant')) ?></th>
    </tr></thead>
    <tbody>
      <?php foreach ($units as $u): ?>
      <tr>
        <td><strong><?= e($u['code']) ?></strong></td>
        <td><?= e($u['building_name']) ?> <span class="muted">(<?= e($u['quarter']) ?>)</span></td>
        <td><?= e(str_replace('_', ' ', $u['unit_type'])) ?></td>
        <td class="num"><?= e(fmt_xaf($u['market_rent_xaf'])) ?></td>
        <td><span class="<?= e(badge($u['status'])) ?>"><?= e(str_replace('_', ' ', $u['status'])) ?></span></td>
        <td><?= $u['lease_id']
            ? '<a href="' . e(url('leases/' . $u['lease_id'])) . '">' . e($u['lease_code']) . '</a> — ' . e($u['tenant_name'])
            : '—' ?></td>
      </tr>
      <?php endforeach; ?>
      <?php if (!$units): ?><tr><td colspan="6" class="empty"><?= e(t('common.none')) ?></td></tr><?php endif; ?>
    </tbody>
  </table>
</section>
