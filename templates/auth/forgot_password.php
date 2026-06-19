<?php $base = rtrim($config['app']['base_url'], '/'); ?>

<div class="auth-card">
    <div class="auth-card__brand">
        <div class="auth-card__logo" aria-hidden="true">◎</div>
        <div class="auth-card__app-name"><?= htmlspecialchars($config['app']['name']) ?></div>
        <div class="auth-card__tagline">Passwort zurücksetzen</div>
    </div>

    <p style="margin-bottom: var(--sp-5); color: var(--c-ink-2); font-size: var(--text-sm);">
        Gib deine E-Mail-Adresse ein. Falls sie bei uns registriert ist,
        schicken wir dir einen Link, mit dem du ein neues Passwort festlegen kannst.
    </p>

    <form method="post" action="<?= $base ?>/password/forgot" class="form">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">

        <div class="form-field">
            <label class="form-label form-label--required" for="email">E-Mail</label>
            <input
                type="email"
                id="email"
                name="email"
                class="form-input"
                autocomplete="email"
                autofocus
                required
            >
        </div>

        <button type="submit" class="btn btn--primary" style="width:100%;justify-content:center;padding:.6rem">
            <i class="ti ti-mail" aria-hidden="true"></i> Link zusenden
        </button>
    </form>

    <div class="auth-card__foot">
        <a class="auth-card__link" href="<?= $base ?>/login">
            <i class="ti ti-arrow-left" aria-hidden="true"></i> Zurück zur Anmeldung
        </a>
    </div>
</div>
