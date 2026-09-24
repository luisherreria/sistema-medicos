<?php
/**
 * registro_facturas_pdf.php
 * Grilla de pendientes (t_facturas_temp) para completar y confirmar.
 */
require_once dirname(__FILE__) . '/includes/facturas_pdf_common.php';
require_once dirname(__FILE__) . '/models/FacturasTempModel.php';

rfpRequireAuth();
rfpRequirePermiso();

$model = new FacturasTempModel();
$filas = array();
try {
    $filas = $model->listarPendientes();
} catch (PDOException $e) {
    $errorLista = $e->getMessage();
}

$pageTitle  = 'COMEDICA — Pendientes PDF';
$breadcrumb = array(
    array('label' => 'Carga de Datos'),
    array('label' => 'Registración de Facturas', 'url' => 'index.php?route=registro-facturas'),
    array('label' => 'Pendientes PDF'),
);
$csrf = isset($_SESSION['csrf_token']) ? $_SESSION['csrf_token'] : '';
$extraCss = array('https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/dropzone.min.css');

require_once dirname(__FILE__) . '/views/layouts/header.php';
?>

<div class="rfp-topbar d-flex align-items-center justify-content-between flex-wrap gap-2">
    <div class="d-flex align-items-center gap-2">
        <div class="rfp-topbar-icon"><i class="fa-solid fa-clipboard-list"></i></div>
        <h1 class="rfp-topbar-title mb-0">Pendientes de Carga Automática PDF</h1>
    </div>
    <div class="d-flex gap-2">
        <a href="index.php?route=registro-facturas"
           class="btn btn-sm text-white fw-semibold"
           style="background:rgba(255,255,255,.18);border:1px solid rgba(255,255,255,.45);font-size:.78rem;">
            <i class="fa-solid fa-arrow-left me-1"></i>Volver
        </a>
    </div>
</div>

<!-- INICIO ZONA DE CARGA DROPZONE -->
<div class="card mb-4 border-primary">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fa fa-upload"></i> Subir Nuevas Facturas</h5>
    </div>
    <div class="card-body">
        <form action="procesar_pdf.php" class="dropzone" id="miDropzone" style="border: 2px dashed #0d6efd; background: #f8f9fa; border-radius: 5px;">
            <div class="dz-message text-center">
                <h4>Arrastrá los PDF aquí o hacé clic para seleccionar</h4>
                <span class="text-muted">Las facturas procesadas aparecerán automáticamente en la tabla de abajo.</span>
            </div>
        </form>
        <div id="rfp-upload-msg" class="alert d-none mt-3 py-2 mb-0" style="font-size:.85rem;"></div>
    </div>
</div>
<!-- FIN ZONA DE CARGA DROPZONE -->

<div class="rfp-grid-wrap">
    <table id="tabla-facturas" class="table table-sm table-striped table-hover w-100" style="font-size:.78rem;">
        <thead>
        <tr>
            <th class="text-center" style="width:36px;">
                <input type="checkbox" id="rfp-check-all" title="Seleccionar todas">
            </th>
            <th>Prestador</th>
            <th style="width:110px;">Cód. Prestador</th>
            <th style="width:95px;">Fecha</th>
            <th style="width:140px;">Nro Factura</th>
            <th style="width:100px;" class="text-end">Importe</th>
            <th style="width:140px;">O. Social</th>
            <th style="width:90px;">Período</th>
            <th style="width:90px;" class="text-center">Texto OCR</th>
            <th style="width:110px;" class="text-center">Acción</th>
        </tr>
        </thead>
        <tbody>
        <?php if (!empty($filas)): ?>
            <?php foreach ($filas as $r): ?>
                <?php echo rfpHtmlFilaPendiente($r); ?>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
    <button type="button" id="rfp-btn-confirmar" class="btn btn-sm btn-success fw-semibold" style="font-size:.78rem;">
        <i class="fa-solid fa-check me-1"></i>Confirmar Seleccionadas
    </button>
    <button type="button" id="rfp-btn-borrar" class="btn btn-sm btn-outline-danger fw-semibold" style="font-size:.78rem;">
        <i class="fa-solid fa-trash me-1"></i>Borrar Seleccionados
    </button>
