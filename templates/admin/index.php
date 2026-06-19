<?php $base = rtrim($config['app']['base_url'], '/'); ?>

<div class="page-header">
    <div>
        <h1 class="page-header__title">Administration</h1>
        <p class="page-header__sub"><?= htmlspecialchars($config['app']['name']) ?></p>
    </div>
</div>

<!-- Statistik-Übersicht -->
<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-card__icon"><i class="ti ti-circle" aria-hidden="true"></i></div>
        <div class="stat-card__value"><?= $stats['circles'] ?></div>
        <div class="stat-card__label">Kreise gesamt</div>
    </div>
    <div class="stat-card">
        <div class="stat-card__icon"><i class="ti ti-user-circle" aria-hidden="true"></i></div>
        <div class="stat-card__value"><?= $stats['roles'] ?></div>
        <div class="stat-card__label">Rollen</div>
    </div>
    <div class="stat-card">
        <div class="stat-card__icon"><i class="ti ti-users" aria-hidden="true"></i></div>
        <div class="stat-card__value"><?= $stats['members'] ?></div>
        <div class="stat-card__label">Mitglieder</div>
    </div>
    <div class="stat-card">
        <div class="stat-card__icon"><i class="ti ti-file-text" aria-hidden="true"></i></div>
        <div class="stat-card__value"><?= $stats['agreements'] ?></div>
        <div class="stat-card__label">Vereinbarungen</div>
    </div>
    <div class="stat-card">
        <div class="stat-card__icon"><i class="ti ti-notes" aria-hidden="true"></i></div>
        <div class="stat-card__value"><?= $stats['meetings'] ?></div>
        <div class="stat-card__label">Meetings</div>
    </div>
    <div class="stat-card">
        <div class="stat-card__icon" style="background:var(--c-error-lt);color:var(--c-error)">
            <i class="ti ti-bolt" aria-hidden="true"></i>
        </div>
        <div class="stat-card__value"><?= $stats['tensions'] ?></div>
        <div class="stat-card__label">Spannungen</div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--sp-5)">

    <!-- Export -->
    <div class="card">
        <div class="card__header">
            <span class="card__title"><i class="ti ti-database-export" aria-hidden="true"></i> Daten exportieren</span>
        </div>
        <div class="card__body" style="display:flex;flex-direction:column;gap:var(--sp-4)">

            <div style="padding:var(--sp-4);background:var(--c-bg);border-radius:var(--r-md);border:1px solid var(--c-border)">
                <div class="fw-600 text-sm" style="margin-bottom:var(--sp-1)">
                    <i class="ti ti-file-type-pdf" aria-hidden="true" style="color:var(--c-error)"></i>
                    PDF-Logbuch
                </div>
                <p class="text-sm text-muted" style="margin-bottom:var(--sp-3)">
                    Vollständiges Logbuch als druckbares PDF — alle Kreise, Rollen, Vereinbarungen,
                    Meetings und Spannungen. Erfordert <code style="font-size:11px">mpdf/mpdf</code> via Composer.
                </p>
                <a href="<?= $base ?>/admin/export?format=pdf" class="btn btn--primary btn--sm">
                    <i class="ti ti-download" aria-hidden="true"></i> PDF herunterladen
                </a>
            </div>

            <div style="padding:var(--sp-4);background:var(--c-bg);border-radius:var(--r-md);border:1px solid var(--c-border)">
                <div class="fw-600 text-sm" style="margin-bottom:var(--sp-1)">
                    <i class="ti ti-braces" aria-hidden="true" style="color:var(--c-accent)"></i>
                    JSON-Export
                </div>
                <p class="text-sm text-muted" style="margin-bottom:var(--sp-3)">
                    Alle Daten als strukturiertes JSON — geeignet als Backup oder für den
                    Import in andere Systeme. Keine zusätzliche Abhängigkeit erforderlich.
                </p>
                <a href="<?= $base ?>/admin/export?format=json" class="btn btn--secondary btn--sm">
                    <i class="ti ti-download" aria-hidden="true"></i> JSON herunterladen
                </a>
            </div>

        </div>
    </div>

    <!-- Einstellungen & Hinweise -->
    <div style="display:flex;flex-direction:column;gap:var(--sp-5)">

        <!-- Schnellzugriff -->
        <div class="card">
            <div class="card__header">
                <span class="card__title"><i class="ti ti-link" aria-hidden="true"></i> Schnellzugriff</span>
            </div>
            <div class="card__body" style="display:flex;flex-direction:column;gap:var(--sp-2)">
                <a href="<?= $base ?>/circles/new"  class="btn btn--ghost" style="justify-content:flex-start">
                    <i class="ti ti-circle-plus" aria-hidden="true"></i> Neuen Kreis anlegen
                </a>
                <a href="<?= $base ?>/members/new"  class="btn btn--ghost" style="justify-content:flex-start">
                    <i class="ti ti-user-plus" aria-hidden="true"></i> Person anlegen
                </a>
                <a href="<?= $base ?>/agreements/new" class="btn btn--ghost" style="justify-content:flex-start">
                    <i class="ti ti-file-plus" aria-hidden="true"></i> Vereinbarung anlegen
                </a>
                <a href="<?= $base ?>/tensions/new" class="btn btn--ghost" style="justify-content:flex-start">
                    <i class="ti ti-bolt" aria-hidden="true"></i> Spannung einreichen
                </a>
            </div>
        </div>

        <!-- Setup-Hinweise -->
        <div class="card">
            <div class="card__header">
                <span class="card__title"><i class="ti ti-info-circle" aria-hidden="true"></i> PDF-Export einrichten</span>
            </div>
            <div class="card__body">
                <p class="text-sm text-muted" style="margin-bottom:var(--sp-3)">
                    Der PDF-Export verwendet <strong>mPDF</strong>. Installation per Composer im Projektstammverzeichnis:
                </p>
                <pre style="background:var(--c-ink);color:#C8C4BC;padding:var(--sp-3) var(--sp-4);
                             border-radius:var(--r-md);font-size:12px;overflow-x:auto;line-height:1.6">composer require mpdf/mpdf</pre>
                <p class="text-sm text-muted" style="margin-top:var(--sp-3)">
                    Danach steht der «PDF herunterladen»-Button links zur Verfügung.
                    Ohne Composer ist der JSON-Export sofort nutzbar.
                </p>
            </div>
        </div>

    </div>
