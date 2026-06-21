<?php
/**
 * Soziokratisches Logbuch — Front-Controller
 * Alle Requests landen hier via .htaccess
 */

declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));
define('APP_START', microtime(true));

// ------------------------------------------------------------------
//  Composer-Autoloader (für PHPMailer etc., falls installiert)
// ------------------------------------------------------------------
if (file_exists(APP_ROOT . '/vendor/autoload.php')) {
    require APP_ROOT . '/vendor/autoload.php';
}

// ------------------------------------------------------------------
//  Autoloader (PSR-4-ähnlich, Namespace Logbuch\ → src/)
// ------------------------------------------------------------------
spl_autoload_register(function (string $class): void {
    $prefix = 'Logbuch\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }
    $rel  = str_replace('\\', DIRECTORY_SEPARATOR, substr($class, strlen($prefix)));
    $file = APP_ROOT . '/src/' . $rel . '.php';
    if (file_exists($file)) {
        require $file;
    }
});

// ------------------------------------------------------------------
//  Konfiguration laden — fehlt sie, zur Installation weiterleiten
// ------------------------------------------------------------------
$configFile = APP_ROOT . '/config/config.php';
if (!file_exists($configFile)) {
    require APP_ROOT . '/public/install.php';
    exit;
}
$config = require $configFile;
$local  = APP_ROOT . '/config/config.local.php';
if (file_exists($local)) {
    $config = array_replace_recursive($config, require $local);
}

// Zeitzone & Fehler-Reporting
date_default_timezone_set($config['app']['timezone']);
if ($config['app']['debug']) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(0);
}

// ------------------------------------------------------------------
//  Session starten
// ------------------------------------------------------------------
$sc = $config['session'];
session_name($sc['name']);
session_set_cookie_params([
    'lifetime' => $sc['lifetime'],
    'path'     => '/',
    'secure'   => $sc['secure'],
    'httponly' => $sc['httponly'],
    'samesite' => $sc['samesite'],
]);
session_start();

// ------------------------------------------------------------------
//  Request-Pfad ermitteln
// ------------------------------------------------------------------
$basePath   = parse_url($config['app']['base_url'], PHP_URL_PATH) ?? '';
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path       = '/' . trim(substr($requestUri, strlen($basePath)), '/');
$method     = strtoupper($_SERVER['REQUEST_METHOD']);

// ------------------------------------------------------------------
//  Router
// ------------------------------------------------------------------
use Logbuch\Router;
$router = new Router($config);

// Dashboard
$router->get('/', 'Dashboard@index');

// Kreise
$router->get('/circles',                      'Circle@index');
$router->get('/circles/new',                  'Circle@create');
$router->post('/circles',                     'Circle@store');
$router->get('/circles/{id}',                 'Circle@show');
$router->get('/circles/{id}/edit',            'Circle@edit');
$router->post('/circles/{id}',                'Circle@update');
$router->post('/circles/{id}/delete',         'Circle@delete');

// Rollen
$router->get('/circles/{cid}/roles',          'Role@index');
$router->get('/circles/{cid}/roles/new',      'Role@create');
$router->post('/circles/{cid}/roles',         'Role@store');
$router->get('/roles/{id}',                   'Role@show');
$router->get('/roles/{id}/edit',              'Role@edit');
$router->post('/roles/{id}',                  'Role@update');
$router->post('/roles/{id}/assign',           'Role@assign');

// Vereinbarungen
$router->get('/circles/{cid}/agreements',     'Agreement@index');
$router->get('/agreements/new',               'Agreement@create');
$router->post('/agreements',                  'Agreement@store');
$router->get('/agreements/{id}',              'Agreement@show');
$router->get('/agreements/{id}/edit',         'Agreement@edit');
$router->post('/agreements/{id}',             'Agreement@update');

// Meetings
$router->get('/circles/{cid}/meetings',       'Meeting@index');
$router->get('/meetings/new',                 'Meeting@create');
$router->post('/meetings',                    'Meeting@store');
$router->get('/meetings/{id}',                'Meeting@show');
$router->get('/meetings/{id}/edit',           'Meeting@edit');
$router->post('/meetings/{id}',               'Meeting@update');
$router->post('/meetings/{id}/agenda',        'Meeting@addAgendaItem');

// Spannungen
$router->get('/circles/{cid}/tensions',       'Tension@index');
$router->get('/tensions/new',                 'Tension@create');
$router->post('/tensions',                    'Tension@store');
$router->get('/tensions/{id}',                'Tension@show');
$router->post('/tensions/{id}',               'Tension@update');

// Mitglieder
$router->get('/members',                      'Member@index');
$router->get('/members/new',                  'Member@create');
$router->post('/members',                     'Member@store');
$router->get('/members/{id}',                 'Member@show');
$router->get('/members/{id}/edit',            'Member@edit');
$router->post('/members/{id}',                'Member@update');
$router->post('/members/{id}/circles',        'Member@updateCircles');
$router->post('/members/{id}/roles',          'Member@assignRole');
$router->post('/members/{id}/roles/{assignmentId}/end', 'Member@endRole');

// Auth
$router->get('/login',                        'Auth@loginForm');
$router->post('/logout',                      'Auth@logout');
$router->get('/auth/nextcloud',                'Auth@nextcloudStart');
$router->get('/auth/nextcloud/callback',       'Auth@nextcloudCallback');

// Vereinbarungs-Versionen
$router->get('/agreements/{id}/versions',                        'Agreement@versions');
$router->get('/agreements/{id}/versions/{version}',              'Agreement@showVersion');
$router->post('/agreements/{id}/versions/{version}/restore',     'Agreement@restoreVersion');

// Delegationen
$router->get('/delegations',                  'Delegation@index');
$router->get('/delegations/new',              'Delegation@create');
$router->post('/delegations',                 'Delegation@store');
$router->get('/delegations/{id}',             'Delegation@show');
$router->get('/delegations/{id}/edit',        'Delegation@edit');
$router->post('/delegations/{id}',            'Delegation@update');
$router->post('/delegations/{id}/end',        'Delegation@end');
$router->post('/delegations/{id}/delete',     'Delegation@delete');
$router->get('/api/delegations/roles',        'Delegation@rolesForCircles');

// Admin
$router->get('/admin',                        'Admin@index');
$router->post('/admin',                       'Admin@update');
$router->get('/admin/export',                 'Admin@export');

// ------------------------------------------------------------------
//  Request dispatchen
// ------------------------------------------------------------------
$router->dispatch($method, $path);
