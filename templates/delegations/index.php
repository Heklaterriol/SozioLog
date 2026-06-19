<?php $base = rtrim($config['app']['base_url'], '/'); ?>

<div class="page-header">
    <div>
        <h1 class="page-header__title">Delegationen</h1>
        <p class="page-header__sub">Übertragung von Autorität zwischen Kreisen</p>
    </div>
    <div class="page-header__actions">
        <?php if (!empty($currentUser['is_admin'])): ?>
            <a href="<?= $base ?>/delegations/new" class="btn btn--primary">
                <i class="ti ti-plus" aria-hidden="true"></i> Neue Delegation
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- Filter -->
<div class="tabs">
    <a href="<?= $base ?>/delegations"
       class="tab-btn <?= !$includeEnded ? 'tab-btn--active' : '' ?>"
       style="text-decoration:none">Aktive</a>
    <a href="<?= $base ?>/delegations?ended=1"
       class="tab-btn <?= $includeEnded ? 'tab-btn--active' : '' ?>"
       style="text-decoration:none">Alle inkl. beendete</a>
</div>

<?php if (empty($delegations)): ?>
    <div class="card">
        <div class="empty-state">
            <i class="ti ti-arrow-right-circle" aria-hidden="true"></i>
            <span class="empty-state__title">Noch keine Delegationen</span>
            <p class="empty-state__body">
                Eine Delegation beschreibt, welche Autorität ein Überkreis an einen Unterkreis überträgt,
                und welche Rollen (Rep-Link / Del-Link) die Verbindung halten.
            </p>
            <?php if (!empty($currentUser['is_admin'])): ?>
                <a href="<?= $base ?>/delegations/new" class="btn btn--primary" style="margin-top:var(--sp-3)">
                    <i class="ti ti-plus" aria-hidden="true"></i> Erste Delegation anlegen
                </a>
            <?php endif; ?>
        </div>
    </div>
<?php else: ?>

    <?php
    // Delegationen nach Überkreis gruppieren
    $grouped = [];
    foreach ($delegations as $d) {
        $grouped[$d['from_circle_name']][] = $d;
    }
    ksort($grouped);
    ?>

    <?php foreach ($grouped as $fromName => $items): ?>
        <div class="card">
            <div class="card__header">
                <span class="card__title">
                    <i class="ti ti-circle" aria-hidden="true"></i>
                    <?= htmlspecialchars($fromName) ?>
                    <span class="text-muted" style="font-weight:400;font-size:.85em">delegiert an</span>
                </span>
            </div>
            <div class="card__body--flush">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Empfangender Kreis</th>
                            <th>Beschreibung</th>
                            <th>Rep-Link</th>
                            <th>Del-Link</th>
                            <th>Seit</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $d): ?>
                            <tr>
                                <td>
                                    <a href="<?= $base ?>/delegations/<?= $d['id'] ?>" class="fw-600">
                                        <?= htmlspecialchars($d['to_circle_name']) ?>
                                    </a>
                                </td>
                                <td class="text-sm text-muted" style="max-width:200px">
                                    <?php if ($d['description']): ?>
                                        <?= htmlspecialchars(mb_substr($d['description'], 0, 70)) ?><?= mb_strlen($d['description']) > 70 ? '…' : '' ?>
                                    <?php else: ?>—<?php endif; ?>
                                </td>
                                <td class="text-sm">
                                    <?php if ($d['rep_link_name']): ?>
                                        <div><?= htmlspecialchars($d['rep_link_name']) ?></div>
                                        <?php if ($d['rep_link_holder']): ?>
                                            <div class="text-xs text-muted"><?= htmlspecialchars($d['rep_link_holder']) ?></div>
                                        <?php else: ?>
                                            <div class="text-xs" style="color:var(--c-error)">unbesetzt</div>
                                        <?php endif; ?>
                                    <?php else: ?>—<?php endif; ?>
                                </td>
                                <td class="text-sm">
                                    <?php if ($d['del_link_name']): ?>
                                        <div><?= htmlspecialchars($d['del_link_name']) ?></div>
                                        <?php if ($d['del_link_holder']): ?>
                                            <div class="text-xs text-muted"><?= htmlspecialchars($d['del_link_holder']) ?></div>
                                        <?php else: ?>
                                            <div class="text-xs" style="color:var(--c-error)">unbesetzt</div>
                                        <?php endif; ?>
                                    <?php else: ?>—<?php endif; ?>
                                </td>
                                <td class="text-sm text-muted">
                                    <?= $d['started_at'] ? date('d.m.Y', strtotime($d['started_at'])) : '—' ?>
                                </td>
                                <td>
                                    <?php if ($d['status'] === 'active'): ?>
                                        <span class="badge badge--active">Aktiv</span>
                                    <?php else: ?>
                                        <span class="badge badge--expired">Beendet</span>
                                        <?php if ($d['ended_at']): ?>
                                            <div class="text-xs text-muted"><?= date('d.m.Y', strtotime($d['ended_at'])) ?></div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="table__actions">
                                        <a href="<?= $base ?>/delegations/<?= $d['id'] ?>"
                                           class="btn btn--ghost btn--sm" title="Detail">
                                            <i class="ti ti-eye" aria-hidden="true"></i>
                                        </a>
                                        <?php if (!empty($currentUser['is_admin'])): ?>
                                            <a href="<?= $base ?>/delegations/<?= $d['id'] ?>/edit"
                                               class="btn btn--ghost btn--sm" title="Bearbeiten">
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
        </div>
    <?php endforeach; ?>

<?php endif; ?>
