<?php
namespace Logbuch\Controller;

use Logbuch\Model\AgreementModel;
use Logbuch\Model\CircleModel;
use Logbuch\Model\MeetingModel;

class AgreementController extends BaseController
{
    private AgreementModel $agreements;

    public function __construct(array $config)
    {
        parent::__construct($config);
        $this->agreements = new AgreementModel();
    }

    /** GET /circles/{cid}/agreements */
    public function index(array $params): void
    {
        $cid    = (int) $params['cid'];
        $circle = (new CircleModel())->findById($cid);
        if (!$circle) { $this->flash('error', 'Kreis nicht gefunden.'); $this->redirect('/circles'); }

        $filter     = $_GET['status'] ?? 'all';
        $activeOnly = ($filter === 'active');
        $agreements = $this->agreements->findByCircle($cid, $activeOnly);

        // Client-seitiger Filter nach Status (wenn nicht 'all' oder 'active')
        if (!in_array($filter, ['all', 'active'], true)) {
            $agreements = array_values(array_filter($agreements, fn($a) => $a['status'] === $filter));
        }

        $this->render('agreements/index', [
            'pageTitle'  => 'Vereinbarungen: ' . $circle['name'],
            'circle'     => $circle,
            'agreements' => $agreements,
            'filter'     => $filter,
            'csrf'       => $this->csrfToken(),
        ]);
    }

    /** GET /agreements/{id} */
    public function show(array $params): void
    {
        $agreement = $this->agreements->findById((int) $params['id']);
        if (!$agreement) { $this->flash('error', 'Vereinbarung nicht gefunden.'); $this->redirect('/circles'); }

        $this->render('agreements/show', [
            'pageTitle' => $agreement['title'],
            'agreement' => $agreement,
            'csrf'      => $this->csrfToken(),
        ]);
    }

    /** GET /agreements/new?circle_id=X[&meeting_id=Y] */
    public function create(array $params): void
    {
        $circles  = (new CircleModel())->findAll();
        $prefill  = [
            'circle_id'  => (int) ($_GET['circle_id']  ?? 0),
            'meeting_id' => (int) ($_GET['meeting_id'] ?? 0),
            'agreed_at'  => date('Y-m-d'),
            'status'     => 'active',
        ];

        // Meetings für den vorgewählten Kreis laden
        $meetings = $prefill['circle_id']
            ? (new MeetingModel())->findByCircle($prefill['circle_id'], 50)
            : [];

        $this->render('agreements/form', [
            'pageTitle' => 'Neue Vereinbarung',
            'agreement' => $prefill,
            'circles'   => $circles,
            'meetings'  => $meetings,
            'errors'    => [],
            'csrf'      => $this->csrfToken(),
        ]);
    }

    /** POST /agreements */
    public function store(array $params): void
    {
        $this->verifyCsrf();

        $user = $this->currentUser();
        $data = [
            'circle_id'   => $this->inputInt('circle_id'),
            'meeting_id'  => $this->inputInt('meeting_id') ?: null,
            'title'       => $this->inputString('title'),
            'driver'      => $this->inputString('driver'),
            'body'        => $this->inputString('body'),
            'agreed_at'   => $this->inputDate('agreed_at') ?? date('Y-m-d'),
            'review_date' => $this->inputDate('review_date'),
            'status'      => $this->inputString('status') ?: 'active',
            'created_by'  => $user['id'] ?? null,
        ];

        $errors = $this->agreements->validate($data);

        if ($errors) {
            $circles  = (new CircleModel())->findAll();
            $meetings = $data['circle_id']
                ? (new MeetingModel())->findByCircle($data['circle_id'], 50)
                : [];
            $this->render('agreements/form', [
                'pageTitle' => 'Neue Vereinbarung',
                'agreement' => $data,
                'circles'   => $circles,
                'meetings'  => $meetings,
                'errors'    => $errors,
                'csrf'      => $this->csrfToken(),
            ]);
            return;
        }

        $id = $this->agreements->create($data);
        $this->flash('success', 'Vereinbarung "' . htmlspecialchars($data['title']) . '" angelegt.');
        $this->redirect('/agreements/' . $id);
    }

    /** GET /agreements/{id}/edit */
    public function edit(array $params): void
    {
        $agreement = $this->agreements->findById((int) $params['id']);
        if (!$agreement) { $this->flash('error', 'Vereinbarung nicht gefunden.'); $this->redirect('/circles'); }

        $circles  = (new CircleModel())->findAll();
        $meetings = (new MeetingModel())->findByCircle($agreement['circle_id'], 50);

        $this->render('agreements/form', [
            'pageTitle' => 'Vereinbarung bearbeiten',
            'agreement' => $agreement,
            'circles'   => $circles,
            'meetings'  => $meetings,
            'errors'    => [],
            'csrf'      => $this->csrfToken(),
        ]);
    }

    /** POST /agreements/{id} */
    public function update(array $params): void
    {
        $this->verifyCsrf();

        $id        = (int) $params['id'];
        $agreement = $this->agreements->findById($id);
        if (!$agreement) { $this->flash('error', 'Vereinbarung nicht gefunden.'); $this->redirect('/circles'); }

        $data = [
            'title'       => $this->inputString('title'),
            'driver'      => $this->inputString('driver'),
            'body'        => $this->inputString('body'),
            'agreed_at'   => $this->inputDate('agreed_at') ?? $agreement['agreed_at'],
            'review_date' => $this->inputDate('review_date'),
            'status'      => $this->inputString('status') ?: 'active',
        ];

        $errors = $this->agreements->validate($data);

        if ($errors) {
            $circles  = (new CircleModel())->findAll();
            $meetings = (new MeetingModel())->findByCircle($agreement['circle_id'], 50);
            $this->render('agreements/form', [
                'pageTitle' => 'Vereinbarung bearbeiten',
                'agreement' => array_merge($agreement, $data),
                'circles'   => $circles,
                'meetings'  => $meetings,
                'errors'    => $errors,
                'csrf'      => $this->csrfToken(),
            ]);
            return;
        }

        $this->agreements->update($id, $data);
        $this->flash('success', 'Vereinbarung aktualisiert.');
        $this->redirect('/agreements/' . $id);
    }
}
