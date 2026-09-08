<?php

declare(strict_types=1);

namespace Modules\Leases\Models;

use Core\Database;

/** Generated legal documents (agreement, notice to quit, …) kept inline as HTML. */
final class LeaseDocument
{
    public static function add(int $leaseId, string $kind, string $title, string $html): int
    {
        Database::run(
            'INSERT INTO lease_documents (lease_id, doc_kind, title, content) VALUES (?,?,?,?)',
            [$leaseId, $kind, $title, $html]
        );
        return Database::lastId();
    }

    public static function forLease(int $leaseId): array
    {
        return Database::rows(
            'SELECT id, doc_kind, title, created_at FROM lease_documents WHERE lease_id = ? ORDER BY created_at DESC',
            [$leaseId]
        );
    }
}
