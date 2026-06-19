<?php
$base = rtrim($config['app']['base_url'], '/');
$statusLabels = [
    'active'     => 'Aktiv',
    'draft'      => 'Entwurf',
    'review_due' => 'Review fällig',
    'expired'    => 'Abgelaufen',
];
?>

<nav class="breadcrumb">
    <a href="<?= $base ?>/circles">Kreise</a>
    <span class="breadcrumb__sep">/</span>
    <a href="<?= $base ?>/circles/<?= $agreement['circle_id'] ?>/agreements">Vereinbarungen</a>
    <span class="breadcrumb__sep">/</span>
    <a href="<?= $base ?>/agreements/<?= $agreement['id'] ?>"><?= htmlspecialchars($agreement['title']) ?></a>
    <span class="breadcrumb__sep">/</span>
    <span>Versionshistorie</span>
</nav>

<div class="page-header">
    <div>
        <h1 class="page-header__title">Versionshistorie</h1>
        <p class="page-header__sub"><?= htmlspecialchars($agreement['title']) ?></p>
    </div>
    <div class="page-header__actions">
        <a href="<?= $base ?>/agreements/<?= $agreement['id'] ?>" class="btn btn--secondary">
            <i class="ti ti-arrow-left" aria-hidden="true"></i> Zur Vereinbarung
        </a>
    </div>
</div>

<!-- Aktueller Stand -->
<div class="card">
    <div class="card__header">
        <span class="card__title">
            <i class="ti ti-clock-check" aria-hidden="true" style="color:var(--c-accent)"></i>
            Aktueller Stand
        </span>
        <span class="badge badge--active">Aktuell</span>
    </div>
    <div class="card__body">
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:var(--sp-4)">
            <div>
                <div class="text-xs text-muted" style="text-transform:uppercase;letter-spacing:.04em;margin-bottom:4px">Titel</div>
                <div class="text-sm fw-600"><?= htmlspecialchars($agreement['title']) ?></div>
            </div>
            <div>
                <div class="text-xs text-muted" style="text-transform:uppercase;letter-spacing:.04em;margin-bottom:4px">Status</div>
                <span class="badge badge--<?= htmlspecialchars($agreement['status']) ?>">
                    <?= htmlspecialchars($statusLabels[$agreement['status']] ?? $agreement['status']) ?>
                </span>
            </div>
            <div>
                <div class="text-xs text-muted" style="text-transform:uppercase;letter-spacing:.04em;margin-bottom:4px">Beschlossen</div>
                <div class="text-sm"><?= date('d.m.Y', strtotime($agreement['agreed_at'])) ?></div>
            </div>
            <?php if ($agreement['review_date']): ?>
                <div>
                    <div class="text-xs text-muted" style="text-transform:uppercase;letter-spacing:.04em;margin-bottom:4px">Review</div>
                    <div class="text-sm"><?= date('d.m.Y', strtotime($agreement['review_date'])) ?></div>
                </div>
            <?php endif; ?>
        </div>
        <div style="margin-top:var(--sp-3)">
            <a href="<?= $base ?>/agreements/<?= $agreement['id'] ?>/edit" class="btn btn--secondary btn--sm">
                <i class="ti ti-edit" aria-hidden="true"></i> Bearbeiten (erzeugt neue Version)
            </a>
        </div>
    </div>
</div>

<!-- Versionshistorie -->
<?php if (empty($versions)): ?>
    <div class="card">
        <div class="empty-state">
            <i class="ti ti-history-off" aria-hidden="true"></i>
            <span class="empty-state__title">Noch keine Versionen</span>
            <p class="empty-state__body">
                Beim nächsten Speichern wird automatisch eine Version angelegt.
            </p>
        </div>
    </div>
<?php else: ?>
    <div class="card">
        <div class="card__header">
            <span class="card__title">
                <i class="ti ti-history" aria-hidden="true"></i>
                Frühere Versionen (<?= count($versions) ?>)
            </span>
        </div>
        <div class="card__body--flush">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width:60px">Version</th>
                        <th>Titel</th>
                        <th>Status</th>
                        <th>Beschlossen</th>
                        <th>Geändert von</th>
                        <th>Änderungsgrund</th>
                        <th>Gespeichert am</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($versions as $v): ?>
                        <tr>
                            <td>
                                <span class="badge badge--draft" style="font-family:var(--font-mono)">
                                    v<?= $v['version'] ?>
                                </span>
                            </td>
                            <td class="fw-600">
                                <a href="<?= $base ?>/agreements/<?= $agreement['id'] ?>/versions/<?= $v['version'] ?>">
                                    <?= htmlspecialchars($v['title']) ?>
                                </a>
                            </td>
                            <td>
                                <span class="badge badge--<?= htmlspecialchars($v['status']) ?>">
                                    <?= htmlspecialchars($statusLabels[$v['status']] ?? $v['status']) ?>
                                </span>
                            </td>
                            <td class="text-sm"><?= date('d.m.Y', strtotime($v['agreed_at'])) ?></td>
                            <td class="text-sm"><?= htmlspecialchars($v['changed_by_name'] ?? '—') ?></td>
                            <td class="text-sm text-muted">
                                <?= $v['change_note'] ? htmlspecialchars($v['change_note']) : '—' ?>
                            </td>
                            <td class="text-sm text-muted">
                                <?= date('d.m.Y H:i', strtotime($v['created_at'])) ?>
                            </td>
                            <td>
                                <div class="table__actions">
                                    <a href="<?= $base ?>/agreements/<?= $agreement['id'] ?>/versions/<?= $v['version'] ?>"
                                       class="btn btn--ghost btn--sm" title="Version ansehen">
                                        <i class="ti ti-eye" aria-hidden="true"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>
