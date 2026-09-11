<?php
/**
 * views/prestadores/index.php
 * Módulo: Archivos → Prestadores
 * Grilla real conectada a la tabla `ebamp` vía AJAX.
 */

$pageTitle  = 'COMEDICA — Prestadores';
$breadcrumb = [
    ['label' => 'Archivos'],
    ['label' => 'Prestadores'],
];

require_once __DIR__ . '/../../views/layouts/header.php';

// Permiso para gestionar OS (mismo permiso del módulo, sub-acción)
$puedeGestionarOS = false;
foreach (($_SESSION['permisos'] ?? []) as $p) {
    if (isset($p['CLAVE']) && $p['CLAVE'] === 'MNU_ARC_PRESTADORES') {
        $puedeGestionarOS = true;
        break;
    }
}
?>

<!-- ═══════════════ CABECERA DEL MÓDULO ══════════════════════════════════ -->
<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
    <div>
        <h5 class="mb-1 fw-bold text-primary">
            <i class="fa-solid fa-stethoscope me-2"></i>Módulo de Prestadores
        </h5>
        <p class="text-muted mb-0" style="font-size:0.82rem;">
            Archivos &rsaquo; Prestadores &mdash; Padrón de prestadores médicos
        </p>
    </div>
    <div class="d-flex gap-2 align-items-center flex-wrap">
        <span id="prest-total-badge"
              class="badge bg-secondary bg-opacity-75 px-3 py-2"
              style="font-size:0.8rem;">
            <i class="fa-solid fa-list-ol me-1"></i>Cargando…
        </span>
        <button id="btn-nuevo-prestador"
                class="btn btn-sm btn-success"
                title="Agregar nuevo prestador">
            <i class="fa-solid fa-user-plus me-1"></i>Nuevo
        </button>
    </div>
</div>

<!-- ═══════════════ BARRA DE FILTROS ════════════════════════════════════ -->
<div class="card border-0 shadow-sm mb-3" style="border-radius:10px;">
    <div class="card-body py-2 px-3">
        <div class="row g-2 align-items-center">

            <!-- Búsqueda libre -->
            <div class="col-md-4">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light">
                        <i class="fa-solid fa-magnifying-glass text-muted"></i>
                    </span>
                    <input type="text" id="flt-busqueda" class="form-control"
                           placeholder="Buscar por nombre, código, CUIT…"
                           autocomplete="off">
                </div>
            </div>

            <!-- Categoría -->
            <div class="col-md-3">
                <select id="flt-categ" class="form-select form-select-sm">
                    <option value="">— Todas las categorías —</option>
                </select>
            </div>

            <!-- Ocultar inactivos -->
            <div class="col-md-3 d-flex align-items-center ps-3">
                <div class="form-check form-switch mb-0">
                    <input class="form-check-input" type="checkbox"
                           id="flt-ocultar-inactivos" checked
                           style="width:2.4em; height:1.25em; cursor:pointer;">
                    <label class="form-check-label ms-2" for="flt-ocultar-inactivos"
                           style="font-size:0.82rem; cursor:pointer; white-space:nowrap;">
                        Ocultar inactivos
                    </label>
                </div>
            </div>

            <!-- Botón buscar -->
            <div class="col-md-2">
                <button id="btn-buscar"
                        class="btn btn-primary btn-sm w-100">
                    <i class="fa-solid fa-search me-1"></i>Buscar
                </button>
            </div>

        </div>
    </div>
</div>

