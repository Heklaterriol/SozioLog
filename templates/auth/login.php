<?php $base = rtrim($config['app']['base_url'], '/'); ?>

<div class="auth-card">
    <div class="auth-card__brand">
        <div class="auth-card__logo" aria-hidden="true">◎</div>
        <div class="auth-card__app-name"><?= htmlspecialchars($config['app']['name']) ?></div>
        <div class="auth-card__tagline">Soziokratisches Logbuch (S3)</div>
    </div>

    <form method="post" action="<?= $base ?>/login" class="form">
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

        <div class="form-field">
            <label class="form-label form-label--required" for="password">Passwort</label>
            <div class="input-with-action">
                <input
                    type="password"
                    id="password"
                    name="password"
                    class="form-input"
                    autocomplete="current-password"
                    required
                >
                <button
                    type="button"
                    class="input-with-action__btn"
                    id="password-toggle"
                    aria-label="Passwort anzeigen"
                    aria-pressed="false"
                    aria-controls="password"
                >
                    <i class="ti ti-eye" aria-hidden="true"></i>
                </button>
            </div>
        </div>

        <div class="auth-card__row">
            <a class="auth-card__link" href="<?= $base ?>/password/forgot">Passwort vergessen?</a>
        </div>

        <button type="submit" class="btn btn--primary" style="width:100%;justify-content:center;padding:.6rem">
            <i class="ti ti-login" aria-hidden="true"></i> Anmelden
        </button>
    </form>
</div>

<script>
(function () {
    var toggle = document.getElementById('password-toggle');
    var input  = document.getElementById('password');
    if (!toggle || !input) return;

    toggle.addEventListener('click', function () {
        var showing = input.type === 'text';
        input.type = showing ? 'password' : 'text';
        toggle.setAttribute('aria-pressed', String(!showing));
        toggle.setAttribute('aria-label', showing ? 'Passwort anzeigen' : 'Passwort verbergen');
        toggle.querySelector('i').className = showing ? 'ti ti-eye' : 'ti ti-eye-off';
    });
})();
</script>
