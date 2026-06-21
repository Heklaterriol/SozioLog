<?php
namespace Logbuch;

/**
 * Router — einfacher URL-Dispatcher
 *
 * Unterstützt Platzhalter der Form {name} in URLs.
 * Controller-Klassen liegen unter src/Controller/,
 * Klassenname = Logbuch\Controller\{Name}Controller
 */
class Router
{
    /** @var array<string, list<array{pattern:string, handler:string, regex:string, keys:list<string>}>> */
    private array $routes = [];
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    // ------------------------------------------------------------------
    //  Route-Registrierung
    // ------------------------------------------------------------------

    public function get(string $path, string $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    public function post(string $path, string $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    private function add(string $method, string $path, string $handler): void
    {
        [$regex, $keys] = $this->compile($path);
        $this->routes[$method][] = [
            'pattern' => $path,
            'handler' => $handler,
            'regex'   => $regex,
            'keys'    => $keys,
        ];
    }

    /**
     * Konvertiert /circles/{id}/roles zu Regex + Parameternamen
     * @return array{string, list<string>}
     */
    private function compile(string $path): array
    {
        $keys  = [];
        $regex = preg_replace_callback('/\{(\w+)\}/', function ($m) use (&$keys) {
            $keys[] = $m[1];
            return '([^/]+)';
        }, $path);
        return ['#^' . $regex . '$#', $keys];
    }

    // ------------------------------------------------------------------
    //  Dispatch
    // ------------------------------------------------------------------

    public function dispatch(string $method, string $path): void
    {
        $routes = $this->routes[$method] ?? [];

        foreach ($routes as $route) {
            if (preg_match($route['regex'], $path, $matches)) {
                array_shift($matches); // Gesamtmatch entfernen
                $params = array_combine($route['keys'], $matches);

                $this->invoke($route['handler'], $params ?: []);
                return;
            }
        }

        // Keine Route gefunden
        $this->notFound();
    }

    // ------------------------------------------------------------------
    //  Controller aufrufen
    // ------------------------------------------------------------------

    private function invoke(string $handler, array $params): void
    {
        [$controllerName, $action] = explode('@', $handler);

        $class = "Logbuch\\Controller\\{$controllerName}Controller";

        if (!class_exists($class)) {
            $this->serverError("Controller-Klasse {$class} nicht gefunden.");
            return;
        }

        $controller = new $class($this->config);

        if (!method_exists($controller, $action)) {
            $this->serverError("Methode {$action} in {$class} nicht gefunden.");
            return;
        }

        // Auth-Check (außer Login- und Nextcloud-OAuth-Seiten)
        $publicHandlers = [
            'Auth@loginForm',
            'Auth@nextcloudStart',
            'Auth@nextcloudCallback',
        ];
        if (!in_array($handler, $publicHandlers, true)) {
            $auth = new Middleware\AuthMiddleware();
            if (!$auth->handle()) {
                $this->redirect('/login');
                return;
            }
        }

        $controller->$action($params);
    }

    // ------------------------------------------------------------------
    //  Hilfsmethoden
    // ------------------------------------------------------------------

    public function redirect(string $path, int $code = 302): never
    {
        $url = rtrim($this->config['app']['base_url'], '/') . $path;
        http_response_code($code);
        header("Location: {$url}");
        exit;
    }

    private function notFound(): void
    {
        http_response_code(404);
        echo '<h1>404 — Seite nicht gefunden</h1>';
    }

    private function serverError(string $msg): void
    {
        http_response_code(500);
        echo '<h1>500 — Serverfehler</h1>';
        if ($this->config['app']['debug']) {
            echo '<pre>' . htmlspecialchars($msg) . '</pre>';
        }
    }
}
