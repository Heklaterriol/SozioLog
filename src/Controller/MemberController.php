<?php
namespace Logbuch\Controller;

use Logbuch\Model\MemberModel;

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
        $members = $this->members->findAll();

        $this->render('members/index', [
            'pageTitle' => 'Mitglieder',
            'members'   => $members,
            'csrf'      => $this->csrfToken(),
        ]);
    }

    /** GET /members/{id} */
    public function show(array $params): void
    {
        $member = $this->members->findById((int) $params['id']);
        if (!$member) { $this->flash('error', 'Person nicht gefunden.'); $this->redirect('/members'); }

        $roles = $this->members->findRoles($member['id']);

        $this->render('members/show', [
            'pageTitle' => $member['name'],
            'member'    => $member,
            'roles'     => $roles,
            'csrf'      => $this->csrfToken(),
        ]);
    }

    /** GET /members/new */
    public function create(array $params): void
    {
        $this->requireAdmin();

        $this->render('members/form', [
            'pageTitle' => 'Person anlegen',
            'member'    => [],
            'errors'    => [],
            'csrf'      => $this->csrfToken(),
        ]);
    }

    /** POST /members */
    public function store(array $params): void
    {
        $this->requireAdmin();
        $this->verifyCsrf();

        $data = [
            'name'     => $this->inputString('name'),
            'email'    => strtolower(trim($this->inputString('email'))),
            'password' => $this->inputString('password'),
            'is_admin' => !empty($_POST['is_admin']),
        ];

        $errors = $this->validateMember($data);

        if ($errors) {
            $this->render('members/form', [
                'pageTitle' => 'Person anlegen',
                'member'    => $data,
                'errors'    => $errors,
                'csrf'      => $this->csrfToken(),
            ]);
            return;
        }

        $id = $this->members->create($data);
        $this->flash('success', 'Person "' . htmlspecialchars($data['name']) . '" angelegt.');
        $this->redirect('/members/' . $id);
    }

    /** GET /members/{id}/edit */
    public function edit(array $params): void
    {
        $this->requireAdmin();

        $member = $this->members->findById((int) $params['id']);
        if (!$member) { $this->flash('error', 'Person nicht gefunden.'); $this->redirect('/members'); }

        $this->render('members/form', [
            'pageTitle' => 'Person bearbeiten: ' . $member['name'],
            'member'    => $member,
            'errors'    => [],
            'csrf'      => $this->csrfToken(),
        ]);
    }

    /** POST /members/{id} */
    public function update(array $params): void
    {
        $this->requireAdmin();
        $this->verifyCsrf();

        $id     = (int) $params['id'];
        $member = $this->members->findById($id);
        if (!$member) { $this->flash('error', 'Person nicht gefunden.'); $this->redirect('/members'); }

        $data = [
            'name'     => $this->inputString('name'),
            'email'    => strtolower(trim($this->inputString('email'))),
            'password' => $this->inputString('password'),   // leer = nicht ändern
            'is_admin' => !empty($_POST['is_admin']),
        ];

        $errors = $this->validateMember($data, $id);

        if ($errors) {
            $this->render('members/form', [
                'pageTitle' => 'Person bearbeiten',
                'member'    => array_merge($member, $data),
                'errors'    => $errors,
                'csrf'      => $this->csrfToken(),
            ]);
            return;
        }

        $this->members->update($id, $data);

        // Passwort nur ändern wenn angegeben
        if ($data['password'] !== '') {
            $this->members->updatePassword($id, $data['password']);
        }

        $this->flash('success', 'Person aktualisiert.');
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

        // Nur bei Neuanlage: Passwort ist Pflicht
        if ($excludeId === null && empty($data['password'])) {
            $errors['password'] = 'Passwort ist erforderlich.';
        }

        if (!empty($data['password']) && strlen($data['password']) < 8) {
            $errors['password'] = 'Passwort muss mindestens 8 Zeichen haben.';
        }

        return $errors;
    }
}
