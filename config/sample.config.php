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
    //  E-Mail-Versand (SMTP) — aktuell ungenutzt seit Umstellung
    //  auf Nextcloud-Login, für künftige Benachrichtigungen.
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
    ],

    // ----------------------------------------------------------
    //  Nextcloud SSO (OAuth2) — alleinige Login-Methode
    //  Client unter Nextcloud-Einstellungen → Sicherheit → OAuth2
    //  anlegen, Redirect-URI: {app.base_url}/auth/nextcloud/callback
    // ----------------------------------------------------------
    'nextcloud' => [
        'base_url'      => 'https://cloud.example.org',  // kein trailing slash
        // true, wenn KEINE Pretty-URLs aktiv sind (URLs mit /index.php/)
        'use_index_php' => false,
        'client_id'     => '',
        'client_secret' => '',
        // Nur Mitglieder dieser Nextcloud-Gruppe dürfen sich einloggen
        'required_group' => '',
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
