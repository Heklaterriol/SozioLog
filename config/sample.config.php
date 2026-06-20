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
    //  E-Mail-Versand (SMTP, für Passwort-Reset etc.)
    //  In config.local.php überschreiben!
    // ----------------------------------------------------------
    'mail' => [
        'host'        => 'smtp.example.org',
        'port'        => 587,
        'encryption'  => 'tls',     // 'tls', 'ssl' oder '' (kein Verschlüsselung)
        'username'    => 'logbuch@example.org',
        'password'    => '',
        'from_email'  => 'logbuch@example.org',
        'from_name'   => 'Soziokratisches Logbuch',
        // Reset-Link verfällt nach dieser Zeit (Sekunden)
        'reset_token_ttl' => 3600,
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
