# Soziokratisches Logbuch

Webbasiertes Logbuch für Organisationen nach **Sociocracy 3.0 (S3)**.  
PHP 8.1+ · MySQL 8+ · Apache 2.4+ · kein Framework · Composer für Passwort-Reset (PHPMailer)

---

## Funktionsumfang

| Modul | Funktionen |
|---|---|
| **Kreise** | Hierarchische Struktur (Über-/Unterkreise), Treiber, Domäne, Zweck, Archivierung |
| **Rollen** | Rollenbeschreibung mit Accountabilities, Rollentypen (Rep-Link, Del-Link, Wahl …), Belegungs­verlauf |
| **Vereinbarungen** | Volltext, Treiber, Status, Review-Datum, Meeting-Verknüpfung |
| **Meetings** | Protokoll mit Agenda-Punkten, Teilnehmenden, Moderator·in & Protokollant·in |
| **Spannungen** | Einreichen, Status-Workflow, Auflösung via Vereinbarung |
| **Dashboard** | Kennzahlen, anstehende Meetings, Review-fällige Vereinbarungen, unbesetzte Rollen |
| **Mitglieder** | Benutzerverwaltung mit Admin-Rollen, Passwort-Hashing, Passwort-Reset per E-Mail |
| **Export** | JSON-Dump (sofort) · PDF-Logbuch via mPDF (optional) |

**Sicherheit:** CSRF-Schutz auf allen POST-Formularen, Session-Auth, Prepared Statements (PDO), Security-Header per `.htaccess`

---

## Voraussetzungen

| Komponente | Version |
|---|---|
| PHP | ≥ 8.1, Erweiterung `pdo_mysql` |
| MySQL / MariaDB | ≥ 8.0 / ≥ 10.5 |
| Apache | ≥ 2.4 mit `mod_rewrite` |
| Composer | **erforderlich** für Passwort-Reset (PHPMailer), optional für PDF-Export (mPDF) |
| SMTP-Zugang | für den Versand der Passwort-Reset-Mails (eigener Mailserver, Gmail, Mailgun, …) |

---

## Installation

### 1. Dateien hochladen

Lade das Projektverzeichnis auf deinen Server.  
**Empfehlung:** `DocumentRoot` direkt auf `public/` setzen.

```
/var/www/logbuch/
├── public/          ← DocumentRoot
│   ├── index.php
│   ├── .htaccess
│   └── assets/
├── src/
├── config/
├── templates/
└── database/
```

### 2. Datenbank anlegen

```sql
CREATE DATABASE logbuch CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'logbuch_user'@'localhost' IDENTIFIED BY 'sicheres_passwort';
GRANT ALL PRIVILEGES ON logbuch.* TO 'logbuch_user'@'localhost';
FLUSH PRIVILEGES;
```

Schema einspielen:

```bash
mysql -u logbuch_user -p logbuch < database/schema.sql
```

**Bestehende Installation aktualisieren?** Statt `schema.sql` neu einzuspielen
reicht die Migration:

```bash
mysql -u logbuch_user -p logbuch < database/migration_003_password_resets.sql
```

### 3. Konfiguration

```bash
cp config/config.php config/config.local.php
nano config/config.local.php
```

Mindest-Anpassungen:

```php
return [
    'db' => [
        'host' => 'localhost',
        'name' => 'logbuch',
        'user' => 'logbuch_user',
        'pass' => 'sicheres_passwort',
    ],
    'app' => [
        'name'     => 'Meine Organisation',
        'base_url' => 'https://logbuch.example.org',
        'debug'    => false,
    ],
    'mail' => [
        'host'       => 'smtp.example.org',
        'port'       => 587,
        'encryption' => 'tls',
        'username'   => 'logbuch@example.org',
        'password'   => 'smtp_passwort',
        'from_email' => 'logbuch@example.org',
        'from_name'  => 'Meine Organisation',
    ],
];
```

### 4. Composer-Pakete installieren (Pflicht)

`PHPMailer` wird für den Versand der Passwort-Reset-Mails benötigt:

```bash
cd /pfad/zum/logbuch
composer install
```

Das legt einen `vendor/`-Ordner an, der automatisch eingebunden wird.
Ohne diesen Schritt funktioniert nur „Passwort vergessen“ nicht — der
Rest der Anwendung läuft trotzdem.

### 5. Admin-Passwort setzen

```bash
# Hash erzeugen:
php -r "echo password_hash('mein_passwort', PASSWORD_BCRYPT);"
```

```sql
UPDATE members SET password_hash = '$2y$12$...' WHERE id = 1;
UPDATE members SET email = 'admin@meineorg.de'  WHERE id = 1;
```

