<?php
namespace Logbuch\Model;

use Logbuch\Database;

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
