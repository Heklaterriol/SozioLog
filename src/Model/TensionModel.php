<?php
namespace Logbuch\Model;

use Logbuch\Database;

class TensionModel
{
    private Database $db;
    public function __construct() { $this->db = Database::getInstance(); }

    public function findByCircle(int $circleId, ?string $status = null): array
    {
        $where  = $status ? "AND t.status = ?" : '';
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
            $data['raised_by']   ?? null,
            $data['meeting_id']  ?? null,
            $data['title']       ?? null,
            $data['description'] ?? null,
            $data['status']      ?? 'open',
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
