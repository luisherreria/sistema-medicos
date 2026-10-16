<?php
/**
 * views/obras_sociales/index.php
 * Grilla de Obras Sociales. La carga inicial trae solo las vigentes.
 *
 * @var string $pageTitle
 * @var array  $breadcrumb
 * @var string $csrfToken
 */

$pageTitle  = $pageTitle ?? 'COMEDICA — Obras Sociales';
$breadcrumb = $breadcrumb ?? [['label' => 'Obras Sociales']];
$csrfToken  = (string) ($csrfToken ?? '');

$extraCss = array_merge(
    (isset($extraCss) && is_array($extraCss)) ? $extraCss : [],
    ['https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css']
);

require_once __DIR__ . '/../layouts/header.php';
?>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-3">
        <div class="d-flex flex-wrap align-items-center gap-2">
            <div class="d-flex align-items-center me-2">
                <span class="d-inline-flex align-items-center justify-content-center rounded-3 me-2"
                      style="width:36px;height:36px;background:linear-gradient(135deg,#0d47a1,#1976d2);">
                    <i class="fa-solid fa-building-columns text-white"></i>
                </span>
                <div>
                    <div class="fw-bold text-primary lh-1">Obras Sociales</div>
                    <div class="text-muted small" id="osResumen">Cargando obras sociales vigentes…</div>
                </div>
            </div>

            <button class="btn btn-primary" id="btnAgregarOS" type="button">
                <i class="bi bi-plus-circle me-1"></i> Agregar Obra Social
            </button>
            <button class="btn btn-outline-warning text-dark" id="btnCierrePeriodo" type="button">
                <i class="bi bi-calendar-check me-1"></i> Cierre de Período
            </button>

            <div class="form-check form-switch ms-3 align-self-center">
                <input class="form-check-input" type="checkbox" id="chkMostrarInactivas">
                <label class="form-check-label fw-bold small" for="chkMostrarInactivas">Mostrar dadas de baja / inactivas</label>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <table id="tablaObrasSociales" class="table table-striped table-hover table-sm w-100 align-middle">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Nombre / Razón Social</th>
                    <th>Afiliados</th>
                    <th>Teléfonos</th>
                    <th>Fecha Fin / Estado</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
        <p class="text-muted small mb-0 mt-2">Doble clic en una fila abre los datos de la obra social.</p>
    </div>
</div>

<?php require __DIR__ . '/_modal_editar_obra_social.php'; ?>