</div>

<!-- Einstellungen (erweiterbar) -->
<div class="card">
    <div class="card__header">
        <span class="card__title"><i class="ti ti-settings" aria-hidden="true"></i> Konfiguration</span>
    </div>
    <div class="card__body">
        <form method="post" action="<?= $base ?>/admin" class="form">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">

            <div class="form-row">
                <div class="form-field">
                    <label class="form-label" for="cfg_name">Organisations-Name</label>
                    <input type="text" id="cfg_name" name="cfg_name"
                           class="form-input"
                           value="<?= htmlspecialchars($config['app']['name']) ?>"
                           placeholder="Name der Organisation">
                    <span class="form-hint">Wird im Seitentitel und im Export verwendet.</span>
                </div>
                <div class="form-field">
                    <label class="form-label" for="cfg_timezone">Zeitzone</label>
                    <input type="text" id="cfg_timezone" name="cfg_timezone"
                           class="form-input"
                           value="<?= htmlspecialchars($config['app']['timezone']) ?>"
                           placeholder="Europe/Berlin">
                    <span class="form-hint">Gültige PHP-Zeitzone, z.B. <code>Europe/Berlin</code>.</span>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn--primary">
                    <i class="ti ti-device-floppy" aria-hidden="true"></i> Einstellungen speichern
                </button>
                <span class="text-sm text-muted" style="margin-left:var(--sp-2)">
                    Hinweis: Einstellungen werden derzeit in <code>config/config.local.php</code> gepflegt.
                </span>
            </div>
        </form>
    </div>
</div>
