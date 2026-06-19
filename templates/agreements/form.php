<?php
$base   = rtrim($config['app']['base_url'], '/');
$isEdit = !empty($agreement['id']);
$action = $isEdit ? $base . '/agreements/' . $agreement['id'] : $base . '/agreements';

function agErr(array $errors, string $field): string {
    return isset($errors[$field])
        ? '<span class="form-error">' . htmlspecialchars($errors[$field]) . '</span>' : '';
}
function agCls(array $errors, string $field): string {
    return isset($errors[$field]) ? ' form-input--error' : '';
}
?>

<nav class="breadcrumb">
    <a href="<?= $base ?>/circles">Kreise</a>
    <span class="breadcrumb__sep">/</span>
    <?php if (!empty($agreement['circle_id'])): ?>
        <a href="<?= $base ?>/circles/<?= $agreement['circle_id'] ?>/agreements">Vereinbarungen</a>
        <span class="breadcrumb__sep">/</span>
    <?php endif; ?>
    <span><?= $isEdit ? 'Bearbeiten' : 'Neue Vereinbarung' ?></span>
</nav>

<div class="page-header">
    <h1 class="page-header__title"><?= $isEdit ? 'Vereinbarung bearbeiten' : 'Neue Vereinbarung' ?></h1>
</div>

<div class="card" style="max-width:800px">
    <div class="card__body">
        <form method="post" action="<?= $action ?>" class="form">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">

            <div class="form-row">
                <div class="form-field">
                    <label class="form-label form-label--required" for="circle_id">Kreis</label>
                    <select id="circle_id" name="circle_id" class="form-select<?= agCls($errors, 'circle_id') ?>"
                            <?= $isEdit ? 'disabled' : '' ?>>
                        <option value="">— bitte wählen —</option>
                        <?php foreach ($circles as $c): ?>
                            <option value="<?= $c['id'] ?>"
                                <?= ($agreement['circle_id'] ?? 0) == $c['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($isEdit): ?>
                        <input type="hidden" name="circle_id" value="<?= $agreement['circle_id'] ?>">
                    <?php endif; ?>
                    <?= agErr($errors, 'circle_id') ?>
                </div>

                <div class="form-field">
                    <label class="form-label" for="meeting_id">Zugehöriges Meeting</label>
                    <select id="meeting_id" name="meeting_id" class="form-select">
                        <option value="">— kein Meeting verknüpft —</option>
                        <?php foreach ($meetings as $m): ?>
                            <option value="<?= $m['id'] ?>"
                                <?= ($agreement['meeting_id'] ?? 0) == $m['id'] ? 'selected' : '' ?>>
                                <?= date('d.m.Y', strtotime($m['held_at'])) ?>
                                — <?= htmlspecialchars($m['meeting_type']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-field">
                <label class="form-label form-label--required" for="title">Titel der Vereinbarung</label>
                <input type="text" id="title" name="title"
                       class="form-input<?= agCls($errors, 'title') ?>"
                       value="<?= htmlspecialchars($agreement['title'] ?? '') ?>"
                       placeholder="Kurzer, prägnanter Titel"
                       required autofocus>
                <?= agErr($errors, 'title') ?>
            </div>

            <div class="form-field">
                <label class="form-label" for="driver">Treiber / Ausgangslage</label>
                <textarea id="driver" name="driver" class="form-textarea" rows="3"
                          placeholder="Welches Bedürfnis oder welche Situation hat diese Vereinbarung ausgelöst?"
                ><?= htmlspecialchars($agreement['driver'] ?? '') ?></textarea>
                <span class="form-hint">S3: Beschreibe Situation, Bedürfnis und Auswirkung.</span>
            </div>

            <div class="form-field">
                <label class="form-label" for="body">Inhalt der Vereinbarung</label>
                <textarea id="body" name="body" class="form-textarea" rows="6"
                          placeholder="Was genau wurde vereinbart? Welche Regeln, Einschränkungen oder Erwartungen gelten?"
                ><?= htmlspecialchars($agreement['body'] ?? '') ?></textarea>
            </div>

            <div class="form-row">
                <div class="form-field">
                    <label class="form-label form-label--required" for="agreed_at">Beschlossen am</label>
                    <input type="date" id="agreed_at" name="agreed_at"
                           class="form-input<?= agCls($errors, 'agreed_at') ?>"
                           value="<?= htmlspecialchars($agreement['agreed_at'] ?? date('Y-m-d')) ?>"
                           required>
                    <?= agErr($errors, 'agreed_at') ?>
                </div>

                <div class="form-field">
                    <label class="form-label" for="review_date">Review-Datum</label>
                    <input type="date" id="review_date" name="review_date"
                           class="form-input"
                           value="<?= htmlspecialchars($agreement['review_date'] ?? '') ?>">
                    <span class="form-hint">Wann soll diese Vereinbarung überprüft werden?</span>
                </div>
            </div>

            <div class="form-field">
                <label class="form-label" for="status">Status</label>
                <select id="status" name="status" class="form-select">
                    <?php foreach (['active' => 'Aktiv', 'draft' => 'Entwurf', 'review_due' => 'Review fällig', 'expired' => 'Abgelaufen'] as $val => $lbl): ?>
                        <option value="<?= $val ?>"
                            <?= ($agreement['status'] ?? 'active') === $val ? 'selected' : '' ?>>
                            <?= $lbl ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <?php if ($isEdit): ?>
                <div class="form-field" style="padding:var(--sp-3) var(--sp-4);background:var(--c-bg);
                            border-radius:var(--r-md);border:1px solid var(--c-border)">
                    <label class="form-label" for="change_note">
                        <i class="ti ti-history" aria-hidden="true" style="color:var(--c-accent)"></i>
                        Änderungsgrund <span class="text-muted" style="font-weight:400">(optional)</span>
                    </label>
                    <input type="text" id="change_note" name="change_note" class="form-input"
                           placeholder="z.B. Review nach 6 Monaten, Präzisierung nach Feedback …"
                           value="<?= htmlspecialchars($agreement['change_note'] ?? '') ?>">
                    <span class="form-hint">
                        Beim Speichern wird der aktuelle Stand automatisch als Version gesichert.
                        <a href="<?= $base ?>/agreements/<?= $agreement['id'] ?>/versions">Versionshistorie ansehen</a>
                    </span>
                </div>
            <?php endif; ?>

            <div class="form-actions">
                <button type="submit" class="btn btn--primary">
                    <i class="ti ti-device-floppy" aria-hidden="true"></i>
                    <?= $isEdit ? 'Änderungen speichern' : 'Vereinbarung anlegen' ?>
                </button>
                <a href="<?= $isEdit ? $base . '/agreements/' . $agreement['id'] : $base . '/circles/' . ($agreement['circle_id'] ?? '') . '/agreements' ?>"
                   class="btn btn--secondary">Abbrechen</a>
            </div>
        </form>
    </div>
</div>
