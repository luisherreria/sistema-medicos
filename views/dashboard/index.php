<?php
/**
 * views/dashboard/index.php
 * Panel principal del Sistema Médico COMEDICA.
 * Favoritos personalizables por usuario (localStorage) + DataTables de prueba.
 */

$pageTitle  = 'COMEDICA — Dashboard';
$breadcrumb = [['label' => 'Dashboard']];

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../../models/Permission.php';

$user     = $_SESSION['user']     ?? [];
$permisos = $_SESSION['permisos'] ?? [];
$userId   = (int)($user['id']     ?? 0);

// ── Preparar datos de permisos para JS (con íconos y rutas resueltos en PHP) ──
$permisosParaJS = [];
foreach ($permisos as $p) {
    $clave = isset($p['CLAVE']) ? $p['CLAVE'] : '';
    $cat   = isset($p['CLAVE_CATEGORIA']) ? $p['CLAVE_CATEGORIA'] : 'GENERAL';
    $permisosParaJS[] = [
        'clave'      => $clave,
        'nombre'     => isset($p['NOMBRE_PERMISO']) ? $p['NOMBRE_PERMISO'] : $clave,
        'categoria'  => $cat,
        'catLabel'   => Permission::nombreCategoria($cat),
        'descripcion'=> isset($p['DESCRIPCION']) ? $p['DESCRIPCION'] : '',
        'route'      => Permission::getRoute($clave),
        'icono'      => Permission::iconoPorClave($clave, $cat),
    ];
}
$totalModulos     = count($permisos);
$totalCategorias  = count(array_unique(array_column($permisos, 'CLAVE_CATEGORIA')));
?>

<!-- ═══════════════ BIENVENIDA ══════════════════════════════════════════ -->
<div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
    <div>
        <h5 class="mb-1 fw-bold text-primary">
            <i class="fa-solid fa-gauge-high me-2"></i>Panel Principal
        </h5>
        <p class="text-muted mb-0" style="font-size:0.85rem;">
            Bienvenido, <strong><?= htmlspecialchars($user['full_name'] ?? 'Usuario') ?></strong>.
            Hoy es <strong id="fecha-dashboard"></strong>.
        </p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <span class="badge bg-primary px-3 py-2">
            <i class="fa-solid fa-th me-1"></i>
            <?= $totalModulos ?> módulo<?= $totalModulos !== 1 ? 's' : '' ?>
        </span>
        <span class="badge bg-info text-dark px-3 py-2">
            <i class="fa-solid fa-layer-group me-1"></i>
            <?= $totalCategorias ?> categoría<?= $totalCategorias !== 1 ? 's' : '' ?>
        </span>
    </div>
</div>

<!-- ═══════════════ SECCIÓN DE FAVORITOS ════════════════════════════════ -->
<div class="card border-0 shadow-sm mb-4" style="border-radius:14px;">

    <!-- Cabecera de favoritos -->
    <div class="card-header d-flex align-items-center justify-content-between py-2 px-3"
         style="background:linear-gradient(90deg,#0d47a1,#1976d2);border-radius:14px 14px 0 0;">
        <div class="d-flex align-items-center gap-2 text-white">
            <i class="fa-solid fa-star"></i>
            <span class="fw-semibold" style="font-size:0.9rem;">Accesos rápidos</span>
            <span class="badge bg-white text-primary ms-1" id="badge-contador"
                  style="font-size:0.72rem;">0 / 10</span>
        </div>
        <button class="btn btn-sm btn-light fw-semibold"
                style="font-size:0.8rem; border-radius:20px; padding:3px 14px;"
                data-bs-toggle="modal" data-bs-target="#modalFavoritos"
                id="btn-abrir-modal">
            <i class="fa-solid fa-plus me-1"></i>Personalizar
        </button>
    </div>

    <!-- Grilla de favoritos -->
    <div class="card-body px-3 py-3">
        <div class="row row-cols-2 row-cols-sm-4 row-cols-md-5 g-3 justify-content-start"
             id="favoritesGrid">
            <!-- Renderizado por JS -->
        </div>
        <!-- Placeholder cuando no hay favoritos -->
        <div id="favEmpty" class="text-center py-4 d-none">
            <i class="fa-solid fa-star fa-2x text-muted mb-2 d-block" style="opacity:.35;"></i>
            <p class="text-muted mb-1" style="font-size:0.88rem;">
                No tienes accesos rápidos configurados.
            </p>
            <button class="btn btn-sm btn-primary mt-1"
                    data-bs-toggle="modal" data-bs-target="#modalFavoritos">
                <i class="fa-solid fa-plus me-1"></i>Agregar favoritos
            </button>
        </div>
    </div>
