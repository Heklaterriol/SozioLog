<?php
$base = rtrim($config['app']['base_url'], '/');

/** Rekursive Ausgabe des Kreisbaums */
function renderCircleTree(array $nodes, string $base): void {
    foreach ($nodes as $node): ?>
        <div class="circle-node">
            <div class="circle-node__icon" aria-hidden="true">
                <i class="ti ti-circle"></i>
            </div>
            <div class="circle-node__info">
                <a href="<?= $base ?>/circles/<?= $node['id'] ?>" class="circle-node__name">
                    <?= htmlspecialchars($node['name']) ?>
                </a>
                <?php if ($node['driver']): ?>
                    <div class="circle-node__meta" title="<?= htmlspecialchars($node['driver']) ?>">
                        <?= htmlspecialchars(mb_substr($node['driver'], 0, 80)) ?><?= mb_strlen($node['driver']) > 80 ? '…' : '' ?>
                    </div>
                <?php endif; ?>
            </div>
            <div style="display:flex;gap:var(--sp-2);flex-shrink:0">
                <a href="<?= $base ?>/circles/<?= $node['id'] ?>/meetings" class="btn btn--ghost btn--sm" title="Meetings">
                    <i class="ti ti-notes" aria-hidden="true"></i>
                </a>
                <a href="<?= $base ?>/circles/<?= $node['id'] ?>/agreements" class="btn btn--ghost btn--sm" title="Vereinbarungen">
                    <i class="ti ti-file-text" aria-hidden="true"></i>
                </a>
                <?php if (!empty($currentUser['is_admin'])): ?>
                    <a href="<?= $base ?>/circles/<?= $node['id'] ?>/edit" class="btn btn--ghost btn--sm" title="Bearbeiten">
                        <i class="ti ti-edit" aria-hidden="true"></i>
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <?php if (!empty($node['children'])): ?>
            <div class="circle-children">
                <?php renderCircleTree($node['children'], $base); ?>
            </div>
        <?php endif; ?>
    <?php endforeach;
}
?>

<div class="page-header">
    <div>
        <h1 class="page-header__title">Kreise</h1>
        <p class="page-header__sub">Alle aktiven Kreise der Organisation</p>
    </div>
    <?php if (!empty($currentUser['is_admin'])): ?>
        <div class="page-header__actions">
            <a href="<?= $base ?>/circles/new" class="btn btn--primary">
                <i class="ti ti-plus" aria-hidden="true"></i> Neuer Kreis
            </a>
        </div>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card__body--flush">
        <?php if (empty($tree)): ?>
            <div class="empty-state">
                <i class="ti ti-circle-off" aria-hidden="true"></i>
                <span class="empty-state__title">Noch keine Kreise</span>
                <?php if (!empty($currentUser['is_admin'])): ?>
                    <a href="<?= $base ?>/circles/new" class="btn btn--primary" style="margin-top:var(--sp-2)">
                        <i class="ti ti-plus" aria-hidden="true"></i> Ersten Kreis anlegen
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="circle-tree" style="padding:var(--sp-4)">
                <?php renderCircleTree($tree, $base); ?>
            </div>
        <?php endif; ?>
    </div>
</div>
