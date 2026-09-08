<?php

declare(strict_types=1);

namespace Modules\Leases\Controllers;

use Core\Config;
use Core\Controller;
use Core\Database;
use Core\Request;
use Core\Response;
use Core\View;
use Modules\Leases\Models\Lease;
use Modules\Leases\Models\LeaseDocument;
use Modules\Leases\Models\LeaseEvent;
use Modules\Leases\Models\RecoveryCase;
use Modules\Properties\Models\Unit;
use Modules\Tenants\Models\Tenant;

final class LeaseController extends Controller
{
    public function index(): void
    {
        $this->requireRole('admin', 'property_manager', 'accountant');
        $this->view('Leases::index', [
            'title'    => t('lease.title'),
            'leases'   => Lease::all(),
            'units'    => Unit::leasable(),
            'tenants'  => Tenant::options(),
        ]);
    }

    public function show(array $params): void
    {
        $this->requireRole('admin', 'property_manager', 'accountant');
        $lease = Lease::find((int) $params['id']);
        if (!$lease) {
            Response::redirect('leases', 'Lease not found', 'error');
        }
        $invoices = Database::rows(
            'SELECT * FROM invoices WHERE lease_id = ? ORDER BY issue_date DESC, id DESC',
            [$lease['id']]
        );
        $this->view('Leases::show', [
            'title'     => $lease['code'],
            'lease'     => $lease,
            'events'    => LeaseEvent::forLease((int) $lease['id']),
            'documents' => LeaseDocument::forLease((int) $lease['id']),
            'recovery'  => RecoveryCase::openForLease((int) $lease['id']),
            'nextStage' => null, // computed in view when recovery exists
            'invoices'  => $invoices,
            'stages'    => RecoveryCase::STAGES,
        ]);
    }

    public function store(): void
    {
        $user = $this->requireRole('admin', 'property_manager');
        csrf_check();
        try {
            $d = [
                'unit_id'          => Request::requireStr('unit_id', 'unit'),
                'tenant_id'        => Request::requireStr('tenant_id', 'tenant'),
                'start_date'       => Request::requireStr('start_date', 'start date'),
                'end_date'         => Request::requireStr('end_date', 'end date'),
                'rent_xaf'         => Request::requireStr('rent_xaf', 'rent'),
                'billing_cycle'    => Request::str('billing_cycle', 'monthly'),
                'rent_type'        => Request::str('rent_type', 'fixed'),
                'escalation_pct'   => Request::str('escalation_pct'),
                'escalation_interval_months' => Request::str('escalation_interval_months'),
                'deposit_xaf'      => Request::str('deposit_xaf', '0'),
                'deposit_paid'     => Request::int('deposit_paid', 0),
                'notice_to_quit_days' => Request::str('notice_days', (string) Config::get('legal.default_notice_days', 30)),
                'jurisdiction'     => Request::str('jurisdiction', (string) Config::get('legal.default_jurisdiction')),
                'legal_system'     => Request::str('legal_system', 'common_law'),
                'witness_1'        => Request::str('witness_1'),
                'witness_2'        => Request::str('witness_2'),
                'notes'            => Request::str('notes'),
            ];
            $id = Lease::create($d, (int) $user['id']);
            Response::redirect('leases/' . $id, 'Lease drafted — review, then activate');
        } catch (\InvalidArgumentException $e) {
            Response::redirect('leases', $e->getMessage(), 'error');
        } catch (\DomainException $e) {
            Response::redirect('leases', $e->getMessage(), 'error');
        }
    }

    public function activate(array $params): void
    {
        $user = $this->requireRole('admin', 'property_manager');
        csrf_check();
        try {
            Lease::activate((int) $params['id'], (int) $user['id']);
            Response::redirect('leases/' . $params['id'], 'Lease activated — unit is now leased');
        } catch (\DomainException $e) {
            Response::redirect('leases/' . $params['id'], $e->getMessage(), 'error');
        }
    }

    public function terminate(array $params): void
    {
        $user = $this->requireRole('admin', 'property_manager');
        csrf_check();
        try {
            Lease::terminate((int) $params['id'], Request::str('note'), (int) $user['id']);
            Response::redirect('leases/' . $params['id'], 'Lease terminated — unit released');
        } catch (\DomainException $e) {
            Response::redirect('leases/' . $params['id'], $e->getMessage(), 'error');
        }
    }