</div>

<!-- ═══════════════ GRILLA DATATABLE DE PRUEBA ══════════════════════════ -->
<div class="card card-module mb-4">
    <div class="card-header d-flex align-items-center justify-content-between">
        <span><i class="fa-solid fa-table me-2"></i>Grilla de prueba — DataTables</span>
        <small class="opacity-75">Clase: <code class="text-warning">.datatable-medical</code></small>
    </div>
    <div class="card-body">
        <table class="table table-hover table-striped datatable-medical w-100" id="tbl-prueba">
            <thead>
                <tr>
                    <th>#</th><th>Nombre</th><th>Apellido</th>
                    <th>Módulo</th><th>Estado</th><th>Fecha</th>
                    <th class="no-export">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $demo = [
                    [1,'María','González','Facturación','Activo','2026-09-01'],
                    [2,'Carlos','Rodríguez','Internaciones','Activo','2026-09-02'],
                    [3,'Ana','Martínez','Afiliados','Inactivo','2026-08-15'],
                    [4,'Luis','Herrería','Ordenes/Autorizaciones','Activo','2026-09-10'],
                    [5,'Verónica','López','Cartilla','Activo','2026-07-30'],
                    [6,'Javier','Fernández','Telemedicina','Pendiente','2026-09-05'],
                    [7,'Sofía','Díaz','Call Center','Activo','2026-09-08'],
                    [8,'Pablo','Torres','Expedientes','Inactivo','2026-08-20'],
                    [9,'Laura','Sánchez','Facturación','Activo','2026-09-09'],
                    [10,'Diego','Ramírez','Negociaciones','Activo','2026-09-07'],
                    [11,'Camila','Vargas','Recursos Humanos','Activo','2026-09-03'],
                    [12,'Martín','Acosta','Prestadores','Pendiente','2026-09-06'],
                ];
                $estadoMap = ['Activo'=>'success','Inactivo'=>'danger','Pendiente'=>'warning'];
                foreach ($demo as $row):
                    $cls = isset($estadoMap[$row[4]]) ? $estadoMap[$row[4]] : 'secondary';
                ?>
                <tr>
                    <td><?= $row[0] ?></td>
                    <td><?= htmlspecialchars($row[1]) ?></td>
                    <td><?= htmlspecialchars($row[2]) ?></td>
                    <td><?= htmlspecialchars($row[3]) ?></td>
                    <td><span class="badge bg-<?= $cls ?>"><?= htmlspecialchars($row[4]) ?></span></td>
                    <td><?= htmlspecialchars($row[5]) ?></td>
                    <td class="no-export">
                        <button class="btn btn-sm btn-outline-primary py-0 px-2"><i class="fa-solid fa-eye"></i></button>
                        <button class="btn btn-sm btn-outline-warning py-0 px-2"><i class="fa-solid fa-pen"></i></button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>


