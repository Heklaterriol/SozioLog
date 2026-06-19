<?php
namespace Logbuch\Controller;

/**
 * AdminController
 *
 * Seiten:
 *   GET  /admin          → Übersicht + Einstellungen
 *   POST /admin          → Einstellungen speichern
 *   GET  /admin/export   → Export (JSON oder PDF je nach ?format=)
 */
class AdminController extends BaseController
{
    public function __construct(array $config)
    {
        parent::__construct($config);
        $this->requireAdmin();
    }

    // ------------------------------------------------------------------
    //  GET /admin
    // ------------------------------------------------------------------
    public function index(array $params): void
    {
        $stats = [
            'circles'     => (int) $this->db->fetchValue("SELECT COUNT(*) FROM circles"),
            'roles'       => (int) $this->db->fetchValue("SELECT COUNT(*) FROM roles"),
            'members'     => (int) $this->db->fetchValue("SELECT COUNT(*) FROM members"),
            'agreements'  => (int) $this->db->fetchValue("SELECT COUNT(*) FROM agreements"),
            'meetings'    => (int) $this->db->fetchValue("SELECT COUNT(*) FROM meetings"),
            'tensions'    => (int) $this->db->fetchValue("SELECT COUNT(*) FROM tensions"),
            'delegations' => (int) $this->db->fetchValue("SELECT COUNT(*) FROM delegations WHERE status = 'active'"),
        ];

        $this->render('admin/index', [
            'pageTitle' => 'Admin',
            'stats'     => $stats,
            'csrf'      => $this->csrfToken(),
        ]);
    }

    // ------------------------------------------------------------------
    //  POST /admin  (Einstellungen — Platzhalter, erweiterbar)
    // ------------------------------------------------------------------
    public function update(array $params): void
    {
        $this->verifyCsrf();
        // Hier könnten zukünftig Org-Name, Sprache etc. gespeichert werden
        $this->flash('success', 'Einstellungen gespeichert.');
        $this->redirect('/admin');
    }

    // ------------------------------------------------------------------
    //  GET /admin/export?format=json|pdf
    // ------------------------------------------------------------------
    public function export(array $params): void
    {
        $format = strtolower($_GET['format'] ?? 'json');

        match ($format) {
            'pdf'  => $this->exportPdf(),
            default => $this->exportJson(),
        };
    }

