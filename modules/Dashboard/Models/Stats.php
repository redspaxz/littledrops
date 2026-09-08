<?php

declare(strict_types=1);

namespace Modules\Dashboard\Models;

use Core\Database;

/** Read-model for the reporting dashboard (Module 8 of the PBMS spec). */
final class Stats
{
    public static function unitStatusCounts(): array
    {
        $rows = Database::rows('SELECT status, COUNT(*) AS n FROM units GROUP BY status');
        $out = [];
        foreach ($rows as $row) {
            $out[$row['status']] = (int) $row['n'];
        }
        return $out;
    }

    public static function occupancyRate(): float
    {
        $total  = (int) Database::scalar('SELECT COUNT(*) FROM units');
        $leased = (int) Database::scalar("SELECT COUNT(*) FROM units WHERE status = 'leased'");
        return $total > 0 ? round($leased / $total * 100, 1) : 0.0;
    }

    public static function monthlyRentRoll(): int
    {
        // Normalised to a monthly figure: monthly rent as-is, quarterly /3, yearly /12.
        return (int) Database::scalar(
            "SELECT COALESCE(SMONTH,0) FROM (
               SELECT SUM(CASE billing_cycle
                            WHEN 'monthly'   THEN rent_xaf
                            WHEN 'quarterly' THEN rent_xaf / 3
                            WHEN 'annually'  THEN rent_xaf / 12
                          END) AS SMONTH
                 FROM leases WHERE status IN ('active','in_recovery')
             ) x"
        );
    }

    public static function collectedThisMonth(): int
    {
        return (int) Database::scalar(
            "SELECT COALESCE(SUM(amount_xaf),0) FROM payments
              WHERE status = 'confirmed' AND DATE_FORMAT(paid_at, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')"
        );
    }

    public static function outstandingArrears(): int
    {
        return (int) Database::scalar(
            "SELECT COALESCE(SUM(amount_xaf - paid_xaf),0) FROM invoices WHERE status IN ('unpaid','partial')"
        );
    }

    public static function openTickets(): array
    {
        return Database::rows(
            "SELECT severity, COUNT(*) AS n FROM maintenance_tickets
              WHERE status NOT IN ('resolved','closed') GROUP BY severity"
        );
    }

    public static function recoveryCases(): array
    {
        return Database::rows(
            "SELECT rc.*, l.code AS lease_code, l.tenant_id, tn.name AS tenant_name
               FROM recovery_cases rc
               JOIN leases l ON l.id = rc.lease_id
               JOIN tenants tn ON tn.id = l.tenant_id
              WHERE rc.stage <> 'closed'
              ORDER BY rc.opened_at DESC"
        );
    }

    public static function recentPayments(int $limit = 7): array
    {
        return Database::rows(
            "SELECT p.*, i.number AS invoice_number, tn.name AS tenant_name
               FROM payments p
               LEFT JOIN invoices i ON i.id = p.invoice_id
               LEFT JOIN tenants tn ON tn.id = COALESCE(i.tenant_id, (SELECT tenant_id FROM leases WHERE id = p.lease_id))
              ORDER BY p.paid_at DESC LIMIT " . (int) $limit
        );
    }

    public static function paymentChannelsThisMonth(): array
    {
        return Database::rows(
            "SELECT channel, SUM(amount_xaf) AS total, COUNT(*) AS n FROM payments
              WHERE status = 'confirmed' AND DATE_FORMAT(paid_at, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')
              GROUP BY channel ORDER BY total DESC"
        );
    }

    /** Billed vs collected per month for the last N months (inclusive of current). */
    public static function monthlyCollections(int $months = 6): array
    {
        $start = date('Y-m-01', strtotime('first day of ' . -($months - 1) . ' months'));

        $billed = Database::rows(
            "SELECT DATE_FORMAT(issue_date, '%Y-%m') AS m, SUM(amount_xaf) AS total
               FROM invoices
              WHERE status <> 'cancelled' AND issue_date >= ?
              GROUP BY m",
            [$start]
        );
        $collected = Database::rows(
            "SELECT DATE_FORMAT(paid_at, '%Y-%m') AS m, SUM(amount_xaf) AS total
               FROM payments
              WHERE status = 'confirmed' AND paid_at >= ?
              GROUP BY m",
            [$start]
        );

        $mapB = array_column($billed, 'total', 'm');
        $mapC = array_column($collected, 'total', 'm');

        $series = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $key = date('Y-m', strtotime('first day of ' . -$i . ' months'));
            $series[] = [
                'label'     => date('M', strtotime($key . '-01')),
                'billed'    => (int) ($mapB[$key] ?? 0),
                'collected' => (int) ($mapC[$key] ?? 0),
            ];
        }
        return $series;
    }

    /** Outstanding balances bucketed by how long overdue (0 = not yet due). */
    public static function arrearsAging(): array
    {
        $row = Database::row(
            "SELECT
               COALESCE(SUM(CASE WHEN due_date >= CURDATE() THEN amount_xaf - paid_xaf ELSE 0 END), 0) AS current,
               COALESCE(SUM(CASE WHEN due_date < CURDATE() AND due_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN amount_xaf - paid_xaf ELSE 0 END), 0) AS d1_30,
               COALESCE(SUM(CASE WHEN due_date < DATE_SUB(CURDATE(), INTERVAL 30 DAY) AND due_date >= DATE_SUB(CURDATE(), INTERVAL 60 DAY) THEN amount_xaf - paid_xaf ELSE 0 END), 0) AS d31_60,
               COALESCE(SUM(CASE WHEN due_date < DATE_SUB(CURDATE(), INTERVAL 60 DAY) THEN amount_xaf - paid_xaf ELSE 0 END), 0) AS d60_plus
             FROM invoices WHERE status IN ('unpaid','partial')"
        );
        return array_map('intval', $row ?? ['current' => 0, 'd1_30' => 0, 'd31_60' => 0, 'd60_plus' => 0]);
    }
}
