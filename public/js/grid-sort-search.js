/**
 * Orden ASC/DESC por encabezado (triángulo) + buscar en cualquier fila.
 * Uso: class="grid-sort-search" en la <table>.
 *   data-gss-search="#id"  → input de búsqueda existente
 */
(function () {
    'use strict';

    var CSS = [
        '.gss-search-wrap{display:flex;align-items:center;gap:6px;min-width:220px;max-width:340px;}',
        '.gss-search-wrap .form-control{min-width:180px;}',
        'table.grid-sort-search thead th.gss-sortable{cursor:pointer;user-select:none;white-space:nowrap;}',
        'table.grid-sort-search thead th.gss-sortable .gss-tri{display:inline-block;margin-left:4px;font-size:0.62em;line-height:1;opacity:0.4;vertical-align:middle;}',
        'table.grid-sort-search thead th.gss-asc .gss-tri,',
        'table.grid-sort-search thead th.gss-desc .gss-tri{opacity:1;}',
        'table.grid-sort-search thead th.gss-sortable .gss-tri::before{content:"\\25B2\\25BC";letter-spacing:-1px;}',
        'table.grid-sort-search thead th.gss-asc .gss-tri::before{content:"\\25B2";}',
        'table.grid-sort-search thead th.gss-desc .gss-tri::before{content:"\\25BC";}'
    ].join('');

    function injectCss() {
        if (document.getElementById('gss-css')) return;
        var st = document.createElement('style');
        st.id = 'gss-css';
        st.appendChild(document.createTextNode(CSS));
        document.head.appendChild(st);
    }

    function stateOf(table) {
        if (!table._gss) {
            table._gss = { col: -1, dir: 'ASC', q: '' };
        }
        return table._gss;
    }

    function norm(s) {
        s = String(s || '').toLowerCase();
        try {
            s = s.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
        } catch (e) {}
        return s;
    }

    function parseVal(s) {
        s = String(s == null ? '' : s).replace(/\s+/g, ' ').trim();
        if (s === '') return '';
        var dmy = s.match(/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/);
        if (dmy) {
            return dmy[3] + ('0' + dmy[2]).slice(-2) + ('0' + dmy[1]).slice(-2);
        }
        var ymd = s.match(/^(\d{4})-(\d{2})-(\d{2})/);
        if (ymd) return ymd[1] + ymd[2] + ymd[3];
        var n = s.replace(/\s/g, '');
        if (/^-?\d+([.,]\d+)?$/.test(n)) {
            return parseFloat(n.replace(',', '.'));
        }
        if (/^-?\d{1,3}(\.\d{3})+(,\d+)?$/.test(n)) {
            return parseFloat(n.replace(/\./g, '').replace(',', '.'));
        }
        return norm(s);
    }

    function cellVal(td) {
        if (!td) return '';
        var raw = td.getAttribute('data-sort-val');
        if (raw !== null && raw !== '') return parseVal(raw);
        return parseVal(td.textContent);
    }

    function cmp(a, b, dir) {
        var va = cellVal(a);
        var vb = cellVal(b);
        var na = typeof va === 'number';
        var nb = typeof vb === 'number';
        var r = 0;
        if (va === '' && vb !== '') r = 1;
        else if (va !== '' && vb === '') r = -1;
        else if (na && nb) r = va - vb;
        else if (na) r = -1;
        else if (nb) r = 1;
        else if (va < vb) r = -1;
        else if (va > vb) r = 1;
        return dir === 'DESC' ? -r : r;
    }

    function markHeaders(table) {
        var ths = table.querySelectorAll('thead th');
        var st = stateOf(table);
        var i;
        for (i = 0; i < ths.length; i++) {
            var th = ths[i];
            if (th.classList.contains('gss-skip')) continue;
            th.classList.add('gss-sortable');
            if (!th.querySelector('.gss-tri')) {
                var sp = document.createElement('span');
                sp.className = 'gss-tri';
                sp.setAttribute('aria-hidden', 'true');
                th.appendChild(sp);
            }
            th.classList.remove('gss-asc', 'gss-desc');
            if (i === st.col) {
                th.classList.add(st.dir === 'DESC' ? 'gss-desc' : 'gss-asc');
            }
        }
    }

    function apply(table) {
        var st = stateOf(table);
        var selIn = table.getAttribute('data-gss-search');
        if (selIn) {
            var inp = document.querySelector(selIn);
            if (inp) st.q = inp.value;
        }
        var tbody = table.tBodies[0];
        if (!tbody) return;
        var rows = [];
        var i;
        for (i = 0; i < tbody.rows.length; i++) {
            rows.push(tbody.rows[i]);
        }
        if (st.col >= 0) {
            rows.sort(function (ra, rb) {
                return cmp(ra.cells[st.col], rb.cells[st.col], st.dir);
            });
            for (i = 0; i < rows.length; i++) {
                tbody.appendChild(rows[i]);
            }
        }
        var q = norm(st.q);
        var vis = 0;
        for (i = 0; i < tbody.rows.length; i++) {
            var tr = tbody.rows[i];
            var ok = true;
            if (q !== '') {
                ok = norm(tr.textContent).indexOf(q) !== -1;
            }
            tr.style.display = ok ? '' : 'none';
            if (ok) vis++;
        }
        var badge = table.getAttribute('data-gss-badge');
        if (badge) {
            var el = document.querySelector(badge);
            if (el) {
                var total = tbody.rows.length;
                el.innerHTML = '<i class="fa-solid fa-list-ol me-1"></i>'
                    + (q !== '' ? (vis + ' / ' + total) : String(total))
                    + ' filas';
            }
        }
        markHeaders(table);
    }

    function bindSearch(table) {
        var sel = table.getAttribute('data-gss-search');
        var input = sel ? document.querySelector(sel) : null;
        if (!input) {
            var host = table.parentNode;
            input = document.createElement('input');
            input.type = 'search';
            input.className = 'form-control form-control-sm';
            input.placeholder = 'Buscar en cualquier fila...';
            var wrap = document.createElement('div');
            wrap.className = 'gss-search-wrap mb-2';
            wrap.innerHTML = '<i class="fa-solid fa-magnifying-glass text-muted"></i>';
            wrap.appendChild(input);
            if (host && host.parentNode) {
                host.parentNode.insertBefore(wrap, host);
            }
        }
        if (input.getAttribute('data-gss-bound') === '1') return;
        input.setAttribute('data-gss-bound', '1');
        if (!input.placeholder) {
            input.placeholder = 'Buscar en cualquier fila...';
        }
        input.addEventListener('input', function () {
            stateOf(table).q = input.value;
            apply(table);
        });
    }

    function bindHeaders(table) {
        if (table.getAttribute('data-gss-head') === '1') return;
        table.setAttribute('data-gss-head', '1');
        table.addEventListener('click', function (ev) {
            var th = ev.target;
            while (th && th.tagName && th.tagName.toLowerCase() !== 'th') {
                th = th.parentNode;
                if (th === table) return;
            }
            if (!th || typeof th.cellIndex !== 'number') return;
            if (th.classList.contains('gss-skip')) return;
            if (th.parentNode.parentNode.tagName.toLowerCase() !== 'thead'
                && !(th.parentNode.tagName.toLowerCase() === 'tr'
                    && th.parentNode.parentNode.tagName.toLowerCase() === 'thead')) {
                return;
            }
            var st = stateOf(table);
            var idx = th.cellIndex;
            if (st.col === idx) {
                st.dir = st.dir === 'ASC' ? 'DESC' : 'ASC';
            } else {
                st.col = idx;
                st.dir = 'ASC';
            }
            apply(table);
        });
    }

    function enhance(table) {
        if (!table || table.getAttribute('data-gss') === '1') {
            if (table) apply(table);
            return;
        }
        table.setAttribute('data-gss', '1');
        table.classList.add('grid-sort-search');
        injectCss();
        markHeaders(table);
        bindHeaders(table);
        bindSearch(table);
        apply(table);
    }

    function scan() {
        injectCss();
        var list = document.querySelectorAll('table.grid-sort-search');
        var i;
        for (i = 0; i < list.length; i++) {
            enhance(list[i]);
        }
    }

    window.GridSortSearch = {
        enhance: enhance,
        refresh: apply,
        clearSort: function (table) {
            if (!table) return;
            var st = stateOf(table);
            st.col = -1;
            st.dir = 'ASC';
            apply(table);
        },
        scan: scan
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', scan);
    } else {
        scan();
    }
})();
