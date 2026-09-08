<?php

declare(strict_types=1);

namespace Modules\Leases\Models;

use Core\Config;
use Core\Database;

/**
 * Lease model — Module 3 (contract lifecycle).
 * Common-law fields (legal_system, jurisdiction, notice_to_quit_days) are
 * first-class citizens, not afterthoughts: Bamenda is a Common Law
 * jurisdiction and the recovery pipeline assumes court-supervised eviction.
 */
final class Lease
{
    public static function all(): array
    {
        return Database::rows(
            'SELECT l.*, u.code AS unit_code, u.building_id, b.name AS building_name, b.quarter,
                    tn.name AS tenant_name
               FROM leases l
               JOIN units u ON u.id = l.unit_id
               JOIN buildings b ON b.id = u.building_id
               JOIN tenants tn ON tn.id = l.tenant_id
              ORDER BY l.created_at DESC, l.id DESC'
        );
    }

    public static function find(int $id): ?array
    {
        return Database::row(
            'SELECT l.*, u.code AS unit_code, u.unit_type, u.sqm, u.building_id,
                    b.name AS building_name, b.quarter, b.city, b.address AS building_address,
                    tn.name AS tenant_name, tn.phone AS tenant_phone, tn.email AS tenant_email,
                    tn.kind AS tenant_kind
               FROM leases l
               JOIN units u ON u.id = l.unit_id
               JOIN buildings b ON b.id = u.building_id
               JOIN tenants tn ON tn.id = l.tenant_id
              WHERE l.id = ?',
            [$id]
        );
    }

    public static function create(array $d, int $userId): int
    {
        return Database::tx(static function () use ($d, $userId): int {
            $year = (int) date('Y');
            $seq  = (int) Database::scalar(
                'SELECT COUNT(*) + 1 FROM leases WHERE code LIKE ?',
                ["LSE-$year-%"]
            );
            $code = sprintf('LSE-%d-%03d', $year, $seq);

            Database::run(
                'INSERT INTO leases
                   (code, unit_id, tenant_id, start_date, end_date, rent_xaf, billing_cycle, rent_type,
                    escalation_pct, escalation_interval_months, grace_days, penalty_pct,
                    deposit_xaf, deposit_paid, legal_system, jurisdiction, notice_to_quit_days,
                    status, witness_1, witness_2, notes)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
                [
                    $code, (int) $d['unit_id'], (int) $d['tenant_id'],
                    $d['start_date'], $d['end_date'], (int) $d['rent_xaf'],
                    in_array($d['billing_cycle'] ?? 'monthly', ['monthly', 'quarterly', 'annually'], true)
                        ? $d['billing_cycle'] : 'monthly',
                    ($d['rent_type'] ?? 'fixed') === 'dynamic' ? 'dynamic' : 'fixed',
                    isset($d['escalation_pct']) && $d['escalation_pct'] !== '' ? (float) $d['escalation_pct'] : null,
                    isset($d['escalation_interval_months']) && $d['escalation_interval_months'] !== ''
                        ? (int) $d['escalation_interval_months'] : null,
                    (int) ($d['grace_days'] ?? Config::get('legal.late_fee_grace_days', 7)),
                    (float) ($d['penalty_pct'] ?? Config::get('legal.late_fee_penalty_pct', 5)),
                    (int) ($d['deposit_xaf'] ?? 0),
                    (int) ($d['deposit_paid'] ?? 0),
                    ($d['legal_system'] ?? 'common_law') === 'civil_law' ? 'civil_law' : 'common_law',
                    $d['jurisdiction'] ?? Config::get('legal.default_jurisdiction'),
                    (int) ($d['notice_to_quit_days'] ?? Config::get('legal.default_notice_days', 30)),
                    'draft', $d['witness_1'] ?? null, $d['witness_2'] ?? null, $d['notes'] ?? null,
                ]
            );
            $id = Database::lastId();
            LeaseEvent::log($id, 'created', 'Lease drafted (' . $code . ')', $userId);
            return $id;
        });
    }

    /** Draft → active: countersign, mark unit leased. */
    public static function activate(int $id, int $userId): void
    {
        Database::tx(static function () use ($id, $userId): void {
            $lease = self::find($id);
            if (!$lease || $lease['status'] !== 'draft') {
                throw new \DomainException('Only draft leases can be activated');
            }
            Database::run(
                "UPDATE leases SET status = 'active', signed_at = NOW() WHERE id = ?",
                [$id]
            );
            Database::run("UPDATE units SET status = 'leased' WHERE id = ?", [(int) $lease['unit_id']]);
            LeaseEvent::log($id, 'activated', 'Agreement activated; unit marked leased; deposit '
                . fmt_xaf($lease['deposit_xaf']) . ($lease['deposit_paid'] ? ' received' : ' pending'), $userId);
        });
    }

    /** Amicable termination: frees the unit, closes any recovery case. */
    public static function terminate(int $id, string $note, int $userId): void
    {
        Database::tx(static function () use ($id, $note, $userId): void {
            $lease = self::find($id);
            if (!$lease || !in_array($lease['status'], ['active', 'in_recovery', 'expired'], true)) {
                throw new \DomainException('Lease cannot be terminated from its current status');
            }
            Database::run("UPDATE leases SET status = 'terminated' WHERE id = ?", [$id]);
            Database::run("UPDATE units SET status = 'vacant' WHERE id = ?", [(int) $lease['unit_id']]);
            Database::run(
                "UPDATE recovery_cases SET stage = 'closed', notes = CONCAT(COALESCE(notes,''), '\nClosed — lease terminated') WHERE lease_id = ? AND stage <> 'closed'",
                [$id]
            );
            LeaseEvent::log($id, 'terminated', $note !== '' ? $note : 'Lease terminated; unit released', $userId);
        });
    }
}
