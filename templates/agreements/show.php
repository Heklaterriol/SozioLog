<?php
$base        = rtrim($config['app']['base_url'], '/');
$statusLabel = ['active' => 'Aktiv', 'draft' => 'Entwurf', 'review_due' => 'Review fällig', 'expired' => 'Abgelaufen'];
$reviewDate  = $agreement['review_date'] ? strtotime($agreement['review_date']) : null;
$isPastReview = $reviewDate && $reviewDate < time();
?>

<nav class="breadcrumb">
    <a href="<?= $base ?>/circles">Kreise</a>
    <span class="breadcrumb__sep">/</span>
    <a href="<?= $base ?>/circles/<?= $agreement['circle_id'] ?>"><?= htmlspecialchars($agreement['circle_name']) ?></a>
    <span class="breadcrumb__sep">/</span>
    <a href="<?= $base ?>/circles/<?= $agreement['circle_id'] ?>/agreements">Vereinbarungen</a>
    <span class="breadcrumb__sep">/</span>
    <span><?= htmlspecialchars($agreement['title']) ?></span>
</nav>

<div class="page-header">
    <div>
        <h1 class="page-header__title"><?= htmlspecialchars($agreement['title']) ?></h1>
        <p class="page-header__sub">
            <?= htmlspecialchars($agreement['circle_name']) ?>
            · Beschlossen am <?= date('d.m.Y', strtotime($agreement['agreed_at'])) ?>
        </p>
    </div>
    <div class="page-header__actions">
        <a href="<?= $base ?>/agreements/<?= $agreement['id'] ?>/edit" class="btn btn--secondary">
            <i class="ti ti-edit" aria-hidden="true"></i> Bearbeiten
        </a>
    </div>
</div>

<!-- Meta-Banner -->
<div class="card">
    <div class="card__body">
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:var(--sp-5)">
            <div>
                <div class="text-xs text-muted" style="text-transform:uppercase;letter-spacing:.04em;margin-bottom:4px">Status</div>
                <span class="badge badge--<?= htmlspecialchars($agreement['status']) ?>">
                    <?= htmlspecialchars($statusLabel[$agreement['status']] ?? $agreement['status']) ?>
                </span>
            </div>
            <div>
                <div class="text-xs text-muted" style="text-transform:uppercase;letter-spacing:.04em;margin-bottom:4px">Beschlossen</div>
                <div class="text-sm fw-600"><?= date('d.m.Y', strtotime($agreement['agreed_at'])) ?></div>
            </div>
            <div>
                <div class="text-xs text-muted" style="text-transform:uppercase;letter-spacing:.04em;margin-bottom:4px">Review</div>
                <?php if ($reviewDate): ?>
                    <div class="text-sm fw-600" style="<?= $isPastReview ? 'color:var(--c-error)' : '' ?>">
                        <?= date('d.m.Y', $reviewDate) ?>
                        <?= $isPastReview ? ' <span class="badge badge--expired">überfällig</span>' : '' ?>
                    </div>
                <?php else: ?>
                    <div class="text-sm text-muted">—</div>
                <?php endif; ?>
            </div>
            <div>
                <div class="text-xs text-muted" style="text-transform:uppercase;letter-spacing:.04em;margin-bottom:4px">Erstellt von</div>
                <div class="text-sm fw-600"><?= htmlspecialchars($agreement['created_by_name'] ?? '—') ?></div>
            </div>
        </div>
    </div>
</div>

<?php if ($agreement['driver']): ?>
    <div class="card">
        <div class="card__header">
            <span class="card__title"><i class="ti ti-bulb" aria-hidden="true"></i> Treiber / Ausgangslage</span>
        </div>
        <div class="card__body">
            <p class="text-sm" style="white-space:pre-line"><?= htmlspecialchars($agreement['driver']) ?></p>
        </div>
    </div>
<?php endif; ?>

<?php if ($agreement['body']): ?>
    <div class="card">
        <div class="card__header">
            <span class="card__title"><i class="ti ti-file-text" aria-hidden="true"></i> Inhalt der Vereinbarung</span>
        </div>
        <div class="card__body">
            <div class="text-sm" style="white-space:pre-line;line-height:1.75">
                <?= htmlspecialchars($agreement['body']) ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if (!$agreement['driver'] && !$agreement['body']): ?>
    <div class="card">
        <div class="card__body">
            <p class="text-sm text-muted">Kein Inhalt hinterlegt. <a href="<?= $base ?>/agreements/<?= $agreement['id'] ?>/edit">Jetzt bearbeiten</a></p>
        </div>
    </div>
<?php endif; ?>

<!-- Versionshistorie-Link -->
<div style="display:flex;justify-content:flex-end">
    <a href="<?= $base ?>/agreements/<?= $agreement['id'] ?>/versions"
       class="btn btn--ghost btn--sm">
        <i class="ti ti-history" aria-hidden="true"></i> Versionshistorie ansehen
    </a>
</div>
