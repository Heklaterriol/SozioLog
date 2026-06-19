<?php
/** @var array $stats */
/** @var array $upcomingMeetings */
/** @var array $reviewDue */
/** @var array $openTensions */
/** @var array $unfilledRoles */
$base = rtrim($config['app']['base_url'], '/');
?>

<div class="page-header">
    <div>
        <h1 class="page-header__title">Dashboard</h1>
        <p class="page-header__sub"><?= date('l, j. F Y') ?></p>
    </div>
</div>

<!-- Kennzahlen -->
<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-card__icon"><i class="ti ti-circle" aria-hidden="true"></i></div>
        <div class="stat-card__value"><?= $stats['circles'] ?></div>
        <div class="stat-card__label">Aktive Kreise</div>
    </div>
    <div class="stat-card">
        <div class="stat-card__icon"><i class="ti ti-user-circle" aria-hidden="true"></i></div>
        <div class="stat-card__value"><?= $stats['roles'] ?></div>
        <div class="stat-card__label">Rollen gesamt</div>
    </div>
    <div class="stat-card">
        <div class="stat-card__icon" style="background:var(--c-error-lt);color:var(--c-error)">
            <i class="ti ti-bolt" aria-hidden="true"></i>
        </div>
        <div class="stat-card__value"><?= $stats['open_tensions'] ?></div>
        <div class="stat-card__label">Offene Spannungen</div>
    </div>
    <div class="stat-card">
        <div class="stat-card__icon" style="background:var(--c-warning-lt);color:var(--c-warning)">
            <i class="ti ti-calendar-due" aria-hidden="true"></i>
        </div>
        <div class="stat-card__value"><?= $stats['review_due'] ?></div>
        <div class="stat-card__label">Reviews fällig (30 Tage)</div>
    </div>
</div>