<!-- ═══════════════ GRILLA PRINCIPAL ════════════════════════════════════ -->
<div class="card card-module">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2 py-2">
        <span style="font-size:0.88rem;">
            <i class="fa-solid fa-table me-2"></i>Listado de Prestadores
        </span>
        <!-- Leyenda de colores -->
        <div class="d-flex flex-wrap gap-1 align-items-center" style="font-size:0.7rem;">
            <span class="px-2 py-1 rounded fw-semibold" style="background:#e91e63;color:#fff;border:1px solid #c2185b;">
                Sin Sucursal
            </span>
            <span class="px-2 py-1 rounded fw-semibold" style="background:#ff9800;color:#fff;border:1px solid #f57c00;">
                Sin O.Social
            </span>
            <span class="px-2 py-1 rounded fw-semibold" style="background:#ffc107;color:#212529;border:1px solid #ffa000;">
                Sin Prestaciones
            </span>
            <span class="px-2 py-1 rounded fw-semibold" style="background:#9c27b0;color:#fff;border:1px solid #7b1fa2;">
                Sin Práctica Suc.
            </span>
            <span class="px-2 py-1 rounded fw-semibold" style="background:#ede7f6;color:#6a1b9a;border:1px solid #ce93d8;">
                De baja
            </span>
        </div>
    </div>

    <!-- Spinner de carga -->
    <div id="prest-loading" class="text-center py-5">
        <div class="spinner-border text-primary" role="status"></div>
        <p class="text-muted mt-2 mb-0" style="font-size:0.85rem;">Cargando prestadores…</p>
    </div>

    <!-- Tabla (oculta hasta que carguen los datos) -->
    <div id="prest-table-wrap" class="p-0" style="display:none;">
        <div class="table-responsive">
            <table class="table table-hover table-bordered mb-0"
                   id="tbl-prestadores"
                   style="font-size:0.76rem; white-space:nowrap;">
                <thead>
                    <tr style="background:#e8edf5; font-size:0.72rem; text-align:center;">
                        <th style="width:32px; padding:5px 6px;"></th>
                        <th data-sort="nombre"    style="min-width:170px;padding:5px 8px;text-align:left;">Nombre<span class="sort-ind">↕</span></th>
                        <th data-sort="direcc"    style="min-width:130px;padding:5px 8px;text-align:left;">Dirección<span class="sort-ind">↕</span></th>
                        <th data-sort="zona"      style="width:120px;padding:5px 8px;">Zona<span class="sort-ind">↕</span></th>
                        <th data-sort="localidad" style="width:100px;padding:5px 8px;">Localidad<span class="sort-ind">↕</span></th>
                        <th data-sort="telcons"   style="width:110px;padding:5px 8px;">Tel. Cons.<span class="sort-ind">↕</span></th>
                        <th data-sort="celular"   style="width:90px; padding:5px 8px;">Particular<span class="sort-ind">↕</span></th>
                        <th data-sort="codigo"    style="width:80px; padding:5px 8px;">Código<span class="sort-ind">↕</span></th>
                        <th data-sort="grupoweb"  style="width:90px; padding:5px 8px;">Grupo Web<span class="sort-ind">↕</span></th>
                        <th data-sort="categ"     style="width:90px; padding:5px 8px;">Categ.<span class="sort-ind">↕</span></th>
                        <th data-sort="fechabaja" style="width:82px; padding:5px 8px;">F. Baja<span class="sort-ind">↕</span></th>
                        <th data-sort="exclucart"  style="width:38px;padding:5px 3px;" title="Exclusión Cartilla">ExCa<span class="sort-ind">↕</span></th>
                        <th data-sort="tienesuc"   style="width:38px;padding:5px 3px;" title="Tiene Sucursal">Suc<span class="sort-ind">↕</span></th>
                        <th data-sort="tienesucpr" style="width:38px;padding:5px 3px;" title="Sucursal con Práctica">S/P<span class="sort-ind">↕</span></th>
                        <th data-sort="trabajaos"  style="width:38px;padding:5px 3px;" title="Tiene Obra Social">OS<span class="sort-ind">↕</span></th>
                        <th data-sort="tieneprest" style="width:38px;padding:5px 3px;" title="Tiene Prestaciones">Prac<span class="sort-ind">↕</span></th>
                        <th data-sort="contacto"  style="min-width:100px;padding:5px 8px;text-align:left;">Contacto<span class="sort-ind">↕</span></th>
                    </tr>
                </thead>
                <tbody id="prest-tbody"></tbody>
            </table>
        </div>

        <!-- Sin resultados -->
        <div id="prest-empty" style="display:none;"
             class="text-center py-5 px-4">
            <i class="fa-solid fa-user-slash fa-2x text-muted mb-3 d-block" style="opacity:.4;"></i>
            <p class="text-muted mb-0" style="font-size:0.88rem;">
                No se encontraron prestadores con los filtros seleccionados.
            </p>
        </div>
    </div>

    <!-- Pie: paginación -->
    <div id="prest-footer"
         class="card-footer d-flex align-items-center justify-content-between flex-wrap gap-2 py-2"
         style="background:#f5f7fb; font-size:0.79rem; color:#546e7a; display:none !important;">
        <span id="prest-footer-info">—</span>
        <nav aria-label="Paginación prestadores">
            <ul class="pagination pagination-sm mb-0" id="prest-pagination">
            </ul>
        </nav>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════════════
     MODAL OBRAS SOCIALES HABILITADAS
     ═══════════════════════════════════════════════════════════════════════ -->
