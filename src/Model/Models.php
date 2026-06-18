<?php
namespace Logbuch\Model;

use Logbuch\Database;

// ============================================================
//  AgreementModel
// ============================================================
class AgreementModel
{
    private Database $db;
    public function __construct() { $this->db = Database::getInstance(); }

    public function findByCircle(int $circleId, bool $activeOnly = false): array
    {
        $where = $activeOnly ? "AND a.status IN ('active','review_due')" : '';
        return $this->db->fetchAll("
            SELECT a.*, m.name AS created_by_name
            FROM   agreements a
            LEFT JOIN members m ON a.created_by = m.id
            WHERE  a.circle_id = ? {$where}
            ORDER BY a.agreed_at DESC
        ", [$circleId]);
    }

    public function findById(int $id): ?array
    {
        return $this->db->fetchOne("
            SELECT a.*, c.name AS circle_name, m.name AS created_by_name
            FROM   agreements a
            JOIN   circles c ON a.circle_id = c.id
            LEFT JOIN members m ON a.created_by = m.id
            WHERE  a.id = ?
        ", [$id]);
    }

    public function findDueForReview(int $days = 30): array
    {
        return $this->db->fetchAll("
            SELECT a.*, c.name AS circle_name
            FROM   agreements a
            JOIN   circles c ON a.circle_id = c.id
            WHERE  a.status = 'active'
              AND  a.review_date IS NOT NULL
              AND  a.review_date <= DATE_ADD(CURDATE(), INTERVAL ? DAY)
            ORDER BY a.review_date
        ", [$days]);
    }

    public function create(array $data): int
    {
        return $this->db->insert("
            INSERT INTO agreements
                (circle_id, meeting_id, title, driver, body, agreed_at, review_date, status, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ", [
            $data['circle_id'],
            $data['meeting_id']  ?? null,
            $data['title'],
            $data['driver']      ?? null,
            $data['body']        ?? null,
            $data['agreed_at'],
            $data['review_date'] ?? null,
            $data['status']      ?? 'active',
            $data['created_by']  ?? null,
        ]);
    }

    public function update(int $id, array $data): int
    {
        return $this->db->execute("
            UPDATE agreements
               SET title       = ?,
                   driver      = ?,
                   body        = ?,
                   agreed_at   = ?,
                   review_date = ?,
                   status      = ?
             WHERE id = ?
        ", [
            $data['title'],
            $data['driver']      ?? null,
            $data['body']        ?? null,
            $data['agreed_at'],
            $data['review_date'] ?? null,
            $data['status']      ?? 'active',
            $id,
        ]);
    }

    public function validate(array $data): array
    {
        $errors = [];
        if (empty(trim($data['title'] ?? ''))) {
            $errors['title'] = 'Titel ist erforderlich.';
        }
        if (empty($data['agreed_at'])) {
            $errors['agreed_at'] = 'Beschlussdatum ist erforderlich.';
        }
        return $errors;
    }
}

// ============================================================
//  RoleModel
// ============================================================
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
            $data['domain']           ?? null,
            $data['purpose']          ?? null,
            isset($data['accountabilities']) ? json_encode($data['accountabilities']) : null,
            $data['role_type']        ?? 'general',
            $data['is_elected']       ? 1 : 0,
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
            $data['domain']           ?? null,
            $data['purpose']          ?? null,
            isset($data['accountabilities']) ? json_encode($data['accountabilities']) : null,
            $data['role_type']        ?? 'general',
            $data['is_elected']       ? 1 : 0,
            $id,
        ]);
    }

    public function assign(int $roleId, int $memberId, string $startDate, ?string $electedUntil = null): int
    {
        // Vorherige Zuweisung beenden
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

// ============================================================
//  TensionModel
// ============================================================
class TensionModel
{
    private Database $db;
    public function __construct() { $this->db = Database::getInstance(); }

    public function findByCircle(int $circleId, ?string $status = null): array
    {
        $where = $status ? "AND t.status = ?" : '';
        $params = $status ? [$circleId, $status] : [$circleId];
        return $this->db->fetchAll("
            SELECT t.*, m.name AS raised_by_name
            FROM   tensions t
            LEFT JOIN members m ON t.raised_by = m.id
            WHERE  t.circle_id = ? {$where}
            ORDER BY t.created_at DESC
        ", $params);
    }

    public function findById(int $id): ?array
    {
        return $this->db->fetchOne("
            SELECT t.*, c.name AS circle_name, m.name AS raised_by_name
            FROM   tensions t
            JOIN   circles c ON t.circle_id = c.id
            LEFT JOIN members m ON t.raised_by = m.id
            WHERE  t.id = ?
        ", [$id]);
    }

    public function create(array $data): int
    {
        return $this->db->insert("
            INSERT INTO tensions (circle_id, raised_by, meeting_id, title, description, status)
            VALUES (?, ?, ?, ?, ?, ?)
        ", [
            $data['circle_id'],
            $data['raised_by']  ?? null,
            $data['meeting_id'] ?? null,
            $data['title']      ?? null,
            $data['description'] ?? null,
            $data['status']     ?? 'open',
        ]);
    }

    public function update(int $id, array $data): int
    {
        return $this->db->execute("
            UPDATE tensions
               SET title       = ?,
                   description = ?,
                   status      = ?,
                   resolved_by = ?
             WHERE id = ?
        ", [
            $data['title']       ?? null,
            $data['description'] ?? null,
            $data['status']      ?? 'open',
            $data['resolved_by'] ?? null,
            $id,
        ]);
    }
}

// ============================================================
//  MemberModel
// ============================================================
class MemberModel
{
    private Database $db;
    public function __construct() { $this->db = Database::getInstance(); }

    public function findAll(): array
    {
        return $this->db->fetchAll(
            "SELECT id, name, email, is_admin, created_at FROM members ORDER BY name"
        );
    }

    public function findById(int $id): ?array
    {
        return $this->db->fetchOne(
            "SELECT id, name, email, is_admin, created_at FROM members WHERE id = ?",
            [$id]
        );
    }

    public function findRoles(int $memberId): array
    {
        return $this->db->fetchAll("
            SELECT r.name AS role_name, r.role_type, c.name AS circle_name,
                   ra.start_date, ra.end_date, ra.elected_until
            FROM   role_assignments ra
            JOIN   roles   r ON ra.role_id   = r.id
            JOIN   circles c ON r.circle_id  = c.id
            WHERE  ra.member_id = ?
            ORDER BY ra.end_date IS NULL DESC, ra.start_date DESC
        ", [$memberId]);
    }

    public function create(array $data): int
    {
        return $this->db->insert("
            INSERT INTO members (name, email, password_hash, is_admin)
            VALUES (?, ?, ?, ?)
        ", [
            $data['name'],
            strtolower(trim($data['email'])),
            password_hash($data['password'], PASSWORD_BCRYPT),
            $data['is_admin'] ? 1 : 0,
        ]);
    }

    public function update(int $id, array $data): int
    {
        return $this->db->execute("
            UPDATE members SET name = ?, email = ?, is_admin = ? WHERE id = ?",
            [$data['name'], strtolower(trim($data['email'])), $data['is_admin'] ? 1 : 0, $id]
        );
    }

    public function updatePassword(int $id, string $newPassword): void
    {
        $this->db->execute(
            "UPDATE members SET password_hash = ? WHERE id = ?",
            [password_hash($newPassword, PASSWORD_BCRYPT), $id]
        );
    }

    public function emailExists(string $email, ?int $excludeId = null): bool
    {
        $sql    = "SELECT COUNT(*) FROM members WHERE email = ?";
        $params = [strtolower(trim($email))];
        if ($excludeId) { $sql .= ' AND id != ?'; $params[] = $excludeId; }
        return (bool) $this->db->fetchValue($sql, $params);
    }
}
