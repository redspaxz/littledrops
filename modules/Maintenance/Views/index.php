<?php /** Maintenance → Views/index.php — tickets with severity routing + dispatch */ ?>

<section class="panel">
  <div class="panel-head">
    <h2><?= e(t('maint.tickets')) ?></h2>
    <details class="dropdown">
      <summary class="btn btn-primary">+ <?= e(t('maint.add_ticket')) ?></summary>
      <form method="post" action="<?= e(url('maintenance')) ?>" class="form-grid wide panel-body">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <label class="field field-span"><span>Title</span><input name="title" required placeholder="e.g. Water leak in kitchen"></label>
        <label class="field"><span><?= e(t('maint.severity')) ?></span>
          <select name="severity">
            <option value="urgent">Urgent</option>
            <option value="high" selected>High</option>
            <option value="low">Low</option>
          </select>
        </label>
        <label class="field"><span><?= e(t('prop.building')) ?></span>
          <select name="building_id">
            <option value="">—</option>
            <?php foreach ($buildings as $b): ?><option value="<?= (int) $b['id'] ?>"><?= e($b['name']) ?></option><?php endforeach; ?>
          </select>
        </label>
        <label class="field"><span><?= e(t('lease.unit')) ?> (if unit-specific)</span>
          <select name="unit_id">
            <option value="">—</option>
            <?php foreach ($units as $u): ?><option value="<?= (int) $u['id'] ?>"><?= e($u['building_name']) ?> · <?= e($u['code']) ?></option><?php endforeach; ?>
          </select>
        </label>
        <label class="field"><span><?= e(t('lease.tenant')) ?> (optional)</span>
          <select name="tenant_id">
            <option value="">—</option>
            <?php foreach ($tenants as $tn): ?><option value="<?= (int) $tn['id'] ?>"><?= e($tn['name']) ?></option><?php endforeach; ?>
          </select>
        </label>
        <label class="field field-span"><span>Description</span><textarea name="description" rows="3"></textarea></label>
        <div class="form-actions"><button class="btn btn-primary" type="submit"><?= e(t('common.save')) ?></button></div>
      </form>
    </details>
  </div>

  <form method="get" action="<?= e(url('maintenance')) ?>" class="filters">
    <input type="hidden" name="r" value="maintenance">
    <select name="severity" onchange="this.form.submit()">
      <option value=""><?= e(t('maint.severity')) ?>: all</option>
      <?php foreach (['urgent', 'high', 'low'] as $s): ?>
        <option value="<?= e($s) ?>" <?= $severity === $s ? 'selected' : '' ?>><?= e($s) ?></option>
      <?php endforeach; ?>
    </select>
  </form>

  <table class="table">
    <thead><tr>
      <th>Ticket</th>
      <th>Where</th>
      <th><?= e(t('maint.severity')) ?></th>
      <th><?= e(t('common.status')) ?></th>
      <th><?= e(t('maint.assigned_to')) ?></th>
      <th><?= e(t('common.actions')) ?></th>
    </tr></thead>
    <tbody>
      <?php foreach ($tickets as $t):
        $canDispatch = in_array(current_user()['role'], ['admin', 'property_manager'], true);
        $open = !in_array($t['status'], ['resolved', 'closed'], true);
      ?>
      <tr>
        <td><strong><?= e($t['title']) ?></strong>
          <div class="muted"><?= e(date('d M', strtotime($t['created_at']))) ?><?= $t['tenant_name'] ? ' · ' . e($t['tenant_name']) : '' ?></div></td>
        <td><?= e($t['unit_code'] ? $t['unit_code'] . ' · ' . $t['building_name'] : ($t['building_name'] ?? '—')) ?></td>
        <td><span class="<?= e(badge($t['severity'])) ?>"><?= e($t['severity']) ?></span></td>
        <td><span class="<?= e(badge($t['status'])) ?>"><?= e(str_replace('_', ' ', $t['status'])) ?></span></td>
        <td><?= e($t['assignee_name'] ?? '—') ?>
          <?= $t['assignee_kind'] ? '<div class="muted">' . e($t['assignee_kind']) . '</div>' : '' ?></td>
        <td>
          <?php if ($open && $canDispatch && !$t['assignee_name']): ?>
          <details class="dropdown">
            <summary class="btn btn-sm btn-primary"><?= e(t('maint.assign')) ?></summary>
            <form method="post" action="<?= e(url('maintenance/' . $t['id'] . '/assign')) ?>" class="form-grid panel-body">
              <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
              <label class="field"><span>Assign to</span>
                <select name="assignee_kind" onchange="this.form.querySelectorAll('[data-kind]').forEach(n => n.hidden = n.dataset.kind !== this.value)">
                  <option value="internal">Internal technician</option>
                  <option value="vendor">Vendor</option>
                </select>
              </label>
              <label class="field" data-kind="internal"><span>Technician</span>
                <select name="user_id">
                  <?php foreach ($technicians as $tech): ?><option value="<?= (int) $tech['id'] ?>"><?= e($tech['full_name']) ?></option><?php endforeach; ?>
                </select>
              </label>
              <label class="field" data-kind="vendor" hidden><span>Vendor</span>
                <select name="vendor_id">
                  <?php foreach ($vendors as $v): ?><option value="<?= (int) $v['id'] ?>"><?= e($v['name']) ?> (<?= e($v['service_type']) ?>)</option><?php endforeach; ?>
                </select>
              </label>
              <label class="field"><span><?= e(t('maint.sla')) ?></span><input type="number" name="sla_hours" min="1" value="48"></label>
              <div class="form-actions"><button class="btn btn-primary" type="submit"><?= e(t('maint.assign')) ?></button></div>
            </form>
          </details>
          <?php elseif ($open): ?>
          <form method="post" action="<?= e(url('maintenance/' . $t['id'] . '/status')) ?>" class="inline-form">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="status" value="<?= $t['status'] === 'assigned' ? 'in_progress' : 'resolved' ?>">
            <button class="btn btn-sm" type="submit">
              <?= $t['status'] === 'assigned' ? '▶ Start work' : '✓ Mark resolved' ?>
            </button>
          </form>
          <?php else: ?>—<?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (!$tickets): ?><tr><td colspan="6" class="empty"><?= e(t('common.none')) ?></td></tr><?php endif; ?>
    </tbody>
  </table>
