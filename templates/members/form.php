<?php
$base   = rtrim($config['app']['base_url'], '/');
$isEdit = !empty($member['id']);
$action = $isEdit ? $base . '/members/' . $member['id'] : $base . '/members';
$currentLevel = $member['permission_level'] ?? ($member['is_admin'] ?? false ? 'admin' : 'member');

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
                <span class="form-hint">
                    Die Anmeldung erfolgt per Nextcloud — sobald sich diese Person mit
                    derselben E-Mail-Adresse über Nextcloud anmeldet, wird ihr Konto
                    automatisch verknüpft.
                </span>
            </div>

            <?php if (!empty($isAdmin)): ?>
                <div class="form-field">
                    <label class="form-label">Berechtigung</label>
                    <div style="display:flex;flex-direction:column;gap:var(--sp-2)">
                        <label class="form-check form-check--block">
                            <input type="radio" name="permission_level" value="admin"
                                   <?= $currentLevel === 'admin' ? 'checked' : '' ?>>
                            <span>
                                <strong>Admin</strong><br>
                                <span class="text-muted">kann Kreise, Rollen und Mitglieder verwalten</span>
                            </span>
                        </label>
                        <label class="form-check form-check--block">
                            <input type="radio" name="permission_level" value="member"
                                   <?= $currentLevel === 'member' || $currentLevel === '' ? 'checked' : '' ?>>
                            <span>
                                <strong>Mitglied</strong><br>
                                <span class="text-muted">sieht alles, verwaltet im eigenen Kreis</span>
                            </span>
                        </label>
                        <label class="form-check form-check--block">
                            <input type="radio" name="permission_level" value="readonly"
                                   <?= $currentLevel === 'readonly' ? 'checked' : '' ?>>
                            <span>
                                <strong>Lesend</strong><br>
                                <span class="text-muted">sieht Kreise und Mitglieder, keine Bearbeitung</span>
                            </span>
                        </label>
                    </div>
                </div>
            <?php elseif (!$isEdit): ?>
                <?php if (!empty($ownCircles)): ?>
                    <div class="form-field">
                        <label class="form-label">Kreiszugehörigkeit</label>
                        <?php if (count($ownCircles) === 1): ?>
                            <p class="text-sm text-muted">
                                Wird automatisch <strong><?= htmlspecialchars($ownCircles[0]['name']) ?></strong> zugeordnet.
                            </p>
                            <input type="hidden" name="circle_ids[]" value="<?= $ownCircles[0]['id'] ?>">
                        <?php else: ?>
                            <p class="text-sm text-muted" style="margin-bottom:var(--sp-2)">
                                Du kannst Personen nur deinen eigenen Kreisen zuordnen.
                            </p>
                            <div style="display:flex;flex-direction:column;gap:var(--sp-2);padding:var(--sp-3);
                                        border:1px solid var(--c-border);border-radius:var(--r-md)">
                                <?php foreach ($ownCircles as $c): ?>
                                    <label class="form-check">
                                        <input type="checkbox" name="circle_ids[]" value="<?= $c['id'] ?>" checked>
                                        <?= htmlspecialchars($c['name']) ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
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
