<?php /** Billing → Views/index.php — invoices, record-payment forms, late-fee runner */ ?>

<section class="panel">
  <div class="panel-head">
    <h2><?= e(t('bill.invoices')) ?></h2>
    <div class="btn-row">
      <form method="post" action="<?= e(url('billing/late-fees')) ?>">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <button class="btn" type="submit">⚡ <?= e(t('bill.run_late_fees')) ?></button>
      </form>
      <details class="dropdown">
        <summary class="btn btn-primary">+ <?= e(t('bill.add_invoice')) ?></summary>
        <form method="post" action="<?= e(url('billing/invoices')) ?>" class="form-grid wide panel-body">
          <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
          <label class="field"><span><?= e(t('lease.code')) ?></span>
            <select name="lease_id">
              <option value="">—</option>
              <?php foreach ($leases as $l): ?>
                <option value="<?= (int) $l['id'] ?>"><?= e($l['code']) ?> — <?= e($l['tenant_name']) ?>
                  (<?= e(fmt_xaf($l['rent_xaf'])) ?>)</option>
              <?php endforeach; ?>
            </select>
          </label>
          <label class="field"><span><?= e(t('lease.tenant')) ?> (if no lease)</span>
            <select name="tenant_id">
              <option value="">—</option>
              <?php foreach ($tenants as $tn): ?>
                <option value="<?= (int) $tn['id'] ?>"><?= e($tn['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </label>
          <label class="field"><span><?= e(t('bill.kind')) ?></span>
            <select name="kind">
              <option value="rent">Rent</option>
              <option value="utility">Utility</option>
              <option value="late_fee">Late fee</option>
              <option value="amenity">Amenity</option>
              <option value="other">Other</option>
            </select>
          </label>
          <label class="field"><span><?= e(t('common.amount')) ?> (FCFA)</span>
            <input type="number" name="amount_xaf" min="1" required></label>
          <label class="field"><span><?= e(t('bill.issue_date')) ?></span>
            <input type="date" name="issue_date" value="<?= e(date('Y-m-d')) ?>"></label>
          <label class="field"><span><?= e(t('bill.due_date')) ?></span>
            <input type="date" name="due_date" value="<?= e(date('Y-m-d', strtotime('+5 days'))) ?>"></label>
          <label class="field field-span"><span>Description</span><input name="description"></label>
          <div class="form-actions"><button class="btn btn-primary" type="submit"><?= e(t('common.save')) ?></button></div>
        </form>
      </details>
    </div>
  </div>

  <form method="get" action="<?= e(url('billing')) ?>" class="filters">
    <input type="hidden" name="r" value="billing">
    <select name="status" onchange="this.form.submit()">
      <option value=""><?= e(t('common.status')) ?>: all</option>
      <?php foreach (['unpaid', 'partial', 'paid', 'overdue'] as $s): ?>
        <option value="<?= e($s) ?>" <?= $status === $s ? 'selected' : '' ?>>
          <?= e($s === 'overdue' ? t('bill.overdue') : $s) ?></option>
      <?php endforeach; ?>
    </select>
  </form>

  <table class="table">
    <thead><tr>
      <th><?= e(t('bill.number')) ?></th>
      <th><?= e(t('lease.tenant')) ?></th>
      <th><?= e(t('bill.kind')) ?></th>
      <th><?= e(t('bill.issue_date')) ?></th>
      <th><?= e(t('bill.due_date')) ?></th>
      <th class="num"><?= e(t('common.amount')) ?></th>
      <th class="num"><?= e(t('bill.balance')) ?></th>
      <th><?= e(t('common.status')) ?></th>
      <th><?= e(t('common.actions')) ?></th>
    </tr></thead>
    <tbody>
      <?php foreach ($invoices as $inv):
        $balance = (int) $inv['amount_xaf'] - (int) $inv['paid_xaf'];
        $overdue = $balance > 0 && strtotime($inv['due_date']) < time();
      ?>
      <tr class="<?= $overdue ? 'row-danger' : '' ?>">
        <td><strong><?= e($inv['number']) ?></strong><?= $inv['lease_code'] ? '<div class="muted">' . e($inv['lease_code']) . '</div>' : '' ?></td>
        <td><?= e($inv['tenant_name']) ?></td>
        <td><?= e(str_replace('_', ' ', $inv['kind'])) ?></td>
        <td><?= e(fmt_date($inv['issue_date'])) ?></td>
        <td><?= e(fmt_date($inv['due_date'])) ?><?= $overdue ? ' <span class="badge badge-red">' . e(t('bill.overdue')) . '</span>' : '' ?></td>
        <td class="num"><?= e(fmt_xaf($inv['amount_xaf'])) ?></td>
        <td class="num"><?= e(fmt_xaf($balance)) ?></td>
        <td><span class="<?= e(badge($inv['status'])) ?>"><?= e($inv['status']) ?></span></td>
        <td>
          <?php if ($balance > 0 && in_array(current_user()['role'], ['admin', 'accountant'], true)): ?>
          <details class="dropdown">
            <summary class="btn btn-sm btn-primary"><?= e(t('bill.record_payment')) ?></summary>
            <form method="post" action="<?= e(url('billing/payments')) ?>" class="form-grid panel-body">
              <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
              <input type="hidden" name="invoice_id" value="<?= (int) $inv['id'] ?>">
              <label class="field"><span><?= e(t('common.amount')) ?> (FCFA)</span>
                <input type="number" name="amount_xaf" min="1" max="<?= $balance ?>" value="<?= $balance ?>" required></label>
              <label class="field"><span><?= e(t('bill.channel')) ?></span>
                <select name="channel">
                  <option value="mtn_momo">MTN MoMo</option>
                  <option value="orange_money">Orange Money</option>
                  <option value="bank_transfer">Bank transfer</option>
                  <option value="card">Card</option>
                  <option value="cash">Cash</option>
                  <option value="cheque">Cheque</option>
                </select>
              </label>
              <label class="field field-span"><span><?= e(t('bill.reference')) ?></span>
                <input name="reference" placeholder="MoMo / OM transaction ID"></label>
              <div class="form-actions"><button class="btn btn-primary" type="submit"><?= e(t('common.save')) ?></button></div>
            </form>
          </details>
          <?php else: ?>—<?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (!$invoices): ?><tr><td colspan="9" class="empty"><?= e(t('common.none')) ?></td></tr><?php endif; ?>
    </tbody>
  </table>
</section>
