<?php $base = rtrim($config['app']['base_url'], '/'); ?>

<div class="auth-card">
    <div class="auth-card__brand">
        <div class="auth-card__logo" aria-hidden="true">◎</div>
        <div class="auth-card__app-name"><?= htmlspecialchars($config['app']['name']) ?></div>
        <div class="auth-card__tagline">Soziokratisches Logbuch</div>
    </div>

    <p style="text-align:center;color:var(--c-ink-2);margin-bottom:var(--sp-5)">
        Anmeldung erfolgt mit deinem Nextcloud-Konto.
    </p>

    <a href="<?= $base ?>/auth/nextcloud" class="btn btn--primary"
       style="width:100%;justify-content:center;padding:.7rem">
        <i class="ti ti-cloud" aria-hidden="true"></i> Mit Nextcloud anmelden
    </a>
</div>
