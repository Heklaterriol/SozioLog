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
            "SELECT id, name, email, is_admin, permission_level, created_at FROM members ORDER BY name"
        );
    }

    /**
     * Kreiszugehörigkeit ALLER Mitglieder auf einmal, gruppiert nach
     * member_id => [circle_id, ...]. Für Listenansichten (kein N+1).
     */
    public function findAllCircleIdsGrouped(): array
    {
        $rows = $this->db->fetchAll("SELECT member_id, circle_id FROM circle_memberships");
        $grouped = [];
        foreach ($rows as $r) {
            $grouped[(int) $r['member_id']][] = (int) $r['circle_id'];
        }
        return $grouped;
    }

    public function findById(int $id): ?array
    {
        return $this->db->fetchOne(
            "SELECT id, name, email, is_admin, permission_level, created_at FROM members WHERE id = ?",
            [$id]
        );
    }

    public function findRoles(int $memberId): array
    {
        return $this->db->fetchAll("
            SELECT ra.id AS assignment_id, r.id AS role_id, r.name AS role_name, r.role_type,
                   c.id AS circle_id, c.name AS circle_name,
                   ra.start_date, ra.end_date, ra.elected_until
            FROM   role_assignments ra
            JOIN   roles   r ON ra.role_id  = r.id
            JOIN   circles c ON r.circle_id = c.id
            WHERE  ra.member_id = ?
            ORDER BY ra.end_date IS NULL DESC, ra.start_date DESC
        ", [$memberId]);
    }

    /**
     * Nur die aktuell aktiven Rollenzuweisungen (end_date IS NULL).
     */
    public function findActiveRoles(int $memberId): array
    {
        return $this->db->fetchAll("
            SELECT ra.id AS assignment_id, r.id AS role_id, r.name AS role_name, r.role_type,
                   c.id AS circle_id, c.name AS circle_name, ra.elected_until
            FROM   role_assignments ra
            JOIN   roles   r ON ra.role_id  = r.id
            JOIN   circles c ON r.circle_id = c.id
            WHERE  ra.member_id = ? AND ra.end_date IS NULL
            ORDER BY c.name, r.name
        ", [$memberId]);
    }

    public function create(array $data): int
    {
        return $this->db->insert("
            INSERT INTO members (name, email, password_hash, is_admin, permission_level)
            VALUES (?, ?, ?, ?, ?)
        ", [
            $data['name'],
            strtolower(trim($data['email'])),
            password_hash($data['password'], PASSWORD_BCRYPT),
            ($data['permission_level'] ?? 'member') === 'admin' ? 1 : 0,
            $data['permission_level'] ?? 'member',
        ]);
    }

    public function update(int $id, array $data): int
    {
        // is_admin bleibt synchron zu permission_level (Abwärtskompatibilität)
        return $this->db->execute(
            "UPDATE members SET name = ?, email = ?, is_admin = ?, permission_level = ? WHERE id = ?",
            [
                $data['name'],
                strtolower(trim($data['email'])),
                ($data['permission_level'] ?? 'member') === 'admin' ? 1 : 0,
                $data['permission_level'] ?? 'member',
                $id,
            ]
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
    //  Kreiszugehörigkeit (circle_memberships) — direkt, unabhängig
    //  von Rollen. Wird vom Admin bzw. berechtigten Mitglied auf der
    //  Mitglieder-Detailseite gepflegt.
    // ------------------------------------------------------------------

    /**
     * IDs der Kreise, denen diese Person direkt zugeordnet ist.
     */
    public function findCircleIds(int $memberId): array
    {
        $rows = $this->db->fetchAll(
            "SELECT circle_id FROM circle_memberships WHERE member_id = ?",
            [$memberId]
        );
        return array_map(fn($r) => (int) $r['circle_id'], $rows);
    }

    /**
     * Kreiszugehörigkeit inkl. Kreisnamen (für die Anzeige).
     */
    public function findCircleMemberships(int $memberId): array
    {
        return $this->db->fetchAll("
            SELECT cm.id, c.id AS circle_id, c.name AS circle_name
            FROM   circle_memberships cm
            JOIN   circles c ON cm.circle_id = c.id
            WHERE  cm.member_id = ?
            ORDER BY c.name
        ", [$memberId]);
    }

    /**
     * Ersetzt die komplette Kreiszuordnung einer Person durch die
     * übergebene Liste von Kreis-IDs (nur bereits existierende Kreise).
     */
    public function setCircleMemberships(int $memberId, array $circleIds): void
    {
        $circleIds = array_values(array_unique(array_map('intval', $circleIds)));

        $this->db->transaction(function () use ($memberId, $circleIds) {
            $this->db->execute(
                "DELETE FROM circle_memberships WHERE member_id = ?",
                [$memberId]
            );
            foreach ($circleIds as $cid) {
                if ($cid <= 0) {
                    continue;
                }
                $this->db->execute(
                    "INSERT IGNORE INTO circle_memberships (member_id, circle_id) VALUES (?, ?)",
                    [$memberId, $cid]
                );
            }
        });
    }

    // ------------------------------------------------------------------
    //  Rollenzuweisungen direkt von der Mitglieder-Detailseite aus
    // ------------------------------------------------------------------

    /**
     * Weist dieser Person eine bestehende Rolle zu (optional befristet
     * bis elected_until). Eine laufende Zuweisung derselben Rolle an
     * eine andere Person wird automatisch beendet (siehe RoleModel::assign).
     */
    public function assignRole(int $memberId, int $roleId, ?string $electedUntil, ?string $startDate = null): void
    {
        (new RoleModel())->assign($roleId, $memberId, $startDate ?? date('Y-m-d'), $electedUntil);
    }

    /**
     * Liefert die circle_id der Rolle hinter einer Zuweisung (für
     * Berechtigungsprüfungen bei endRoleAssignment).
     */
    public function findRoleAssignmentCircle(int $assignmentId): ?int
    {
        $circleId = $this->db->fetchValue("
            SELECT r.circle_id
            FROM   role_assignments ra
            JOIN   roles r ON ra.role_id = r.id
            WHERE  ra.id = ?
        ", [$assignmentId]);

        return ($circleId !== false && $circleId !== null) ? (int) $circleId : null;
    }

    /**
     * Beendet eine aktive Rollenzuweisung dieser Person (Rolle entzogen).
     */
    public function endRoleAssignment(int $memberId, int $assignmentId): void
    {
        $this->db->execute(
            "UPDATE role_assignments SET end_date = ?
             WHERE id = ? AND member_id = ? AND end_date IS NULL",
            [date('Y-m-d'), $assignmentId, $memberId]
        );
    }

    /**
     * Aktualisiert nur das Bis-Datum (elected_until) einer aktiven
     * Rollenzuweisung dieser Person.
     */
    public function updateRoleUntilDate(int $memberId, int $assignmentId, ?string $electedUntil): void
    {
        $this->db->execute(
            "UPDATE role_assignments SET elected_until = ?
             WHERE id = ? AND member_id = ? AND end_date IS NULL",
            [$electedUntil, $assignmentId, $memberId]
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