Alternativ kann das Passwort jetzt auch bequem über **„Passwort
vergessen?“** auf der Login-Seite gesetzt werden, sobald SMTP
konfiguriert ist.

### 6. PDF-Export (optional)

```bash
cd /pfad/zum/logbuch
composer require mpdf/mpdf
```

Danach ist der «PDF herunterladen»-Button unter `/admin` aktiv.

---

## Dateistruktur

```
logbuch/
├── assets/
│   ├── css/app.css              Design-System (CSS Custom Properties, alle Komponenten)
│   └── js/app.js               Tab-Umschaltung, Flash-Auto-close, Textarea-Resize
├── config/
│   ├── config.php              Standard-Konfiguration (db, app, session, mail)
│   └── config.local.php        Lokale Überschreibung — NICHT ins Repository!
├── database/
│   ├── schema.sql               10 Tabellen mit FKs, Indizes, ENUMs, Seed-Admin
│   ├── migration_002_versions_delegations.sql
│   └── migration_003_password_resets.sql
├── public/
│   ├── index.php               Front-Controller (Composer- & Autoloader, Session, Router)
│   └── .htaccess               Pretty URLs + Security-Header
├── src/
│   ├── Database.php            PDO-Singleton (fetchAll, fetchOne, insert, transaction …)
│   ├── Router.php              URL-Dispatcher mit {platzhalter}-Support
│   ├── Controller/
│   │   ├── BaseController.php  render(), redirect(), flash(), csrf(), currentUser()
│   │   ├── AuthController.php  Login / Logout / Passwort-Reset
│   │   ├── DashboardController.php
│   │   ├── CircleController.php
│   │   ├── MeetingController.php
│   │   ├── AgreementController.php
│   │   ├── RoleController.php
│   │   ├── TensionController.php
│   │   ├── MemberController.php
│   │   └── AdminController.php  + JSON/PDF-Export
│   ├── Helper/
│   │   └── Mailer.php           PHPMailer-Wrapper für SMTP-Versand
│   ├── Middleware/
│   │   └── AuthMiddleware.php
│   └── Model/
│       ├── CircleModel.php
│       ├── MeetingModel.php
│       ├── AgreementModel.php
│       ├── RoleModel.php
│       ├── TensionModel.php
│       └── MemberModel.php      + Passwort-Reset-Tokens
└── templates/
    ├── layout/main.php          Sidebar-Layout
    ├── layout/bare.php          Login-/Reset-Seiten (ohne Sidebar)
    ├── auth/login.php
    ├── auth/forgot_password.php
    ├── auth/reset_password.php
    ├── dashboard/index.php
    ├── circles/                 index · show · form
    ├── roles/                   index · show · form
    ├── agreements/              index · show · form
    ├── meetings/                index · show · form
    ├── tensions/                index · show · form
    ├── members/                 index · show · form
    └── admin/index.php
```

---

## URL-Übersicht

| URL | Beschreibung |
|---|---|
| `/` | Dashboard |
| `/login` | Anmelden |
| `/password/forgot` | Passwort vergessen — E-Mail anfordern |
| `/password/reset/{token}` | Neues Passwort festlegen (Link aus der E-Mail) |
| `/circles` | Kreisbaum |
| `/circles/{id}` | Kreis-Detail (Tabs: Rollen, Vereinbarungen, Meetings, Spannungen, Mitglieder) |
| `/circles/{id}/roles` | Rollen eines Kreises |
| `/roles/{id}` | Rollen-Detail + Zuweisung |
| `/circles/{id}/agreements` | Vereinbarungen |
| `/agreements/{id}` | Vereinbarungs-Detail |
| `/circles/{id}/meetings` | Meeting-Liste |
| `/meetings/{id}` | Protokoll (Agenda, Vereinbarungen, Spannungen) |
| `/circles/{id}/tensions` | Spannungen |
| `/tensions/{id}` | Spannungs-Detail + Auflösung |
| `/members` | Mitgliederliste |
| `/admin` | Administration + Export |

---

## Sicherheitshinweise

- `config/config.local.php` **niemals** ins Git einchecken → `.gitignore` eintragen
- `exports/` und `database/` dürfen vom Web nicht erreichbar sein
- In Produktion: `'debug' => false`
- HTTPS nutzen (HSTS in `.htaccess` ist vorbereitet, nur auskommentiert)
- **Passwort-Reset:** Tokens werden nur als SHA-256-Hash in der Tabelle
  `password_resets` gespeichert, sind standardmäßig 60 Minuten gültig,
  einmal verwendbar und werden bei jeder neuen Anfrage für dasselbe
  Konto entwertet. Die „Passwort vergessen“-Antwort ist absichtlich
  immer identisch (unabhängig davon, ob die E-Mail existiert), damit
  sich keine registrierten Adressen erraten lassen.
