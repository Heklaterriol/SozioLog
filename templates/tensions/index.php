<?php
$base = rtrim($config['app']['base_url'], '/');
$statusLabels = [
    'open'        => 'Offen',
    'in_progress' => 'In Bearbeitung',
    'resolved'    => 'Gelöst',
    'dropped'     => 'Fallen gelassen',
];
$filterOptions = [
    ''            => 'Alle',
    'open'        => 'Offen',
    'in_progress' => 'In Bearbeitung',
    'resolved'    => 'Gelöst',
    'dropped'     => 'Fallen gelassen',
];
?>

<nav class="breadcrumb">
    <a href="<?= $base ?>/circles">Kreise</a>
    <span class="breadcrumb__sep">/</span>
    <a href="<?= $base ?>/circles/<?= $circle['id'] ?>"><?= htmlspecialchars($circle['name']) ?></a>
    <span class="breadcrumb__sep">/</span>
    <span>Spannungen</span>
</nav>

<div class="page-header">
    <div>
        <h1 class="page-header__title">Spannungen</h1>
        <p class="page-header__sub"><?= htmlspecialchars($circle['name']) ?></p>
    </div>
    <div class="page-header__actions">
        <a href="<?= $base ?>/tensions/new?circle_id=<?= $circle['id'] ?>" class="btn btn--primary">
            <i class="ti ti-plus" aria-hidden="true"></i> Spannung einreichen
        </a>
    </div>
</div>

<!-- Filter -->
<div class="tabs">
    <?php foreach ($filterOptions as $key => $label): ?>
        <a href="?status=<?= $key ?>"
           class="tab-btn <?= ($filter ?? '') === $key ? 'tab-btn--active' : '' ?>"
           style="text-decoration:none">
            <?= $label ?>
        </a>
    <?php endforeach; ?>
</div>

<div class="card">
    <div class="card__body--flush">
        <?php if (empty($tensions)): ?>
            <div class="empty-state">
                <i class="ti ti-mood-happy" aria-hidden="true"></i>
                <span class="empty-state__title">Keine Spannungen gefunden</span>
                <a href="<?= $base ?>/tensions/new?circle_id=<?= $circle['id'] ?>"
                   class="btn btn--primary" style="margin-top:var(--sp-2)">
                    <i class="ti ti-plus" aria-hidden="true"></i> Spannung einreichen
                </a>
            </div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Titel / Beschreibung</th>
                            <th>Status</th>
                            <th>Eingereicht von</th>
                            <th>Datum</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tensions as $t): ?>
                            <tr>
                                <td>
                                    <a href="<?= $base ?>/tensions/<?= $t['id'] ?>" class="fw-600">
                                        <?= htmlspecialchars($t['title'] ?: mb_substr($t['description'] ?? '', 0, 70)) ?>
                                        <?= !$t['title'] && mb_strlen($t['description'] ?? '') > 70 ? '…' : '' ?>
                                    </a>
                                </td>
                                <td>
                                    <span class="badge badge--<?= htmlspecialchars($t['status']) ?>">
                                        <?= htmlspecialchars($statusLabels[$t['status']] ?? $t['status']) ?>
                                    </span>
                                </td>
                                <td class="text-sm"><?= htmlspecialchars($t['raised_by_name'] ?? '—') ?></td>
                                <td class="text-sm text-muted">
                                    <?= date('d.m.Y', strtotime($t['created_at'])) ?>
                                </td>
                                <td>
                                    <div class="table__actions">
                                        <a href="<?= $base ?>/tensions/<?= $t['id'] ?>"
                                           class="btn btn--ghost btn--sm" title="Details">
                                            <i class="ti ti-eye" aria-hidden="true"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
