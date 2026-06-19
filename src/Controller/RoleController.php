<?php
namespace Logbuch\Controller;

use Logbuch\Model\RoleModel;
use Logbuch\Model\CircleModel;
use Logbuch\Model\MemberModel;

class RoleController extends BaseController
{
    private RoleModel $roles;

    public function __construct(array $config)
    {
        parent::__construct($config);
        $this->roles = new RoleModel();
    }

    /** GET /circles/{cid}/roles */
    public function index(array $params): void
    {
        $cid    = (int) $params['cid'];
        $circle = (new CircleModel())->findById($cid);
        if (!$circle) { $this->flash('error', 'Kreis nicht gefunden.'); $this->redirect('/circles'); }

        $roles = $this->roles->findByCircle($cid);

        $this->render('roles/index', [
            'pageTitle' => 'Rollen: ' . $circle['name'],
            'circle'    => $circle,
            'roles'     => $roles,
            'csrf'      => $this->csrfToken(),
        ]);
    }

    /** GET /roles/{id} */
    public function show(array $params): void
    {
        $role = $this->roles->findById((int) $params['id']);
        if (!$role) { $this->flash('error', 'Rolle nicht gefunden.'); $this->redirect('/circles'); }

        $history = $this->roles->findAssignmentHistory($role['id']);
        $members = (new MemberModel())->findAll();

        // Aktuelle Besetzung (end_date IS NULL)
        $current = array_values(array_filter($history, fn($h) => $h['end_date'] === null));

        $this->render('roles/show', [
            'pageTitle' => $role['name'],
            'role'      => $role,
            'history'   => $history,
            'current'   => $current[0] ?? null,
            'members'   => $members,
            'csrf'      => $this->csrfToken(),
        ]);
    }

    /** GET /circles/{cid}/roles/new */
    public function create(array $params): void
    {
        $this->requireAdmin();
        $cid    = (int) $params['cid'];
        $circle = (new CircleModel())->findById($cid);
        if (!$circle) { $this->flash('error', 'Kreis nicht gefunden.'); $this->redirect('/circles'); }

        $this->render('roles/form', [
            'pageTitle' => 'Neue Rolle in: ' . $circle['name'],
            'role'      => ['circle_id' => $cid],
            'circle'    => $circle,
            'errors'    => [],
            'csrf'      => $this->csrfToken(),
        ]);
    }

    /** POST /circles/{cid}/roles */
    public function store(array $params): void
    {
        $this->requireAdmin();
        $this->verifyCsrf();

        $cid    = (int) $params['cid'];
        $circle = (new CircleModel())->findById($cid);
        if (!$circle) { $this->flash('error', 'Kreis nicht gefunden.'); $this->redirect('/circles'); }

        // Accountabilities als Array aus zeilenweisem Textarea-Input
        $accRaw = array_filter(
            array_map('trim', explode("\n", $this->inputString('accountabilities_text'))),
            fn($l) => $l !== ''
        );

        $data = [
            'circle_id'       => $cid,
            'name'            => $this->inputString('name'),
            'domain'          => $this->inputString('domain'),
            'purpose'         => $this->inputString('purpose'),
            'accountabilities'=> array_values($accRaw),
            'role_type'       => $this->inputString('role_type') ?: 'general',
            'is_elected'      => !empty($_POST['is_elected']),
        ];

        $errors = $this->validateRole($data);

        if ($errors) {
            $this->render('roles/form', [
                'pageTitle' => 'Neue Rolle',
                'role'      => $data,
                'circle'    => $circle,
                'errors'    => $errors,
                'csrf'      => $this->csrfToken(),
            ]);
            return;
        }

        $id = $this->roles->create($data);
        $this->flash('success', 'Rolle "' . htmlspecialchars($data['name']) . '" angelegt.');
        $this->redirect('/roles/' . $id);
    }

    /** GET /roles/{id}/edit */
    public function edit(array $params): void
    {
        $this->requireAdmin();
        $role = $this->roles->findById((int) $params['id']);
        if (!$role) { $this->flash('error', 'Rolle nicht gefunden.'); $this->redirect('/circles'); }

        $circle = (new CircleModel())->findById($role['circle_id']);

        // JSON → Textarea-Text
        $acc = json_decode($role['accountabilities'] ?? '[]', true) ?? [];
        $role['accountabilities_text'] = implode("\n", $acc);

        $this->render('roles/form', [
            'pageTitle' => 'Rolle bearbeiten: ' . $role['name'],
            'role'      => $role,
            'circle'    => $circle,
            'errors'    => [],
            'csrf'      => $this->csrfToken(),
        ]);
    }

    /** POST /roles/{id} */
    public function update(array $params): void
    {
        $this->requireAdmin();
        $this->verifyCsrf();

        $id   = (int) $params['id'];
        $role = $this->roles->findById($id);
        if (!$role) { $this->flash('error', 'Rolle nicht gefunden.'); $this->redirect('/circles'); }

        $accRaw = array_filter(
            array_map('trim', explode("\n", $this->inputString('accountabilities_text'))),
            fn($l) => $l !== ''
        );

        $data = [
            'name'            => $this->inputString('name'),
            'domain'          => $this->inputString('domain'),
            'purpose'         => $this->inputString('purpose'),
            'accountabilities'=> array_values($accRaw),
            'role_type'       => $this->inputString('role_type') ?: 'general',
            'is_elected'      => !empty($_POST['is_elected']),
        ];

        $errors = $this->validateRole($data);

        if ($errors) {
            $circle = (new CircleModel())->findById($role['circle_id']);
            $data['id']                    = $id;
            $data['accountabilities_text'] = $this->inputString('accountabilities_text');
            $this->render('roles/form', [
                'pageTitle' => 'Rolle bearbeiten',
                'role'      => $data,
                'circle'    => $circle,
                'errors'    => $errors,
                'csrf'      => $this->csrfToken(),
            ]);
            return;
        }

        $this->roles->update($id, $data);
        $this->flash('success', 'Rolle aktualisiert.');
        $this->redirect('/roles/' . $id);
    }

    /** POST /roles/{id}/assign */
    public function assign(array $params): void
    {
        $this->requireAdmin();
        $this->verifyCsrf();

        $id        = (int) $params['id'];
        $memberId  = $this->inputInt('member_id');
        $startDate = $this->inputDate('start_date') ?? date('Y-m-d');
        $until     = $this->inputDate('elected_until');

        if (!$memberId) {
            $this->flash('error', 'Bitte eine Person auswählen.');
            $this->redirect('/roles/' . $id);
        }

        $this->roles->assign($id, $memberId, $startDate, $until);
        $this->flash('success', 'Rollenbesetzung gespeichert.');
        $this->redirect('/roles/' . $id);
    }

    private function validateRole(array $data): array
    {
        $errors = [];
        if (empty(trim($data['name'] ?? ''))) {
            $errors['name'] = 'Rollenname ist erforderlich.';
        }
        $valid = ['general','facilitator','secretary','rep_link','delegate_link','elected'];
        if (!in_array($data['role_type'] ?? '', $valid, true)) {
            $errors['role_type'] = 'Ungültiger Rollentyp.';
        }
        return $errors;
    }
}
