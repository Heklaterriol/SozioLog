<?php
namespace Logbuch\Controller;

use Logbuch\Database;
use Logbuch\Helper\Permissions;

/**
 * BaseController — gemeinsame Helfer für alle Controller
 */
abstract class BaseController
{
    protected array $config;
    protected Database $db;
    private ?array $resolvedUser = null;
    private bool $userResolved = false;
    private ?Permissions $permissions = null;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->db     = Database::getInstance();
    }

    // ------------------------------------------------------------------
    //  Template rendern
    // ------------------------------------------------------------------

    /**
     * Template ausgeben.
     *
     * @param string $template  z.B. 'circles/index'  → templates/circles/index.php
     * @param array  $data      Variablen, die im Template verfügbar sind
     * @param string $layout    Layout-Template (Standard: 'layout/main')
     */
    protected function render(string $template, array $data = [], string $layout = 'layout/main'): void
    {
        $tplDir = $this->config['paths']['templates'];

        // Daten als Variablen extrahieren (im Template-Scope)
        extract($data, EXTR_SKIP);
        $config      = $this->config;
        $flashMsg    = $this->getFlash();
        $currentUser = $this->currentUser();
        $perm        = $this->permissions();

        // Inhalt des eigentlichen Templates buffern
        ob_start();
        $tplFile = $tplDir . '/' . $template . '.php';
        if (!file_exists($tplFile)) {
            throw new \RuntimeException("Template nicht gefunden: {$tplFile}");
        }
        require $tplFile;
        $content = ob_get_clean();

        // Layout ausgeben (bindet $content ein)
        $layoutFile = $tplDir . '/' . $layout . '.php';
        if (!file_exists($layoutFile)) {
            throw new \RuntimeException("Layout nicht gefunden: {$layoutFile}");
        }
        require $layoutFile;
    }

    // ------------------------------------------------------------------
    //  Weiterleitung
    // ------------------------------------------------------------------

    protected function redirect(string $path, int $code = 302): never
    {
        $url = rtrim($this->config['app']['base_url'], '/') . $path;
        http_response_code($code);
        header("Location: {$url}");
        exit;
    }

    // ------------------------------------------------------------------
    //  Flash-Nachrichten (einmalig, nach Redirect)
    // ------------------------------------------------------------------

    protected function flash(string $type, string $message): void
    {
        // type: 'success' | 'error' | 'info' | 'warning'
        $_SESSION['flash'] = ['type' => $type, 'message' => $message];
    }

    protected function getFlash(): ?array
    {
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        return $flash;
    }

    // ------------------------------------------------------------------
    //  Aktueller Benutzer
    // ------------------------------------------------------------------

    protected function currentUser(): ?array
    {
        if ($this->userResolved) {
            return $this->resolvedUser;
        }
        $this->userResolved = true;

        $id = $_SESSION['user_id'] ?? null;
        if (!$id) {
            return $this->resolvedUser = null;
        }
        return $this->resolvedUser = $this->db->fetchOne(
            'SELECT id, name, email, is_admin, permission_level FROM members WHERE id = ?',
            [(int) $id]
        );
    }

    /**
     * Zentraler Zugriff auf alle Berechtigungsentscheidungen.
     * Siehe Logbuch\Helper\Permissions für die einzelnen can*()-Methoden.
     */
    protected function permissions(): Permissions
    {
        if ($this->permissions === null) {
            $this->permissions = new Permissions($this->currentUser());
        }
        return $this->permissions;
    }

    protected function requireAdmin(): void
    {
        if (!$this->permissions()->isAdmin()) {
            $this->flash('error', 'Diese Aktion erfordert Admin-Rechte.');
            $this->redirect('/');
        }
    }

    /**
     * Bricht mit Fehlermeldung ab, wenn der Nutzer im angegebenen Kreis
     * weder Admin noch berechtigtes Mitglied ("eigener Kreis") ist.
     *
     * @param callable(int): bool $check  z.B. fn($cid) => $this->permissions()->canManageRolesIn($cid)
     */
    protected function requireCirclePermission(int $circleId, callable $check, string $message = 'Dafür fehlen dir die Berechtigungen.'): void
    {
        if (!$check($circleId)) {
            $this->flash('error', $message);
            $this->redirect('/circles/' . $circleId);
        }
    }

    // ------------------------------------------------------------------
    //  POST-Eingabe bereinigen
    // ------------------------------------------------------------------

    protected function input(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $default;
    }

    protected function inputString(string $key, string $default = ''): string
    {
        return trim((string) ($_POST[$key] ?? $default));
    }

    protected function inputInt(string $key, int $default = 0): int
    {
        return (int) ($_POST[$key] ?? $default);
    }

    protected function inputDate(string $key): ?string
    {
        $val = trim((string) ($_POST[$key] ?? ''));
        if ($val === '' || !strtotime($val)) {
            return null;
        }
        return date('Y-m-d', strtotime($val));
    }

    // ------------------------------------------------------------------
    //  CSRF-Schutz
    // ------------------------------------------------------------------

    protected function csrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    protected function verifyCsrf(): void
    {
        $token = $_POST['csrf_token'] ?? '';
        if (!hash_equals($this->csrfToken(), $token)) {
            http_response_code(403);
            die('Ungültiges CSRF-Token.');
        }
    }

    // ------------------------------------------------------------------
    //  JSON-Antwort (für künftige API-Endpunkte)
    // ------------------------------------------------------------------

    protected function json(mixed $data, int $code = 200): never
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
}
