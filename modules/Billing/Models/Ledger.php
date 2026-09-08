<?php

declare(strict_types=1);

namespace Modules\Billing\Models;

use Core\Database;

/**
 * Double-entry ledger. Every financial event posts balanced journal lines
 * against SYSCOHADA-shaped GL accounts.
 */
final class Ledger
{
    /** @param array<int, array{gl_code: string, debit?: int, credit?: int}> $lines */
    public static function post(string $date, string $memo, string $refType, int $refId, array $lines): int
    {
        Database::run(
            'INSERT INTO journal_entries (entry_date, memo, ref_type, ref_id) VALUES (?,?,?,?)',
            [$date, $memo, $refType, $refId]
        );
        $entryId = Database::lastId();
        foreach ($lines as $line) {
            Database::run(
                'INSERT INTO journal_lines (entry_id, gl_code, debit_xaf, credit_xaf) VALUES (?,?,?,?)',
                [$entryId, $line['gl_code'], (int) ($line['debit'] ?? 0), (int) ($line['credit'] ?? 0)]
            );
        }
        return $entryId;
    }
}
