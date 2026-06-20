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
$levelLabels = ['admin' => 'Admin', 'member' => 'Mitglied', 'readonly' => 'Lesend'];
$levelBadge  = ['admin' => 'badge--facilitator', 'member' => 'badge--draft', 'readonly' => 'badge--expired'];
$currentLevel = $member['permission_level'] ?? ($member['is_admin'] ? 'admin' : 'member');

// Zur schnellen Prüfung, welche Kreise bereits zugewiesen sind
$memberCircleIds = array_map(fn($cm) => (int) $cm['circle_id'], $circleMemberships);
?>

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
                · <span class="badge <?= $levelBadge[$currentLevel] ?? 'badge--draft' ?>">
                    <?= $levelLabels[$currentLevel] ?? $currentLevel ?>
                  </span>
            </p>
        </div>
    </div>
    <?php if ($canManage): ?>
        <div class="page-header__actions">
            <a href="<?= $base ?>/members/<?= $member['id'] ?>/edit" class="btn btn--secondary">
                <i class="ti ti-edit" aria-hidden="true"></i> Bearbeiten
            </a>
        </div>
    <?php endif; ?>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--sp-5);align-items:start">

    <!-- Kreiszugehörigkeit -->
    <div class="card">
        <div class="card__header">
            <span class="card__title"><i class="ti ti-circle" aria-hidden="true"></i> Kreiszugehörigkeit</span>
        </div>

        <?php if ($canManage): ?>
            <div class="card__body">
                <?php if (empty($allCircles)): ?>
                    <p class="text-sm text-muted">Es sind noch keine Kreise angelegt.</p>
                <?php else: ?>
                    <form method="post" action="<?= $base ?>/members/<?= $member['id'] ?>/circles">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">

                        <div style="display:flex;flex-direction:column;gap:var(--sp-2);max-height:280px;overflow-y:auto;
                                    padding:var(--sp-3);border:1px solid var(--c-border);border-radius:var(--r-md);
                                    background:var(--c-surface);margin-bottom:var(--sp-3)">
                            <?php foreach ($allCircles as $c): ?>
                                <label class="form-check">
                                    <input type="checkbox" name="circle_ids[]" value="<?= $c['id'] ?>"
                                           <?= in_array((int) $c['id'], $memberCircleIds, true) ? 'checked' : '' ?>>
                                    <?= htmlspecialchars($c['name']) ?>
                                </label>
                            <?php endforeach; ?>
                        </div>

                        <button type="submit" class="btn btn--primary btn--sm">
                            <i class="ti ti-device-floppy" aria-hidden="true"></i> Kreiszugehörigkeit speichern
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="card__body--flush">
                <?php if (empty($circleMemberships)): ?>
                    <div class="empty-state" style="padding:var(--sp-6)">
                        <i class="ti ti-circle-off" aria-hidden="true"></i>
                        <span>Keinem Kreis zugeordnet</span>
                    </div>
                <?php else: ?>
                    <ul style="list-style:none;padding:var(--sp-3) var(--sp-4);display:flex;flex-direction:column;gap:var(--sp-2)">
                        <?php foreach ($circleMemberships as $cm): ?>
                            <li>
                                <a href="<?= $base ?>/circles/<?= $cm['circle_id'] ?>" class="text-sm fw-600">
                                    <i class="ti ti-circle" style="color:var(--c-accent)" aria-hidden="true"></i>
                                    <?= htmlspecialchars($cm['circle_name']) ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Berechtigungsstufe -->
    <div class="card">
        <div class="card__header">
            <span class="card__title"><i class="ti ti-shield-lock" aria-hidden="true"></i> Berechtigung</span>
        </div>

        <?php if ($perm->canChangePermissionLevel()): ?>
            <div class="card__body">
                <p class="text-sm text-muted" style="margin-bottom:var(--sp-3)">
                    Wird über «Bearbeiten» geändert.
                </p>
                <div style="display:flex;flex-direction:column;gap:var(--sp-3)">
                    <div class="d-flex align-center gap-2">
                        <span class="badge <?= $levelBadge['admin'] ?>">Admin</span>
                        <span class="text-sm text-muted">kann Kreise, Rollen und Mitglieder verwalten</span>
                    </div>
                    <div class="d-flex align-center gap-2">
                        <span class="badge <?= $levelBadge['member'] ?>">Mitglied</span>
                        <span class="text-sm text-muted">sieht alles, verwaltet im eigenen Kreis</span>
                    </div>
                    <div class="d-flex align-center gap-2">
                        <span class="badge <?= $levelBadge['readonly'] ?>">Lesend</span>
                        <span class="text-sm text-muted">sieht Kreise und Mitglieder, keine Bearbeitung</span>
                    </div>
                </div>
                <a href="<?= $base ?>/members/<?= $member['id'] ?>/edit" class="btn btn--secondary btn--sm" style="margin-top:var(--sp-4)">
                    <i class="ti ti-edit" aria-hidden="true"></i> Berechtigung ändern
                </a>
            </div>
        <?php else: ?>
            <div class="card__body">
                <span class="badge <?= $levelBadge[$currentLevel] ?? 'badge--draft' ?>" style="font-size:.9em">
                    <?= $levelLabels[$currentLevel] ?? $currentLevel ?>
                </span>
                <p class="text-sm text-muted" style="margin-top:var(--sp-3)">
                    Nur Admins können die Berechtigungsstufe ändern.
                </p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Rollen -->
