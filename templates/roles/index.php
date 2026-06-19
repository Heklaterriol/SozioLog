<?php
$base = rtrim($config['app']['base_url'], '/');
$roleTypeLabels = [
    'general'       => 'Allgemein',
    'facilitator'   => 'Moderator·in',
    'secretary'     => 'Sekretär·in',
    'rep_link'      => 'Rep-Link',
    'delegate_link' => 'Del-Link',
    'elected'       => 'Gewählt',
];
?>

<nav class="breadcrumb">
    <a href="<?= $base ?>/circles">Kreise</a>
    <span class="breadcrumb__sep">/</span>
    <a href="<?= $base ?>/circles/<?= $circle['id'] ?>"><?= htmlspecialchars($circle['name']) ?></a>
    <span class="breadcrumb__sep">/</span>
    <span>Rollen</span>
</nav>

<div class="page-header">
    <div>
        <h1 class="page-header__title">Rollen</h1>
        <p class="page-header__sub"><?= htmlspecialchars($circle['name']) ?></p>
    </div>
    <?php if (!empty($currentUser['is_admin'])): ?>
        <div class="page-header__actions">
            <a href="<?= $base ?>/circles/<?= $circle['id'] ?>/roles/new" class="btn btn--primary">
                <i class="ti ti-plus" aria-hidden="true"></i> Neue Rolle
            </a>
        </div>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card__body--flush">
        <?php if (empty($roles)): ?>
            <div class="empty-state">
                <i class="ti ti-user-off" aria-hidden="true"></i>
                <span class="empty-state__title">Noch keine Rollen definiert</span>
                <?php if (!empty($currentUser['is_admin'])): ?>
                    <a href="<?= $base ?>/circles/<?= $circle['id'] ?>/roles/new" class="btn btn--primary" style="margin-top:var(--sp-2)">
                        <i class="ti ti-plus" aria-hidden="true"></i> Erste Rolle anlegen
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Rolle</th>
                            <th>Typ</th>
                            <th>Zweck</th>
                            <th>Aktuelle Besetzung</th>
                            <th>Besetzt seit</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($roles as $r):
                            $typeLabel = $roleTypeLabels[$r['role_type']] ?? $r['role_type'];
                        ?>
                            <tr>
                                <td>
                                    <a href="<?= $base ?>/roles/<?= $r['id'] ?>" class="fw-600">
                                        <?= htmlspecialchars($r['name']) ?>
                                    </a>
                                    <?php if ($r['is_elected']): ?>
                                        <span class="badge badge--elected" style="margin-left:4px">Wahl</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge badge--<?= htmlspecialchars($r['role_type']) ?>">
                                        <?= htmlspecialchars($typeLabel) ?>
                                    </span>
                                </td>
                                <td class="text-sm text-muted" style="max-width:220px">
                                    <?php if ($r['purpose']): ?>
                                        <?= htmlspecialchars(mb_substr($r['purpose'], 0, 80)) ?><?= mb_strlen($r['purpose']) > 80 ? '…' : '' ?>
                                    <?php else: ?>
                                        <span>—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-sm">
                                    <?php if ($r['current_holder']): ?>
                                        <span class="d-flex align-center gap-2">
                                            <i class="ti ti-user-check" style="color:var(--c-success)" aria-hidden="true"></i>
                                            <?= htmlspecialchars($r['current_holder']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span style="color:var(--c-error)">
                                            <i class="ti ti-user-x" aria-hidden="true"></i> Unbesetzt
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-sm text-muted">
                                    <?= $r['start_date'] ? date('d.m.Y', strtotime($r['start_date'])) : '—' ?>
                                    <?php if ($r['elected_until']): ?>
                                        <br><span class="text-xs">bis <?= date('d.m.Y', strtotime($r['elected_until'])) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="table__actions">
                                        <a href="<?= $base ?>/roles/<?= $r['id'] ?>" class="btn btn--ghost btn--sm" title="Details">
                                            <i class="ti ti-eye" aria-hidden="true"></i>
                                        </a>
                                        <?php if (!empty($currentUser['is_admin'])): ?>
                                            <a href="<?= $base ?>/roles/<?= $r['id'] ?>/edit" class="btn btn--ghost btn--sm" title="Bearbeiten">
                                                <i class="ti ti-edit" aria-hidden="true"></i>
                                            </a>
                                        <?php endif; ?>
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