<?php require_once __DIR__ . '/_modal_obras_sociales.php'; ?>
<?php require_once __DIR__ . '/_modal_editar_prestador.php'; ?>
<?php require_once __DIR__ . '/_modal_sucursales.php'; ?>


<!-- ═══════════════════════════════════════════════════════════════════════
     JAVASCRIPT: Grilla + filtros + wiring del modal
     ═══════════════════════════════════════════════════════════════════════ -->
<script>
(function () {
    'use strict';

    /* ── Constantes ────────────────────────────────────────────────────── */
    var BASE_URL  = 'index.php';
    var POR_PAG   = 100;

    /* ── Estado ────────────────────────────────────────────────────────── */
    var state = {
        pagina:           1,
        total:            0,
        paginas:          1,
        ocultar:          true,
        categ:            '',
        busqueda:         '',
        cargando:         false,
    };

    /* ── Estado de ordenamiento ────────────────────────────────────────── */
    var sortState  = { col: 'nombre', dir: 'asc' };
    var currentRows = [];   // datos de la página actual (sin ordenar)

    /* ── DOM refs ──────────────────────────────────────────────────────── */
    var $loading   = document.getElementById('prest-loading');
    var $tableWrap = document.getElementById('prest-table-wrap');
    var $tbody     = document.getElementById('prest-tbody');
    var $empty     = document.getElementById('prest-empty');
    var $footer    = document.getElementById('prest-footer');
    var $footInfo  = document.getElementById('prest-footer-info');
    var $pagination= document.getElementById('prest-pagination');
    var $totalBdg  = document.getElementById('prest-total-badge');
    var $fltBus    = document.getElementById('flt-busqueda');
    var $fltCateg  = document.getElementById('flt-categ');
    var $fltOcultar= document.getElementById('flt-ocultar-inactivos');
    var $btnBuscar = document.getElementById('btn-buscar');

    /* ══════════════════════════════════════════════════════════════════════
     * CARGA DE CATEGORÍAS → poblar el <select>
     * ════════════════════════════════════════════════════════════════════ */
    fetch(BASE_URL + '?route=prestadores&action=categorias', {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function (r) { return r.json(); })
    .then(function (res) {
        if (!res.ok || !res.data) return;
        res.data.forEach(function (c) {
            var opt = document.createElement('option');
            opt.value = c.clave;
            // ADEF va marcado visualmente para que se note
            opt.textContent = c.clave
                + (c.descripcion ? ' — ' + c.descripcion : '')
                + (c.clave === 'ADEF' ? ' ⚠' : '');
            $fltCateg.appendChild(opt);
        });
    })
    .catch(function () {});

    /* ══════════════════════════════════════════════════════════════════════
     * CARGA DE DATOS
     * ════════════════════════════════════════════════════════════════════ */
    function cargar(pagina) {
        if (state.cargando) return;
        state.cargando = true;
        state.pagina   = pagina || 1;

        // Leer filtros activos
        state.busqueda = $fltBus.value.trim();
        state.categ    = $fltCateg.value;
        state.ocultar  = $fltOcultar.checked;

        mostrarLoading();

        var qs = [
            'route=prestadores',
            'action=listado',
            'ocultar_inactivos=' + (state.ocultar ? '1' : '0'),
            'categ='     + encodeURIComponent(state.categ),
            'busqueda='  + encodeURIComponent(state.busqueda),
            'pagina='    + state.pagina,
            'por_pagina='+ POR_PAG,
        ].join('&');

        fetch(BASE_URL + '?' + qs, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            state.cargando = false;
            if (!res.ok) {
                mostrarError(res.error || 'Error al cargar los datos.');
                return;
            }
            state.total   = res.total   || 0;
            state.paginas = res.paginas || 1;
            state.pagina  = res.pagina  || 1;

            $totalBdg.innerHTML = '<i class="fa-solid fa-list-ol me-1"></i>'
                                + state.total + ' prestadores';

            currentRows = res.datos || [];
            sortAndRender();
            renderPaginacion();
        })
        .catch(function (err) {
            state.cargando = false;
            mostrarError('Error de comunicación con el servidor.');
            console.error(err);
        });
    }

    /* ══════════════════════════════════════════════════════════════════════
     * ORDENAMIENTO DE COLUMNAS
     * ════════════════════════════════════════════════════════════════════ */
    function sortAndRender() {
        var col = sortState.col;
        var dir = sortState.dir;
        var sorted = currentRows.slice().sort(function (a, b) {
            var av = String(a[col] || '').toLowerCase();
            var bv = String(b[col] || '').toLowerCase();
            // Para fechas (YYYY-MM-DD) ordenar como string funciona bien
            if (av < bv) return dir === 'asc' ? -1 :  1;
            if (av > bv) return dir === 'asc' ?  1 : -1;
            return 0;
        });
        renderTabla(sorted);
        updateSortIndicators();
    }

    function updateSortIndicators() {
        document.querySelectorAll('#tbl-prestadores thead th[data-sort]').forEach(function (th) {
            var ind = th.querySelector('.sort-ind');
            if (!ind) return;
            var col = th.dataset.sort;
            th.classList.remove('sorted-asc', 'sorted-desc');
            if (col === sortState.col) {
                th.classList.add('sorted-' + sortState.dir);
                ind.textContent = sortState.dir === 'asc' ? ' ↑' : ' ↓';
            } else {
                ind.textContent = ' ↕';
            }
        });
    }

    // Wiring de click en cabeceras
    document.querySelectorAll('#tbl-prestadores thead th[data-sort]').forEach(function (th) {
        th.addEventListener('click', function () {
            var col = this.dataset.sort;
            if (sortState.col === col) {
                sortState.dir = sortState.dir === 'asc' ? 'desc' : 'asc';
            } else {
                sortState.col = col;
                sortState.dir = 'asc';
            }
            sortAndRender();
        });
    });

    /* ══════════════════════════════════════════════════════════════════════
     * RENDER TABLA
     * ════════════════════════════════════════════════════════════════════ */
    function renderTabla(rows) {
        $tbody.innerHTML = '';

        if (!rows.length) {
            $loading.style.display    = 'none';
            $tableWrap.style.display  = 'block';
            $empty.style.display      = 'block';
            $tbody.closest('table').style.display = 'none';
            $footer.style.removeProperty('display');
            $footer.style.display = 'none';
            return;
        }

        $empty.style.display = 'none';
        $tbody.closest('table').style.display = '';

        rows.forEach(function (r) {
            var tr = document.createElement('tr');
            if (r._row_class) tr.className = r._row_class;

            var nombre  = escHtml(r.nombre    || '');
            var fantas  = r.nomfantas ? escHtml(r.nomfantas) : '';
            var direcc  = escHtml(r.direcc    || '');
            var zona    = escHtml(r.zona      || '');
            var local   = escHtml(r.localidad || '');
            var telcons = escHtml(r.telcons   || '');
            var celular = escHtml(r.celular   || '');
            var codigo  = escHtml(r.codigo    || r.matricula || '');
            var grupoweb= escHtml(r.grupoweb  || '');
            var contacto= escHtml(r.contacto  || '');

            // Fecha baja DD/MM/YYYY — siempre en rojo si tiene valor
            var fbRaw  = r.fechabaja || '';
            var fbShow = '';
            var fbPast = false;
            if (fbRaw && fbRaw !== '0000-00-00' && fbRaw !== '0000-00-00 00:00:00') {
                var pts = fbRaw.substring(0, 10).split('-');
                if (pts.length === 3) fbShow = pts[2] + '/' + pts[1] + '/' + pts[0];
                // Verificar si es pasada
                var fbDate = new Date(fbRaw.substring(0, 10) + 'T00:00:00');
                fbPast = fbDate < new Date();
            }

            // Helper: icono bool
            function chk(val) {
                return val === '1'
                    ? '<i class="fa-solid fa-check" style="color:#2e7d32;font-size:.75rem;"></i>'
                    : '<i class="fa-solid fa-xmark" style="color:#c62828;font-size:.75rem;"></i>';
            }

            tr.innerHTML =
                /* Acciones */
                '<td style="padding:3px 5px;text-align:center;vertical-align:middle;">'
                  + buildDropdown(r.codigo || r.matricula, r.nombre)
                + '</td>'
                /* Nombre */
                + '<td style="padding:4px 8px;vertical-align:middle;">'
                  + '<div class="fw-semibold lh-sm" style="font-size:.78rem;">' + nombre + '</div>'
                  + (fantas ? '<div class="text-muted" style="font-size:.69rem;">' + fantas + '</div>' : '')
                + '</td>'
                /* Dirección */
                + '<td style="padding:4px 8px;vertical-align:middle;max-width:160px;overflow:hidden;text-overflow:ellipsis;" title="' + direcc + '">'
                  + direcc
                + '</td>'
                /* Zona */
                + '<td style="padding:4px 8px;vertical-align:middle;text-align:center;">'
                  + zona
                + '</td>'
                /* Localidad */
                + '<td style="padding:4px 8px;vertical-align:middle;text-align:center;">'
                  + local
                + '</td>'
                /* Tel Cons */
                + '<td style="padding:4px 8px;vertical-align:middle;font-family:monospace;font-size:.73rem;">'
                  + telcons
                + '</td>'
                /* Particular (celular) */
                + '<td style="padding:4px 8px;vertical-align:middle;font-family:monospace;font-size:.73rem;">'
                  + celular
                + '</td>'
                /* Código (matricula) */
                + '<td style="padding:4px 8px;vertical-align:middle;text-align:center;">'
                  + '<span class="badge bg-light border text-dark" style="font-size:.69rem;font-family:monospace;">'
                  + codigo + '</span>'
                + '</td>'
                /* Grupo Web */
                + '<td style="padding:4px 8px;vertical-align:middle;text-align:center;font-size:.72rem;">'
                  + grupoweb
                + '</td>'
                /* Categ (badge) */
                + '<td style="padding:4px 8px;vertical-align:middle;text-align:center;">'
                  + (r._badge_categ || escHtml(r.categ || ''))
                + '</td>'
                /* F. Baja — negro si es futura, rojo si es pasada */
                + '<td style="padding:4px 8px;vertical-align:middle;text-align:center;font-size:.72rem;'
                  + (fbPast ? 'color:#c62828;font-weight:700;' : fbShow ? 'color:#212529;' : 'color:#aaa;') + '">'
                  + (fbShow || '—')
                + '</td>'
                /* ExcluCart */
                + '<td style="padding:4px 2px;vertical-align:middle;text-align:center;">' + chk(r.exclucart)  + '</td>'
                /* Tienesuc */
                + '<td style="padding:4px 2px;vertical-align:middle;text-align:center;">' + chk(r.tienesuc)   + '</td>'
                /* Tienesucpr */
                + '<td style="padding:4px 2px;vertical-align:middle;text-align:center;">' + chk(r.tienesucpr) + '</td>'
                /* Trabajaos (OS) */
                + '<td style="padding:4px 2px;vertical-align:middle;text-align:center;">' + chk(r.trabajaos)  + '</td>'
                /* Tieneprest */
                + '<td style="padding:4px 2px;vertical-align:middle;text-align:center;">' + chk(r.tieneprest) + '</td>'
                /* Contacto */
                + '<td style="padding:4px 8px;vertical-align:middle;font-size:.72rem;">'
                  + contacto
                + '</td>';

            $tbody.appendChild(tr);
        });

        // Delegación de eventos
        $tbody.querySelectorAll('[data-prest-action]').forEach(function (el) {
            el.addEventListener('click', function (e) {
                e.preventDefault();
                despacharAccion(
                    this.getAttribute('data-prest-action'),
                    this.getAttribute('data-codigo'),
                    this.getAttribute('data-nombre')
                );
            });
        });

        $loading.style.display   = 'none';
        $tableWrap.style.display = 'block';
        $footer.style.removeProperty('display');
        $footer.style.display = '';
    }

    /* ══════════════════════════════════════════════════════════════════════
     * DROPDOWN de acciones por fila
     * ════════════════════════════════════════════════════════════════════ */
    function buildDropdown(codigo, nombre) {
        var id = 'dd-' + codigo.replace(/[^a-z0-9]/gi, '');
        return '<div class="dropdown">'
            + '<button class="btn btn-sm btn-outline-secondary py-0 px-1 dropdown-toggle dropdown-toggle-no-caret"'
            + '  type="button" data-bs-toggle="dropdown" aria-expanded="false"'
            + '  style="font-size:0.8rem; border-radius:4px;">'
            + '  <i class="fa-solid fa-ellipsis-vertical"></i>'
            + '</button>'
            + '<ul class="dropdown-menu dropdown-menu-end shadow-sm" style="font-size:0.8rem; min-width:170px;">'

            /* Modificar */
            + '<li><a class="dropdown-item" href="#"'
            + '  data-prest-action="modificar" data-codigo="' + codigo + '" data-nombre="' + nombre + '">'
            + '  <i class="fa-solid fa-pen-to-square me-2 text-warning"></i>Modificar'
            + '</a></li>'

            /* Borrar */
            + '<li><a class="dropdown-item disabled" href="#"'
            + '  data-prest-action="borrar" data-codigo="' + codigo + '" data-nombre="' + nombre + '">'
            + '  <i class="fa-solid fa-trash me-2 text-danger"></i>Borrar'
            + '</a></li>'

            + '<li><hr class="dropdown-divider"></li>'

            /* Obras Sociales */
            + '<li><a class="dropdown-item" href="#"'
            + '  data-prest-action="obras_sociales" data-codigo="' + codigo + '" data-nombre="' + nombre + '">'
            + '  <i class="fa-solid fa-building-columns me-2 text-info"></i>Obras Sociales'
            + '</a></li>'

            + '<li><hr class="dropdown-divider"></li>'

            /* Prácticas */
            + '<li><a class="dropdown-item disabled" href="#"'
            + '  data-prest-action="practicas" data-codigo="' + codigo + '" data-nombre="' + nombre + '">'
            + '  <i class="fa-solid fa-syringe me-2 text-secondary"></i>Prácticas'
            + '</a></li>'

            + '<li><hr class="dropdown-divider"></li>'

            /* Sucursales */
            + '<li><a class="dropdown-item disabled" href="#"'
            + '  data-prest-action="sucursales" data-codigo="' + codigo + '" data-nombre="' + nombre + '">'
            + '  <i class="fa-solid fa-map-location-dot me-2 text-secondary"></i>Sucursales'
            + '</a></li>'

            + '<li><hr class="dropdown-divider"></li>'

            /* Exclusión Cartilla */
            + '<li><a class="dropdown-item disabled" href="#"'
            + '  data-prest-action="excl_cartilla" data-codigo="' + codigo + '" data-nombre="' + nombre + '">'
            + '  <i class="fa-solid fa-address-book me-2 text-secondary"></i>Exclusión Cartilla'
            + '</a></li>'

            /* Exclusión Prácticas */
            + '<li><a class="dropdown-item disabled" href="#"'
            + '  data-prest-action="excl_practicas" data-codigo="' + codigo + '" data-nombre="' + nombre + '">'
            + '  <i class="fa-solid fa-list-check me-2 text-secondary"></i>Exclusión Prácticas'
            + '</a></li>'

            + '</ul>'
            + '</div>';
    }

    /* ── Dispatcher de acciones ────────────────────────────────────────── */
    function despacharAccion(action, codigo, nombre) {
        switch (action) {
            case 'obras_sociales':
                <?php if ($puedeGestionarOS): ?>
                window.abrirModalObrasSociales(codigo, nombre);
                <?php else: ?>
                alert('No tiene permiso para gestionar Obras Sociales.');
                <?php endif; ?>
                break;
            case 'modificar':
                window.abrirModalEditarPrestador(codigo, nombre);
                break;
            case 'borrar':
            case 'sucursales':
                window.abrirModalSucursales(codigo, nombre);
                break;
            case 'practicas':
            case 'excl_cartilla':
            case 'excl_practicas':
                // TODO: implementar en fase siguiente
                break;
        }
    }

    /* Función pública para que el modal de edición recargue la grilla */
    window.recargarGrillaPrestadores = function () { cargar(state.pagina); };

    /* Botón Nuevo */
    document.getElementById('btn-nuevo-prestador').addEventListener('click', function () {
        window.abrirModalNuevoPrestador();
    });

    /* ══════════════════════════════════════════════════════════════════════
     * PAGINACIÓN
     * ════════════════════════════════════════════════════════════════════ */
    function renderPaginacion() {
        var desde = (state.pagina - 1) * POR_PAG + 1;
        var hasta = Math.min(state.pagina * POR_PAG, state.total);
        $footInfo.textContent = 'Mostrando ' + desde + '–' + hasta + ' de ' + state.total + ' prestadores';

        $pagination.innerHTML = '';

        if (state.paginas <= 1) {
            $footer.style.display = 'none';
            return;
        }

        // Prev
        addPagBtn('&laquo;', state.pagina > 1 ? state.pagina - 1 : null, state.pagina <= 1);

        // Páginas (máx 7 visibles)
        var start = Math.max(1, state.pagina - 3);
        var end   = Math.min(state.paginas, start + 6);
        for (var p = start; p <= end; p++) {
            addPagBtn(p, p, false, p === state.pagina);
        }

        // Next
        addPagBtn('&raquo;', state.pagina < state.paginas ? state.pagina + 1 : null,
                  state.pagina >= state.paginas);
    }

    function addPagBtn(label, targetPage, disabled, active) {
        var li  = document.createElement('li');
        li.className = 'page-item'
            + (disabled ? ' disabled' : '')
            + (active   ? ' active'   : '');
        var a = document.createElement('a');
        a.className   = 'page-link';
        a.href        = '#';
        a.innerHTML   = label;
        if (targetPage && !disabled && !active) {
            a.addEventListener('click', function (e) {
                e.preventDefault();
                cargar(targetPage);
            });
        }
        li.appendChild(a);
        $pagination.appendChild(li);
    }

    /* ══════════════════════════════════════════════════════════════════════
     * EVENTOS DE FILTROS
     * ════════════════════════════════════════════════════════════════════ */
    $btnBuscar.addEventListener('click', function () { cargar(1); });

    $fltBus.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') { e.preventDefault(); cargar(1); }
    });

    $fltCateg.addEventListener('change', function () { cargar(1); });

    $fltOcultar.addEventListener('change', function () { cargar(1); });

    /* ── Helpers visuales ───────────────────────────────────────────────── */
    function mostrarLoading() {
        $loading.style.display   = 'block';
        $tableWrap.style.display = 'none';
        $footer.style.display    = 'none';
    }

    function mostrarError(msg) {
        $loading.innerHTML = '<div class="text-center py-4">'
            + '<i class="fa-solid fa-triangle-exclamation fa-2x text-warning mb-2 d-block"></i>'
            + '<p class="text-muted mb-0" style="font-size:.85rem;">' + escHtml(msg) + '</p>'
            + '<button class="btn btn-sm btn-outline-primary mt-2" onclick="location.reload()">'
            + '<i class="fa-solid fa-rotate-right me-1"></i>Reintentar</button>'
            + '</div>';
        $loading.style.display   = 'block';
        $tableWrap.style.display = 'none';
    }

    function escHtml(str) {
        var d = document.createElement('div');
        d.appendChild(document.createTextNode(str || ''));
        return d.innerHTML;
    }

    /* ── Carga inicial ──────────────────────────────────────────────────── */
    cargar(1);

})();
</script>

