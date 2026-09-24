<?php
/**
 * views/prestadores/_modal_excl_practicas.php
 * ─────────────────────────────────────────────────────────────────────────
 * Modal: Exclusión de Prácticas del Prestador por Obra Social
 * Gestiona la tabla `exclu_prac` (o equivalente) que define qué prácticas
 * del prestador están excluidas para determinadas obras sociales.
 * ─────────────────────────────────────────────────────────────────────────
 */
$csrfToken = $_SESSION['csrf_token'] ?? '';
?>

<!-- ╔═════════════════════════════════════════════════════════════════════╗
     ║  MODAL: EXCLUSIÓN DE PRÁCTICAS                                     ║
     ╚═════════════════════════════════════════════════════════════════════╝ -->
<div class="modal fade" id="modalExclPracticas" tabindex="-1"
     aria-labelledby="modalExclPracticasLabel" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="false">

    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content" style="border-radius:12px; overflow:hidden;">

        <!-- Encabezado -->
        <div class="modal-header py-2 px-4"
             style="background:linear-gradient(90deg,#e65100,#f57c00); color:#fff; border-bottom:none;">
            <div class="d-flex align-items-center gap-3">
                <div style="width:38px;height:38px;border-radius:50%;
                            background:rgba(255,255,255,.2);
                            display:flex;align-items:center;justify-content:center;">
                    <i class="fa-solid fa-ban"></i>
                </div>
                <div>
                    <h6 class="modal-title mb-0 fw-bold" id="modalExclPracticasLabel">Exclusión de Prácticas</h6>
                    <small style="opacity:.85; font-size:0.73rem;">
                        Prestador: <span id="exclprac-modal-nombre" class="fw-semibold">—</span>
                        &nbsp;|&nbsp; Código: <code id="exclprac-modal-codigo" style="color:#ffcc80; font-size:.8rem;">—</code>
                    </small>
                </div>
            </div>
            <button type="button" class="btn-close btn-close-white"
                    data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>

        <!-- Cuerpo -->
        <div class="modal-body p-0">

            <!-- Selector de OS + búsqueda -->
            <div class="d-flex align-items-center gap-2 flex-wrap px-3 py-2"
                 style="background:#fff8f0; border-bottom:1px solid #ffe0c0;">
                <div style="min-width:200px; max-width:300px;">
                    <label class="form-label mb-0 me-1" style="font-size:.75rem; font-weight:600;">Obra Social:</label>
                    <select id="exclprac-select-os" class="form-select form-select-sm d-inline-block" style="width:220px; font-size:.78rem;">
                        <option value="">— Todas —</option>
                    </select>
                </div>
                <div class="input-group input-group-sm" style="max-width:240px;">
                    <span class="input-group-text bg-white"><i class="fa-solid fa-search text-muted" style="font-size:.75rem;"></i></span>
                    <input type="text" id="exclprac-busqueda" class="form-control" placeholder="Buscar práctica…" autocomplete="off" style="font-size:.8rem;">
                </div>
                <button id="exclprac-btn-buscar" class="btn btn-sm btn-warning text-white" style="font-size:.78rem;">
                    <i class="fa-solid fa-search me-1"></i>Buscar
                </button>
                <span id="exclprac-total-badge" class="badge bg-warning text-dark ms-auto" style="font-size:.72rem;">—</span>
            </div>

            <!-- Spinner -->
            <div id="exclprac-loading" class="text-center py-5">
                <div class="spinner-border text-warning spinner-border-sm" role="status"></div>
                <p class="text-muted mt-2 mb-0" style="font-size:.82rem;">Cargando exclusiones…</p>
            </div>

            <!-- Tabla -->
            <div id="exclprac-table-wrap" style="display:none;">
                <div class="table-responsive">
                    <table class="table table-hover table-bordered mb-0" style="font-size:.76rem; white-space:nowrap;">
                        <thead>
                            <tr style="background:#fff3e0; font-size:.72rem;">
                                <th class="px-3 py-2" style="width:90px;">Código</th>
                                <th class="px-3 py-2">Nombre Práctica</th>
                                <th class="px-3 py-2" style="width:120px;">Grupo</th>
                                <th class="px-3 py-2" style="width:100px;">Obra Social</th>
                                <th class="px-3 py-2 text-center" style="width:100px;">Estado</th>
                                <th class="px-3 py-2 text-center" style="width:80px;">Acción</th>
                            </tr>
                        </thead>
                        <tbody id="exclprac-tbody"></tbody>
                    </table>
                </div>
                <div id="exclprac-empty" class="text-center py-5" style="display:none;">
                    <i class="fa-solid fa-check-circle fa-2x text-success mb-2 d-block" style="opacity:.4;"></i>
                    <p class="text-muted mb-0" style="font-size:.85rem;">No hay prácticas excluidas para los filtros seleccionados.</p>
                </div>
            </div>

            <!-- Paginación -->
            <div id="exclprac-footer"
                 class="d-flex align-items-center justify-content-between px-3 py-2 border-top"
                 style="background:#fff8f0; font-size:.78rem; display:none !important;">
                <span id="exclprac-footer-info" class="text-muted">—</span>
                <ul class="pagination pagination-sm mb-0" id="exclprac-pagination"></ul>
            </div>

        </div>

        <!-- Footer -->
        <div class="modal-footer py-2" style="background:#fff8f0; border-top:1px solid #ffe0c0;">
            <span class="text-muted me-auto" style="font-size:.75rem;">
                <i class="fa-solid fa-circle-info me-1"></i>
                Prácticas excluidas no serán facturadas para la obra social seleccionada
            </span>
            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
        </div>

    </div>
    </div>
