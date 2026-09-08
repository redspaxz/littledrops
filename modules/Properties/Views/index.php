<?php /** Properties → Views/index.php — buildings roll-up + add building form */ ?>

<section class="panel">
  <div class="panel-head">
    <h2><?= e(t('prop.title')) ?></h2>
    <details class="dropdown">
      <summary class="btn btn-primary">+ <?= e(t('prop.add_building')) ?></summary>
      <form method="post" action="<?= e(url('properties')) ?>" class="form-grid panel-body">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <label class="field"><span><?= e(t('prop.portfolio')) ?></span>
          <select name="portfolio_id" required>
            <?php foreach ($portfolios as $p): ?>
              <option value="<?= (int) $p['id'] ?>"><?= e($p['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label class="field"><span><?= e(t('prop.building')) ?></span>
          <input name="name" required placeholder="e.g. Nkwen Duplex Court"></label>
        <label class="field"><span><?= e(t('prop.quarter')) ?></span>
          <input name="quarter" required placeholder="Nkwen / Mankon / Old Town…"></label>
        <label class="field"><span><?= e(t('prop.city')) ?></span>
          <input name="city" value="Bamenda"></label>
        <label class="field"><span>Address</span>
          <input name="address" placeholder="Street / landmark"></label>
        <div class="form-actions">
          <button class="btn btn-primary" type="submit"><?= e(t('common.save')) ?></button>
        </div>
      </form>
    </details>
  </div>

  <table class="table">
    <thead><tr>
      <th><?= e(t('prop.building')) ?></th>
      <th><?= e(t('prop.quarter')) ?></th>
      <th><?= e(t('prop.portfolio')) ?></th>
      <th class="num"><?= e(t('prop.units')) ?></th>
      <th class="num"><?= e(t('prop.occupancy')) ?></th>
      <th class="num"><?= e(t('dash.rent_roll')) ?></th>
      <th></th>
    </tr></thead>
    <tbody>
      <?php foreach ($buildings as $b): $uc = (int) $b['unit_count']; $lc = (int) $b['leased_count']; ?>
      <tr>
        <td><a href="<?= e(url('properties/' . $b['id'])) ?>"><strong><?= e($b['name']) ?></strong></a>
          <div class="muted"><?= e($b['address'] ?? '') ?></div></td>
        <td><?= e($b['quarter']) ?> · <?= e($b['city']) ?></td>
        <td><?= e($b['portfolio_name']) ?></td>
        <td class="num"><?= $uc ?></td>
        <td class="num"><?= $uc ? round($lc / $uc * 100) : 0 ?>%</td>
        <td class="num"><?= e(fmt_xaf($b['leased_rent_xaf'])) ?></td>
        <td><a class="btn btn-sm" href="<?= e(url('properties/' . $b['id'])) ?>"><?= e(t('common.details')) ?></a></td>
      </tr>
      <?php endforeach; ?>
      <?php if (!$buildings): ?><tr><td colspan="7" class="empty"><?= e(t('common.none')) ?></td></tr><?php endif; ?>
    </tbody>
  </table>
</section>