<!-- ═══════════════ MODAL: AGREGAR / QUITAR FAVORITOS ═══════════════════ -->
<div class="modal fade" id="modalFavoritos" tabindex="-1"
     aria-labelledby="modalFavoritosLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content" style="border-radius:14px; overflow:hidden;">

            <!-- Header del modal -->
            <div class="modal-header"
                 style="background:linear-gradient(90deg,#0d47a1,#1565c0);">
                <div class="d-flex align-items-center gap-2 text-white">
                    <i class="fa-solid fa-star fa-lg"></i>
                    <div>
                        <h6 class="modal-title mb-0 fw-bold" id="modalFavoritosLabel">
                            Personalizar accesos rápidos
                        </h6>
                        <small style="opacity:.8; font-size:0.72rem;">
                            Seleccioná hasta 10 módulos para mostrar en tu Dashboard
                        </small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white"
                        data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <!-- Subheader: contador + búsqueda -->
            <div class="px-3 pt-3 pb-2 border-bottom" style="background:#f8f9fc;">

                <!-- Alerta de límite -->
                <div id="alertLimite" class="alert alert-warning d-flex align-items-center gap-2 py-2 d-none"
                     style="font-size:0.82rem; border-radius:8px;">
                    <i class="fa-solid fa-triangle-exclamation flex-shrink-0"></i>
                    <span>Alcanzaste el límite de <strong>10 favoritos</strong>.
                          Quita uno para poder agregar otro.</span>
                </div>

                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <!-- Barra de progreso del contador -->
                    <div class="flex-grow-1" style="min-width:200px;">
                        <div class="d-flex justify-content-between mb-1"
                             style="font-size:0.75rem; color:#546e7a;">
                            <span>Favoritos seleccionados</span>
                            <strong id="modal-contador">0 / 10</strong>
                        </div>
                        <div class="progress" style="height:6px; border-radius:20px;">
                            <div id="modal-progreso"
                                 class="progress-bar bg-primary"
                                 style="width:0%; transition:width .3s;"></div>
                        </div>
                    </div>
                    <!-- Buscador -->
                    <div class="input-group input-group-sm" style="max-width:220px;">
                        <span class="input-group-text bg-white">
                            <i class="fa-solid fa-magnifying-glass text-muted"></i>
                        </span>
                        <input type="text" id="modalBuscar" class="form-control"
                               placeholder="Buscar módulo...">
                    </div>
                </div>
            </div>

            <!-- Cuerpo del modal: lista de permisos agrupados por categoría -->
            <div class="modal-body px-3 py-2" id="modalCuerpo">
                <!-- Renderizado por JS -->
            </div>

            <!-- Footer del modal -->
            <div class="modal-footer py-2" style="background:#f8f9fc;">
                <small class="text-muted me-auto">
                    <i class="fa-solid fa-circle-info me-1 text-info"></i>
                    Los cambios se guardan automáticamente en este dispositivo.
                </small>
                <button type="button" class="btn btn-primary btn-sm px-4"
                        data-bs-dismiss="modal">
                    <i class="fa-solid fa-check me-1"></i>Listo
                </button>
            </div>

        </div>
    </div>
</div>
<!-- ════════════════════════════════════════════════════════════════════ -->


