<?php
$base = rtrim($config['app']['base_url'], '/');
$statusLabels = [
    'active'     => 'Aktiv',
    'draft'      => 'Entwurf',
    'review_due' => 'Review fällig',
    'expired'    => 'Abgelaufen',
];

/**
 * Einfacher Zeilenvergleich: gibt HTML-String zurück,
 * bei dem geänderte Zeilen hervorgehoben sind.
 */
function highlightDiff(string $old, string $new): string
{
    $oldLines = explode("\n", $old);
    $newLines = explode("\n", $new);
    $out = [];
    $maxLen = max(count($oldLines), count($newLines));

    for ($i = 0; $i < $maxLen; $i++) {
        $o = $oldLines[$i] ?? '';
        $n = $newLines[$i] ?? '';
        if ($o === $n) {
            $out[] = '<span>' . htmlspecialchars($n) . '</span>';
        } elseif ($o === '') {
            $out[] = '<span style="background:rgba(29,111,106,.12);display:block;padding:0 3px">+ ' . htmlspecialchars($n) . '</span>';
        } elseif ($n === '') {
            $out[] = '<span style="background:rgba(139,32,32,.10);display:block;padding:0 3px">- ' . htmlspecialchars($o) . '</span>';
        } else {
            $out[] = '<span style="background:rgba(139,32,32,.10);display:block;padding:0 3px;text-decoration:line-through">- ' . htmlspecialchars($o) . '</span>';
            $out[] = '<span style="background:rgba(29,111,106,.12);display:block;padding:0 3px">+ ' . htmlspecialchars($n) . '</span>';
        }
    }
    return implode("\n", $out);
}
?>

<nav class="breadcrumb">
    <a href="<?= $base ?>/circles/<?= $agreement['circle_id'] ?>/agreements">Vereinbarungen</a>
    <span class="breadcrumb__sep">/</span>
    <a href="<?= $base ?>/agreements/<?= $agreement['id'] ?>"><?= htmlspecialchars($agreement['title']) ?></a>
    <span class="breadcrumb__sep">/</span>
    <a href="<?= $base ?>/agreements/<?= $agreement['id'] ?>/versions">Versionen</a>
    <span class="breadcrumb__sep">/</span>
    <span>v<?= $version['version'] ?></span>
</nav>

<div class="page-header">
    <div>
        <h1 class="page-header__title">
            Version <?= $version['version'] ?>
            <span style="color:var(--c-ink-2);font-weight:400;font-size:.7em">
                — <?= htmlspecialchars($agreement['title']) ?>
            </span>
        </h1>
        <p class="page-header__sub">
            Gespeichert am <?= date('d.m.Y \u\m H:i', strtotime($version['created_at'])) ?> Uhr
            <?= $version['changed_by_name'] ? '· von ' . htmlspecialchars($version['changed_by_name']) : '' ?>
            <?= $version['change_note'] ? '· <em>' . htmlspecialchars($version['change_note']) . '</em>' : '' ?>
        </p>
    </div>
    <div class="page-header__actions">
        <a href="<?= $base ?>/agreements/<?= $agreement['id'] ?>/versions" class="btn btn--secondary">
            <i class="ti ti-history" aria-hidden="true"></i> Alle Versionen
        </a>
        <?php if (!empty($currentUser['is_admin'])): ?>
            <form method="post"
                  action="<?= $base ?>/agreements/<?= $agreement['id'] ?>/versions/<?= $version['version'] ?>/restore"
                  onsubmit="return confirm('Version <?= $version['version'] ?> wiederherstellen? Der aktuelle Stand wird vorher als neue Version gesichert.')">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                <button type="submit" class="btn btn--primary">
                    <i class="ti ti-restore" aria-hidden="true"></i> Diese Version wiederherstellen
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>

