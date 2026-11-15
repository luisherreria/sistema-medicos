/**
 * Máscara YY/MM para campos Periodo.
 * Se escribe 2302 y aparece 23/02; la barra no se tipea.
 */
(function () {
    'use strict';

    function esPlaceholder(v) {
        v = String(v || '').replace(/\s/g, '').toUpperCase();
        return v === '' || v === 'AA/MM' || v === 'YY/MM' || v === 'AAMM';
    }

    function soloDigitos(v) {
        return String(v || '').replace(/\D/g, '').substring(0, 4);
    }

    function formatear(v) {
        var d = soloDigitos(v);
        if (d.length <= 2) {
            return d;
        }
        return d.substring(0, 2) + '/' + d.substring(2);
    }

    function aplicar(el) {
        if (!el || el.type === 'hidden' || el.readOnly) {
            return;
        }
        var next = formatear(el.value);
        if (el.value !== next) {
            el.value = next;
        }
        if (el.maxLength !== 5) {
            el.maxLength = 5;
        }
    }

    function onFocus(e) {
        var el = e.target;
        if (esPlaceholder(el.value)) {
            el.value = '';
        }
        try {
            el.select();
        } catch (err) {}
    }

    function onInput(e) {
        var el = e.target;
        var next = formatear(el.value);
        el.value = next;
        try {
            var pos = next.length;
            el.setSelectionRange(pos, pos);
        } catch (err) {}
    }

    function onKeyDown(e) {
        var k = e.key;
        if (k === 'Tab' || k === 'Enter' || k === 'Escape'
            || k === 'Backspace' || k === 'Delete'
            || k === 'ArrowLeft' || k === 'ArrowRight'
            || k === 'Home' || k === 'End') {
            return;
        }
        if (e.ctrlKey || e.metaKey || e.altKey) {
            return;
        }
        if (k && k.length === 1 && k >= '0' && k <= '9') {
            return;
        }
        if (k === '/' || k === '-') {
            e.preventDefault();
            return;
        }
        if (k && k.length === 1) {
            e.preventDefault();
        }
    }

    function esCampoPeriodo(el) {
        if (!el || !el.tagName || el.tagName.toLowerCase() !== 'input') {
            return false;
        }
        if (el.type === 'hidden') {
            return false;
        }
        var name = (el.name || '').toLowerCase();
        var id = (el.id || '').toLowerCase();
        if ((el.className || '').indexOf('periodo-yymm') !== -1) {
            return true;
        }
        if (name === 'periodo' || name === 'tperi' || name === 'tperih') {
            return true;
        }
        if (id === 'af-periodo' || id === 'pad-periodo') {
            return true;
        }
        if (id.indexOf('periodo') !== -1 || id.indexOf('tperi') !== -1) {
            return true;
        }
        return false;
    }

    function bind(el) {
        if (!esCampoPeriodo(el) || el.getAttribute('data-periodo-mask') === '1') {
            return;
        }
        el.setAttribute('data-periodo-mask', '1');
        el.setAttribute('inputmode', 'numeric');
        el.setAttribute('autocomplete', 'off');
        if (!el.getAttribute('placeholder')) {
            el.setAttribute('placeholder', 'YY/MM');
        }
        el.maxLength = 5;
        if (!esPlaceholder(el.value)) {
            aplicar(el);
        }
        el.addEventListener('focus', onFocus);
        el.addEventListener('input', onInput);
        el.addEventListener('keydown', onKeyDown);
        el.addEventListener('blur', function () {
            aplicar(el);
        });
    }

    function scan(root) {
        var list = (root || document).querySelectorAll('input');
        var i = 0;
        for (i = 0; i < list.length; i++) {
            bind(list[i]);
        }
    }

    function init() {
        scan(document);
        if (typeof MutationObserver === 'undefined') {
            return;
        }
        var obs = new MutationObserver(function (muts) {
            var m = 0;
            for (m = 0; m < muts.length; m++) {
                var nodes = muts[m].addedNodes;
                var n = 0;
                for (n = 0; n < nodes.length; n++) {
                    var node = nodes[n];
                    if (!node || node.nodeType !== 1) {
                        continue;
                    }
                    if (node.tagName && node.tagName.toLowerCase() === 'input') {
                        bind(node);
                    }
                    if (node.querySelectorAll) {
                        scan(node);
                    }
                }
            }
        });
        obs.observe(document.body, { childList: true, subtree: true });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
