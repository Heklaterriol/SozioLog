<?php
namespace Logbuch\Model;

use Logbuch\Database;

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
            JOIN   roles   r ON ra.role_id  = r.id
            JOIN   circles c ON r.circle_id = c.id
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
        return $this->db->execute(
            "UPDATE members SET name = ?, email = ?, is_admin = ? WHERE id = ?",
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
        if ($excludeId) {
            $sql    .= ' AND id != ?';
            $params[] = $excludeId;
        }
        return (bool) $this->db->fetchValue($sql, $params);
    }

    public function findByEmail(string $email): ?array
    {
        return $this->db->fetchOne(
            "SELECT id, name, email FROM members WHERE email = ?",
            [strtolower(trim($email))]
        );
    }

    // ------------------------------------------------------------------
    //  Passwort-Reset
    // ------------------------------------------------------------------

    /**
     * Legt einen neuen Reset-Token an und gibt den KLARTEXT-Token zurück
     * (wird nur in der E-Mail verwendet, nie persistiert).
     */
    public function createPasswordReset(int $memberId, int $ttlSeconds, ?string $ip): string
    {
        $token     = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        $expiresAt = date('Y-m-d H:i:s', time() + $ttlSeconds);

        // Alte, noch offene Tokens dieses Mitglieds entwerten
        $this->db->execute(
            "DELETE FROM password_resets WHERE member_id = ? AND used_at IS NULL",
            [$memberId]
        );

        $this->db->insert(
            "INSERT INTO password_resets (member_id, token_hash, expires_at, requested_ip)
             VALUES (?, ?, ?, ?)",
            [$memberId, $tokenHash, $expiresAt, $ip]
        );

        return $token;
    }

    /**
     * Prüft einen Klartext-Token und gibt bei Gültigkeit die zugehörige
     * member_id zurück, sonst null (abgelaufen, schon benutzt, unbekannt).
     */
    public function findValidPasswordReset(string $token): ?int
    {
        $tokenHash = hash('sha256', $token);

        $row = $this->db->fetchOne(
            "SELECT member_id FROM password_resets
             WHERE token_hash = ? AND used_at IS NULL AND expires_at > NOW()",
            [$tokenHash]
        );

        return $row ? (int) $row['member_id'] : null;
    }

    /**
     * Markiert den Token als benutzt (Einmal-Verwendung).
     */
    public function consumePasswordReset(string $token): void
    {
        $tokenHash = hash('sha256', $token);
        $this->db->execute(
            "UPDATE password_resets SET used_at = NOW() WHERE token_hash = ?",
            [$tokenHash]
        );
    }
}
