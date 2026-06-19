<?php
$base   = rtrim($config['app']['base_url'], '/');
$isEdit = !empty($meeting['id']);
$action = $isEdit ? $base . '/meetings/' . $meeting['id'] : $base . '/meetings';
$attendeeIds = is_array($meeting['attendees'] ?? null)
    ? $meeting['attendees']
    : (json_decode($meeting['attendees'] ?? '[]', true) ?? []);

function mErr(array $errors, string $field): string {
    return isset($errors[$field])
        ? '<span class="form-error">' . htmlspecialchars($errors[$field]) . '</span>' : '';
}
function mCls(array $errors, string $field): string {
    return isset($errors[$field]) ? ' form-input--error' : '';
}
?>

<nav class="breadcrumb">
    <a href="<?= $base ?>/circles">Kreise</a>
    <?php if (!empty($meeting['circle_id'])): ?>
        <span class="breadcrumb__sep">/</span>
        <a href="<?= $base ?>/circles/<?= $meeting['circle_id'] ?>/meetings">Meetings</a>
    <?php endif; ?>
    <span class="breadcrumb__sep">/</span>
    <span><?= $isEdit ? 'Bearbeiten' : 'Neues Meeting' ?></span>
</nav>

<div class="page-header">
    <h1 class="page-header__title"><?= $isEdit ? 'Meeting bearbeiten' : 'Neues Meeting' ?></h1>
</div>

<div class="card" style="max-width:800px">
    <div class="card__body">
        <form method="post" action="<?= $action ?>" class="form">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">

            <div class="form-row">
                <div class="form-field">
                    <label class="form-label form-label--required" for="m_circle_id">Kreis</label>
                    <select id="m_circle_id" name="circle_id"
                            class="form-select<?= mCls($errors, 'circle_id') ?>"
                            <?= $isEdit ? 'disabled' : '' ?> required>
                        <option value="">— bitte wählen —</option>
                        <?php foreach ($circles as $c): ?>
                            <option value="<?= $c['id'] ?>"
                                <?= ($meeting['circle_id'] ?? 0) == $c['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($isEdit): ?>
                        <input type="hidden" name="circle_id" value="<?= $meeting['circle_id'] ?>">
                    <?php endif; ?>
                    <?= mErr($errors, 'circle_id') ?>
                </div>

                <div class="form-field">
                    <label class="form-label form-label--required" for="meeting_type">Meeting-Typ</label>
                    <select id="meeting_type" name="meeting_type" class="form-select">
                        <?php foreach ([
                            'governance'    => 'Governance',
                            'operational'   => 'Operativ',
                            'election'      => 'Wahl',
                            'retrospective' => 'Retrospektive',
                            'other'         => 'Sonstiges',
                        ] as $val => $lbl): ?>
                            <option value="<?= $val ?>"
                                <?= ($meeting['meeting_type'] ?? 'governance') === $val ? 'selected' : '' ?>>
                                <?= $lbl ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-field">
                    <label class="form-label form-label--required" for="held_at">Datum & Uhrzeit</label>
                    <input type="datetime-local" id="held_at" name="held_at"
                           class="form-input<?= mCls($errors, 'held_at') ?>"
                           value="<?= htmlspecialchars(isset($meeting['held_at']) ? date('Y-m-d\TH:i', strtotime($meeting['held_at'])) : date('Y-m-d\TH:i')) ?>"
                           required>
                    <?= mErr($errors, 'held_at') ?>
                </div>

                <div class="form-field">
                    <label class="form-label" for="location">Ort</label>
                    <input type="text" id="location" name="location"
                           class="form-input"
                           value="<?= htmlspecialchars($meeting['location'] ?? '') ?>"
                           placeholder="Raum, Videokonferenz-Link …">
                </div>
            </div>

            <div class="form-row">
                <div class="form-field">
                    <label class="form-label" for="facilitator_id">Moderator·in</label>
                    <select id="facilitator_id" name="facilitator_id" class="form-select">
                        <option value="">— keine Angabe —</option>
                        <?php foreach ($members as $m): ?>
                            <option value="<?= $m['id'] ?>"
                                <?= ($meeting['facilitator_id'] ?? 0) == $m['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($m['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-field">
                    <label class="form-label" for="secretary_id">Protokollant·in</label>
                    <select id="secretary_id" name="secretary_id" class="form-select">
                        <option value="">— keine Angabe —</option>
                        <?php foreach ($members as $m): ?>
                            <option value="<?= $m['id'] ?>"
                                <?= ($meeting['secretary_id'] ?? 0) == $m['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($m['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Anwesende (Mehrfachauswahl) -->
            <div class="form-field">
                <label class="form-label">Anwesende Personen</label>
                <div style="display:flex;flex-wrap:wrap;gap:var(--sp-2);padding:var(--sp-3);
                            border:1px solid var(--c-border);border-radius:var(--r-md);background:var(--c-surface)">
                    <?php foreach ($members as $m): ?>
                        <label class="form-check">
                            <input type="checkbox" name="attendees[]" value="<?= $m['id'] ?>"
                                   <?= in_array($m['id'], $attendeeIds) ? 'checked' : '' ?>>
                            <?= htmlspecialchars($m['name']) ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="form-field">
                <label class="form-label" for="notes">Check-in / Notizen</label>
                <textarea id="notes" name="notes" class="form-textarea" rows="4"
                          placeholder="Check-in, allgemeine Notizen …"
                ><?= htmlspecialchars($meeting['notes'] ?? '') ?></textarea>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn--primary">
                    <i class="ti ti-device-floppy" aria-hidden="true"></i>
                    <?= $isEdit ? 'Änderungen speichern' : 'Meeting anlegen' ?>
                </button>
                <a href="<?= $isEdit ? $base . '/meetings/' . $meeting['id'] : $base . '/circles/' . ($meeting['circle_id'] ?? '') . '/meetings' ?>"
                   class="btn btn--secondary">Abbrechen</a>
            </div>
        </form>
    </div>
</div>
