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
        $perm = $this->permissions();

        // Nur Kreise zur Auswahl anbieten, in denen Vereinbarungen
        // angelegt werden dürfen (admin: alle, member: eigene Kreise)
        $circles = array_values(array_filter(
            (new CircleModel())->findAll(),
            fn($c) => $perm->canCreateAgreementIn((int) $c['id'])
        ));

        if (empty($circles)) {
            $this->flash('error', 'Du hast in keinem Kreis die Berechtigung, Vereinbarungen anzulegen.');
            $this->redirect('/circles');
        }

        $prefill  = [
            'circle_id'  => (int) ($_GET['circle_id']  ?? 0),
            'meeting_id' => (int) ($_GET['meeting_id'] ?? 0),
            'agreed_at'  => date('Y-m-d'),
            'status'     => 'active',
        ];

        if ($prefill['circle_id'] && !$perm->canCreateAgreementIn($prefill['circle_id'])) {
            $prefill['circle_id'] = 0;
        }

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

        $user     = $this->currentUser();
        $circleId = $this->inputInt('circle_id');

        $this->requireCirclePermission($circleId, fn($c) => $this->permissions()->canCreateAgreementIn($c));

        $data = [
            'circle_id'   => $circleId,
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
            $perm     = $this->permissions();
            $circles  = array_values(array_filter(
                (new CircleModel())->findAll(),
                fn($c) => $perm->canCreateAgreementIn((int) $c['id'])
            ));
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

        $this->requireCirclePermission((int) $agreement['circle_id'], fn($c) => $this->permissions()->canEditAgreementIn($c));

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

        $this->requireCirclePermission((int) $agreement['circle_id'], fn($c) => $this->permissions()->canEditAgreementIn($c));

        $user = $this->currentUser();
        $data = [
            'title'       => $this->inputString('title'),
            'driver'      => $this->inputString('driver'),
            'body'        => $this->inputString('body'),
            'agreed_at'   => $this->inputDate('agreed_at') ?? $agreement['agreed_at'],
            'review_date' => $this->inputDate('review_date'),
            'status'      => $this->inputString('status') ?: 'active',
            // Versionsmeta — wird von AgreementModel::update() genutzt
            'changed_by'  => $user['id'] ?? null,
            'change_note' => $this->inputString('change_note'),
        ];

        $errors = $this->agreements->validate($data);

        if ($errors) {
            $circles  = (new CircleModel())->findAll();
            $meetings = (new MeetingModel())->findByCircle($agreement['circle_id'], 50);
            $versions = $this->agreements->findVersions($id);
            $this->render('agreements/form', [
                'pageTitle' => 'Vereinbarung bearbeiten',
                'agreement' => array_merge($agreement, $data),
                'circles'   => $circles,
                'meetings'  => $meetings,
                'versions'  => $versions,
                'errors'    => $errors,
                'csrf'      => $this->csrfToken(),
            ]);
            return;
        }

        $this->agreements->update($id, $data);
        $this->flash('success', 'Vereinbarung aktualisiert (Version gespeichert).');
        $this->redirect('/agreements/' . $id);
    }

    /** GET /agreements/{id}/versions */
    public function versions(array $params): void
    {
        $id        = (int) $params['id'];
        $agreement = $this->agreements->findById($id);
        if (!$agreement) { $this->flash('error', 'Vereinbarung nicht gefunden.'); $this->redirect('/circles'); }

        $versions = $this->agreements->findVersions($id);

        $this->render('agreements/versions', [
            'pageTitle' => 'Versionshistorie: ' . $agreement['title'],
            'agreement' => $agreement,
            'versions'  => $versions,
            'csrf'      => $this->csrfToken(),
        ]);
    }

    /** GET /agreements/{id}/versions/{version} */
    public function showVersion(array $params): void
    {
        $id        = (int) $params['id'];
        $versionNr = (int) $params['version'];
        $agreement = $this->agreements->findById($id);
        if (!$agreement) { $this->flash('error', 'Vereinbarung nicht gefunden.'); $this->redirect('/circles'); }

        $version = $this->agreements->findVersion($id, $versionNr);
        if (!$version) { $this->flash('error', 'Version nicht gefunden.'); $this->redirect('/agreements/' . $id . '/versions'); }

        $this->render('agreements/version_show', [
            'pageTitle' => 'Version ' . $versionNr . ': ' . $agreement['title'],
            'agreement' => $agreement,
            'version'   => $version,
            'csrf'      => $this->csrfToken(),
        ]);
    }

    /** POST /agreements/{id}/versions/{version}/restore */
    public function restoreVersion(array $params): void
    {
        $this->requireAdmin();
        $this->verifyCsrf();

        $id        = (int) $params['id'];
        $versionNr = (int) $params['version'];
        $user      = $this->currentUser();

        $ok = $this->agreements->restoreVersion($id, $versionNr, $user['id'] ?? null);

        if ($ok) {
            $this->flash('success', "Version {$versionNr} wurde wiederhergestellt. Der vorherige Stand ist als neue Version gespeichert.");
        } else {
            $this->flash('error', 'Version konnte nicht wiederhergestellt werden.');
        }
        $this->redirect('/agreements/' . $id);
    }
}
