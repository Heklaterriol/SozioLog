<?php
namespace Logbuch\Helper;

use Logbuch\Database;

/**
 * Permissions — zentrale Stelle für alle Berechtigungsentscheidungen.
 *
 * Drei Stufen (members.permission_level):
 *   - admin     alles: Kreise, Rollen, Mitglieder verwalten, Export
 *   - member    überall lesen; im EIGENEN Kreis verwalten
 *               (Rollen, Mitglieder, Vereinbarungen, Spannungen,
 *               Protokolle, Personen anlegen)
 *   - readonly  überall nur lesen (Kreisliste + Mitglieder der Kreise)
 *
 * "Eigener Kreis" = Kreise aus circle_memberships für diesen Nutzer.
 *
 * Diese Klasse ist absichtlich der EINZIGE Ort, an dem diese Fragen
 * entschieden werden. Neue Rechte später einfach als weitere
 * can*()-Methode ergänzen, statt is_admin/$_SESSION an vielen
 * Stellen im Code zu prüfen.
 *
 * Verwendung (i.d.R. über BaseController::permissions()):
 *   $perm = new Permissions($currentUser);
 *   if ($perm->canManageCircle($circleId)) { ... }
 */
class Permissions
{
    public const LEVEL_ADMIN    = 'admin';
    public const LEVEL_MEMBER   = 'member';
    public const LEVEL_READONLY = 'readonly';

    private ?array $user;
    private ?array $ownCircleIds = null; // lazy geladen + gecached

    public function __construct(?array $currentUser)
    {
        $this->user = $currentUser;
    }

    // ------------------------------------------------------------------
    //  Grundlegende Stufen-Checks
    // ------------------------------------------------------------------

    public function level(): string
    {
        return $this->user['permission_level'] ?? self::LEVEL_READONLY;
    }

    public function isAdmin(): bool
    {
        return $this->level() === self::LEVEL_ADMIN;
    }

    public function isMember(): bool
    {
        return $this->level() === self::LEVEL_MEMBER;
    }

    public function isReadonly(): bool
    {
        return $this->level() === self::LEVEL_READONLY;
    }

    // ------------------------------------------------------------------
    //  Eigene Kreise
    // ------------------------------------------------------------------

    /**
     * IDs der Kreise, denen der aktuelle Nutzer über circle_memberships
     * direkt zugeordnet ist ("eigener Kreis").
     */
    public function ownCircleIds(): array
    {
        if ($this->ownCircleIds !== null) {
            return $this->ownCircleIds;
        }
        if (empty($this->user['id'])) {
            return $this->ownCircleIds = [];
        }

        $rows = Database::getInstance()->fetchAll(
            'SELECT circle_id FROM circle_memberships WHERE member_id = ?',
            [$this->user['id']]
        );
        return $this->ownCircleIds = array_map(fn($r) => (int) $r['circle_id'], $rows);
    }

    public function belongsToCircle(int $circleId): bool
    {
        return in_array($circleId, $this->ownCircleIds(), true);
    }

    // ------------------------------------------------------------------
    //  Globale Verwaltungsrechte (nur admin)
    // ------------------------------------------------------------------

    public function canCreateCircle(): bool
    {
        return $this->isAdmin();
    }

    public function canManageDelegations(): bool
    {
        return $this->isAdmin();
    }

    public function canExport(): bool
    {
        return $this->isAdmin();
    }

    public function canManageMembers(): bool
    {
        // Globale Mitgliederverwaltung (Berechtigungsstufe ändern,
        // Person löschen) bleibt Admin-Sache.
        return $this->isAdmin();
    }

    /**
     * Die Berechtigungsstufe (admin/member/readonly) einer Person ändern
     * darf ausschließlich ein Admin — sonst könnten sich Mitglieder
     * gegenseitig hochstufen.
     */
    public function canChangePermissionLevel(): bool
    {
        return $this->isAdmin();
    }

    // ------------------------------------------------------------------
    //  Kreisbezogene Rechte (admin ODER eigener Kreis, wenn member)
    // ------------------------------------------------------------------

    public function canManageRolesIn(int $circleId): bool
    {
        return $this->isAdmin() || ($this->isMember() && $this->belongsToCircle($circleId));
    }

    public function canManageMembersIn(int $circleId): bool
    {
        return $this->isAdmin() || ($this->isMember() && $this->belongsToCircle($circleId));
    }

    /**
     * Darf der aktuelle Nutzer die Detailseite EINER BESTIMMTEN Person
     * bearbeiten (Rollen/Kreiszuordnung ändern)?
     * admin: immer. member: nur wenn die Zielperson mindestens einen
     * Kreis mit dem eigenen Kreis des Nutzers teilt.
     *
     * @param int[] $targetMemberCircleIds  Kreis-IDs der Zielperson (circle_memberships)
     */
    public function canManageMemberRecord(array $targetMemberCircleIds): bool
    {
        if ($this->isAdmin()) {
            return true;
        }
        if (!$this->isMember()) {
            return false;
        }
        return (bool) array_intersect($this->ownCircleIds(), $targetMemberCircleIds);
    }

    public function canCreatePersonIn(int $circleId): bool
    {
        return $this->isAdmin() || ($this->isMember() && $this->belongsToCircle($circleId));
    }

    public function canCreateAgreementIn(int $circleId): bool
    {
        return $this->isAdmin() || ($this->isMember() && $this->belongsToCircle($circleId));
    }

    public function canEditAgreementIn(int $circleId): bool
    {
        return $this->isAdmin() || ($this->isMember() && $this->belongsToCircle($circleId));
    }

    public function canRaiseTensionIn(int $circleId): bool
    {
        return $this->isAdmin() || ($this->isMember() && $this->belongsToCircle($circleId));
    }

    public function canEditTensionIn(int $circleId): bool
    {
        return $this->isAdmin() || ($this->isMember() && $this->belongsToCircle($circleId));
    }

    /**
     * "Schreiben" im Sinne von Protokollen/Meetings im Kreis
     * (Meeting anlegen/bearbeiten, Agendapunkte hinzufügen).
     */
    public function canWriteMeetingsIn(int $circleId): bool
    {
        return $this->isAdmin() || ($this->isMember() && $this->belongsToCircle($circleId));
    }

    // ------------------------------------------------------------------
    //  Lesen — laut Anforderung dürfen ALLE Stufen Kreisliste und
    //  Mitglieder der Kreise sehen. Hier trotzdem als explizite
    //  Methoden, damit das im Code dokumentiert und erweiterbar bleibt.
    // ------------------------------------------------------------------

    public function canViewCircleList(): bool
    {
        return true; // admin, member, readonly — alle dürfen lesen
    }

    public function canViewCircleMembers(): bool
    {
        return true;
    }
}
