<?php
/**
 * views/plantillas_emails/index.php
 * Módulo: Tablas generales → Cabecera Mails
 * Grilla DataTables + formulario crear/editar.
 */

$pageTitle  = $pageTitle  ?? 'COMEDICA — Cabecera Mails';
$breadcrumb = $breadcrumb ?? [
    ['label' => 'Tablas generales'],
    ['label' => 'Cabecera Mails'],
];
$csrfToken = $csrfToken ?? (string) ($_SESSION['csrf_token'] ?? '');

require_once __DIR__ . '/../../models/Permission.php';
require_once __DIR__ . '/../../views/layouts/header.php';
?>

<!-- ═══════════════ CABECERA DEL MÓDULO ══════════════════════════════════ -->
<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
    <div>
        <h5 class="mb-1 fw-bold text-primary">
            <i class="fa-solid fa-envelope-open-text me-2"></i>Cabecera Mails
        </h5>
        <p class="text-muted mb-0" style="font-size:0.82rem;">
            Tablas generales &rsaquo; Cabecera Mails &mdash; Plantillas de correo electrónico
        </p>
    </div>
    <div class="d-flex gap-2 align-items-center flex-wrap">
        <span id="pe-total-badge"
              class="badge bg-secondary bg-opacity-75 px-3 py-2"
              style="font-size:0.8rem;">
            <i class="fa-solid fa-list-ol me-1"></i>Cargando…
        </span>
        <button type="button" id="btn-nueva-plantilla" class="btn btn-sm btn-success">
            <i class="fa-solid fa-plus me-1"></i>Nuevo
        </button>
    </div>
</div>

<!-- ═══════════════ BARRA DE FILTROS ════════════════════════════════════ -->
<div class="card border-0 shadow-sm mb-3" style="border-radius:10px;">
    <div class="card-body py-2 px-3">
        <div class="row g-2 align-items-center">
            <div class="col-md-6">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light">
                        <i class="fa-solid fa-magnifying-glass text-muted"></i>
                    </span>
                    <input type="text" id="flt-busqueda" class="form-control"
                           placeholder="Buscar por nombre de uso, código o asunto…"
                           autocomplete="off">
                </div>
            </div>
            <div class="col-md-3">
                <select id="flt-activo" class="form-select form-select-sm">
                    <option value="">— Todos los estados —</option>
                    <option value="1">Activo</option>
                    <option value="0">Inactivo</option>
                </select>
            </div>
            <div class="col-md-3">
                <button type="button" id="btn-buscar" class="btn btn-primary btn-sm w-100">
                    <i class="fa-solid fa-search me-1"></i>Buscar
                </button>
            </div>
        </div>
    </div>
</div>

<div id="pe-alert" class="alert d-none mb-3 py-2" role="alert"></div>

<!-- ═══════════════ GRILLA PRINCIPAL ════════════════════════════════════ -->
<div class="card card-module">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2 py-2">
        <span style="font-size:0.88rem;">
            <i class="fa-solid fa-table me-2"></i>Listado de plantillas
        </span>
    </div>
    <div class="card-body p-3">
        <div class="table-responsive">
            <table id="tbl-plantillas"
                   class="table table-hover table-bordered table-sm mb-0"
                   data-page-length="25"
                   data-order='[[0,"asc"]]'>
                <thead>
                    <tr>
                        <th>Nombre de Uso</th>
                        <th>Código</th>
                        <th>Asunto</th>
                        <th class="text-center" style="width:110px;">Estado</th>
                        <th class="no-export text-center" style="width:90px;">Acciones</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<!-- ═══════════════ MODAL CREAR / EDITAR ════════════════════════════════ -->
