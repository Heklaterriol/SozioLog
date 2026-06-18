<?php
$base = rtrim($config['app']['base_url'], '/');
$meetingDate = date('d.m.Y', strtotime($meeting['held_at']));
$meetingTime = date('H:i', strtotime($meeting['held_at']));
$attendeeIds = json_decode($meeting['attendees'] ?? '[]', true) ?? [];

$meetingTypeLabel = [
    'governance'    => 'Governance',
    'operational'   => 'Operativ',
    'election'      => 'Wahl',
    'retrospective' => 'Retrospektive',
    'other'         => 'Sonstiges',
][$meeting['meeting_type']] ?? $meeting['meeting_type'];
?>

<!-- Breadcrumb -->
<nav class="breadcrumb" aria-label="Breadcrumb">
    <a href="<?= $base ?>/">Dashboard</a>
    <span class="breadcrumb__sep" aria-hidden="true">/</span>
    <a href="<?= $base ?>/circles/<?= $meeting['circle_id'] ?>"><?= htmlspecialchars($meeting['circle_name']) ?></a>
    <span class="breadcrumb__sep" aria-hidden="true">/</span>
    <a href="<?= $base ?>/circles/<?= $meeting['circle_id'] ?>/meetings">Meetings</a>
    <span class="breadcrumb__sep" aria-hidden="true">/</span>
    <span><?= $meetingDate ?></span>
</nav>

<div class="page-header">
    <div>
        <h1 class="page-header__title">
            <?= $meetingTypeLabel ?>-Meeting
            <span style="color:var(--c-ink-2);font-weight:400">— <?= $meetingDate ?></span>
        </h1>
        <p class="page-header__sub">
            <?= htmlspecialchars($meeting['circle_name']) ?>
            <?php if ($meeting['location']): ?>
                · <?= htmlspecialchars($meeting['location']) ?>
            <?php endif; ?>
            · <?= $meetingTime ?> Uhr
        </p>
    </div>
    <div class="page-header__actions">
        <?php if (!empty($currentUser['is_admin'])): ?>
            <a href="<?= $base ?>/meetings/<?= $meeting['id'] ?>/edit" class="btn btn--secondary btn--sm">
                <i class="ti ti-edit" aria-hidden="true"></i> Bearbeiten
            </a>
        <?php endif; ?>
        <a href="<?= $base ?>/circles/<?= $meeting['circle_id'] ?>/meetings" class="btn btn--ghost btn--sm">
            <i class="ti ti-arrow-left" aria-hidden="true"></i> Zurück
        </a>
    </div>
</div>

<!-- Meta-Karte -->
<div class="card">
    <div class="card__body">
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:var(--sp-5)">
            <div>
                <div class="text-xs text-muted" style="text-transform:uppercase;letter-spacing:.04em;margin-bottom:4px">Moderator·in</div>
                <div class="text-sm fw-600"><?= htmlspecialchars($meeting['facilitator_name'] ?? '—') ?></div>
            </div>
            <div>
                <div class="text-xs text-muted" style="text-transform:uppercase;letter-spacing:.04em;margin-bottom:4px">Protokoll</div>
                <div class="text-sm fw-600"><?= htmlspecialchars($meeting['secretary_name'] ?? '—') ?></div>
            </div>
            <div>
                <div class="text-xs text-muted" style="text-transform:uppercase;letter-spacing:.04em;margin-bottom:4px">Typ</div>
                <div><span class="badge badge--open"><?= htmlspecialchars($meetingTypeLabel) ?></span></div>
            </div>
            <div>
                <div class="text-xs text-muted" style="text-transform:uppercase;letter-spacing:.04em;margin-bottom:4px">Anwesend</div>
                <div class="text-sm fw-600"><?= count($attendeeIds) ?> Personen</div>
            </div>
        </div>

        <?php if ($meeting['notes']): ?>
            <div style="margin-top:var(--sp-4);padding-top:var(--sp-4);border-top:1px solid var(--c-border)">
                <div class="text-xs text-muted" style="text-transform:uppercase;letter-spacing:.04em;margin-bottom:var(--sp-2)">Check-in / Notizen</div>
                <p class="text-sm" style="white-space:pre-line"><?= htmlspecialchars($meeting['notes']) ?></p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Tabs: Agenda | Vereinbarungen | Spannungen -->
