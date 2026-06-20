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
    <?php if ($circle['parent_id']): ?>
        <a href="<?= $base ?>/circles/<?= $circle['parent_id'] ?>"><?= htmlspecialchars($circle['parent_name']) ?></a>
        <span class="breadcrumb__sep">/</span>
    <?php endif; ?>
    <span><?= htmlspecialchars($circle['name']) ?></span>
</nav>

<div class="page-header">
    <div>
        <h1 class="page-header__title">
            <i class="ti ti-circle" style="color:var(--c-accent);font-size:.9em" aria-hidden="true"></i>
            <?= htmlspecialchars($circle['name']) ?>
            <?php if ($circle['status'] === 'archived'): ?>
                <span class="badge badge--archived" style="font-size:.55em;vertical-align:middle">Archiviert</span>
            <?php endif; ?>
        </h1>
        <?php if ($circle['purpose']): ?>
            <p class="page-header__sub"><?= htmlspecialchars($circle['purpose']) ?></p>
        <?php endif; ?>
    </div>
    <div class="page-header__actions">
        <?php if ($perm->canWriteMeetingsIn($circle['id'])): ?>
            <a href="<?= $base ?>/circles/<?= $circle['id'] ?>/meetings/new" class="btn btn--secondary btn--sm">
                <i class="ti ti-calendar-plus" aria-hidden="true"></i> Meeting anlegen
            </a>
        <?php endif; ?>
        <?php if ($perm->isAdmin()): ?>
            <a href="<?= $base ?>/circles/<?= $circle['id'] ?>/edit" class="btn btn--secondary btn--sm">
                <i class="ti ti-edit" aria-hidden="true"></i> Bearbeiten
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- Metadaten-Banner -->
<?php if ($circle['driver'] || $circle['domain']): ?>
<div class="card">
    <div class="card__body" style="display:grid;grid-template-columns:1fr 1fr;gap:var(--sp-6)">
        <?php if ($circle['driver']): ?>
            <div>
                <div class="text-xs text-muted" style="text-transform:uppercase;letter-spacing:.04em;margin-bottom:var(--sp-2)">Treiber</div>
                <p class="text-sm" style="white-space:pre-line"><?= htmlspecialchars($circle['driver']) ?></p>
            </div>
        <?php endif; ?>
        <?php if ($circle['domain']): ?>
            <div>
                <div class="text-xs text-muted" style="text-transform:uppercase;letter-spacing:.04em;margin-bottom:var(--sp-2)">Domäne</div>
                <p class="text-sm" style="white-space:pre-line"><?= htmlspecialchars($circle['domain']) ?></p>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- Unterkreise -->
<?php if ($children): ?>
<div class="card">
    <div class="card__header">
        <span class="card__title"><i class="ti ti-circle" aria-hidden="true"></i> Unterkreise (<?= count($children) ?>)</span>
        <?php if ($perm->isAdmin()): ?>
            <a href="<?= $base ?>/circles/new?parent_id=<?= $circle['id'] ?>" class="btn btn--ghost btn--sm">
                <i class="ti ti-plus" aria-hidden="true"></i> Unterkreis anlegen
            </a>
        <?php endif; ?>
    </div>
    <div class="card__body--flush">
        <div class="circle-tree" style="padding:var(--sp-3) var(--sp-4)">
            <?php foreach ($children as $ch): ?>
                <div class="circle-node">
                    <div class="circle-node__icon"><i class="ti ti-circle" aria-hidden="true"></i></div>
                    <div class="circle-node__info">
                        <a href="<?= $base ?>/circles/<?= $ch['id'] ?>" class="circle-node__name">
                            <?= htmlspecialchars($ch['name']) ?>
                        </a>
                        <?php if ($ch['purpose']): ?>
                            <div class="circle-node__meta"><?= htmlspecialchars(mb_substr($ch['purpose'], 0, 80)) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($perm->isReadonly()): ?>

    <!-- Lesend: nur die Mitgliederliste, keine Tabs -->
    <div class="card">
        <div class="card__header">
            <span class="card__title"><i class="ti ti-users" aria-hidden="true"></i> Mitglieder</span>
        </div>
        <div class="card__body--flush">
            <?php if (empty($members)): ?>
                <div class="empty-state"><i class="ti ti-users-off" aria-hidden="true"></i><span>Noch keine Mitglieder zugewiesen</span></div>
            <?php else: ?>
                <table class="table">
                    <thead><tr><th>Person</th><th>Rolle</th><th>Rollentyp</th></tr></thead>
                    <tbody>
                        <?php foreach ($members as $m): ?>
                            <tr>
                                <td>
                                    <a href="<?= $base ?>/members/<?= $m['id'] ?>" class="fw-600">
                                        <?= htmlspecialchars($m['name']) ?>
                                    </a>
                                </td>
                                <td class="text-sm">
                                    <?= $m['role_name'] ? htmlspecialchars($m['role_name']) : '<span class="text-muted">— ohne Rolle —</span>' ?>
                                </td>
                                <td>
                                    <?php if ($m['role_type']): ?>
                                        <span class="badge badge--<?= htmlspecialchars($m['role_type']) ?>"><?= htmlspecialchars($roleTypeLabels[$m['role_type']] ?? $m['role_type']) ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

