<?php
$base   = rtrim($config['app']['base_url'], '/');
$isEdit = !empty($role['id']);
$action = $isEdit
    ? $base . '/roles/' . $role['id']
    : $base . '/circles/' . $role['circle_id'] . '/roles';

function rfErr(array $errors, string $field): string {
    return isset($errors[$field])
        ? '<span class="form-error">' . htmlspecialchars($errors[$field]) . '</span>' : '';
}
function rfCls(array $errors, string $field): string {
    return isset($errors[$field]) ? ' form-input--error' : '';
}
?>

<nav class="breadcrumb">
    <a href="<?= $base ?>/circles">Kreise</a>
    <span class="breadcrumb__sep">/</span>
    <a href="<?= $base ?>/circles/<?= $circle['id'] ?>"><?= htmlspecialchars($circle['name']) ?></a>
    <span class="breadcrumb__sep">/</span>
    <a href="<?= $base ?>/circles/<?= $circle['id'] ?>/roles">Rollen</a>
    <span class="breadcrumb__sep">/</span>
    <span><?= $isEdit ? 'Bearbeiten' : 'Neue Rolle' ?></span>
</nav>

<div class="page-header">
    <h1 class="page-header__title"><?= $isEdit ? 'Rolle bearbeiten' : 'Neue Rolle' ?></h1>
    <p class="page-header__sub" style="margin-top:var(--sp-1)">Kreis: <?= htmlspecialchars($circle['name']) ?></p>
</div>

<div class="card" style="max-width:760px">
    <div class="card__body">
        <form method="post" action="<?= $action ?>" class="form">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">

            <div class="form-row">
                <div class="form-field">
                    <label class="form-label form-label--required" for="name">Rollenname</label>
                    <input type="text" id="name" name="name"
                           class="form-input<?= rfCls($errors, 'name') ?>"
                           value="<?= htmlspecialchars($role['name'] ?? '') ?>"
                           placeholder="z.B. Koordinator·in, Kassierer·in …"
                           required autofocus>
                    <?= rfErr($errors, 'name') ?>
                </div>

                <div class="form-field">
                    <label class="form-label form-label--required" for="role_type">Rollentyp</label>
                    <select id="role_type" name="role_type" class="form-select<?= rfCls($errors, 'role_type') ?>">
                        <?php
                        $types = [
                            'general'       => 'Allgemein',
                            'facilitator'   => 'Moderator·in',
                            'secretary'     => 'Sekretär·in',
                            'rep_link'      => 'Rep-Link',
                            'delegate_link' => 'Del-Link',
                            'elected'       => 'Gewählt',
                        ];
                        foreach ($types as $val => $label): ?>
                            <option value="<?= $val ?>"
                                <?= ($role['role_type'] ?? 'general') === $val ? 'selected' : '' ?>>
                                <?= $label ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?= rfErr($errors, 'role_type') ?>
                </div>
            </div>

            <div class="form-field">
                <label class="form-label" for="purpose">Zweck</label>
                <input type="text" id="purpose" name="purpose"
                       class="form-input"
                       value="<?= htmlspecialchars($role['purpose'] ?? '') ?>"
                       placeholder="Wofür existiert diese Rolle?">
            </div>

            <div class="form-field">
                <label class="form-label" for="domain">Domäne</label>
                <textarea id="domain" name="domain" class="form-textarea" rows="2"
                          placeholder="Welcher Bereich liegt in der exklusiven Autorität dieser Rolle?"
                ><?= htmlspecialchars($role['domain'] ?? '') ?></textarea>
            </div>

            <!-- Accountabilities — eine pro Zeile -->
            <div class="form-field">
                <label class="form-label" for="accountabilities_text">
                    Accountabilities
                    <span class="form-hint" style="font-weight:400;margin-left:var(--sp-2)">eine pro Zeile</span>
                </label>
                <textarea id="accountabilities_text" name="accountabilities_text"
                          class="form-textarea" rows="5"
                          placeholder="Kommunikation nach außen pflegen&#10;Protokoll führen&#10;…"
                ><?= htmlspecialchars($role['accountabilities_text'] ?? '') ?></textarea>
                <span class="form-hint">Jede Zeile wird als einzelne Accountability gespeichert.</span>
            </div>

            <div class="form-field">
                <label class="form-check">
                    <input type="checkbox" name="is_elected" value="1"
                           <?= !empty($role['is_elected']) ? 'checked' : '' ?>>
                    Gewählte Rolle (unterliegt dem Wahlprozess)
                </label>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn--primary">
                    <i class="ti ti-device-floppy" aria-hidden="true"></i>
                    <?= $isEdit ? 'Änderungen speichern' : 'Rolle anlegen' ?>
                </button>
                <a href="<?= $isEdit ? $base . '/roles/' . $role['id'] : $base . '/circles/' . $circle['id'] . '/roles' ?>"
                   class="btn btn--secondary">Abbrechen</a>
            </div>
        </form>
    </div>
</div>
