<?php

declare(strict_types=1);

namespace Modules\Maintenance\Models;

use Core\Database;

/** Maintenance ticket + work-order model — Module 5. */
final class Ticket
{
    public static function all(?string $severity = null): array
    {
        $sql = 'SELECT t.*, u.code AS unit_code, b.name AS building_name, tn.name AS tenant_name,
                       (SELECT w.assignee_kind FROM work_orders w WHERE w.ticket_id = t.id ORDER BY w.id DESC LIMIT 1) AS assignee_kind,
                       (SELECT COALESCE(v.name, uu.full_name) FROM work_orders w
                          LEFT JOIN vendors v ON v.id = w.vendor_id
                          LEFT JOIN users uu ON uu.id = w.user_id
                         WHERE w.ticket_id = t.id ORDER BY w.id DESC LIMIT 1) AS assignee_name
                  FROM maintenance_tickets t
                  LEFT JOIN units u ON u.id = t.unit_id
                  LEFT JOIN buildings b ON b.id = t.building_id
                  LEFT JOIN tenants tn ON tn.id = t.tenant_id
                 WHERE 1=1';
        $params = [];
        if ($severity && in_array($severity, ['urgent', 'high', 'low'], true)) {
            $sql .= ' AND t.severity = ?';
            $params[] = $severity;
        }
        $sql .= ' ORDER BY FIELD(t.severity, "urgent", "high", "low"), t.created_at DESC LIMIT 200';
        return Database::rows($sql, $params);
    }

    public static function create(array $d): int
    {
        Database::run(
            'INSERT INTO maintenance_tickets (unit_id, building_id, tenant_id, title, description, severity, photos)
             VALUES (?,?,?,?,?,?,?)',
            [
                isset($d['unit_id']) && $d['unit_id'] !== '' ? (int) $d['unit_id'] : null,
                isset($d['building_id']) && $d['building_id'] !== '' ? (int) $d['building_id'] : null,
                isset($d['tenant_id']) && $d['tenant_id'] !== '' ? (int) $d['tenant_id'] : null,
                $d['title'], $d['description'] ?? null,
                in_array($d['severity'] ?? 'high', ['urgent', 'high', 'low'], true) ? $d['severity'] : 'high',
                $d['photos'] ?? null,
            ]
        );
        return Database::lastId();
    }

    /** Dispatch: internal technician or vendor, with SLA hours. */
    public static function assign(int $ticketId, string $kind, ?int $userId, ?int $vendorId, int $slaHours): void
    {
        Database::tx(static function () use ($ticketId, $kind, $userId, $vendorId, $slaHours): void {
            Database::run(
                'INSERT INTO work_orders (ticket_id, assignee_kind, user_id, vendor_id, sla_hours, status)
                 VALUES (?,?,?,?,?, "assigned")',
                [$ticketId, $kind === 'vendor' ? 'vendor' : 'internal', $userId, $vendorId, $slaHours]
            );
            Database::run("UPDATE maintenance_tickets SET status = 'assigned' WHERE id = ?", [$ticketId]);
        });
    }

    public static function setStatus(int $ticketId, string $status): void
    {
        $allowed = ['open', 'assigned', 'in_progress', 'resolved', 'closed'];
        if (!in_array($status, $allowed, true)) {
            throw new \DomainException('Unknown ticket status');
        }
        Database::run('UPDATE maintenance_tickets SET status = ? WHERE id = ?', [$status, $ticketId]);
        if (in_array($status, ['resolved', 'closed'], true)) {
            Database::run(
                "UPDATE work_orders SET status = 'done', closed_at = NOW() WHERE ticket_id = ? AND status IN ('assigned','in_progress')",
                [$ticketId]
            );
        }
    }
}
