<?php
namespace Logbuch\Model;

use Logbuch\Database;

/**
 * CircleModel — alle DB-Operationen für Kreise
 */
class CircleModel
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // ------------------------------------------------------------------
    //  Abfragen
    // ------------------------------------------------------------------

    /** Alle aktiven Kreise, optional mit Überkreis-Name */
    public function findAll(bool $includeArchived = false): array
    {
        $where = $includeArchived ? '' : "WHERE c.status = 'active'";
        return $this->db->fetchAll("
            SELECT c.*,
                   p.name AS parent_name
            FROM   circles c
            LEFT JOIN circles p ON c.parent_id = p.id
            {$where}
            ORDER BY p.name NULLS FIRST, c.name
        ");
    }

    /** Einzelnen Kreis anhand ID */
    public function findById(int $id): ?array
    {
        return $this->db->fetchOne("
            SELECT c.*,
                   p.name AS parent_name
            FROM   circles c
            LEFT JOIN circles p ON c.parent_id = p.id
            WHERE  c.id = ?
        ", [$id]);
    }

    /** Unterkreise eines Kreises */
    public function findChildren(int $parentId): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM circles WHERE parent_id = ? AND status = 'active' ORDER BY name",
            [$parentId]
        );
    }

    /**
     * Baum aller Kreise als verschachtelte Struktur
     * @return list<array>
     */
    public function getTree(): array
    {
        $all   = $this->findAll();
        $byId  = [];
        $roots = [];

        foreach ($all as $c) {
            $c['children'] = [];
            $byId[$c['id']] = $c;
        }
        foreach ($byId as $id => &$c) {
            if ($c['parent_id'] && isset($byId[$c['parent_id']])) {
                $byId[$c['parent_id']]['children'][] = &$c;
            } else {
                $roots[] = &$c;
            }
        }
        return $roots;
    }

    /** Mitglieder eines Kreises (über aktive Rollenzuweisungen) */
    public function findMembers(int $circleId): array
    {
        return $this->db->fetchAll("
            SELECT DISTINCT m.id, m.name, m.email,
                   r.name AS role_name, r.role_type
            FROM   members m
            JOIN   role_assignments ra ON ra.member_id = m.id AND ra.end_date IS NULL
            JOIN   roles r             ON ra.role_id = r.id
            WHERE  r.circle_id = ?
            ORDER BY m.name
        ", [$circleId]);
    }

    // ------------------------------------------------------------------
    //  Schreiben
    // ------------------------------------------------------------------

    public function create(array $data): int
    {
        return $this->db->insert("
            INSERT INTO circles (parent_id, name, driver, domain, purpose, status)
            VALUES (?, ?, ?, ?, ?, ?)
        ", [
            $data['parent_id'] ?: null,
            $data['name'],
            $data['driver']  ?? null,
            $data['domain']  ?? null,
            $data['purpose'] ?? null,
            $data['status']  ?? 'active',
        ]);
    }

    public function update(int $id, array $data): int
    {
        return $this->db->execute("
            UPDATE circles
               SET parent_id = ?,
                   name      = ?,
                   driver    = ?,
                   domain    = ?,
                   purpose   = ?,
                   status    = ?
             WHERE id = ?
        ", [
            $data['parent_id'] ?: null,
            $data['name'],
            $data['driver']  ?? null,
            $data['domain']  ?? null,
            $data['purpose'] ?? null,
            $data['status']  ?? 'active',
            $id,
        ]);
    }

    public function archive(int $id): int
    {
        return $this->db->execute(
            "UPDATE circles SET status = 'archived' WHERE id = ?",
            [$id]
        );
    }

    // ------------------------------------------------------------------
    //  Validierung
    // ------------------------------------------------------------------

    /** @return array<string, string>  leeres Array = keine Fehler */
    public function validate(array $data): array
    {
        $errors = [];
        if (empty(trim($data['name'] ?? ''))) {
            $errors['name'] = 'Der Kreis-Name darf nicht leer sein.';
        }
        if (!empty($data['parent_id']) && (int) $data['parent_id'] === (int) ($data['id'] ?? 0)) {
            $errors['parent_id'] = 'Ein Kreis kann nicht sein eigener Überkreis sein.';
        }
        return $errors;
    }
}
