<?php
/**
 * views/prestadores/_modal_sucursales.php
 * ─────────────────────────────────────────────────────────────────────────
 * Modal Sucursales del Prestador — equivalente VFP SUCURSALE.SCX
 * Grilla paginada + sortable + búsqueda
 * Sub-modal: Excluir OS de Cartilla
 * Sub-modal: Prácticas de la Sucursal
 */
$csrfToken = $_SESSION['csrf_token'] ?? '';
?>

<!-- ══════════════════════════════════════════════════════════════════════
     MODAL PRINCIPAL: SUCURSALES
═══════════════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="modalSucursales" tabindex="-1"
     aria-labelledby="modalSucursalesLabel" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content" style="border-radius:10px;overflow:hidden;">

        <!-- Header -->
        <div class="modal-header py-2 px-4"
             style="background:linear-gradient(90deg,#1565c0,#1976d2);color:#fff;border-bottom:none;">
            <div class="d-flex align-items-center gap-3">
                <div style="width:34px;height:34px;border-radius:50%;background:rgba(255,255,255,.18);
                            display:flex;align-items:center;justify-content:center;">
                    <i class="fa-solid fa-map-location-dot"></i>
                </div>
                <div>
                    <h6 class="modal-title mb-0 fw-bold" id="modalSucursalesLabel">Sucursales</h6>
                    <small style="opacity:.8;font-size:.72rem;">
                        Código: <code id="suc-header-codigo" style="color:#90caf9;font-size:.8rem;">—</code>
                        &nbsp;<span id="suc-header-nombre" style="font-size:.75rem;"></span>
                    </small>
                </div>
            </div>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>

        <!-- Body -->
        <div class="modal-body p-0">

            <!-- ── PANEL: GRILLA ─────────────────────────────────────── -->
            <div id="suc-panel-grid">
                <!-- Barra búsqueda -->
                <div class="px-3 pt-3 pb-2 d-flex gap-2 align-items-center" style="background:#f8f9fc;">
                    <div class="input-group input-group-sm" style="max-width:320px;">
                        <span class="input-group-text"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                        <input type="text" id="suc-busqueda" class="form-control"
                               placeholder="Buscar por código, nombre, dirección…" autocomplete="off">
                    </div>
                    <button id="suc-btn-buscar" class="btn btn-sm btn-primary">
                        <i class="fa-solid fa-search me-1"></i>Buscar
                    </button>
                    <span id="suc-total-badge" class="badge bg-secondary ms-auto" style="font-size:.72rem;"></span>
                </div>

                <!-- Spinner -->
                <div id="suc-loading" class="text-center py-4">
                    <div class="spinner-border spinner-border-sm text-primary"></div>
                    <span class="ms-2 text-muted" style="font-size:.83rem;">Cargando…</span>
                </div>

                <!-- Tabla -->
                <div id="suc-table-wrap" style="display:none;" class="px-3">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover table-bordered mb-0" id="tbl-sucursales"
                               style="font-size:.78rem;white-space:nowrap;">
                            <thead style="background:#e8edf5;font-size:.73rem;">
                                <tr>
                                    <th style="width:32px;padding:5px;"></th>
                                    <th data-sort="sucodigo"   class="suc-th" style="width:90px;">Código Suc.<span class="suc-ind"> ↕</span></th>
                                    <th data-sort="sunombre"   class="suc-th" style="min-width:140px;">Nombre<span class="suc-ind"> ↕</span></th>
                                    <th data-sort="sudir"      class="suc-th" style="min-width:140px;">Dirección<span class="suc-ind"> ↕</span></th>
                                    <th data-sort="sulocalida" class="suc-th" style="width:100px;">Localidad<span class="suc-ind"> ↕</span></th>
                                    <th data-sort="suzona"     class="suc-th" style="width:110px;">Zona<span class="suc-ind"> ↕</span></th>
                                    <th data-sort="sutelef"    class="suc-th" style="width:110px;">Teléfono<span class="suc-ind"> ↕</span></th>
                                    <th data-sort="sucontacto" class="suc-th" style="width:100px;">Contacto<span class="suc-ind"> ↕</span></th>
                                    <th data-sort="suemail"    class="suc-th" style="min-width:150px;">E-mail<span class="suc-ind"> ↕</span></th>
                                </tr>
                            </thead>
                            <tbody id="suc-tbody"></tbody>
                        </table>
                    </div>
                    <div id="suc-empty" style="display:none;" class="text-center py-4 text-muted">
                        <i class="fa-solid fa-map-pin fa-lg mb-2 d-block" style="opacity:.35;"></i>
                        No hay sucursales registradas para este prestador.
                    </div>
                </div>

                <!-- Paginación -->
                <div id="suc-footer" class="px-3 py-2 d-flex align-items-center justify-content-between"
                     style="font-size:.78rem;background:#f5f7fb;display:none!important;">
                    <span id="suc-footer-info" class="text-muted">—</span>
                    <ul class="pagination pagination-sm mb-0" id="suc-pagination"></ul>
                </div>
            </div><!-- /#suc-panel-grid -->

            <!-- ── PANEL: FORMULARIO ─────────────────────────────────── -->
            <div id="suc-panel-form" style="display:none;" class="px-4 py-3">
                <h6 id="suc-form-titulo" class="fw-semibold mb-3" style="font-size:.85rem;color:#1565c0;">
                    <i class="fa-solid fa-plus-circle me-1"></i>Nueva Sucursal
                </h6>
                <form id="suc-form" novalidate autocomplete="off">
                <input type="hidden" name="csrf_token"  id="suc-csrf"       value="<?= htmlspecialchars($csrfToken) ?>">
                <input type="hidden" name="accion"      id="suc-accion"     value="nuevo">
                <input type="hidden" name="suprestado"  id="suc-suprestado" value="">
                <div class="row g-2">
                    <div class="col-md-3">
                        <label class="suc-lbl">Código Suc.</label>
                        <input type="text" name="sucodigo" id="suc-sucodigo"
                               class="form-control form-control-sm fw-bold font-monospace bg-light"
                               readonly>
                    </div>
                    <div class="col-md-9">
                        <label class="suc-lbl"><span class="text-danger">*</span> Nombre</label>
                        <input type="text" name="sunombre" id="suc-sunombre"
                               class="form-control form-control-sm" maxlength="100">
                    </div>
                    <div class="col-md-6">
                        <label class="suc-lbl">Dirección</label>
                        <input type="text" name="sudir" id="suc-sudir"
                               class="form-control form-control-sm" maxlength="60">
                    </div>
                    <div class="col-md-3">
                        <label class="suc-lbl">Localidad</label>
                        <input type="text" name="sulocalida" id="suc-sulocalida"
                               class="form-control form-control-sm" maxlength="20">
                    </div>
                    <div class="col-md-3">
                        <label class="suc-lbl">Zona</label>
                        <input type="text" name="suzona" id="suc-suzona"
                               class="form-control form-control-sm" maxlength="20">
                    </div>
                    <div class="col-md-3">
                        <label class="suc-lbl">Teléfono</label>
                        <input type="text" name="sutelef" id="suc-sutelef"
                               class="form-control form-control-sm font-monospace" maxlength="70">
                    </div>
                    <div class="col-md-3">
                        <label class="suc-lbl">Contacto</label>
                        <input type="text" name="sucontacto" id="suc-sucontacto"
                               class="form-control form-control-sm" maxlength="60">
                    </div>
                    <div class="col-md-6">
                        <label class="suc-lbl">E-mail (Autorizaciones)</label>
                        <input type="text" name="suemail" id="suc-suemail"
                               class="form-control form-control-sm" maxlength="250">
                    </div>
                    <div class="col-md-3">
                        <label class="suc-lbl">Horario</label>
                        <input type="text" name="suhorario" id="suc-suhorario"
                               class="form-control form-control-sm" maxlength="50">
                    </div>
                    <div class="col-md-9">
                        <label class="suc-lbl">Dirección Mapa</label>
                        <input type="text" name="sudirmap" id="suc-sudirmap"
                               class="form-control form-control-sm" maxlength="200">
                    </div>
                </div>
                </form>
                <div id="suc-form-alert" class="alert alert-dismissible mt-3 py-2 px-3"
                     role="alert" style="display:none;font-size:.8rem;">
                    <span id="suc-form-alert-msg"></span>
                    <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
                </div>
            </div><!-- /#suc-panel-form -->

        </div><!-- /.modal-body -->

        <!-- Footer -->
        <div class="modal-footer py-2 px-3 gap-1 flex-wrap"
             style="background:#f5f7fb;border-top:1px solid #dee2e6;">

            <!-- Botones del GRID -->
            <div id="suc-footer-grid" class="d-flex gap-1 flex-wrap w-100 justify-content-between">
                <div class="d-flex gap-1 flex-wrap">
                    <button id="suc-btn-agregar"   class="btn btn-sm btn-success">
                        <i class="fa-solid fa-plus me-1"></i>Agregar
                    </button>
                    <button id="suc-btn-modificar" class="btn btn-sm btn-warning text-white" disabled>
                        <i class="fa-solid fa-pen me-1"></i>Modificar
                    </button>
                    <button id="suc-btn-borrar"    class="btn btn-sm btn-danger" disabled>
                        <i class="fa-solid fa-trash me-1"></i>Borrar
                    </button>
                    <span style="border-left:1px solid #dee2e6;height:24px;align-self:center;margin:0 3px;"></span>
                    <button id="suc-btn-exclu"     class="btn btn-sm btn-outline-info" disabled>
                        <i class="fa-solid fa-ban me-1"></i>Excluir OS Cartilla
                    </button>
                    <button id="suc-btn-prac"      class="btn btn-sm btn-outline-secondary" disabled>
                        <i class="fa-solid fa-syringe me-1"></i>Prácticas
                    </button>
                </div>
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">
                    <i class="fa-solid fa-xmark me-1"></i>Cerrar
                </button>
            </div>

            <!-- Botones del FORMULARIO -->
            <div id="suc-footer-form" class="d-flex gap-1 w-100 justify-content-end" style="display:none!important;">
                <button id="suc-btn-cancelar-form" class="btn btn-sm btn-secondary">
                    <i class="fa-solid fa-arrow-left me-1"></i>Volver
                </button>
                <button id="suc-btn-guardar" class="btn btn-sm btn-primary">
                    <i class="fa-solid fa-floppy-disk me-1"></i>Guardar
                </button>
            </div>

        </div>

    </div></div>
