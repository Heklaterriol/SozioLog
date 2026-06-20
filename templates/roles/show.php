<?php
$base = rtrim($config['app']['base_url'], '/');
$acc  = json_decode($role['accountabilities'] ?? '[]', true) ?? [];

$roleTypeLabels = [
    'general'       => 'Allgemein',
    'facilitator'   => 'Moderator·in',
    'secretary'     => 'Sekretär·in',
    'rep_link'      => 'Rep-Link',
    'delegate_link' => 'Del-Link',
    'elected'       => 'Gewählt',
];
$typeLabel = $roleTypeLabels[$role['role_type']] ?? $role['role_type'];
?>

<nav class="breadcrumb">
    <a href="<?= $base ?>/circles">Kreise</a>
    <span class="breadcrumb__sep">/</span>
    <a href="<?= $base ?>/circles/<?= $role['circle_id'] ?>"><?= htmlspecialchars($role['circle_name']) ?></a>
    <span class="breadcrumb__sep">/</span>
    <a href="<?= $base ?>/circles/<?= $role['circle_id'] ?>/roles">Rollen</a>
    <span class="breadcrumb__sep">/</span>
    <span><?= htmlspecialchars($role['name']) ?></span>
</nav>

<div class="page-header">
    <div>
        <h1 class="page-header__title">
            <?= htmlspecialchars($role['name']) ?>
            <span class="badge badge--<?= htmlspecialchars($role['role_type']) ?>" style="font-size:.65em;vertical-align:middle">
                <?= htmlspecialchars($typeLabel) ?>
            </span>
        </h1>
        <p class="page-header__sub"><?= htmlspecialchars($role['circle_name']) ?></p>
    </div>
    <?php if ($perm->canManageRolesIn($role['circle_id'])): ?>
        <div class="page-header__actions">
            <a href="<?= $base ?>/roles/<?= $role['id'] ?>/edit" class="btn btn--secondary">
                <i class="ti ti-edit" aria-hidden="true"></i> Bearbeiten
            </a>
        </div>
    <?php endif; ?>
</div>

