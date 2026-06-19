<?php
$base = rtrim($config['app']['base_url'], '/');

$statusLabels = [
    'active'     => 'Aktiv',
    'draft'      => 'Entwurf',
    'review_due' => 'Review fällig',
    'expired'    => 'Abgelaufen',
];

$filterOptions = [
    'all'        => 'Alle',
    'active'     => 'Aktiv',
    'review_due' => 'Review fällig',
    'expired'    => 'Abgelaufen',
    'draft'      => 'Entwurf',
];
?>

<nav class="breadcrumb">
    <a href="<?= $base ?>/circles">Kreise</a>
    <span class="breadcrumb__sep">/</span>
    <a href="<?= $base ?>/circles/<?= $circle['id'] ?>"><?= htmlspecialchars($circle['name']) ?></a>
    <span class="breadcrumb__sep">/</span>
    <span>Vereinbarungen</span>
</nav>

<div class="page-header">
    <div>
        <h1 class="page-header__title">Vereinbarungen</h1>
        <p class="page-header__sub"><?= htmlspecialchars($circle['name']) ?></p>
    </div>
    <div class="page-header__actions">
        <a href="<?= $base ?>/agreements/new?circle_id=<?= $circle['id'] ?>" class="btn btn--primary">
            <i class="ti ti-plus" aria-hidden="true"></i> Neue Vereinbarung
        </a>
    </div>
</div>

<!-- Filter-Tabs -->
<div class="tabs">
    <?php foreach ($filterOptions as $key => $label): ?>
        <a href="?status=<?= $key ?>"
           class="tab-btn <?= ($filter ?? 'all') === $key ? 'tab-btn--active' : '' ?>"
           style="text-decoration:none">
            <?= $label ?>
        </a>
    <?php endforeach; ?>
</div>

<div class="card">
    <div class="card__body--flush">
        <?php if (empty($agreements)): ?>
            <div class="empty-state">
                <i class="ti ti-file-off" aria-hidden="true"></i>
                <span class="empty-state__title">Keine Vereinbarungen</span>
                <p class="empty-state__body">Für den gewählten Filter wurden keine Einträge gefunden.</p>
                <a href="<?= $base ?>/agreements/new?circle_id=<?= $circle['id'] ?>" class="btn btn--primary" style="margin-top:var(--sp-2)">
                    <i class="ti ti-plus" aria-hidden="true"></i> Vereinbarung anlegen
                </a>
            </div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Titel</th>
                            <th>Status</th>
                            <th>Beschlossen</th>
                            <th>Review</th>
                            <th>Erstellt von</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($agreements as $a):
                            $reviewDate  = $a['review_date'] ? strtotime($a['review_date']) : null;
                            $reviewClass = '';
                            if ($reviewDate) {
                                $reviewClass = $reviewDate < time() ? 'style="color:var(--c-error)"' : ($reviewDate < strtotime('+30 days') ? 'style="color:var(--c-warning)"' : '');
                            }
                        ?>
                            <tr>
                                <td>
                                    <a href="<?= $base ?>/agreements/<?= $a['id'] ?>" class="fw-600">
                                        <?= htmlspecialchars($a['title']) ?>
                                    </a>
                                    <?php if ($a['driver']): ?>
                                        <div class="text-xs text-muted" style="margin-top:2px">
                                            <?= htmlspecialchars(mb_substr($a['driver'], 0, 70)) ?><?= mb_strlen($a['driver']) > 70 ? '…' : '' ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge badge--<?= htmlspecialchars($a['status']) ?>">
                                        <?= htmlspecialchars($statusLabels[$a['status']] ?? $a['status']) ?>
                                    </span>
                                </td>
                                <td class="text-sm">
                                    <?= date('d.m.Y', strtotime($a['agreed_at'])) ?>
                                </td>
                                <td class="text-sm" <?= $reviewClass ?>>
                                    <?= $reviewDate ? date('d.m.Y', $reviewDate) : '—' ?>
                                </td>
                                <td class="text-sm text-muted">
                                    <?= htmlspecialchars($a['created_by_name'] ?? '—') ?>
                                </td>
                                <td>
                                    <div class="table__actions">
                                        <a href="<?= $base ?>/agreements/<?= $a['id'] ?>" class="btn btn--ghost btn--sm" title="Anzeigen">
                                            <i class="ti ti-eye" aria-hidden="true"></i>
                                        </a>
                                        <a href="<?= $base ?>/agreements/<?= $a['id'] ?>/edit" class="btn btn--ghost btn--sm" title="Bearbeiten">
                                            <i class="ti ti-edit" aria-hidden="true"></i>
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