</div>

<script>
(function () {
    'use strict';

    var codigoPrestador = '';
    var POR_PAG = 100;
    var state = { pagina: 1, total: 0, paginas: 1, busqueda: '', cosoc: '', cargando: false };

    window.abrirModalExclPracticas = function (codigo, nombre) {
        codigoPrestador = codigo;
        document.getElementById('exclprac-modal-codigo').textContent = codigo;
        document.getElementById('exclprac-modal-nombre').textContent = nombre;
        document.getElementById('exclprac-busqueda').value  = '';
        document.getElementById('exclprac-select-os').value = '';
        state.pagina = 1; state.busqueda = ''; state.cosoc = '';
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalExclPracticas')).show();
        cargarOSSelector();
        cargarExclPrac(1);
    };

    /* ── Carga selector de OS del prestador ────────────────────────── */
    function cargarOSSelector() {
        var sel = document.getElementById('exclprac-select-os');
        // Reutilizar datos de obramed del prestador
        fetch('index.php?route=prestadores&action=os_list&codigo=' + encodeURIComponent(codigoPrestador),
              { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            sel.innerHTML = '<option value="">— Todas —</option>';
            if (!res.ok || !res.data) return;
            res.data.forEach(function (o) {
                var opt = document.createElement('option');
                opt.value = o.cosoc;
                opt.textContent = o.cosoc + (o.nombre ? ' — ' + o.nombre : '');
                sel.appendChild(opt);
            });
        })
        .catch(function () {});
    }

    /* ── Carga exclusiones de prácticas ────────────────────────────── */
    function cargarExclPrac(pagina) {
        if (state.cargando) return;
        state.cargando = true;
        state.pagina   = pagina || 1;
        state.busqueda = document.getElementById('exclprac-busqueda').value.trim();
        state.cosoc    = document.getElementById('exclprac-select-os').value;

        mostrarLoadingExclPrac();

        var qs = 'route=prestadores&action=excl_prac_listar'
               + '&codigo='   + encodeURIComponent(codigoPrestador)
               + '&cosoc='    + encodeURIComponent(state.cosoc)
               + '&busqueda=' + encodeURIComponent(state.busqueda)
               + '&pagina='   + state.pagina
               + '&por_pagina='+ POR_PAG;

        fetch('index.php?' + qs, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            state.cargando = false;
            if (!res.ok) { mostrarErrorExclPrac(res.error || 'Error'); return; }
            state.total   = res.total   || 0;
            state.paginas = res.paginas || 1;
            document.getElementById('exclprac-total-badge').textContent = state.total + ' exclusiones';
            renderExclPrac(res.datos || []);
            renderPagExclPrac();
        })
        .catch(function () { state.cargando = false; mostrarErrorExclPrac('Error de comunicación.'); });
    }

    function renderExclPrac(rows) {
        var tbody = document.getElementById('exclprac-tbody');
        tbody.innerHTML = '';
        document.getElementById('exclprac-loading').style.display    = 'none';
        document.getElementById('exclprac-table-wrap').style.display = 'block';

        if (!rows.length) {
            document.getElementById('exclprac-empty').style.display = 'block';
            tbody.closest('table').style.display = 'none';
            return;
        }
        document.getElementById('exclprac-empty').style.display = 'none';
        tbody.closest('table').style.display = '';

        rows.forEach(function (r, i) {
            var tr = document.createElement('tr');
            tr.style.background = i % 2 === 0 ? '#fff' : '#fff8f0';

            var activo = r.activo === '1' || r.activo === 1;

            tr.innerHTML =
                '<td class="px-3 py-1 font-monospace fw-semibold text-warning">' + esc(r.practica || r.PEPRACTICA || '') + '</td>'
              + '<td class="px-3 py-1">' + esc(r.nombre || r.PENOMBPRAC || '') + '</td>'
              + '<td class="px-3 py-1 text-center">'
                  + (r.grupo || r.PEGRUPO
                      ? '<span class="badge bg-light border text-dark" style="font-size:.69rem;">' + esc(r.grupo || r.PEGRUPO) + '</span>'
                      : '<span class="text-muted">—</span>')
              + '</td>'
              + '<td class="px-3 py-1 text-center font-monospace" style="font-size:.73rem;">' + esc(r.cosoc || '—') + '</td>'
              + '<td class="px-3 py-1 text-center">'
                  + (activo
                      ? '<span class="badge bg-danger" style="font-size:.7rem;">Excluida</span>'
                      : '<span class="badge bg-success" style="font-size:.7rem;">Habilitada</span>')
              + '</td>'
              + '<td class="px-3 py-1 text-center">'
                  + '<button class="btn btn-xs px-2 py-0 exclprac-toggle"'
                  + '  data-id="' + esc(r.id || '') + '"'
                  + '  data-activo="' + (activo ? '1' : '0') + '"'
                  + '  style="font-size:.7rem; background:' + (activo ? '#f97316' : '#22c55e') + ';color:#fff;border:none;border-radius:4px;">'
                  + (activo ? '<i class="fa-solid fa-rotate-left me-1"></i>Habilitar' : '<i class="fa-solid fa-ban me-1"></i>Excluir')
                  + '</button>'
              + '</td>';

            tbody.appendChild(tr);
        });

        tbody.querySelectorAll('.exclprac-toggle').forEach(function (btn) {
            btn.addEventListener('click', function () {
                toggleExclPrac(this.dataset.id, this.dataset.activo);
            });
        });

        var footer = document.getElementById('exclprac-footer');
        footer.style.removeProperty('display'); footer.style.display = '';
    }

    function toggleExclPrac(id, activo) {
        if (!id) return;
        var fd = new FormData();
        fd.append('csrf_token', '<?= $csrfToken ?>');
        fd.append('id',     id);
        fd.append('activo', activo);
        fetch('index.php?route=prestadores&action=excl_prac_toggle', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: fd
        })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            if (!res.ok) { alert(res.error || 'Error'); return; }
            cargarExclPrac(state.pagina);
        })
        .catch(function () { alert('Error de comunicación.'); });
    }

    function renderPagExclPrac() {
        var $pag  = document.getElementById('exclprac-pagination');
        var $info = document.getElementById('exclprac-footer-info');
        $info.textContent = 'Mostrando ' + Math.min(1, state.total) + '–' + Math.min(state.pagina * POR_PAG, state.total) + ' de ' + state.total;
        $pag.innerHTML = '';
        if (state.paginas <= 1) return;
        addEPBtn($pag, '«', state.pagina > 1 ? state.pagina - 1 : null, state.pagina <= 1);
        var s = Math.max(1, state.pagina - 2), e = Math.min(state.paginas, s + 4);
        for (var p = s; p <= e; p++) addEPBtn($pag, p, p, false, p === state.pagina);
        addEPBtn($pag, '»', state.pagina < state.paginas ? state.pagina + 1 : null, state.pagina >= state.paginas);
    }
    function addEPBtn($ul, lbl, pg, dis, act) {
        var li = document.createElement('li');
        li.className = 'page-item' + (dis ? ' disabled' : '') + (act ? ' active' : '');
        var a = document.createElement('a'); a.className = 'page-link'; a.href = '#'; a.innerHTML = lbl;
        if (pg && !dis && !act) a.addEventListener('click', function (e) { e.preventDefault(); cargarExclPrac(pg); });
        li.appendChild(a); $ul.appendChild(li);
    }

    function mostrarLoadingExclPrac() {
        document.getElementById('exclprac-loading').style.display    = 'block';
        document.getElementById('exclprac-table-wrap').style.display = 'none';
    }
    function mostrarErrorExclPrac(msg) {
        document.getElementById('exclprac-loading').innerHTML =
            '<div class="text-center py-4"><i class="fa-solid fa-triangle-exclamation text-warning fa-lg mb-2 d-block"></i>'
            + '<p class="text-muted mb-0" style="font-size:.82rem;">' + esc(msg) + '</p></div>';
        document.getElementById('exclprac-loading').style.display = 'block';
        document.getElementById('exclprac-table-wrap').style.display = 'none';
    }
    function esc(s) { var d = document.createElement('div'); d.appendChild(document.createTextNode(s||'')); return d.innerHTML; }

    document.getElementById('exclprac-btn-buscar').addEventListener('click', function () { cargarExclPrac(1); });
    document.getElementById('exclprac-busqueda').addEventListener('keydown', function (e) {
        if (e.key === 'Enter') { e.preventDefault(); cargarExclPrac(1); }
    });
    document.getElementById('exclprac-select-os').addEventListener('change', function () { cargarExclPrac(1); });

})();
</script>
