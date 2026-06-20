<?php
$base = rtrim($config['app']['base_url'], '/');
$levelLabels = ['admin' => 'Admin', 'member' => 'Mitglied', 'readonly' => 'Lesend'];
$levelBadge  = ['admin' => 'badge--facilitator', 'member' => 'badge--draft', 'readonly' => 'badge--expired'];
?>

<div class="page-header">
    <div>
        <h1 class="page-header__title">Mitglieder</h1>
        <p class="page-header__sub"><?= count($members) ?> Person<?= count($members) !== 1 ? 'en' : '' ?> in der Organisation</p>
    </div>
    <?php if ($perm->isAdmin() || $perm->isMember()): ?>
        <div class="page-header__actions">
            <a href="<?= $base ?>/members/new" class="btn btn--primary">
                <i class="ti ti-user-plus" aria-hidden="true"></i> Person anlegen
            </a>
        </div>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card__body--flush">
        <?php if (empty($members)): ?>
            <div class="empty-state">
                <i class="ti ti-users-off" aria-hidden="true"></i>
                <span class="empty-state__title">Noch keine Mitglieder</span>
            </div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>E-Mail</th>
                            <th>Berechtigung</th>
                            <th>Mitglied seit</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($members as $m):
                            $level     = $m['permission_level'] ?? ($m['is_admin'] ? 'admin' : 'member');
                            $canManage = $perm->canManageMemberRecord($circleIdsByUser[$m['id']] ?? []);
                        ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-center gap-3">
                                        <div style="width:32px;height:32px;border-radius:50%;background:var(--c-accent-lt);
                                                    color:var(--c-accent);display:flex;align-items:center;
                                                    justify-content:center;font-size:.85rem;font-weight:700;flex-shrink:0">
                                            <?= mb_strtoupper(mb_substr($m['name'], 0, 1)) ?>
                                        </div>
                                        <a href="<?= $base ?>/members/<?= $m['id'] ?>" class="fw-600">
                                            <?= htmlspecialchars($m['name']) ?>
                                        </a>
                                    </div>
                                </td>
                                <td class="text-sm"><?= htmlspecialchars($m['email']) ?></td>
                                <td>
                                    <span class="badge <?= $levelBadge[$level] ?? 'badge--draft' ?>">
                                        <?= $levelLabels[$level] ?? $level ?>
                                    </span>
                                </td>
                                <td class="text-sm text-muted">
                                    <?= date('d.m.Y', strtotime($m['created_at'])) ?>
                                </td>
                                <td>
                                    <div class="table__actions">
                                        <a href="<?= $base ?>/members/<?= $m['id'] ?>" class="btn btn--ghost btn--sm" title="Profil">
                                            <i class="ti ti-eye" aria-hidden="true"></i>
                                        </a>
                                        <?php if ($canManage): ?>
                                            <a href="<?= $base ?>/members/<?= $m['id'] ?>/edit" class="btn btn--ghost btn--sm" title="Bearbeiten">
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
