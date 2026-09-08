<?php

declare(strict_types=1);

namespace Modules\Billing\Models;

use Core\Config;
use Core\Database;

/** Invoice + payment model — Module 4 (accounting & rent collection). */
final class Invoice
{
    public static function all(?string $status = null): array
    {
        $sql = 'SELECT i.*, tn.name AS tenant_name, l.code AS lease_code
                  FROM invoices i
                  JOIN tenants tn ON tn.id = i.tenant_id
                  LEFT JOIN leases l ON l.id = i.lease_id';
        $params = [];
        if ($status && in_array($status, ['unpaid', 'partial', 'paid', 'cancelled', 'overdue'], true)) {
            if ($status === 'overdue') {
                $sql .= " WHERE i.status IN ('unpaid','partial') AND i.due_date < CURDATE()";
            } else {
                $sql .= ' WHERE i.status = ?';
                $params[] = $status;
            }
        }
        $sql .= ' ORDER BY i.issue_date DESC, i.id DESC LIMIT 200';
        return Database::rows($sql, $params);
    }

    public static function find(int $id): ?array
    {
        return Database::row(
            'SELECT i.*, tn.name AS tenant_name FROM invoices i JOIN tenants tn ON tn.id = i.tenant_id WHERE i.id = ?',
            [$id]
        );
    }

    public static function create(array $d, int $userId): int
    {
        return Database::tx(static function () use ($d, $userId): int {
            $seq = (int) Database::scalar(
                'SELECT COUNT(*) + 1 FROM invoices WHERE number LIKE ?',
                ['INV-' . date('Y') . '-%']
            );
            $number = sprintf('INV-%d-%04d', (int) date('Y'), $seq);

            $glCode = match ($d['kind'] ?? 'rent') {
                'late_fee' => '7588',
                'utility'  => '7068',
                default    => '706',
            };

            Database::run(
                'INSERT INTO invoices (number, lease_id, tenant_id, kind, issue_date, due_date, period_start, period_end, amount_xaf, status, notes)
                 VALUES (?,?,?,?,?,?,?,?,?,"unpaid",?)',
                [
                    $number,
                    isset($d['lease_id']) && $d['lease_id'] !== '' ? (int) $d['lease_id'] : null,
                    (int) $d['tenant_id'],
                    in_array($d['kind'] ?? 'rent', ['rent', 'utility', 'late_fee', 'amenity', 'other'], true) ? $d['kind'] : 'rent',
                    $d['issue_date'] ?: date('Y-m-d'),
                    $d['due_date'] ?: date('Y-m-d', strtotime('+5 days')),
                    $d['period_start'] ?? null,
                    $d['period_end'] ?? null,
                    (int) $d['amount_xaf'],
                    $d['notes'] ?? null,
                ]
            );
            $id = Database::lastId();

            Database::run(
                'INSERT INTO invoice_lines (invoice_id, gl_code, description, amount_xaf) VALUES (?,?,?,?)',
                [$id, $glCode, $d['description'] ?? ucfirst(str_replace('_', ' ', $d['kind'] ?? 'rent')), (int) $d['amount_xaf']]
            );

            // Post to the ledger: DR Tenants receivable / CR income.
            $tenantName = Database::scalar('SELECT name FROM tenants WHERE id = ?', [(int) $d['tenant_id']]);
            Ledger::post(
                $d['issue_date'] ?: date('Y-m-d'),
                "$number billed to $tenantName",
                'invoice',
                $id,
                [
                    ['gl_code' => '411', 'debit' => (int) $d['amount_xaf']],
                    ['gl_code' => $glCode, 'credit' => (int) $d['amount_xaf']],
                ]
            );
            return $id;
        });
    }

