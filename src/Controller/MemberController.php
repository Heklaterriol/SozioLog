<?php
namespace Logbuch\Controller;

use Logbuch\Model\MemberModel;
use Logbuch\Model\CircleModel;
use Logbuch\Model\RoleModel;

class MemberController extends BaseController
{
    private MemberModel $members;

    public function __construct(array $config)
    {
        parent::__construct($config);
        $this->members = new MemberModel();
    }

    /** GET /members */
    public function index(array $params): void
    {
        $members         = $this->members->findAll();
        $circleIdsByUser = $this->members->findAllCircleIdsGrouped();

        $this->render('members/index', [
            'pageTitle'       => 'Mitglieder',
            'members'         => $members,
            'circleIdsByUser' => $circleIdsByUser,
            'csrf'            => $this->csrfToken(),
        ]);
    }

    /** GET /members/{id} */
    public function show(array $params): void
    {
        $member = $this->members->findById((int) $params['id']);
        if (!$member) { $this->flash('error', 'Person nicht gefunden.'); $this->redirect('/members'); }

        $memberCircleIds = $this->members->findCircleIds($member['id']);
        $canManage       = $this->permissions()->canManageMemberRecord($memberCircleIds);

        $this->render('members/show', [
            'pageTitle'         => $member['name'],
            'member'            => $member,
            'roles'             => $this->members->findRoles($member['id']),
            'circleMemberships' => $this->members->findCircleMemberships($member['id']),
            'allCircles'        => $canManage ? (new CircleModel())->findAll() : [],
            'allRoles'          => $canManage ? (new RoleModel())->findAllWithCircle() : [],
            'canManage'         => $canManage,
            'csrf'              => $this->csrfToken(),
        ]);
    }

    /** GET /members/new */
    public function create(array $params): void
    {
        $perm = $this->permissions();
        if (!$perm->isAdmin() && !$perm->isMember()) {
            $this->flash('error', 'Diese Aktion erfordert Verwaltungsrechte.');
            $this->redirect('/members');
        }

        // Mitglieder (nicht-Admin) dürfen nur Personen für ihre eigenen
        // Kreise anlegen — die Person wird automatisch zugeordnet.
        $ownCircleIds = $perm->ownCircleIds();
        if (!$perm->isAdmin() && empty($ownCircleIds)) {
            $this->flash('error', 'Du bist noch keinem Kreis zugeordnet und kannst daher keine Person anlegen.');
            $this->redirect('/members');
        }

        $this->render('members/form', [
            'pageTitle'    => 'Person anlegen',
            'member'       => [],
            'errors'       => [],
            'isAdmin'      => $perm->isAdmin(),
            'allCircles'   => $perm->isAdmin() ? (new CircleModel())->findAll() : [],
            'ownCircles'   => $perm->isAdmin() ? [] : (new CircleModel())->findByIds($ownCircleIds),
            'csrf'         => $this->csrfToken(),
        ]);
    }

    /** POST /members */
    public function store(array $params): void
    {
        $this->verifyCsrf();
        $perm = $this->permissions();

        if (!$perm->isAdmin() && !$perm->isMember()) {
            $this->flash('error', 'Diese Aktion erfordert Verwaltungsrechte.');
            $this->redirect('/members');
        }

        $ownCircleIds = $perm->ownCircleIds();
        if (!$perm->isAdmin() && empty($ownCircleIds)) {
            $this->flash('error', 'Du bist noch keinem Kreis zugeordnet und kannst daher keine Person anlegen.');
            $this->redirect('/members');
        }

        $data = [
            'name'             => $this->inputString('name'),
            'email'            => strtolower(trim($this->inputString('email'))),
            // Berechtigungsstufe darf nur ein Admin vergeben — Mitglieder
            // legen immer auf der Standardstufe 'member' an.
            'permission_level' => $perm->isAdmin()
                ? ($this->inputString('permission_level') ?: 'member')
                : 'member',
        ];

        $errors = $this->validateMember($data);

        // Kreiszuordnung bestimmen
        if ($perm->isAdmin()) {
            $circleIds = array_filter(array_map('intval', (array) ($_POST['circle_ids'] ?? [])));
        } else {
            // Mitglied: automatisch dem/den eigenen Kreis(en) zuordnen.
            // Falls das Mitglied mehrere eigene Kreise hat, kann es per
            // Checkbox einschränken — Default ist "alle eigenen Kreise".
            $selected  = array_filter(array_map('intval', (array) ($_POST['circle_ids'] ?? [])));
            $circleIds = $selected ?: $ownCircleIds;
            // Nicht erlauben, Personen Kreisen außerhalb der eigenen zuzuordnen
            $circleIds = array_values(array_intersect($circleIds, $ownCircleIds));
            if (empty($circleIds)) {
                $circleIds = $ownCircleIds;
            }
        }

        if ($errors) {
            $this->render('members/form', [
                'pageTitle'  => 'Person anlegen',
                'member'     => $data,
                'errors'     => $errors,
                'isAdmin'    => $perm->isAdmin(),
                'allCircles' => $perm->isAdmin() ? (new CircleModel())->findAll() : [],
                'ownCircles' => $perm->isAdmin() ? [] : (new CircleModel())->findByIds($ownCircleIds),
                'csrf'       => $this->csrfToken(),
            ]);
            return;
        }

        $id = $this->members->create($data);
        $this->members->setCircleMemberships($id, $circleIds);

        $this->flash('success', 'Person "' . htmlspecialchars($data['name']) . '" angelegt.');
        $this->redirect('/members/' . $id);
    }