</div>

<!-- ══════════════════════════════════════════════════════════════════════
     SUB-MODAL: EXCLUIR OS DE CARTILLA
═══════════════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="modalSucExcluOS" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content" style="border-radius:10px;overflow:hidden;">
        <div class="modal-header py-2 px-4"
             style="background:#1565c0;color:#fff;border-bottom:none;">
            <h6 class="modal-title fw-bold mb-0">
                <i class="fa-solid fa-ban me-2"></i>Excluir Sucursal de Cartilla — O.S.
            </h6>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-0">
            <div class="px-3 py-2 bg-light" style="font-size:.78rem;border-bottom:1px solid #dee2e6;">
                <i class="fa-solid fa-circle-info me-1 text-info"></i>
                Activar excluye la sucursal <strong id="exclu-suc-label"></strong>
                de la cartilla de esa Obra Social.
            </div>
            <div id="exclu-loading" class="text-center py-4">
                <div class="spinner-border spinner-border-sm text-primary"></div>
            </div>
            <div id="exclu-list-wrap" class="p-3" style="display:none;">
                <div class="table-responsive">
                    <table class="table table-sm table-hover table-bordered mb-0"
                           style="font-size:.8rem;">
                        <thead style="background:#e8edf5;font-size:.74rem;">
                            <tr>
                                <th style="width:90px;padding:5px 8px;">Código OS</th>
                                <th style="padding:5px 8px;">Nombre OS</th>
                                <th style="width:100px;padding:5px 8px;text-align:center;">Excluida</th>
                            </tr>
                        </thead>
                        <tbody id="exclu-os-tbody"></tbody>
                    </table>
                </div>
                <p id="exclu-empty" class="text-center text-muted py-3 mb-0" style="display:none;font-size:.83rem;">
                    El prestador no tiene Obras Sociales activas.
                </p>
            </div>
        </div>
        <div class="modal-footer py-2 px-3">
            <button class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cerrar</button>
        </div>
    </div></div>