<div class="modal fade" id="modalCierrePeriodo" tabindex="-1" aria-labelledby="modalCierreTitulo" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="frmCierrePeriodo" autocomplete="off">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalCierreTitulo">
                        <i class="bi bi-calendar-check me-1"></i> Cierre de Período
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                    <div class="form-floating mb-3">
                        <input type="date" class="form-control rounded-3" id="cierreFecha" name="fecha" placeholder="Fecha" required>
                        <label for="cierreFecha"><i class="fa-solid fa-calendar-check me-1 text-muted"></i> Fecha de cierre</label>
                    </div>
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control rounded-3 text-uppercase" id="cierreCodigo"
                               name="codigo" maxlength="8" placeholder="Código" list="listaCodigosOS">
                        <label for="cierreCodigo"><i class="fa-solid fa-hashtag me-1 text-muted"></i> Código (una obra social)</label>
                    </div>
                    <datalist id="listaCodigosOS"></datalist>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="cierreTodas" name="aplicar_todas" value="1">
                        <label class="form-check-label" for="cierreTodas">
                            Aplicar a todas las obras sociales vigentes
                        </label>
                    </div>
                    <p class="text-muted small mt-3 mb-0">
                        Se graba en <strong>TAFECHAFIN</strong>. El cierre masivo no modifica las que ya están dadas de baja.
                        La vigencia incluye el día de la fecha de cierre.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning text-dark">Registrar cierre</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var CSRF = <?= json_encode($csrfToken, JSON_UNESCAPED_UNICODE) ?>;
    var modalOS = new bootstrap.Modal(document.getElementById('modalObraSocial'));
    var modalCierre = new bootstrap.Modal(document.getElementById('modalCierrePeriodo'));
    var codigosCargados = [];

    var tablaOS = $('#tablaObrasSociales').DataTable({
        pageLength: 25,
        lengthMenu: [[25, 50, 100, 500, -1], [25, 50, 100, 500, 'Todas']],
        searching: true,
        responsive: true,
        fixedHeader: {
            header: true,
            headerOffset: 64
        },
        order: [[0, 'asc']],
        createdRow: function (row) {
            row.style.cursor = 'pointer';
        },
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
        },
        dom: '<"d-flex justify-content-between align-items-center mb-3"Bf>rt<"d-flex justify-content-between align-items-center mt-3"lip>',
        buttons: [
            {
                extend: 'excel',
                title: 'Obras Sociales',
                exportOptions: { columns: [0, 1, 2, 3, 4], orthogonal: 'export' }
            },
            {
                extend: 'print',
                title: 'Obras Sociales',
                exportOptions: { columns: [0, 1, 2, 3, 4], orthogonal: 'export' }
            }
        ],
        columns: [
            {
                data: 'TACODIGO',
                render: function (data, type, row) {
                    var codigo = data == null ? '' : String(data);
                    if (type === 'export' || type === 'filter' || type === 'sort') {
                        return codigo;
                    }
                    if (row.ES_INACTIVA) {
                        return '<span class="badge bg-danger text-white px-2 py-1 fs-6">' + esc(codigo) + '</span>';
                    }
                    return '<span class="fw-semibold">' + esc(codigo) + '</span>';
                }
            },
            {
                data: 'TADESCRIP',
                render: function (data, type) {
                    var nombre = data == null ? '' : String(data);
                    if (type === 'export' || type === 'filter' || type === 'sort') {
                        return nombre;
                    }
                    return esc(nombre);
                }
            },
            {
                data: 'TACANTAFIL',
                className: 'text-end',
                render: function (data, type) {
                    if (data === null || data === '') {
                        return type === 'sort' ? -1 : '';
                    }
                    var n = Number(data);
                    if (type === 'export' || type === 'sort' || type === 'filter') {
                        return isNaN(n) ? '' : n;
                    }
                    return isNaN(n) ? '' : n.toLocaleString('es-AR');
                }
            },
            {
                data: 'TATELEF',
                render: function (data, type) {
                    var tel = data == null ? '' : String(data);
                    if (type === 'export' || type === 'filter' || type === 'sort') {
                        return tel;
                    }
                    return esc(tel);
                }
            },
            {
                data: 'TAFECHAFIN',
                render: function (data, type, row) {
                    var fecha = fmtFecha(data);
                    if (type === 'export' || type === 'filter' || type === 'sort') {
                        return (row.ES_INACTIVA ? 'Baja' : 'Vigente') + (fecha ? ' ' + fecha : '');
                    }
                    if (row.ES_INACTIVA) {
                        return '<span class="badge bg-danger me-1">Baja</span>' + esc(fecha);
                    }
                    return '<span class="badge bg-success me-1">Vigente</span>' + esc(fecha);
                }
            },
            {
                data: null,
                orderable: false,
                searchable: false,
                className: 'text-end',
                render: function (data, type, row) {
                    if (type !== 'display') {
                        return '';
                    }
                    var codigo = escAttr(row.TACODIGO);
                    return ''
                        + '<div class="dropdown">'
                        + '  <button class="btn btn-sm btn-light border dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">'
                        + '    <i class="bi bi-gear me-1"></i> Opciones'
                        + '  </button>'
                        + '  <ul class="dropdown-menu dropdown-menu-end shadow">'
                        + '    <li><a class="dropdown-item btn-edit-os" href="#" data-codigo="' + codigo + '"><i class="bi bi-pencil me-2 text-primary"></i>Datos Generales</a></li>'
                        + '    <li><hr class="dropdown-divider"></li>'
                        + '    <li class="dropdown-header text-uppercase small fw-bold text-muted">Prestaciones</li>'
                        + '    <li><a class="dropdown-item btn-prestaciones" href="#" data-codigo="' + codigo + '"><i class="bi bi-list-check me-2 text-info"></i>Prestaciones</a></li>'
                        + '    <li><a class="dropdown-item btn-hist-prestaciones" href="#" data-codigo="' + codigo + '"><i class="bi bi-clock-history me-2 text-secondary"></i>Histórico Prestaciones</a></li>'
                        + '    <li><hr class="dropdown-divider"></li>'
                        + '    <li class="dropdown-header text-uppercase small fw-bold text-muted">Coseguros</li>'
                        + '    <li><a class="dropdown-item btn-coseguros" href="#" data-codigo="' + codigo + '"><i class="bi bi-shield-minus me-2 text-warning"></i>Coseguros</a></li>'
                        + '    <li><a class="dropdown-item btn-hist-coseguros" href="#" data-codigo="' + codigo + '"><i class="bi bi-journal-text me-2 text-dark"></i>Histórico Coseguros</a></li>'
                        + '    <li><hr class="dropdown-divider"></li>'
                        + '    <li><a class="dropdown-item btn-borrar-os text-danger" href="#" data-codigo="' + codigo + '"><i class="bi bi-trash me-2"></i>Borrar</a></li>'
                        + '  </ul>'
                        + '</div>';
                }
            }
        ]
    });

    function esc(valor) {
        return $('<div>').text(valor == null ? '' : String(valor)).html();
    }

    function escAttr(valor) {
        return esc(valor).replace(/"/g, '&quot;');
    }

    function fmtFecha(valor) {
        if (!valor) {
            return '';
        }
        var s = String(valor);
        if (s.indexOf('0000-00-00') === 0) {
            return '';
        }
        var p = s.substring(0, 10).split('-');
        if (p.length !== 3) {
            return s.substring(0, 10);
        }
        return p[2] + '/' + p[1] + '/' + p[0];
    }

    function cargarObrasSociales() {
        var incluirInactivas = $('#chkMostrarInactivas').is(':checked') ? 1 : 0;
        $('#osResumen').text(incluirInactivas ? 'Cargando todas las obras sociales…' : 'Cargando obras sociales vigentes…');

        $.ajax({
            url: 'index.php?route=obras_sociales&action=listar',
            type: 'GET',
            data: { incluir_inactivas: incluirInactivas },
            dataType: 'json',
            success: function (res) {
                if (!res || !res.ok) {
                    $('#osResumen').text('No se pudo cargar el listado.');
                    Swal.fire({ icon: 'error', title: 'Obras Sociales', text: (res && res.msg) ? res.msg : 'Error al consultar.' });
                    return;
                }
                var filas = res.data || [];
                codigosCargados = filas.map(function (r) { return r.TACODIGO; });
                tablaOS.clear();
                tablaOS.rows.add(filas).draw();
                var bajas = filas.filter(function (r) { return r.ES_INACTIVA; }).length;
                if (incluirInactivas) {
                    $('#osResumen').text(filas.length + ' obras sociales (' + bajas + ' dadas de baja)');
                } else {
                    $('#osResumen').text(filas.length + ' obras sociales vigentes');
                }
            },
            error: function (xhr) {
                var msg = 'No se pudo cargar el listado.';
                if (xhr.responseJSON && xhr.responseJSON.msg) {
                    msg = xhr.responseJSON.msg;
                }
                $('#osResumen').text(msg);
                Swal.fire({ icon: 'error', title: 'Obras Sociales', text: msg });
            }
        });
    }

    function ponerOpcion(selector, valor) {
        var $s = $(selector);
        valor = valor || '';
        if (valor && $s.find('option').filter(function () { return this.value === valor; }).length === 0) {
            $s.append($('<option>').val(valor).text(valor));
        }
        $s.val(valor);
    }

    function limpiarFormulario() {
        var form = document.getElementById('frmObraSocial');
        form.reset();
        $('#osModo').val('alta');
        $('#osCodigo').prop('readonly', false);
        $('#osFechaFin').val('2099-12-31');
        $('#osCosegCon, #osCosegPra, #osCosegDet, #osCapita, #osRefact').val('0.00');
        $('#modalObraSocialTitulo').text('Agregar Obra Social');
        $('#osAfilAdh').text('—');
        sincronizarRutas();
    }

    function archivoPublicoObra(valor) {
        valor = $.trim(valor || '');
        if (!valor || /[\\:]/.test(valor) || valor.indexOf('..') !== -1) {
            return '';
        }
        var nombre = valor.split('/').pop();
        if (!nombre) {
            return '';
        }
        return 'public/uploads/obrassoc/' + encodeURIComponent(nombre);
    }

    function actualizarVerArchivo(chk, input, ver) {
        var mostrar = $(chk).is(':checked') && $.trim($(input).val()) !== '';
        $(ver).toggleClass('d-none', !mostrar);
    }

    function habilitarRuta(chk, input, boton, zona, ver) {
        var activo = $(chk).is(':checked');
        $(input).prop('readonly', !activo).toggleClass('bg-light', !activo);
        $(boton).prop('disabled', !activo);
        $(zona)
            .toggleClass('opacity-50', !activo)
            .toggleClass('os-zona-activa', activo)
            .css('pointer-events', activo ? '' : 'none')
            .attr('data-activo', activo ? '1' : '0');
        actualizarVerArchivo(chk, input, ver);
    }

    function sincronizarRutas() {
        habilitarRuta('#osMailCoseg', '#osDirCoseg', '#btnRutaCoseg', '#dropCoseg', '#btnVerCoseg');
        habilitarRuta('#osMailNorma', '#osDirNorma', '#btnRutaNorma', '#dropNorma', '#btnVerNorma');
    }

    function abrirArchivoObra(input) {
        var url = archivoPublicoObra($(input).val());
        if (!url) {
            Swal.fire({
                icon: 'info',
                title: 'Ver archivo',
                text: 'Esa ruta es local y no está en el servidor. Arrastrá el archivo para poder abrirlo.'
            });
            return;
        }
        window.open(url, '_blank', 'noopener');
    }

    function subirArchivoObra(archivo, input, boton, tipo) {
        if (!archivo) {
            return;
        }
        var $input = $(input);
        var previo = $input.val();
        var $btn = $(boton);
        var icono = $btn.html();
        $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i>');
        var fd = new FormData();
        fd.append('csrf_token', CSRF);
        fd.append('archivo', archivo);
        fd.append('tipo', tipo);
        fd.append('codigo', $.trim($('#osCodigo').val()));
        $.ajax({
            url: 'index.php?route=obras_sociales&action=subir_archivo',
            type: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function (res) {
                if (!res || !res.ok || !res.nombre) {
                    $input.val(previo);
                    Swal.fire({ icon: 'error', title: 'Archivo', text: (res && res.msg) ? res.msg : 'No se pudo guardar el archivo.' });
                    return;
                }
                $input.val(res.nombre).trigger('input');
            },
            error: function (xhr) {
                $input.val(previo);
                var msg = (xhr.responseJSON && xhr.responseJSON.msg) ? xhr.responseJSON.msg : 'No se pudo guardar el archivo.';
                Swal.fire({ icon: 'error', title: 'Archivo', text: msg });
            },
            complete: function () {
                $btn.html(icono);
                sincronizarRutas();
            }
        });
    }

    function engancharDrop(zona, input, fileInput, chk, boton, tipo) {
        var nodo = document.querySelector(zona);
        if (!nodo) {
            return;
        }
        function activo() {
            return $(chk).is(':checked');
        }
        nodo.addEventListener('dragenter', function (e) {
            e.preventDefault();
            e.stopPropagation();
            if (activo()) {
                nodo.classList.add('os-drop-on');
            }
        }, true);
        nodo.addEventListener('dragover', function (e) {
            e.preventDefault();
            e.stopPropagation();
            if (e.dataTransfer) {
                e.dataTransfer.dropEffect = activo() ? 'copy' : 'none';
            }
            if (activo()) {
                nodo.classList.add('os-drop-on');
            }
        }, true);
        nodo.addEventListener('dragleave', function (e) {
            if (!nodo.contains(e.relatedTarget)) {
                nodo.classList.remove('os-drop-on');
            }
        }, true);
        nodo.addEventListener('drop', function (e) {
            e.preventDefault();
            e.stopPropagation();
            nodo.classList.remove('os-drop-on');
            if (!activo()) {
                return;
            }
            var files = e.dataTransfer && e.dataTransfer.files;
            if (files && files[0]) {
                subirArchivoObra(files[0], input, boton, tipo);
            }
        }, true);
        $(fileInput).on('change', function () {
            var archivo = this.files && this.files[0];
            if (archivo && activo()) {
                subirArchivoObra(archivo, input, boton, tipo);
            }
            this.value = '';
        });
    }

    function aplicarFicha(d) {
        $('#osCodigo').val(d.TACODIGO || '');
        $('#osNombre').val(d.TADESCRIP || '');
        $('#osMesaOp').val(d.TAMESAOP || '');
        $('#osDireccion').val(d.TADIR || '');
        $('#osCp').val(d.TACP || '');
        $('#osTelefono').val(d.TATELEF || '');
        $('#osContacto').val(d.TACONTACTO || '');
        $('#osCuit').val(d.TACUIT || '');
        ponerOpcion('#osCondIva', d.TACONDIVA || '');
        $('#osCosegCon').val(d.TACOSEGCON || '0.00');
        $('#osCosegPra').val(d.TACOSEGPRA || '0.00');
        $('#osCosegDet').val(d.TACOSEGDET || '0.00');
        $('#osPadron').val(d.TAPADRON || '');
        $('#osAfiliados').val(d.TACANTAFIL == null ? '' : d.TACANTAFIL);
        $('#osAfilAdh').text('—');
        $('#osCapita').val(d.TAVALCAPIT || '0.00');
        $('#osRefact').val(d.TAREFACT || '0.00');
        $('#osFechaIni').val(d.TAFECHAINI || '');
        $('#osFechaFin').val(d.TAFECHAFIN || '');
        ponerOpcion('#osGrupo', d.GRUPO_OS || '');
        $('#osEmail').val(d.TAEMAIL || '');
        $('#osEmailCar').val(d.TAMAILCAR || '');
        $('#osDirCoseg').val(d.DIRCOSEG || '');
        $('#osLinkCoseg').val(d.LINKCOSEG || '');
        $('#osDirNorma').val(d.DIRNORMA || '');
        $('#osPorPrest').prop('checked', d.XPREST === 'T');
        $('#osExcCart').prop('checked', d.TAEXCCAR === 'T');
        $('#osMailCoseg').prop('checked', d.MAILCOSEG === 'T');
        $('#osMailNorma').prop('checked', d.MAILNORMA === 'T');
        $('#osVerPlan').prop('checked', d.ISVERPLAN === 'T');
        $('#osConvAdm').prop('checked', d.CONVADM === 'T');
        sincronizarRutas();
    }

    function abrirAlta() {
        limpiarFormulario();
        modalOS.show();
        setTimeout(function () { $('#osCodigo').trigger('focus'); }, 250);
    }

    function abrirEdicionObraSocial(codigo) {
        $.ajax({
            url: 'index.php?route=obras_sociales&action=obtener',
            type: 'GET',
            data: { codigo: codigo },
            dataType: 'json',
            success: function (res) {
                if (!res || !res.ok || !res.data) {
                    Swal.fire({ icon: 'error', title: 'Datos de la obra social', text: (res && res.msg) ? res.msg : 'No se encontró la obra social.' });
                    return;
                }
                var d = res.data;
                limpiarFormulario();
                $('#osModo').val('edicion');
                aplicarFicha(d);
                $('#osCodigo').prop('readonly', true);
                $('#modalObraSocialTitulo').text('Datos de la Obra Social — ' + (d.TACODIGO || ''));
                modalOS.show();
            },
            error: function (xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.msg) ? xhr.responseJSON.msg : 'No se pudo leer la obra social.';
                Swal.fire({ icon: 'error', title: 'Datos de la obra social', text: msg });
            }
        });
    }

    function avisoModulo(titulo, codigo) {
        Swal.fire({
            icon: 'info',
            title: titulo,
            text: 'La pantalla de ' + titulo + ' para ' + codigo + ' se habilita en el módulo correspondiente.'
        });
    }

    $('#chkMostrarInactivas').on('change', function () {
        tablaOS.page.len(this.checked ? 50 : 25);
        cargarObrasSociales();
    });

    $('#btnAgregarOS').on('click', abrirAlta);

    $('#btnCierrePeriodo').on('click', function () {
        document.getElementById('frmCierrePeriodo').reset();
        var lista = document.getElementById('listaCodigosOS');
        lista.innerHTML = '';
        codigosCargados.forEach(function (codigo) {
            var opt = document.createElement('option');
            opt.value = codigo;
            lista.appendChild(opt);
        });
        modalCierre.show();
    });

    $('#cierreTodas').on('change', function () {
        $('#cierreCodigo').prop('disabled', this.checked);
        if (this.checked) {
            $('#cierreCodigo').val('');
        }
    });

    $('#tablaObrasSociales').on('click', '.btn-edit-os', function (e) {
        e.preventDefault();
        abrirEdicionObraSocial($(this).attr('data-codigo'));
    });

    $('#tablaObrasSociales tbody').on('dblclick', 'tr', function () {
        var data = tablaOS.row(this).data();
        if (data && data.TACODIGO) {
            abrirEdicionObraSocial(data.TACODIGO);
        }
    });

    var btnVerificarPadron = document.getElementById('btnVerificarPadron');
    if (btnVerificarPadron && window.bootstrap && bootstrap.Tooltip) {
        new bootstrap.Tooltip(btnVerificarPadron);
    }
    $('#btnVerificarPadron').on('click', function () {
        Swal.fire({
            icon: 'info',
            title: 'Verificar padrón',
            text: 'Todavía es informativo. Más adelante completa afiliados directos y la suma de adherentes.'
        });
    });

    $('#osMailCoseg').on('change', function () {
        if (!this.checked) {
            $('#osDirCoseg').val('');
        }
        habilitarRuta('#osMailCoseg', '#osDirCoseg', '#btnRutaCoseg', '#dropCoseg', '#btnVerCoseg');
        if (this.checked) {
            $('#osDirCoseg').trigger('focus');
        }
    });
    $('#osMailNorma').on('change', function () {
        if (!this.checked) {
            $('#osDirNorma').val('');
        }
        habilitarRuta('#osMailNorma', '#osDirNorma', '#btnRutaNorma', '#dropNorma', '#btnVerNorma');
        if (this.checked) {
            $('#osDirNorma').trigger('focus');
        }
    });
    $('#osDirCoseg').on('input', function () {
        actualizarVerArchivo('#osMailCoseg', '#osDirCoseg', '#btnVerCoseg');
    });
    $('#osDirNorma').on('input', function () {
        actualizarVerArchivo('#osMailNorma', '#osDirNorma', '#btnVerNorma');
    });
    $('#btnVerCoseg').on('click', function () {
        abrirArchivoObra('#osDirCoseg');
    });
    $('#btnVerNorma').on('click', function () {
        abrirArchivoObra('#osDirNorma');
    });
    $('#btnRutaCoseg').on('click', function () {
        if (!$('#osMailCoseg').is(':checked')) {
            return;
        }
        $('#fileCoseg').trigger('click');
    });
    $('#btnRutaNorma').on('click', function () {
        if (!$('#osMailNorma').is(':checked')) {
            return;
        }
        $('#fileNorma').trigger('click');
    });
    engancharDrop('#dropCoseg', '#osDirCoseg', '#fileCoseg', '#osMailCoseg', '#btnRutaCoseg', 'coseg');
    engancharDrop('#dropNorma', '#osDirNorma', '#fileNorma', '#osMailNorma', '#btnRutaNorma', 'norma');

    function avisoFicha(titulo) {
        avisoModulo(titulo, $.trim($('#osCodigo').val()) || 'esta obra social');
    }
    $('#btnOsPlanes').on('click', function () { avisoFicha('Planes'); });
    $('#btnOsPlanesOs').on('click', function () { avisoFicha('Planes OS'); });
    $('#btnOsNombrePrest').on('click', function () { avisoFicha('Nombre OS a Prestador'); });
    $('#btnOsPadrones').on('click', function () { avisoFicha('Padrones'); });
    $('#btnOsCopiarPrest').on('click', function () { avisoFicha('Copiar Prestadores a'); });

    $('#tablaObrasSociales').on('click', '.btn-prestaciones', function (e) {
        e.preventDefault();
        avisoModulo('Prestaciones', $(this).attr('data-codigo'));
    });
    $('#tablaObrasSociales').on('click', '.btn-hist-prestaciones', function (e) {
        e.preventDefault();
        avisoModulo('Histórico Prestaciones', $(this).attr('data-codigo'));
    });
    $('#tablaObrasSociales').on('click', '.btn-coseguros', function (e) {
        e.preventDefault();
        avisoModulo('Coseguros', $(this).attr('data-codigo'));
    });
    $('#tablaObrasSociales').on('click', '.btn-hist-coseguros', function (e) {
        e.preventDefault();
        avisoModulo('Histórico Coseguros', $(this).attr('data-codigo'));
    });

    $('#tablaObrasSociales').on('click', '.btn-borrar-os', function (e) {
        e.preventDefault();
        borrarObraSocial($(this).attr('data-codigo'));
    });

    function borrarObraSocial(codigo) {
        Swal.fire({
            icon: 'warning',
            title: '¿Confirma el Borrado?',
            text: 'Se eliminará la obra social ' + codigo + ' y sus asignaciones a prestadores y prestaciones. Esta acción no se puede deshacer.',
            showCancelButton: true,
            focusCancel: true,
            confirmButtonText: 'Sí, borrar',
            cancelButtonText: 'No',
            confirmButtonColor: '#dc3545'
        }).then(function (result) {
            if (!result.isConfirmed) {
                return;
            }
            $.ajax({
                url: 'index.php?route=obras_sociales&action=eliminar',
                type: 'POST',
                data: {
                    csrf_token: CSRF,
                    codigo: codigo
                },
                dataType: 'json',
                success: function (res) {
                    if (res && res.ok) {
                        Swal.fire({ icon: 'success', title: 'Obra social eliminada', text: res.msg });
                        cargarObrasSociales();
                        return;
                    }
                    Swal.fire({
                        icon: 'error',
                        title: 'Borrar',
                        text: (res && (res.error || res.msg)) ? (res.error || res.msg) : 'No se pudo eliminar.'
                    });
                },
                error: function (xhr) {
                    var msg = 'Error de comunicación con el servidor.';
                    if (xhr.responseJSON && (xhr.responseJSON.error || xhr.responseJSON.msg)) {
                        msg = xhr.responseJSON.error || xhr.responseJSON.msg;
                    }
                    Swal.fire({ icon: 'error', title: 'Borrar', text: msg });
                }
            });
        });
    }

    $('#frmObraSocial').on('submit', function (e) {
        e.preventDefault();
        var $btn = $('#btnGuardarOS').prop('disabled', true);
        $.ajax({
            url: 'index.php?route=obras_sociales&action=guardar',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function (res) {
                if (!res || !res.ok) {
                    Swal.fire({ icon: 'error', title: 'Guardar', text: (res && res.msg) ? res.msg : 'No se pudo guardar.' });
                    return;
                }
                modalOS.hide();
                Swal.fire({ icon: 'success', title: 'Obras Sociales', text: res.msg, timer: 1600, showConfirmButton: false });
                cargarObrasSociales();
            },
            error: function (xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.msg) ? xhr.responseJSON.msg : 'No se pudo guardar.';
                Swal.fire({ icon: 'error', title: 'Guardar', text: msg });
            },
            complete: function () {
                $btn.prop('disabled', false);
            }
        });
    });

    $('#frmCierrePeriodo').on('submit', function (e) {
        e.preventDefault();
        var todas = $('#cierreTodas').is(':checked');
        var codigo = $.trim($('#cierreCodigo').val());
        var fecha = $('#cierreFecha').val();
        if (!fecha) {
            Swal.fire({ icon: 'warning', title: 'Cierre de período', text: 'Indique la fecha de cierre.' });
            return;
        }
        if (!todas && codigo === '') {
            Swal.fire({ icon: 'warning', title: 'Cierre de período', text: 'Indique un código o marque el cierre de todas las vigentes.' });
            return;
        }

        var detalle = todas
            ? 'Se cerrará el período de todas las obras sociales vigentes al ' + fecha + '.'
            : 'Se cerrará el período de ' + codigo + ' al ' + fecha + '.';

        Swal.fire({
            icon: 'warning',
            title: 'Confirmar cierre',
            text: detalle,
            showCancelButton: true,
            confirmButtonText: 'Registrar cierre',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#f0ad4e'
        }).then(function (result) {
            if (!result.isConfirmed) {
                return;
            }
            $.ajax({
                url: 'index.php?route=obras_sociales&action=cierre',
                type: 'POST',
                data: {
                    csrf_token: CSRF,
                    fecha: fecha,
                    codigo: todas ? '' : codigo,
                    aplicar_todas: todas ? 1 : 0
                },
                dataType: 'json',
                success: function (res) {
                    if (!res || !res.ok) {
                        Swal.fire({ icon: 'error', title: 'Cierre de período', text: (res && res.msg) ? res.msg : 'No se pudo registrar.' });
                        return;
                    }
                    modalCierre.hide();
                    Swal.fire({ icon: 'success', title: 'Cierre de período', text: res.msg });
                    cargarObrasSociales();
                },
                error: function (xhr) {
                    var msg = (xhr.responseJSON && xhr.responseJSON.msg) ? xhr.responseJSON.msg : 'No se pudo registrar el cierre.';
                    Swal.fire({ icon: 'error', title: 'Cierre de período', text: msg });
                }
            });
        });
    });

    cargarObrasSociales();
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