    /** GET /members/{id}/edit */
    public function edit(array $params): void
    {
        $member = $this->members->findById((int) $params['id']);
        if (!$member) { $this->flash('error', 'Person nicht gefunden.'); $this->redirect('/members'); }

        $memberCircleIds = $this->members->findCircleIds($member['id']);
        $perm            = $this->permissions();

        if (!$perm->canManageMemberRecord($memberCircleIds)) {
            $this->flash('error', 'Dafür fehlen dir die Berechtigungen.');
            $this->redirect('/members/' . $member['id']);
        }

        $this->render('members/form', [
            'pageTitle' => 'Person bearbeiten: ' . $member['name'],
            'member'    => $member,
            'errors'    => [],
            'isAdmin'   => $perm->isAdmin(),
            'csrf'      => $this->csrfToken(),
        ]);
    }

    /** POST /members/{id} */
    public function update(array $params): void
    {
        $this->verifyCsrf();

        $id     = (int) $params['id'];
        $member = $this->members->findById($id);
        if (!$member) { $this->flash('error', 'Person nicht gefunden.'); $this->redirect('/members'); }

        $memberCircleIds = $this->members->findCircleIds($id);
        $perm            = $this->permissions();

        if (!$perm->canManageMemberRecord($memberCircleIds)) {
            $this->flash('error', 'Dafür fehlen dir die Berechtigungen.');
            $this->redirect('/members/' . $id);
        }

        $data = [
            'name'     => $this->inputString('name'),
            'email'    => strtolower(trim($this->inputString('email'))),
            // Berechtigungsstufe darf nur ein Admin ändern — sonst bleibt
            // die bisherige Stufe der Person unverändert.
            'permission_level' => $perm->canChangePermissionLevel()
                ? ($this->inputString('permission_level') ?: $member['permission_level'])
                : $member['permission_level'],
        ];

        $errors = $this->validateMember($data, $id);

        if ($errors) {
            $this->render('members/form', [
                'pageTitle' => 'Person bearbeiten',
                'member'    => array_merge($member, $data),
                'errors'    => $errors,
                'isAdmin'   => $perm->isAdmin(),
                'csrf'      => $this->csrfToken(),
            ]);
            return;
        }

        $this->members->update($id, $data);

        $this->flash('success', 'Person aktualisiert.');
        $this->redirect('/members/' . $id);
    }

    /** POST /members/{id}/circles — Kreiszuordnung speichern */
    public function updateCircles(array $params): void
    {
        $this->verifyCsrf();

        $id     = (int) $params['id'];
        $member = $this->members->findById($id);
        if (!$member) { $this->flash('error', 'Person nicht gefunden.'); $this->redirect('/members'); }

        $memberCircleIds = $this->members->findCircleIds($id);
        $perm            = $this->permissions();

        if (!$perm->canManageMemberRecord($memberCircleIds)) {
            $this->flash('error', 'Dafür fehlen dir die Berechtigungen.');
            $this->redirect('/members/' . $id);
        }

        $selected = array_filter(array_map('intval', (array) ($_POST['circle_ids'] ?? [])));

        if (!$perm->isAdmin()) {
            // Mitglieder dürfen Kreiszuordnungen nur innerhalb ihrer
            // eigenen Kreise ändern — fremde Zuordnungen bleiben erhalten.
            $own              = $perm->ownCircleIds();
            $keepForeign      = array_diff($memberCircleIds, $own);
            $selectedWithinOwn = array_intersect($selected, $own);
            $selected          = array_values(array_unique(array_merge($keepForeign, $selectedWithinOwn)));
        }

        $this->members->setCircleMemberships($id, $selected);

        $this->flash('success', 'Kreiszugehörigkeit aktualisiert.');
        $this->redirect('/members/' . $id);
    }