    /**
     * Generate (or regenerate) the Common Law tenancy agreement for this lease
     * and stream it as a downloadable HTML document.
     */
    public function agreement(array $params): void
    {
        $user = $this->requireRole('admin', 'property_manager', 'accountant');
        $lease = Lease::find((int) $params['id']);
        if (!$lease) {
            Response::redirect('leases', 'Lease not found', 'error');
        }

        $html = View::partial('Leases::documents/agreement', ['lease' => $lease]);
        LeaseDocument::add((int) $lease['id'], 'agreement', 'Tenancy Agreement — ' . $lease['code'], $html);
        LeaseEvent::log((int) $lease['id'], 'agreement_generated', 'Tenancy agreement generated (' . $lease['legal_system'] . ')', (int) $user['id']);

        Response::downloadHtml('Tenancy-Agreement-' . $lease['code'] . '.html', $html);
    }

    /**
     * Issue a notice to quit (Common Law). Opens/advances the recovery case,
     * flags the lease in_recovery, and generates the formal notice document.
     */
    public function noticeToQuit(array $params): void
    {
        $user = $this->requireRole('admin', 'property_manager');
        csrf_check();
        $lease = Lease::find((int) $params['id']);
        if (!$lease) {
            Response::redirect('leases', 'Lease not found', 'error');
        }
        if (!in_array($lease['status'], ['active', 'in_recovery'], true)) {
            Response::redirect('leases/' . $lease['id'], 'A notice to quit can only be served on an active lease', 'error');
        }

        $days  = max(1, (int) Request::int('days', (int) $lease['notice_to_quit_days']));
        $reason = Request::str('reason', 'Persistent default in payment of rent');

        $notice = [
            'issue_date'  => date('Y-m-d'),
            'expiry_date' => date('Y-m-d', strtotime('+' . $days . ' days')),
            'days'        => $days,
            'reason'      => $reason,
        ];

        Database::tx(static function () use ($lease, $user, $notice): void {
            Database::run("UPDATE leases SET status = 'in_recovery' WHERE id = ?", [(int) $lease['id']]);
            RecoveryCase::openOrCreate(
                (int) $lease['id'],
                'notice_to_quit_issued',
                'Notice to quit issued ' . $notice['issue_date'] . ', expires ' . $notice['expiry_date']
            );
            LeaseEvent::log(
                (int) $lease['id'],
                'notice_to_quit',
                'Notice to quit served — ' . $notice['days'] . ' days; expiry ' . $notice['expiry_date']
                . '. Reason: ' . $notice['reason'] . '. If not remedied, file for recovery of premises.',
                (int) $user['id']
            );
        });

        $html = View::partial('Leases::documents/notice_to_quit', ['lease' => $lease, 'notice' => $notice]);
        LeaseDocument::add((int) $lease['id'], 'notice_to_quit', 'Notice to Quit — ' . $lease['code'], $html);

        Response::downloadHtml('Notice-to-Quit-' . $lease['code'] . '.html', $html);
    }

    /** Advance the recovery case to its next Common Law stage. */
    public function advanceRecovery(array $params): void
    {
        $user = $this->requireRole('admin', 'property_manager');
        csrf_check();
        $lease = Lease::find((int) $params['id']);
        if (!$lease) {
            Response::redirect('leases', 'Lease not found', 'error');
        }
        $case = RecoveryCase::openForLease((int) $lease['id']);
        if (!$case) {
            Response::redirect('leases/' . $lease['id'], 'No open recovery case for this lease', 'error');
        }
        $target = Request::str('stage');
        $allowed = array_keys(RecoveryCase::STAGES);
        if (!in_array($target, $allowed, true)) {
            Response::redirect('leases/' . $lease['id'], 'Unknown recovery stage', 'error');
        }

        RecoveryCase::openOrCreate((int) $lease['id'], $target, 'Stage advanced to ' . RecoveryCase::STAGES[$target]);
        LeaseEvent::log((int) $lease['id'], 'recovery_stage', 'Recovery stage → ' . RecoveryCase::STAGES[$target], (int) $user['id']);
        if ($target === 'closed') {
            Database::run("UPDATE leases SET status = 'terminated' WHERE id = ? AND status = 'in_recovery'", [(int) $lease['id']]);
            Database::run("UPDATE units SET status = 'vacant' WHERE id = ?", [(int) $lease['unit_id']]);
        }

        Response::redirect('leases/' . $lease['id'], 'Recovery stage updated: ' . RecoveryCase::STAGES[$target]);
    }
}
