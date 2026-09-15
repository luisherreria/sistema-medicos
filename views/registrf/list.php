<?php
/**
 * views/registrf/list.php
 * ─────────────────────────────────────────────────────────────────────────
 * Vista principal: Registración de Facturas
 * Grilla AJAX · paginación · búsqueda · ordenamiento por columna
 *
     * PERÍODO (COPERIODO):
     *   Almacenado como AAMM (ej: 2607 = Julio 2026).
     *   Mostrado como AA/MM (ej: 26/07).
 * ─────────────────────────────────────────────────────────────────────────
 */

$pageTitle  = 'COMEDICA — Registración de Facturas';
$breadcrumb = [
    ['label' => 'Carga de Datos'],
    ['label' => 'Registración de Facturas'],
];

require_once __DIR__ . '/../../views/layouts/header.php';
require_once __DIR__ . '/_modal_ingresar.php';
?>

<!-- ── Tailwind CSS CDN (preflight desactivado para convivir con Bootstrap 5) -->
<script>
window.tailwind && (window.tailwind.config = { corePlugins: { preflight: false } });
</script>
<script src="https://cdn.tailwindcss.com?plugins=forms"></script>
<script>
tailwind.config = { corePlugins: { preflight: false } };
</script>

<div id="rf-app">

    <!-- ═══ BARRA SUPERIOR: Título · Búsqueda · Botones ═══════════════════ -->
    <div class="d-flex align-items-center gap-2 flex-wrap mb-2"
         style="background:#fff;border:1px solid #e2e8f0;border-radius:10px;
                padding:8px 12px;box-shadow:0 1px 4px rgba(0,0,0,.06);">

        <!-- Título -->
        <div class="d-flex align-items-center gap-2 me-1 flex-shrink-0">
            <div style="width:32px;height:32px;border-radius:8px;
                        background:linear-gradient(135deg,#0d47a1,#1976d2);
                        display:flex;align-items:center;justify-content:center;">
                <i class="fa-solid fa-file-invoice-dollar text-white" style="font-size:.85rem;"></i>
            </div>
            <span style="font-weight:700;font-size:.85rem;color:#1e293b;white-space:nowrap;">
                Registración de Facturas
            </span>
        </div>

        <!-- Input de búsqueda -->
        <div class="d-flex align-items-center gap-1 flex-grow-1" style="min-width:180px;max-width:340px;">
            <input type="text" id="rf-busqueda"
                   class="form-control form-control-sm"
                   style="font-size:.78rem;"
                   placeholder="Prestador, N° Factura u Obra Social…">
            <button id="rf-btn-buscar"
                    class="btn btn-sm btn-primary flex-shrink-0"
                    style="font-size:.75rem;padding:4px 10px;">
                <i class="fa-solid fa-magnifying-glass me-1"></i>Buscar
            </button>
        </div>

        <!-- Filas por página -->
        <div class="d-flex align-items-center gap-1 flex-shrink-0">
            <label style="font-size:.72rem;color:#64748b;white-space:nowrap;">Filas:</label>
            <select id="rf-pp" class="form-select form-select-sm" style="width:70px;font-size:.75rem;">
                <option value="25">25</option>
                <option value="50" selected>50</option>
                <option value="100">100</option>
                <option value="200">200</option>
            </select>
        </div>

        <!-- Botón Ingresar -->
        <button id="btn-rf-ingresar"
                class="btn btn-sm text-white fw-semibold flex-shrink-0"
                style="background:#2563eb;border-color:#1d4ed8;font-size:0.78rem;"
                onmouseover="this.style.background='#1d4ed8'"
                onmouseout="this.style.background='#2563eb'"
                title="Ingresar nueva factura">
            <i class="fa-solid fa-plus me-1"></i>Ingresar
        </button>

        <!-- Botón Imprimir -->
        <button id="btn-rf-imprimir"
                class="btn btn-sm btn-outline-secondary flex-shrink-0"
                style="font-size:0.78rem;" title="Imprimir listado">
            <i class="fa-solid fa-print me-1"></i>Imprimir
        </button>

        <!-- Terminar -->
        <a href="index.php?route=dashboard"
           class="btn btn-sm btn-danger fw-semibold flex-shrink-0 ms-auto"
           style="font-size:0.78rem;" title="Volver al Dashboard">
            <i class="fa-solid fa-xmark me-1"></i>Terminar
        </a>
    </div>
    <!-- /barra superior -->

    <!-- ═══ GRILLA ══════════════════════════════════════════════════════════ -->
    <div class="overflow-x-auto" style="border-radius:10px;box-shadow:0 2px 8px rgba(0,0,0,.08);">
    <table class="w-full border-collapse" id="rf-tabla" style="min-width:900px;">
        <thead>
        <tr style="background:#1e293b;color:#fff;">
            <th class="rf-th sortable" data-col="COPERIODO"   style="width:80px;">PERÍODO  <span class="rf-sort-icon">⇅</span></th>
            <th class="rf-th sortable" data-col="COOBRASOC"   style="width:110px;">O. SOCIAL <span class="rf-sort-icon">⇅</span></th>
            <th class="rf-th sortable" data-col="COPRESTADO"  style="width:100px;">CÓD. PREST. <span class="rf-sort-icon">⇅</span></th>
            <th class="rf-th sortable" data-col="CONOMPREST"  style="min-width:200px;">PRESTADOR <span class="rf-sort-icon">⇅</span></th>
            <th class="rf-th sortable" data-col="CONROFAC"    style="width:120px;">N° FACTURA <span class="rf-sort-icon">⇅</span></th>
            <th class="rf-th sortable" data-col="COFECFAC"    style="width:95px;">F. FACTURA <span class="rf-sort-icon">⇅</span></th>
            <th class="rf-th sortable" data-col="COTOTALFAC"  style="width:110px;text-align:right;">TOTAL <span class="rf-sort-icon">⇅</span></th>
            <th class="rf-th sortable" data-col="COUSUARIO"   style="width:80px;">USUARIO <span class="rf-sort-icon">⇅</span></th>
            <th class="rf-th sortable" data-col="COFECCARGA"  style="width:110px;">F. CARGA <span class="rf-sort-icon">⇅</span></th>
        </tr>
        </thead>
        <tbody id="rf-tbody">
        <tr>
            <td colspan="10" class="text-center py-4 text-muted" style="font-size:.82rem;">
                <span class="spinner-border spinner-border-sm me-2"></span>Cargando…
            </td>
        </tr>
        </tbody>
    </table>
    </div>
    <!-- /grilla -->

    <!-- ═══ PAGINACIÓN + INFO ══════════════════════════════════════════════ -->
    <div class="d-flex justify-content-between align-items-center mt-2 flex-wrap gap-2">
        <div id="rf-info" style="font-size:.75rem;color:#64748b;"></div>
        <div class="d-flex gap-1 align-items-center">
            <button id="rf-prev" class="btn btn-sm btn-outline-secondary" style="font-size:.75rem;">
                <i class="fa-solid fa-chevron-left me-1"></i>Anterior
            </button>
            <span id="rf-pagina-info" style="font-size:.75rem;color:#475569;padding:0 8px;"></span>
            <button id="rf-next" class="btn btn-sm btn-outline-secondary" style="font-size:.75rem;">
                Siguiente<i class="fa-solid fa-chevron-right ms-1"></i>
            </button>
        </div>
    </div>

</div><!-- /#rf-app -->

<style>
.rf-th {
    padding: 8px 10px;
    font-size: .72rem;
    font-weight: 700;
    letter-spacing: .03em;
    text-transform: uppercase;
    white-space: nowrap;
    border-right: 1px solid rgba(255,255,255,.12);
    user-select: none;
}
.rf-th.sortable { cursor: pointer; }
.rf-th.sortable:hover { background: #334155; }
.rf-th.sort-asc  .rf-sort-icon::after { content: ' ▲'; }
.rf-th.sort-desc .rf-sort-icon::after { content: ' ▼'; }
.rf-sort-icon { opacity: .5; font-size: .65rem; }
.rf-th.sort-asc  .rf-sort-icon,
.rf-th.sort-desc .rf-sort-icon { opacity: 1; color: #60a5fa; }

#rf-tabla tbody tr:nth-child(odd)  { background: #fff; }
#rf-tabla tbody tr:nth-child(even) { background: #f8fafc; }
#rf-tabla tbody tr:hover { background: #eff6ff !important; }

#rf-tabla tbody td {
    padding: 5px 10px;
    font-size: .78rem;
    color: #1e293b;
    border-bottom: 1px solid #f1f5f9;
    white-space: nowrap;
}
.badge-os {
    display: inline-block;
    background: #dbeafe;
    color: #1d4ed8;
    border-radius: 4px;
    padding: 1px 6px;
    font-size: .7rem;
    font-weight: 600;
    font-family: monospace;
}
.td-total { text-align: right; font-family: monospace; font-weight: 600; color: #0f5132; }
.td-fecha { font-family: monospace; font-size: .75rem; color: #475569; }
</style>

<script>
(function () {
    'use strict';

    var ROUTE = 'registro-facturas';

    var state = {
        busqueda : '',
        pagina   : 1,
        porPag   : 50,
        orderCol : 'COPERIODO',
        orderDir : 'DESC',
        total    : 0,
        paginas  : 1,
        cargando : false,
    };

    // ── Referencias DOM ────────────────────────────────────────────────────
    var $tbody   = document.getElementById('rf-tbody');
    var $info    = document.getElementById('rf-info');
    var $pgInfo  = document.getElementById('rf-pagina-info');
    var $prev    = document.getElementById('rf-prev');
    var $next    = document.getElementById('rf-next');
    var $bus     = document.getElementById('rf-busqueda');
    var $pp      = document.getElementById('rf-pp');
    var $btnBus  = document.getElementById('rf-btn-buscar');

    // ══════════════════════════════════════════════════════════════════════
    //  CARGA AJAX
    // ══════════════════════════════════════════════════════════════════════
    function cargar(pagina) {
        if (state.cargando) return;
        state.cargando = true;
        state.pagina   = pagina || state.pagina;

        $tbody.innerHTML = '<tr><td colspan="10" class="text-center py-4 text-muted" style="font-size:.82rem;">'
            + '<span class="spinner-border spinner-border-sm me-2"></span>Cargando…</td></tr>';

        var url = 'index.php?route=' + ROUTE + '&action=listado'
            + '&busqueda='  + encodeURIComponent(state.busqueda)
            + '&pagina='    + state.pagina
            + '&por_pagina='+ state.porPag
            + '&order_col=' + encodeURIComponent(state.orderCol)
            + '&order_dir=' + state.orderDir;

        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                state.cargando = false;
                if (!res.ok) {
                    $tbody.innerHTML = '<tr><td colspan="10" class="text-center text-danger py-3" style="font-size:.82rem;">'
                        + '<i class="fa-solid fa-triangle-exclamation me-1"></i>'
                        + esc(res.error || 'Error al cargar datos.') + '</td></tr>';
                    return;
                }
                state.total   = res.total;
                state.paginas = res.paginas;
                renderTabla(res.datos);
                renderPaginacion();
            })
            .catch(function (err) {
                state.cargando = false;
                $tbody.innerHTML = '<tr><td colspan="10" class="text-center text-danger py-3" style="font-size:.82rem;">'
                    + 'Error de comunicación con el servidor.</td></tr>';
            });
    }

    // ══════════════════════════════════════════════════════════════════════
    //  RENDERIZADO DE FILAS
    //  COPERIODO: almacenado AAMM (ej: "2607") → mostrar MM/AA (ej: "07/26")
    // ══════════════════════════════════════════════════════════════════════
    function renderTabla(datos) {
        if (!datos || !datos.length) {
            $tbody.innerHTML = '<tr><td colspan="10" class="text-center text-muted py-4" style="font-size:.82rem;">'
                + '<i class="fa-solid fa-inbox me-2"></i>No se encontraron facturas.</td></tr>';
            return;
        }

        var html = '';
        datos.forEach(function (r) {
            // ── Período: AAMM → MM/AA ───────────────────────────────────
            var per = (r.COPERIODO || '').trim();
            var perDisplay = per.length === 4
                ? per.substring(0, 2) + '/' + per.substring(2, 4)   // AA/MM  (ej: "2607" → "26/07")
                : per;

            // ── N° Factura: suc-nro ─────────────────────────────────────
            var suc = (r.COSUCFAC || '').trim();
            var nro = (r.CONROFAC || '').trim();
            var factura = suc && nro ? suc + '-' + nro : (nro || suc || '—');

            // ── Fechas dd/mm/yyyy ───────────────────────────────────────
            var fecFac    = fmtFecha(r.COFECFAC);
            var fecCarga  = fmtFecha(r.COFECCARGA, true);

            // ── Total en moneda ─────────────────────────────────────────
            var total = r.COTOTALFAC != null && r.COTOTALFAC !== ''
                ? '$ ' + parseFloat(r.COTOTALFAC).toLocaleString('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
                : '—';

            html += '<tr>'
                + '<td><span style="font-family:monospace;font-weight:600;font-size:.78rem;">' + esc(perDisplay) + '</span></td>'
                + '<td><span class="badge-os">' + esc(r.COOBRASOC || '') + '</span></td>'
                + '<td style="font-family:monospace;font-size:.75rem;color:#1d4ed8;font-weight:600;">' + esc(r.COPRESTADO || '') + '</td>'
                + '<td style="max-width:280px;overflow:hidden;text-overflow:ellipsis;" title="' + esc(r.CONOMPREST || '') + '">' + esc(r.CONOMPREST || '') + '</td>'
                + '<td style="font-family:monospace;font-size:.75rem;font-weight:600;">' + esc(factura) + '</td>'
                + '<td class="td-fecha">' + fecFac + '</td>'
                + '<td class="td-total">' + esc(total) + '</td>'
                + '<td style="font-size:.73rem;color:#64748b;">' + esc(r.COUSUARIO || '') + '</td>'
                + '<td class="td-fecha">' + fecCarga + '</td>'
                + '</tr>';
        });
        $tbody.innerHTML = html;
    }

    // ══════════════════════════════════════════════════════════════════════
    //  PAGINACIÓN
    // ══════════════════════════════════════════════════════════════════════
    function renderPaginacion() {
        var desde = state.total > 0 ? ((state.pagina - 1) * state.porPag + 1) : 0;
        var hasta = Math.min(state.pagina * state.porPag, state.total);
        $info.textContent  = state.total > 0
            ? 'Mostrando ' + desde + '–' + hasta + ' de ' + state.total.toLocaleString('es-AR') + ' registros'
            : 'Sin resultados';
        $pgInfo.textContent = 'Pág. ' + state.pagina + ' / ' + state.paginas;
        $prev.disabled = state.pagina <= 1;
        $next.disabled = state.pagina >= state.paginas;
    }

    $prev.addEventListener('click', function () { if (state.pagina > 1) cargar(state.pagina - 1); });
    $next.addEventListener('click', function () { if (state.pagina < state.paginas) cargar(state.pagina + 1); });

    // ══════════════════════════════════════════════════════════════════════
    //  BÚSQUEDA
    // ══════════════════════════════════════════════════════════════════════
    $btnBus.addEventListener('click', function () {
        state.busqueda = $bus.value.trim();
        cargar(1);
    });
    $bus.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') { e.preventDefault(); state.busqueda = $bus.value.trim(); cargar(1); }
    });
    $pp.addEventListener('change', function () {
        state.porPag = parseInt(this.value);
        cargar(1);
    });

    // ══════════════════════════════════════════════════════════════════════
    //  ORDENAMIENTO POR COLUMNA
    // ══════════════════════════════════════════════════════════════════════
    document.querySelectorAll('.rf-th.sortable').forEach(function ($th) {
        $th.addEventListener('click', function () {
            var col = this.dataset.col;
            if (state.orderCol === col) {
                state.orderDir = state.orderDir === 'ASC' ? 'DESC' : 'ASC';
            } else {
                state.orderCol = col;
                state.orderDir = 'DESC';
            }
            document.querySelectorAll('.rf-th').forEach(function (el) {
                el.classList.remove('sort-asc', 'sort-desc');
            });
            this.classList.add(state.orderDir === 'ASC' ? 'sort-asc' : 'sort-desc');
            cargar(1);
        });
    });
    // Marcar columna inicial
    (function () {
        var $th = document.querySelector('.rf-th[data-col="' + state.orderCol + '"]');
        if ($th) $th.classList.add('sort-desc');
    })();
    // ══════════════════════════════════════════════════════════════════════
    //  BOTONES DE ACCIÓN
    // ══════════════════════════════════════════════════════════════════════
    document.getElementById('btn-rf-ingresar').addEventListener('click', function () {
        window.abrirModalIngresarFactura();
    });

    document.getElementById('btn-rf-imprimir').addEventListener('click', function () {
        window.print();
    });

    // ══════════════════════════════════════════════════════════════════════
    //  HELPERS
    // ══════════════════════════════════════════════════════════════════════

    /**
     * Formatea una fecha ISO (yyyy-mm-dd o datetime) en dd/mm/yyyy.
     * @param {string} s    Valor crudo de la BD
     * @param {boolean} dt  Si es datetime, incluir hora
     */
    function fmtFecha(s, dt) {
        if (!s || s === '0000-00-00' || s === '0000-00-00 00:00:00') return '—';
        var parts = s.substring(0, 10).split('-');
        if (parts.length !== 3) return s;
        var base = parts[2] + '/' + parts[1] + '/' + parts[0];
        if (dt && s.length > 10) {
            var hora = s.substring(11, 16);
            return base + ' ' + hora;
        }
        return base;
    }

    function esc(s) {
        var d = document.createElement('div');
        d.appendChild(document.createTextNode(String(s)));
        return d.innerHTML;
    }

    // ══════════════════════════════════════════════════════════════════════
    //  CARGA INICIAL + EXPONER PARA EL MODAL
    // ══════════════════════════════════════════════════════════════════════
    window.rfCargar = cargar;
    cargar(1);

})();
</script>

<?php require_once __DIR__ . '/../../views/layouts/footer.php'; ?>