<div class="modal fade" id="modal-plantilla" tabindex="-1"
     aria-labelledby="modal-plantilla-title" aria-hidden="true"
     data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="border-radius:12px; overflow:hidden;">
            <form id="form-plantilla" autocomplete="off">
                <div class="modal-header py-2 px-3 text-white"
                     style="background:linear-gradient(90deg,#0d47a1,#1976d2);">
                    <h6 class="modal-title fw-semibold mb-0" id="modal-plantilla-title">
                        <i class="fa-solid fa-plus me-1"></i>Nueva plantilla
                    </h6>
                    <button type="button" class="btn-close btn-close-white"
                            data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <input type="hidden" name="id" id="pe-id" value="">

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold" for="pe-codigo">
                                Código <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control form-control-sm font-monospace"
                                   id="pe-codigo" name="codigo" maxlength="50" required>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label small fw-semibold" for="pe-nombre-uso">
                                Nombre de Uso <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control form-control-sm"
                                   id="pe-nombre-uso" name="nombre_uso" maxlength="150" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold" for="pe-asunto">
                                Asunto <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control form-control-sm"
                                   id="pe-asunto" name="asunto" maxlength="255" required>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label small fw-semibold" for="pe-destinatarios">
                                Destinatarios
                            </label>
                            <input type="text" class="form-control form-control-sm"
                                   id="pe-destinatarios" name="destinatarios" maxlength="255"
                                   placeholder="uno@correo.com; otro@correo.com">
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <div class="form-check form-switch mb-1">
                                <input class="form-check-input" type="checkbox" role="switch"
                                       id="pe-activo" name="activo" value="1" checked>
                                <label class="form-check-label small" for="pe-activo">Activo</label>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold" for="pe-cuerpo">Cuerpo</label>
                            <textarea class="form-control" id="pe-cuerpo" name="cuerpo"
                                      rows="12" style="min-height:220px; font-family:Consolas,monospace; font-size:0.85rem;"
                                      placeholder="Cuerpo del mail (texto o HTML)"></textarea>
                        </div>
                    </div>
                    <div id="pe-form-error" class="alert alert-danger py-2 px-3 mt-3 mb-0 d-none small"></div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">
                        Cancelar
                    </button>
                    <button type="submit" id="btn-guardar-plantilla" class="btn btn-sm btn-primary">
                        <i class="fa-solid fa-floppy-disk me-1"></i>Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    var $ = window.jQuery;
    if (!$) return;

    var CSRF = <?= json_encode($csrfToken, JSON_UNESCAPED_UNICODE) ?>;
    var dt   = null;

    function apiUrl(action, extra) {
        var q = 'index.php?route=cabecera-mails&action=' + encodeURIComponent(action);
        return extra ? (q + extra) : q;
    }

    function showAlert(msg, ok) {
        var el = document.getElementById('pe-alert');
        el.className = 'alert mb-3 py-2 ' + (ok ? 'alert-success' : 'alert-danger');
        el.innerHTML = '<i class="fa-solid ' + (ok ? 'fa-circle-check' : 'fa-triangle-exclamation') + ' me-1"></i>'
                     + $('<div>').text(msg).html();
        el.classList.remove('d-none');
    }

    function badgeEstado(activo) {
        if (parseInt(activo, 10) === 1) {
            return '<span class="badge bg-success">Activo</span>';
        }
        return '<span class="badge bg-secondary">Inactivo</span>';
    }

    function botonesAccion(id) {
        return '<div class="d-flex justify-content-center gap-1">'
             + '<button type="button" class="btn btn-sm btn-primary pe-edit" data-id="' + id + '" title="Editar">'
             + '<i class="fa-solid fa-pen"></i></button>'
             + '<button type="button" class="btn btn-sm btn-danger pe-del" data-id="' + id + '" title="Borrar">'
             + '<i class="fa-solid fa-trash"></i></button>'
             + '</div>';
    }

    function cargarGrilla() {
        var params = {
            busqueda: $('#flt-busqueda').val() || '',
            activo:   $('#flt-activo').val() || '',
            pagina:   1,
            por_pagina: 500
        };

        $.ajax({
            url: apiUrl('listado'),
            method: 'GET',
            data: params,
            dataType: 'json',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        }).done(function (res) {
            if (!res || !res.ok) {
                showAlert((res && res.error) || 'No se pudo cargar el listado.', false);
                return;
            }

            var rows = res.datos || [];
            $('#pe-total-badge').html(
                '<i class="fa-solid fa-list-ol me-1"></i>' + rows.length + ' registro' + (rows.length === 1 ? '' : 's')
            );

            if (dt) {
                dt.clear();
                dt.rows.add(rows);
                dt.draw();
                return;
            }

            if ($.fn.DataTable.isDataTable('#tbl-plantillas')) {
                $('#tbl-plantillas').DataTable().destroy();
            }

            dt = window.MedicalGrid
                ? MedicalGrid.init('#tbl-plantillas', {
                    data: rows,
                    columns: [
                        { data: 'nombre_uso' },
                        { data: 'codigo' },
                        { data: 'asunto' },
                        {
                            data: 'activo',
                            className: 'text-center',
                            render: function (val) { return badgeEstado(val); }
                        },
                        {
                            data: 'id',
                            className: 'text-center no-export',
                            orderable: false,
                            searchable: false,
                            render: function (val) { return botonesAccion(val); }
                        }
                    ],
                    order: [[0, 'asc']]
                })
                : $('#tbl-plantillas').DataTable({
                    data: rows,
                    pageLength: 25,
                    columns: [
                        { data: 'nombre_uso' },
                        { data: 'codigo' },
                        { data: 'asunto' },
                        { data: 'activo', className: 'text-center', render: function (v) { return badgeEstado(v); } },
                        { data: 'id', className: 'text-center', orderable: false, render: function (v) { return botonesAccion(v); } }
                    ]
                });
        }).fail(function () {
            showAlert('Error de comunicación al cargar las plantillas.', false);
        });
    }

    function getModal() {
        return bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-plantilla'));
    }

    function resetForm() {
        var form = document.getElementById('form-plantilla');
        form.reset();
        $('#pe-id').val('');
        $('#pe-activo').prop('checked', true);
        $('#pe-form-error').addClass('d-none').text('');
    }

    function abrirAlta() {
        resetForm();
        $('#modal-plantilla-title').html('<i class="fa-solid fa-plus me-1"></i>Nueva plantilla');
        getModal().show();
        setTimeout(function () { $('#pe-codigo').trigger('focus'); }, 250);
    }

    function abrirEdicion(id) {
        if (!id) return;
        $.ajax({
            url: apiUrl('obtener', '&id=' + encodeURIComponent(id)),
            method: 'GET',
            dataType: 'json',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        }).done(function (res) {
            if (!res || !res.ok || !res.registro) {
                showAlert((res && res.error) || 'No se pudo cargar la plantilla.', false);
                return;
            }
            resetForm();
            var r = res.registro;
            $('#pe-id').val(r.id || '');
            $('#pe-codigo').val(r.codigo || '');
            $('#pe-nombre-uso').val(r.nombre_uso || '');
            $('#pe-asunto').val(r.asunto || '');
            $('#pe-destinatarios').val(r.destinatarios || '');
            $('#pe-cuerpo').val(r.cuerpo || '');
            $('#pe-activo').prop('checked', parseInt(r.activo, 10) === 1);
            $('#modal-plantilla-title').html('<i class="fa-solid fa-pen-to-square me-1"></i>Editar plantilla');
            getModal().show();
        }).fail(function () {
            showAlert('Error de comunicación al cargar la plantilla.', false);
        });
    }

    function borrarId(id) {
        if (!id) return;
        if (!confirm('¿Eliminar la plantilla #' + id + '? Esta acción no se puede deshacer.')) return;

        $.ajax({
            url: apiUrl('eliminar'),
            method: 'POST',
            dataType: 'json',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            data: { csrf_token: CSRF, id: id }
        }).done(function (res) {
            if (!res || !res.ok) {
                showAlert((res && res.error) || 'No se pudo eliminar.', false);
                return;
            }
            showAlert(res.msg || 'Plantilla eliminada.', true);
            cargarGrilla();
        }).fail(function () {
            showAlert('Error de comunicación al eliminar.', false);
        });
    }

    $('#btn-nueva-plantilla').on('click', abrirAlta);
    $('#btn-buscar').on('click', cargarGrilla);
    $('#flt-busqueda').on('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            cargarGrilla();
        }
    });
    $('#flt-activo').on('change', cargarGrilla);

    $('#tbl-plantillas').on('click', '.pe-edit', function () {
        abrirEdicion(parseInt($(this).data('id'), 10) || 0);
    });
    $('#tbl-plantillas').on('click', '.pe-del', function () {
        borrarId(parseInt($(this).data('id'), 10) || 0);
    });

    $('#form-plantilla').on('submit', function (e) {
        e.preventDefault();
        var err = $('#pe-form-error');
        err.addClass('d-none').text('');

        var $btn = $('#btn-guardar-plantilla');
        $btn.prop('disabled', true);

        var data = {
            csrf_token: CSRF,
            id: $('#pe-id').val(),
            codigo: $('#pe-codigo').val(),
            nombre_uso: $('#pe-nombre-uso').val(),
            asunto: $('#pe-asunto').val(),
            destinatarios: $('#pe-destinatarios').val(),
            cuerpo: $('#pe-cuerpo').val(),
            activo: $('#pe-activo').is(':checked') ? '1' : '0'
        };

        $.ajax({
            url: apiUrl('guardar'),
            method: 'POST',
            dataType: 'json',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            data: data
        }).done(function (res) {
            $btn.prop('disabled', false);
            if (!res || !res.ok) {
                err.text((res && res.error) || 'No se pudo guardar.').removeClass('d-none');
                return;
            }
            getModal().hide();
            showAlert(res.msg || 'Plantilla guardada.', true);
            cargarGrilla();
        }).fail(function () {
            $btn.prop('disabled', false);
            err.text('Error de comunicación al guardar.').removeClass('d-none');
        });
    });

    cargarGrilla();
});
</script>

<?php require_once __DIR__ . '/../../views/layouts/footer.php'; ?>
