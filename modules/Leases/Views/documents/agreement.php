<?php
/**
 * Leases → Views/documents/agreement.php
 * Common Law tenancy agreement (Bamenda, North-West Region, Cameroon).
 * Rendered without layout; stored in lease_documents and offered as download.
 */
$leasedBy = 'LittleDrops Property Management (as agent for the Lessor)';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Tenancy Agreement — <?= e($lease['code']) ?></title>
<style>
  body { font-family: Georgia, 'Times New Roman', serif; margin: 3em auto; max-width: 46em; color: #111; line-height: 1.55; }
  h1 { font-size: 1.35em; text-align: center; text-transform: uppercase; letter-spacing: .08em; }
  .parties { border: 1px solid #999; padding: 1em; margin: 1.5em 0; }
  ol.clauses li { margin-bottom: .9em; }
  .sig { display: flex; gap: 3em; margin-top: 3em; }
  .sig div { flex: 1; border-top: 1px solid #111; padding-top: .4em; margin-top: 2.5em; }
  .meta { text-align: center; color: #444; font-size: .9em; }
  table { width: 100%; border-collapse: collapse; margin: 1em 0; }
  td, th { border: 1px solid #999; padding: .45em .6em; text-align: left; font-size: .95em; }
  .notice { background: #f5f2e8; border-left: 4px solid #8a7a3f; padding: .8em 1em; font-size: .92em; }
</style>
</head>
<body>

<h1>Tenancy Agreement</h1>
<p class="meta">
  Reference: <strong><?= e($lease['code']) ?></strong> ·
  Made this <?= e(date('jS F Y', strtotime($lease['signed_at'] ?? 'now'))) ?><br>
  <strong>Governed by <?= e($lease['legal_system'] === 'common_law' ? 'Common Law' : 'Civil Law') ?></strong>
  — <?= e($lease['jurisdiction']) ?>
</p>

<div class="parties">
  <strong>THIS AGREEMENT</strong> is made<br><br>
  BETWEEN <strong><?= e($leasedBy) ?></strong> (hereinafter “the Lessor”)<br>
  AND <strong><?= e($lease['tenant_name']) ?></strong>
  <?= $lease['tenant_kind'] === 'corporate' ? '(a company incorporated under the laws of the Republic of Cameroon)' : '' ?>
  (hereinafter “the Lessee”).
</div>

<p><strong>IN CONSIDERATION</strong> of the rents and covenants hereinafter reserved and contained, the Lessor agrees to let and the Lessee agrees to take:</p>

<table>
  <tr><th>Premises</th><td>Unit <?= e($lease['unit_code']) ?> (<?= e(str_replace('_', ' ', $lease['unit_type'])) ?><?= $lease['sqm'] ? ', ' . e(rtrim(rtrim((string) $lease['sqm'], '0'), '.')) . ' m²' : '' ?>), <?= e($lease['building_name']) ?>, <?= e($lease['building_address'] ?? $lease['quarter']) ?>, <?= e($lease['city']) ?></td></tr>
  <tr><th>Term</th><td>From <?= e(date('jS F Y', strtotime($lease['start_date']))) ?> to <?= e(date('jS F Y', strtotime($lease['end_date']))) ?></td></tr>
  <tr><th>Rent</th><td><?= e(fmt_xaf($lease['rent_xaf'])) ?> payable <?= e($lease['billing_cycle']) ?> in advance, on or before the 5th day of each period</td></tr>
  <tr><th>Security deposit</th><td><?= e(fmt_xaf($lease['deposit_xaf'])) ?> held against dilapidations beyond fair wear and tear, refundable within 30 days of handover</td></tr>
  <tr><th>Late payment</th><td>A penalty of <?= e(rtrim(rtrim((string) $lease['penalty_pct'], '0'), '.')) ?>% per month on rent outstanding beyond <?= (int) $lease['grace_days'] ?> days</td></tr>
  <?php if ($lease['escalation_pct'] !== null): ?>
  <tr><th>Rent review</th><td>The rent may be increased by <?= e(rtrim(rtrim((string) $lease['escalation_pct'], '0'), '.')) ?>% every <?= (int) $lease['escalation_interval_months'] ?> months, subject to one period’s written notice</td></tr>
  <?php endif; ?>
</table>

<ol class="clauses">
  <li><strong>Quiet enjoyment.</strong> The Lessee, paying the rent and observing the covenants, shall quietly hold and enjoy the premises during the term without unlawful interruption by the Lessor.</li>
  <li><strong>Use.</strong> The premises shall be used solely for residential/professional purposes as agreed and shall not be sublet nor assigned without the Lessor’s prior written consent.</li>
  <li><strong>Condition.</strong> The Lessee shall keep the interior in good and tenantable condition, fair wear and tear excepted, and shall promptly report disrepair.</li>
  <li><strong>Utilities.</strong> Electricity, water and other consumables shall be paid by the Lessee; shared facility charges (e.g. generator fuel, common-area lighting) shall be apportioned fairly and notified in advance.</li>
  <li><strong>Deposit.</strong> The security deposit shall not be treated as payment of rent. It shall be refunded, less lawful deductions, after the move-out inspection and handover of keys.</li>
  <li><strong>Termination and notice to quit.</strong> Either party may determine this tenancy by <?= (int) $lease['notice_to_quit_days'] ?> days’ written notice, to expire at the end of a rent period. Determination for breach shall follow the rules of <?= e($lease['legal_system'] === 'common_law' ? 'Common Law' : 'the applicable law') ?> applicable in <?= e($lease['jurisdiction']) ?>.</li>
  <li><strong>Recovery of premises.</strong> On expiry of a duly served notice to quit, possession shall be recovered only through due process of law before the competent court; the Lessor shall not resort to self-help.</li>
  <li><strong>Governing law.</strong> This agreement shall be governed by <?= e($lease['legal_system'] === 'common_law' ? 'Common Law as applicable in the North-West and South-West Regions' : 'Civil Law as applicable in the Francophone Regions') ?> of the Republic of Cameroon, together with the applicable ordinances on leases, and any dispute shall be submitted to the competent courts of <?= e($lease['jurisdiction']) ?>.</li>
</ol>

<div class="notice">
  <strong>Note:</strong> This template is a working draft generated by the PBMS for operational use.
  It must be reviewed and settled by counsel, and stamped/registered as required by fiscal
  legislation, before execution.
</div>

<div class="sig">
  <div>Signed for the Lessor<br>Name &amp; signature</div>
  <div>Signed by the Lessee<br>Name &amp; signature</div>
</div>
<div class="sig">
  <div>Witness 1: <?= e($lease['witness_1'] ?? '____________________') ?><br>Signature</div>
  <div>Witness 2: <?= e($lease['witness_2'] ?? '____________________') ?><br>Signature</div>
</div>

<p class="meta">Generated by <?= e(t('app.name')) ?> on <?= e(date('d M Y H:i')) ?></p>
</body>
</html>
