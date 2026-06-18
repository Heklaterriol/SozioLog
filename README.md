# Soziokratisches Logbuch

Ein webbasiertes Logbuch für Organisationen, die nach **Sociocracy 3.0 (S3)** arbeiten.

---

## Funktionsumfang

- **Kreisstruktur** — hierarchische Über-/Unterkreise mit Treiber, Domäne, Zweck
- **Rollen** — Rollenbeschreibungen inkl. Accountabilities, gewählte Rollen, Rollenverlauf
- **Vereinbarungen** — Beschlüsse mit Review-Datum, Status, Verlinkung zum Meeting
- **Meetings** — Protokolle mit Agenda-Punkten, Teilnehmenden, Moderator & Protokollant
- **Spannungen** — Eingabe, Bearbeitung, Auflösung via Vereinbarung
- **Dashboard** — Kennzahlen, anstehende Meetings, fällige Reviews, unbesetzte Rollen
- **CSRF-Schutz**, Session-Auth, Admin-Rollen

---

## Voraussetzungen

| Komponente | Version    |
|------------|------------|
| PHP        | ≥ 8.1      |
| MySQL      | ≥ 8.0 (oder MariaDB ≥ 10.5) |
| Apache     | ≥ 2.4 mit `mod_rewrite` |

---

## Installation

### 1. Dateien hochladen

Lade das gesamte Projektverzeichnis auf deinen Server.  
Idealerweise zeigt der **DocumentRoot** direkt auf `public/`.

```
/var/www/logbuch/
├── public/          ← DocumentRoot hier
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

### 3. Konfiguration

```bash
cp config/config.php config/config.local.php
```

`config/config.local.php` bearbeiten:

```php
return [
    'db' => [
        'host' => 'localhost',
        'name' => 'logbuch',
        'user' => 'logbuch_user',
        'pass' => 'sicheres_passwort',
    ],
    'app' => [
        'base_url' => 'https://logbuch.meineorganisation.org',
        'debug'    => false,
    ],
];
```

### 4. Admin-Passwort setzen

Das Schema legt einen Platzhalter-Admin an. Passwort-Hash erzeugen:

```php
php -r "echo password_hash('mein_passwort', PASSWORD_BCRYPT);"
```

In der Datenbank aktualisieren:

```sql
UPDATE members SET password_hash = '$2y$12$...' WHERE email = 'admin@example.org';
UPDATE members SET email = 'echte@email.org' WHERE id = 1;
```

---

## Dateistruktur

```
logbuch/
├── assets/
│   ├── css/app.css          Alle Styles (Design-Tokens, Layout, Komponenten)
│   └── js/app.js            Tabs, Flash, dynamische Listen
├── config/
│   ├── config.php           Standardkonfiguration
│   └── config.local.php     Lokale Überschreibung (nicht ins Repo!)
├── database/
│   └── schema.sql           Datenbankschema mit allen 9 Tabellen
├── public/
│   ├── index.php            Front-Controller (Autoloader, Router, Session)
│   └── .htaccess            Pretty URLs + Security-Header
├── src/
│   ├── Database.php         PDO-Singleton mit Helfer-Methoden
│   ├── Router.php           URL-Dispatcher mit {platzhalter}-Support
│   ├── Controller/
│   │   ├── BaseController.php
│   │   ├── AuthController.php
│   │   ├── DashboardController.php
│   │   ├── CircleController.php
│   │   ├── MeetingController.php
│   │   └── ...              (Role, Agreement, Tension, Member, Admin)
│   ├── Middleware/
│   │   └── AuthMiddleware.php
│   └── Model/
│       ├── CircleModel.php
│       ├── MeetingModel.php
│       └── Models.php       (Agreement, Role, Tension, Member)
└── templates/
    ├── layout/
    │   ├── main.php         Sidebar + Flash + Content-Wrapper
    │   └── bare.php         Login-Seite ohne Sidebar
    ├── auth/login.php
    ├── dashboard/index.php
    ├── circles/             index.php, form.php, show.php
    ├── meetings/            index.php, form.php, show.php
    ├── agreements/          index.php, form.php, show.php
    ├── roles/               index.php, form.php, show.php
    ├── tensions/            index.php, form.php, show.php
    └── members/             index.php, form.php, show.php
```

---

## Noch fehlende Controller/Templates

Die folgenden Dateien sind als Stubs vorbereitet (Modelle vorhanden) und können nach gleichem Muster ausgefüllt werden:

- `RoleController` + Templates `roles/`
- `AgreementController` + Templates `agreements/`
- `TensionController` + Templates `tensions/`
- `MemberController` + Templates `members/`
- `AdminController` + Templates `admin/`

---

## Sicherheitshinweise

- `config/config.local.php` **niemals** ins Git-Repository einchecken (`.gitignore`!)
- `exports/` und `database/` vor Webzugriff schützen
- In Produktion `'debug' => false` in der Konfiguration
- HTTPS verwenden (HSTS-Header ist in `.htaccess` vorbereitet, nur auskommentiert)
