# Soziokratisches Logbuch

Webbasiertes Logbuch für soziokratisch organisierte Gruppen und Organisationen.  
PHP 8.3+ · MySQL 8+ / MariaDB 10.2.7+ · Apache 2.4+ · kein Framework · Anmeldung per Nextcloud-SSO

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
| **Mitglieder** | Berechtigungsstufen (Admin/Mitglied/Lesend), Kreiszugehörigkeit, Rollenzuweisung mit Befristung, Anmeldung per Nextcloud-SSO |
| **Export** | JSON-Dump (sofort) · PDF-Logbuch via mPDF (optional) |

**Sicherheit:** CSRF-Schutz auf allen POST-Formularen, Session-Auth, Prepared Statements (PDO), Security-Header per `.htaccess`

---

## Berechtigungsstufen

Jede Person hat genau eine Stufe (`members.permission_level`):

| | Admin | Mitglied | Lesend |
|---|:---:|:---:|:---:|
| Liste der Kreise sehen | ✓ | ✓ | ✓ |
| Mitglieder der Kreise sehen | ✓ | ✓ | ✓ |
| Rollen verwalten | ✓ | im eigenen Kreis | — |
| Mitglieder verwalten | ✓ | im eigenen Kreis | — |
| Neuen Kreis anlegen | ✓ | — | — |
| Person anlegen | ✓ | im eigenen Kreis | — |
| Vereinbarung anlegen | ✓ | im eigenen Kreis | — |
| Spannung einreichen | ✓ | im eigenen Kreis | — |
| Schreiben (Protokolle, Beschlüsse etc.) | ✓ | im eigenen Kreis | — |
| PDF/JSON-Export | ✓ | — | — |

**„Eigener Kreis"** = die Kreise, denen eine Person auf ihrer Mitglieder-Detailseite
direkt zugeordnet wurde (unabhängig von Rollen). Ein Mitglied kann mehreren Kreisen
zugeordnet sein. Die Berechtigungsstufe selbst sowie globale Aktionen (Kreis anlegen,
Delegationen, Export) bleiben ausschließlich Admins vorbehalten — so kann sich niemand
selbst hochstufen.

Die gesamte Logik liegt zentral in `src/Helper/Permissions.php` — neue Rechte lassen
sich dort als weitere `can*()`-Methode ergänzen, ohne an anderer Stelle im Code suchen
zu müssen.

---

## Voraussetzungen

| Komponente | Version |
|---|---|
| PHP | ≥ 8.3, Erweiterungen `pdo_mysql`, `curl` |
| MySQL / MariaDB | ≥ 8.0 / ≥ 10.5 |
| Apache | ≥ 2.4 mit `mod_rewrite` |
| Composer | optional, nur für PDF-Export (mPDF) |
| Nextcloud-Instanz | für die Anmeldung (SSO), mit OAuth2-Client (Admin-Einstellungen → Sicherheit) |

---

## Installation

### 1. Dateien hochladen

Lade das Projektverzeichnis auf deinen Server. `DocumentRoot` auf `public/` setzen.

### 2. Composer-Pakete installieren (optional)

```bash
composer install               # nur falls composer.lock Pakete enthält
composer require mpdf/mpdf      # optional, für PDF-Export
```

### 3. Leere Datenbank anlegen

```sql
CREATE DATABASE logbuch CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'logbuch_user'@'localhost' IDENTIFIED BY 'sicheres_passwort';
GRANT ALL PRIVILEGES ON logbuch.* TO 'logbuch_user'@'localhost';
FLUSH PRIVILEGES;
```

### 4. Nextcloud OAuth2-Client anlegen

In Nextcloud: Einstellungen → Sicherheit → OAuth2-Client hinzufügen.
Redirect-URI: `https://<deine-soziolog-url>/auth/nextcloud/callback`.
Client-ID und Client-Secret notieren — werden im nächsten Schritt gebraucht.

### 5. Installer aufrufen