<div class="tabs" role="tablist">
    <button class="tab-btn tab-btn--active" role="tab" aria-controls="tab-agenda"    aria-selected="true"  onclick="switchTab(this,'tab-agenda')">
        <i class="ti ti-list-check" aria-hidden="true"></i>
        Agenda (<?= count($agendaItems) ?>)
    </button>
    <button class="tab-btn" role="tab" aria-controls="tab-agreements" aria-selected="false" onclick="switchTab(this,'tab-agreements')">
        <i class="ti ti-file-text" aria-hidden="true"></i>
        Vereinbarungen (<?= count($agreements) ?>)
    </button>
    <button class="tab-btn" role="tab" aria-controls="tab-tensions"   aria-selected="false" onclick="switchTab(this,'tab-tensions')">
        <i class="ti ti-bolt" aria-hidden="true"></i>
        Offene Spannungen (<?= count($openTensions) ?>)
    </button>
</div>

<!-- TAB: Agenda -->
<div id="tab-agenda" class="tab-panel tab-panel--active" role="tabpanel">
    <div class="card">
        <div class="card__header">
            <span class="card__title"><i class="ti ti-list-check" aria-hidden="true"></i> Agenda-Punkte</span>
        </div>

        <?php if (empty($agendaItems)): ?>
            <div class="empty-state">
                <i class="ti ti-list-details" aria-hidden="true"></i>
                <span class="empty-state__title">Noch keine Agenda-Punkte</span>
                <p class="empty-state__body">Füge unten einen Punkt hinzu.</p>
            </div>
        <?php else: ?>
            <div class="card__body--flush">
                <table class="table">
                    <thead>
                        <tr>
                            <th style="width:32px">#</th>
                            <th>Punkt</th>
                            <th>Typ</th>
                            <th>Ergebnis / Outcome</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($agendaItems as $i => $item): ?>
                            <tr>
                                <td class="text-muted text-sm"><?= $i + 1 ?></td>
                                <td>
                                    <div class="text-sm fw-600"><?= htmlspecialchars($item['title']) ?></div>
                                    <?php if ($item['tension_title']): ?>
                                        <div class="text-xs text-muted" style="margin-top:2px">
                                            Spannung: <?= htmlspecialchars($item['tension_title']) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge badge--open"><?= htmlspecialchars($item['item_type']) ?></span></td>
                                <td class="text-sm" style="max-width:280px">
                                    <?= $item['outcome']
                                        ? htmlspecialchars(mb_substr($item['outcome'], 0, 120)) . (mb_strlen($item['outcome']) > 120 ? '…' : '')
                                        : '<span class="text-muted">—</span>' ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <!-- Neuen Punkt hinzufügen -->
        <div class="card__footer" style="flex-direction:column;align-items:flex-start;gap:var(--sp-3)">
            <div class="fw-600 text-sm">Agenda-Punkt hinzufügen</div>
            <form method="post" action="<?= $base ?>/meetings/<?= $meeting['id'] ?>/agenda"
                  style="display:flex;gap:var(--sp-3);flex-wrap:wrap;width:100%">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">

                <input type="text" name="title" placeholder="Titel des Punktes"
                       class="form-input" style="flex:1;min-width:180px" required>

                <select name="tension_id" class="form-select" style="width:200px">
                    <option value="">— Spannung verknüpfen —</option>
                    <?php foreach ($openTensions as $t): ?>
                        <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['title'] ?: mb_substr($t['description'] ?? '', 0, 40)) ?></option>
                    <?php endforeach; ?>
                </select>

                <select name="item_type" class="form-select" style="width:140px">
                    <option value="tension">Spannung</option>
                    <option value="agreement">Vereinbarung</option>
                    <option value="election">Wahl</option>
                    <option value="checkin">Check-in</option>
                    <option value="other">Sonstiges</option>
                </select>

                <button type="submit" class="btn btn--primary">
                    <i class="ti ti-plus" aria-hidden="true"></i> Hinzufügen
                </button>
            </form>
        </div>
    </div>