<!-- Estilos grilla prestadores -->
<style>
.dropdown-toggle-no-caret::after { display: none !important; }

/* Colores de fila según estado (equivalente VFP) */
.tr-sin-suc      td { background-color: #fce4ec !important; }   /* Rosa  – Sin Sucursal */
.tr-sin-os       td { background-color: #fff3e0 !important; }   /* Naranja – Sin O.Social */
.tr-sin-prest    td { background-color: #fffde7 !important; }   /* Amarillo – Sin Prestaciones */
.tr-sin-suc-prac td { background-color: #f3e5f5 !important; }  /* Lavanda – Sin Práctica en Suc */
.tr-baja         td { background-color: #ede7f6 !important; color: #6a1b9a; } /* Violeta – Dado de baja */

/* Columnas ordenables */
#tbl-prestadores thead th[data-sort] {
    cursor: pointer;
    user-select: none;
    white-space: nowrap;
}
#tbl-prestadores thead th[data-sort]:hover { background-color: #d8e0f0 !important; }
#tbl-prestadores thead th[data-sort] .sort-ind { font-size:.7rem; opacity:.3; margin-left:3px; }
#tbl-prestadores thead th[data-sort].sorted-asc  .sort-ind,
#tbl-prestadores thead th[data-sort].sorted-desc .sort-ind { opacity:1; color:#0d6efd; }
</style>

<?php require_once __DIR__ . '/../../views/layouts/footer.php'; ?>
