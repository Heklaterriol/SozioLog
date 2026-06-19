<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Logbuch') ?> — <?= htmlspecialchars($config['app']['name']) ?></title>
    <link rel="stylesheet" href="<?= rtrim($config['app']['base_url'], '/') ?>/assets/css/app.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.10.0/dist/tabler-icons.min.css">
</head>
<body>

<!-- ============================================================
     SIDEBAR
============================================================ -->
<nav class="sidebar" aria-label="Hauptnavigation">

    <div class="sidebar__brand">
        <span class="sidebar__brand-icon" aria-hidden="true">◎</span>
        <span class="sidebar__brand-name"><?= htmlspecialchars($config['app']['name']) ?></span>
    </div>

    <?php
    $currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $base        = rtrim($config['app']['base_url'], '/');

    $navItems = [
        ['href' => '/',            'icon' => 'ti-layout-dashboard', 'label' => 'Dashboard'],
        ['href' => '/circles',     'icon' => 'ti-circle',           'label' => 'Kreise'],
        ['href' => '/delegations', 'icon' => 'ti-arrow-right-circle','label' => 'Delegationen'],
        ['href' => '/members',     'icon' => 'ti-users',            'label' => 'Mitglieder'],
        ['href' => '/admin',       'icon' => 'ti-settings',         'label' => 'Admin', 'admin' => true],
    ];
    ?>

    <ul class="sidebar__nav" role="list">
        <?php foreach ($navItems as $item): ?>
            <?php if (!empty($item['admin']) && empty($currentUser['is_admin'])) continue; ?>
            <?php $active = ($currentPath === $base . $item['href']
                          || ($item['href'] !== '/' && str_starts_with($currentPath, $base . $item['href']))); ?>
            <li>
                <a href="<?= $base . $item['href'] ?>"
                   class="sidebar__link <?= $active ? 'sidebar__link--active' : '' ?>"
                   <?= $active ? 'aria-current="page"' : '' ?>>
                    <i class="ti <?= $item['icon'] ?>" aria-hidden="true"></i>
                    <span><?= $item['label'] ?></span>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>

    <div class="sidebar__footer">
        <?php if ($currentUser): ?>
            <div class="sidebar__user">
                <div class="sidebar__user-avatar" aria-hidden="true">
                    <?= mb_strtoupper(mb_substr($currentUser['name'], 0, 1)) ?>
                </div>
                <div class="sidebar__user-info">
                    <span class="sidebar__user-name"><?= htmlspecialchars($currentUser['name']) ?></span>
                    <?php if ($currentUser['is_admin']): ?>
                        <span class="sidebar__user-role">Admin</span>
                    <?php endif; ?>
                </div>
            </div>
            <form method="post" action="<?= $base ?>/logout">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf ?? '') ?>">
                <button type="submit" class="sidebar__logout" title="Abmelden">
                    <i class="ti ti-logout" aria-hidden="true"></i>
                </button>
            </form>
        <?php endif; ?>
    </div>
</nav>

<!-- ============================================================
     MAIN CONTENT
============================================================ -->
<main class="main" id="main-content">

    <?php if ($flashMsg): ?>
        <div class="flash flash--<?= htmlspecialchars($flashMsg['type']) ?>" role="alert">
            <i class="ti <?= match($flashMsg['type']) {
                'success' => 'ti-circle-check',
                'error'   => 'ti-alert-circle',
                'warning' => 'ti-alert-triangle',
                default   => 'ti-info-circle',
            } ?>" aria-hidden="true"></i>
            <?= htmlspecialchars($flashMsg['message']) ?>
            <button class="flash__close" onclick="this.parentElement.remove()" aria-label="Schließen">
                <i class="ti ti-x" aria-hidden="true"></i>
            </button>
        </div>
    <?php endif; ?>

    <div class="page">
        <?= $content ?>
    </div>

</main>

<script src="<?= $base ?>/assets/js/app.js"></script>
</body>
</html>
