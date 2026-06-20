<?php
namespace Logbuch\Controller;

use Logbuch\Model\TensionModel;
use Logbuch\Model\CircleModel;
use Logbuch\Model\AgreementModel;

class TensionController extends BaseController
{
    private TensionModel $tensions;

    public function __construct(array $config)
    {
        parent::__construct($config);
        $this->tensions = new TensionModel();
    }

    /** GET /circles/{cid}/tensions */
    public function index(array $params): void
    {
        $cid    = (int) $params['cid'];
        $circle = (new CircleModel())->findById($cid);
        if (!$circle) { $this->flash('error', 'Kreis nicht gefunden.'); $this->redirect('/circles'); }

        $status   = $_GET['status'] ?? null;
        $tensions = $this->tensions->findByCircle($cid, $status ?: null);

        $this->render('tensions/index', [
            'pageTitle' => 'Spannungen: ' . $circle['name'],
            'circle'    => $circle,
            'tensions'  => $tensions,
            'filter'    => $status,
            'csrf'      => $this->csrfToken(),
        ]);
    }

    /** GET /tensions/{id} */
    public function show(array $params): void
    {
        $tension = $this->tensions->findById((int) $params['id']);
        if (!$tension) { $this->flash('error', 'Spannung nicht gefunden.'); $this->redirect('/circles'); }

        // Vereinbarungen des Kreises für "Auflösung verknüpfen"
        $agreements = (new AgreementModel())->findByCircle($tension['circle_id'], activeOnly: false);

        $this->render('tensions/show', [
            'pageTitle'  => $tension['title'] ?: 'Spannung #' . $tension['id'],
            'tension'    => $tension,
            'agreements' => $agreements,
            'csrf'       => $this->csrfToken(),
        ]);
    }

    /** GET /tensions/new?circle_id=X */
    public function create(array $params): void
    {
        $perm = $this->permissions();

        $circles = array_values(array_filter(
            (new CircleModel())->findAll(),
            fn($c) => $perm->canRaiseTensionIn((int) $c['id'])
        ));

        if (empty($circles)) {
            $this->flash('error', 'Du hast in keinem Kreis die Berechtigung, Spannungen einzureichen.');
            $this->redirect('/circles');
        }

        $prefillCircleId = (int) ($_GET['circle_id'] ?? 0);
        if ($prefillCircleId && !$perm->canRaiseTensionIn($prefillCircleId)) {
            $prefillCircleId = 0;
        }

        $this->render('tensions/form', [
            'pageTitle' => 'Spannung einreichen',
            'tension'   => ['circle_id' => $prefillCircleId, 'status' => 'open'],
            'circles'   => $circles,
            'errors'    => [],
            'csrf'      => $this->csrfToken(),
        ]);
    }

    /** POST /tensions */
    public function store(array $params): void
    {
        $this->verifyCsrf();
        $perm     = $this->permissions();
        $user     = $this->currentUser();
        $circleId = $this->inputInt('circle_id');

        $this->requireCirclePermission($circleId, fn($c) => $perm->canRaiseTensionIn($c));

        $data = [
            'circle_id'   => $circleId,
            'raised_by'   => $user['id'] ?? null,
            'title'       => $this->inputString('title'),
            'description' => $this->inputString('description'),
            'status'      => 'open',
        ];

        $errors = $this->validateTension($data);

        if ($errors) {
            $circles = array_values(array_filter(
                (new CircleModel())->findAll(),
                fn($c) => $perm->canRaiseTensionIn((int) $c['id'])
            ));
            $this->render('tensions/form', [
                'pageTitle' => 'Spannung einreichen',
                'tension'   => $data,
                'circles'   => $circles,
                'errors'    => $errors,
                'csrf'      => $this->csrfToken(),
            ]);
            return;
        }

        $id = $this->tensions->create($data);
        $this->flash('success', 'Spannung eingereicht.');
        $this->redirect('/tensions/' . $id);
    }

    /** POST /tensions/{id}  — Status + Auflösung aktualisieren */
    public function update(array $params): void
    {
        $this->verifyCsrf();

        $id      = (int) $params['id'];
        $tension = $this->tensions->findById($id);
        if (!$tension) { $this->flash('error', 'Spannung nicht gefunden.'); $this->redirect('/circles'); }

        $this->requireCirclePermission((int) $tension['circle_id'], fn($c) => $this->permissions()->canEditTensionIn($c));

        $data = [
            'title'       => $this->inputString('title')       ?: $tension['title'],
            'description' => $this->inputString('description') ?: $tension['description'],
            'status'      => $this->inputString('status')      ?: $tension['status'],
            'resolved_by' => $this->inputInt('resolved_by')    ?: null,
        ];

        // Wenn resolved_by gesetzt → Status automatisch auf resolved
        if ($data['resolved_by']) {
            $data['status'] = 'resolved';
        }

        $this->tensions->update($id, $data);
        $this->flash('success', 'Spannung aktualisiert.');
        $this->redirect('/tensions/' . $id);
    }

    private function validateTension(array $data): array
    {
        $errors = [];
        if (empty($data['circle_id'])) {
            $errors['circle_id'] = 'Bitte einen Kreis wählen.';
        }
        if (empty(trim($data['title'] ?? '')) && empty(trim($data['description'] ?? ''))) {
            $errors['title'] = 'Titel oder Beschreibung ist erforderlich.';
        }
        return $errors;
    }
}