<!-- ═══════════════ ESTILOS DE FAVORITOS ════════════════════════════════ -->
<style>
    /* ── Card de favorito ─────────────────────────────────────────────── */
    .card-fav {
        background: #fff;
        border: 1.5px solid #e8edf5;
        border-radius: 12px;
        transition: transform .18s, box-shadow .18s, border-color .18s;
        position: relative;
        overflow: hidden;
    }
    .card-fav:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 22px rgba(21,101,192,.14);
        border-color: #90caf9;
    }
    /* Enlace interno (ocupa todo el card) */
    .card-fav-link {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 16px 8px 12px;
        text-decoration: none;
        color: inherit;
        min-height: 90px;
    }
    /* Círculo de ícono */
    .fav-icon-wrap {
        width: 44px;
        height: 44px;
        border-radius: 50%;
        background: #e3f2fd;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.15rem;
        color: #1565c0;
        margin-bottom: 8px;
        transition: background .18s, color .18s;
        flex-shrink: 0;
    }
    .card-fav:hover .fav-icon-wrap {
        background: #1565c0;
        color: #fff;
    }
    /* Nombre del módulo */
    .fav-label {
        font-size: 0.72rem;
        font-weight: 600;
        color: #37474f;
        text-align: center;
        line-height: 1.3;
        margin: 0;
        word-break: break-word;
        max-width: 90px;
    }
    /* Botón X (quitar favorito) */
    .btn-fav-remove {
        position: absolute;
        top: 4px;
        right: 4px;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        background: rgba(198,40,40,.08);
        border: none;
        color: #c62828;
        font-size: 0.65rem;
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: opacity .15s, background .15s;
        cursor: pointer;
        z-index: 2;
        padding: 0;
    }
    .card-fav:hover .btn-fav-remove {
        opacity: 1;
    }
    .btn-fav-remove:hover {
        background: #ffcdd2;
    }
    /* ── Ítems en el modal ────────────────────────────────────────────── */
    .modal-perm-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 8px 10px;
        border-radius: 8px;
        transition: background .12s;
        cursor: pointer;
        border: 1.5px solid transparent;
    }
    .modal-perm-item:hover {
        background: #f0f4fb;
    }
    .modal-perm-item.is-added {
        background: #e8f5e9;
        border-color: #a5d6a7;
    }
    .modal-perm-item.is-disabled {
        opacity: .45;
        cursor: not-allowed;
        pointer-events: none;
    }
    .modal-perm-icon {
        width: 34px;
        height: 34px;
        border-radius: 8px;
        background: #e3f2fd;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.9rem;
        color: #1565c0;
        flex-shrink: 0;
    }
    .modal-perm-item.is-added .modal-perm-icon {
        background: #c8e6c9;
        color: #2e7d32;
    }
    .modal-cat-header {
        font-size: 0.68rem;
        font-weight: 700;
        letter-spacing: .8px;
        text-transform: uppercase;
        color: #90a4ae;
        padding: 10px 4px 4px;
        border-bottom: 1px solid #eef2f7;
        margin-bottom: 4px;
    }
    .btn-fav-toggle {
        flex-shrink: 0;
        border-radius: 20px;
        font-size: 0.72rem;
        padding: 3px 10px;
        font-weight: 600;
    }
    /* Ocultar items filtrados */
    .modal-perm-item.hidden { display: none !important; }
    .modal-cat-block.hidden { display: none !important; }
</style>


