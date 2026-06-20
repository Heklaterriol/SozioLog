<?php
/**
 * SozioLog Installer — läuft nur, solange config/config.php fehlt.
 * Wird automatisch von public/index.php eingebunden.
 */

declare(strict_types=1);

$root   = dirname(__DIR__);
$target = $root . '/config/config.php';
$sample = $root . '/config/sample.config.php';

if (file_exists($target)) {
    http_response_code(403);
    echo 'Installation bereits abgeschlossen.';
    exit;
}

$errors  = [];
$success = false;

// Europäische Zeitzonen zuerst, Rest alphabetisch dahinter
$euZones    = ['Europe/Berlin', 'Europe/Vienna', 'Europe/Zurich', 'Europe/Amsterdam', 'Europe/Brussels',
               'Europe/Paris', 'Europe/Madrid', 'Europe/Rome', 'Europe/Lisbon', 'Europe/London',
               'Europe/Dublin', 'Europe/Copenhagen', 'Europe/Stockholm', 'Europe/Oslo', 'Europe/Helsinki',
               'Europe/Warsaw', 'Europe/Prague', 'Europe/Budapest', 'Europe/Athens', 'Europe/Bucharest',
               'Europe/Sofia', 'Europe/Kyiv', 'Europe/Istanbul', 'UTC'];
$allZones   = \DateTimeZone::listIdentifiers();
$otherZones = array_values(array_diff($allZones, $euZones));
$timezones  = array_merge($euZones, $otherZones);

$encryptions = ['tls' => 'TLS (empfohlen, Port 587)', 'ssl' => 'SSL (Port 465)', '' => 'Keine'];

