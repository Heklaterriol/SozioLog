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
            "SELECT id, name, email, is_admin, permission_level, nextcloud_user_id, created_at FROM members ORDER BY name"
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
            "SELECT id, name, email, is_admin, permission_level, nextcloud_user_id, created_at FROM members WHERE id = ?",
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
            // Login erfolgt ausschließlich per Nextcloud-SSO — kein
            // lokales Passwort nötig, zufälliger nie genutzter Hash.
            password_hash(bin2hex(random_bytes(32)), PASSWORD_BCRYPT),
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
    //  Nextcloud-SSO — Verknüpfung & automatische Anlage
    // ------------------------------------------------------------------

    /**
     * Findet ein Mitglied anhand der Nextcloud-User-ID.
     */
    public function findByNextcloudId(string $ncUserId): ?array
    {
        return $this->db->fetchOne(
            "SELECT id, name, email, is_admin, permission_level, nextcloud_user_id
             FROM members WHERE nextcloud_user_id = ?",
            [$ncUserId]
        );
    }

    /**
     * Verknüpft ein bestehendes Mitglied (gefunden über E-Mail) mit
     * seiner Nextcloud-User-ID.
     */
    public function linkNextcloudId(int $memberId, string $ncUserId): void
    {
        $this->db->execute(
            "UPDATE members SET nextcloud_user_id = ? WHERE id = ?",
            [$ncUserId, $memberId]
        );
    }

    /**
     * Legt ein neues Mitglied an, das sich erstmals per Nextcloud
     * angemeldet hat. Kein lokales Passwort nötig — Login läuft
     * ausschließlich über Nextcloud-SSO.
     */
    public function createFromNextcloud(string $name, string $email, string $ncUserId): int
    {
        return $this->db->insert("
            INSERT INTO members (name, email, password_hash, is_admin, permission_level, nextcloud_user_id)
            VALUES (?, ?, ?, 0, 'member', ?)
        ", [
            $name,
            strtolower(trim($email)),
            // Kein Passwort-Login möglich — zufälliger, nie genutzter Hash.
            password_hash(bin2hex(random_bytes(32)), PASSWORD_BCRYPT),
            $ncUserId,
        ]);
    }
}
