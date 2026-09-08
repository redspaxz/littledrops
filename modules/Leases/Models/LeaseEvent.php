<?php

declare(strict_types=1);

namespace Modules\Leases\Models;

use Core\Database;

/** Immutable lease timeline (created/activated/renewal_notice/notice_to_quit/…). */
final class LeaseEvent
{
    public static function log(int $leaseId, string $type, ?string $detail = null, ?int $userId = null): void
    {
        Database::run(
            'INSERT INTO lease_events (lease_id, event_type, detail, created_by) VALUES (?,?,?,?)',
            [$leaseId, $type, $detail, $userId]
        );
    }

    public static function forLease(int $leaseId): array
    {
        return Database::rows(
            'SELECT le.*, u.full_name AS by_name
               FROM lease_events le LEFT JOIN users u ON u.id = le.created_by
              WHERE le.lease_id = ? ORDER BY le.created_at DESC, le.id DESC',
            [$leaseId]
        );
    }
}