</section>

<section class="panel">
  <h2>Preventative maintenance (PM) schedule</h2>
  <table class="table">
    <thead><tr><th>Task</th><th><?= e(t('prop.building')) ?></th><th>Frequency</th><th>Last done</th><th>Next due</th></tr></thead>
    <tbody>
      <?php $pms = \Core\Database::rows(
        'SELECT pm.*, b.name AS building_name, v.name AS vendor_name FROM pm_schedules pm
           LEFT JOIN buildings b ON b.id = pm.building_id
           LEFT JOIN vendors v ON v.id = pm.vendor_id
          WHERE pm.active = 1 ORDER BY pm.next_due'
      ); ?>
      <?php foreach ($pms as $pm): ?>
      <tr class="<?= strtotime($pm['next_due']) < strtotime('+7 days') ? 'row-danger' : '' ?>">
        <td><strong><?= e($pm['title']) ?></strong><?= $pm['vendor_name'] ? '<div class="muted">' . e($pm['vendor_name']) . '</div>' : '' ?></td>
        <td><?= e($pm['building_name'] ?? '—') ?></td>
        <td><?= e($pm['frequency']) ?></td>
        <td><?= e(fmt_date($pm['last_done'])) ?></td>
        <td><?= e(fmt_date($pm['next_due'])) ?></td>
      </tr>
      <?php endforeach; ?>
      <?php if (!$pms): ?><tr><td colspan="5" class="empty"><?= e(t('common.none')) ?></td></tr><?php endif; ?>
    </tbody>
  </table>
</section>
