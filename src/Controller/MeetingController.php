<?php
namespace Logbuch\Controller;

use Logbuch\Model\MeetingModel;
use Logbuch\Model\CircleModel;
use Logbuch\Model\MemberModel;
use Logbuch\Model\TensionModel;

class MeetingController extends BaseController
{
    private MeetingModel $meetings;

    public function __construct(array $config)
    {
        parent::__construct($config);
        $this->meetings = new MeetingModel();
    }

    /** GET /circles/{cid}/meetings */
    public function index(array $params): void
    {
        $cid    = (int) $params['cid'];
        $circle = (new CircleModel())->findById($cid);
        if (!$circle) { $this->flash('error', 'Kreis nicht gefunden.'); $this->redirect('/circles'); }

        $type     = $_GET['type'] ?? null;
        $meetings = $this->meetings->findByCircle($cid, 50, $type);

        $this->render('meetings/index', [
            'pageTitle' => 'Meetings: ' . $circle['name'],
            'circle'    => $circle,
            'meetings'  => $meetings,
            'filter'    => $type,
            'csrf'      => $this->csrfToken(),
        ]);
    }

    /** GET /meetings/{id} */
    public function show(array $params): void
    {
        $meeting = $this->meetings->findById((int) $params['id']);
        if (!$meeting) { $this->flash('error', 'Meeting nicht gefunden.'); $this->redirect('/circles'); }

        $agendaItems = $this->meetings->findAgendaItems($meeting['id']);
        $agreements  = $this->meetings->findAgreements($meeting['id']);
        $openTensions = (new TensionModel())->findByCircle($meeting['circle_id'], 'open');

        $this->render('meetings/show', [
            'pageTitle'    => 'Meeting ' . date('d.m.Y', strtotime($meeting['held_at'])),
            'meeting'      => $meeting,
            'agendaItems'  => $agendaItems,
            'agreements'   => $agreements,
            'openTensions' => $openTensions,
            'csrf'         => $this->csrfToken(),
        ]);
    }

    /** GET /meetings/new?circle_id=X */
    public function create(array $params): void
    {
        $circles = (new CircleModel())->findAll();
        $members = (new MemberModel())->findAll();

        $this->render('meetings/form', [
            'pageTitle' => 'Neues Meeting',
            'meeting'   => ['circle_id' => $_GET['circle_id'] ?? ''],
            'circles'   => $circles,
            'members'   => $members,
            'errors'    => [],
            'csrf'      => $this->csrfToken(),
        ]);
    }

    /** POST /meetings */
    public function store(array $params): void
    {
        $this->verifyCsrf();

        $attendeeIds = array_filter(array_map('intval', (array) ($_POST['attendees'] ?? [])));

        $data = [
            'circle_id'      => $this->inputInt('circle_id'),
            'meeting_type'   => $this->inputString('meeting_type') ?: 'governance',
            'held_at'        => $this->inputString('held_at'),
            'location'       => $this->inputString('location'),
            'facilitator_id' => $this->inputInt('facilitator_id') ?: null,
            'secretary_id'   => $this->inputInt('secretary_id')   ?: null,
            'attendees'      => $attendeeIds,
            'notes'          => $this->inputString('notes'),
        ];

        $errors = $this->meetings->validate($data);

        if ($errors) {
            $this->render('meetings/form', [
                'pageTitle' => 'Neues Meeting',
                'meeting'   => $data,
                'circles'   => (new CircleModel())->findAll(),
                'members'   => (new MemberModel())->findAll(),
                'errors'    => $errors,
                'csrf'      => $this->csrfToken(),
            ]);
            return;
        }

        $id = $this->meetings->create($data);
        $this->flash('success', 'Meeting angelegt.');
        $this->redirect('/meetings/' . $id);
    }

    /** GET /meetings/{id}/edit */
    public function edit(array $params): void
    {
        $meeting = $this->meetings->findById((int) $params['id']);
        if (!$meeting) { $this->flash('error', 'Meeting nicht gefunden.'); $this->redirect('/circles'); }

        // attendees aus JSON dekodieren
        $meeting['attendees'] = json_decode($meeting['attendees'] ?? '[]', true) ?? [];

        $this->render('meetings/form', [
            'pageTitle' => 'Meeting bearbeiten',
            'meeting'   => $meeting,
            'circles'   => (new CircleModel())->findAll(),
            'members'   => (new MemberModel())->findAll(),
            'errors'    => [],
            'csrf'      => $this->csrfToken(),
        ]);
    }

    /** POST /meetings/{id} */
    public function update(array $params): void
    {
        $this->verifyCsrf();
        $id = (int) $params['id'];

        $meeting = $this->meetings->findById($id);
        if (!$meeting) { $this->flash('error', 'Meeting nicht gefunden.'); $this->redirect('/circles'); }

        $attendeeIds = array_filter(array_map('intval', (array) ($_POST['attendees'] ?? [])));

        $data = [
            'meeting_type'   => $this->inputString('meeting_type') ?: 'governance',
            'held_at'        => $this->inputString('held_at'),
            'location'       => $this->inputString('location'),
            'facilitator_id' => $this->inputInt('facilitator_id') ?: null,
            'secretary_id'   => $this->inputInt('secretary_id')   ?: null,
            'attendees'      => $attendeeIds,
            'notes'          => $this->inputString('notes'),
        ];

        $errors = $this->meetings->validate(array_merge($data, ['circle_id' => $meeting['circle_id']]));

        if ($errors) {
            $this->render('meetings/form', [
                'pageTitle' => 'Meeting bearbeiten',
                'meeting'   => array_merge($meeting, $data),
                'circles'   => (new CircleModel())->findAll(),
                'members'   => (new MemberModel())->findAll(),
                'errors'    => $errors,
                'csrf'      => $this->csrfToken(),
            ]);
            return;
        }

        $this->meetings->update($id, $data);
        $this->flash('success', 'Meeting aktualisiert.');
        $this->redirect('/meetings/' . $id);
    }

    /** POST /meetings/{id}/agenda */
    public function addAgendaItem(array $params): void
    {
        $this->verifyCsrf();
        $id = (int) $params['id'];

        $this->meetings->addAgendaItem($id, [
            'tension_id' => $this->inputInt('tension_id') ?: null,
            'title'      => $this->inputString('title'),
            'item_type'  => $this->inputString('item_type') ?: 'tension',
        ]);

        $this->redirect('/meetings/' . $id);
    }
}