<!-- Rollenbeschreibung -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--sp-5)">

    <div class="card">
        <div class="card__header">
            <span class="card__title"><i class="ti ti-file-description" aria-hidden="true"></i> Rollenbeschreibung</span>
        </div>
        <div class="card__body">
            <?php if ($role['purpose']): ?>
                <div style="margin-bottom:var(--sp-4)">
                    <div class="text-xs text-muted" style="text-transform:uppercase;letter-spacing:.04em;margin-bottom:var(--sp-1)">Zweck</div>
                    <p class="text-sm"><?= nl2br(htmlspecialchars($role['purpose'])) ?></p>
                </div>
            <?php endif; ?>

            <?php if ($role['domain']): ?>
                <div style="margin-bottom:var(--sp-4)">
                    <div class="text-xs text-muted" style="text-transform:uppercase;letter-spacing:.04em;margin-bottom:var(--sp-1)">Domäne</div>
                    <p class="text-sm"><?= nl2br(htmlspecialchars($role['domain'])) ?></p>
                </div>
            <?php endif; ?>

            <?php if ($acc): ?>
                <div>
                    <div class="text-xs text-muted" style="text-transform:uppercase;letter-spacing:.04em;margin-bottom:var(--sp-2)">Accountabilities</div>
                    <ul style="display:flex;flex-direction:column;gap:var(--sp-2);list-style:none">
                        <?php foreach ($acc as $item): ?>
                            <li class="text-sm d-flex gap-2">
                                <i class="ti ti-check" style="color:var(--c-accent);flex-shrink:0;margin-top:2px" aria-hidden="true"></i>
                                <?= htmlspecialchars($item) ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if (!$role['purpose'] && !$role['domain'] && !$acc): ?>
                <p class="text-sm text-muted">Noch keine Beschreibung hinterlegt.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Aktuelle Besetzung + Zuweisen -->
    <div style="display:flex;flex-direction:column;gap:var(--sp-5)">

        <div class="card">
            <div class="card__header">
                <span class="card__title"><i class="ti ti-user-check" aria-hidden="true"></i> Aktuelle Besetzung</span>
            </div>
            <div class="card__body">
                <?php if ($current): ?>
                    <div class="d-flex align-center gap-3" style="margin-bottom:var(--sp-3)">
                        <div style="width:40px;height:40px;border-radius:50%;background:var(--c-accent-lt);
                                    color:var(--c-accent);display:flex;align-items:center;justify-content:center;
                                    font-size:1.1rem;font-weight:700;flex-shrink:0">
                            <?= mb_strtoupper(mb_substr($current['member_name'], 0, 1)) ?>
                        </div>
                        <div>
                            <div class="fw-600"><?= htmlspecialchars($current['member_name']) ?></div>
                            <div class="text-xs text-muted">
                                seit <?= date('d.m.Y', strtotime($current['start_date'])) ?>
                                <?php if ($current['elected_until']): ?>
                                    · bis <?= date('d.m.Y', strtotime($current['elected_until'])) ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="d-flex align-center gap-2" style="color:var(--c-error);margin-bottom:var(--sp-3)">
                        <i class="ti ti-user-x" aria-hidden="true"></i>
                        <span class="text-sm">Aktuell unbesetzt</span>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($perm->canManageRolesIn($role['circle_id'])): ?>
                <div class="card__footer" style="flex-direction:column;align-items:flex-start;gap:var(--sp-3)">
                    <div class="text-sm fw-600">Person zuweisen</div>
                    <form method="post" action="<?= $base ?>/roles/<?= $role['id'] ?>/assign"
                          style="width:100%;display:flex;flex-direction:column;gap:var(--sp-3)">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">

                        <div class="form-field">
                            <label class="form-label" for="member_id">Person</label>
                            <select id="member_id" name="member_id" class="form-select" required>
                                <option value="">— bitte wählen —</option>
                                <?php foreach ($members as $m): ?>
                                    <option value="<?= $m['id'] ?>"
                                        <?= ($current && $current['member_id'] == $m['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($m['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--sp-3)">
                            <div class="form-field">
                                <label class="form-label" for="start_date">Startdatum</label>
                                <input type="date" id="start_date" name="start_date"
                                       class="form-input" value="<?= date('Y-m-d') ?>">
                            </div>
                            <?php if ($role['is_elected'] || $role['role_type'] === 'elected'): ?>
                                <div class="form-field">
                                    <label class="form-label" for="elected_until">Gewählt bis</label>
                                    <input type="date" id="elected_until" name="elected_until" class="form-input">
                                </div>
                            <?php endif; ?>
                        </div>

                        <button type="submit" class="btn btn--primary btn--sm">
                            <i class="ti ti-user-plus" aria-hidden="true"></i> Zuweisen
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<!-- Verlauf -->
<div class="card">
    <div class="card__header">
        <span class="card__title"><i class="ti ti-history" aria-hidden="true"></i> Rolleninhaber-Verlauf</span>
    </div>
    <div class="card__body--flush">
        <?php if (empty($history)): ?>
            <div class="empty-state">
                <i class="ti ti-clock-off" aria-hidden="true"></i>
                <span>Noch keine Zuweisungen</span>
            </div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Person</th>
                            <th>Von</th>
                            <th>Bis</th>
                            <th>Gewählt bis</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($history as $h): ?>
                            <tr>
                                <td class="fw-600"><?= htmlspecialchars($h['member_name']) ?></td>
                                <td class="text-sm"><?= date('d.m.Y', strtotime($h['start_date'])) ?></td>
                                <td class="text-sm">
                                    <?= $h['end_date'] ? date('d.m.Y', strtotime($h['end_date'])) : '—' ?>
                                </td>
                                <td class="text-sm">
                                    <?= $h['elected_until'] ? date('d.m.Y', strtotime($h['elected_until'])) : '—' ?>
                                </td>
                                <td>
                                    <?php if ($h['end_date'] === null): ?>
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
