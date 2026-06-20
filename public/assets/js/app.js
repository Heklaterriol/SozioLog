/**
 * Soziokratisches Logbuch — Frontend JS
 */
'use strict';

// ----------------------------------------------------------------
//  Flash-Nachrichten nach 5 Sek. automatisch ausblenden
// ----------------------------------------------------------------
document.querySelectorAll('.flash').forEach(function (el) {
    setTimeout(function () {
        el.style.transition = 'opacity .4s';
        el.style.opacity    = '0';
        setTimeout(function () { el.remove(); }, 400);
    }, 5000);
});

// ----------------------------------------------------------------
//  Tab-Umschaltung (wird auch direkt in Templates genutzt,
//  diese globale Funktion dient als Fallback)
// ----------------------------------------------------------------
window.switchTab = function (btn, panelId) {
    var container = btn.closest('[role="tablist"]') || document.body;

    // Alle Tabs im selben Tablist deaktivieren
    container.querySelectorAll('.tab-btn').forEach(function (b) {
        b.classList.remove('tab-btn--active');
        b.setAttribute('aria-selected', 'false');
    });

    // Alle Panels verbergen (suche nächstes Geschwisterelement)
    var sibling = container.nextElementSibling;
    while (sibling && sibling.classList.contains('tab-panel')) {
        sibling.classList.remove('tab-panel--active');
        sibling = sibling.nextElementSibling;
    }

    btn.classList.add('tab-btn--active');
    btn.setAttribute('aria-selected', 'true');

    var panel = document.getElementById(panelId);
    if (panel) panel.classList.add('tab-panel--active');
};

// ----------------------------------------------------------------
//  Accountabilities-Feld: Listeneinträge dynamisch verwalten
//  Für Rollen-Formular: data-dynamic-list
// ----------------------------------------------------------------
document.querySelectorAll('[data-dynamic-list]').forEach(function (wrapper) {
    var input  = wrapper.querySelector('[data-list-input]');
    var list   = wrapper.querySelector('[data-list-items]');
    var addBtn = wrapper.querySelector('[data-list-add]');
    var name   = wrapper.dataset.dynamicList;

    if (!input || !list || !addBtn) return;

    function addItem(value) {
        value = value.trim();
        if (!value) return;

        var li   = document.createElement('li');
        li.style.cssText = 'display:flex;gap:.5rem;align-items:center;padding:.3rem 0;font-size:.875rem';

        var hidden = document.createElement('input');
        hidden.type  = 'hidden';
        hidden.name  = name + '[]';
        hidden.value = value;

        var text = document.createElement('span');
        text.style.flex = '1';
        text.textContent = value;

        var del = document.createElement('button');
        del.type      = 'button';
        del.textContent = '×';
        del.title       = 'Entfernen';
        del.style.cssText = 'background:none;border:none;cursor:pointer;color:var(--c-error);font-size:1.1rem;line-height:1;padding:0 .2rem';
        del.addEventListener('click', function () { li.remove(); });

        li.appendChild(hidden);
        li.appendChild(text);
        li.appendChild(del);
        list.appendChild(li);
        input.value = '';
    }

    addBtn.addEventListener('click', function () { addItem(input.value); });
    input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') { e.preventDefault(); addItem(input.value); }
    });
});

// ----------------------------------------------------------------
//  Automatische Textarea-Höhe
// ----------------------------------------------------------------
document.querySelectorAll('textarea.form-textarea').forEach(function (ta) {
    function resize() {
        ta.style.height = 'auto';
        ta.style.height = ta.scrollHeight + 'px';
    }
    ta.addEventListener('input', resize);
});