</div>

<!-- ══════════════════════════════════════════════════════════════════════
     SUB-MODAL: PRÁCTICAS DE LA SUCURSAL
═══════════════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="modalSucPracticas" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content" style="border-radius:10px;overflow:hidden;">
        <div class="modal-header py-2 px-4"
             style="background:#1565c0;color:#fff;border-bottom:none;">
            <h6 class="modal-title fw-bold mb-0">
                <i class="fa-solid fa-syringe me-2"></i>Prácticas — <span id="prac-suc-label"></span>
            </h6>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-0">
            <!-- Búsqueda -->
            <div class="px-3 pt-3 pb-2 d-flex gap-2" style="background:#f8f9fc;">
                <div class="input-group input-group-sm" style="max-width:280px;">
                    <span class="input-group-text"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                    <input type="text" id="prac-busqueda" class="form-control"
                           placeholder="Buscar práctica…" autocomplete="off">
                </div>
                <button id="prac-btn-buscar" class="btn btn-sm btn-primary">Buscar</button>
                <span id="prac-total-badge" class="badge bg-secondary ms-auto align-self-center" style="font-size:.72rem;"></span>
            </div>
            <!-- Tabla -->
            <div class="px-3 pb-2">
                <div class="table-responsive">
                    <table class="table table-sm table-hover table-bordered mb-0"
                           id="tbl-prac-suc" style="font-size:.78rem;white-space:nowrap;">
                        <thead style="background:#e8edf5;font-size:.73rem;">
                            <tr>
                                <th data-sort="practica" class="prac-th" style="width:80px;">Código<span class="prac-ind"> ↕</span></th>
                                <th data-sort="nombre"   class="prac-th" style="min-width:200px;">Nombre<span class="prac-ind"> ↕</span></th>
                                <th data-sort="grupo"    class="prac-th" style="width:90px;">Grupo<span class="prac-ind"> ↕</span></th>
                                <th data-sort="tipo"     class="prac-th" style="width:60px;">Tipo<span class="prac-ind"> ↕</span></th>
                                <th data-sort="importe"  class="prac-th" style="width:80px;text-align:right;">Importe<span class="prac-ind"> ↕</span></th>
                            </tr>
                        </thead>
                        <tbody id="prac-suc-tbody">
                            <tr><td colspan="5" class="text-center text-muted py-3">Cargando…</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <!-- Paginación prácticas -->
            <div id="prac-footer" class="px-3 py-2 d-flex align-items-center justify-content-between"
                 style="font-size:.77rem;background:#f5f7fb;display:none!important;">
                <span id="prac-footer-info" class="text-muted">—</span>
                <ul class="pagination pagination-sm mb-0" id="prac-pagination"></ul>
            </div>
        </div>
        <div class="modal-footer py-2 px-3">
            <button class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cerrar</button>
        </div>
    </div></div>
