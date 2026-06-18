<?php
namespace Logbuch\Controller;

/**
 * AuthController — Login / Logout
 */
class AuthController extends BaseController
{
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
}
