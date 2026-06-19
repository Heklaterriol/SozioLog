<?php
namespace Logbuch\Controller;

use Logbuch\Helper\Mailer;
use Logbuch\Model\MemberModel;

/**
 * AuthController — Login / Logout / Passwort-Reset
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
        // Bereits eingeloggt → Dashboard
        if (isset($_SESSION['user_id'])) {
            $this->redirect('/');
        }
        $this->render('auth/login', [
            'pageTitle' => 'Anmelden',
            'csrf'      => $this->csrfToken(),
        ], 'layout/bare');
    }

    public function login(array $params): void
    {
        $this->verifyCsrf();

        $email    = strtolower(trim($this->inputString('email')));
        $password = $this->inputString('password');

        if ($email === '' || $password === '') {
            $this->flash('error', 'Bitte E-Mail und Passwort eingeben.');
            $this->redirect('/login');
        }

        $member = $this->db->fetchOne(
            'SELECT id, name, email, password_hash, is_admin FROM members WHERE email = ?',
            [$email]
        );

        if (!$member || !password_verify($password, $member['password_hash'])) {
            // Timing-sicheres Verhalten: immer gleiche Antwortzeit
            usleep(random_int(100_000, 300_000));
            $this->flash('error', 'E-Mail oder Passwort falsch.');
            $this->redirect('/login');
        }

        // Session neu generieren (Session-Fixation verhindern)
        session_regenerate_id(true);

        $_SESSION['user_id']   = $member['id'];
        $_SESSION['user_name'] = $member['name'];
        $_SESSION['is_admin']  = (bool) $member['is_admin'];

        $this->flash('success', 'Willkommen, ' . htmlspecialchars($member['name']) . '!');
        $this->redirect('/');
    }

    public function logout(array $params): void
    {
        $this->verifyCsrf();

        $_SESSION = [];
        session_destroy();

        $this->redirect('/login');
    }

    // ------------------------------------------------------------------
    //  Passwort vergessen — Schritt 1: E-Mail anfordern
    // ------------------------------------------------------------------

    public function forgotPasswordForm(array $params): void
    {
        if (isset($_SESSION['user_id'])) {
            $this->redirect('/');
        }
        $this->render('auth/forgot_password', [
            'pageTitle' => 'Passwort vergessen',
            'csrf'      => $this->csrfToken(),
        ], 'layout/bare');
    }

    public function forgotPassword(array $params): void
    {
        $this->verifyCsrf();

        $email = strtolower(trim($this->inputString('email')));

        // Immer dieselbe Meldung — unabhängig davon, ob die E-Mail
        // existiert. So lässt sich nicht erraten, welche Adressen
        // im System registriert sind.
        $genericMessage = 'Falls diese E-Mail-Adresse bei uns registriert ist, '
            . 'wurde soeben ein Link zum Zurücksetzen des Passworts verschickt.';

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->flash('success', $genericMessage);
            $this->redirect('/password/forgot');
        }

        $member = $this->members->findByEmail($email);

        if ($member) {
            $ttl   = (int) ($this->config['mail']['reset_token_ttl'] ?? 3600);
            $token = $this->members->createPasswordReset(
                (int) $member['id'],
                $ttl,
                $_SERVER['REMOTE_ADDR'] ?? null
            );

            $resetUrl = rtrim($this->config['app']['base_url'], '/')
                . '/password/reset/' . $token;

            $minutes = (int) ceil($ttl / 60);
            $html = $this->renderMailTemplate($member['name'], $resetUrl, $minutes);

            try {
                $mailer = new Mailer($this->config['mail']);
                $mailer->send(
                    $member['email'],
                    $member['name'],
                    'Passwort zurücksetzen — ' . $this->config['app']['name'],
                    $html
                );
            } catch (\Throwable $e) {
                // Versandfehler nicht an den Nutzer durchreichen (kein Enumeration-Leak),
                // aber im Debug-Modus sichtbar machen.
                if ($this->config['app']['debug']) {
                    $this->flash('error', 'Mailversand fehlgeschlagen: ' . $e->getMessage());
                    $this->redirect('/password/forgot');
                }
            }
        }

        $this->flash('success', $genericMessage);
        $this->redirect('/password/forgot');
    }

    private function renderMailTemplate(string $name, string $resetUrl, int $minutes): string
    {
        $appName = htmlspecialchars($this->config['app']['name']);
        $safeName = htmlspecialchars($name);
        $safeUrl  = htmlspecialchars($resetUrl);

        return <<<HTML
            <div style="font-family: Arial, sans-serif; font-size: 15px; color: #222; max-width: 480px;">
                <p>Hallo {$safeName},</p>
                <p>
                    für dein Konto im <strong>{$appName}</strong> wurde ein Zurücksetzen
                    des Passworts angefordert.
                </p>
                <p>
                    <a href="{$safeUrl}"
                       style="display:inline-block;padding:10px 20px;background:#1D6F6A;
                              color:#fff;text-decoration:none;border-radius:6px;">
                        Neues Passwort festlegen
                    </a>
                </p>
                <p>
                    Falls der Button nicht funktioniert, kopiere diesen Link in deinen Browser:<br>
                    <a href="{$safeUrl}">{$safeUrl}</a>
                </p>
                <p>Der Link ist {$minutes} Minuten gültig.</p>
                <p>
                    Wenn du das nicht warst, kannst du diese E-Mail ignorieren —
                    dein Passwort bleibt unverändert.
                </p>
            </div>
            HTML;
    }

    // ------------------------------------------------------------------
    //  Passwort vergessen — Schritt 2: neues Passwort setzen
    // ------------------------------------------------------------------

    public function resetPasswordForm(array $params): void
    {
        if (isset($_SESSION['user_id'])) {
            $this->redirect('/');
        }

        $token    = $params['token'] ?? '';
        $memberId = $this->members->findValidPasswordReset($token);

        if (!$memberId) {
            $this->flash('error', 'Dieser Link ist ungültig oder abgelaufen. Bitte fordere einen neuen an.');
            $this->redirect('/password/forgot');
        }

        $this->render('auth/reset_password', [
            'pageTitle' => 'Neues Passwort festlegen',
            'csrf'      => $this->csrfToken(),
            'token'     => $token,
        ], 'layout/bare');
    }

    public function resetPassword(array $params): void
    {
        $this->verifyCsrf();

        $token           = $params['token'] ?? '';
        $password        = $this->inputString('password');
        $passwordConfirm = $this->inputString('password_confirm');

        $memberId = $this->members->findValidPasswordReset($token);

        if (!$memberId) {
            $this->flash('error', 'Dieser Link ist ungültig oder abgelaufen. Bitte fordere einen neuen an.');
            $this->redirect('/password/forgot');
        }

        if (strlen($password) < 8) {
            $this->flash('error', 'Das Passwort muss mindestens 8 Zeichen lang sein.');
            $this->redirect('/password/reset/' . $token);
        }

        if ($password !== $passwordConfirm) {
            $this->flash('error', 'Die Passwörter stimmen nicht überein.');
            $this->redirect('/password/reset/' . $token);
        }

        $this->members->updatePassword($memberId, $password);
        $this->members->consumePasswordReset($token);

        $this->flash('success', 'Dein Passwort wurde geändert. Du kannst dich jetzt anmelden.');
        $this->redirect('/login');
    }
}
