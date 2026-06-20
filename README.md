# Soziokratisches Logbuch

Webbasiertes Logbuch für soziokratisch organisierte Gruppen und Organisationen.  
PHP 8.3+ · MySQL 8+ / MariaDB 10.2.7+ · Apache 2.4+ · kein Framework · Composer für Passwort-Reset (PHPMailer)

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
| **Mitglieder** | Berechtigungsstufen (Admin/Mitglied/Lesend), Kreiszugehörigkeit, Rollenzuweisung mit Befristung, Passwort-Hashing, Passwort-Reset per E-Mail |
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
| PHP | ≥ 8.3, Erweiterung `pdo_mysql` |
| MySQL / MariaDB | ≥ 8.0 / ≥ 10.5 |
| Apache | ≥ 2.4 mit `mod_rewrite` |
| Composer | **erforderlich** für Passwort-Reset (PHPMailer), optional für PDF-Export (mPDF) |
| SMTP-Zugang | für den Versand der Passwort-Reset-Mails (eigener Mailserver, Gmail, Mailgun, …) |

---

## Installation

### 1. Dateien hochladen

Lade das Projektverzeichnis auf deinen Server. `DocumentRoot` auf `public/` setzen.

### 2. Composer-Pakete installieren

```bash
composer install
composer require mpdf/mpdf   # optional, für PDF-Export
```

### 3. Leere Datenbank anlegen

```sql
CREATE DATABASE logbuch CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'logbuch_user'@'localhost' IDENTIFIED BY 'sicheres_passwort';
GRANT ALL PRIVILEGES ON logbuch.* TO 'logbuch_user'@'localhost';
FLUSH PRIVILEGES;
```

### 4. Installer aufrufen

Im Browser die Seite öffnen — solange `config/config.php` fehlt, erscheint
automatisch der Installer. Dort Datenbank-, SMTP- und Admin-Zugangsdaten
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
│   │   ├── AuthController.php  Login / Logout / Passwort-Reset
│   │   ├── DashboardController.php
│   │   ├── CircleController.php
│   │   ├── MeetingController.php
│   │   ├── AgreementController.php
│   │   ├── RoleController.php
│   │   ├── TensionController.php
│   │   ├── MemberController.php + Kreiszuordnung & Rollenzuweisung
│   │   └── AdminController.php  + JSON/PDF-Export
│   ├── Helper/
│   │   ├── Mailer.php           PHPMailer-Wrapper für SMTP-Versand
│   │   └── Permissions.php      Zentrale Berechtigungslogik (admin/member/readonly)
│   ├── Middleware/
│   │   └── AuthMiddleware.php
│   └── Model/
│       ├── CircleModel.php      + findMembers() inkl. direkter Zuordnung
│       ├── MeetingModel.php
│       ├── AgreementModel.php
│       ├── RoleModel.php        + findAllWithCircle()
│       ├── TensionModel.php
│       └── MemberModel.php      + Kreiszuordnung, Rollenzuweisung, Passwort-Reset
└── templates/
    ├── layout/main.php          Sidebar-Layout
    ├── layout/bare.php          Login-/Reset-Seiten (ohne Sidebar)
    ├── auth/login.php
    ├── auth/forgot_password.php
    ├── auth/reset_password.php
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
| `/members/{id}` | Mitglieder-Detail (Kreiszugehörigkeit, Rollen, Berechtigung) |
| `/members/new` | Person anlegen |
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