</div>

<!-- TAB: Vereinbarungen -->
<div id="tab-agreements" class="tab-panel" role="tabpanel">
    <div class="card">
        <div class="card__header">
            <span class="card__title"><i class="ti ti-file-text" aria-hidden="true"></i> Im Meeting getroffene Vereinbarungen</span>
            <a href="<?= $base ?>/agreements/new?circle_id=<?= $meeting['circle_id'] ?>&meeting_id=<?= $meeting['id'] ?>"
               class="btn btn--primary btn--sm">
                <i class="ti ti-plus" aria-hidden="true"></i> Vereinbarung anlegen
            </a>
        </div>
        <?php if (empty($agreements)): ?>
            <div class="empty-state">
                <i class="ti ti-file-off" aria-hidden="true"></i>
                <span>Noch keine Vereinbarungen für dieses Meeting</span>
            </div>
        <?php else: ?>
            <div class="card__body--flush">
                <table class="table">
                    <thead><tr><th>Titel</th><th>Status</th><th>Review</th></tr></thead>
                    <tbody>
                        <?php foreach ($agreements as $a): ?>
                            <tr>
                                <td>
                                    <a href="<?= $base ?>/agreements/<?= $a['id'] ?>">
                                        <?= htmlspecialchars($a['title']) ?>
                                    </a>
                                </td>
                                <td><span class="badge badge--<?= htmlspecialchars($a['status']) ?>"><?= htmlspecialchars($a['status']) ?></span></td>
                                <td class="text-sm"><?= $a['review_date'] ? date('d.m.Y', strtotime($a['review_date'])) : '—' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- TAB: Offene Spannungen -->
<div id="tab-tensions" class="tab-panel" role="tabpanel">
    <div class="card">
        <div class="card__header">
            <span class="card__title"><i class="ti ti-bolt" aria-hidden="true"></i> Offene Spannungen im Kreis</span>
            <a href="<?= $base ?>/tensions/new?circle_id=<?= $meeting['circle_id'] ?>"
               class="btn btn--secondary btn--sm">
                <i class="ti ti-plus" aria-hidden="true"></i> Spannung einreichen
            </a>
        </div>
        <?php if (empty($openTensions)): ?>
            <div class="empty-state">
                <i class="ti ti-mood-happy" aria-hidden="true"></i>
                <span>Keine offenen Spannungen</span>
            </div>
        <?php else: ?>
            <div class="card__body--flush">
                <table class="table">
                    <thead><tr><th>Spannung</th><th>Eingereicht von</th><th>Erstellt</th></tr></thead>
                    <tbody>
                        <?php foreach ($openTensions as $t): ?>
                            <tr>
                                <td>
                                    <a href="<?= $base ?>/tensions/<?= $t['id'] ?>">
                                        <?= htmlspecialchars($t['title'] ?: mb_substr($t['description'] ?? '', 0, 60)) ?>
                                    </a>
                                </td>
                                <td class="text-sm"><?= htmlspecialchars($t['raised_by_name'] ?? '—') ?></td>
                                <td class="text-sm text-muted"><?= date('d.m.Y', strtotime($t['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function switchTab(btn, panelId) {
    document.querySelectorAll('.tab-btn').forEach(b => {
        b.classList.remove('tab-btn--active');
        b.setAttribute('aria-selected', 'false');
    });
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('tab-panel--active'));
    btn.classList.add('tab-btn--active');
    btn.setAttribute('aria-selected', 'true');
    document.getElementById(panelId).classList.add('tab-panel--active');
}
</script>
