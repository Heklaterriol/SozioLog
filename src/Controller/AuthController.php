<?php
namespace Logbuch\Controller;

use Logbuch\Model\MemberModel;

/**
 * AuthController — Anmeldung ausschließlich per Nextcloud-SSO (OAuth2).
 */
class AuthController extends BaseController
{
    private MemberModel $members;

    public function __construct(array $config)
    {
        parent::__construct($config);
        $this->members = new MemberModel();
    }

    public function loginForm(array $params): void
    {
        if (isset($_SESSION['user_id'])) {
            $this->redirect('/');
        }
        $this->render('auth/login', [
            'pageTitle' => 'Anmelden',
        ], 'layout/bare');
    }

    public function logout(array $params): void
    {
        $this->verifyCsrf();

        $_SESSION = [];
        session_destroy();

        $this->redirect('/login');
    }

    // ------------------------------------------------------------------
    //  Nextcloud OAuth2 — Schritt 1: zu Nextcloud weiterleiten
    // ------------------------------------------------------------------

    public function nextcloudStart(array $params): void
    {
        $nc = $this->config['nextcloud'];

        if (empty($nc['client_id']) || empty($nc['base_url'])) {
            $this->flash('error', 'Nextcloud-Login ist nicht konfiguriert.');
            $this->redirect('/login');
        }

        // CSRF-Schutz für den OAuth-Flow selbst (state-Parameter)
        $state = bin2hex(random_bytes(16));
        $_SESSION['oauth_state'] = $state;

        $redirectUri = rtrim($this->config['app']['base_url'], '/') . '/auth/nextcloud/callback';

        $query = http_build_query([
            'response_type' => 'code',
            'client_id'     => $nc['client_id'],
            'redirect_uri'  => $redirectUri,
            'state'         => $state,
        ]);

        $this->redirect($this->ncUrl('/apps/oauth2/authorize') . '?' . $query);
    }

    // ------------------------------------------------------------------
    //  Nextcloud OAuth2 — Schritt 2: Callback, Token- & Profil-Abruf
    // ------------------------------------------------------------------

    public function nextcloudCallback(array $params): void
    {
        $nc = $this->config['nextcloud'];

        $code  = $_GET['code']  ?? '';
        $state = $_GET['state'] ?? '';

        $expectedState = $_SESSION['oauth_state'] ?? null;
        unset($_SESSION['oauth_state']);

        if ($code === '' || $state === '' || !$expectedState || !hash_equals($expectedState, $state)) {
            $this->flash('error', 'Anmeldung über Nextcloud ist fehlgeschlagen (ungültige Anfrage).');
            $this->redirect('/login');
        }

        $redirectUri = rtrim($this->config['app']['base_url'], '/') . '/auth/nextcloud/callback';

        // ---- Token-Austausch ----
        $token = $this->fetchAccessToken($nc, $code, $redirectUri);
        if (!$token) {
            $this->flash('error', 'Anmeldung über Nextcloud ist fehlgeschlagen (Token konnte nicht abgerufen werden).');
            $this->redirect('/login');
        }

        // ---- Profil + Gruppen über die OCS-User-API abrufen ----
        $profile = $this->fetchNextcloudProfile($nc, $token);
        if (!$profile) {
            $this->flash('error', 'Anmeldung über Nextcloud ist fehlgeschlagen (Profil konnte nicht geladen werden).');
            $this->redirect('/login');
        }

        $ncUserId = (string) ($profile['id'] ?? '');
        $email    = strtolower(trim((string) ($profile['email'] ?? '')));
        $name     = trim((string) ($profile['display-name'] ?? $ncUserId));
        $groups   = (array) ($profile['groups'] ?? []);

        if ($ncUserId === '') {
            $this->flash('error', 'Anmeldung über Nextcloud ist fehlgeschlagen (keine Benutzer-ID erhalten).');
            $this->redirect('/login');
        }

        // ---- Gruppenzugehörigkeit prüfen ----
        $requiredGroup = trim((string) ($nc['required_group'] ?? ''));
        if ($requiredGroup !== '' && !in_array($requiredGroup, $groups, true)) {
            $this->flash('error', 'Dein Nextcloud-Konto ist nicht für dieses Logbuch freigeschaltet.');
            $this->redirect('/login');
        }

        // ---- Mitglied finden / verknüpfen / anlegen ----
        $member = $this->members->findByNextcloudId($ncUserId);

        if (!$member && $email !== '') {
            // Noch nicht verknüpft — über E-Mail ein bestehendes Konto suchen
            $existing = $this->members->findByEmail($email);
            if ($existing) {
                $this->members->linkNextcloudId((int) $existing['id'], $ncUserId);
                $member = $this->members->findById((int) $existing['id']);
            }
        }

        if (!$member) {
            if ($email === '') {
                $this->flash('error', 'Dein Nextcloud-Konto hat keine hinterlegte E-Mail-Adresse — bitte in Nextcloud ergänzen.');
                $this->redirect('/login');
            }
            $newId  = $this->members->createFromNextcloud($name ?: $ncUserId, $email, $ncUserId);
            $member = $this->members->findById($newId);
        }

        // ---- Einloggen ----
        session_regenerate_id(true);

        $_SESSION['user_id']   = $member['id'];
        $_SESSION['user_name'] = $member['name'];
        $_SESSION['is_admin']  = (bool) $member['is_admin'];

        $this->flash('success', 'Willkommen, ' . htmlspecialchars($member['name']) . '!');
        $this->redirect('/');
    }

    // ------------------------------------------------------------------
    //  Interne Helfer
    // ------------------------------------------------------------------

    private function ncUrl(string $path): string
    {
        $nc   = $this->config['nextcloud'];
        $base = rtrim($nc['base_url'], '/');
        $idx  = !empty($nc['use_index_php']) ? '/index.php' : '';
        return $base . $idx . $path;
    }

    private function fetchAccessToken(array $nc, string $code, string $redirectUri): ?string
    {
        $ch = curl_init($this->ncUrl('/apps/oauth2/api/v1/token'));
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_POSTFIELDS     => http_build_query([
                'grant_type'    => 'authorization_code',
                'code'          => $code,
                'client_id'     => $nc['client_id'],
                'client_secret' => $nc['client_secret'],
                'redirect_uri'  => $redirectUri,
            ]),
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false || $httpCode !== 200) {
            return null;
        }

        $data = json_decode($response, true);
        return $data['access_token'] ?? null;
    }

    /**
     * Holt Profildaten (E-Mail, Anzeigename, Gruppen) über die
     * Nextcloud OCS-User-API, autorisiert mit dem OAuth2 Bearer-Token.
     */
    private function fetchNextcloudProfile(array $nc, string $accessToken): ?array
    {
        $ch = curl_init($this->ncUrl('/ocs/v2.php/cloud/user?format=json'));
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $accessToken,
                'OCS-APIRequest: true',
                'Accept: application/json',
            ],
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false || $httpCode !== 200) {
            return null;
        }

        $data = json_decode($response, true);
        return $data['ocs']['data'] ?? null;
    }
}
