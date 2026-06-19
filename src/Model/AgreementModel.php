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
        // Vor dem Update: aktuellen Stand als Version sichern
        $this->snapshotVersion($id, $data['changed_by'] ?? null, $data['change_note'] ?? null);

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

    // ------------------------------------------------------------------
    //  Versionshistorie
    // ------------------------------------------------------------------

    /**
     * Snapshot des aktuellen Zustands in agreement_versions speichern.
     * Wird automatisch vor jedem update() aufgerufen.
     */
    public function snapshotVersion(int $id, ?int $changedBy = null, ?string $changeNote = null): void
    {
        $current = $this->findById($id);
        if (!$current) {
            return;
        }

        // Nächste Versionsnummer ermitteln
        $nextVersion = (int) $this->db->fetchValue(
            "SELECT COALESCE(MAX(version), 0) + 1 FROM agreement_versions WHERE agreement_id = ?",
            [$id]
        );

        $this->db->insert("
            INSERT INTO agreement_versions
                (agreement_id, version, title, driver, body, agreed_at, review_date, status, changed_by, change_note)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ", [
            $id,
            $nextVersion,
            $current['title'],
            $current['driver'],
            $current['body'],
            $current['agreed_at'],
            $current['review_date'],
            $current['status'],
            $changedBy,
            $changeNote,
        ]);
    }

    /**
     * Alle Versionen einer Vereinbarung, neueste zuerst.
     */
    public function findVersions(int $agreementId): array
    {
        return $this->db->fetchAll("
            SELECT v.*, m.name AS changed_by_name
            FROM   agreement_versions v
            LEFT JOIN members m ON v.changed_by = m.id
            WHERE  v.agreement_id = ?
            ORDER BY v.version DESC
        ", [$agreementId]);
    }

    /**
     * Eine einzelne Version laden.
     */
    public function findVersion(int $agreementId, int $version): ?array
    {
        return $this->db->fetchOne("
            SELECT v.*, m.name AS changed_by_name
            FROM   agreement_versions v
            LEFT JOIN members m ON v.changed_by = m.id
            WHERE  v.agreement_id = ? AND v.version = ?
        ", [$agreementId, $version]);
    }

    /**
     * Vereinbarung auf eine frühere Version zurücksetzen.
     * Sichert den aktuellen Stand zuerst als Snapshot, dann Restore.
     */
    public function restoreVersion(int $agreementId, int $version, ?int $restoredBy = null): bool
    {
        $v = $this->findVersion($agreementId, $version);
        if (!$v) {
            return false;
        }

        $this->update($agreementId, [
            'title'       => $v['title'],
            'driver'      => $v['driver'],
            'body'        => $v['body'],
            'agreed_at'   => $v['agreed_at'],
            'review_date' => $v['review_date'],
            'status'      => $v['status'],
            'changed_by'  => $restoredBy,
            'change_note' => "Wiederhergestellt aus Version {$version}",
        ]);

        return true;
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
