<?php
/** Leases → Views/show.php — lease lifecycle, common-law panel, recovery pipeline */

use Modules\Leases\Models\RecoveryCase;

$nextStage = $recovery ? RecoveryCase::nextStage($recovery['stage']) : null;
$canManage = in_array(current_user()['role'], ['admin', 'property_manager'], true);
?>

<section class="panel">
  <div class="panel-head">
    <h2><?= e($lease['code']) ?>
      <span class="<?= e(badge($lease['status'])) ?>"><?= e(str_replace('_', ' ', $lease['status'])) ?></span>
    </h2>
    <div class="btn-row">
      <a class="btn" href="<?= e(url('leases')) ?>">← <?= e(t('common.back')) ?></a>
      <a class="btn" href="<?= e(url('leases/' . $lease['id'] . '/agreement')) ?>">📄 <?= e(t('lease.agreement_download')) ?></a>
      <?php if ($canManage && $lease['status'] === 'draft'): ?>
        <form method="post" action="<?= e(url('leases/' . $lease['id'] . '/activate')) ?>">
          <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
          <button class="btn btn-primary" type="submit">✓ <?= e(t('lease.activate')) ?></button>
        </form>
      <?php endif; ?>
      <?php if ($canManage && in_array($lease['status'], ['active', 'in_recovery'], true)): ?>
        <details class="dropdown">
          <summary class="btn btn-danger">⚠ <?= e(t('lease.notice_to_quit')) ?></summary>
          <form method="post" action="<?= e(url('leases/' . $lease['id'] . '/notice-to-quit')) ?>" class="panel-body form-grid">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <label class="field"><span>Notice period (days)</span>
              <input type="number" name="days" min="1" value="<?= (int) $lease['notice_to_quit_days'] ?>"></label>
            <label class="field field-span"><span>Ground / reason</span>
              <input name="reason" value="Persistent default in payment of rent"></label>
            <div class="form-actions">
              <button class="btn btn-danger" type="submit"><?= e(t('lease.notice_to_quit')) ?></button>
            </div>
            <p class="hint field-span">Serving notice opens a recovery case and generates the formal notice document.</p>
          </form>
        </details>
      <?php endif; ?>
    </div>
  </div>

  <div class="detail-grid">
    <div><span class="muted"><?= e(t('lease.tenant')) ?></span>
      <strong><a href="<?= e(url('tenants/' . $lease['tenant_id'])) ?>"><?= e($lease['tenant_name']) ?></a></strong></div>
    <div><span class="muted"><?= e(t('lease.unit')) ?></span>
      <strong><?= e($lease['unit_code']) ?> · <?= e($lease['building_name']) ?> (<?= e($lease['quarter']) ?>)</strong></div>
    <div><span class="muted"><?= e(t('lease.rent')) ?></span>
      <strong><?= e(fmt_xaf($lease['rent_xaf'])) ?> / <?= e($lease['billing_cycle']) ?></strong></div>
    <div><span class="muted"><?= e(t('lease.deposit')) ?></span>
      <strong><?= e(fmt_xaf($lease['deposit_xaf'])) ?> <?= $lease['deposit_paid'] ? '✓ received' : '— pending' ?></strong></div>
    <div><span class="muted">Term</span>
      <strong><?= e(fmt_date($lease['start_date'])) ?> → <?= e(fmt_date($lease['end_date'])) ?></strong></div>
    <div><span class="muted">Escalation</span>
      <strong><?= $lease['escalation_pct'] !== null
        ? '+' . rtrim(rtrim((string) $lease['escalation_pct'], '0'), '.') . '% every ' . (int) $lease['escalation_interval_months'] . ' mo'
        : '—' ?></strong></div>
  </div>

  <div class="legal-panel">
    <h3>⚖ <?= e(t('lease.legal_system')) ?> — <?= e($lease['legal_system'] === 'common_law' ? 'Common Law' : 'Civil Law') ?></h3>
    <p class="hint"><?= e(t('lease.common_law_note')) ?></p>
    <div class="detail-grid">
      <div><span class="muted"><?= e(t('lease.jurisdiction')) ?></span><strong><?= e($lease['jurisdiction']) ?></strong></div>
      <div><span class="muted"><?= e(t('lease.notice_days')) ?></span><strong><?= (int) $lease['notice_to_quit_days'] ?></strong></div>
      <div><span class="muted"><?= e(t('lease.witnesses')) ?></span>
        <strong><?= e(trim(($lease['witness_1'] ?? '—') . ' · ' . ($lease['witness_2'] ?? '—'))) ?></strong></div>
      <div><span class="muted">Stamp duty</span>
        <strong><?= $lease['stamp_duty_paid'] ? 'Paid' : 'Not yet paid' ?></strong></div>
    </div>
  </div>
</section>

