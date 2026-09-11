<?php
/**
 * views/prestadores/_modal_obras_sociales.php
 * ─────────────────────────────────────────────────────────────────────────
 * Modal Bootstrap 5: Obras Sociales Habilitadas del Prestador
 *
 * Equivalente web del formulario VFP MEDOBRAS.SCT/SCX
 *
 * Permiso requerido: MNU_ARC_PRESTADORES
 * (La autorización granular de escritura se verifica en el controller)
 *
 * Incluir en la vista padre con:
 *   require_once __DIR__ . '/_modal_obras_sociales.php';
 * ─────────────────────────────────────────────────────────────────────────
 */

$csrfToken = $_SESSION['csrf_token'] ?? '';
?>

<!-- ╔═════════════════════════════════════════════════════════════════════╗
     ║  MODAL: OBRAS SOCIALES HABILITADAS                                 ║
     ╚═════════════════════════════════════════════════════════════════════╝ -->
<div class="modal fade"
     id="modalObrasSociales"
     tabindex="-1"
     aria-labelledby="modalObrasSocialesLabel"
     aria-hidden="true"
     data-bs-backdrop="static"
     data-bs-keyboard="false">

    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content" style="border-radius:12px; overflow:hidden;">

        <!-- ── Encabezado ───────────────────────────────────────────────── -->
        <div class="modal-header py-2 px-4"
             style="background:linear-gradient(90deg,#0d47a1,#1976d2); color:#fff; border-bottom:none;">
            <div class="d-flex align-items-center gap-3">
                <div style="width:38px;height:38px;border-radius:50%;
                            background:rgba(255,255,255,0.2);
                            display:flex;align-items:center;justify-content:center;">
                    <i class="fa-solid fa-building-columns"></i>
                </div>
                <div>
                    <h6 class="modal-title mb-0 fw-bold" id="modalObrasSocialesLabel">
                        Obras Sociales Habilitadas
                    </h6>
                    <small style="opacity:.85; font-size:0.73rem;">
                        Prestador: <span id="os-modal-nombre-prestador" class="fw-semibold">—</span>
                        &nbsp;|&nbsp; Código: <code id="os-modal-codigo-prestador"
                                                     style="color:#90caf9; font-size:0.8rem;">—</code>
                    </small>
                </div>
            </div>
            <button type="button" class="btn-close btn-close-white"
                    data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>

        <!-- ── Cuerpo ────────────────────────────────────────────────────── -->
        <div class="modal-body p-0">

            <!-- Spinner de carga inicial -->
            <div id="os-loading" class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Cargando...</span>
                </div>
                <p class="text-muted mt-2 mb-0" style="font-size:0.85rem;">Cargando obras sociales...</p>
            </div>

            <!-- Contenido principal (visible después de cargar) -->
            <div id="os-content" style="display:none;">

                <!-- ── Panel superior: Agregar nueva OS ─────────────────── -->
                <div class="px-4 pt-3 pb-3"
                     style="background:#f8fafd; border-bottom:1px solid #dee2e6;">

                    <div class="row g-2 align-items-end">

                        <!-- Select2 / Dropdown de OS a agregar -->
                        <div class="col-md-5">
                            <label class="form-label fw-semibold mb-1" style="font-size:0.8rem;">
                                <i class="fa-solid fa-plus-circle me-1 text-success"></i>
                                Agregar Obra Social
                            </label>
                            <select id="os-select-nueva"
                                    class="form-select form-select-sm"
                                    style="font-size:0.85rem;">
                                <option value="">— Seleccione una O.S. —</option>
                            </select>
                        </div>

                        <!-- Opciones al agregar -->
                        <div class="col-md-3">
                            <label class="form-label fw-semibold mb-1 d-block" style="font-size:0.8rem;">
                                Opciones
                            </label>
                            <div class="form-check form-check-sm mb-0">
                                <input class="form-check-input" type="checkbox"
                                       id="os-add-exclucart" value="1">
                                <label class="form-check-label" for="os-add-exclucart"
                                       style="font-size:0.8rem;">
                                    Excl. Cartilla
                                </label>
                            </div>
                        </div>

                        <!-- Botón Agregar -->
                        <div class="col-md-2">
                            <button id="btn-os-agregar"
                                    class="btn btn-success btn-sm w-100"
                                    style="font-size:0.82rem;">
                                <i class="fa-solid fa-plus me-1"></i>Asignar O.S.
                            </button>
                        </div>

                        <!-- Botón Actualizar lista -->
                        <div class="col-md-2">
                            <button id="btn-os-refresh"
                                    class="btn btn-outline-secondary btn-sm w-100"
                                    title="Actualizar listado"
                                    style="font-size:0.82rem;">
                                <i class="fa-solid fa-rotate-right me-1"></i>Actualizar
                            </button>
                        </div>

                    </div>

                    <!-- Alert de resultado de operación (ALTA/BAJA) -->
                    <div id="os-alert"
                         class="alert alert-dismissible mt-2 mb-0 py-2 px-3"
                         role="alert"
                         style="display:none; font-size:0.82rem; border-radius:8px;">
                        <span id="os-alert-msg"></span>
                        <button type="button" class="btn-close py-2" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                    </div>

                </div>

                <!-- ── Tabla de OS asignadas ─────────────────────────────── -->
                <div class="table-responsive">
                    <table class="table table-hover table-bordered mb-0"
                           id="tbl-obras-sociales"
                           style="font-size:0.83rem;">
                        <thead>
                            <tr style="background:#e8edf5; font-size:0.78rem;">
                                <th style="width:105px; padding:8px 10px; white-space:nowrap;">
                                    <i class="fa-solid fa-hashtag me-1 text-muted"></i>O.Social
                                </th>
                                <th style="padding:8px 10px;">
                                    <i class="fa-solid fa-building-columns me-1 text-muted"></i>Nombre Obra Social
                                </th>
                                <th style="width:105px; padding:8px 10px; text-align:center; white-space:nowrap;">
                                    <i class="fa-solid fa-calendar-plus me-1 text-muted"></i>F.Alta
                                </th>
                                <th style="width:105px; padding:8px 10px; text-align:center; white-space:nowrap;">
                                    <i class="fa-solid fa-calendar-xmark me-1 text-muted"></i>F.Baja
                                </th>
                                <th style="width:64px; padding:8px 10px; text-align:center; white-space:nowrap;"
                                    title="Exclusión Cartilla">
                                    Exc.<br>Cart.
                                </th>
                                <th style="width:90px; padding:8px 10px; text-align:center;">
                                    Acciones
                                </th>
                            </tr>
                        </thead>
                        <tbody id="os-tbody">
                            <!-- Filas inyectadas vía JS -->
                        </tbody>
                    </table>
                </div>

                <!-- ── Leyenda + toggle "Ocultar dadas de baja" ─────────── -->
                <div class="px-3 py-2 d-flex flex-wrap align-items-center gap-3"
                     style="background:#fafbfd; border-top:1px solid #dee2e6; font-size:0.75rem;">

                    <!-- Leyenda amarillo -->
                    <span>
                        <span style="display:inline-block;width:14px;height:14px;
                                     background:#fff3cd;border:1px solid #ffc107;
                                     border-radius:3px;vertical-align:middle;"></span>
                        &nbsp;O.S. dada de baja
                    </span>
                    <!-- Leyenda rojo -->
                    <span>
                        <span style="display:inline-block;width:14px;height:14px;
                                     background:#f8d7da;border:1px solid #f5c6cb;
                                     border-radius:3px;vertical-align:middle;"></span>
                        &nbsp;O.S. con excepciones
                    </span>

                    <!-- Separador vertical -->
                    <span style="border-left:1px solid #dee2e6; height:16px; display:inline-block;"></span>

                    <!-- Toggle: Ocultar dadas de baja -->
                    <div class="form-check form-switch mb-0 d-flex align-items-center gap-2">
                        <input class="form-check-input"
                               type="checkbox"
                               role="switch"
                               id="os-toggle-ocultar-baja"
                               style="cursor:pointer; width:2.2em; height:1.15em; margin-top:0;">
                        <label class="form-check-label"
                               for="os-toggle-ocultar-baja"
                               style="cursor:pointer; font-size:0.75rem; color:#495057; white-space:nowrap;">
                            Ocultar dadas de baja
                        </label>
                    </div>

                    <!-- Contador (derecha) -->
                    <span class="ms-auto text-muted" id="os-total-label">0 registros</span>
                </div>

            </div><!-- /#os-content -->

            <!-- Mensaje sin datos -->
            <div id="os-empty" style="display:none;"
                 class="text-center py-5 px-4">
                <i class="fa-solid fa-building-columns fa-2x text-muted mb-3 d-block"
                   style="opacity:.4;"></i>
                <p class="text-muted mb-0" style="font-size:0.88rem;">
                    Este prestador aún no tiene Obras Sociales asignadas.
                </p>
            </div>

        </div><!-- /.modal-body -->

        <!-- ── Pie del modal ─────────────────────────────────────────────── -->
        <div class="modal-footer py-2 px-4"
             style="background:#f5f7fb; border-top:1px solid #dee2e6;">
            <span class="text-muted me-auto" style="font-size:0.75rem;">
                <i class="fa-solid fa-circle-info me-1 text-info"></i>
                Los cambios se guardan automáticamente al operar.
            </span>
            <button type="button"
                    class="btn btn-sm btn-secondary"
                    data-bs-dismiss="modal">
                <i class="fa-solid fa-xmark me-1"></i>Cerrar
            </button>
        </div>

    </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div>