<?php else: ?>

<!-- Tabs -->
<div class="tabs" role="tablist">
    <button class="tab-btn tab-btn--active" role="tab" aria-selected="true" onclick="switchTab(this,'tab-roles')">
        <i class="ti ti-user-circle" aria-hidden="true"></i> Rollen (<?= count($roles) ?>)
    </button>
    <button class="tab-btn" role="tab" aria-selected="false" onclick="switchTab(this,'tab-agreements')">
        <i class="ti ti-file-text" aria-hidden="true"></i> Vereinbarungen (<?= count($agreements) ?>)
    </button>
    <button class="tab-btn" role="tab" aria-selected="false" onclick="switchTab(this,'tab-meetings')">
        <i class="ti ti-notes" aria-hidden="true"></i> Meetings (<?= count($meetings) ?>)
    </button>
    <button class="tab-btn" role="tab" aria-selected="false" onclick="switchTab(this,'tab-tensions')">
        <i class="ti ti-bolt" aria-hidden="true"></i> Spannungen (<?= count($tensions) ?>)
    </button>
    <button class="tab-btn" role="tab" aria-selected="false" onclick="switchTab(this,'tab-delegations')">
        <i class="ti ti-arrow-right-circle" aria-hidden="true"></i> Delegationen
    </button>
    <button class="tab-btn" role="tab" aria-selected="false" onclick="switchTab(this,'tab-members')">
        <i class="ti ti-users" aria-hidden="true"></i> Mitglieder (<?= count($members) ?>)
    </button>
</div>

<!-- TAB: Rollen -->
<div id="tab-roles" class="tab-panel tab-panel--active" role="tabpanel">
    <div class="card">
        <div class="card__header">
            <span class="card__title"><i class="ti ti-user-circle" aria-hidden="true"></i> Rollen</span>
            <?php if ($perm->canManageRolesIn($circle['id'])): ?>
                <a href="<?= $base ?>/circles/<?= $circle['id'] ?>/roles/new" class="btn btn--primary btn--sm">
                    <i class="ti ti-plus" aria-hidden="true"></i> Neue Rolle
                </a>
            <?php endif; ?>
        </div>
        <div class="card__body--flush">
            <?php if (empty($roles)): ?>
                <div class="empty-state"><i class="ti ti-user-off" aria-hidden="true"></i><span>Keine Rollen definiert</span></div>
            <?php else: ?>
                <table class="table">
                    <thead><tr><th>Rolle</th><th>Typ</th><th>Besetzt von</th><th></th></tr></thead>
                    <tbody>
                        <?php foreach ($roles as $r): ?>
                            <tr>
                                <td>
                                    <a href="<?= $base ?>/roles/<?= $r['id'] ?>" class="fw-600">
                                        <?= htmlspecialchars($r['name']) ?>
                                    </a>
                                </td>
                                <td><span class="badge badge--<?= htmlspecialchars($r['role_type']) ?>"><?= htmlspecialchars($roleTypeLabels[$r['role_type']] ?? $r['role_type']) ?></span></td>
                                <td class="text-sm">
                                    <?= $r['current_holder']
                                        ? htmlspecialchars($r['current_holder'])
                                        : '<span style="color:var(--c-error)">Unbesetzt</span>' ?>
                                </td>
                                <td>
                                    <a href="<?= $base ?>/roles/<?= $r['id'] ?>" class="btn btn--ghost btn--sm">
                                        <i class="ti ti-eye" aria-hidden="true"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php if (!empty($roles)): ?>
            <div class="card__footer">
                <a href="<?= $base ?>/circles/<?= $circle['id'] ?>/roles" class="btn btn--ghost btn--sm">
                    Alle Rollen <i class="ti ti-arrow-right" aria-hidden="true"></i>
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- TAB: Vereinbarungen -->
<div id="tab-agreements" class="tab-panel" role="tabpanel">
    <div class="card">
        <div class="card__header">
            <span class="card__title"><i class="ti ti-file-text" aria-hidden="true"></i> Aktive Vereinbarungen</span>
            <?php if ($perm->canCreateAgreementIn($circle['id'])): ?>
                <a href="<?= $base ?>/agreements/new?circle_id=<?= $circle['id'] ?>" class="btn btn--primary btn--sm">
                    <i class="ti ti-plus" aria-hidden="true"></i> Neue Vereinbarung
                </a>
            <?php endif; ?>
        </div>
        <div class="card__body--flush">
            <?php if (empty($agreements)): ?>
                <div class="empty-state"><i class="ti ti-file-off" aria-hidden="true"></i><span>Keine aktiven Vereinbarungen</span></div>
            <?php else: ?>
                <table class="table">
                    <thead><tr><th>Titel</th><th>Status</th><th>Review</th></tr></thead>
                    <tbody>
                        <?php foreach ($agreements as $a): ?>
                            <tr>
                                <td><a href="<?= $base ?>/agreements/<?= $a['id'] ?>" class="fw-600"><?= htmlspecialchars($a['title']) ?></a></td>
                                <td><span class="badge badge--<?= htmlspecialchars($a['status']) ?>"><?= htmlspecialchars($a['status']) ?></span></td>
                                <td class="text-sm"><?= $a['review_date'] ? date('d.m.Y', strtotime($a['review_date'])) : '—' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <div class="card__footer">
            <a href="<?= $base ?>/circles/<?= $circle['id'] ?>/agreements" class="btn btn--ghost btn--sm">
                Alle Vereinbarungen <i class="ti ti-arrow-right" aria-hidden="true"></i>
            </a>
        </div>
    </div>