<div class="card" style="margin-top:var(--sp-5)">
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
                            <?php if ($canManage): ?><th></th><?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($roles as $r): ?>
                            <tr>
                                <td class="fw-600">
                                    <a href="<?= $base ?>/roles/<?= $r['role_id'] ?>"><?= htmlspecialchars($r['role_name']) ?></a>
                                </td>
                                <td class="text-sm"><?= htmlspecialchars($r['circle_name']) ?></td>
                                <td>
                                    <span class="badge badge--<?= htmlspecialchars($r['role_type']) ?>">
                                        <?= htmlspecialchars($roleTypeLabels[$r['role_type']] ?? $r['role_type']) ?>
                                    </span>
                                </td>
                                <td class="text-sm"><?= date('d.m.Y', strtotime($r['start_date'])) ?></td>
                                <td class="text-sm">
                                    <?= $r['elected_until'] ? date('d.m.Y', strtotime($r['elected_until'])) : '—' ?>
                                </td>
                                <td>
                                    <?php if ($r['end_date'] === null): ?>
                                        <span class="badge badge--active">Aktiv</span>
                                    <?php else: ?>
                                        <span class="badge badge--expired">Beendet</span>
                                    <?php endif; ?>
                                </td>
                                <?php if ($canManage): ?>
                                    <td>
                                        <?php if ($r['end_date'] === null): ?>
                                            <form method="post"
                                                  action="<?= $base ?>/members/<?= $member['id'] ?>/roles/<?= $r['assignment_id'] ?>/end"
                                                  onsubmit="return confirm('Diese Rolle wirklich entziehen?')">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                                                <button type="submit" class="btn btn--ghost btn--sm" title="Rolle entziehen">
                                                    <i class="ti ti-x" aria-hidden="true"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <?php if ($canManage && !empty($allRoles)): ?>
            <div class="card__footer" style="flex-direction:column;align-items:flex-start;gap:var(--sp-3)">
                <div class="text-sm fw-600">Rolle zuweisen</div>
                <form method="post" action="<?= $base ?>/members/<?= $member['id'] ?>/roles"
                      style="width:100%;display:flex;gap:var(--sp-3);flex-wrap:wrap;align-items:flex-end">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">

                    <div class="form-field" style="flex:1;min-width:220px;margin-bottom:0">
                        <label class="form-label" for="role_id">Rolle</label>
                        <select id="role_id" name="role_id" class="form-select" required>
                            <option value="">— bitte wählen —</option>
                            <?php foreach ($allRoles as $r): ?>
                                <option value="<?= $r['id'] ?>">
                                    <?= htmlspecialchars($r['circle_name']) ?> — <?= htmlspecialchars($r['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-field" style="margin-bottom:0">
                        <label class="form-label" for="elected_until">Bis (optional)</label>
                        <input type="date" id="elected_until" name="elected_until" class="form-input">
                    </div>

                    <button type="submit" class="btn btn--primary btn--sm">
                        <i class="ti ti-user-plus" aria-hidden="true"></i> Zuweisen
                    </button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</div>
