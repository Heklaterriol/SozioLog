<?php
$base = rtrim($config['app']['base_url'], '/');
$statusLabels = [
    'open'        => 'Offen',
    'in_progress' => 'In Bearbeitung',
    'resolved'    => 'Gelöst',
    'dropped'     => 'Fallen gelassen',
];
?>

<nav class="breadcrumb">
    <a href="<?= $base ?>/circles">Kreise</a>
    <span class="breadcrumb__sep">/</span>
    <a href="<?= $base ?>/circles/<?= $tension['circle_id'] ?>"><?= htmlspecialchars($tension['circle_name']) ?></a>
    <span class="breadcrumb__sep">/</span>
    <a href="<?= $base ?>/circles/<?= $tension['circle_id'] ?>/tensions">Spannungen</a>
    <span class="breadcrumb__sep">/</span>
    <span><?= htmlspecialchars($tension['title'] ?: '#' . $tension['id']) ?></span>
</nav>

<div class="page-header">
    <div>
        <h1 class="page-header__title">
            <?= htmlspecialchars($tension['title'] ?: 'Spannung #' . $tension['id']) ?>
        </h1>
        <p class="page-header__sub">
            <?= htmlspecialchars($tension['circle_name']) ?>
            · Eingereicht von <?= htmlspecialchars($tension['raised_by_name'] ?? 'Unbekannt') ?>
            · <?= date('d.m.Y', strtotime($tension['created_at'])) ?>
        </p>
    </div>
</div>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:var(--sp-5)">

    <!-- Beschreibung -->
    <div style="display:flex;flex-direction:column;gap:var(--sp-5)">
        <div class="card">
            <div class="card__header">
                <span class="card__title"><i class="ti ti-bolt" aria-hidden="true"></i> Beschreibung</span>
                <span class="badge badge--<?= htmlspecialchars($tension['status']) ?>">
                    <?= htmlspecialchars($statusLabels[$tension['status']] ?? $tension['status']) ?>
                </span>
            </div>
            <div class="card__body">
                <?php if ($tension['description']): ?>
                    <p class="text-sm" style="white-space:pre-line;line-height:1.75">
                        <?= htmlspecialchars($tension['description']) ?>
                    </p>
                <?php else: ?>
                    <p class="text-sm text-muted">Keine Beschreibung hinterlegt.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Status aktualisieren -->
        <?php if ($perm->canEditTensionIn($tension['circle_id'])): ?>
        <div class="card">
            <div class="card__header">
                <span class="card__title"><i class="ti ti-edit" aria-hidden="true"></i> Status / Auflösung aktualisieren</span>
            </div>
            <div class="card__body">
                <form method="post" action="<?= $base ?>/tensions/<?= $tension['id'] ?>" class="form">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">

                    <div class="form-row">
                        <div class="form-field">
                            <label class="form-label" for="t_title">Titel</label>
                            <input type="text" id="t_title" name="title" class="form-input"
                                   value="<?= htmlspecialchars($tension['title'] ?? '') ?>">
                        </div>
                        <div class="form-field">
                            <label class="form-label" for="t_status">Status</label>
                            <select id="t_status" name="status" class="form-select">
                                <?php foreach ($statusLabels as $val => $lbl): ?>
                                    <option value="<?= $val ?>"
                                        <?= $tension['status'] === $val ? 'selected' : '' ?>>
                                        <?= $lbl ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-field">
                        <label class="form-label" for="t_desc">Beschreibung</label>
                        <textarea id="t_desc" name="description" class="form-textarea" rows="4"
                        ><?= htmlspecialchars($tension['description'] ?? '') ?></textarea>
                    </div>

                    <div class="form-field">
                        <label class="form-label" for="resolved_by">Gelöst durch Vereinbarung</label>
                        <select id="resolved_by" name="resolved_by" class="form-select">
                            <option value="">— noch keine Vereinbarung —</option>
                            <?php foreach ($agreements as $a): ?>
                                <option value="<?= $a['id'] ?>"
                                    <?= ($tension['resolved_by'] ?? 0) == $a['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($a['title']) ?>
                                    (<?= date('d.m.Y', strtotime($a['agreed_at'])) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <span class="form-hint">Wähle die Vereinbarung, die diese Spannung auflöst — setzt Status automatisch auf «Gelöst».</span>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn--primary">
                            <i class="ti ti-device-floppy" aria-hidden="true"></i> Speichern
                        </button>
                        <a href="<?= $base ?>/circles/<?= $tension['circle_id'] ?>/tensions"
                           class="btn btn--secondary">Zurück zur Liste</a>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Seitenleiste -->
    <div style="display:flex;flex-direction:column;gap:var(--sp-5)">
        <div class="card">
            <div class="card__body">
                <div style="display:flex;flex-direction:column;gap:var(--sp-4)">
                    <div>
                        <div class="text-xs text-muted" style="text-transform:uppercase;letter-spacing:.04em;margin-bottom:4px">Kreis</div>
                        <a href="<?= $base ?>/circles/<?= $tension['circle_id'] ?>" class="text-sm fw-600">
                            <?= htmlspecialchars($tension['circle_name']) ?>
                        </a>
                    </div>
                    <div>
                        <div class="text-xs text-muted" style="text-transform:uppercase;letter-spacing:.04em;margin-bottom:4px">Eingereicht von</div>
                        <div class="text-sm fw-600"><?= htmlspecialchars($tension['raised_by_name'] ?? '—') ?></div>
                    </div>
                    <div>
                        <div class="text-xs text-muted" style="text-transform:uppercase;letter-spacing:.04em;margin-bottom:4px">Erstellt</div>
                        <div class="text-sm"><?= date('d.m.Y', strtotime($tension['created_at'])) ?></div>
                    </div>
                    <?php if ($tension['resolved_by']): ?>
                        <div>
                            <div class="text-xs text-muted" style="text-transform:uppercase;letter-spacing:.04em;margin-bottom:4px">Gelöst durch</div>
                            <a href="<?= $base ?>/agreements/<?= $tension['resolved_by'] ?>" class="text-sm">
                                Vereinbarung #<?= $tension['resolved_by'] ?>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