    // ------------------------------------------------------------------
    //  JSON-Export
    // ------------------------------------------------------------------
    private function exportJson(): void
    {
        $data = $this->collectAllData();

        $filename = 'logbuch-export-' . date('Y-m-d') . '.json';
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache');

        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    // ------------------------------------------------------------------
    //  PDF-Export via mPDF
    // ------------------------------------------------------------------
    private function exportPdf(): void
    {
        // mPDF via Composer prüfen
        $autoload = dirname(__DIR__, 2) . '/vendor/autoload.php';
        if (!file_exists($autoload)) {
            $this->flash('error', 'mPDF ist nicht installiert. Bitte "composer require mpdf/mpdf" ausführen.');
            $this->redirect('/admin');
        }
        require_once $autoload;

        $data = $this->collectAllData();
        $html = $this->buildPdfHtml($data);

        $mpdf = new \Mpdf\Mpdf([
            'margin_top'    => 18,
            'margin_bottom' => 18,
            'margin_left'   => 20,
            'margin_right'  => 20,
            'default_font'  => 'dejavusans',
            'format'        => 'A4',
        ]);

        $mpdf->SetTitle($this->config['app']['name'] . ' — Logbuch-Export');
        $mpdf->SetAuthor($this->config['app']['name']);
        $mpdf->SetCreator('Soziokratisches Logbuch');

        // Kopf- und Fußzeile
        $mpdf->SetHTMLHeader('
            <div style="font-size:9pt;color:#5C574E;border-bottom:1px solid #DDD9D1;padding-bottom:4pt">
                <b>' . htmlspecialchars($this->config['app']['name']) . '</b> — Logbuch-Export
            </div>
        ');
        $mpdf->SetHTMLFooter('
            <div style="font-size:8pt;color:#9C9589;text-align:center">
                Erstellt am ' . date('d.m.Y') . ' | Seite {PAGENO} von {nbpg}
            </div>
        ');

        $mpdf->WriteHTML($html);

        $filename = 'logbuch-' . date('Y-m-d') . '.pdf';
        $mpdf->Output($filename, \Mpdf\Output\Destination::DOWNLOAD);
        exit;
    }

    // ------------------------------------------------------------------
    //  Alle Daten aus der DB sammeln
    // ------------------------------------------------------------------
    private function collectAllData(): array
    {
        return [
            'exported_at' => date('c'),
            'app'         => $this->config['app']['name'],
            'circles'     => $this->db->fetchAll("
                SELECT c.*, p.name AS parent_name
                FROM circles c LEFT JOIN circles p ON c.parent_id = p.id
                ORDER BY p.name, c.name
            "),
            'roles'       => $this->db->fetchAll("
                SELECT r.*, c.name AS circle_name
                FROM roles r JOIN circles c ON r.circle_id = c.id
                ORDER BY c.name, r.name
            "),
            'role_assignments' => $this->db->fetchAll("
                SELECT ra.*, r.name AS role_name, m.name AS member_name, c.name AS circle_name
                FROM role_assignments ra
                JOIN roles r   ON ra.role_id   = r.id
                JOIN members m ON ra.member_id  = m.id
                JOIN circles c ON r.circle_id   = c.id
                ORDER BY ra.start_date DESC
            "),
            'agreements'  => $this->db->fetchAll("
                SELECT a.*, c.name AS circle_name
                FROM agreements a JOIN circles c ON a.circle_id = c.id
                ORDER BY c.name, a.agreed_at DESC
            "),
            'meetings'    => $this->db->fetchAll("
                SELECT m.*, c.name AS circle_name,
                       f.name AS facilitator_name, s.name AS secretary_name
                FROM meetings m
                JOIN circles c ON m.circle_id = c.id
                LEFT JOIN members f ON m.facilitator_id = f.id
                LEFT JOIN members s ON m.secretary_id   = s.id
                ORDER BY m.held_at DESC
            "),
            'tensions'    => $this->db->fetchAll("
                SELECT t.*, c.name AS circle_name, m.name AS raised_by_name
                FROM tensions t
                JOIN circles c ON t.circle_id = c.id
                LEFT JOIN members m ON t.raised_by = m.id
                ORDER BY t.created_at DESC
            "),
            'members'     => $this->db->fetchAll(
                "SELECT id, name, email, is_admin, created_at FROM members ORDER BY name"
            ),
            'delegations' => $this->db->fetchAll("
                SELECT d.*,
                       fc.name AS from_circle_name,
                       tc.name AS to_circle_name,
                       rr.name AS rep_link_name,
                       dr.name AS del_link_name
                FROM   delegations d
                JOIN   circles fc ON d.from_circle = fc.id
                JOIN   circles tc ON d.to_circle   = tc.id
                LEFT JOIN roles rr ON d.rep_link_role = rr.id
                LEFT JOIN roles dr ON d.del_link_role = dr.id
                ORDER BY fc.name, tc.name
            "),
            'agreement_versions' => $this->db->fetchAll("
                SELECT v.*, a.title AS agreement_title, c.name AS circle_name,
                       m.name AS changed_by_name
                FROM   agreement_versions v
                JOIN   agreements a ON v.agreement_id = a.id
                JOIN   circles    c ON a.circle_id    = c.id
                LEFT JOIN members m ON v.changed_by   = m.id
                ORDER BY v.agreement_id, v.version DESC
            "),
        ];
    }

    // ------------------------------------------------------------------
    //  HTML-String für PDF aufbauen
    // ------------------------------------------------------------------
    private function buildPdfHtml(array $data): string
    {
        $appName = htmlspecialchars($this->config['app']['name']);
        $date    = date('d.m.Y');

        $css = '
        <style>
            body      { font-family: dejavusans, sans-serif; font-size: 10pt; color: #1C1A17; }
            h1        { font-size: 18pt; color: #1D6F6A; margin: 0 0 4pt; }
            h2        { font-size: 13pt; color: #1D6F6A; margin: 14pt 0 4pt; border-bottom: 1px solid #DDD9D1; padding-bottom: 3pt; }
            h3        { font-size: 11pt; color: #1C1A17; margin: 10pt 0 3pt; }
            p, li     { margin: 2pt 0; }
            table     { width: 100%; border-collapse: collapse; margin: 6pt 0; font-size: 9pt; }
            th        { background: #F0EEE9; color: #5C574E; text-align: left; padding: 4pt 5pt;
                        border-bottom: 1px solid #DDD9D1; font-size: 8pt; text-transform: uppercase; letter-spacing: .03em; }
            td        { padding: 4pt 5pt; border-bottom: 1px solid #F0EEE9; vertical-align: top; }
            tr:last-child td { border-bottom: none; }
            .badge    { display: inline; padding: 1pt 4pt; border-radius: 3pt; font-size: 8pt; }
            .active   { background: #D6F0E3; color: #1A6B3C; }
            .expired  { background: #FAE0E0; color: #8B2020; }
            .review   { background: #FFF0C8; color: #7A5200; }
            .open     { background: #DCE9F8; color: #1A4A7A; }
            .resolved { background: #D6F0E3; color: #1A6B3C; }
            .draft    { background: #F0EEE9; color: #9C9589; }
            .cover    { text-align: center; padding: 60pt 0 40pt; }
            .cover .org { font-size: 11pt; color: #5C574E; margin-top: 6pt; }
            .cover .dt  { font-size: 9pt;  color: #9C9589; margin-top: 4pt; }
            .meta-row   { color: #5C574E; font-size: 9pt; margin: 2pt 0; }
            .empty      { color: #9C9589; font-style: italic; }
        </style>';

        $html = $css;

        // Titelseite
        $html .= '
        <div class="cover">
            <h1 style="font-size:24pt">' . $appName . '</h1>
            <div class="org">Soziokratisches Logbuch</div>
            <div class="dt">Export vom ' . $date . '</div>
        </div>';

        // ---- Kreise ----
        $html .= '<h2>Kreise (' . count($data['circles']) . ')</h2>';
        if ($data['circles']) {
            $html .= '<table><thead><tr>
                <th>Name</th><th>Überkreis</th><th>Zweck</th><th>Status</th>
            </tr></thead><tbody>';
            foreach ($data['circles'] as $c) {
                $status = $c['status'] === 'active' ? '<span class="badge active">aktiv</span>' : '<span class="badge draft">archiviert</span>';
                $html .= '<tr>
                    <td><b>' . htmlspecialchars($c['name']) . '</b></td>
                    <td>' . htmlspecialchars($c['parent_name'] ?? '—') . '</td>
                    <td>' . htmlspecialchars($c['purpose'] ?? '—') . '</td>
                    <td>' . $status . '</td>
                </tr>';
                if ($c['driver']) {
                    $html .= '<tr><td colspan="4" style="color:#5C574E;font-size:8.5pt;padding-left:10pt">
                        <i>Treiber:</i> ' . htmlspecialchars($c['driver']) . '
                    </td></tr>';
                }
            }
            $html .= '</tbody></table>';
        } else {
            $html .= '<p class="empty">Keine Kreise vorhanden.</p>';
        }

        // ---- Rollen ----
        $html .= '<h2>Rollen (' . count($data['roles']) . ')</h2>';
        if ($data['roles']) {
            $html .= '<table><thead><tr>
                <th>Rolle</th><th>Kreis</th><th>Typ</th><th>Zweck</th>
            </tr></thead><tbody>';
            foreach ($data['roles'] as $r) {
                $html .= '<tr>
                    <td><b>' . htmlspecialchars($r['name']) . '</b></td>
                    <td>' . htmlspecialchars($r['circle_name']) . '</td>
                    <td>' . htmlspecialchars($r['role_type']) . '</td>
                    <td>' . htmlspecialchars($r['purpose'] ?? '—') . '</td>
                </tr>';
                // Accountabilities
                $acc = json_decode($r['accountabilities'] ?? '[]', true) ?? [];
                if ($acc) {
                    $items = array_map(fn($a) => '<li>' . htmlspecialchars($a) . '</li>', $acc);
                    $html .= '<tr><td colspan="4" style="padding-left:10pt;font-size:8.5pt;color:#5C574E">
                        <i>Accountabilities:</i><ul style="margin:2pt 0">' . implode('', $items) . '</ul>
                    </td></tr>';
                }
            }
            $html .= '</tbody></table>';
        } else {
            $html .= '<p class="empty">Keine Rollen vorhanden.</p>';
        }

        // ---- Vereinbarungen ----
        $html .= '<h2>Vereinbarungen (' . count($data['agreements']) . ')</h2>';
        if ($data['agreements']) {
            $html .= '<table><thead><tr>
                <th>Titel</th><th>Kreis</th><th>Beschlossen</th><th>Review</th><th>Status</th>
            </tr></thead><tbody>';
            foreach ($data['agreements'] as $a) {
                $sc     = htmlspecialchars($a['status']);
                $badge  = '<span class="badge ' . $sc . '">' . $sc . '</span>';
                $review = $a['review_date'] ? date('d.m.Y', strtotime($a['review_date'])) : '—';
                $html  .= '<tr>
                    <td><b>' . htmlspecialchars($a['title']) . '</b></td>
                    <td>' . htmlspecialchars($a['circle_name']) . '</td>
                    <td>' . date('d.m.Y', strtotime($a['agreed_at'])) . '</td>
                    <td>' . $review . '</td>
                    <td>' . $badge . '</td>
                </tr>';
                if ($a['body']) {
                    $html .= '<tr><td colspan="5" style="padding-left:10pt;font-size:8.5pt;color:#5C574E">
                        ' . nl2br(htmlspecialchars(mb_substr($a['body'], 0, 400))) . (mb_strlen($a['body']) > 400 ? ' …' : '') . '
                    </td></tr>';
                }
            }
            $html .= '</tbody></table>';
        } else {
            $html .= '<p class="empty">Keine Vereinbarungen vorhanden.</p>';
        }

        // ---- Meetings ----
        $html .= '<h2>Meetings (' . count($data['meetings']) . ')</h2>';
        if ($data['meetings']) {
            $html .= '<table><thead><tr>
                <th>Datum</th><th>Kreis</th><th>Typ</th><th>Moderator·in</th><th>Protokoll</th>
            </tr></thead><tbody>';
            foreach ($data['meetings'] as $m) {
                $html .= '<tr>
                    <td>' . date('d.m.Y H:i', strtotime($m['held_at'])) . '</td>
                    <td>' . htmlspecialchars($m['circle_name']) . '</td>
                    <td>' . htmlspecialchars($m['meeting_type']) . '</td>
                    <td>' . htmlspecialchars($m['facilitator_name'] ?? '—') . '</td>
                    <td>' . htmlspecialchars($m['secretary_name']   ?? '—') . '</td>
                </tr>';
            }
            $html .= '</tbody></table>';
        } else {
            $html .= '<p class="empty">Keine Meetings vorhanden.</p>';
        }

        // ---- Spannungen ----
        $html .= '<h2>Spannungen (' . count($data['tensions']) . ')</h2>';
        if ($data['tensions']) {
            $html .= '<table><thead><tr>
                <th>Titel</th><th>Kreis</th><th>Status</th><th>Eingereicht von</th><th>Datum</th>
            </tr></thead><tbody>';
            foreach ($data['tensions'] as $t) {
                $sc    = htmlspecialchars($t['status']);
                $badge = '<span class="badge ' . $sc . '">' . $sc . '</span>';
                $html .= '<tr>
                    <td>' . htmlspecialchars($t['title'] ?: mb_substr($t['description'] ?? '', 0, 60)) . '</td>
                    <td>' . htmlspecialchars($t['circle_name']) . '</td>
                    <td>' . $badge . '</td>
                    <td>' . htmlspecialchars($t['raised_by_name'] ?? '—') . '</td>
                    <td>' . date('d.m.Y', strtotime($t['created_at'])) . '</td>
                </tr>';
            }
            $html .= '</tbody></table>';
        } else {
            $html .= '<p class="empty">Keine Spannungen vorhanden.</p>';
        }

        // ---- Mitglieder ----
        $html .= '<h2>Mitglieder (' . count($data['members']) . ')</h2>';
        if ($data['members']) {
            $html .= '<table><thead><tr>
                <th>Name</th><th>E-Mail</th><th>Admin</th><th>Mitglied seit</th>
            </tr></thead><tbody>';
            foreach ($data['members'] as $m) {
                $html .= '<tr>
                    <td>' . htmlspecialchars($m['name']) . '</td>
                    <td>' . htmlspecialchars($m['email']) . '</td>
                    <td>' . ($m['is_admin'] ? 'Ja' : 'Nein') . '</td>
                    <td>' . date('d.m.Y', strtotime($m['created_at'])) . '</td>
                </tr>';
            }
            $html .= '</tbody></table>';
        } else {
            $html .= '<p class="empty">Keine Mitglieder vorhanden.</p>';
        }

        // ---- Delegationen ----
        $html .= '<h2>Delegationen (' . count($data['delegations']) . ')</h2>';
        if ($data['delegations']) {
            $html .= '<table><thead><tr>
                <th>Von (Anker)</th><th>An (Delegiert)</th><th>Rep-Link</th><th>Del-Link</th><th>Status</th><th>Seit</th>
            </tr></thead><tbody>';
            foreach ($data['delegations'] as $d) {
                $sc    = $d['status'] === 'active' ? 'active' : 'expired';
                $label = $d['status'] === 'active' ? 'Aktiv' : 'Beendet';
                $html .= '<tr>
                    <td><b>' . htmlspecialchars($d['from_circle_name']) . '</b></td>
                    <td>' . htmlspecialchars($d['to_circle_name']) . '</td>
                    <td>' . htmlspecialchars($d['rep_link_name'] ?? '—') . '</td>
                    <td>' . htmlspecialchars($d['del_link_name'] ?? '—') . '</td>
                    <td><span class="badge ' . $sc . '">' . $label . '</span></td>
                    <td>' . ($d['started_at'] ? date('d.m.Y', strtotime($d['started_at'])) : '—') . '</td>
                </tr>';
                if ($d['description']) {
                    $html .= '<tr><td colspan="6" style="padding-left:10pt;font-size:8.5pt;color:#5C574E">
                        ' . htmlspecialchars(mb_substr($d['description'], 0, 200)) . '
                    </td></tr>';
                }
            }
            $html .= '</tbody></table>';
        } else {
            $html .= '<p class="empty">Keine Delegationen vorhanden.</p>';
        }

        // ---- Versionshistorie ----
        if (!empty($data['agreement_versions'])) {
            $html .= '<h2>Vereinbarungs-Versionshistorie (' . count($data['agreement_versions']) . ' Einträge)</h2>';
            $html .= '<table><thead><tr>
                <th>Vereinbarung</th><th>Kreis</th><th>Version</th><th>Status</th><th>Geändert von</th><th>Gespeichert</th>
            </tr></thead><tbody>';
            foreach ($data['agreement_versions'] as $v) {
                $sc    = htmlspecialchars($v['status']);
                $badge = '<span class="badge ' . $sc . '">' . $sc . '</span>';
                $html .= '<tr>
                    <td>' . htmlspecialchars($v['agreement_title']) . '</td>
                    <td>' . htmlspecialchars($v['circle_name']) . '</td>
                    <td style="font-family:monospace">v' . $v['version'] . '</td>
                    <td>' . $badge . '</td>
                    <td>' . htmlspecialchars($v['changed_by_name'] ?? '—') . '</td>
                    <td>' . date('d.m.Y H:i', strtotime($v['created_at'])) . '</td>
                </tr>';
            }
            $html .= '</tbody></table>';
        }

        return $html;
    }
}