</div>

<!-- TAB: Meetings -->
<div id="tab-meetings" class="tab-panel" role="tabpanel">
    <div class="card">
        <div class="card__header">
            <span class="card__title"><i class="ti ti-notes" aria-hidden="true"></i> Letzte Meetings</span>
            <?php if ($perm->canWriteMeetingsIn($circle['id'])): ?>
                <a href="<?= $base ?>/meetings/new?circle_id=<?= $circle['id'] ?>" class="btn btn--primary btn--sm">
                    <i class="ti ti-plus" aria-hidden="true"></i> Meeting anlegen
                </a>
            <?php endif; ?>
        </div>
        <div class="card__body--flush">
            <?php if (empty($meetings)): ?>
                <div class="empty-state"><i class="ti ti-calendar-off" aria-hidden="true"></i><span>Noch keine Meetings</span></div>
            <?php else: ?>
                <table class="table">
                    <thead><tr><th>Datum</th><th>Typ</th><th>Moderator·in</th></tr></thead>
                    <tbody>
                        <?php foreach ($meetings as $m): ?>
                            <tr>
                                <td><a href="<?= $base ?>/meetings/<?= $m['id'] ?>"><?= date('d.m.Y H:i', strtotime($m['held_at'])) ?></a></td>
                                <td><span class="badge badge--open"><?= htmlspecialchars($m['meeting_type']) ?></span></td>
                                <td class="text-sm"><?= htmlspecialchars($m['facilitator_name'] ?? '—') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <div class="card__footer">
            <a href="<?= $base ?>/circles/<?= $circle['id'] ?>/meetings" class="btn btn--ghost btn--sm">
                Alle Meetings <i class="ti ti-arrow-right" aria-hidden="true"></i>
            </a>
        </div>
    </div>
</div>

