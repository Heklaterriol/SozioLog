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

        // Aktive Delegationen mit unbesetzten Link-Rollen
        $unfilledLinks = $this->db->fetchAll("
            SELECT d.id,
                   fc.name AS from_circle_name,
                   tc.name AS to_circle_name,
                   CASE
                       WHEN d.rep_link_role IS NOT NULL
                            AND NOT EXISTS (
                                SELECT 1 FROM role_assignments ra
                                WHERE ra.role_id = d.rep_link_role AND ra.end_date IS NULL
                            ) THEN 'Rep-Link'
                       WHEN d.del_link_role IS NOT NULL
                            AND NOT EXISTS (
                                SELECT 1 FROM role_assignments ra
                                WHERE ra.role_id = d.del_link_role AND ra.end_date IS NULL
                            ) THEN 'Del-Link'
                       ELSE NULL
                   END AS missing_link
            FROM   delegations d
            JOIN   circles fc ON d.from_circle = fc.id
            JOIN   circles tc ON d.to_circle   = tc.id
            WHERE  d.status = 'active'
              AND (
                  (d.rep_link_role IS NOT NULL AND NOT EXISTS (
                      SELECT 1 FROM role_assignments ra WHERE ra.role_id = d.rep_link_role AND ra.end_date IS NULL
                  ))
                  OR
                  (d.del_link_role IS NOT NULL AND NOT EXISTS (
                      SELECT 1 FROM role_assignments ra WHERE ra.role_id = d.del_link_role AND ra.end_date IS NULL
                  ))
              )
            ORDER BY fc.name, tc.name
            LIMIT 8
        ");

        $this->render('dashboard/index', [
            'pageTitle'        => 'Dashboard',
            'stats'            => $stats,
            'upcomingMeetings' => $upcomingMeetings,
            'reviewDue'        => $reviewDue,
            'openTensions'     => $openTensions,
            'unfilledRoles'    => $unfilledRoles,
            'unfilledLinks'    => $unfilledLinks,
            'csrf'             => $this->csrfToken(),
        ]);
    }
}
