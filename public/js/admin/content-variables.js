/*
 * {{variable}} autocomplete for admin text fields (FAQ question/answer).
 * Fields opt in with data-tt-variables='[{"k":"prom.price.3","l":"Парти – цена","v":"130 €","g":"Цени: …"}]'
 * (App\Support\ContentVariables::forEditor()). Typing "{{" opens a list that can be
 * filtered by typing and by group; Enter/Tab/click inserts "{{key}}".
 * Event delegation on document, so it survives Livewire re-renders.
 */
(function () {
    'use strict';

    var SELECTOR = 'textarea[data-tt-variables], input[data-tt-variables]';
    var menu = null;
    var state = null; // { el, start, items, filtered, index, group }

    function parseItems(el) {
        try {
            return JSON.parse(el.getAttribute('data-tt-variables') || '[]');
        } catch (e) {
            return [];
        }
    }

    /** "{{" before the caret that is not closed yet -> { start, query }. */
    function context(el) {
        var pos = el.selectionStart;
        if (pos === null || pos !== el.selectionEnd) return null;
        var before = el.value.slice(0, pos);
        var open = before.lastIndexOf('{{');
        if (open < 0) return null;
        var typed = before.slice(open + 2);
        if (typed.indexOf('}}') !== -1 || /[\n{]/.test(typed) || typed.length > 40) return null;
        return { start: open, query: typed.trim().toLowerCase() };
    }

    function ensureMenu() {
        if (menu) return menu;
        menu = document.createElement('div');
        menu.id = 'tt-var-menu';
        menu.className = 'tt-var-menu';
        menu.setAttribute('role', 'listbox');
        menu.hidden = true;
        // Keep focus in the field while clicking inside the menu.
        menu.addEventListener('mousedown', function (e) {
            e.preventDefault();
            var chip = e.target.closest('[data-group]');
            if (chip && state) {
                state.group = chip.getAttribute('data-group');
                state.index = 0;
                render();
                return;
            }
            var option = e.target.closest('[data-index]');
            if (option && state) insert(state.filtered[+option.getAttribute('data-index')]);
        });
        document.body.appendChild(menu);
        return menu;
    }

    function escapeHtml(s) {
        return String(s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    function filter() {
        var q = state.query;
        state.filtered = state.items.filter(function (item) {
            if (state.group && item.g !== state.group) return false;
            if (!q) return true;
            return (item.k + ' ' + item.l + ' ' + item.g + ' ' + item.v).toLowerCase().indexOf(q) !== -1;
        });
        if (state.index >= state.filtered.length) state.index = Math.max(0, state.filtered.length - 1);
    }

    function render() {
        filter();
        var m = ensureMenu();
        var groups = [];
        state.items.forEach(function (item) {
            if (groups.indexOf(item.g) === -1) groups.push(item.g);
        });

        var html = '<div class="tt-var-menu__groups">'
            + '<button type="button" data-group="" class="' + (state.group ? '' : 'is-active') + '">Всички</button>'
            + groups.map(function (g) {
                return '<button type="button" data-group="' + escapeHtml(g) + '" class="' + (state.group === g ? 'is-active' : '') + '">' + escapeHtml(g) + '</button>';
            }).join('')
            + '</div><div class="tt-var-menu__list">';

        if (!state.filtered.length) {
            html += '<div class="tt-var-menu__empty">Няма съвпадения за „' + escapeHtml(state.query) + '“</div>';
        }

        var lastGroup = null;
        state.filtered.slice(0, 200).forEach(function (item, i) {
            if (!state.group && item.g !== lastGroup) {
                html += '<div class="tt-var-menu__heading">' + escapeHtml(item.g) + '</div>';
                lastGroup = item.g;
            }
            html += '<div class="tt-var-menu__item' + (i === state.index ? ' is-active' : '') + '" role="option" id="tt-var-opt-' + i + '" data-index="' + i + '">'
                + '<span class="tt-var-menu__label">' + escapeHtml(item.l) + '</span>'
                + '<span class="tt-var-menu__value">' + escapeHtml(item.v) + '</span>'
                + '<code class="tt-var-menu__key">{{' + escapeHtml(item.k) + '}}</code>'
                + '</div>';
        });
        html += '</div><div class="tt-var-menu__hint">↑↓ избор · Enter вмъкни · Esc затвори</div>';

        m.innerHTML = html;
        m.hidden = false;
        position();

        var active = m.querySelector('.tt-var-menu__item.is-active');
        if (active) {
            active.scrollIntoView({ block: 'nearest' });
            state.el.setAttribute('aria-activedescendant', active.id);
        }
    }

    function position() {
        if (!menu || !state) return;
        var r = state.el.getBoundingClientRect();
        var width = Math.min(Math.max(r.width, 320), 560, window.innerWidth - 16);
        var left = Math.min(Math.max(8, r.left), window.innerWidth - width - 8);
        menu.style.width = width + 'px';
        menu.style.left = left + 'px';
        var below = window.innerHeight - r.bottom;
        if (below < 260 && r.top > below) {
            menu.style.top = '';
            menu.style.bottom = (window.innerHeight - r.top + 4) + 'px';
        } else {
            menu.style.bottom = '';
            menu.style.top = (r.bottom + 4) + 'px';
        }
    }

    function close() {
        if (menu) menu.hidden = true;
        if (state && state.el) state.el.removeAttribute('aria-activedescendant');
        state = null;
    }

    function insert(item) {
        if (!item || !state) return;
        var el = state.el;
        var pos = el.selectionStart;
        var after = el.value.slice(pos);
        var token = '{{' + item.k + '}}';
        // "{{" typed + an auto-closed "}}" right after the caret: replace both.
        if (after.slice(0, 2) === '}}') after = after.slice(2);
        el.value = el.value.slice(0, state.start) + token + after;
        var caret = state.start + token.length;
        el.setSelectionRange(caret, caret);
        close();
        el.dispatchEvent(new Event('input', { bubbles: true }));
    }

    function update(el) {
        var ctx = context(el);
        if (!ctx) {
            if (state && state.el === el) close();
            return;
        }
        if (!state || state.el !== el) {
            state = { el: el, items: parseItems(el), index: 0, group: '' };
        }
        if (state.query !== ctx.query) state.index = 0;
        state.start = ctx.start;
        state.query = ctx.query;
        render();
    }

    document.addEventListener('input', function (e) {
        if (e.target.matches && e.target.matches(SELECTOR)) update(e.target);
    }, true);

    document.addEventListener('click', function (e) {
        if (e.target.matches && e.target.matches(SELECTOR)) update(e.target);
        else if (menu && !menu.contains(e.target)) close();
    }, true);

    document.addEventListener('keydown', function (e) {
        if (!state || !menu || menu.hidden || e.target !== state.el) return;
        if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
            e.preventDefault();
            var n = state.filtered.length;
            if (n) state.index = (state.index + (e.key === 'ArrowDown' ? 1 : n - 1)) % n;
            render();
        } else if ((e.key === 'Enter' || e.key === 'Tab') && state.filtered.length) {
            e.preventDefault();
            insert(state.filtered[state.index]);
        } else if (e.key === 'Escape') {
            e.preventDefault();
            e.stopPropagation(); // do not close the Filament modal / slide-over
            close();
        }
    }, true);

    document.addEventListener('focusout', function (e) {
        if (state && e.target === state.el) setTimeout(function () {
            if (state && document.activeElement !== state.el) close();
        }, 150);
    }, true);

    window.addEventListener('resize', position);
    window.addEventListener('scroll', position, true);
})();
