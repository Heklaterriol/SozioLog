<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Anmelden') ?> — <?= htmlspecialchars($config['app']['name']) ?></title>
    <link rel="stylesheet" href="<?= rtrim($config['app']['base_url'], '/') ?>/assets/css/app.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.10.0/dist/tabler-icons.min.css">
</head>
<body class="body--bare">

    <?php if ($flashMsg): ?>
        <div class="flash flash--<?= htmlspecialchars($flashMsg['type']) ?>" role="alert" style="position:fixed;top:1rem;left:50%;transform:translateX(-50%);z-index:100">
            <i class="ti <?= $flashMsg['type'] === 'error' ? 'ti-alert-circle' : 'ti-circle-check' ?>" aria-hidden="true"></i>
            <?= htmlspecialchars($flashMsg['message']) ?>
        </div>
    <?php endif; ?>

    <div class="auth-wrap">
        <?= $content ?>
    </div>

</body>
</html>