<!-- TAB: Spannungen -->
<div id="tab-tensions" class="tab-panel" role="tabpanel">
    <div class="card">
        <div class="card__header">
            <span class="card__title"><i class="ti ti-bolt" aria-hidden="true"></i> Offene Spannungen</span>
            <?php if ($perm->canRaiseTensionIn($circle['id'])): ?>
                <a href="<?= $base ?>/tensions/new?circle_id=<?= $circle['id'] ?>" class="btn btn--primary btn--sm">
                    <i class="ti ti-plus" aria-hidden="true"></i> Spannung einreichen
                </a>
            <?php endif; ?>
        </div>
        <div class="card__body--flush">
            <?php if (empty($tensions)): ?>
                <div class="empty-state"><i class="ti ti-mood-happy" aria-hidden="true"></i><span>Keine offenen Spannungen</span></div>
            <?php else: ?>
                <table class="table">
                    <thead><tr><th>Spannung</th><th>Von</th><th>Datum</th></tr></thead>
                    <tbody>
                        <?php foreach ($tensions as $t): ?>
                            <tr>
                                <td><a href="<?= $base ?>/tensions/<?= $t['id'] ?>" class="fw-600">
                                    <?= htmlspecialchars($t['title'] ?: mb_substr($t['description'] ?? '', 0, 60)) ?>
                                </a></td>
                                <td class="text-sm"><?= htmlspecialchars($t['raised_by_name'] ?? '—') ?></td>
                                <td class="text-sm text-muted"><?= date('d.m.Y', strtotime($t['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <div class="card__footer">
            <a href="<?= $base ?>/circles/<?= $circle['id'] ?>/tensions" class="btn btn--ghost btn--sm">
                Alle Spannungen <i class="ti ti-arrow-right" aria-hidden="true"></i>
            </a>
        </div>
    </div>
</div>

<!-- TAB: Delegationen -->
<div id="tab-delegations" class="tab-panel" role="tabpanel">
    <div class="card">
        <div class="card__header">
            <span class="card__title"><i class="ti ti-arrow-right-circle" aria-hidden="true"></i> Delegationen</span>
            <?php if ($perm->isAdmin()): ?>
                <a href="<?= $base ?>/delegations/new?from_circle=<?= $circle['id'] ?>"
                   class="btn btn--primary btn--sm">
                    <i class="ti ti-plus" aria-hidden="true"></i> Neue Delegation
                </a>
            <?php endif; ?>
        </div>
        <?php
        // Delegationen dieses Kreises live nachladen
        $circleDelegations = (new \Logbuch\Model\DelegationModel())->findByCircle($circle['id']);
        ?>
        <div class="card__body--flush">
            <?php if (empty($circleDelegations)): ?>
                <div class="empty-state">
                    <i class="ti ti-arrow-right-circle" aria-hidden="true"></i>
                    <span>Keine Delegationen für diesen Kreis</span>
                </div>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr><th>Richtung</th><th>Kreis</th><th>Rep-Link</th><th>Del-Link</th><th>Status</th><th></th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($circleDelegations as $d):
                            $isFrom = $d['from_circle'] == $circle['id'];
                        ?>
                            <tr>
                                <td>
                                    <?php if ($isFrom): ?>
                                        <span class="badge badge--review" title="Dieser Kreis delegiert">↓ delegiert an</span>
                                    <?php else: ?>
                                        <span class="badge badge--open" title="Dieser Kreis empfängt">↑ empfängt von</span>
                                    <?php endif; ?>
                                </td>
                                <td class="fw-600">
                                    <a href="<?= $base ?>/circles/<?= $isFrom ? $d['to_circle'] : $d['from_circle'] ?>">
                                        <?= htmlspecialchars($isFrom ? $d['to_circle_name'] : $d['from_circle_name']) ?>
                                    </a>
                                </td>
                                <td class="text-sm"><?= htmlspecialchars($d['rep_link_name'] ?? '—') ?></td>
                                <td class="text-sm"><?= htmlspecialchars($d['del_link_name'] ?? '—') ?></td>
                                <td><span class="badge badge--<?= $d['status'] === 'active' ? 'active' : 'expired' ?>"><?= $d['status'] === 'active' ? 'Aktiv' : 'Beendet' ?></span></td>
                                <td>
                                    <a href="<?= $base ?>/delegations/<?= $d['id'] ?>" class="btn btn--ghost btn--sm">
                                        <i class="ti ti-eye" aria-hidden="true"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <div class="card__footer">
            <a href="<?= $base ?>/delegations" class="btn btn--ghost btn--sm">
                Alle Delegationen <i class="ti ti-arrow-right" aria-hidden="true"></i>
            </a>
        </div>
    </div>
</div>

<!-- TAB: Mitglieder -->
<div id="tab-members" class="tab-panel" role="tabpanel">
    <div class="card">
        <div class="card__header">
            <span class="card__title"><i class="ti ti-users" aria-hidden="true"></i> Mitglieder</span>
        </div>
        <div class="card__body--flush">
            <?php if (empty($members)): ?>
                <div class="empty-state"><i class="ti ti-users-off" aria-hidden="true"></i><span>Noch keine Mitglieder zugewiesen</span></div>
            <?php else: ?>
                <table class="table">
                    <thead><tr><th>Person</th><th>Rolle</th><th>Rollentyp</th></tr></thead>
                    <tbody>
                        <?php foreach ($members as $m): ?>
                            <tr>
                                <td>
                                    <a href="<?= $base ?>/members/<?= $m['id'] ?>" class="fw-600">
                                        <?= htmlspecialchars($m['name']) ?>
                                    </a>
                                </td>
                                <td class="text-sm">
                                    <?= $m['role_name'] ? htmlspecialchars($m['role_name']) : '<span class="text-muted">— ohne Rolle —</span>' ?>
                                </td>
                                <td>
                                    <?php if ($m['role_type']): ?>
                                        <span class="badge badge--<?= htmlspecialchars($m['role_type']) ?>"><?= htmlspecialchars($roleTypeLabels[$m['role_type']] ?? $m['role_type']) ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function switchTab(btn, panelId) {
    document.querySelectorAll('.tab-btn').forEach(b => { b.classList.remove('tab-btn--active'); b.setAttribute('aria-selected','false'); });
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('tab-panel--active'));
    btn.classList.add('tab-btn--active');
    btn.setAttribute('aria-selected','true');
    document.getElementById(panelId).classList.add('tab-panel--active');
}
</script>

<?php endif; // !$perm->isReadonly() ?>
