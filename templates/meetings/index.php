<?php
$base = rtrim($config['app']['base_url'], '/');
$typeLabels = [
    'governance'    => 'Governance',
    'operational'   => 'Operativ',
    'election'      => 'Wahl',
    'retrospective' => 'Retrospektive',
    'other'         => 'Sonstiges',
];
?>

<nav class="breadcrumb">
    <a href="<?= $base ?>/circles">Kreise</a>
    <span class="breadcrumb__sep">/</span>
    <a href="<?= $base ?>/circles/<?= $circle['id'] ?>"><?= htmlspecialchars($circle['name']) ?></a>
    <span class="breadcrumb__sep">/</span>
    <span>Meetings</span>
</nav>

<div class="page-header">
    <div>
        <h1 class="page-header__title">Meetings</h1>
        <p class="page-header__sub"><?= htmlspecialchars($circle['name']) ?></p>
    </div>
    <div class="page-header__actions">
        <a href="<?= $base ?>/meetings/new?circle_id=<?= $circle['id'] ?>" class="btn btn--primary">
            <i class="ti ti-plus" aria-hidden="true"></i> Meeting anlegen
        </a>
    </div>
</div>

<!-- Typ-Filter -->
<div class="tabs">
    <?php
    $filterOptions = ['' => 'Alle', 'governance' => 'Governance', 'operational' => 'Operativ', 'election' => 'Wahl', 'retrospective' => 'Retrospektive'];
    foreach ($filterOptions as $key => $label): ?>
        <a href="?type=<?= $key ?>"
           class="tab-btn <?= ($filter ?? '') === $key ? 'tab-btn--active' : '' ?>"
           style="text-decoration:none"><?= $label ?></a>
    <?php endforeach; ?>
</div>

<div class="card">
    <div class="card__body--flush">
        <?php if (empty($meetings)): ?>
            <div class="empty-state">
                <i class="ti ti-calendar-off" aria-hidden="true"></i>
                <span class="empty-state__title">Keine Meetings</span>
                <a href="<?= $base ?>/meetings/new?circle_id=<?= $circle['id'] ?>" class="btn btn--primary" style="margin-top:var(--sp-2)">
                    <i class="ti ti-plus" aria-hidden="true"></i> Erstes Meeting anlegen
                </a>
            </div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Datum & Uhrzeit</th>
                            <th>Typ</th>
                            <th>Ort</th>
                            <th>Moderator·in</th>
                            <th>Protokoll</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($meetings as $m): ?>
                            <tr>
                                <td>
                                    <a href="<?= $base ?>/meetings/<?= $m['id'] ?>" class="fw-600">
                                        <?= date('d.m.Y', strtotime($m['held_at'])) ?>
                                    </a>
                                    <div class="text-xs text-muted"><?= date('H:i', strtotime($m['held_at'])) ?> Uhr</div>
                                </td>
                                <td>
                                    <span class="badge badge--open">
                                        <?= htmlspecialchars($typeLabels[$m['meeting_type']] ?? $m['meeting_type']) ?>
                                    </span>
                                </td>
                                <td class="text-sm"><?= htmlspecialchars($m['location'] ?? '—') ?></td>
                                <td class="text-sm"><?= htmlspecialchars($m['facilitator_name'] ?? '—') ?></td>
                                <td class="text-sm"><?= htmlspecialchars($m['secretary_name'] ?? '—') ?></td>
                                <td>
                                    <div class="table__actions">
                                        <a href="<?= $base ?>/meetings/<?= $m['id'] ?>" class="btn btn--ghost btn--sm" title="Protokoll">
                                            <i class="ti ti-eye" aria-hidden="true"></i>
                                        </a>
                                        <a href="<?= $base ?>/meetings/<?= $m['id'] ?>/edit" class="btn btn--ghost btn--sm" title="Bearbeiten">
                                            <i class="ti ti-edit" aria-hidden="true"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
