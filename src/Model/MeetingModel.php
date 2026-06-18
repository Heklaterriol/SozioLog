<?php
namespace Logbuch\Model;

use Logbuch\Database;

/**
 * MeetingModel — DB-Operationen für Meetings und Agenda-Punkte
 */
class MeetingModel
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // ------------------------------------------------------------------
    //  Abfragen
    // ------------------------------------------------------------------

    public function findByCircle(int $circleId, int $limit = 20, ?string $type = null): array
    {
        $typeWhere = $type ? "AND m.meeting_type = '{$type}'" : '';
        return $this->db->fetchAll("
            SELECT m.*,
                   f.name AS facilitator_name,
                   s.name AS secretary_name
            FROM   meetings m
            LEFT JOIN members f ON m.facilitator_id = f.id
            LEFT JOIN members s ON m.secretary_id   = s.id
            WHERE  m.circle_id = ? {$typeWhere}
            ORDER BY m.held_at DESC
            LIMIT  {$limit}
        ", [$circleId]);
    }

    public function findById(int $id): ?array
    {
        return $this->db->fetchOne("
            SELECT m.*,
                   c.name AS circle_name,
                   f.name AS facilitator_name,
                   s.name AS secretary_name
            FROM   meetings m
            JOIN   circles  c ON m.circle_id      = c.id
            LEFT JOIN members f ON m.facilitator_id = f.id
            LEFT JOIN members s ON m.secretary_id   = s.id
            WHERE  m.id = ?
        ", [$id]);
    }

    public function findAgendaItems(int $meetingId): array
    {
        return $this->db->fetchAll("
            SELECT ai.*,
                   t.title       AS tension_title,
                   t.description AS tension_desc
            FROM   agenda_items ai
            LEFT JOIN tensions t ON ai.tension_id = t.id
            WHERE  ai.meeting_id = ?
            ORDER BY ai.sort_order, ai.id
        ", [$meetingId]);
    }

    public function findAgreements(int $meetingId): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM agreements WHERE meeting_id = ? ORDER BY agreed_at",
            [$meetingId]
        );
    }

    // ------------------------------------------------------------------
    //  Schreiben
    // ------------------------------------------------------------------

    public function create(array $data): int
    {
        return $this->db->insert("
            INSERT INTO meetings
                (circle_id, meeting_type, held_at, location, facilitator_id, secretary_id, attendees, notes)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ", [
            $data['circle_id'],
            $data['meeting_type'] ?? 'governance',
            $data['held_at'],
            $data['location']       ?? null,
            $data['facilitator_id'] ?? null,
            $data['secretary_id']   ?? null,
            isset($data['attendees']) ? json_encode($data['attendees']) : null,
            $data['notes']          ?? null,
        ]);
    }

    public function update(int $id, array $data): int
    {
        return $this->db->execute("
            UPDATE meetings
               SET meeting_type   = ?,
                   held_at        = ?,
                   location       = ?,
                   facilitator_id = ?,
                   secretary_id   = ?,
                   attendees      = ?,
                   notes          = ?
             WHERE id = ?
        ", [
            $data['meeting_type']   ?? 'governance',
            $data['held_at'],
            $data['location']       ?? null,
            $data['facilitator_id'] ?? null,
            $data['secretary_id']   ?? null,
            isset($data['attendees']) ? json_encode($data['attendees']) : null,
            $data['notes']          ?? null,
            $id,
        ]);
    }

    public function addAgendaItem(int $meetingId, array $data): int
    {
        $maxOrder = (int) $this->db->fetchValue(
            'SELECT COALESCE(MAX(sort_order), 0) FROM agenda_items WHERE meeting_id = ?',
            [$meetingId]
        );

        return $this->db->insert("
            INSERT INTO agenda_items (meeting_id, tension_id, title, item_type, outcome, sort_order)
            VALUES (?, ?, ?, ?, ?, ?)
        ", [
            $meetingId,
            $data['tension_id'] ?? null,
            $data['title'],
            $data['item_type']  ?? 'tension',
            $data['outcome']    ?? null,
            $maxOrder + 1,
        ]);
    }

    public function updateAgendaItem(int $id, array $data): int
    {
        return $this->db->execute("
            UPDATE agenda_items
               SET title     = ?,
                   item_type = ?,
                   outcome   = ?
             WHERE id = ?
        ", [
            $data['title'],
            $data['item_type'] ?? 'tension',
            $data['outcome']   ?? null,
            $id,
        ]);
    }

    // ------------------------------------------------------------------
    //  Validierung
    // ------------------------------------------------------------------

    public function validate(array $data): array
    {
        $errors = [];
        if (empty($data['circle_id'])) {
            $errors['circle_id'] = 'Bitte einen Kreis wählen.';
        }
        if (empty($data['held_at'])) {
            $errors['held_at'] = 'Datum und Uhrzeit sind erforderlich.';
        }
        return $errors;
    }
}