<!-- ═══════════════ JAVASCRIPT ═══════════════════════════════════════════ -->
<script>
(function () {
    'use strict';

    /* ── Constantes ──────────────────────────────────────────────────── */
    var MAX_FAV     = 10;
    var USER_ID     = <?= $userId ?>;
    var STORAGE_KEY = 'comedica_favs_' + USER_ID;

    /* ── Datos de permisos (resueltos en PHP) ────────────────────────── */
    var ALL_PERMISOS = <?= json_encode($permisosParaJS, JSON_UNESCAPED_UNICODE) ?>;

    /* ── Mapeo de íconos JS por CLAVE (fallback / cliente puro) ─────── */
    var ICON_MAP = {
        /* ARCHIVOS */
        'MNU_ARC_PRESTADORES'    : 'fa-solid fa-stethoscope',
        'MNU_ARC_ESPECIALIDADES' : 'fa-solid fa-briefcase-medical',
        'MNU_ARC_OBRAS_SOCIALES' : 'fa-solid fa-building-columns',
        'MNU_ARC_NOMENCLADOR'    : 'fa-solid fa-book-medical',
        'MNU_ARC_NBU'            : 'fa-solid fa-file-medical',
        'MNU_ARC_KAIROS'         : 'fa-solid fa-clock-rotate-left',
        /* CARGA DATOS */
        'MNU_CD_PREST_INGRESO'   : 'fa-solid fa-file-medical',
        'MNU_CD_PREST_MOD'       : 'fa-solid fa-file-pen',
        'MNU_CD_FAC_INGRESO'     : 'fa-solid fa-file-invoice',
        'MNU_CD_DIAG'            : 'fa-solid fa-notes-medical',
        /* AUTORIZACIONES */
        'MNU_AUT_AUTORIZACIONES' : 'fa-solid fa-clipboard-check',
        /* AUDIT FAC */
        'MNU_AF_AUDITAR'         : 'fa-solid fa-magnifying-glass-dollar',
        'MNU_AF_CIERRE_AUTO'     : 'fa-solid fa-lock',
        'MNU_AF_REIMP_LIQ'       : 'fa-solid fa-print',
        'MNU_AF_ENV_DEBITO'      : 'fa-solid fa-envelope',
        'MNU_AF_CALC_CAPITAS'    : 'fa-solid fa-calculator',
        /* CONTADURÍA */
        'MNU_CONT_SALDOS'        : 'fa-solid fa-scale-balanced',
        'MNU_CONT_FECHAS_PAGO'   : 'fa-solid fa-calendar-days',
        'MNU_CONT_RES_OPAGO'     : 'fa-solid fa-receipt',
        /* REPORTES */
        'MNU_REP_HIST_CLIN'      : 'fa-solid fa-book',
        'MNU_REP_NORM_650'       : 'fa-solid fa-gavel',
        /* CONFIGURACIÓN */
        'MNU_CFG_CAMBIO_USR'     : 'fa-solid fa-user-pen',
        'MNU_CFG_CARGA_USR'      : 'fa-solid fa-user-plus',
        'MNU_CFG_EMAIL_MAS'      : 'fa-solid fa-envelope-open-text',
        'MNU_CFG_CARTILLA'       : 'fa-solid fa-address-book',
    };

    /* Devuelve el ícono FA de un permiso (prioridad: dato PHP → ICON_MAP → default) */
    function getIcon(perm) {
        if (perm.icono && perm.icono !== '') return perm.icono;
        if (ICON_MAP[perm.clave])           return ICON_MAP[perm.clave];
        return 'fa-solid fa-th-large';       // default
    }

    /* ── localStorage ────────────────────────────────────────────────── */
    function getFavorites() {
        try {
            var raw = localStorage.getItem(STORAGE_KEY);
            if (raw) {
                var parsed = JSON.parse(raw);
                if (Array.isArray(parsed)) return parsed;
            }
        } catch (e) {}
        // Primera visita: tomar los primeros 8 permisos como favoritos default
        return ALL_PERMISOS
            .slice(0, Math.min(8, ALL_PERMISOS.length))
            .map(function (p) { return p.clave; });
    }

    function saveFavorites(favs) {
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(favs));
        } catch (e) {}
    }

    /* ── Helpers HTML ────────────────────────────────────────────────── */
    function esc(str) {
        var d = document.createElement('div');
        d.appendChild(document.createTextNode(String(str)));
        return d.innerHTML;
    }

    /* ── Actualizar badge y barra de progreso ────────────────────────── */
    function actualizarContador(favs) {
        var n   = favs.length;
        var pct = Math.round((n / MAX_FAV) * 100);

        var badge = document.getElementById('badge-contador');
        if (badge) badge.textContent = n + ' / ' + MAX_FAV;

        var mCont = document.getElementById('modal-contador');
        if (mCont) mCont.textContent = n + ' / ' + MAX_FAV;

        var mProg = document.getElementById('modal-progreso');
        if (mProg) {
            mProg.style.width = pct + '%';
            mProg.className   = 'progress-bar ' + (n >= MAX_FAV ? 'bg-warning' : 'bg-primary');
        }

        var alerta = document.getElementById('alertLimite');
        if (alerta) {
            if (n >= MAX_FAV) {
                alerta.classList.remove('d-none');
            } else {
                alerta.classList.add('d-none');
            }
        }
    }

    /* ── Renderizar grilla de favoritos en el Dashboard ─────────────── */
    function renderFavorites() {
        var favs      = getFavorites();
        var grid      = document.getElementById('favoritesGrid');
        var emptyMsg  = document.getElementById('favEmpty');
        if (!grid) return;

        actualizarContador(favs);

        if (favs.length === 0) {
            grid.innerHTML = '';
            if (emptyMsg) emptyMsg.classList.remove('d-none');
            return;
        }
        if (emptyMsg) emptyMsg.classList.add('d-none');

        var html = '';
        favs.forEach(function (clave) {
            var perm = ALL_PERMISOS.find(function (p) { return p.clave === clave; });
            if (!perm) return;
            var icon = getIcon(perm);
            html += '<div class="col">'
                 +    '<div class="card-fav">'
                 +      '<button class="btn-fav-remove" '
                 +              'onclick="window.__removeFav(\'' + esc(clave) + '\')" '
                 +              'title="Quitar de favoritos" type="button">'
                 +          '<i class="fa-solid fa-xmark"></i>'
                 +      '</button>'
                 +      '<a href="index.php?route=' + esc(perm.route) + '" class="card-fav-link">'
                 +          '<div class="fav-icon-wrap"><i class="' + esc(icon) + '"></i></div>'
                 +          '<p class="fav-label">' + esc(perm.nombre) + '</p>'
                 +      '</a>'
                 +    '</div>'
                 +  '</div>';
        });
        grid.innerHTML = html;
    }

    /* ── Renderizar modal (lista de todos los permisos) ──────────────── */
    function renderModal() {
        var favs    = getFavorites();
        var cuerpo  = document.getElementById('modalCuerpo');
        if (!cuerpo) return;

        // Agrupar por categoría
        var grupos = {};
        var orden  = [];
        ALL_PERMISOS.forEach(function (p) {
            var cat = p.categoria || 'GENERAL';
            if (!grupos[cat]) {
                grupos[cat] = [];
                orden.push(cat);
            }
            grupos[cat].push(p);
        });

        var html = '';
        orden.forEach(function (cat) {
            var items = grupos[cat];
            // Verificar si algún item de esta categoría matchea la búsqueda (inicial: todos visibles)
            html += '<div class="modal-cat-block" data-cat="' + esc(cat) + '">';
            html += '<div class="modal-cat-header">'
                 +      '<i class="fa-solid fa-folder me-1"></i>'
                 +      esc(items[0].catLabel || cat)
                 +  '</div>';

            items.forEach(function (perm) {
                var icon     = getIcon(perm);
                var isAdded  = favs.indexOf(perm.clave) !== -1;
                var isFull   = favs.length >= MAX_FAV && !isAdded;
                var itemClass = 'modal-perm-item'
                              + (isAdded ? ' is-added' : '')
                              + (isFull  ? ' is-disabled' : '');

                html += '<div class="' + itemClass + '" data-clave="' + esc(perm.clave) + '"'
                     +       ' data-nombre="' + esc(perm.nombre.toLowerCase()) + '">';
                html +=   '<div class="modal-perm-icon"><i class="' + esc(icon) + '"></i></div>';
                html +=   '<div class="flex-grow-1" style="min-width:0;">';
                html +=     '<p class="mb-0 fw-semibold" style="font-size:0.83rem;">'
                          +   esc(perm.nombre) + '</p>';
                if (perm.descripcion) {
                    html += '<p class="mb-0 text-muted" style="font-size:0.72rem;">'
                          +   esc(perm.descripcion) + '</p>';
                }
                html +=   '</div>';
                // Botón Agregar / Quitar
                if (isAdded) {
                    html += '<button class="btn btn-sm btn-outline-success btn-fav-toggle" '
                         +          'onclick="window.__toggleFavModal(\'' + esc(perm.clave) + '\')" '
                         +          'type="button">'
                         +    '<i class="fa-solid fa-check me-1"></i>Agregado'
                         +  '</button>';
                } else {
                    html += '<button class="btn btn-sm btn-outline-primary btn-fav-toggle" '
                         +          'onclick="window.__toggleFavModal(\'' + esc(perm.clave) + '\')" '
                         +          'type="button"' + (isFull ? ' disabled' : '') + '>'
                         +    '<i class="fa-solid fa-plus me-1"></i>Agregar'
                         +  '</button>';
                }
                html += '</div>';
            });

            html += '</div>'; // .modal-cat-block
        });

        cuerpo.innerHTML = html || '<p class="text-muted text-center py-3">No tienes módulos asignados.</p>';
        actualizarContador(favs);
    }

    /* ── Agregar / Quitar favorito desde la grilla (botón X) ─────────── */
    window.__removeFav = function (clave) {
        var favs = getFavorites();
        var idx  = favs.indexOf(clave);
        if (idx !== -1) {
            favs.splice(idx, 1);
            saveFavorites(favs);
            renderFavorites();
            renderModal(); // refrescar modal si está abierto
        }
    };

    /* ── Toggle favorito desde el modal ─────────────────────────────── */
    window.__toggleFavModal = function (clave) {
        var favs = getFavorites();
        var idx  = favs.indexOf(clave);

        if (idx !== -1) {
            // Quitar favorito
            favs.splice(idx, 1);
        } else {
            // Agregar favorito (respetar límite)
            if (favs.length >= MAX_FAV) return;
            favs.push(clave);
        }

        saveFavorites(favs);
        renderFavorites();
        renderModal(); // re-render modal con estado actualizado
    };

    /* ── Buscador en el modal ────────────────────────────────────────── */
    function initBuscador() {
        var input = document.getElementById('modalBuscar');
        if (!input) return;
        input.addEventListener('input', function () {
            var q = input.value.toLowerCase().trim();
            document.querySelectorAll('.modal-perm-item').forEach(function (el) {
                var nombre = el.getAttribute('data-nombre') || '';
                var clave  = (el.getAttribute('data-clave') || '').toLowerCase();
                var match  = !q || nombre.indexOf(q) !== -1 || clave.indexOf(q) !== -1;
                el.classList.toggle('hidden', !match);
            });
            // Ocultar categorías vacías
            document.querySelectorAll('.modal-cat-block').forEach(function (block) {
                var visibles = block.querySelectorAll('.modal-perm-item:not(.hidden)');
                block.classList.toggle('hidden', visibles.length === 0);
            });
        });
    }

    /* ── Inicialización ──────────────────────────────────────────────── */
    document.addEventListener('DOMContentLoaded', function () {
        renderFavorites();

        // Limpiar buscador y re-renderizar modal al abrirlo
        var modalEl = document.getElementById('modalFavoritos');
        if (modalEl) {
            modalEl.addEventListener('show.bs.modal', function () {
                var input = document.getElementById('modalBuscar');
                if (input) input.value = '';
                // Limpiar filtros
                document.querySelectorAll('.modal-perm-item').forEach(function (el) {
                    el.classList.remove('hidden');
                });
                document.querySelectorAll('.modal-cat-block').forEach(function (el) {
                    el.classList.remove('hidden');
                });
                renderModal();
                initBuscador();
            });
        }

        // Fecha de bienvenida
        var dias  = ['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];
        var meses = ['enero','febrero','marzo','abril','mayo','junio',
                     'julio','agosto','septiembre','octubre','noviembre','diciembre'];
        var now   = new Date();
        var el    = document.getElementById('fecha-dashboard');
        if (el) {
            el.textContent = dias[now.getDay()] + ', '
                + now.getDate() + ' de '
                + meses[now.getMonth()] + ' de '
                + now.getFullYear();
        }
    });

})();
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