<!-- Meta-Info der Version -->
<div class="card">
    <div class="card__body">
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:var(--sp-4)">
            <div>
                <div class="text-xs text-muted" style="text-transform:uppercase;letter-spacing:.04em;margin-bottom:4px">Version</div>
                <span class="badge badge--draft" style="font-family:var(--font-mono);font-size:.85em">
                    v<?= $version['version'] ?>
                </span>
            </div>
            <div>
                <div class="text-xs text-muted" style="text-transform:uppercase;letter-spacing:.04em;margin-bottom:4px">Status (damals)</div>
                <span class="badge badge--<?= htmlspecialchars($version['status']) ?>">
                    <?= htmlspecialchars($statusLabels[$version['status']] ?? $version['status']) ?>
                </span>
            </div>
            <div>
                <div class="text-xs text-muted" style="text-transform:uppercase;letter-spacing:.04em;margin-bottom:4px">Beschlossen (damals)</div>
                <div class="text-sm"><?= date('d.m.Y', strtotime($version['agreed_at'])) ?></div>
            </div>
            <?php if ($version['review_date']): ?>
                <div>
                    <div class="text-xs text-muted" style="text-transform:uppercase;letter-spacing:.04em;margin-bottom:4px">Review (damals)</div>
                    <div class="text-sm"><?= date('d.m.Y', strtotime($version['review_date'])) ?></div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Vergleich: Version ↔ Aktuell -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--sp-5)">

    <!-- Inhalt der Version -->
    <div style="display:flex;flex-direction:column;gap:var(--sp-4)">
        <div class="card">
            <div class="card__header">
                <span class="card__title">
                    <span class="badge badge--draft" style="font-family:var(--font-mono)">v<?= $version['version'] ?></span>
                    Inhalt dieser Version
                </span>
            </div>
            <div class="card__body" style="display:flex;flex-direction:column;gap:var(--sp-4)">
                <?php if ($version['driver']): ?>
                    <div>
                        <div class="text-xs text-muted" style="text-transform:uppercase;letter-spacing:.04em;margin-bottom:var(--sp-1)">Treiber</div>
                        <p class="text-sm" style="white-space:pre-line"><?= htmlspecialchars($version['driver']) ?></p>
                    </div>
                <?php endif; ?>
                <?php if ($version['body']): ?>
                    <div>
                        <div class="text-xs text-muted" style="text-transform:uppercase;letter-spacing:.04em;margin-bottom:var(--sp-1)">Inhalt</div>
                        <p class="text-sm" style="white-space:pre-line"><?= htmlspecialchars($version['body']) ?></p>
                    </div>
                <?php endif; ?>
                <?php if (!$version['driver'] && !$version['body']): ?>
                    <p class="text-sm text-muted">Kein Inhalt in dieser Version.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Aktueller Stand -->
    <div style="display:flex;flex-direction:column;gap:var(--sp-4)">
        <div class="card">
            <div class="card__header">
                <span class="card__title">
                    <span class="badge badge--active">Aktuell</span>
                    Aktueller Stand
                </span>
            </div>
            <div class="card__body" style="display:flex;flex-direction:column;gap:var(--sp-4)">
                <?php if ($agreement['driver']): ?>
                    <div>
                        <div class="text-xs text-muted" style="text-transform:uppercase;letter-spacing:.04em;margin-bottom:var(--sp-1)">Treiber</div>
                        <p class="text-sm" style="white-space:pre-line"><?= htmlspecialchars($agreement['driver']) ?></p>
                    </div>
                <?php endif; ?>
                <?php if ($agreement['body']): ?>
                    <div>
                        <div class="text-xs text-muted" style="text-transform:uppercase;letter-spacing:.04em;margin-bottom:var(--sp-1)">Inhalt</div>
                        <p class="text-sm" style="white-space:pre-line"><?= htmlspecialchars($agreement['body']) ?></p>
                    </div>
                <?php endif; ?>
                <?php if (!$agreement['driver'] && !$agreement['body']): ?>
                    <p class="text-sm text-muted">Kein Inhalt aktuell.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>

<!-- Änderungen (Diff) -->
<?php
$bodyOld = $version['body'] ?? '';
$bodyCur = $agreement['body'] ?? '';
$driverOld = $version['driver'] ?? '';
$driverCur = $agreement['driver'] ?? '';
$hasDiff = ($bodyOld !== $bodyCur || $driverOld !== $driverCur);
?>
<?php if ($hasDiff): ?>
    <div class="card">
        <div class="card__header">
            <span class="card__title"><i class="ti ti-git-diff" aria-hidden="true"></i> Änderungen (v<?= $version['version'] ?> → Aktuell)</span>
        </div>
        <div class="card__body" style="display:flex;flex-direction:column;gap:var(--sp-4)">
            <?php if ($driverOld !== $driverCur): ?>
                <div>
                    <div class="text-xs text-muted" style="text-transform:uppercase;letter-spacing:.04em;margin-bottom:var(--sp-2)">Treiber</div>
                    <pre style="font-family:var(--font-mono);font-size:12px;line-height:1.7;background:var(--c-bg);
                                padding:var(--sp-3);border-radius:var(--r-md);overflow-x:auto;white-space:pre-wrap"><?= highlightDiff($driverOld, $driverCur) ?></pre>
                </div>
            <?php endif; ?>
            <?php if ($bodyOld !== $bodyCur): ?>
                <div>
                    <div class="text-xs text-muted" style="text-transform:uppercase;letter-spacing:.04em;margin-bottom:var(--sp-2)">Inhalt</div>
                    <pre style="font-family:var(--font-mono);font-size:12px;line-height:1.7;background:var(--c-bg);
                                padding:var(--sp-3);border-radius:var(--r-md);overflow-x:auto;white-space:pre-wrap"><?= highlightDiff($bodyOld, $bodyCur) ?></pre>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php else: ?>
    <div class="card">
        <div class="card__body">
            <p class="text-sm text-muted">
                <i class="ti ti-check" style="color:var(--c-success)" aria-hidden="true"></i>
                Kein Unterschied im Inhalt zwischen dieser Version und dem aktuellen Stand
                (Metadaten wie Status oder Datum können sich dennoch unterscheiden).
            </p>
        </div>
    </div>
<?php endif; ?>