<!-- ╚═════════════════════════════════════════════════════════════════════╝ -->


<!-- ═══════════════════════════════════════════════════════════════════════
     JAVASCRIPT: lógica completa del modal Obras Sociales
     ═══════════════════════════════════════════════════════════════════════ -->
<script>
(function () {
    'use strict';

    /* ── Estado interno ────────────────────────────────────────────────── */
    var _codigoPrestador = '';
    var _nombrePrestador = '';
    var _baseUrl         = 'index.php';
    var _csrfToken       = <?= json_encode($csrfToken) ?>;

    /* ── Referencias DOM ───────────────────────────────────────────────── */
    var $modal          = document.getElementById('modalObrasSociales');
    var $loading        = document.getElementById('os-loading');
    var $content        = document.getElementById('os-content');
    var $empty          = document.getElementById('os-empty');
    var $tbody          = document.getElementById('os-tbody');
    var $totalLabel     = document.getElementById('os-total-label');
    var $selectNueva    = document.getElementById('os-select-nueva');
    var $btnAgregar     = document.getElementById('btn-os-agregar');
    var $btnRefresh     = document.getElementById('btn-os-refresh');
    var $alert          = document.getElementById('os-alert');
    var $alertMsg       = document.getElementById('os-alert-msg');
    var $labelCodigo    = document.getElementById('os-modal-codigo-prestador');
    var $labelNombre    = document.getElementById('os-modal-nombre-prestador');
    var $toggleOcultar  = document.getElementById('os-toggle-ocultar-baja');

    /* ══════════════════════════════════════════════════════════════════════
     * API PÚBLICA: window.abrirModalObrasSociales(codigo, nombre)
     * Llamada desde el botón de la vista de prestadores.
     * ════════════════════════════════════════════════════════════════════ */
    window.abrirModalObrasSociales = function (codigo, nombre) {
        _codigoPrestador = String(codigo || '').trim();
        _nombrePrestador = String(nombre || '').trim();

        if (!_codigoPrestador) {
            alert('Seleccione un prestador antes de gestionar sus Obras Sociales.');
            return;
        }

        // Actualizar cabecera del modal
        $labelCodigo.textContent = _codigoPrestador;
        $labelNombre.textContent = _nombrePrestador || '—';

        // Resetear estado visual
        mostrarLoading();
        ocultarAlert();
        document.getElementById('os-add-exclucart').checked = false;
        $selectNueva.value     = '';
        $toggleOcultar.checked = false;   // siempre mostrar todo al abrir

        // Abrir modal
        var bsModal = bootstrap.Modal.getOrCreateInstance($modal);
        bsModal.show();

        // Cargar datos en paralelo
        cargarCatalogo();
        cargarLista();
    };

    /* ── Cargar catálogo de OS vigentes en el <select> ─────────────────── */
    function cargarCatalogo() {
        fetch(_baseUrl + '?route=prestadores&action=os_obrasoc&codigo=' + encodeURIComponent(_codigoPrestador), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            if (!res.ok) { console.warn('os_obrasoc:', res.error); return; }
            // Resetear opciones
            $selectNueva.innerHTML = '<option value="">— Seleccione una O.S. —</option>';
            (res.data || []).forEach(function (os) {
                var opt = document.createElement('option');
                opt.value       = os.cosoc;
                opt.textContent = os.cosoc + ' — ' + (os.nombre || os.cosoc);
                $selectNueva.appendChild(opt);
            });
        })
        .catch(function (err) { console.error('Error cargando catálogo OS:', err); });
    }

    /* ── Cargar listado de OS del prestador ─────────────────────────────── */
    function cargarLista() {
        fetch(_baseUrl + '?route=prestadores&action=os_list&codigo=' + encodeURIComponent(_codigoPrestador), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            if (!res.ok) {
                mostrarAlert('danger', res.error || 'Error al cargar las obras sociales.');
                mostrarContenido(false);
                return;
            }
            renderTabla(res.data || []);
        })
        .catch(function (err) {
            mostrarAlert('danger', 'Error de comunicación con el servidor.');
            mostrarContenido(false);
            console.error(err);
        });
    }

    /* ── Renderizar tabla de OS ─────────────────────────────────────────── */
    function renderTabla(rows) {
        $tbody.innerHTML = '';

        if (!rows.length) {
            mostrarContenido(false, true); // vacío
            return;
        }

        var hoy = new Date();
        hoy.setHours(0, 0, 0, 0);

        rows.forEach(function (os) {
            var tr = document.createElement('tr');

            // ── Determinar clase de fila ──────────────────────────────────
            var esBaja  = false;
            var tieneEx = false;

            if (os.fechabaja) {
                var fb = new Date(os.fechabaja.replace(/-/g, '/'));
                fb.setHours(0, 0, 0, 0);
                if (fb <= hoy) { esBaja = true; }
            }
            if (os.excep && os.excep.toUpperCase() === 'S') { tieneEx = true; }

            // Prioridad: excep > baja (si ambas, excep gana el color)
            if (tieneEx) {
                tr.classList.add('table-danger');
            } else if (esBaja) {
                tr.classList.add('table-warning');
            }

            // Marca de baja para el filtro del toggle
            tr.dataset.esbaja = esBaja ? '1' : '0';

            // Datos para edición inline (fechas en ISO yyyy-mm-dd, sin hora)
            tr.dataset.cosoc     = os.cosoc;
            tr.dataset.fechaalta = os.fechaalta ? os.fechaalta.substring(0, 10) : '';
            tr.dataset.fechabaja = os.fechabaja ? os.fechabaja.substring(0, 10) : '';
            tr.dataset.exclucart = (os.exclucart == 1 || os.exclucart === 'S') ? '1' : '0';

            // ── Estado badge ──────────────────────────────────────────────
            var estadoBadge = esBaja
                ? '<span class="badge bg-danger" style="font-size:0.65rem;">Baja</span>'
                : '<span class="badge bg-success" style="font-size:0.65rem;">Activa</span>';

            // ── Formatear fechas ──────────────────────────────────────────
            var fechaaltaFmt = formatDate(os.fechaalta);
            var fechabajaTxt = formatDate(os.fechabaja);   // siempre mostrar la fecha real

            // ── Exc. Cartilla ─────────────────────────────────────────────
            var checkCart = (os.exclucart == 1 || os.exclucart === 'S')
                ? '<i class="fa-solid fa-check text-danger"></i>'
                : '<i class="fa-solid fa-minus text-muted" style="opacity:.3;"></i>';

            // ── Botones de acción ─────────────────────────────────────────
            var btnEditar = '<button class="btn btn-sm btn-outline-primary py-0 px-2 btn-os-editar me-1"'
                          + ' data-cosoc="' + escHtml(os.cosoc) + '"'
                          + ' title="Editar fechas y exclusión cartilla">'
                          + '<i class="fa-solid fa-pencil"></i>'
                          + '</button>';

            var btnBaja = '';
            if (!esBaja) {
                btnBaja = '<button class="btn btn-sm btn-outline-danger py-0 px-2 btn-os-baja"'
                        + ' data-cosoc="' + escHtml(os.cosoc) + '"'
                        + ' title="Dar de baja esta Obra Social">'
                        + '<i class="fa-solid fa-trash-can"></i>'
                        + '</button>';
            } else {
                btnBaja = '<span class="text-muted px-1" style="font-size:0.75rem;">—</span>';
            }

            tr.innerHTML =
                /* O.Social + badge */
                '<td style="padding:7px 10px; font-weight:600; font-size:0.8rem; white-space:nowrap;">'
                    + escHtml(os.cosoc) + ' ' + estadoBadge
                + '</td>'
                /* Nombre */
                + '<td style="padding:7px 10px;">' + escHtml(os.nombre || os.cosoc) + '</td>'
                /* F.Alta */
                + '<td class="os-cell-fechaalta" style="padding:7px 10px; text-align:center;">'
                    + fechaaltaFmt
                + '</td>'
                /* F.Baja */
                + '<td class="os-cell-fechabaja" style="padding:7px 10px; text-align:center;">'
                    + fechabajaTxt
                + '</td>'
                /* Exc. Cart. */
                + '<td class="os-cell-exclucart" style="padding:7px 10px; text-align:center;">'
                    + checkCart
                + '</td>'
                /* Acciones */
                + '<td style="padding:7px 8px; text-align:center; white-space:nowrap;">'
                    + btnEditar + btnBaja
                + '</td>';

            $tbody.appendChild(tr);
        });

        // Delegación de eventos: baja y editar
        $tbody.querySelectorAll('.btn-os-baja').forEach(function (btn) {
            btn.addEventListener('click', function () {
                confirmarBaja(this.getAttribute('data-cosoc'));
            });
        });
        $tbody.querySelectorAll('.btn-os-editar').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var cosoc = this.getAttribute('data-cosoc');
                var tr    = this.closest('tr');
                editarFila(cosoc, tr);
            });
        });

        $totalLabel.textContent = rows.length + ' registro' + (rows.length !== 1 ? 's' : '');
        mostrarContenido(true);

        // Aplicar filtro del toggle si ya estaba activado antes de recargar
        aplicarFiltroOcultarBaja();
    }

    /* ══════════════════════════════════════════════════════════════════════
     * EDICIÓN INLINE DE UNA FILA
     * ════════════════════════════════════════════════════════════════════ */

    /**
     * Activa el modo edición en la fila `tr`.
     * Transforma las celdas editables en inputs y muestra Guardar/Cancelar.
     */
    function editarFila(cosoc, tr) {
        // Deshabilitar todos los botones editar/baja mientras esta fila está en edición
        $tbody.querySelectorAll('.btn-os-editar, .btn-os-baja').forEach(function (b) {
            b.disabled = true;
        });

        // Guardar HTML original para restaurar en cancelar
        tr.dataset.htmlOriginal = tr.innerHTML;

        // Valores actuales desde data-atributos
        var fa   = tr.dataset.fechaalta || '';
        var fb   = tr.dataset.fechabaja || '';
        var cart = tr.dataset.exclucart === '1';

        // Mostrar fecha de baja como string legible en el placeholder
        var fbDisplay = (fb === '' || fb.startsWith('2099')) ? '' : fb;

        // ── Celda F.Alta ──────────────────────────────────────────────────
        tr.querySelector('.os-cell-fechaalta').innerHTML =
            '<input type="date" class="form-control form-control-sm os-input-fechaalta"'
            + ' value="' + escHtml(fa) + '"'
            + ' style="font-size:0.78rem; min-width:120px; padding:2px 6px;">';

        // ── Celda F.Baja ──────────────────────────────────────────────────
        tr.querySelector('.os-cell-fechabaja').innerHTML =
            '<input type="date" class="form-control form-control-sm os-input-fechabaja"'
            + ' value="' + escHtml(fbDisplay) + '"'
            + ' title="Dejar vacío = sin fecha de baja (se usará 2099-12-31)"'
            + ' style="font-size:0.78rem; min-width:120px; padding:2px 6px;">';

        // ── Celda Exc. Cartilla ───────────────────────────────────────────
        tr.querySelector('.os-cell-exclucart').innerHTML =
            '<div class="form-check form-switch mb-0 d-flex justify-content-center">'
            + '<input class="form-check-input os-input-exclucart" type="checkbox" role="switch"'
            + (cart ? ' checked' : '')
            + ' style="width:2em; height:1.1em; cursor:pointer;">'
            + '</div>';

        // ── Celda Acciones → Guardar | Cancelar ───────────────────────────
        var $tdAcciones = tr.cells[tr.cells.length - 1];
        $tdAcciones.innerHTML =
            '<button class="btn btn-sm btn-success py-0 px-2 me-1 btn-os-guardar-edit"'
            + ' data-cosoc="' + escHtml(cosoc) + '"'
            + ' title="Guardar cambios">'
            + '<i class="fa-solid fa-check"></i>'
            + '</button>'
            + '<button class="btn btn-sm btn-outline-secondary py-0 px-2 btn-os-cancelar-edit"'
            + ' title="Cancelar edición">'
            + '<i class="fa-solid fa-xmark"></i>'
            + '</button>';

        // Listeners de guardar y cancelar
        $tdAcciones.querySelector('.btn-os-guardar-edit').addEventListener('click', function () {
            guardarEdicion(cosoc, tr);
        });
        $tdAcciones.querySelector('.btn-os-cancelar-edit').addEventListener('click', function () {
            cancelarEdicion(tr);
        });

        // Marcar la fila como en edición
        tr.classList.add('os-fila-editando');
        tr.style.outline = '2px solid #0d6efd';
    }

    /**
     * Restaura la fila a su estado original (sin guardar).
     */
    function cancelarEdicion(tr) {
        tr.innerHTML        = tr.dataset.htmlOriginal || '';
        tr.style.outline    = '';
        tr.classList.remove('os-fila-editando');
        delete tr.dataset.htmlOriginal;

        // Re-registrar listeners de la fila restaurada
        var btnBaja   = tr.querySelector('.btn-os-baja');
        var btnEditar = tr.querySelector('.btn-os-editar');
        if (btnBaja)   btnBaja.addEventListener('click', function () {
            confirmarBaja(this.getAttribute('data-cosoc'));
        });
        if (btnEditar) btnEditar.addEventListener('click', function () {
            editarFila(this.getAttribute('data-cosoc'), this.closest('tr'));
        });

        // Rehabilitar todos los botones
        $tbody.querySelectorAll('.btn-os-editar, .btn-os-baja').forEach(function (b) {
            b.disabled = false;
        });
    }

    /**
     * Recoge los valores del modo edición, valida y envía al servidor.
     */
    function guardarEdicion(cosoc, tr) {
        var fa   = tr.querySelector('.os-input-fechaalta').value.trim();
        var fb   = tr.querySelector('.os-input-fechabaja').value.trim();
        var cart = tr.querySelector('.os-input-exclucart').checked ? '1' : '0';

        // Validar F.Alta obligatoria
        if (!fa) {
            alert('La Fecha de Alta es obligatoria.');
            return;
        }

        // Si F.Baja queda vacía → fechaalta + 100 años (mismo día/mes)
        if (!fb) {
            var base = fa ? new Date(fa + 'T00:00:00') : new Date();
            fb = (base.getFullYear() + 100)
               + '-' + String(base.getMonth() + 1).padStart(2, '0')
               + '-' + String(base.getDate()).padStart(2, '0');
        }

        // F.Alta no puede ser posterior a F.Baja
        if (fb && fa > fb) {
            alert('La Fecha de Alta no puede ser posterior a la Fecha de Baja.');
            return;
        }

        // Bloquear botones mientras se guarda
        var $btnGuardar = tr.querySelector('.btn-os-guardar-edit');
        var $btnCancelar = tr.querySelector('.btn-os-cancelar-edit');
        $btnGuardar.disabled  = true;
        $btnCancelar.disabled = true;
        $btnGuardar.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

        var formData = new FormData();
        formData.append('csrf_token', _csrfToken);
        formData.append('codigo',     _codigoPrestador);
        formData.append('cosoc',      cosoc);
        formData.append('fechaalta',  fa);
        formData.append('fechabaja',  fb);
        formData.append('exclucart',  cart);

        fetch(_baseUrl + '?route=prestadores&action=os_edit', {
            method:  'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body:    formData,
        })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            if (res.ok) {
                mostrarAlert('success', res.msg || 'Cambios guardados correctamente.');
                cargarLista();
            } else {
                mostrarAlert('danger', res.error || 'Error al guardar los cambios.');
                $btnGuardar.disabled  = false;
                $btnCancelar.disabled = false;
                $btnGuardar.innerHTML = '<i class="fa-solid fa-check"></i>';
                // Rehabilitar todos los botones
                $tbody.querySelectorAll('.btn-os-editar, .btn-os-baja').forEach(function (b) {
                    b.disabled = false;
                });
            }
        })
        .catch(function (err) {
            mostrarAlert('danger', 'Error de comunicación con el servidor.');
            $btnGuardar.disabled  = false;
            $btnCancelar.disabled = false;
            $btnGuardar.innerHTML = '<i class="fa-solid fa-check"></i>';
            $tbody.querySelectorAll('.btn-os-editar, .btn-os-baja').forEach(function (b) {
                b.disabled = false;
            });
            console.error(err);
        });
    }

    /* ── FILTRO: Ocultar / mostrar filas dadas de baja ──────────────────── */

    /**
     * Recorre todas las filas del tbody y las muestra u oculta según
     * el estado del toggle y el atributo data-esbaja.
     * Actualiza también el contador visible / total.
     */
    function aplicarFiltroOcultarBaja() {
        var ocultar = $toggleOcultar.checked;
        var filas   = $tbody.querySelectorAll('tr');
        var visible = 0;
        var total   = filas.length;

        filas.forEach(function (tr) {
            var esBaja = tr.dataset.esbaja === '1';
            if (ocultar && esBaja) {
                tr.style.display = 'none';
            } else {
                tr.style.display = '';
                visible++;
            }
        });

        // Actualizar etiqueta contadora
        if (ocultar && visible < total) {
            $totalLabel.textContent = visible + ' visible' + (visible !== 1 ? 's' : '')
                                    + ' (' + total + ' total)';
        } else {
            $totalLabel.textContent = total + ' registro' + (total !== 1 ? 's' : '');
        }
    }

    /* Listener del toggle ─────────────────────────────────────────────── */
    $toggleOcultar.addEventListener('change', aplicarFiltroOcultarBaja);

    /* ── AGREGAR Obra Social ─────────────────────────────────────────────── */
    $btnAgregar.addEventListener('click', function () {
        var cosoc     = $selectNueva.value;
        var exclucart = document.getElementById('os-add-exclucart').checked ? 1 : 0;

        if (!cosoc) {
            mostrarAlert('warning', 'Seleccione una Obra Social para asignar.');
            return;
        }

        setBusy(true);
        ocultarAlert();

        var formData = new FormData();
        formData.append('csrf_token', _csrfToken);
        formData.append('codigo',     _codigoPrestador);
        formData.append('cosoc',      cosoc);
        if (exclucart) formData.append('exclucart', '1');

        fetch(_baseUrl + '?route=prestadores&action=os_add', {
            method:  'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body:    formData,
        })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            setBusy(false);
            if (res.ok) {
                mostrarAlert('success', res.msg || 'Obra Social asignada.');
                $selectNueva.value = '';
                document.getElementById('os-add-exclucart').checked = false;
                cargarCatalogo();   // quitar la OS recién asignada del desplegable
                cargarLista();
            } else {
                mostrarAlert('danger', res.error || 'Error al asignar la Obra Social.');
            }
        })
        .catch(function (err) {
            setBusy(false);
            mostrarAlert('danger', 'Error de comunicación con el servidor.');
            console.error(err);
        });
    });

    /* ── BAJA de Obra Social ─────────────────────────────────────────────── */
    function confirmarBaja(cosoc) {
        if (!confirm('¿Dar de baja la O.S. «' + cosoc + '» para este prestador?\n\nSe registrará la operación en el historial de novedades.')) {
            return;
        }

        setBusy(true);
        ocultarAlert();

        var formData = new FormData();
        formData.append('csrf_token', _csrfToken);
        formData.append('codigo',     _codigoPrestador);
        formData.append('cosoc',      cosoc);

        fetch(_baseUrl + '?route=prestadores&action=os_baja', {
            method:  'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body:    formData,
        })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            setBusy(false);
            if (res.ok) {
                mostrarAlert('success', res.msg || 'Obra Social dada de baja.');
                cargarCatalogo();   // volver a mostrar la OS en el desplegable
                cargarLista();
            } else {
                mostrarAlert('danger', res.error || 'Error al dar de baja.');
            }
        })
        .catch(function (err) {
            setBusy(false);
            mostrarAlert('danger', 'Error de comunicación con el servidor.');
            console.error(err);
        });
    }

    /* ── Botón Refresh ───────────────────────────────────────────────────── */
    $btnRefresh.addEventListener('click', function () {
        mostrarLoading();
        ocultarAlert();
        cargarLista();
    });

    /* ── Helpers visuales ───────────────────────────────────────────────── */
    function mostrarLoading() {
        $loading.style.display  = 'block';
        $content.style.display  = 'none';
        $empty.style.display    = 'none';
    }

    function mostrarContenido(conDatos, vacio) {
        $loading.style.display = 'none';
        if (vacio) {
            $content.style.display = 'none';
            $empty.style.display   = 'block';
        } else {
            $content.style.display = 'block';
            $empty.style.display   = 'none';
        }
    }

    function mostrarAlert(tipo, msg) {
        $alert.className = 'alert alert-' + tipo + ' alert-dismissible mt-2 mb-0 py-2 px-3';
        $alertMsg.textContent = msg;
        $alert.style.display  = 'block';
    }

    function ocultarAlert() {
        $alert.style.display = 'none';
    }

    function setBusy(busy) {
        $btnAgregar.disabled = busy;
        $btnRefresh.disabled = busy;
        if (busy) {
            $btnAgregar.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Procesando...';
        } else {
            $btnAgregar.innerHTML = '<i class="fa-solid fa-plus me-1"></i>Asignar O.S.';
        }
    }

    /* ── Utilidades ─────────────────────────────────────────────────────── */
    function escHtml(str) {
        var d = document.createElement('div');
        d.appendChild(document.createTextNode(str || ''));
        return d.innerHTML;
    }

    function formatDate(str) {
        if (!str || str === '0000-00-00') {
            return '<span class="text-muted">—</span>';
        }
        // Convertir "YYYY-MM-DD" a "DD/MM/YYYY"
        var p = str.substring(0, 10).split('-');
        if (p.length === 3) { return p[2] + '/' + p[1] + '/' + p[0]; }
        return escHtml(str);
    }

})();
</script>