    /** POST /members/{id}/roles — Rolle zuweisen */
    public function assignRole(array $params): void
    {
        $this->verifyCsrf();

        $id     = (int) $params['id'];
        $member = $this->members->findById($id);
        if (!$member) { $this->flash('error', 'Person nicht gefunden.'); $this->redirect('/members'); }

        $memberCircleIds = $this->members->findCircleIds($id);
        $perm            = $this->permissions();

        if (!$perm->canManageMemberRecord($memberCircleIds)) {
            $this->flash('error', 'Dafür fehlen dir die Berechtigungen.');
            $this->redirect('/members/' . $id);
        }

        $roleId = $this->inputInt('role_id');
        if (!$roleId) {
            $this->flash('error', 'Bitte eine Rolle auswählen.');
            $this->redirect('/members/' . $id);
        }

        $role = (new RoleModel())->findById($roleId);
        if (!$role) {
            $this->flash('error', 'Rolle nicht gefunden.');
            $this->redirect('/members/' . $id);
        }

        // Mitglieder dürfen nur Rollen aus dem eigenen Kreis zuweisen
        if (!$perm->isAdmin() && !$perm->belongsToCircle((int) $role['circle_id'])) {
            $this->flash('error', 'Diese Rolle gehört nicht zu deinem Kreis.');
            $this->redirect('/members/' . $id);
        }

        $electedUntil = $this->inputDate('elected_until');
        $this->members->assignRole($id, $roleId, $electedUntil);

        $this->flash('success', 'Rolle "' . htmlspecialchars($role['name']) . '" zugewiesen.');
        $this->redirect('/members/' . $id);
    }

    /** POST /members/{id}/roles/{assignmentId}/end — Rollenzuweisung beenden */
    public function endRole(array $params): void
    {
        $this->verifyCsrf();

        $id           = (int) $params['id'];
        $assignmentId = (int) $params['assignmentId'];
        $member       = $this->members->findById($id);
        if (!$member) { $this->flash('error', 'Person nicht gefunden.'); $this->redirect('/members'); }

        $memberCircleIds = $this->members->findCircleIds($id);
        $perm            = $this->permissions();

        if (!$perm->canManageMemberRecord($memberCircleIds)) {
            $this->flash('error', 'Dafür fehlen dir die Berechtigungen.');
            $this->redirect('/members/' . $id);
        }

        // Zusätzlich sicherstellen: die zu entziehende Rolle selbst liegt
        // im eigenen Kreis (relevant wenn die Person mehreren Kreisen
        // angehört, aber nur einer davon der eigene Kreis ist).
        if (!$perm->isAdmin()) {
            $assignment = $this->members->findRoleAssignmentCircle($assignmentId);
            if (!$assignment || !$perm->belongsToCircle($assignment)) {
                $this->flash('error', 'Diese Rolle gehört nicht zu deinem Kreis.');
                $this->redirect('/members/' . $id);
            }
        }

        $this->members->endRoleAssignment($id, $assignmentId);

        $this->flash('success', 'Rolle entzogen.');
        $this->redirect('/members/' . $id);
    }

    private function validateMember(array $data, ?int $excludeId = null): array
    {
        $errors = [];

        if (empty(trim($data['name'] ?? ''))) {
            $errors['name'] = 'Name ist erforderlich.';
        }

        if (empty(trim($data['email'] ?? ''))) {
            $errors['email'] = 'E-Mail ist erforderlich.';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Ungültige E-Mail-Adresse.';
        } elseif ($this->members->emailExists($data['email'], $excludeId)) {
            $errors['email'] = 'Diese E-Mail-Adresse ist bereits vergeben.';
        }

        return $errors;
    }
}