</div>

<style>
.rfp-topbar {
    background: linear-gradient(90deg, #0d47a1 0%, #1565c0 60%, #1976d2 100%);
    color: #fff;
    border-radius: 10px;
    padding: 12px 16px;
    margin-bottom: 12px;
    box-shadow: 0 2px 8px rgba(13, 71, 161, .28);
}
.rfp-topbar-icon {
    width: 34px; height: 34px; border-radius: 8px;
    background: rgba(255,255,255,.18);
    display: flex; align-items: center; justify-content: center;
}
.rfp-topbar-title { font-size: .95rem; font-weight: 700; }
.rfp-grid-wrap {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 10px 10px 4px;
    box-shadow: 0 2px 8px rgba(0,0,0,.06);
}
#tabla-facturas_wrapper > .row:first-child {
    justify-content: center;
    align-items: center;
}
#tabla-facturas_wrapper .dataTables_length {
    display: flex;
    align-items: center;
    gap: 14px;
    margin: 0;
}
#rfp-btn-confirmar,
#rfp-btn-borrar { white-space: nowrap; }
#tabla-facturas thead th {
    background: #1e293b;
    color: #fff;
    font-size: .7rem;
    font-weight: 700;
    text-transform: uppercase;
    white-space: nowrap;
    vertical-align: middle;
}
.rfp-inp { font-size: .75rem; padding: 2px 6px; height: 28px; }
.rfp-btn-del { background: transparent; border: none; color: #dc2626; cursor: pointer; }
.rfp-btn-del:hover { color: #991b1b; }
.rfp-inp.rfp-error { border: 1px solid red !important; }
#rfp-ocr-pre {
    height: 300px;
    overflow-y: scroll;
    white-space: pre-wrap;
    word-break: break-word;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    padding: 10px;
    font-size: .78rem;
    margin: 0;
}
#resultadosPrestador tr,
#resultadosObraSoc tr { cursor: pointer; }
#resultadosPrestador tr:hover,
#resultadosObraSoc tr:hover,
#resultadosPrestador tr.rfp-res-activa,
#resultadosObraSoc tr.rfp-res-activa { background: #bbdefb; }
.rfp-modal-tabla { font-size: .78rem; margin-bottom: 0; }
.rfp-modal-tabla thead th {
    background: #1e293b;
    color: #fff;
    font-size: .7rem;
    font-weight: 700;
    text-transform: uppercase;
}
</style>

<div class="modal fade" id="modalTextoOcr" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header" style="background:#0d47a1;color:#fff;">
                <h5 class="modal-title" style="font-size:.95rem;">Texto extraído por OCR</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <pre id="rfp-ocr-pre"></pre>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalBusquedaPrestador" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header" style="background:#0d47a1;color:#fff;">
                <h5 class="modal-title" style="font-size:.95rem;">Buscar prestador</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex gap-2 mb-2">
                    <input type="text" id="rfp-q-prestador" class="form-control form-control-sm"
                           placeholder="Nombre o nombre de fantasía">
                    <button type="button" id="rfp-btn-q-prestador" class="btn btn-sm btn-primary">Buscar</button>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover rfp-modal-tabla">
                        <thead>
                        <tr>
                            <th style="width:90px;">Código</th>
                            <th>Nombre</th>
                            <th>Nom. Fantasía</th>
                        </tr>
                        </thead>
                        <tbody id="resultadosPrestador"></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer d-flex justify-content-between align-items-center">
                <label class="mb-0" style="font-size:.78rem;cursor:pointer;">
                    <input type="checkbox" id="rfp-ver-adef">
                    Ver/ocultar categoría ADEF
                </label>
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalBusquedaObraSoc" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header" style="background:#0d47a1;color:#fff;">
                <h5 class="modal-title" style="font-size:.95rem;">Buscar obra social</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex gap-2 mb-2">
                    <input type="text" id="rfp-q-obrasoc" class="form-control form-control-sm"
                           placeholder="Código (tacodigo) o nombre">
                    <button type="button" id="rfp-btn-q-obrasoc" class="btn btn-sm btn-primary">Buscar</button>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover rfp-modal-tabla">
                        <thead>
                        <tr>
                            <th style="width:90px;">Código</th>
                            <th>Descripción</th>
                        </tr>
                        </thead>
                        <tbody id="resultadosObraSoc"></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<?php require_once dirname(__FILE__) . '/views/layouts/footer.php'; ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/dropzone.min.js"></script>
<script>
(function () {
    'use strict';
    if (typeof jQuery === 'undefined' || typeof jQuery.fn.DataTable === 'undefined') return;

    var CSRF = <?= json_encode($csrf) ?>;
    var $jq = jQuery;

    var dt = $jq('#tabla-facturas').DataTable({
        language: {
            decimal: ',',
            thousands: '.',
            emptyTable: 'No hay facturas pendientes. Arrastrá los PDF arriba para cargarlos.',
            info: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
            infoEmpty: 'Mostrando 0 a 0 de 0 registros',
            infoFiltered: '(filtrado de _MAX_ registros)',
            lengthMenu: 'Mostrar _MENU_ registros',
            loadingRecords: 'Cargando…',
            processing: 'Procesando…',
            search: 'Buscar:',
            zeroRecords: 'No se encontraron coincidencias',
            paginate: {
                first: 'Primero',
                last: 'Último',
                next: 'Siguiente',
                previous: 'Anterior'
            }
        },
        paging: true,
        pagingType: 'full_numbers',
        pageLength: 25,
        lengthMenu: [10, 25, 50, 100],
        ordering: true,
        order: [[1, 'asc']],
        columnDefs: [
            { orderable: false, targets: [0, 8, 9] }
        ]
    });

    var $len = $jq('#tabla-facturas_wrapper .dataTables_length');
    if ($len.length) {
        $len.append($jq('#rfp-btn-confirmar'));
        $len.append($jq('#rfp-btn-borrar'));
        $len.parent().addClass('d-flex justify-content-center align-items-center');
    }

    var rfpTarget = { id: null, tipo: '' };

    function rfpAbrirModal(id) {
        var el = document.getElementById(id);
        if (!el) {
            return;
        }
        try {
            if (window.bootstrap && bootstrap.Modal) {
                bootstrap.Modal.getOrCreateInstance(el).show();
                return;
            }
        } catch (e1) {}
        if ($jq.fn.modal) {
            $jq(el).modal('show');
        }
    }

    function rfpCerrarModal(id) {
        var el = document.getElementById(id);
        if (!el) {
            return;
        }
        try {
            if (window.bootstrap && bootstrap.Modal) {
                var inst = bootstrap.Modal.getInstance(el);
                if (inst) {
                    inst.hide();
                }
                return;
            }
        } catch (e2) {}
        if ($jq.fn.modal) {
            $jq(el).modal('hide');
        }
    }

    function rfpFilaPorId(id) {
        return $jq(dt.rows().nodes()).filter(function () {
            return String($jq(this).attr('data-id')) === String(id);
        });
    }

    function rfpAjaxBusqueda(tipo, valor, accion, okFn) {
        $jq.ajax({
            url: 'busqueda_dinamica.php',
            type: 'POST',
            dataType: 'json',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            data: {
                csrf_token: CSRF,
                tipo_busqueda: tipo,
                tipo: tipo,
                valor: valor,
                accion: accion
            },
            success: function (res) {
                if (typeof okFn === 'function') {
                    okFn(res || {});
                }
            },
            error: function (xhr) {
                var res = xhr.responseJSON;
                if (res && typeof okFn === 'function') {
                    okFn(res);
                    return;
                }
                alert((res && res.error) ? res.error : 'Error de comunicación en la búsqueda.');
            }
        });
    }

    var rfpPrestRows = [];

    function rfpEsAdef(categ) {
        return String(categ || '').toUpperCase().replace(/\s+/g, '') === 'ADEF';
    }

    function rfpRenderPrestadores(rows) {
        if (rows) {
            rfpPrestRows = rows;
        }
        var verAdef = $jq('#rfp-ver-adef').is(':checked');
        var $tb = $jq('#resultadosPrestador');
        $tb.empty();
        var visibles = [];
        $jq.each(rfpPrestRows || [], function (_, r) {
            if (!verAdef && rfpEsAdef(r.categ)) {
                return;
            }
            visibles.push(r);
        });
        if (!visibles.length) {
            $tb.append('<tr><td colspan="3" class="text-muted">Sin resultados.</td></tr>');
            return;
        }
        $jq.each(visibles, function (_, r) {
            var cod = r.codigo || '';
            var nom = r.nombre || '';
            var fan = r.nomfantas || '';
            $tb.append(
                '<tr class="rfp-res-prestador" data-codigo="' + $jq('<div>').text(cod).html() + '">'
                + '<td>' + $jq('<div>').text(cod).html() + '</td>'
                + '<td>' + $jq('<div>').text(nom).html() + '</td>'
                + '<td>' + $jq('<div>').text(fan).html() + '</td>'
                + '</tr>'
            );
        });
        rfpMarcarFila($tb, $tb.find('tr.rfp-res-prestador').first());
    }

    function rfpRenderObrasoc(rows) {
        var $tb = $jq('#resultadosObraSoc');
        $tb.empty();
        if (!rows || !rows.length) {
            $tb.append('<tr><td colspan="2" class="text-muted">Sin resultados.</td></tr>');
            return;
        }
        $jq.each(rows, function (_, r) {
            var cod = r.tacodigo || '';
            var nom = r.tadescrip || '';
            $tb.append(
                '<tr class="rfp-res-obrasoc" data-codigo="' + $jq('<div>').text(cod).html() + '">'
                + '<td>' + $jq('<div>').text(cod).html() + '</td>'
                + '<td>' + $jq('<div>').text(nom).html() + '</td>'
                + '</tr>'
            );
        });
        rfpMarcarFila($tb, $tb.find('tr.rfp-res-obrasoc').first());
    }

    function rfpFilasNav(tbodySel) {
        return $jq(tbodySel).find('tr.rfp-res-prestador, tr.rfp-res-obrasoc');
    }

    function rfpMarcarFila($tb, $row) {
        $tb.find('tr').removeClass('rfp-res-activa');
        if ($row && $row.length) {
            $row.addClass('rfp-res-activa');
            if ($row[0] && $row[0].scrollIntoView) {
                $row[0].scrollIntoView({ block: 'nearest' });
            }
        }
    }

    function rfpMoverFila(tbodySel, dir) {
        var $rows = rfpFilasNav(tbodySel);
        if (!$rows.length) {
            return;
        }
        var idx = $rows.index($rows.filter('.rfp-res-activa'));
        if (idx < 0) {
            idx = 0;
        } else {
            idx += dir;
        }
        if (idx < 0) {
            idx = 0;
        }
        if (idx >= $rows.length) {
            idx = $rows.length - 1;
        }
        rfpMarcarFila($jq(tbodySel), $rows.eq(idx));
    }

    function rfpElegirFilaActiva(tbodySel) {
        var $row = $jq(tbodySel).find('tr.rfp-res-activa').first();
        if (!$row.length) {
            $row = rfpFilasNav(tbodySel).first();
        }
        if ($row.length) {
            $row.trigger('click');
            return true;
        }
        return false;
    }

    function rfpTeclasLista(e, tbodySel, btnBuscar) {
        var key = e.which || e.keyCode;
        if (key === 40) {
            e.preventDefault();
            e.stopPropagation();
            if (e.stopImmediatePropagation) {
                e.stopImmediatePropagation();
            }
            rfpMoverFila(tbodySel, 1);
            return true;
        }
        if (key === 38) {
            e.preventDefault();
            e.stopPropagation();
            if (e.stopImmediatePropagation) {
                e.stopImmediatePropagation();
            }
            rfpMoverFila(tbodySel, -1);
            return true;
        }
        if (key === 13) {
            e.preventDefault();
            e.stopPropagation();
            if (e.stopImmediatePropagation) {
                e.stopImmediatePropagation();
            }
            if (rfpElegirFilaActiva(tbodySel)) {
                return true;
            }
            if (btnBuscar) {
                $jq(btnBuscar).click();
            }
            return true;
        }
        return false;
    }

    function rfpModalListaAbierta() {
        if ($jq('#modalBusquedaObraSoc').hasClass('show')) {
            return {
                tbody: '#resultadosObraSoc',
                btn: '#rfp-btn-q-obrasoc',
                input: '#rfp-q-obrasoc'
            };
        }
        if ($jq('#modalBusquedaPrestador').hasClass('show')) {
            return {
                tbody: '#resultadosPrestador',
                btn: '#rfp-btn-q-prestador',
                input: '#rfp-q-prestador'
            };
        }
        return null;
    }

    document.addEventListener('keydown', function (e) {
        var ctx = rfpModalListaAbierta();
        if (!ctx) {
            return;
        }
        var key = e.which || e.keyCode;
        if (key !== 38 && key !== 40 && key !== 13) {
            return;
        }
        rfpTeclasLista(e, ctx.tbody, ctx.btn);
    }, true);

    function rfpBuscarPrestadorModal(q) {
        $jq('#rfp-q-prestador').val(q || '');
        rfpAjaxBusqueda('prestador', q || '', 'buscar', function (res) {
            rfpRenderPrestadores(res.resultados || res.data || []);
            rfpAbrirModal('modalBusquedaPrestador');
            setTimeout(function () { $jq('#rfp-q-prestador').focus().select(); }, 250);
        });
    }

    function rfpBuscarObraSocModal(q) {
        $jq('#rfp-q-obrasoc').val(q || '');
        rfpAjaxBusqueda('obrasoc', q || '', 'buscar', function (res) {
            rfpRenderObrasoc(res.resultados || []);
            rfpAbrirModal('modalBusquedaObraSoc');
            setTimeout(function () { $jq('#rfp-q-obrasoc').focus().select(); }, 250);
        });
    }

    $jq(document).on('input', '.edit-codprest, .edit-obrasoc', function () {
        $jq(this).val($jq(this).val().toUpperCase());
    });

    $jq(document).on('keydown', '#tabla-facturas .edit-codprest', function (e) {
        if (e.which !== 13) {
            return;
        }
        e.preventDefault();
        e.stopImmediatePropagation();

        var $inp = $jq(this);
        var $tr  = $inp.closest('tr');
        var id   = $tr.attr('data-id');
        var val  = $jq.trim($inp.val());
        rfpTarget.id = id;
        rfpTarget.tipo = 'prestador';

        if (val !== '') {
            rfpAjaxBusqueda('prestador', val, 'validar', function (res) {
                if (res.ok && res.existe) {
                    if (res.codigo) {
                        $inp.val(String(res.codigo).toUpperCase());
                    }
                    $tr.find('.inp-nro').focus().select();
                    return;
                }
                rfpBuscarPrestadorModal(val);
            });
            return;
        }

        rfpBuscarPrestadorModal($tr.attr('data-prestador-ocr') || '');
    });

    $jq(document).on('keydown', '#tabla-facturas .edit-obrasoc', function (e) {
        if (e.which !== 13) {
            return;
        }
        e.preventDefault();
        e.stopImmediatePropagation();

        var $inp = $jq(this);
        var $tr  = $inp.closest('tr');
        var id   = $tr.attr('data-id');
        var val  = $jq.trim($inp.val());
        rfpTarget.id = id;
        rfpTarget.tipo = 'obrasoc';

        if (val !== '') {
            rfpAjaxBusqueda('obrasoc', val, 'validar', function (res) {
                if (res.ok && res.existe) {
                    if (res.tacodigo) {
                        $inp.val(res.tacodigo);
                    }
                    $tr.find('.edit-periodo').focus().select();
                    return;
                }
                rfpBuscarObraSocModal(val);
            });
            return;
        }

        rfpBuscarObraSocModal('');
    });

    $jq(document).on('keydown', '#tabla-facturas input[type="text"]', function (e) {
        if (e.which !== 13) {
            return;
        }
        if ($jq(this).hasClass('edit-codprest') || $jq(this).hasClass('edit-obrasoc')) {
            return;
        }
        e.preventDefault();
        e.stopPropagation();
        var $inputs = $jq(this).closest('tr').find('input[type="text"]:visible');
        var idx = $inputs.index(this);
        if (idx > -1 && idx < $inputs.length - 1) {
            $inputs.eq(idx + 1).focus().select();
        }
    });

    $jq('#rfp-ver-adef').on('change', function () {
        rfpRenderPrestadores();
    });

    $jq('#rfp-btn-q-prestador').on('click', function () {
        rfpAjaxBusqueda('prestador', $jq.trim($jq('#rfp-q-prestador').val()), 'buscar', function (res) {
            rfpRenderPrestadores(res.resultados || res.data || []);
        });
    });
    $jq('#rfp-q-prestador').on('keydown', function (e) {
        rfpTeclasLista(e, '#resultadosPrestador', '#rfp-btn-q-prestador');
    });
    $jq('#resultadosPrestador').on('mouseenter', 'tr.rfp-res-prestador', function () {
        rfpMarcarFila($jq('#resultadosPrestador'), $jq(this));
    });

    $jq('#rfp-btn-q-obrasoc').on('click', function () {
        rfpAjaxBusqueda('obrasoc', $jq.trim($jq('#rfp-q-obrasoc').val()), 'buscar', function (res) {
            rfpRenderObrasoc(res.resultados || []);
        });
    });
    $jq('#rfp-q-obrasoc').on('keydown', function (e) {
        rfpTeclasLista(e, '#resultadosObraSoc', '#rfp-btn-q-obrasoc');
    });
    $jq('#resultadosObraSoc').on('mouseenter', 'tr.rfp-res-obrasoc', function () {
        rfpMarcarFila($jq('#resultadosObraSoc'), $jq(this));
    });

    $jq('#resultadosPrestador').on('click', 'tr.rfp-res-prestador', function () {
        var codigo = $jq(this).attr('data-codigo') || '';
        var $tr = rfpFilaPorId(rfpTarget.id);
        if (!$tr.length) {
            return;
        }
        $tr.find('.edit-codprest').val(codigo);
        rfpCerrarModal('modalBusquedaPrestador');
        $tr.find('.inp-nro').focus().select();
    });

    $jq('#resultadosObraSoc').on('click', 'tr.rfp-res-obrasoc', function () {
        var codigo = $jq(this).attr('data-codigo') || '';
        var $tr = rfpFilaPorId(rfpTarget.id);
        if (!$tr.length) {
            return;
        }
        $tr.find('.edit-obrasoc').val(codigo);
        rfpCerrarModal('modalBusquedaObraSoc');
        $tr.find('.edit-periodo').focus().select();
    });

    $jq(document).on('click', '#tabla-facturas .rfp-btn-ocr', function () {
        var txt = $jq(this).attr('data-texto') || '';
        $jq('#rfp-ocr-pre').text(txt);
        if ($jq.fn.modal) {
            $jq('#modalTextoOcr').modal('show');
        } else if (window.bootstrap && bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalTextoOcr')).show();
        }
    });

    $jq('#rfp-check-all').on('change', function () {
        $jq(dt.rows({ search: 'applied' }).nodes()).find('.rfp-check').prop('checked', this.checked);
    });

    $jq('#rfp-btn-borrar').on('click', function () {
        var ids = [];
        $jq(dt.rows().nodes()).find('.rfp-check:checked').each(function () {
            var id = parseInt(this.value, 10);
            if (id > 0) {
                ids.push(id);
            }
        });
        if (!ids.length) {
            alert('Seleccioná al menos una factura para borrar.');
            return;
        }
        if (!confirm('¿Eliminar las ' + ids.length + ' factura(s) seleccionada(s) de la previsualización?')) {
            return;
        }
        var fd = new FormData();
        fd.append('csrf_token', CSRF);
        fd.append('accion', 'eliminar');
        $jq.each(ids, function (i, id) {
            fd.append('ids[' + i + ']', id);
        });
        fetch('acciones_masivas.php', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: fd
        })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (!res.ok) {
                    alert(res.error || 'No se pudo eliminar.');
                    return;
                }
                window.location.reload();
            })
            .catch(function () { alert('Error de comunicación al eliminar.'); });
    });

    $jq('#rfp-btn-confirmar').on('click', function () {
        var filasOk = [];
        var faltan  = false;

        $jq(dt.rows().nodes()).find('.rfp-check:checked').each(function () {
            var $tr  = $jq(this).closest('tr');
            var $cod = $tr.find('.inp-cod');
            var $nro = $tr.find('.inp-nro');
            var $os  = $tr.find('.inp-os');
            var $per = $tr.find('.inp-per');

            $cod.add($nro).add($os).add($per).removeClass('rfp-error').css('border', '');

            var vacios = false;
            $jq.each([$cod, $nro, $os, $per], function (_, $el) {
                if ($jq.trim($el.val()) === '') {
                    $el.addClass('rfp-error').css('border', '1px solid red');
                    vacios = true;
                }
            });

            if (vacios) {
                this.checked = false;
                faltan = true;
                return;
            }

            filasOk.push({
                id: this.value,
                COD_PREST: $jq.trim($cod.val()),
                NRO_FACTURA: $jq.trim($nro.val()),
                O_SOCIAL: $jq.trim($os.val()),
                PERIODO: $jq.trim($per.val())
            });
        });

        if (faltan) {
            alert('Faltan datos obligatorios en filas seleccionadas.');
        }
        if (!filasOk.length) {
            if (!faltan) {
                alert('Seleccioná al menos una fila completa para confirmar.');
            }
            return;
        }

        var fd = new FormData();
        fd.append('csrf_token', CSRF);
        fd.append('accion', 'confirmar');
        $jq.each(filasOk, function (i, f) {
            fd.append('filas[' + i + '][id]', f.id);
            fd.append('filas[' + i + '][COD_PREST]', f.COD_PREST);
            fd.append('filas[' + i + '][NRO_FACTURA]', f.NRO_FACTURA);
            fd.append('filas[' + i + '][O_SOCIAL]', f.O_SOCIAL);
            fd.append('filas[' + i + '][PERIODO]', f.PERIODO);
        });

        fetch('acciones_masivas.php', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: fd
        })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (!res.ok) {
                    alert(res.error || 'No se pudo confirmar.');
                    return;
                }
                alert(res.msg || ('Se confirmaron ' + res.confirmadas + ' factura(s).'));
                window.location.reload();
            })
            .catch(function () {
                alert('Error de comunicación al confirmar.');
            });
    });

    $jq(document).on('click', '#tabla-facturas .rfp-btn-del', function () {
        var id = this.getAttribute('data-id');
        if (!id || !confirm('¿Eliminar esta factura de la previsualización?')) return;
        var fd = new FormData();
        fd.append('csrf_token', CSRF);
        fd.append('accion', 'eliminar');
        fd.append('id', id);
        fetch('acciones_masivas.php', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: fd
        })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (!res.ok) {
                    alert(res.error || 'No se pudo eliminar.');
                    return;
                }
                window.location.reload();
            })
            .catch(function () { alert('Error de comunicación al eliminar.'); });
    });
})();
</script>
<script>
if (typeof Dropzone !== 'undefined') {
    Dropzone.autoDiscover = false;
}

