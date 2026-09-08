<?php /** Tenants → Views/index.php */ ?>

<section class="panel">
  <div class="panel-head">
    <h2><?= e(t('tenant.title')) ?></h2>
    <details class="dropdown">
      <summary class="btn btn-primary">+ <?= e(t('tenant.add')) ?></summary>
      <form method="post" action="<?= e(url('tenants')) ?>" class="form-grid panel-body">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <label class="field"><span><?= e(t('tenant.kind')) ?></span>
          <select name="kind">
            <option value="individual"><?= e(t('tenant.individual')) ?></option>
            <option value="corporate"><?= e(t('tenant.corporate')) ?></option>
          </select>
        </label>
        <label class="field"><span><?= e(t('common.name')) ?></span><input name="name" required></label>
        <label class="field"><span><?= e(t('common.phone')) ?></span><input name="phone" placeholder="+237 6XX XXX XXX"></label>
        <label class="field"><span><?= e(t('common.email')) ?></span><input name="email" type="email"></label>
        <label class="field"><span><?= e(t('tenant.id_doc')) ?></span>
          <select name="id_type">
            <option value="">—</option>
            <option value="national_id">National ID</option>
            <option value="passport">Passport</option>
            <option value="certificate_incorporation">Certificate of incorporation</option>
            <option value="residence_permit">Residence permit</option>
          </select>
        </label>
        <label class="field"><span>ID number</span><input name="id_number"></label>
        <label class="field"><span><?= e(t('tenant.emergency')) ?></span><input name="emergency_name" placeholder="Name"></label>
        <label class="field"><span>&nbsp;</span><input name="emergency_phone" placeholder="+237 …"></label>
        <div class="form-actions"><button class="btn btn-primary" type="submit"><?= e(t('common.save')) ?></button></div>
      </form>
    </details>
  </div>

  <table class="table">
    <thead><tr>
      <th><?= e(t('common.name')) ?></th>
      <th><?= e(t('tenant.kind')) ?></th>
      <th><?= e(t('common.phone')) ?></th>
      <th><?= e(t('tenant.id_doc')) ?></th>
      <th class="num"><?= e(t('tenant.leases')) ?></th>
      <th></th>
    </tr></thead>
    <tbody>
      <?php foreach ($tenants as $tn): ?>
      <tr>
        <td><a href="<?= e(url('tenants/' . $tn['id'])) ?>"><strong><?= e($tn['name']) ?></strong></a>
          <?php if ($tn['email']): ?><div class="muted"><?= e($tn['email']) ?></div><?php endif; ?></td>
        <td><?= e($tn['kind'] === 'corporate' ? t('tenant.corporate') : t('tenant.individual')) ?></td>
        <td><?= e($tn['phone'] ?? '—') ?></td>
        <td><?= e($tn['id_type'] ? str_replace('_', ' ', $tn['id_type']) : '—') ?>
          <?php if ($tn['id_number']): ?><div class="muted"><?= e($tn['id_number']) ?></div><?php endif; ?></td>
        <td class="num"><?= (int) $tn['active_leases'] ?></td>
        <td><a class="btn btn-sm" href="<?= e(url('tenants/' . $tn['id'])) ?>"><?= e(t('common.details')) ?></a></td>
      </tr>
      <?php endforeach; ?>
      <?php if (!$tenants): ?><tr><td colspan="6" class="empty"><?= e(t('common.none')) ?></td></tr><?php endif; ?>
    </tbody>
  </table>
</section>
