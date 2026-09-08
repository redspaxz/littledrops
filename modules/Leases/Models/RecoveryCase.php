<?php

declare(strict_types=1);

namespace Modules\Leases\Models;

use Core\Database;

/**
 * Recovery of premises under Common Law. Stages are ordered; eviction can
 * only follow a court order (judgment → enforcement). Self-help eviction is
 * never an available stage by design.
 */
final class RecoveryCase
{
    public const STAGES = [
        'demand_letter'        => 'Demand letter',
        'notice_to_quit_issued'=> 'Notice to quit issued',
        'notice_expired'       => 'Notice expired — no remedy',
        'court_filing'         => 'Court filing (recovery of premises)',
        'judgment'             => 'Judgment obtained',
        'enforcement'          => 'Enforcement / eviction warrant',
        'closed'               => 'Closed',
    ];

    public static function openForLease(int $leaseId): ?array
    {
        return Database::row(
            'SELECT * FROM recovery_cases WHERE lease_id = ? AND stage <> ? ORDER BY id DESC LIMIT 1',
            [$leaseId, 'closed']
        );
    }

    public static function openOrCreate(int $leaseId, string $stage, ?string $note = null): int
    {
        $existing = self::openForLease($leaseId);
        if ($existing) {
            $notes = trim(($existing['notes'] ?? '') . "\n" . (string) $note);
            Database::run(
                'UPDATE recovery_cases SET stage = ?, notes = ? WHERE id = ?',
                [$stage, $notes, (int) $existing['id']]
            );
            return (int) $existing['id'];
        }
        Database::run(
            'INSERT INTO recovery_cases (lease_id, stage, notes) VALUES (?,?,?)',
            [$leaseId, $stage, $note]
        );
        return Database::lastId();
    }

    /** Next stage in the Common Law pipeline, or null if at the end. */
    public static function nextStage(string $current): ?string
    {
        $keys = array_keys(self::STAGES);
        $i = array_search($current, $keys, true);
        return $i === false || $i >= count($keys) - 1 ? null : $keys[$i + 1];
    }
}