// Formularwerte (mit sinnvollen Defaults vorbelegt)
$v = [
    'app_name'     => 'Soziokratisches Logbuch',
    'base_url'     => (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? 'localhost'),
    'timezone'     => 'Europe/Berlin',
    'db_host'      => 'localhost',
    'db_port'      => '3306',
    'db_name'      => '',
    'db_user'      => '',
    'db_pass'      => '',
    'mail_host'       => '',
    'mail_port'       => '587',
    'mail_encryption' => 'tls',
    'mail_username'   => '',
    'mail_password'   => '',
    'mail_from_email' => '',
    'mail_from_name'  => '',
    'admin_name'      => 'Administrator',
    'admin_email'     => '',
    'admin_email_confirm' => '',
    'admin_password'  => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($v as $key => $default) {
        $v[$key] = trim((string) ($_POST[$key] ?? $default));
    }

    if ($v['db_name'] === '' || $v['db_user'] === '') {
        $errors[] = 'Datenbankname und Benutzer sind erforderlich.';
    }
    if ($v['admin_email'] === '' || !filter_var($v['admin_email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Gültige Admin-E-Mail ist erforderlich.';
    } elseif ($v['admin_email'] !== $v['admin_email_confirm']) {
        $errors[] = 'Die beiden E-Mail-Adressen stimmen nicht überein.';
    }
    if (strlen($v['admin_password']) < 8) {
        $errors[] = 'Admin-Passwort muss mindestens 8 Zeichen haben.';
    }

    // Datenbankverbindung testen
    $pdo = null;
    if (!$errors) {
        try {
            $dsn = "mysql:host={$v['db_host']};port={$v['db_port']};dbname={$v['db_name']};charset=utf8mb4";
            $pdo = new PDO($dsn, $v['db_user'], $v['db_pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
        } catch (\PDOException $e) {
            $errors[] = 'Datenbankverbindung fehlgeschlagen: ' . $e->getMessage();
        }
    }

    // Schema einspielen (nur Tabellen, die noch nicht existieren)
    if (!$errors && $pdo) {
        try {
            $sql   = file_get_contents($root . '/database/install.sql');
            $sql   = preg_replace('/^--.*$/m', '', $sql); // Kommentarzeilen entfernen
            $stmts = array_filter(array_map('trim', explode(';', $sql)));
            foreach ($stmts as $stmt) {
                if ($stmt !== '') {
                    $pdo->exec($stmt);
                }
            }
        } catch (\PDOException $e) {
            $errors[] = 'Schema-Import fehlgeschlagen: ' . $e->getMessage();
        }
    }

    // Seed-Admin durch echten Admin ersetzen
    if (!$errors && $pdo) {
        try {
            $hash = password_hash($v['admin_password'], PASSWORD_BCRYPT);
            $pdo->prepare("DELETE FROM members WHERE email = 'admin@example.org'")->execute();
            $stmt = $pdo->prepare(
                "INSERT INTO members (name, email, password_hash, is_admin, permission_level)
                 VALUES (?, ?, ?, 1, 'admin')"
            );
            $stmt->execute([$v['admin_name'], strtolower($v['admin_email']), $hash]);
        } catch (\PDOException $e) {
            $errors[] = 'Admin-Anlage fehlgeschlagen: ' . $e->getMessage();
        }
    }

    // config.php schreiben
    if (!$errors) {
        $tpl = file_get_contents($sample);
        $tpl = str_replace(
            ["'localhost',\n        'port'    => 3306,\n        'name'    => 'logbuch',\n        'user'    => 'logbuch_user',\n        'pass'    => 'geheimes_passwort',",
             "'name'     => 'Soziokratisches Logbuch',\n        'base_url' => 'https://logbuch.example.org',  // kein trailing slash\n        'locale'   => 'de_DE',\n        'timezone' => 'Europe/Berlin',",
             "'host'        => 'smtp.example.org',\n        'port'        => 587,\n        'encryption'  => 'tls',     // 'tls', 'ssl' oder '' (kein Verschlüsselung)\n        'username'    => 'logbuch@example.org',\n        'password'    => '',\n        'from_email'  => 'logbuch@example.org',\n        'from_name'   => 'Soziokratisches Logbuch',"],
            ["'{$v['db_host']}',\n        'port'    => {$v['db_port']},\n        'name'    => '{$v['db_name']}',\n        'user'    => '{$v['db_user']}',\n        'pass'    => '" . addslashes($v['db_pass']) . "',",
             "'name'     => '" . addslashes($v['app_name']) . "',\n        'base_url' => '" . rtrim($v['base_url'], '/') . "',\n        'locale'   => 'de_DE',\n        'timezone' => '{$v['timezone']}',",
             "'host'        => '{$v['mail_host']}',\n        'port'        => {$v['mail_port']},\n        'encryption'  => '{$v['mail_encryption']}',\n        'username'    => '" . addslashes($v['mail_username']) . "',\n        'password'    => '" . addslashes($v['mail_password']) . "',\n        'from_email'  => '" . addslashes($v['mail_from_email']) . "',\n        'from_name'   => '" . addslashes($v['mail_from_name']) . "',"],
            $tpl
        );

        if (file_put_contents($target, $tpl) === false) {
            $errors[] = 'config.php konnte nicht geschrieben werden — Schreibrechte für config/ prüfen.';
        } else {
            $success = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<title>SozioLog — Installation</title>
<style>
    body { font-family: system-ui, sans-serif; background: #F5F3EE; color: #2B2A26; max-width: 640px; margin: 40px auto; padding: 0 20px; }
    h1 { font-size: 1.4rem; }
    fieldset { border: 1px solid #DDD8CC; border-radius: 8px; margin-bottom: 16px; padding: 16px; }
    legend { font-weight: 600; padding: 0 6px; }
    label { display: block; font-size: .85rem; margin: 10px 0 4px; }
    input, select { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 6px; box-sizing: border-box; font: inherit; }
    .pw-wrap { position: relative; }
    .pw-wrap input { padding-right: 36px; }
    .pw-toggle { position: absolute; right: 8px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; padding: 4px; font-size: 1rem; line-height: 1; }
    button { background: #1D6F6A; color: #fff; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-size: 1rem; }
    .error { background: #FBE9E7; color: #A13A2E; padding: 10px; border-radius: 6px; margin-bottom: 6px; }
    .success { background: #E8F3E8; color: #2E6B30; padding: 16px; border-radius: 6px; }
    .row { display: flex; gap: 12px; }
    .row > div { flex: 1; }
</style>
</head>
<body>
<h1>SozioLog installieren</h1>

<?php if ($success): ?>
    <div class="success">
        <strong>Installation abgeschlossen.</strong><br>
        <code>config/config.php</code> wurde erstellt, das Datenbankschema eingespielt
        und dein Admin-Konto angelegt. Diese Seite ist jetzt deaktiviert.<br><br>
        <a href="<?= htmlspecialchars(rtrim($v['base_url'], '/')) ?>/login">Zum Login →</a>
    </div>
<?php else: ?>

    <?php foreach ($errors as $e): ?>
        <div class="error"><?= htmlspecialchars($e) ?></div>
    <?php endforeach; ?>

    <form method="post">
        <fieldset>
            <legend>Anwendung</legend>
            <label>Name</label>
            <input name="app_name" value="<?= htmlspecialchars($v['app_name']) ?>">
            <label>Basis-URL (ohne abschließenden Slash)</label>
            <input name="base_url" value="<?= htmlspecialchars($v['base_url']) ?>">
            <label>Zeitzone</label>
            <select name="timezone">
                <?php foreach ($timezones as $tz): ?>
                    <option value="<?= htmlspecialchars($tz) ?>" <?= $tz === $v['timezone'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($tz) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </fieldset>

        <fieldset>
            <legend>Datenbank</legend>
            <div class="row">
                <div><label>Host</label><input name="db_host" value="<?= htmlspecialchars($v['db_host']) ?>"></div>
                <div><label>Port</label><input name="db_port" value="<?= htmlspecialchars($v['db_port']) ?>"></div>
            </div>
            <label>Datenbankname</label>
            <input name="db_name" value="<?= htmlspecialchars($v['db_name']) ?>" required>
            <label>Benutzer</label>
            <input name="db_user" value="<?= htmlspecialchars($v['db_user']) ?>" required>
            <label>Passwort</label>
            <input type="password" name="db_pass" value="<?= htmlspecialchars($v['db_pass']) ?>">
        </fieldset>

        <fieldset>
            <legend>SMTP (für Passwort-Reset, optional)</legend>
            <div class="row">
                <div><label>Host</label><input name="mail_host" value="<?= htmlspecialchars($v['mail_host']) ?>"></div>
                <div><label>Port</label><input name="mail_port" value="<?= htmlspecialchars($v['mail_port']) ?>"></div>
            </div>
            <label>Verschlüsselung</label>
            <select name="mail_encryption">
                <?php foreach ($encryptions as $val => $label): ?>
                    <option value="<?= htmlspecialchars($val) ?>" <?= $val === $v['mail_encryption'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($label) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <label>Benutzername</label>
            <input name="mail_username" value="<?= htmlspecialchars($v['mail_username']) ?>">
            <label>Passwort</label>
            <input type="password" name="mail_password" value="<?= htmlspecialchars($v['mail_password']) ?>">
            <label>Absender-E-Mail</label>
            <input name="mail_from_email" value="<?= htmlspecialchars($v['mail_from_email']) ?>">
            <label>Absender-Name</label>
            <input name="mail_from_name" value="<?= htmlspecialchars($v['mail_from_name']) ?>">
        </fieldset>

        <fieldset>
            <legend>Admin-Konto</legend>
            <label>Name</label>
            <input name="admin_name" value="<?= htmlspecialchars($v['admin_name']) ?>">
            <label>E-Mail</label>
            <input type="email" name="admin_email" value="<?= htmlspecialchars($v['admin_email']) ?>" required>
            <label>E-Mail bestätigen</label>
            <input type="email" name="admin_email_confirm" value="<?= htmlspecialchars($v['admin_email_confirm']) ?>" required>
            <label>Passwort (min. 8 Zeichen)</label>
            <div class="pw-wrap">
                <input type="password" id="admin_password" name="admin_password" required>
                <button type="button" class="pw-toggle" onclick="
                    var i=document.getElementById('admin_password');
                    i.type = i.type === 'password' ? 'text' : 'password';
                    this.textContent = i.type === 'password' ? '👁' : '🙈';
                ">👁</button>
            </div>
        </fieldset>

        <button type="submit">Installieren</button>
    </form>
<?php endif; ?>
</body>
</html>