<?php if ($recovery): ?>
<section class="panel panel-danger">
  <div class="panel-head">
    <h2><?= e(t('lease.recovery')) ?> — <span class="<?= e(badge($recovery['stage'])) ?>"><?= e(RecoveryCase::STAGES[$recovery['stage']] ?? $recovery['stage']) ?></span></h2>
    <?php if ($canManage && $nextStage): ?>
      <form method="post" action="<?= e(url('leases/' . $lease['id'] . '/recovery')) ?>">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="stage" value="<?= e($nextStage) ?>">
        <button class="btn btn-danger" type="submit" data-confirm="Advance recovery to &quot;<?= e(RecoveryCase::STAGES[$nextStage]) ?>&quot;?">
          → <?= e(RecoveryCase::STAGES[$nextStage]) ?>
        </button>
      </form>
    <?php endif; ?>
  </div>
  <ol class="pipeline">
    <?php $keys = array_keys(RecoveryCase::STAGES); $currentIdx = array_search($recovery['stage'], $keys, true); ?>
    <?php foreach ($keys as $i => $stageKey): ?>
      <li class="<?= $i < $currentIdx ? 'done' : ($i === $currentIdx ? 'current' : 'todo') ?>"><?= e(RecoveryCase::STAGES[$stageKey]) ?></li>
    <?php endforeach; ?>
  </ol>
  <?php if ($recovery['notes']): ?><p class="muted"><?= e($recovery['notes']) ?></p><?php endif; ?>
  <p class="hint">Under Common Law possession is recovered through the court; the system will not model self-help eviction.</p>
</section>
<?php endif; ?>

<div class="two-col">
  <section class="panel">
    <h2><?= e(t('lease.timeline')) ?></h2>
    <ul class="timeline">
      <?php foreach ($events as $ev): ?>
      <li>
        <div class="tl-when"><?= e(date('d M Y H:i', strtotime($ev['created_at']))) ?><?= $ev['by_name'] ? ' · ' . e($ev['by_name']) : '' ?></div>
        <div class="tl-type"><?= e(str_replace('_', ' ', $ev['event_type'])) ?></div>
        <?php if ($ev['detail']): ?><div class="muted"><?= e($ev['detail']) ?></div><?php endif; ?>
      </li>
      <?php endforeach; ?>
    </ul>
  </section>

  <div>
    <section class="panel">
      <h2><?= e(t('bill.invoices')) ?></h2>
      <table class="table">
        <thead><tr><th><?= e(t('bill.number')) ?></th><th><?= e(t('bill.kind')) ?></th>
          <th class="num"><?= e(t('common.amount')) ?></th><th><?= e(t('common.status')) ?></th></tr></thead>
        <tbody>
          <?php foreach ($invoices as $inv): ?>
          <tr>
            <td><?= e($inv['number']) ?></td>
            <td><?= e(str_replace('_', ' ', $inv['kind'])) ?></td>
            <td class="num"><?= e(fmt_xaf($inv['amount_xaf'])) ?></td>
            <td><span class="<?= e(badge($inv['status'])) ?>"><?= e($inv['status']) ?></span></td>
          </tr>
          <?php endforeach; ?>
          <?php if (!$invoices): ?><tr><td colspan="4" class="empty"><?= e(t('common.none')) ?></td></tr><?php endif; ?>
        </tbody>
      </table>
    </section>

    <section class="panel">
      <h2><?= e(t('lease.documents')) ?></h2>
      <table class="table">
        <thead><tr><th><?= e(t('common.date')) ?></th><th>Document</th></tr></thead>
        <tbody>
          <?php foreach ($documents as $doc): ?>
          <tr>
            <td><?= e(fmt_date($doc['created_at'])) ?></td>
            <td><?= e($doc['title']) ?> <span class="muted">(<?= e(str_replace('_', ' ', $doc['doc_kind'])) ?>)</span></td>
          </tr>
          <?php endforeach; ?>
          <?php if (!$documents): ?><tr><td colspan="2" class="empty">—</td></tr><?php endif; ?>
        </tbody>
      </table>
    </section>
  </div>
</div>

<?php if ($canManage && in_array($lease['status'], ['active', 'in_recovery', 'expired'], true)): ?>
<section class="panel">
  <details>
    <summary class="btn">Terminate lease (amicable move-out)</summary>
    <form method="post" action="<?= e(url('leases/' . $lease['id'] . '/terminate')) ?>" class="form-grid panel-body">
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
      <label class="field field-span"><span>Move-out note (inspection result, deposit refund…)</span>
        <input name="note" placeholder="e.g. Inspection clean; deposit refunded in full"></label>
      <div class="form-actions">
        <button class="btn btn-danger" type="submit" data-confirm="Terminate this lease and release the unit?">Terminate</button>
      </div>
    </form>
  </details>
</section>
<?php endif; ?>
