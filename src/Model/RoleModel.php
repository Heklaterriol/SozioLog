<?php
namespace Logbuch\Model;

use Logbuch\Database;

class RoleModel
{
    private Database $db;
    public function __construct() { $this->db = Database::getInstance(); }

    public function findByCircle(int $circleId): array
    {
        return $this->db->fetchAll("
            SELECT r.*,
                   m.name  AS current_holder,
                   ra.id   AS assignment_id,
                   ra.start_date,
                   ra.elected_until
            FROM   roles r
            LEFT JOIN role_assignments ra
                ON  ra.role_id   = r.id
                AND ra.end_date  IS NULL
            LEFT JOIN members m ON ra.member_id = m.id
            WHERE  r.circle_id = ?
            ORDER BY r.role_type, r.name
        ", [$circleId]);
    }

    public function findById(int $id): ?array
    {
        return $this->db->fetchOne("
            SELECT r.*, c.name AS circle_name
            FROM   roles r
            JOIN   circles c ON r.circle_id = c.id
            WHERE  r.id = ?
        ", [$id]);
    }

    public function findAssignmentHistory(int $roleId): array
    {
        return $this->db->fetchAll("
            SELECT ra.*, m.name AS member_name
            FROM   role_assignments ra
            JOIN   members m ON ra.member_id = m.id
            WHERE  ra.role_id = ?
            ORDER BY ra.start_date DESC
        ", [$roleId]);
    }

    public function create(array $data): int
    {
        return $this->db->insert("
            INSERT INTO roles (circle_id, name, domain, purpose, accountabilities, role_type, is_elected)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ", [
            $data['circle_id'],
            $data['name'],
            $data['domain']            ?? null,
            $data['purpose']           ?? null,
            isset($data['accountabilities']) ? json_encode($data['accountabilities']) : null,
            $data['role_type']         ?? 'general',
            $data['is_elected']        ? 1 : 0,
        ]);
    }

    public function update(int $id, array $data): int
    {
        return $this->db->execute("
            UPDATE roles
               SET name             = ?,
                   domain           = ?,
                   purpose          = ?,
                   accountabilities = ?,
                   role_type        = ?,
                   is_elected       = ?
             WHERE id = ?
        ", [
            $data['name'],
            $data['domain']            ?? null,
            $data['purpose']           ?? null,
            isset($data['accountabilities']) ? json_encode($data['accountabilities']) : null,
            $data['role_type']         ?? 'general',
            $data['is_elected']        ? 1 : 0,
            $id,
        ]);
    }

    public function assign(int $roleId, int $memberId, string $startDate, ?string $electedUntil = null): int
    {
        // Laufende Zuweisung beenden
        $this->db->execute(
            "UPDATE role_assignments SET end_date = ? WHERE role_id = ? AND end_date IS NULL",
            [date('Y-m-d'), $roleId]
        );
        return $this->db->insert("
            INSERT INTO role_assignments (role_id, member_id, start_date, elected_until)
            VALUES (?, ?, ?, ?)
        ", [$roleId, $memberId, $startDate, $electedUntil]);
    }
}