</div>

<style>
.suc-lbl  { font-size:.75rem;font-weight:600;margin-bottom:2px;color:#495057; }
.suc-th   { cursor:pointer;user-select:none;padding:5px 8px; }
.suc-th:hover { background:#d0d8f0!important; }
.suc-th .suc-ind { font-size:.68rem;opacity:.3;margin-left:2px; }
.suc-th.sorted-asc .suc-ind, .suc-th.sorted-desc .suc-ind { opacity:1;color:#0d6efd; }
.prac-th  { cursor:pointer;user-select:none;padding:5px 8px; }
.prac-th:hover { background:#d0d8f0!important; }
.prac-th .prac-ind { font-size:.68rem;opacity:.3;margin-left:2px; }
.prac-th.sorted-asc .prac-ind, .prac-th.sorted-desc .prac-ind { opacity:1;color:#0d6efd; }
tr.suc-selected td { background:#e3f2fd!important; }
</style>

<script>
(function () {
'use strict';

/* ── Estado ─────────────────────────────────────────────────────── */
var _codigo   = '';
var _nombre   = '';
var _sucSel   = null;   // fila seleccionada { sucodigo, sunombre, ... }
var _csrf     = <?= json_encode($csrfToken) ?>;
var BASE      = 'index.php';

/* ── Paginación + sort (grilla principal) ────────────────────────── */
var sucState  = { pagina:1, total:0, paginas:1, busqueda:'', cargando:false };
var sucSort   = { col:'sucodigo', dir:'asc' };
var sucRows   = [];

/* ── Paginación + sort (prácticas) ──────────────────────────────── */
var pracState = { pagina:1, total:0, paginas:1, busqueda:'' };
var pracSort  = { col:'practica', dir:'asc' };
var pracRows  = [];

/* ── DOM helpers ─────────────────────────────────────────────────── */
var $ = function (id) { return document.getElementById(id); };
var $modal       = $('modalSucursales');
var $loading     = $('suc-loading');
var $tableWrap   = $('suc-table-wrap');
var $tbody       = $('suc-tbody');
var $empty       = $('suc-empty');
var $footer      = $('suc-footer');
var $footInfo    = $('suc-footer-info');
var $pagination  = $('suc-pagination');
var $totalBadge  = $('suc-total-badge');
var $panelGrid   = $('suc-panel-grid');
var $panelForm   = $('suc-panel-form');
var $footerGrid  = $('suc-footer-grid');
var $footerForm  = $('suc-footer-form');

/* ════════════════════════════════════════════════════════════════
 * API PÚBLICA
 * ═════════════════════════════════════════════════════════════= */
window.abrirModalSucursales = function (codigo, nombre) {
    _codigo  = String(codigo || '').trim();
    _nombre  = String(nombre || '').trim();
    if (!_codigo) { alert('Seleccione un prestador.'); return; }

    $('suc-header-codigo').textContent = _codigo;
    $('suc-header-nombre').textContent = _nombre;
    $('suc-busqueda').value = '';
    sucState.busqueda = '';
    sucState.pagina   = 1;
    mostrarPanelGrid();

    bootstrap.Modal.getOrCreateInstance($modal).show();
    cargarSucursales();
};

/* ════════════════════════════════════════════════════════════════
 * CARGA PRINCIPAL
 * ═════════════════════════════════════════════════════════════= */
function cargarSucursales(pagina) {
    if (sucState.cargando) return;
    sucState.cargando = true;
    sucState.pagina   = pagina || 1;
    sucState.busqueda = $('suc-busqueda').value.trim();

    $loading.style.display   = 'block';
    $tableWrap.style.display = 'none';
    $footer.style.display    = 'none';

    var qs = 'route=prestadores&action=suc_listar'
        + '&codigo='    + encodeURIComponent(_codigo)
        + '&busqueda='  + encodeURIComponent(sucState.busqueda)
        + '&pagina='    + sucState.pagina
        + '&por_pagina=25';

    fetch(BASE + '?' + qs, { headers: {'X-Requested-With':'XMLHttpRequest'} })
    .then(function (r) { return r.json(); })
    .then(function (res) {
        sucState.cargando = false;
        if (!res.ok) { alert(res.error || 'Error al cargar.'); return; }
        sucState.total   = res.total   || 0;
        sucState.paginas = res.paginas || 1;
        sucRows = res.datos || [];
        $totalBadge.textContent = sucState.total + ' sucursal' + (sucState.total !== 1 ? 'es' : '');
        sortAndRenderSuc();
        renderPagSuc();
    })
    .catch(function () { sucState.cargando = false; alert('Error de comunicación.'); });
}

/* ── Ordenamiento sucursales ─────────────────────────────────────── */
function sortAndRenderSuc() {
    var col = sucSort.col, dir = sucSort.dir;
    var sorted = sucRows.slice().sort(function (a, b) {
        var av = String(a[col] || '').toLowerCase();
        var bv = String(b[col] || '').toLowerCase();
        return dir === 'asc' ? av.localeCompare(bv) : bv.localeCompare(av);
    });
    renderSucTabla(sorted);
    updateSucSortInd();
}

document.querySelectorAll('#tbl-sucursales thead th[data-sort]').forEach(function (th) {
    th.addEventListener('click', function () {
        var col = this.dataset.sort;
        sucSort.dir = sucSort.col === col && sucSort.dir === 'asc' ? 'desc' : 'asc';
        sucSort.col = col;
        sortAndRenderSuc();
    });
});

function updateSucSortInd() {
    document.querySelectorAll('#tbl-sucursales thead th[data-sort]').forEach(function (th) {
        var ind = th.querySelector('.suc-ind');
        if (!ind) return;
        th.classList.remove('sorted-asc','sorted-desc');
        if (th.dataset.sort === sucSort.col) {
            th.classList.add('sorted-' + sucSort.dir);
            ind.textContent = sucSort.dir === 'asc' ? ' ↑' : ' ↓';
        } else { ind.textContent = ' ↕'; }
    });
}

/* ── Render tabla sucursales ─────────────────────────────────────── */
function renderSucTabla(rows) {
    $loading.style.display   = 'none';
    $tableWrap.style.display = 'block';
    $tbody.innerHTML = '';
    _sucSel = null;
    actualizarBotonesFilas();

    if (!rows.length) {
        $empty.style.display = 'block';
        $tbody.closest('table').style.display = 'none';
        $footer.style.display = 'none';
        return;
    }
    $empty.style.display = 'none';
    $tbody.closest('table').style.display = '';

    rows.forEach(function (r) {
        var tr = document.createElement('tr');
        tr.style.cursor = 'pointer';

        // Radio selector
        var radio = '<td style="padding:3px 6px;text-align:center;vertical-align:middle;">'
            + '<input type="radio" name="suc-sel" class="form-check-input suc-radio" style="cursor:pointer;" data-sucodigo="'
            + esc(r.sucodigo) + '"></td>';

        tr.innerHTML = radio
            + '<td style="padding:4px 8px;font-family:monospace;font-weight:600;">'
            + '<span class="badge bg-primary bg-opacity-10 text-primary border border-primary" style="font-size:.72rem;">'
            + esc(r.sucodigo) + '</span></td>'
            + '<td style="padding:4px 8px;">' + esc(r.sunombre) + '</td>'
            + '<td style="padding:4px 8px;">' + esc(r.sudir) + '</td>'
            + '<td style="padding:4px 8px;">' + esc(r.sulocalida) + '</td>'
            + '<td style="padding:4px 8px;">' + esc(r.suzona) + '</td>'
            + '<td style="padding:4px 8px;font-family:monospace;font-size:.73rem;">' + esc(r.sutelef) + '</td>'
            + '<td style="padding:4px 8px;">' + esc(r.sucontacto) + '</td>'
            + '<td style="padding:4px 8px;font-size:.72rem;">' + esc(r.suemail) + '</td>';

        tr.dataset.row = JSON.stringify(r);
        $tbody.appendChild(tr);
    });

    // Selección de fila
    $tbody.querySelectorAll('.suc-radio').forEach(function (radio) {
        radio.addEventListener('change', function () {
            $tbody.querySelectorAll('tr').forEach(function (t) { t.classList.remove('suc-selected'); });
            var tr = this.closest('tr');
            tr.classList.add('suc-selected');
            _sucSel = JSON.parse(tr.dataset.row || '{}');
            actualizarBotonesFilas();
        });
    });
    $tbody.querySelectorAll('tr').forEach(function (tr) {
        tr.addEventListener('click', function () {
            var radio = this.querySelector('.suc-radio');
            if (radio) { radio.checked = true; radio.dispatchEvent(new Event('change')); }
        });
    });

    $footer.style.removeProperty('display');
    $footer.style.display = sucState.paginas > 1 ? '' : 'none';
}

function actualizarBotonesFilas() {
    var sel = !!_sucSel;
    $('suc-btn-modificar').disabled = !sel;
    $('suc-btn-borrar').disabled    = !sel;
    $('suc-btn-exclu').disabled     = !sel;
    $('suc-btn-prac').disabled      = !sel;
}

/* ── Paginación ─────────────────────────────────────────────────── */
function renderPagSuc() {
    var de = (sucState.pagina - 1) * 25 + 1;
    var a  = Math.min(sucState.pagina * 25, sucState.total);
    $footInfo.textContent = 'Mostrando ' + de + '–' + a + ' de ' + sucState.total;
    $pagination.innerHTML = '';
    if (sucState.paginas <= 1) { $footer.style.display = 'none'; return; }
    addPagBtn($pagination, '«', sucState.pagina > 1 ? sucState.pagina - 1 : null, sucState.pagina <= 1, false);
    for (var p = Math.max(1, sucState.pagina - 3); p <= Math.min(sucState.paginas, sucState.pagina + 3); p++) {
        addPagBtn($pagination, p, p, false, p === sucState.pagina);
    }
    addPagBtn($pagination, '»', sucState.pagina < sucState.paginas ? sucState.pagina + 1 : null,
              sucState.pagina >= sucState.paginas, false);
    $footer.style.removeProperty('display');
    $footer.style.display = '';
}
function addPagBtn(container, label, target, disabled, active) {
    var li = document.createElement('li');
    li.className = 'page-item' + (disabled?' disabled':'') + (active?' active':'');
    var a = document.createElement('a'); a.className='page-link'; a.href='#'; a.innerHTML=String(label);
    if (target && !disabled && !active) a.addEventListener('click', function(e){e.preventDefault();cargarSucursales(target);});
    li.appendChild(a); container.appendChild(li);
}

/* ════════════════════════════════════════════════════════════════
 * PANEL FORM: AGREGAR / MODIFICAR
 * ═════════════════════════════════════════════════════════════= */
$('suc-btn-agregar').addEventListener('click', function () {
    abrirForm('nuevo', null);
});
$('suc-btn-modificar').addEventListener('click', function () {
    if (_sucSel) abrirForm('modificar', _sucSel);
});
$('suc-btn-cancelar-form').addEventListener('click', function () {
    mostrarPanelGrid();
});

function abrirForm(accion, row) {
    $('suc-form-titulo').innerHTML = accion === 'nuevo'
        ? '<i class="fa-solid fa-plus-circle me-1"></i>Nueva Sucursal'
        : '<i class="fa-solid fa-pen me-1"></i>Modificar Sucursal';
    $('suc-accion').value     = accion;
    $('suc-suprestado').value = _codigo;
    $('suc-form-alert').style.display = 'none';

    if (accion === 'nuevo') {
        // Limpiar + generar código
        $('suc-form').querySelectorAll('input:not([type=hidden])').forEach(function(el){el.value='';});
        fetch(BASE + '?route=prestadores&action=suc_defaults&codigo=' + encodeURIComponent(_codigo),
              { headers: {'X-Requested-With':'XMLHttpRequest'} })
        .then(function(r){return r.json();})
        .then(function(res){ if (res.ok) $('suc-sucodigo').value = res.sucodigo; });
    } else {
        $('suc-sucodigo').value   = row.sucodigo;
        $('suc-sunombre').value   = row.sunombre   || '';
        $('suc-sudir').value      = row.sudir      || '';
        $('suc-sulocalida').value = row.sulocalida || '';
        $('suc-suzona').value     = row.suzona     || '';
        $('suc-sutelef').value    = row.sutelef    || '';
        $('suc-sucontacto').value = row.sucontacto || '';
        $('suc-suemail').value    = row.suemail    || '';
        $('suc-suhorario').value  = row.suhorario  || '';
        $('suc-sudirmap').value   = row.sudirmap   || '';
    }
    mostrarPanelForm();
}

$('suc-btn-guardar').addEventListener('click', function () {
    if (!$('suc-sunombre').value.trim()) {
        showFormAlert('warning', 'El Nombre es obligatorio.');
        return;
    }
    var btn = this;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Guardando…';
    $('suc-form-alert').style.display = 'none';

    var fd = new FormData($('suc-form'));
    fetch(BASE + '?route=prestadores&action=suc_guardar', {
        method: 'POST', headers: {'X-Requested-With':'XMLHttpRequest'}, body: fd
    })
    .then(function(r){return r.json();})
    .then(function(res){
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-floppy-disk me-1"></i>Guardar';
        if (res.ok) {
            mostrarPanelGrid();
            cargarSucursales();
        } else {
            showFormAlert('danger', res.error || 'Error al guardar.');
        }
    })
    .catch(function(){
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-floppy-disk me-1"></i>Guardar';
        showFormAlert('danger', 'Error de comunicación.');
    });
});

function showFormAlert(tipo, msg) {
    var el = $('suc-form-alert');
    el.className = 'alert alert-' + tipo + ' alert-dismissible mt-3 py-2 px-3';
    $('suc-form-alert-msg').textContent = msg;
    el.style.display = 'block';
}

/* ── Borrar ──────────────────────────────────────────────────────── */
$('suc-btn-borrar').addEventListener('click', function () {
    if (!_sucSel) return;
    if (!confirm('¿Confirma eliminar la sucursal «' + _sucSel.sucodigo + ' — ' + _sucSel.sunombre + '»?\nEsta acción no se puede deshacer.')) return;
    var fd = new FormData();
    fd.append('csrf_token', _csrf);
    fd.append('suprestado', _codigo);
    fd.append('sucodigo',   _sucSel.sucodigo);
    fetch(BASE + '?route=prestadores&action=suc_borrar', {
        method: 'POST', headers: {'X-Requested-With':'XMLHttpRequest'}, body: fd
    })
    .then(function(r){return r.json();})
    .then(function(res){
        if (res.ok) cargarSucursales();
        else alert(res.error || 'Error al borrar.');
    });
});

/* ════════════════════════════════════════════════════════════════
 * EXCLUIR OS DE CARTILLA
 * ═════════════════════════════════════════════════════════════= */
$('suc-btn-exclu').addEventListener('click', function () {
    if (!_sucSel) return;
    $('exclu-suc-label').textContent = _sucSel.sucodigo + ' — ' + _sucSel.sunombre;
    $('exclu-loading').style.display    = 'block';
    $('exclu-list-wrap').style.display  = 'none';
    bootstrap.Modal.getOrCreateInstance($('modalSucExcluOS')).show();
    cargarExcluOS();
});

function cargarExcluOS() {
    fetch(BASE + '?route=prestadores&action=suc_exclu_listar'
        + '&codigo='   + encodeURIComponent(_codigo)
        + '&sucodigo=' + encodeURIComponent(_sucSel.sucodigo),
        { headers: {'X-Requested-With':'XMLHttpRequest'} })
    .then(function(r){return r.json();})
    .then(function(res){
        $('exclu-loading').style.display = 'none';
        $('exclu-list-wrap').style.display = 'block';
        var tbody = $('exclu-os-tbody');
        tbody.innerHTML = '';
        if (!res.ok || !res.data.length) {
            $('exclu-empty').style.display = 'block'; return;
        }
        $('exclu-empty').style.display = 'none';
        res.data.forEach(function (r) {
            var tr = document.createElement('tr');
            var chkId = 'exclu-chk-' + r.cosoc.replace(/\W/g,'');
            tr.innerHTML = '<td style="padding:5px 10px;font-family:monospace;font-size:.78rem;">' + esc(r.cosoc) + '</td>'
                + '<td style="padding:5px 10px;font-size:.8rem;">' + esc(r.nombre) + '</td>'
                + '<td style="padding:5px 10px;text-align:center;">'
                + '<div class="form-check form-switch d-inline-block mb-0">'
                + '<input class="form-check-input exclu-toggle" type="checkbox" id="' + chkId + '"'
                + ' data-cosoc="' + esc(r.cosoc) + '" data-nombre="' + esc(r.nombre) + '"'
                + (r.excluida == 1 ? ' checked' : '') + '>'
                + '</div></td>';
            tbody.appendChild(tr);
        });

        // Wiring toggle
        tbody.querySelectorAll('.exclu-toggle').forEach(function (chk) {
            chk.addEventListener('change', function () {
                var cosoc  = this.dataset.cosoc;
                var nombre = this.dataset.nombre;
                var fd = new FormData();
                fd.append('csrf_token', _csrf);
                fd.append('codigo',   _codigo);
                fd.append('sucodigo', _sucSel.sucodigo);
                fd.append('cosoc',    cosoc);
                fd.append('nombre',   nombre);
                fetch(BASE + '?route=prestadores&action=suc_exclu_toggle', {
                    method:'POST', headers:{'X-Requested-With':'XMLHttpRequest'}, body:fd
                })
                .then(function(r){return r.json();})
                .then(function(res){
                    if (!res.ok) { alert(res.error || 'Error.'); cargarExcluOS(); }
                });
            });
        });
    })
    .catch(function(){ $('exclu-loading').style.display='none'; alert('Error de comunicación.'); });
}

/* ════════════════════════════════════════════════════════════════
 * PRÁCTICAS DE LA SUCURSAL
 * ═════════════════════════════════════════════════════════════= */
$('suc-btn-prac').addEventListener('click', function () {
    if (!_sucSel) return;
    $('prac-suc-label').textContent = _sucSel.sucodigo + ' — ' + _sucSel.sunombre;
    $('prac-busqueda').value = '';
    pracState.busqueda = '';
    pracState.pagina   = 1;
    bootstrap.Modal.getOrCreateInstance($('modalSucPracticas')).show();
    cargarPracticas();
});

function cargarPracticas(pagina) {
    pracState.pagina   = pagina || 1;
    pracState.busqueda = $('prac-busqueda').value.trim();

    $('prac-suc-tbody').innerHTML = '<tr><td colspan="5" class="text-center text-muted py-3">'
        + '<span class="spinner-border spinner-border-sm"></span></td></tr>';

    var qs = 'route=prestadores&action=suc_prac_listar'
        + '&codigo='    + encodeURIComponent(_codigo)
        + '&sucodigo='  + encodeURIComponent(_sucSel.sucodigo)
        + '&busqueda='  + encodeURIComponent(pracState.busqueda)
        + '&pagina='    + pracState.pagina
        + '&por_pagina=25';

    fetch(BASE + '?' + qs, { headers: {'X-Requested-With':'XMLHttpRequest'} })
    .then(function(r){return r.json();})
    .then(function(res){
        if (!res.ok) { $('prac-suc-tbody').innerHTML='<tr><td colspan="5" class="text-center text-danger py-2">' + esc(res.error||'Error') + '</td></tr>'; return; }
        pracState.total   = res.total;
        pracState.paginas = res.paginas;
        pracRows = res.datos || [];
        $('prac-total-badge').textContent = pracState.total + ' prácticas';
        sortAndRenderPrac();
        renderPagPrac();
    })
    .catch(function(){}); 
}

function sortAndRenderPrac() {
    var col = pracSort.col, dir = pracSort.dir;
    var sorted = pracRows.slice().sort(function(a,b){
        var av=String(a[col]||'').toLowerCase(), bv=String(b[col]||'').toLowerCase();
        return dir==='asc'?av.localeCompare(bv):bv.localeCompare(av);
    });
    var tbody = $('prac-suc-tbody');
    tbody.innerHTML = '';
    if (!sorted.length) {
        tbody.innerHTML='<tr><td colspan="5" class="text-center text-muted py-3">Sin prácticas registradas.</td></tr>'; return;
    }
    sorted.forEach(function(r){
        var tr=document.createElement('tr');
        tr.innerHTML='<td style="padding:4px 8px;font-family:monospace;">' + esc(r.practica) + '</td>'
            +'<td style="padding:4px 8px;">' + esc(r.nombre) + '</td>'
            +'<td style="padding:4px 8px;text-align:center;">' + esc(r.grupo) + '</td>'
            +'<td style="padding:4px 8px;text-align:center;">' + esc(r.tipo) + '</td>'
            +'<td style="padding:4px 8px;text-align:right;font-family:monospace;">' + (r.importe ? parseFloat(r.importe).toFixed(2) : '—') + '</td>';
        tbody.appendChild(tr);
    });
    updatePracSortInd();
}

document.querySelectorAll('#tbl-prac-suc thead th[data-sort]').forEach(function(th){
    th.addEventListener('click', function(){
        var col=this.dataset.sort;
        pracSort.dir=pracSort.col===col&&pracSort.dir==='asc'?'desc':'asc';
        pracSort.col=col;
        sortAndRenderPrac();
    });
});
function updatePracSortInd(){
    document.querySelectorAll('#tbl-prac-suc thead th[data-sort]').forEach(function(th){
        var ind=th.querySelector('.prac-ind'); if(!ind)return;
        th.classList.remove('sorted-asc','sorted-desc');
        if(th.dataset.sort===pracSort.col){th.classList.add('sorted-'+pracSort.dir);ind.textContent=pracSort.dir==='asc'?' ↑':' ↓';}
        else ind.textContent=' ↕';
    });
}

function renderPagPrac(){
    var pf=$('prac-footer'); var pp=$('prac-pagination');
    var de=(pracState.pagina-1)*25+1, a=Math.min(pracState.pagina*25,pracState.total);
    $('prac-footer-info').textContent='Mostrando '+de+'–'+a+' de '+pracState.total;
    pp.innerHTML='';
    if(pracState.paginas<=1){pf.style.display='none';return;}
    addPagBtn(pp,'«',pracState.pagina>1?pracState.pagina-1:null,pracState.pagina<=1,false);
    for(var p=Math.max(1,pracState.pagina-3);p<=Math.min(pracState.paginas,pracState.pagina+3);p++)
        addPagBtn(pp,p,p,false,p===pracState.pagina);
    addPagBtn(pp,'»',pracState.pagina<pracState.paginas?pracState.pagina+1:null,pracState.pagina>=pracState.paginas,false);
    pf.style.removeProperty('display'); pf.style.display='';
}

$('prac-btn-buscar').addEventListener('click', function(){cargarPracticas(1);});
$('prac-busqueda').addEventListener('keydown', function(e){if(e.key==='Enter'){e.preventDefault();cargarPracticas(1);}});

/* ── Buscar en grilla principal ──────────────────────────────────── */
$('suc-btn-buscar').addEventListener('click', function(){cargarSucursales(1);});
$('suc-busqueda').addEventListener('keydown', function(e){if(e.key==='Enter'){e.preventDefault();cargarSucursales(1);}});

/* ── Mostrar/ocultar paneles ─────────────────────────────────────── */
function mostrarPanelGrid() {
    $panelGrid.style.display  = 'block';
    $panelForm.style.display  = 'none';
    $footerGrid.style.display = '';
    $footerForm.style.removeProperty('display');
    $footerForm.style.display = 'none';
}
function mostrarPanelForm() {
    $panelGrid.style.display  = 'none';
    $panelForm.style.display  = 'block';
    $footerGrid.style.display = 'none';
    $footerForm.style.removeProperty('display');
    $footerForm.style.display = '';
}

/* ── Helper ──────────────────────────────────────────────────────── */
function esc(s) {
    var d=document.createElement('div');
    d.appendChild(document.createTextNode(String(s||'')));
    return d.innerHTML;
}

})();
</script>
