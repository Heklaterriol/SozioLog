<?php
namespace Logbuch\Model;

use Logbuch\Database;

/**
 * DelegationModel
 *
 * Eine Delegation beschreibt die Übertragung von Autorität
 * eines Überkreises (from_circle) an einen Unterkreis (to_circle).
 * Jede Delegation hat optional einen Rep-Link (vom Unterkreis
 * in den Überkreis) und einen Del-Link (vom Überkreis in den Unterkreis).
 */
class DelegationModel
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // ------------------------------------------------------------------
    //  Abfragen
    // ------------------------------------------------------------------

    /** Alle aktiven Delegationen (für Dashboard/Übersicht) */
    public function findAll(bool $includeEnded = false): array
    {
        $where = $includeEnded ? '' : "WHERE d.status = 'active'";
        return $this->db->fetchAll("
            SELECT d.*,
                   fc.name AS from_circle_name,
                   tc.name AS to_circle_name,
                   rr.name AS rep_link_name,
                   dr.name AS del_link_name,
                   rm.name AS rep_link_holder,
                   dm.name AS del_link_holder
            FROM   delegations d
            JOIN   circles fc ON d.from_circle = fc.id
            JOIN   circles tc ON d.to_circle   = tc.id
            LEFT JOIN roles rr ON d.rep_link_role = rr.id
            LEFT JOIN roles dr ON d.del_link_role = dr.id
            -- Aktuelle Rolleninhaber Rep-Link
            LEFT JOIN role_assignments rra
                ON  rra.role_id  = d.rep_link_role
                AND rra.end_date IS NULL
            LEFT JOIN members rm ON rra.member_id = rm.id
            -- Aktuelle Rolleninhaber Del-Link
            LEFT JOIN role_assignments dra
                ON  dra.role_id  = d.del_link_role
                AND dra.end_date IS NULL
            LEFT JOIN members dm ON dra.member_id = dm.id
            {$where}
            ORDER BY fc.name, tc.name
        ");
    }

    /** Delegationen eines bestimmten Kreises (als Überkreis oder Unterkreis) */
    public function findByCircle(int $circleId, bool $includeEnded = false): array
    {
        $statusWhere = $includeEnded ? '' : "AND d.status = 'active'";
        return $this->db->fetchAll("
            SELECT d.*,
                   fc.name AS from_circle_name,
                   tc.name AS to_circle_name,
                   rr.name AS rep_link_name,
                   dr.name AS del_link_name,
                   rm.name AS rep_link_holder,
                   dm.name AS del_link_holder
            FROM   delegations d
            JOIN   circles fc ON d.from_circle = fc.id
            JOIN   circles tc ON d.to_circle   = tc.id
            LEFT JOIN roles rr ON d.rep_link_role = rr.id
            LEFT JOIN roles dr ON d.del_link_role = dr.id
            LEFT JOIN role_assignments rra
                ON  rra.role_id  = d.rep_link_role AND rra.end_date IS NULL
            LEFT JOIN members rm ON rra.member_id = rm.id
            LEFT JOIN role_assignments dra
                ON  dra.role_id  = d.del_link_role AND dra.end_date IS NULL
            LEFT JOIN members dm ON dra.member_id = dm.id
            WHERE  (d.from_circle = ? OR d.to_circle = ?) {$statusWhere}
            ORDER BY fc.name, tc.name
        ", [$circleId, $circleId]);
    }

    /** Einzelne Delegation */
    public function findById(int $id): ?array
    {
        return $this->db->fetchOne("
            SELECT d.*,
                   fc.name AS from_circle_name,
                   tc.name AS to_circle_name,
                   rr.name AS rep_link_name,
                   rr.id   AS rep_link_role_id,
                   dr.name AS del_link_name,
                   dr.id   AS del_link_role_id,
                   rm.name AS rep_link_holder,
                   rm.id   AS rep_link_holder_id,
                   dm.name AS del_link_holder,
                   dm.id   AS del_link_holder_id
            FROM   delegations d
            JOIN   circles fc ON d.from_circle = fc.id
            JOIN   circles tc ON d.to_circle   = tc.id
            LEFT JOIN roles rr ON d.rep_link_role = rr.id
            LEFT JOIN roles dr ON d.del_link_role = dr.id
            LEFT JOIN role_assignments rra
                ON  rra.role_id  = d.rep_link_role AND rra.end_date IS NULL
            LEFT JOIN members rm ON rra.member_id = rm.id
            LEFT JOIN role_assignments dra
                ON  dra.role_id  = d.del_link_role AND dra.end_date IS NULL
            LEFT JOIN members dm ON dra.member_id = dm.id
            WHERE  d.id = ?
        ", [$id]);
    }

    // ------------------------------------------------------------------
    //  Schreiben
    // ------------------------------------------------------------------

    public function create(array $data): int
    {
        return $this->db->insert("
            INSERT INTO delegations
                (from_circle, to_circle, description, notes, status, started_at, rep_link_role, del_link_role)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ", [
            $data['from_circle'],
            $data['to_circle'],
            $data['description']   ?? null,
            $data['notes']         ?? null,
            $data['status']        ?? 'active',
            $data['started_at']    ?? date('Y-m-d'),
            $data['rep_link_role'] ?? null,
            $data['del_link_role'] ?? null,
        ]);
    }

    public function update(int $id, array $data): int
    {
        return $this->db->execute("
            UPDATE delegations
               SET description   = ?,
                   notes         = ?,
                   status        = ?,
                   started_at    = ?,
                   ended_at      = ?,
                   rep_link_role = ?,
                   del_link_role = ?
             WHERE id = ?
        ", [
            $data['description']   ?? null,
            $data['notes']         ?? null,
            $data['status']        ?? 'active',
            $data['started_at']    ?? null,
            $data['ended_at']      ?? null,
            $data['rep_link_role'] ?? null,
            $data['del_link_role'] ?? null,
            $id,
        ]);
    }

    public function end(int $id, string $endedAt = ''): int
    {
        $endedAt = $endedAt ?: date('Y-m-d');
        return $this->db->execute(
            "UPDATE delegations SET status = 'ended', ended_at = ? WHERE id = ?",
            [$endedAt, $id]
        );
    }

    public function delete(int $id): int
    {
        return $this->db->execute("DELETE FROM delegations WHERE id = ?", [$id]);
    }

    // ------------------------------------------------------------------
    //  Hilfsmethoden
    // ------------------------------------------------------------------

    /**
     * Alle Rollen eines Kreises — für Rep/Del-Link-Auswahl im Formular.
     * Gibt Rollen beider beteiligter Kreise zurück (mit Kreis-Label).
     */
    public function findRolesForCircles(int $circleA, int $circleB): array
    {
        return $this->db->fetchAll("
            SELECT r.id, r.name, r.role_type, c.name AS circle_name, c.id AS circle_id
            FROM   roles r
            JOIN   circles c ON r.circle_id = c.id
            WHERE  r.circle_id IN (?, ?)
            ORDER BY c.name, r.role_type, r.name
        ", [$circleA, $circleB]);
    }

    // ------------------------------------------------------------------
    //  Validierung
    // ------------------------------------------------------------------

    public function validate(array $data): array
    {
        $errors = [];

        if (empty($data['from_circle'])) {
            $errors['from_circle'] = 'Delegierender Kreis ist erforderlich.';
        }
        if (empty($data['to_circle'])) {
            $errors['to_circle'] = 'Empfangender Kreis ist erforderlich.';
        }
        if (!empty($data['from_circle']) && !empty($data['to_circle'])
            && (int) $data['from_circle'] === (int) $data['to_circle']) {
            $errors['to_circle'] = 'Ein Kreis kann nicht an sich selbst delegieren.';
        }

        return $errors;
    }
}
