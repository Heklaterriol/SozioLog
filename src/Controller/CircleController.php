<?php
namespace Logbuch\Controller;

use Logbuch\Model\CircleModel;
use Logbuch\Model\RoleModel;
use Logbuch\Model\AgreementModel;
use Logbuch\Model\MeetingModel;
use Logbuch\Model\TensionModel;
use Logbuch\Model\DelegationModel;

/**
 * CircleController — CRUD für Kreise
 */
class CircleController extends BaseController
{
    private CircleModel $circles;

    public function __construct(array $config)
    {
        parent::__construct($config);
        $this->circles = new CircleModel();
    }

    /** GET /circles */
    public function index(array $params): void
    {
        $tree = $this->circles->getTree();

        $this->render('circles/index', [
            'pageTitle' => 'Kreise',
            'tree'      => $tree,
            'csrf'      => $this->csrfToken(),
        ]);
    }

    /** GET /circles/{id} */
    public function show(array $params): void
    {
        $circle = $this->circles->findById((int) $params['id']);
        if (!$circle) {
            $this->flash('error', 'Kreis nicht gefunden.');
            $this->redirect('/circles');
        }

        $children = $this->circles->findChildren($circle['id']);
        $members  = $this->circles->findMembers($circle['id']);

        // Lesend-Berechtigte sehen nur die Kreisstruktur + Mitglieder —
        // Rollen, Vereinbarungen, Meetings, Spannungen, Delegationen
        // werden für sie gar nicht erst geladen.
        if ($this->permissions()->isReadonly()) {
            $this->render('circles/show', [
                'pageTitle' => $circle['name'],
                'circle'    => $circle,
                'children'  => $children,
                'members'   => $members,
                'csrf'      => $this->csrfToken(),
            ]);
            return;
        }

        $roles       = (new RoleModel())->findByCircle($circle['id']);
        $agreements  = (new AgreementModel())->findByCircle($circle['id'], activeOnly: true);
        $meetings    = (new MeetingModel())->findByCircle($circle['id'], limit: 5);
        $tensions    = (new TensionModel())->findByCircle($circle['id'], status: 'open');

        $this->render('circles/show', [
            'pageTitle'  => $circle['name'],
            'circle'     => $circle,
            'roles'      => $roles,
            'agreements' => $agreements,
            'meetings'   => $meetings,
            'tensions'   => $tensions,
            'children'   => $children,
            'members'    => $members,
            'csrf'       => $this->csrfToken(),
        ]);
    }

    /** GET /circles/new */
    public function create(array $params): void
    {
        $this->requireAdmin();

        $allCircles = $this->circles->findAll();

        $this->render('circles/form', [
            'pageTitle'  => 'Neuer Kreis',
            'circle'     => [],
            'allCircles' => $allCircles,
            'errors'     => [],
            'csrf'       => $this->csrfToken(),
        ]);
    }

    /** POST /circles */
    public function store(array $params): void
    {
        $this->requireAdmin();
        $this->verifyCsrf();

        $data = [
            'name'      => $this->inputString('name'),
            'parent_id' => $this->inputInt('parent_id') ?: null,
            'driver'    => $this->inputString('driver'),
            'domain'    => $this->inputString('domain'),
            'purpose'   => $this->inputString('purpose'),
            'status'    => 'active',
        ];

        $errors = $this->circles->validate($data);

        if ($errors) {
            $this->render('circles/form', [
                'pageTitle'  => 'Neuer Kreis',
                'circle'     => $data,
                'allCircles' => $this->circles->findAll(),
                'errors'     => $errors,
                'csrf'       => $this->csrfToken(),
            ]);
            return;
        }

        $id = $this->circles->create($data);
        $this->flash('success', 'Kreis "' . htmlspecialchars($data['name']) . '" angelegt.');
        $this->redirect('/circles/' . $id);
    }

    /** GET /circles/{id}/edit */
    public function edit(array $params): void
    {
        $this->requireAdmin();

        $circle = $this->circles->findById((int) $params['id']);
        if (!$circle) {
            $this->flash('error', 'Kreis nicht gefunden.');
            $this->redirect('/circles');
        }

        // Aktuellen Kreis aus Überkreis-Liste ausschließen
        $allCircles = array_filter(
            $this->circles->findAll(includeArchived: true),
            fn($c) => $c['id'] !== $circle['id']
        );

        $this->render('circles/form', [
            'pageTitle'  => 'Kreis bearbeiten: ' . $circle['name'],
            'circle'     => $circle,
            'allCircles' => $allCircles,
            'errors'     => [],
            'csrf'       => $this->csrfToken(),
        ]);
    }

    /** POST /circles/{id} */
    public function update(array $params): void
    {
        $this->requireAdmin();
        $this->verifyCsrf();

        $id = (int) $params['id'];
        $circle = $this->circles->findById($id);
        if (!$circle) {
            $this->flash('error', 'Kreis nicht gefunden.');
            $this->redirect('/circles');
        }

        $data = [
            'id'        => $id,
            'name'      => $this->inputString('name'),
            'parent_id' => $this->inputInt('parent_id') ?: null,
            'driver'    => $this->inputString('driver'),
            'domain'    => $this->inputString('domain'),
            'purpose'   => $this->inputString('purpose'),
            'status'    => $this->inputString('status') ?: 'active',
        ];

        $errors = $this->circles->validate($data);

        if ($errors) {
            $allCircles = array_filter(
                $this->circles->findAll(includeArchived: true),
                fn($c) => $c['id'] !== $id
            );
            $this->render('circles/form', [
                'pageTitle'  => 'Kreis bearbeiten',
                'circle'     => $data,
                'allCircles' => $allCircles,
                'errors'     => $errors,
                'csrf'       => $this->csrfToken(),
            ]);
            return;
        }

        $this->circles->update($id, $data);
        $this->flash('success', 'Kreis aktualisiert.');
        $this->redirect('/circles/' . $id);
    }

    /** POST /circles/{id}/delete */
    public function delete(array $params): void
    {
        $this->requireAdmin();
        $this->verifyCsrf();

        $id     = (int) $params['id'];
        $circle = $this->circles->findById($id);

        if (!$circle) {
            $this->flash('error', 'Kreis nicht gefunden.');
            $this->redirect('/circles');
        }

        $this->circles->archive($id);
        $this->flash('success', '"' . htmlspecialchars($circle['name']) . '" archiviert.');
        $this->redirect('/circles');
    }
}