<!-- 2-Spalten -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--sp-5)">

    <!-- Nächste Meetings -->
    <div class="card">
        <div class="card__header">
            <span class="card__title">
                <i class="ti ti-notes" aria-hidden="true"></i> Nächste Meetings
            </span>
            <a href="<?= $base ?>/circles" class="btn btn--ghost btn--sm">Alle Kreise</a>
        </div>
        <div class="card__body--flush">
            <?php if (empty($upcomingMeetings)): ?>
                <div class="empty-state" style="padding:var(--sp-8)">
                    <i class="ti ti-calendar-off" aria-hidden="true"></i>
                    <span>Keine anstehenden Meetings</span>
                </div>
            <?php else: ?>
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Datum</th>
                                <th>Kreis</th>
                                <th>Typ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($upcomingMeetings as $m): ?>
                                <tr>
                                    <td>
                                        <a href="<?= $base ?>/meetings/<?= $m['id'] ?>">
                                            <?= date('d.m.Y H:i', strtotime($m['held_at'])) ?>
                                        </a>
                                    </td>
                                    <td class="text-sm"><?= htmlspecialchars($m['circle_name']) ?></td>
                                    <td>
                                        <span class="badge badge--open">
                                            <?= htmlspecialchars($m['meeting_type']) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Review fällig -->
    <div class="card">
        <div class="card__header">
            <span class="card__title">
                <i class="ti ti-calendar-due" aria-hidden="true"></i> Review fällig
            </span>
        </div>
        <div class="card__body--flush">
            <?php if (empty($reviewDue)): ?>
                <div class="empty-state" style="padding:var(--sp-8)">
                    <i class="ti ti-circle-check" aria-hidden="true"></i>
                    <span>Alle Vereinbarungen aktuell</span>
                </div>
            <?php else: ?>
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr><th>Vereinbarung</th><th>Kreis</th><th>Review</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reviewDue as $a): ?>
                                <tr>
                                    <td>
                                        <a href="<?= $base ?>/agreements/<?= $a['id'] ?>">
                                            <?= htmlspecialchars($a['title']) ?>
                                        </a>
                                    </td>
                                    <td class="text-sm"><?= htmlspecialchars($a['circle_name']) ?></td>
                                    <td>
                                        <?php $dueDate = strtotime($a['review_date']); $isPast = $dueDate < time(); ?>
                                        <span class="badge <?= $isPast ? 'badge--expired' : 'badge--review' ?>">
                                            <?= date('d.m.Y', $dueDate) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<!-- 2. Zeile -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--sp-5)">

    <!-- Offene Spannungen -->
    <div class="card">
        <div class="card__header">
            <span class="card__title">
                <i class="ti ti-bolt" aria-hidden="true"></i> Offene Spannungen
            </span>
        </div>
        <div class="card__body--flush">
            <?php if (empty($openTensions)): ?>
                <div class="empty-state" style="padding:var(--sp-8)">
                    <i class="ti ti-mood-happy" aria-hidden="true"></i>
                    <span>Keine offenen Spannungen</span>
                </div>
            <?php else: ?>
                <div class="table-wrap">
                    <table class="table">
                        <thead><tr><th>Spannung</th><th>Kreis</th><th>Von</th></tr></thead>
                        <tbody>
                            <?php foreach ($openTensions as $t): ?>
                                <tr>
                                    <td>
                                        <a href="<?= $base ?>/tensions/<?= $t['id'] ?>">
                                            <?= htmlspecialchars($t['title'] ?: mb_substr($t['description'] ?? '', 0, 50) . '…') ?>
                                        </a>
                                    </td>
                                    <td class="text-sm"><?= htmlspecialchars($t['circle_name']) ?></td>
                                    <td class="text-sm text-muted"><?= htmlspecialchars($t['raised_by_name'] ?? '—') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Rollen ohne Besetzung -->
    <div class="card">
        <div class="card__header">
            <span class="card__title">
                <i class="ti ti-user-off" aria-hidden="true"></i> Rollen ohne Besetzung
            </span>
        </div>
        <div class="card__body--flush">
            <?php if (empty($unfilledRoles)): ?>
                <div class="empty-state" style="padding:var(--sp-8)">
                    <i class="ti ti-circle-check" aria-hidden="true"></i>
                    <span>Alle Rollen besetzt</span>
                </div>
            <?php else: ?>
                <div class="table-wrap">
                    <table class="table">
                        <thead><tr><th>Rolle</th><th>Kreis</th><th>Typ</th></tr></thead>
                        <tbody>
                            <?php foreach ($unfilledRoles as $r): ?>
                                <tr>
                                    <td>
                                        <a href="<?= $base ?>/roles/<?= $r['id'] ?>">
                                            <?= htmlspecialchars($r['name']) ?>
                                        </a>
                                    </td>
                                    <td class="text-sm"><?= htmlspecialchars($r['circle_name']) ?></td>
                                    <td>
                                        <span class="badge badge--<?= htmlspecialchars($r['role_type']) ?>">
                                            <?= htmlspecialchars($r['role_type']) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<!-- 3. Zeile: Delegations-Warnungen -->
<?php if (!empty($unfilledLinks)): ?>
<div class="card">
    <div class="card__header">
        <span class="card__title">
            <i class="ti ti-alert-triangle" aria-hidden="true" style="color:var(--c-warning)"></i>
            Delegationen mit unbesetzten Link-Rollen
        </span>
        <a href="<?= $base ?>/delegations" class="btn btn--ghost btn--sm">Alle Delegationen</a>
    </div>
    <div class="card__body--flush">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Von</th>
                        <th>An</th>
                        <th>Fehlende Rolle</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($unfilledLinks as $d): ?>
                        <tr>
                            <td class="text-sm"><?= htmlspecialchars($d['from_circle_name']) ?></td>
                            <td class="text-sm fw-600"><?= htmlspecialchars($d['to_circle_name']) ?></td>
                            <td>
                                <span class="badge badge--<?= $d['missing_link'] === 'Rep-Link' ? 'review' : 'open' ?>">
                                    <?= htmlspecialchars($d['missing_link']) ?> unbesetzt
                                </span>
                            </td>
                            <td>
                                <a href="<?= $base ?>/delegations/<?= $d['id'] ?>"
                                   class="btn btn--ghost btn--sm">
                                    <i class="ti ti-arrow-right" aria-hidden="true"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>
