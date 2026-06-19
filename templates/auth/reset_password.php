<?php $base = rtrim($config['app']['base_url'], '/'); ?>

<div class="auth-card">
    <div class="auth-card__brand">
        <div class="auth-card__logo" aria-hidden="true">◎</div>
        <div class="auth-card__app-name"><?= htmlspecialchars($config['app']['name']) ?></div>
        <div class="auth-card__tagline">Neues Passwort festlegen</div>
    </div>

    <form method="post" action="<?= $base ?>/password/reset/<?= htmlspecialchars($token) ?>" class="form">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">

        <div class="form-field">
            <label class="form-label form-label--required" for="password">Neues Passwort</label>
            <div class="input-with-action">
                <input
                    type="password"
                    id="password"
                    name="password"
                    class="form-input"
                    autocomplete="new-password"
                    minlength="8"
                    autofocus
                    required
                >
                <button
                    type="button"
                    class="input-with-action__btn js-password-toggle"
                    data-target="password"
                    aria-label="Passwort anzeigen"
                    aria-pressed="false"
                >
                    <i class="ti ti-eye" aria-hidden="true"></i>
                </button>
            </div>
            <div class="form-hint">Mindestens 8 Zeichen.</div>
        </div>

        <div class="form-field">
            <label class="form-label form-label--required" for="password_confirm">Passwort bestätigen</label>
            <div class="input-with-action">
                <input
                    type="password"
                    id="password_confirm"
                    name="password_confirm"
                    class="form-input"
                    autocomplete="new-password"
                    minlength="8"
                    required
                >
                <button
                    type="button"
                    class="input-with-action__btn js-password-toggle"
                    data-target="password_confirm"
                    aria-label="Passwort anzeigen"
                    aria-pressed="false"
                >
                    <i class="ti ti-eye" aria-hidden="true"></i>
                </button>
            </div>
        </div>

        <button type="submit" class="btn btn--primary" style="width:100%;justify-content:center;padding:.6rem">
            <i class="ti ti-lock" aria-hidden="true"></i> Passwort speichern
        </button>
    </form>
</div>

<script>
(function () {
    document.querySelectorAll('.js-password-toggle').forEach(function (toggle) {
        var input = document.getElementById(toggle.getAttribute('data-target'));
        if (!input) return;

        toggle.addEventListener('click', function () {
            var showing = input.type === 'text';
            input.type = showing ? 'password' : 'text';
            toggle.setAttribute('aria-pressed', String(!showing));
            toggle.setAttribute('aria-label', showing ? 'Passwort anzeigen' : 'Passwort verbergen');
            toggle.querySelector('i').className = showing ? 'ti ti-eye' : 'ti ti-eye-off';
        });
    });
})();
</script>
