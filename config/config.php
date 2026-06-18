<?php
/**
 * Soziokratisches Logbuch — Konfiguration
 *
 * Kopiere diese Datei nach config/config.local.php und passe
 * die Werte an. config.local.php wird NICHT ins Repository eingecheckt.
 */

return [

    // ----------------------------------------------------------
    //  Datenbank
    // ----------------------------------------------------------
    'db' => [
        'host'    => 'localhost',
        'port'    => 3306,
        'name'    => 'logbuch',
        'user'    => 'logbuch_user',
        'pass'    => 'geheimes_passwort',
        'charset' => 'utf8mb4',
    ],

    // ----------------------------------------------------------
    //  Anwendung
    // ----------------------------------------------------------
    'app' => [
        'name'     => 'Soziokratisches Logbuch',
        'base_url' => 'https://logbuch.example.org',  // kein trailing slash
        'locale'   => 'de_DE',
        'timezone' => 'Europe/Berlin',
        'debug'    => false,   // auf true setzen beim Entwickeln
    ],

    // ----------------------------------------------------------
    //  Session
    // ----------------------------------------------------------
    'session' => [
        'name'     => 'logbuch_sess',
        'lifetime' => 7200,   // Sekunden (2 Stunden)
        'secure'   => true,   // false, wenn kein HTTPS lokal
        'httponly' => true,
        'samesite' => 'Lax',
    ],

    // ----------------------------------------------------------
    //  Pfade (absolut)
    // ----------------------------------------------------------
    'paths' => [
        'root'      => dirname(__DIR__),
        'templates' => dirname(__DIR__) . '/templates',
        'exports'   => dirname(__DIR__) . '/exports',
        'assets'    => dirname(__DIR__) . '/assets',
    ],

];