$(document).ready(function () {
    if (typeof Dropzone === 'undefined' || !document.getElementById('miDropzone')) {
        return;
    }
    if (document.getElementById('miDropzone').dropzone) {
        return;
    }

    var miDropzone = new Dropzone('#miDropzone', {
        url: 'procesar_pdf.php',
        paramName: 'file',
        parallelUploads: 1,
        uploadMultiple: false,
        acceptedFiles: '.pdf',
        maxFilesize: 10,
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        dictDefaultMessage: 'Arrastra los archivos aquí'
    });

    function rfpMostrarMsg(tipo, txt) {
        var $m = $('#rfp-upload-msg');
        $m.removeClass('d-none alert-danger alert-success alert-warning')
            .addClass('alert-' + tipo)
            .text(txt);
    }

    function rfpParseRes(response) {
        if (response && typeof response === 'object' && !response.nodeType) {
            return response;
        }
        var s = String(response || '');
        try {
            return JSON.parse(s);
        } catch (e1) {
            var i = s.lastIndexOf('{');
            if (i >= 0) {
                try {
                    return JSON.parse(s.substring(i));
                } catch (e2) {}
            }
        }
        return null;
    }

    function rfpMarcarError(file, msg) {
        if (file && file.previewElement) {
            $(file.previewElement).removeClass('dz-success').addClass('dz-error');
            var $err = $(file.previewElement).find('.dz-error-message');
            if ($err.length) {
                $err.text(msg);
            }
        }
        rfpMostrarMsg('danger', msg);
    }

    function rfpInyectarFila(res) {
        $.get('obtener_fila_factura.php?id=' + res.id)
            .done(function (htmlFila) {
                var table = $('#tabla-facturas').DataTable();
                var $wrap = $('<table><tbody></tbody></table>');
                $wrap.find('tbody').append(htmlFila);
                var trNode = $wrap.find('tr')[0];
                if (!trNode) {
                    rfpMostrarMsg('warning', 'Guardado. Recargá la página para ver la fila.');
                    return;
                }
                if (res.status === 'updated') {
                    var $filaExistente = $('#tabla-facturas').find('tr[data-id="' + res.id + '"]');
                    if (!$filaExistente.length) {
                        $filaExistente = $('#tabla-facturas').find('input.rfp-check[value="' + res.id + '"]').closest('tr');
                    }
                    if ($filaExistente.length > 0) {
                        table.row($filaExistente).remove();
                    }
                }
                try {
                    table.row.add(trNode).draw(false);
                    rfpMostrarMsg('success', res.message || 'Procesado correctamente');
                } catch (eAdd) {
                    rfpMostrarMsg('warning', 'Guardado. Recargá la página para ver la fila.');
                }
            })
            .fail(function () {
                rfpMostrarMsg('warning', 'Guardado. Recargá la página para ver la fila.');
            });
    }

    miDropzone.on('sending', function (file, xhr, formData) {
        formData.append('csrf_token', <?= json_encode($csrf) ?>);
        $('#rfp-upload-msg').addClass('d-none').text('');
    });

    miDropzone.on('success', function (file, response) {
        var res = rfpParseRes(response);
        if (!res || res.status === 'error') {
            var msg = (res && res.message) ? res.message : 'No se pudo procesar el PDF';
            rfpMarcarError(file, msg);
            return;
        }
        if ((res.status === 'success' || res.status === 'updated') && res.id) {
            $(file.previewElement).find('.dz-success-mark').css('opacity', '1');
            rfpInyectarFila(res);
            return;
        }
        rfpMarcarError(file, (res && res.message) ? res.message : 'Respuesta inesperada del servidor');
    });

    miDropzone.on('error', function (file, message) {
        var msg = (typeof message === 'string') ? message : ((message && message.message) ? message.message : 'Error de subida');
        rfpMarcarError(file, msg);
    });
});
</script>
