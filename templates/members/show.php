<?php $base = rtrim($config['app']['base_url'], '/'); ?>

<nav class="breadcrumb">
    <a href="<?= $base ?>/members">Mitglieder</a>
    <span class="breadcrumb__sep">/</span>
    <span><?= htmlspecialchars($member['name']) ?></span>
</nav>

<div class="page-header">
    <div class="d-flex align-center gap-3">
        <div style="width:52px;height:52px;border-radius:50%;background:var(--c-accent-lt);
                    color:var(--c-accent);display:flex;align-items:center;justify-content:center;
                    font-size:1.4rem;font-weight:700;flex-shrink:0">
            <?= mb_strtoupper(mb_substr($member['name'], 0, 1)) ?>
        </div>
        <div>
            <h1 class="page-header__title"><?= htmlspecialchars($member['name']) ?></h1>
            <p class="page-header__sub">
                <?= htmlspecialchars($member['email']) ?>
                <?php if ($member['is_admin']): ?>
                    · <span class="badge badge--facilitator">Admin</span>
                <?php endif; ?>
            </p>
        </div>
    </div>
    <?php if (!empty($currentUser['is_admin'])): ?>
        <div class="page-header__actions">
            <a href="<?= $base ?>/members/<?= $member['id'] ?>/edit" class="btn btn--secondary">
                <i class="ti ti-edit" aria-hidden="true"></i> Bearbeiten
            </a>
        </div>
    <?php endif; ?>
</div>

<!-- Rollen -->
<div class="card">
    <div class="card__header">
        <span class="card__title"><i class="ti ti-user-circle" aria-hidden="true"></i> Rollen</span>
    </div>
    <div class="card__body--flush">
        <?php if (empty($roles)): ?>
            <div class="empty-state" style="padding:var(--sp-8)">
                <i class="ti ti-user-off" aria-hidden="true"></i>
                <span>Noch keine Rollenzuweisungen</span>
            </div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Rolle</th>
                            <th>Kreis</th>
                            <th>Typ</th>
                            <th>Von</th>
                            <th>Bis</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($roles as $r): ?>
                            <tr>
                                <td class="fw-600"><?= htmlspecialchars($r['role_name']) ?></td>
                                <td class="text-sm"><?= htmlspecialchars($r['circle_name']) ?></td>
                                <td>
                                    <span class="badge badge--<?= htmlspecialchars($r['role_type']) ?>">
                                        <?= htmlspecialchars($r['role_type']) ?>
                                    </span>
                                </td>
                                <td class="text-sm"><?= date('d.m.Y', strtotime($r['start_date'])) ?></td>
                                <td class="text-sm">
                                    <?= $r['end_date'] ? date('d.m.Y', strtotime($r['end_date'])) : '—' ?>
                                </td>
                                <td>
                                    <?php if ($r['end_date'] === null): ?>
                                        <span class="badge badge--active">Aktiv</span>
                                    <?php else: ?>
                                        <span class="badge badge--expired">Beendet</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
