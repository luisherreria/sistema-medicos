<?php
/**
 * views/prestadores/_modal_excl_os.php
 * ─────────────────────────────────────────────────────────────────────────
 * Modal: Exclusión de Obras Sociales del Prestador
 * Gestiona los flags EXCLUCART (excluir de cartilla) y EXCLUCALL
 * sobre los registros de `obramed` del prestador.
 * ─────────────────────────────────────────────────────────────────────────
 */
$csrfToken = $_SESSION['csrf_token'] ?? '';
?>

<!-- ╔═════════════════════════════════════════════════════════════════════╗
     ║  MODAL: EXCLUSIÓN DE OBRAS SOCIALES                                ║
     ╚═════════════════════════════════════════════════════════════════════╝ -->
<div class="modal fade" id="modalExclOS" tabindex="-1"
     aria-labelledby="modalExclOSLabel" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="false">

    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content" style="border-radius:12px; overflow:hidden;">

        <!-- Encabezado -->
        <div class="modal-header py-2 px-4"
             style="background:linear-gradient(90deg,#b71c1c,#e53935); color:#fff; border-bottom:none;">
            <div class="d-flex align-items-center gap-3">
                <div style="width:38px;height:38px;border-radius:50%;
                            background:rgba(255,255,255,.2);
                            display:flex;align-items:center;justify-content:center;">
                    <i class="fa-solid fa-circle-xmark"></i>
                </div>
                <div>
                    <h6 class="modal-title mb-0 fw-bold" id="modalExclOSLabel">Exclusión de Obras Sociales</h6>
                    <small style="opacity:.85; font-size:0.73rem;">
                        Prestador: <span id="exclos-modal-nombre" class="fw-semibold">—</span>
                        &nbsp;|&nbsp; Código: <code id="exclos-modal-codigo" style="color:#ef9a9a; font-size:.8rem;">—</code>
                    </small>
                </div>
            </div>
            <button type="button" class="btn-close btn-close-white"
                    data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>

        <!-- Cuerpo -->
        <div class="modal-body p-0">

            <!-- Leyenda -->
            <div class="d-flex align-items-center gap-3 px-3 py-2 flex-wrap"
                 style="background:#fff5f5; border-bottom:1px solid #fde8e8; font-size:.76rem;">
                <span><span class="badge bg-danger me-1">Excl. Cartilla</span>OS no publicada en cartilla web</span>
                <span><span class="badge bg-warning text-dark me-1">Excl. Llamado</span>OS no aparece en llamados</span>
            </div>

            <!-- Barra de herramientas -->
            <div class="d-flex align-items-center gap-2 flex-wrap px-3 py-2"
                 style="background:#fafafa; border-bottom:1px solid #f0f0f0;">
                <div class="input-group input-group-sm" style="max-width:260px;">
                    <span class="input-group-text bg-white"><i class="fa-solid fa-search text-muted" style="font-size:.75rem;"></i></span>
                    <input type="text" id="exclos-busqueda" class="form-control" placeholder="Buscar obra social…" autocomplete="off" style="font-size:.8rem;">
                </div>
                <div class="form-check form-switch ms-1 mb-0">
                    <input class="form-check-input" type="checkbox" id="exclos-solo-excluidas" style="cursor:pointer;">
                    <label class="form-check-label" for="exclos-solo-excluidas" style="font-size:.78rem; cursor:pointer;">
                        Solo excluidas
                    </label>
                </div>
                <span id="exclos-total-badge" class="badge bg-danger bg-opacity-75 ms-auto" style="font-size:.72rem;">—</span>
            </div>

            <!-- Spinner -->
            <div id="exclos-loading" class="text-center py-5">
                <div class="spinner-border text-danger spinner-border-sm" role="status"></div>
                <p class="text-muted mt-2 mb-0" style="font-size:.82rem;">Cargando obras sociales…</p>
            </div>

            <!-- Tabla -->
            <div id="exclos-table-wrap" style="display:none;">
                <div class="table-responsive">
                    <table class="table table-hover table-bordered mb-0" style="font-size:.76rem; white-space:nowrap;">
                        <thead>
                            <tr style="background:#fde8e8; font-size:.72rem;">
                                <th class="px-3 py-2" style="width:90px;">Código</th>
                                <th class="px-3 py-2">Nombre O.S.</th>
                                <th class="px-3 py-2 text-center" style="width:110px;">Excl. Cartilla</th>
                                <th class="px-3 py-2 text-center" style="width:110px;">Excl. Llamado</th>
                                <th class="px-3 py-2 text-center" style="width:90px;">F. Alta</th>
                            </tr>
                        </thead>
                        <tbody id="exclos-tbody"></tbody>
                    </table>
                </div>
                <div id="exclos-empty" class="text-center py-5" style="display:none;">
                    <i class="fa-solid fa-circle-check fa-2x text-success mb-2 d-block" style="opacity:.4;"></i>
                    <p class="text-muted mb-0" style="font-size:.85rem;">No hay obras sociales con exclusiones.</p>
                </div>
            </div>

            <!-- Paginación -->
            <div id="exclos-footer"
                 class="d-flex align-items-center justify-content-between px-3 py-2 border-top"
                 style="background:#fafafa; font-size:.78rem; display:none !important;">
                <span id="exclos-footer-info" class="text-muted">—</span>
                <ul class="pagination pagination-sm mb-0" id="exclos-pagination"></ul>
            </div>

        </div>

        <!-- Footer -->
        <div class="modal-footer py-2" style="background:#fafafa; border-top:1px solid #f0f0f0;">
            <span class="text-muted me-auto" style="font-size:.75rem;">
                <i class="fa-solid fa-circle-info me-1"></i>
                Los cambios se guardan automáticamente al hacer clic en los botones de exclusión
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
    var allRows = [];
    var state = { pagina: 1, total: 0, paginas: 1, busqueda: '', soloExcluidas: false, cargando: false };

    window.abrirModalExclOS = function (codigo, nombre) {
        codigoPrestador = codigo;
        document.getElementById('exclos-modal-codigo').textContent = codigo;
        document.getElementById('exclos-modal-nombre').textContent = nombre;
        document.getElementById('exclos-busqueda').value = '';
        document.getElementById('exclos-solo-excluidas').checked = false;
        state.pagina = 1; state.busqueda = ''; state.soloExcluidas = false;
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalExclOS')).show();
        cargarExclOS();
    };

    function cargarExclOS() {
        if (state.cargando) return;
        state.cargando = true;
        state.busqueda     = document.getElementById('exclos-busqueda').value.trim();
        state.soloExcluidas= document.getElementById('exclos-solo-excluidas').checked;

        mostrarLoadingExclOS();

        var qs = 'route=prestadores&action=excl_os_listar'
               + '&codigo=' + encodeURIComponent(codigoPrestador);

        fetch('index.php?' + qs, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            state.cargando = false;
            if (!res.ok) { mostrarErrorExclOS(res.error || 'Error'); return; }
            allRows = res.data || [];
            filtrarYRenderExclOS();
        })
        .catch(function () { state.cargando = false; mostrarErrorExclOS('Error de comunicación.'); });
    }

    function filtrarYRenderExclOS() {
        var bus = state.busqueda.toLowerCase();
        var rows = allRows.filter(function (r) {
            var match = !bus
                || (r.cosoc  || '').toLowerCase().includes(bus)
                || (r.nombre || '').toLowerCase().includes(bus);
            var exclFlt = !state.soloExcluidas
                || r.exclucart === '1' || r.exclucall === '1';
            return match && exclFlt;
        });

        document.getElementById('exclos-total-badge').textContent =
            rows.filter(function(r){ return r.exclucart==='1'||r.exclucall==='1'; }).length + ' excluidas';

        renderExclOS(rows);
    }

    function renderExclOS(rows) {
        var tbody = document.getElementById('exclos-tbody');
        tbody.innerHTML = '';
        document.getElementById('exclos-loading').style.display    = 'none';
        document.getElementById('exclos-table-wrap').style.display = 'block';

        if (!rows.length) {
            document.getElementById('exclos-empty').style.display = 'block';
            tbody.closest('table').style.display = 'none';
            return;
        }
        document.getElementById('exclos-empty').style.display = 'none';
        tbody.closest('table').style.display = '';

        rows.forEach(function (r, i) {
            var tr = document.createElement('tr');
            tr.style.background = i % 2 === 0 ? '#fff' : '#fafafa';

            var isCart = r.exclucart === '1';
            var isCall = r.exclucall === '1';

            var fa = r.fechaalta ? r.fechaalta.substring(0,10).split('-').reverse().join('/') : '—';

            tr.innerHTML =
                '<td class="px-3 py-1 font-monospace fw-semibold text-danger">' + esc(r.cosoc || '') + '</td>'
              + '<td class="px-3 py-1">' + esc(r.nombre || r.cosoc || '') + '</td>'
              + '<td class="px-3 py-1 text-center">'
                  + '<button class="btn btn-xs px-2 py-0 exclos-toggle-btn"'
                  + '  data-cosoc="' + esc(r.cosoc) + '" data-tipo="cart" data-activo="' + (isCart?'1':'0') + '"'
                  + '  style="font-size:.72rem; min-width:70px;'
                  + (isCart ? 'background:#dc2626;color:#fff;border:1px solid #b91c1c;' : 'background:#f3f4f6;color:#374151;border:1px solid #d1d5db;') + '">'
                  + (isCart ? '<i class="fa-solid fa-ban me-1"></i>Excluida' : '<i class="fa-solid fa-check me-1 text-success"></i>Habilitada')
                  + '</button>'
              + '</td>'
              + '<td class="px-3 py-1 text-center">'
                  + '<button class="btn btn-xs px-2 py-0 exclos-toggle-btn"'
                  + '  data-cosoc="' + esc(r.cosoc) + '" data-tipo="call" data-activo="' + (isCall?'1':'0') + '"'
                  + '  style="font-size:.72rem; min-width:70px;'
                  + (isCall ? 'background:#d97706;color:#fff;border:1px solid #b45309;' : 'background:#f3f4f6;color:#374151;border:1px solid #d1d5db;') + '">'
                  + (isCall ? '<i class="fa-solid fa-ban me-1"></i>Excluida' : '<i class="fa-solid fa-check me-1 text-success"></i>Habilitada')
                  + '</button>'
              + '</td>'
              + '<td class="px-3 py-1 text-center text-muted" style="font-size:.72rem;">' + fa + '</td>';

            tbody.appendChild(tr);
        });

        /* delegación de eventos para toggles */
        tbody.querySelectorAll('.exclos-toggle-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                toggleExclOS(this.dataset.cosoc, this.dataset.tipo, this.dataset.activo);
            });
        });
    }

    function toggleExclOS(cosoc, tipo, activo) {
        var fd = new FormData();
        fd.append('csrf_token',   '<?= $csrfToken ?>');
        fd.append('codigo',       codigoPrestador);
        fd.append('cosoc',        cosoc);
        fd.append('tipo',         tipo);
        fd.append('activo',       activo);

        fetch('index.php?route=prestadores&action=excl_os_toggle', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: fd
        })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            if (!res.ok) { alert(res.error || 'Error'); return; }
            // Actualizar el dato local y re-renderizar
            allRows.forEach(function (r) {
                if (r.cosoc === cosoc) {
                    if (tipo === 'cart') r.exclucart = activo === '1' ? '0' : '1';
                    else                r.exclucall = activo === '1' ? '0' : '1';
                }
            });
            filtrarYRenderExclOS();
        })
        .catch(function () { alert('Error de comunicación.'); });
    }

    function mostrarLoadingExclOS() {
        document.getElementById('exclos-loading').style.display    = 'block';
        document.getElementById('exclos-table-wrap').style.display = 'none';
    }
    function mostrarErrorExclOS(msg) {
        document.getElementById('exclos-loading').innerHTML =
            '<div class="text-center py-4"><i class="fa-solid fa-triangle-exclamation text-warning fa-lg mb-2 d-block"></i>'
            + '<p class="text-muted mb-0" style="font-size:.82rem;">' + esc(msg) + '</p></div>';
        document.getElementById('exclos-loading').style.display = 'block';
        document.getElementById('exclos-table-wrap').style.display = 'none';
    }
    function esc(s) { var d = document.createElement('div'); d.appendChild(document.createTextNode(s||'')); return d.innerHTML; }

    document.getElementById('exclos-busqueda').addEventListener('input', function () {
        clearTimeout(this._t);
        var self = this;
        this._t = setTimeout(function () { state.busqueda = self.value.trim(); filtrarYRenderExclOS(); }, 250);
    });
    document.getElementById('exclos-solo-excluidas').addEventListener('change', function () {
        state.soloExcluidas = this.checked;
        filtrarYRenderExclOS();
    });

})();
</script>
