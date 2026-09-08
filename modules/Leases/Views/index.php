<?php /** Leases → Views/index.php — lease list + new lease form */ ?>

<section class="panel">
  <div class="panel-head">
    <h2><?= e(t('lease.title')) ?></h2>
    <details class="dropdown">
      <summary class="btn btn-primary">+ <?= e(t('lease.add')) ?></summary>
      <form method="post" action="<?= e(url('leases')) ?>" class="form-grid wide panel-body">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <label class="field"><span><?= e(t('lease.unit')) ?></span>
          <select name="unit_id" required>
            <option value="">—</option>
            <?php foreach ($units as $u): ?>
              <option value="<?= (int) $u['id'] ?>"><?= e($u['building_name']) ?> · <?= e($u['code']) ?>
                (<?= e(str_replace('_', ' ', $u['unit_type'])) ?>) — <?= e(fmt_xaf($u['market_rent_xaf'])) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label class="field"><span><?= e(t('lease.tenant')) ?></span>
          <select name="tenant_id" required>
            <option value="">—</option>
            <?php foreach ($tenants as $tn): ?>
              <option value="<?= (int) $tn['id'] ?>"><?= e($tn['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label class="field"><span><?= e(t('lease.start')) ?></span><input type="date" name="start_date" required></label>
        <label class="field"><span><?= e(t('lease.end')) ?></span><input type="date" name="end_date" required></label>
        <label class="field"><span><?= e(t('lease.rent')) ?> (FCFA)</span><input type="number" name="rent_xaf" min="0" required></label>
        <label class="field"><span><?= e(t('lease.cycle')) ?></span>
          <select name="billing_cycle">
            <option value="monthly">Monthly</option>
            <option value="quarterly">Quarterly</option>
            <option value="annually">Annually</option>
          </select>
        </label>
        <label class="field"><span><?= e(t('lease.deposit')) ?> (FCFA)</span><input type="number" name="deposit_xaf" min="0" value="0"></label>
        <label class="field"><span>Deposit received</span>
          <select name="deposit_paid"><option value="0">No</option><option value="1">Yes</option></select>
        </label>
        <label class="field"><span>Escalation % (dynamic rent)</span><input type="number" step="0.01" name="escalation_pct" placeholder="e.g. 10"></label>
        <label class="field"><span>Escalation every (months)</span><input type="number" name="escalation_interval_months" placeholder="12"></label>
        <label class="field"><span><?= e(t('lease.legal_system')) ?></span>
          <select name="legal_system">
            <option value="common_law">Common Law (North-West / South-West)</option>
            <option value="civil_law">Civil Law (Francophone regions)</option>
          </select>
        </label>
        <label class="field"><span><?= e(t('lease.jurisdiction')) ?></span>
          <input name="jurisdiction" value="<?= e(\Core\Config::get('legal.default_jurisdiction')) ?>"></label>
        <label class="field"><span><?= e(t('lease.notice_days')) ?></span>
          <input type="number" name="notice_days" min="1" value="<?= (int) \Core\Config::get('legal.default_notice_days', 30) ?>"></label>
        <label class="field"><span><?= e(t('lease.witnesses')) ?> 1</span><input name="witness_1" placeholder="Name"></label>
        <label class="field"><span><?= e(t('lease.witnesses')) ?> 2</span><input name="witness_2" placeholder="Name"></label>
        <label class="field field-span"><span><?= e(t('common.notes')) ?></span><input name="notes"></label>
        <div class="form-actions"><button class="btn btn-primary" type="submit"><?= e(t('common.save')) ?></button></div>
      </form>
    </details>
  </div>

  <table class="table">
    <thead><tr>
      <th><?= e(t('lease.code')) ?></th>
      <th><?= e(t('lease.tenant')) ?></th>
      <th><?= e(t('lease.unit')) ?></th>
      <th><?= e(t('lease.rent')) ?></th>
      <th><?= e(t('lease.start')) ?> → <?= e(t('lease.end')) ?></th>
      <th><?= e(t('lease.legal_system')) ?></th>
      <th><?= e(t('common.status')) ?></th>
    </tr></thead>
    <tbody>
      <?php foreach ($leases as $l): ?>
      <tr>
        <td><a href="<?= e(url('leases/' . $l['id'])) ?>"><strong><?= e($l['code']) ?></strong></a></td>
        <td><?= e($l['tenant_name']) ?></td>
        <td><?= e($l['unit_code']) ?> <span class="muted"><?= e($l['building_name']) ?></span></td>
        <td class="num"><?= e(fmt_xaf($l['rent_xaf'])) ?></td>
        <td><?= e(fmt_date($l['start_date'])) ?> → <?= e(fmt_date($l['end_date'])) ?></td>
        <td><span class="pill"><?= e($l['legal_system'] === 'common_law' ? 'Common Law' : 'Civil Law') ?></span></td>
        <td><span class="<?= e(badge($l['status'])) ?>"><?= e(str_replace('_', ' ', $l['status'])) ?></span></td>
      </tr>
      <?php endforeach; ?>
      <?php if (!$leases): ?><tr><td colspan="7" class="empty"><?= e(t('common.none')) ?></td></tr><?php endif; ?>
    </tbody>
  </table>
</section>
