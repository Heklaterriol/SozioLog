<?php
namespace Logbuch\Controller;

use Logbuch\Model\DelegationModel;
use Logbuch\Model\CircleModel;

/**
 * DelegationController
 *
 * Routen:
 *   GET  /delegations               → Übersicht aller Delegationen
 *   GET  /delegations/new           → Formular (ggf. ?from_circle=X vorbelegt)
 *   POST /delegations               → Anlegen
 *   GET  /delegations/{id}          → Detail
 *   GET  /delegations/{id}/edit     → Bearbeiten
 *   POST /delegations/{id}          → Speichern
 *   POST /delegations/{id}/end      → Beenden
 *   POST /delegations/{id}/delete   → Löschen
 */
class DelegationController extends BaseController
{
    private DelegationModel $delegations;

    public function __construct(array $config)
    {
        parent::__construct($config);
        $this->delegations = new DelegationModel();
    }

    // ------------------------------------------------------------------
    //  GET /delegations
    // ------------------------------------------------------------------
    public function index(array $params): void
    {
        $includeEnded = isset($_GET['ended']);
        $delegations  = $this->delegations->findAll($includeEnded);

        $this->render('delegations/index', [
            'pageTitle'    => 'Delegationen',
            'delegations'  => $delegations,
            'includeEnded' => $includeEnded,
            'csrf'         => $this->csrfToken(),
        ]);
    }

    // ------------------------------------------------------------------
    //  GET /delegations/{id}
    // ------------------------------------------------------------------
    public function show(array $params): void
    {
        $delegation = $this->delegations->findById((int) $params['id']);
        if (!$delegation) {
            $this->flash('error', 'Delegation nicht gefunden.');
            $this->redirect('/delegations');
        }

        $roles = $this->delegations->findRolesForCircles(
            $delegation['from_circle'],
            $delegation['to_circle']
        );

        $this->render('delegations/show', [
            'pageTitle'  => 'Delegation: ' . $delegation['from_circle_name'] . ' → ' . $delegation['to_circle_name'],
            'delegation' => $delegation,
            'roles'      => $roles,
            'csrf'       => $this->csrfToken(),
        ]);
    }

    // ------------------------------------------------------------------
    //  GET /delegations/new
    // ------------------------------------------------------------------
    public function create(array $params): void
    {
        $this->requireAdmin();

        $circles = (new CircleModel())->findAll();
        $prefill = [
            'from_circle' => (int) ($_GET['from_circle'] ?? 0),
            'to_circle'   => (int) ($_GET['to_circle']   ?? 0),
            'status'      => 'active',
            'started_at'  => date('Y-m-d'),
        ];

        // Rollen vorausladen wenn beide Kreise bekannt
        $roles = ($prefill['from_circle'] && $prefill['to_circle'])
            ? $this->delegations->findRolesForCircles($prefill['from_circle'], $prefill['to_circle'])
            : [];

        $this->render('delegations/form', [
            'pageTitle'  => 'Neue Delegation',
            'delegation' => $prefill,
            'circles'    => $circles,
            'roles'      => $roles,
            'errors'     => [],
            'csrf'       => $this->csrfToken(),
        ]);
    }

    // ------------------------------------------------------------------
    //  POST /delegations
    // ------------------------------------------------------------------
    public function store(array $params): void
    {
        $this->requireAdmin();
        $this->verifyCsrf();

        $data = $this->collectFormData();
        $errors = $this->delegations->validate($data);

        if ($errors) {
            $circles = (new CircleModel())->findAll();
            $roles   = ($data['from_circle'] && $data['to_circle'])
                ? $this->delegations->findRolesForCircles($data['from_circle'], $data['to_circle'])
                : [];
            $this->render('delegations/form', [
                'pageTitle'  => 'Neue Delegation',
                'delegation' => $data,
                'circles'    => $circles,
                'roles'      => $roles,
                'errors'     => $errors,
                'csrf'       => $this->csrfToken(),
            ]);
            return;
        }

        $id = $this->delegations->create($data);
        $this->flash('success', 'Delegation angelegt.');
        $this->redirect('/delegations/' . $id);
    }

