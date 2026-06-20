<?php
$base    = rtrim($config['app']['base_url'], '/');
$isEdit  = !empty($circle['id']);
$action  = $isEdit ? $base . '/circles/' . $circle['id'] : $base . '/circles';

function fErr(array $errors, string $field): string {
    return isset($errors[$field])
        ? '<span class="form-error">' . htmlspecialchars($errors[$field]) . '</span>'
        : '';
}
function inputClass(array $errors, string $field): string {
    return isset($errors[$field]) ? ' form-input--error' : '';
}
?>

<nav class="breadcrumb">
    <a href="<?= $base ?>/circles">Kreise</a>
    <span class="breadcrumb__sep">/</span>
    <span><?= $isEdit ? 'Bearbeiten' : 'Neuer Kreis' ?></span>
</nav>

<div class="page-header">
    <h1 class="page-header__title"><?= $isEdit ? 'Kreis bearbeiten' : 'Neuer Kreis' ?></h1>
</div>

<div class="card" style="max-width:720px">
    <div class="card__body">
        <form method="post" action="<?= $action ?>" class="form">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">

            <div class="form-row">
                <div class="form-field">
                    <label class="form-label form-label--required" for="name">Name des Kreises</label>
                    <input
                        type="text"
                        id="name"
                        name="name"
                        class="form-input<?= inputClass($errors, 'name') ?>"
                        value="<?= htmlspecialchars($circle['name'] ?? '') ?>"
                        required
                        autofocus
                    >
                    <?= fErr($errors, 'name') ?>
                </div>

                <div class="form-field">
                    <label class="form-label" for="parent_id">Überkreis</label>
                    <select id="parent_id" name="parent_id" class="form-select<?= inputClass($errors, 'parent_id') ?>">
                        <option value="">— kein Überkreis (Wurzelkreis) —</option>
                        <?php foreach ($allCircles as $c): ?>
                            <option value="<?= $c['id'] ?>"
                                <?= ($circle['parent_id'] ?? '') == $c['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?= fErr($errors, 'parent_id') ?>
                </div>
            </div>

            <div class="form-field">
                <label class="form-label" for="driver">Treiber (Organisationstreiber)</label>
                <textarea id="driver" name="driver" class="form-textarea" rows="3"
                          placeholder="Was ist die Situation, die diesen Kreis notwendig macht?"
                ><?= htmlspecialchars($circle['driver'] ?? '') ?></textarea>
                <span class="form-hint">Beschreibe Situation, Bedürfnis und mögliche Auswirkung.</span>
            </div>

            <div class="form-field">
                <label class="form-label" for="domain">Domäne</label>
                <textarea id="domain" name="domain" class="form-textarea" rows="2"
                          placeholder="Welcher Bereich liegt in der Verantwortung und Autorität dieses Kreises?"
                ><?= htmlspecialchars($circle['domain'] ?? '') ?></textarea>
            </div>

            <div class="form-field">
                <label class="form-label" for="purpose">Zweck</label>
                <input type="text" id="purpose" name="purpose" class="form-input"
                       placeholder="Wofür existiert dieser Kreis?"
                       value="<?= htmlspecialchars($circle['purpose'] ?? '') ?>">
            </div>

            <?php if ($isEdit): ?>
                <div class="form-field">
                    <label class="form-label" for="status">Status</label>
                    <select id="status" name="status" class="form-select">
                        <option value="active"   <?= ($circle['status'] ?? '') === 'active'   ? 'selected' : '' ?>>Aktiv</option>
                        <option value="archived" <?= ($circle['status'] ?? '') === 'archived' ? 'selected' : '' ?>>Archiviert</option>
                    </select>
                </div>
            <?php endif; ?>

            <div class="form-actions">
                <button type="submit" class="btn btn--primary">
                    <i class="ti ti-device-floppy" aria-hidden="true"></i>
                    <?= $isEdit ? 'Änderungen speichern' : 'Kreis anlegen' ?>
                </button>
                <a href="<?= $base ?>/circles<?= $isEdit ? '/' . $circle['id'] : '' ?>" class="btn btn--secondary">
                    Abbrechen
                </a>

                <?php if ($isEdit): ?>
                    <form method="post"
                          action="<?= $base ?>/circles/<?= $circle['id'] ?>/delete"
                          style="margin-left:auto"
                          onsubmit="return confirm('Kreis wirklich archivieren?')">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                        <button type="submit" class="btn btn--danger btn--sm">
                            <i class="ti ti-archive" aria-hidden="true"></i> Archivieren
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>
