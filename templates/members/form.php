<?php
$base   = rtrim($config['app']['base_url'], '/');
$isEdit = !empty($member['id']);
$action = $isEdit ? $base . '/members/' . $member['id'] : $base . '/members';

function mfErr(array $errors, string $field): string {
    return isset($errors[$field])
        ? '<span class="form-error">' . htmlspecialchars($errors[$field]) . '</span>' : '';
}
function mfCls(array $errors, string $field): string {
    return isset($errors[$field]) ? ' form-input--error' : '';
}
?>

<nav class="breadcrumb">
    <a href="<?= $base ?>/members">Mitglieder</a>
    <span class="breadcrumb__sep">/</span>
    <span><?= $isEdit ? 'Bearbeiten' : 'Person anlegen' ?></span>
</nav>

<div class="page-header">
    <h1 class="page-header__title"><?= $isEdit ? 'Person bearbeiten' : 'Person anlegen' ?></h1>
</div>

<div class="card" style="max-width:560px">
    <div class="card__body">
        <form method="post" action="<?= $action ?>" class="form">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">

            <div class="form-field">
                <label class="form-label form-label--required" for="name">Name</label>
                <input type="text" id="name" name="name"
                       class="form-input<?= mfCls($errors, 'name') ?>"
                       value="<?= htmlspecialchars($member['name'] ?? '') ?>"
                       required autofocus>
                <?= mfErr($errors, 'name') ?>
            </div>

            <div class="form-field">
                <label class="form-label form-label--required" for="email">E-Mail</label>
                <input type="email" id="email" name="email"
                       class="form-input<?= mfCls($errors, 'email') ?>"
                       value="<?= htmlspecialchars($member['email'] ?? '') ?>"
                       autocomplete="email"
                       required>
                <?= mfErr($errors, 'email') ?>
            </div>

            <div class="form-field">
                <label class="form-label <?= !$isEdit ? 'form-label--required' : '' ?>" for="password">
                    Passwort<?= $isEdit ? ' <span class="text-muted" style="font-weight:400">(leer = nicht ändern)</span>' : '' ?>
                </label>
                <input type="password" id="password" name="password"
                       class="form-input<?= mfCls($errors, 'password') ?>"
                       autocomplete="new-password"
                       <?= !$isEdit ? 'required' : '' ?>>
                <?= mfErr($errors, 'password') ?>
                <span class="form-hint">Mindestens 8 Zeichen.</span>
            </div>

            <?php if (!empty($currentUser['is_admin'])): ?>
                <div class="form-field">
                    <label class="form-check">
                        <input type="checkbox" name="is_admin" value="1"
                               <?= !empty($member['is_admin']) ? 'checked' : '' ?>>
                        Administrator-Rechte (kann Kreise, Rollen und Mitglieder verwalten)
                    </label>
                </div>
            <?php endif; ?>

            <div class="form-actions">
                <button type="submit" class="btn btn--primary">
                    <i class="ti ti-device-floppy" aria-hidden="true"></i>
                    <?= $isEdit ? 'Änderungen speichern' : 'Person anlegen' ?>
                </button>
                <a href="<?= $isEdit ? $base . '/members/' . $member['id'] : $base . '/members' ?>"
                   class="btn btn--secondary">Abbrechen</a>
            </div>
        </form>
    </div>
</div>
