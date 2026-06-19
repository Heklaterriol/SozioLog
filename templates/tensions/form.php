<?php
$base = rtrim($config['app']['base_url'], '/');

function tfErr(array $errors, string $field): string {
    return isset($errors[$field])
        ? '<span class="form-error">' . htmlspecialchars($errors[$field]) . '</span>' : '';
}
function tfCls(array $errors, string $field): string {
    return isset($errors[$field]) ? ' form-input--error' : '';
}
?>

<nav class="breadcrumb">
    <a href="<?= $base ?>/circles">Kreise</a>
    <span class="breadcrumb__sep">/</span>
    <span>Spannung einreichen</span>
</nav>

<div class="page-header">
    <h1 class="page-header__title">Spannung einreichen</h1>
</div>

<div class="card" style="max-width:700px">
    <div class="card__body">
        <form method="post" action="<?= $base ?>/tensions" class="form">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">

            <div class="form-field">
                <label class="form-label form-label--required" for="circle_id">Kreis</label>
                <select id="circle_id" name="circle_id" class="form-select<?= tfCls($errors, 'circle_id') ?>" required>
                    <option value="">— bitte wählen —</option>
                    <?php foreach ($circles as $c): ?>
                        <option value="<?= $c['id'] ?>"
                            <?= ($tension['circle_id'] ?? 0) == $c['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?= tfErr($errors, 'circle_id') ?>
            </div>

            <div class="form-field">
                <label class="form-label" for="tension_title">Titel <span class="text-muted">(optional, kurz)</span></label>
                <input type="text" id="tension_title" name="title" class="form-input"
                       value="<?= htmlspecialchars($tension['title'] ?? '') ?>"
                       placeholder="Kurze Überschrift der Spannung" autofocus>
                <?= tfErr($errors, 'title') ?>
            </div>

            <div class="form-field">
                <label class="form-label" for="description">Beschreibung</label>
                <textarea id="description" name="description" class="form-textarea" rows="5"
                          placeholder="Was ist die Situation? Welches Bedürfnis ist nicht erfüllt? Was ist die Auswirkung?"
                ><?= htmlspecialchars($tension['description'] ?? '') ?></textarea>
                <span class="form-hint">S3: Beschreibe Situation, Bedürfnis und Auswirkung so konkret wie möglich.</span>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn--primary">
                    <i class="ti ti-send" aria-hidden="true"></i> Spannung einreichen
                </button>
                <a href="<?= $base ?>/circles<?= !empty($tension['circle_id']) ? '/' . $tension['circle_id'] . '/tensions' : '' ?>"
                   class="btn btn--secondary">Abbrechen</a>
            </div>
        </form>
    </div>
</div>
