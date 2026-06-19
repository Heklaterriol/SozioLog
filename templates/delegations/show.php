<?php $base = rtrim($config['app']['base_url'], '/'); ?>

<nav class="breadcrumb">
    <a href="<?= $base ?>/delegations">Delegationen</a>
    <span class="breadcrumb__sep">/</span>
    <span><?= htmlspecialchars($delegation['from_circle_name']) ?> → <?= htmlspecialchars($delegation['to_circle_name']) ?></span>
</nav>

<div class="page-header">
    <div>
        <h1 class="page-header__title">
            <i class="ti ti-arrow-right-circle" style="color:var(--c-accent)" aria-hidden="true"></i>
            <?= htmlspecialchars($delegation['from_circle_name']) ?>
            <span style="color:var(--c-ink-2);font-weight:300;margin:0 .3em">→</span>
            <?= htmlspecialchars($delegation['to_circle_name']) ?>
        </h1>
        <p class="page-header__sub">
            <?= $delegation['status'] === 'active' ? 'Aktive Delegation' : 'Beendete Delegation' ?>
            <?php if ($delegation['started_at']): ?>
                · seit <?= date('d.m.Y', strtotime($delegation['started_at'])) ?>
            <?php endif; ?>
            <?php if ($delegation['ended_at']): ?>
                · bis <?= date('d.m.Y', strtotime($delegation['ended_at'])) ?>
            <?php endif; ?>
        </p>
    </div>
    <?php if (!empty($currentUser['is_admin'])): ?>
        <div class="page-header__actions">
            <a href="<?= $base ?>/delegations/<?= $delegation['id'] ?>/edit" class="btn btn--secondary">
                <i class="ti ti-edit" aria-hidden="true"></i> Bearbeiten
            </a>
        </div>
    <?php endif; ?>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--sp-5)">

    <!-- Beschreibung & Domäne -->
    <div style="display:flex;flex-direction:column;gap:var(--sp-5)">
        <div class="card">
            <div class="card__header">
                <span class="card__title"><i class="ti ti-file-description" aria-hidden="true"></i> Delegationsinhalt</span>
                <span class="badge badge--<?= $delegation['status'] === 'active' ? 'active' : 'expired' ?>">
                    <?= $delegation['status'] === 'active' ? 'Aktiv' : 'Beendet' ?>
                </span>
            </div>
            <div class="card__body" style="display:flex;flex-direction:column;gap:var(--sp-4)">
                <?php if ($delegation['description']): ?>
                    <div>
                        <div class="text-xs text-muted" style="text-transform:uppercase;letter-spacing:.04em;margin-bottom:var(--sp-1)">Beschreibung</div>
                        <p class="text-sm" style="white-space:pre-line"><?= htmlspecialchars($delegation['description']) ?></p>
                    </div>
                <?php endif; ?>

                <?php if ($delegation['notes']): ?>
                    <div>
                        <div class="text-xs text-muted" style="text-transform:uppercase;letter-spacing:.04em;margin-bottom:var(--sp-1)">Notizen</div>
                        <p class="text-sm" style="white-space:pre-line"><?= htmlspecialchars($delegation['notes']) ?></p>
                    </div>
                <?php endif; ?>

                <?php if (!$delegation['description'] && !$delegation['notes']): ?>
                    <p class="text-sm text-muted">Keine Beschreibung hinterlegt.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Kreise -->
        <div class="card">
            <div class="card__header">
                <span class="card__title"><i class="ti ti-circles-relation" aria-hidden="true"></i> Beteiligte Kreise</span>
            </div>
            <div class="card__body" style="display:flex;flex-direction:column;gap:var(--sp-4)">
                <div>
                    <div class="text-xs text-muted" style="text-transform:uppercase;letter-spacing:.04em;margin-bottom:var(--sp-1)">Delegierender Kreis (Anker)</div>
                    <a href="<?= $base ?>/circles/<?= $delegation['from_circle'] ?>" class="fw-600">
                        <?= htmlspecialchars($delegation['from_circle_name']) ?>
                    </a>
                </div>
                <div style="padding-left:var(--sp-4);border-left:2px solid var(--c-accent)">
                    <div class="text-xs text-muted" style="text-transform:uppercase;letter-spacing:.04em;margin-bottom:var(--sp-1)">Empfangender Kreis (Delegierter)</div>
                    <a href="<?= $base ?>/circles/<?= $delegation['to_circle'] ?>" class="fw-600">
                        <?= htmlspecialchars($delegation['to_circle_name']) ?>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Links & Aktionen -->
    <div style="display:flex;flex-direction:column;gap:var(--sp-5)">

        <!-- Rep-Link -->
        <div class="card">
            <div class="card__header">
                <span class="card__title">
                    <i class="ti ti-arrow-up-circle" aria-hidden="true" style="color:#F59E0B"></i>
                    Rep-Link
                </span>
                <span class="text-xs text-muted">Unterkreis → Überkreis</span>
            </div>
            <div class="card__body">
                <?php if ($delegation['rep_link_name']): ?>
                    <div class="fw-600 text-sm" style="margin-bottom:var(--sp-2)">
                        <i class="ti ti-user-circle" aria-hidden="true"></i>
                        <a href="<?= $base ?>/roles/<?= $delegation['rep_link_role_id'] ?>">
                            <?= htmlspecialchars($delegation['rep_link_name']) ?>
                        </a>
                    </div>
                    <?php if ($delegation['rep_link_holder']): ?>
                        <div class="d-flex align-center gap-2">
                            <div style="width:28px;height:28px;border-radius:50%;background:var(--c-warning-lt);
                                        color:var(--c-warning);display:flex;align-items:center;justify-content:center;
                                        font-size:.8rem;font-weight:700;flex-shrink:0">
                                <?= mb_strtoupper(mb_substr($delegation['rep_link_holder'], 0, 1)) ?>
                            </div>
                            <div>
                                <div class="text-sm fw-600"><?= htmlspecialchars($delegation['rep_link_holder']) ?></div>
                                <div class="text-xs text-muted">Aktuell besetzt</div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="text-sm" style="color:var(--c-error)">
                            <i class="ti ti-user-x" aria-hidden="true"></i> Rolle aktuell unbesetzt
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="text-sm text-muted">Kein Rep-Link definiert.</p>
                    <?php if (!empty($currentUser['is_admin'])): ?>
                        <a href="<?= $base ?>/delegations/<?= $delegation['id'] ?>/edit" class="btn btn--ghost btn--sm" style="margin-top:var(--sp-2)">
                            <i class="ti ti-plus" aria-hidden="true"></i> Rep-Link zuweisen
                        </a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Del-Link -->
        <div class="card">
            <div class="card__header">
                <span class="card__title">
                    <i class="ti ti-arrow-down-circle" aria-hidden="true" style="color:#8B5CF6"></i>
                    Del-Link
                </span>
                <span class="text-xs text-muted">Überkreis → Unterkreis</span>
            </div>
            <div class="card__body">
                <?php if ($delegation['del_link_name']): ?>
                    <div class="fw-600 text-sm" style="margin-bottom:var(--sp-2)">
                        <i class="ti ti-user-circle" aria-hidden="true"></i>
                        <a href="<?= $base ?>/roles/<?= $delegation['del_link_role_id'] ?>">
                            <?= htmlspecialchars($delegation['del_link_name']) ?>
                        </a>
                    </div>
                    <?php if ($delegation['del_link_holder']): ?>
                        <div class="d-flex align-center gap-2">
                            <div style="width:28px;height:28px;border-radius:50%;background:#EDE9FE;
                                        color:#6D28D9;display:flex;align-items:center;justify-content:center;
                                        font-size:.8rem;font-weight:700;flex-shrink:0">
                                <?= mb_strtoupper(mb_substr($delegation['del_link_holder'], 0, 1)) ?>
                            </div>
                            <div>
                                <div class="text-sm fw-600"><?= htmlspecialchars($delegation['del_link_holder']) ?></div>
                                <div class="text-xs text-muted">Aktuell besetzt</div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="text-sm" style="color:var(--c-error)">
                            <i class="ti ti-user-x" aria-hidden="true"></i> Rolle aktuell unbesetzt
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="text-sm text-muted">Kein Del-Link definiert.</p>
                    <?php if (!empty($currentUser['is_admin'])): ?>
                        <a href="<?= $base ?>/delegations/<?= $delegation['id'] ?>/edit" class="btn btn--ghost btn--sm" style="margin-top:var(--sp-2)">
                            <i class="ti ti-plus" aria-hidden="true"></i> Del-Link zuweisen
                        </a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Admin-Aktionen -->
        <?php if (!empty($currentUser['is_admin'])): ?>
            <div class="card">
                <div class="card__header">
                    <span class="card__title"><i class="ti ti-tool" aria-hidden="true"></i> Aktionen</span>
                </div>
                <div class="card__body" style="display:flex;flex-direction:column;gap:var(--sp-3)">

                    <?php if ($delegation['status'] === 'active'): ?>
                        <!-- Delegation beenden -->
                        <form method="post" action="<?= $base ?>/delegations/<?= $delegation['id'] ?>/end"
                              id="form-end">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                            <div class="form-field">
                                <label class="form-label" for="ended_at">Enddatum</label>
                                <input type="date" id="ended_at" name="ended_at"
                                       class="form-input" value="<?= date('Y-m-d') ?>">
                            </div>
                            <button type="submit" class="btn btn--danger btn--sm" style="margin-top:var(--sp-2)"
                                    onclick="return confirm('Delegation wirklich beenden?')">
                                <i class="ti ti-x" aria-hidden="true"></i> Delegation beenden
                            </button>
                        </form>
                        <hr style="border:none;border-top:1px solid var(--c-border)">
                    <?php endif; ?>

                    <!-- Löschen -->
                    <form method="post" action="<?= $base ?>/delegations/<?= $delegation['id'] ?>/delete">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                        <button type="submit" class="btn btn--ghost btn--sm" style="color:var(--c-error)"
                                onclick="return confirm('Delegation unwiderruflich löschen?')">
                            <i class="ti ti-trash" aria-hidden="true"></i> Löschen
                        </button>
                    </form>
                </div>
            </div>
        <?php endif; ?>

    </div>
</div>
