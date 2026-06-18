<?php
namespace Logbuch\Controller;

use Logbuch\Model\CircleModel;
use Logbuch\Model\AgreementModel;
use Logbuch\Model\MeetingModel;
use Logbuch\Model\TensionModel;

class DashboardController extends BaseController
{
    public function index(array $params): void
    {
        $circles    = new CircleModel();
        $agreements = new AgreementModel();
        $meetings   = new MeetingModel();
        $tensions   = new TensionModel();

        // Kennzahlen
        $stats = [
            'circles'    => (int) $this->db->fetchValue("SELECT COUNT(*) FROM circles WHERE status='active'"),
            'roles'      => (int) $this->db->fetchValue("SELECT COUNT(*) FROM roles"),
            'open_tensions' => (int) $this->db->fetchValue("SELECT COUNT(*) FROM tensions WHERE status='open'"),
            'review_due' => (int) $this->db->fetchValue(
                "SELECT COUNT(*) FROM agreements WHERE status='active' AND review_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)"
            ),
        ];

        // Nächste Meetings (aufsteigend ab heute)
        $upcomingMeetings = $this->db->fetchAll("
            SELECT m.*, c.name AS circle_name
            FROM   meetings m
            JOIN   circles  c ON m.circle_id = c.id
            WHERE  m.held_at >= CURDATE()
            ORDER BY m.held_at ASC
            LIMIT 5
        ");

        // Vereinbarungen mit Review in 30 Tagen
        $reviewDue = $agreements->findDueForReview(30);

        // Offene Spannungen (neueste 8)
        $openTensions = $this->db->fetchAll("
            SELECT t.*, c.name AS circle_name, m.name AS raised_by_name
            FROM   tensions t
            JOIN   circles  c ON t.circle_id  = c.id
            LEFT JOIN members m ON t.raised_by = m.id
            WHERE  t.status = 'open'
            ORDER BY t.created_at DESC
            LIMIT 8
        ");

        // Rollen ohne Besetzung
        $unfilledRoles = $this->db->fetchAll("
            SELECT r.id, r.name, r.role_type, c.name AS circle_name
            FROM   roles r
            JOIN   circles c ON r.circle_id = c.id
            WHERE  NOT EXISTS (
                SELECT 1 FROM role_assignments ra
                WHERE ra.role_id = r.id AND ra.end_date IS NULL
            )
            ORDER BY c.name, r.name
            LIMIT 10
        ");

        $this->render('dashboard/index', [
            'pageTitle'       => 'Dashboard',
            'stats'           => $stats,
            'upcomingMeetings'=> $upcomingMeetings,
            'reviewDue'       => $reviewDue,
            'openTensions'    => $openTensions,
            'unfilledRoles'   => $unfilledRoles,
            'csrf'            => $this->csrfToken(),
        ]);
    }
}