    /**
     * Record a payment against an invoice and post the ledger entry
     * (DR bank/cash per channel / CR Tenants receivable).
     */
    public static function pay(int $invoiceId, int $amount, string $channel, ?string $reference, int $userId): void
    {
        Database::tx(static function () use ($invoiceId, $amount, $channel, $reference, $userId): void {
            $inv = Database::row('SELECT * FROM invoices WHERE id = ?', [$invoiceId]);
            if (!$inv || $inv['status'] === 'cancelled') {
                throw new \DomainException('Invoice not payable');
            }
            $balance = (int) $inv['amount_xaf'] - (int) $inv['paid_xaf'];
            if ($amount <= 0 || $amount > $balance) {
                throw new \DomainException('Payment must be positive and cannot exceed the balance of ' . fmt_xaf($balance));
            }

            Database::run(
                'INSERT INTO payments (invoice_id, lease_id, amount_xaf, channel, reference, paid_at, received_by, status)
                 VALUES (?,?,?,?,?,NOW(),?,"confirmed")',
                [$invoiceId, $inv['lease_id'], $amount, $channel, $reference, $userId]
            );

            $paid = (int) $inv['paid_xaf'] + $amount;
            $status = $paid >= (int) $inv['amount_xaf'] ? 'paid' : 'partial';
            Database::run('UPDATE invoices SET paid_xaf = ?, status = ? WHERE id = ?', [$paid, $status, $invoiceId]);

            $cashCode = in_array($channel, ['mtn_momo', 'orange_money', 'bank_transfer', 'card'], true) ? '521' : '571';
            $channelName = str_replace('_', ' ', $channel);
            Ledger::post(
                date('Y-m-d'),
                ucfirst($channelName) . ' receipt for ' . $inv['number'] . ($reference ? " ($reference)" : ''),
                'payment',
                $invoiceId,
                [
                    ['gl_code' => $cashCode, 'debit' => $amount],
                    ['gl_code' => '411', 'credit' => $amount],
                ]
            );
        });
    }

    /**
     * Late-fee automation (cron-style job run on demand): for every overdue
     * rent invoice past its grace period without a late fee for that month,
     * raise a late-fee invoice per the active rule.
     */
    public static function runLateFees(): array
    {
        $rule = Database::row('SELECT * FROM late_fee_rules WHERE active = 1 ORDER BY id LIMIT 1');
        if (!$rule) {
            return ['raised' => 0, 'total' => 0];
        }
        $grace = (int) $rule['grace_days'];
        $pct   = (float) $rule['penalty_pct'];
        $flat  = (int) $rule['flat_xaf'];

        $candidates = Database::rows(
            "SELECT i.*, l.rent_xaf,
                    (SELECT COUNT(*) FROM invoices lf
                      WHERE lf.kind = 'late_fee'
                        AND lf.lease_id = i.lease_id
                        AND (lf.period_start = DATE_FORMAT(i.due_date, '%Y-%m-01')
                             OR DATE_FORMAT(lf.issue_date, '%Y-%m') = DATE_FORMAT(i.due_date, '%Y-%m'))) AS already_billed
               FROM invoices i
               LEFT JOIN leases l ON l.id = i.lease_id
              WHERE i.kind = 'rent' AND i.status IN ('unpaid','partial')
                AND i.due_date < CURDATE()
                AND i.due_date < DATE_SUB(CURDATE(), INTERVAL ? DAY)",
            [$grace - 1]
        );

        $raised = 0;
        $totalFees = 0;
        foreach ($candidates as $inv) {
            if ((int) $inv['already_billed'] > 0) {
                continue;
            }
            $base = (int) ($inv['rent_xaf'] ?? $inv['amount_xaf']);
            $fee  = (int) round($base * $pct / 100) + $flat;
            if ($fee <= 0) {
                continue;
            }
            $monthStart = date('Y-m-01', strtotime($inv['due_date']));
            self::create([
                'lease_id'     => $inv['lease_id'],
                'tenant_id'    => $inv['tenant_id'],
                'kind'         => 'late_fee',
                'issue_date'   => date('Y-m-d'),
                'due_date'     => date('Y-m-d', strtotime('+7 days')),
                'period_start' => $monthStart,
                'period_end'   => $monthStart,
                'amount_xaf'   => $fee,
                'description'  => 'Late fee — ' . rtrim(rtrim((string) $pct, '0'), '.') . '% of rent (' . $inv['number'] . ' overdue past ' . $grace . ' days)',
                'notes'        => 'Auto-generated by late-fee rule: ' . $rule['name'],
            ], 0);
            $raised++;
            $totalFees += $fee;
        }
        return ['raised' => $raised, 'total' => $totalFees];
    }
}