Im Browser die Seite öffnen — solange `config/config.php` fehlt, erscheint
automatisch der Installer. Dort Datenbank-, Nextcloud- und Admin-Zugangsdaten
eintragen. Der Installer schreibt `config/config.php`, spielt
`database/install.sql` ein und legt das Admin-Konto an. Danach ist die
Installer-Seite gesperrt.

---

## Dateistruktur

```
logbuch/
├── assets/
│   ├── css/app.css              Design-System (CSS Custom Properties, alle Komponenten)
│   └── js/app.js               Tab-Umschaltung, Flash-Auto-close, Textarea-Resize
├── config/
│   ├── sample.config.php       Vorlage — Installer erzeugt daraus config.php
│   └── config.php              wird vom Installer erzeugt, NICHT im Git
│   └── config.local.php        Lokale Überschreibung — NICHT ins Repository!
├── database/
│   └── install.sql              Vollständiges Schema (Erstinstallation)
├── public/
│   ├── index.php               Front-Controller (Composer- & Autoloader, Session, Router)
│   ├── install.php             Installer — läuft nur solange config.php fehlt
│   └── .htaccess               Pretty URLs + Security-Header
├── src/
│   ├── Database.php            PDO-Singleton (fetchAll, fetchOne, insert, transaction …)
│   ├── Router.php              URL-Dispatcher mit {platzhalter}-Support
│   ├── Controller/
│   │   ├── BaseController.php  render(), redirect(), flash(), csrf(), permissions()
│   │   ├── AuthController.php  Login / Logout / Nextcloud-OAuth2 (SSO)
│   │   ├── DashboardController.php
│   │   ├── CircleController.php
│   │   ├── MeetingController.php
│   │   ├── AgreementController.php
│   │   ├── RoleController.php
│   │   ├── TensionController.php
│   │   ├── MemberController.php + Kreiszuordnung & Rollenzuweisung
│   │   └── AdminController.php  + JSON/PDF-Export
│   ├── Helper/
│   │   ├── Mailer.php           PHPMailer-Wrapper, aktuell ungenutzt
│   │   └── Permissions.php      Zentrale Berechtigungslogik (admin/member/readonly)
│   ├── Middleware/
│   │   └── AuthMiddleware.php
│   └── Model/
│       ├── CircleModel.php      + findMembers() inkl. direkter Zuordnung
│       ├── MeetingModel.php
│       ├── AgreementModel.php
│       ├── RoleModel.php        + findAllWithCircle()
│       ├── TensionModel.php
│       └── MemberModel.php      + Kreiszuordnung, Rollenzuweisung, Nextcloud-Verknüpfung
└── templates/
    ├── layout/main.php          Sidebar-Layout
    ├── layout/bare.php          Login-Seite (ohne Sidebar)
    ├── auth/login.php           Nextcloud-Login-Button
    ├── dashboard/index.php
    ├── circles/                 index · show · form
    ├── roles/                   index · show · form
    ├── agreements/               index · show · form
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
| `/login` | Anmelden (Nextcloud-Button) |
| `/auth/nextcloud` | Startet den Nextcloud-OAuth2-Flow |
| `/auth/nextcloud/callback` | OAuth2-Rücksprung von Nextcloud |
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
| `/members/{id}` | Mitglieder-Detail (Kreiszugehörigkeit, Rollen, Berechtigung) |
| `/members/new` | Person anlegen |
| `/admin` | Administration + Export |

---

## Sicherheitshinweise

- `config/config.local.php` **niemals** ins Git einchecken → `.gitignore` eintragen
- `exports/` und `database/` dürfen vom Web nicht erreichbar sein
- In Produktion: `'debug' => false`
- HTTPS nutzen (HSTS in `.htaccess` ist vorbereitet, nur auskommentiert)
- **Nextcloud-SSO:** Das OAuth2-`state`-Token verhindert CSRF beim Login.
  Nextclouds OAuth2 kennt keine Scopes — ein Access-Token hat vollen
  Zugriff auf den Nextcloud-Account des Nutzers. SozioLog nutzt den
  Token nur einmalig zum Abruf von Name/E-Mail/Gruppen über die
  OCS-User-API und speichert ihn nicht.
