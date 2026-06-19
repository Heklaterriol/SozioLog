<?php
$base   = rtrim($config['app']['base_url'], '/');
$isEdit = !empty($delegation['id']);
$action = $isEdit ? $base . '/delegations/' . $delegation['id'] : $base . '/delegations';

function dfErr(array $errors, string $field): string {
    return isset($errors[$field])
        ? '<span class="form-error">' . htmlspecialchars($errors[$field]) . '</span>' : '';
}
function dfCls(array $errors, string $field): string {
    return isset($errors[$field]) ? ' form-input--error' : '';
}
?>

<nav class="breadcrumb">
    <a href="<?= $base ?>/delegations">Delegationen</a>
    <span class="breadcrumb__sep">/</span>
    <span><?= $isEdit ? 'Bearbeiten' : 'Neue Delegation' ?></span>
</nav>

<div class="page-header">
    <h1 class="page-header__title"><?= $isEdit ? 'Delegation bearbeiten' : 'Neue Delegation' ?></h1>
</div>

<div class="card" style="max-width:760px">
    <div class="card__body">
        <form method="post" action="<?= $action ?>" class="form" id="delegation-form">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">

            <div class="form-row">
                <div class="form-field">
                    <label class="form-label form-label--required" for="from_circle">Delegierender Kreis</label>
                    <select id="from_circle" name="from_circle"
                            class="form-select<?= dfCls($errors, 'from_circle') ?>"
                            <?= $isEdit ? 'disabled' : '' ?> required>
                        <option value="">— bitte wählen —</option>
                        <?php foreach ($circles as $c): ?>
                            <option value="<?= $c['id'] ?>"
                                <?= ($delegation['from_circle'] ?? 0) == $c['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($isEdit): ?>
                        <input type="hidden" name="from_circle" value="<?= $delegation['from_circle'] ?>">
                    <?php endif; ?>
                    <?= dfErr($errors, 'from_circle') ?>
                    <span class="form-hint">Dieser Kreis überträgt Autorität.</span>
                </div>

                <div class="form-field">
                    <label class="form-label form-label--required" for="to_circle">Empfangender Kreis</label>
                    <select id="to_circle" name="to_circle"
                            class="form-select<?= dfCls($errors, 'to_circle') ?>"
                            <?= $isEdit ? 'disabled' : '' ?> required>
                        <option value="">— bitte wählen —</option>
                        <?php foreach ($circles as $c): ?>
                            <option value="<?= $c['id'] ?>"
                                <?= ($delegation['to_circle'] ?? 0) == $c['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($isEdit): ?>
                        <input type="hidden" name="to_circle" value="<?= $delegation['to_circle'] ?>">
                    <?php endif; ?>
                    <?= dfErr($errors, 'to_circle') ?>
                    <span class="form-hint">Dieser Kreis empfängt die Autorität.</span>
                </div>
            </div>

            <div class="form-field">
                <label class="form-label" for="description">Beschreibung der Delegation</label>
                <textarea id="description" name="description" class="form-textarea" rows="3"
                          placeholder="Welche Autorität / Domäne wird delegiert?"
                ><?= htmlspecialchars($delegation['description'] ?? '') ?></textarea>
            </div>

            <div class="form-field">
                <label class="form-label" for="notes">Notizen</label>
                <textarea id="notes" name="notes" class="form-textarea" rows="2"
                          placeholder="Interne Hinweise, Hintergründe …"
                ><?= htmlspecialchars($delegation['notes'] ?? '') ?></textarea>
            </div>

            <div class="form-row">
                <div class="form-field">
                    <label class="form-label" for="started_at">Startdatum</label>
                    <input type="date" id="started_at" name="started_at" class="form-input"
                           value="<?= htmlspecialchars($delegation['started_at'] ?? date('Y-m-d')) ?>">
                </div>
                <div class="form-field">
                    <label class="form-label" for="del_status">Status</label>
                    <select id="del_status" name="status" class="form-select">
                        <option value="active"  <?= ($delegation['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Aktiv</option>
                        <option value="ended"   <?= ($delegation['status'] ?? '') === 'ended'  ? 'selected' : '' ?>>Beendet</option>
                    </select>
                </div>
            </div>

            <!-- Rep-Link / Del-Link -->
            <div style="padding:var(--sp-4);background:var(--c-bg);border-radius:var(--r-md);
                        border:1px solid var(--c-border);display:flex;flex-direction:column;gap:var(--sp-4)">
                <div class="fw-600 text-sm">Link-Rollen (optional)</div>
                <p class="text-sm text-muted" style="margin-top:-var(--sp-2)">
                    Wähle die Rollen, die als Rep-Link und Del-Link zwischen den Kreisen fungieren.
                    Die verfügbaren Rollen werden geladen, sobald beide Kreise gewählt sind.
                </p>

                <div class="form-row">
                    <div class="form-field">
                        <label class="form-label" for="rep_link_role">
                            <span style="color:#F59E0B"><i class="ti ti-arrow-up-circle" aria-hidden="true"></i></span>
                            Rep-Link (Unterkreis → Überkreis)
                        </label>
                        <select id="rep_link_role" name="rep_link_role" class="form-select">
                            <option value="">— kein Rep-Link —</option>
                            <?php foreach ($roles as $r): ?>
                                <option value="<?= $r['id'] ?>"
                                    <?= ($delegation['rep_link_role'] ?? 0) == $r['id'] ? 'selected' : '' ?>>
                                    [<?= htmlspecialchars($r['circle_name']) ?>] <?= htmlspecialchars($r['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-field">
                        <label class="form-label" for="del_link_role">
                            <span style="color:#8B5CF6"><i class="ti ti-arrow-down-circle" aria-hidden="true"></i></span>
                            Del-Link (Überkreis → Unterkreis)
                        </label>
                        <select id="del_link_role" name="del_link_role" class="form-select">
                            <option value="">— kein Del-Link —</option>
                            <?php foreach ($roles as $r): ?>
                                <option value="<?= $r['id'] ?>"
                                    <?= ($delegation['del_link_role'] ?? 0) == $r['id'] ? 'selected' : '' ?>>
                                    [<?= htmlspecialchars($r['circle_name']) ?>] <?= htmlspecialchars($r['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn--primary">
                    <i class="ti ti-device-floppy" aria-hidden="true"></i>
                    <?= $isEdit ? 'Änderungen speichern' : 'Delegation anlegen' ?>
                </button>
                <a href="<?= $isEdit ? $base . '/delegations/' . $delegation['id'] : $base . '/delegations' ?>"
                   class="btn btn--secondary">Abbrechen</a>
            </div>
        </form>
    </div>
</div>

<?php if (!$isEdit): ?>
<script>
// Rollen dynamisch nachladen wenn beide Kreise gewählt sind
(function () {
    var fromSel = document.getElementById('from_circle');
    var toSel   = document.getElementById('to_circle');
    var repSel  = document.getElementById('rep_link_role');
    var delSel  = document.getElementById('del_link_role');
    var base    = '<?= $base ?>';

    function loadRoles() {
        var from = fromSel.value;
        var to   = toSel.value;
        if (!from || !to || from === to) return;

        fetch(base + '/api/delegations/roles?from=' + from + '&to=' + to)
            .then(function(r) { return r.json(); })
            .then(function(roles) {
                [repSel, delSel].forEach(function(sel) {
                    var current = sel.value;
                    sel.innerHTML = '<option value="">— keine —</option>';
                    roles.forEach(function(r) {
                        var opt = document.createElement('option');
                        opt.value = r.id;
                        opt.textContent = '[' + r.circle_name + '] ' + r.name;
                        if (r.id == current) opt.selected = true;
                        sel.appendChild(opt);
                    });
                });
            })
            .catch(function() {}); // Stiller Fehler, manuelle Eingabe bleibt
    }

    fromSel.addEventListener('change', loadRoles);
    toSel.addEventListener('change', loadRoles);
})();
</script>
<?php endif; ?>