    // ------------------------------------------------------------------
    //  GET /delegations/{id}/edit
    // ------------------------------------------------------------------
    public function edit(array $params): void
    {
        $this->requireAdmin();

        $delegation = $this->delegations->findById((int) $params['id']);
        if (!$delegation) {
            $this->flash('error', 'Delegation nicht gefunden.');
            $this->redirect('/delegations');
        }

        $circles = (new CircleModel())->findAll();
        $roles   = $this->delegations->findRolesForCircles(
            $delegation['from_circle'],
            $delegation['to_circle']
        );

        $this->render('delegations/form', [
            'pageTitle'  => 'Delegation bearbeiten',
            'delegation' => $delegation,
            'circles'    => $circles,
            'roles'      => $roles,
            'errors'     => [],
            'csrf'       => $this->csrfToken(),
        ]);
    }

    // ------------------------------------------------------------------
    //  POST /delegations/{id}
    // ------------------------------------------------------------------
    public function update(array $params): void
    {
        $this->requireAdmin();
        $this->verifyCsrf();

        $id         = (int) $params['id'];
        $delegation = $this->delegations->findById($id);
        if (!$delegation) {
            $this->flash('error', 'Delegation nicht gefunden.');
            $this->redirect('/delegations');
        }

        $data   = $this->collectFormData();
        $errors = $this->delegations->validate(array_merge($data, [
            'from_circle' => $delegation['from_circle'],
            'to_circle'   => $delegation['to_circle'],
        ]));

        if ($errors) {
            $circles = (new CircleModel())->findAll();
            $roles   = $this->delegations->findRolesForCircles(
                $delegation['from_circle'],
                $delegation['to_circle']
            );
            $this->render('delegations/form', [
                'pageTitle'  => 'Delegation bearbeiten',
                'delegation' => array_merge($delegation, $data),
                'circles'    => $circles,
                'roles'      => $roles,
                'errors'     => $errors,
                'csrf'       => $this->csrfToken(),
            ]);
            return;
        }

        $this->delegations->update($id, $data);
        $this->flash('success', 'Delegation aktualisiert.');
        $this->redirect('/delegations/' . $id);
    }

    // ------------------------------------------------------------------
    //  POST /delegations/{id}/end
    // ------------------------------------------------------------------
    public function end(array $params): void
    {
        $this->requireAdmin();
        $this->verifyCsrf();

        $id         = (int) $params['id'];
        $delegation = $this->delegations->findById($id);
        if (!$delegation) {
            $this->flash('error', 'Delegation nicht gefunden.');
            $this->redirect('/delegations');
        }

        $endedAt = $this->inputDate('ended_at') ?? date('Y-m-d');
        $this->delegations->end($id, $endedAt);

        $this->flash('success', 'Delegation beendet.');
        $this->redirect('/delegations/' . $id);
    }

    // ------------------------------------------------------------------
    //  POST /delegations/{id}/delete
    // ------------------------------------------------------------------
    public function delete(array $params): void
    {
        $this->requireAdmin();
        $this->verifyCsrf();

        $id = (int) $params['id'];
        $this->delegations->delete($id);

        $this->flash('success', 'Delegation gelöscht.');
        $this->redirect('/delegations');
    }

    // ------------------------------------------------------------------
    //  GET /api/delegations/roles?from=X&to=Y  (AJAX für Formular)
    // ------------------------------------------------------------------
    public function rolesForCircles(array $params): void
    {
        $from  = (int) ($_GET['from'] ?? 0);
        $to    = (int) ($_GET['to']   ?? 0);
        $roles = ($from && $to) ? $this->delegations->findRolesForCircles($from, $to) : [];
        $this->json($roles);
    }

    // ------------------------------------------------------------------
    //  Hilfsmethode: POST-Daten sammeln
    // ------------------------------------------------------------------
    private function collectFormData(): array
    {
        return [
            'from_circle'   => $this->inputInt('from_circle'),
            'to_circle'     => $this->inputInt('to_circle'),
            'description'   => $this->inputString('description'),
            'notes'         => $this->inputString('notes'),
            'status'        => $this->inputString('status') ?: 'active',
            'started_at'    => $this->inputDate('started_at') ?? date('Y-m-d'),
            'ended_at'      => $this->inputDate('ended_at'),
            'rep_link_role' => $this->inputInt('rep_link_role') ?: null,
            'del_link_role' => $this->inputInt('del_link_role') ?: null,
        ];
    }
}
