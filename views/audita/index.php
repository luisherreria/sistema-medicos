<?php
/**
 * views/audita/index.php
 * Form VFP AUDITA — Auditoria de facturacion.
 * Grid1 RecordSource audifac2, 34 columnas (extract AUDITA.SCX).
 */

$pageTitle  = isset($pageTitle) ? $pageTitle : 'COMEDICA — Auditoria de facturacion';
$breadcrumb = isset($breadcrumb) ? $breadcrumb : array(
    array('label' => 'Audit. Facturac.'),
    array('label' => 'Auditar Facturación'),
);
$csrfSess  = isset($_SESSION['csrf_token']) ? $_SESSION['csrf_token'] : '';
$csrfToken = isset($csrfToken) ? (string) $csrfToken : (string) $csrfSess;
$init      = isset($init) ? $init : array();
$titulo    = isset($titulo) ? $titulo : 'Auditoria de facturacion';
$columnas  = isset($columnas) ? $columnas : array();
$botones   = isset($botones) ? $botones : array();

if (!function_exists('audita_h')) {
    function audita_h($s)
    {
        return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
    }
}
if (!function_exists('audita_caption')) {
    function audita_caption($s)
    {
        return str_replace('\\', '', (string) $s);
    }
}

$obrasoc    = isset($init['obrasoc']) ? $init['obrasoc'] : '';
$suc        = isset($init['suc']) ? $init['suc'] : '';
$factura    = isset($init['factura']) ? $init['factura'] : '';
$prestador  = isset($init['prestador']) ? $init['prestador'] : '';
$periodo    = isset($init['periodo']) ? $init['periodo'] : '';
$nombre     = isset($init['nombre']) ? $init['nombre'] : '';
$fueamed    = isset($init['fueamed']) ? $init['fueamed'] : '';
$tcodaud    = isset($init['tcodaud']) ? $init['tcodaud'] : '';
$tapagada   = isset($init['tapagada']) ? $init['tapagada'] : '';
$tyacerrada = isset($init['tyacerrada']) ? $init['tyacerrada'] : '';

$showCerrada = ($tyacerrada === '1' || strtoupper($tyacerrada) === 'T' || strtoupper($tyacerrada) === '.T.');
$showPagada  = ($tapagada === '1' || strtoupper($tapagada) === 'T' || strtoupper($tapagada) === '.T.');

require_once __DIR__ . '/../../models/Permission.php';
require_once __DIR__ . '/../../views/layouts/header.php';
?>

<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
    <div>
        <h5 class="mb-1 fw-bold text-primary">
            <i class="fa-solid fa-magnifying-glass-dollar me-2"></i><?= audita_h($titulo) ?>
        </h5>
        <p class="text-muted mb-0" style="font-size:0.82rem;">
            Audit. Facturac. &rsaquo; Auditar Facturación &mdash; form AUDITA / audifac2
        </p>
    </div>
    <div class="d-flex gap-2 align-items-center flex-wrap">
        <?php if ($showCerrada) { ?>
            <span class="badge bg-danger px-3 py-2">CERRADA</span>
        <?php } ?>
        <?php if ($showPagada) { ?>
            <span class="badge bg-success px-3 py-2">PAGADA</span>
        <?php } ?>
        <span id="af-total-badge" class="badge bg-secondary bg-opacity-75 px-3 py-2" style="font-size:0.8rem;">
            <i class="fa-solid fa-list-ol me-1"></i>Cargando…
        </span>
    </div>
</div>

<input type="hidden" id="af-csrf" value="<?= audita_h($csrfToken) ?>">
<input type="hidden" id="tobra" value="<?= audita_h($obrasoc) ?>">
<input type="hidden" id="tsuc" value="<?= audita_h($suc) ?>">
<input type="hidden" id="tfac" value="<?= audita_h($factura) ?>">
<input type="hidden" id="tpres" value="<?= audita_h($prestador) ?>">
<input type="hidden" id="tperi" value="<?= audita_h($periodo) ?>">
<input type="hidden" id="tnompres" value="<?= audita_h($nombre) ?>">
<input type="hidden" id="fueamed" value="<?= audita_h($fueamed) ?>">
<input type="hidden" id="tcodaud" value="<?= audita_h($tcodaud) ?>">
<input type="hidden" id="tpagada" value="<?= audita_h($tapagada) ?>">
<input type="hidden" id="tyacerrada" value="<?= audita_h($tyacerrada) ?>">
<input type="hidden" id="tdeb" value="NO">

<div class="card border-0 shadow-sm mb-2" style="border-radius:10px;">
    <div class="card-body py-2 px-3">
        <form id="form-init" class="row g-2 align-items-end" method="get" action="index.php">
            <input type="hidden" name="route" value="audit-facturacion">
            <div class="col-md-2">
                <label class="form-label mb-0 small">obrasoc</label>
                <input type="text" name="obrasoc" class="form-control form-control-sm" value="<?= audita_h($obrasoc) ?>">
            </div>
            <div class="col-md-1">
                <label class="form-label mb-0 small">suc</label>
                <input type="text" name="suc" class="form-control form-control-sm" value="<?= audita_h($suc) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label mb-0 small">factura</label>
                <input type="text" name="factura" class="form-control form-control-sm" value="<?= audita_h($factura) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label mb-0 small">prestador</label>
                <input type="text" name="prestador" class="form-control form-control-sm" value="<?= audita_h($prestador) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label mb-0 small">periodo</label>
                <input type="text" name="periodo" class="form-control form-control-sm" value="<?= audita_h($periodo) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label mb-0 small">TITULO</label>
                <input type="text" name="TITULO" class="form-control form-control-sm" value="<?= audita_h($titulo) ?>">
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-primary btn-sm w-100">Cargar</button>
            </div>
        </form>
        <div class="d-flex flex-wrap gap-3 align-items-center mt-2">
            <label class="small mb-0">
                <input type="checkbox" id="Check2" value="1"> Ocultar Columnas
            </label>
            <label class="small mb-0">
                <input type="checkbox" id="Check3" value="1"> Marcar Todo
            </label>
            <label class="small mb-0">
                <input type="checkbox" id="Check1" value="1"> Marcar Todo con A
            </label>
            <label class="small mb-0">Tamaño Letra
                <input type="number" id="Spinner1" class="form-control form-control-sm d-inline-block" style="width:70px;" value="9" min="6" max="20">
            </label>
            <?php if ($tcodaud !== '') { ?>
                <span class="small text-muted">Cod. Aud. <?= audita_h($tcodaud) ?></span>
            <?php } ?>
        </div>
    </div>
</div>

<div id="af-alert" class="alert d-none mb-2 py-2" role="alert"></div>
<div id="af-motivo" class="small text-muted mb-1"></div>

<style>
.af-grid-scroll {
    max-height: min(62vh, calc(100vh - 280px));
    overflow: auto;
}
#tbl-audita {
    border-collapse: separate;
    border-spacing: 0;
}
#tbl-audita thead th {
    position: sticky;
    top: 0;
    z-index: 4;
    background: #2b579a;
    color: #fff;
    font-weight: 600;
    white-space: nowrap;
    vertical-align: middle;
    box-shadow: 0 1px 0 #1e3f73;
}
</style>

<div class="card card-module mb-2">
    <div class="card-header py-2">
        <span style="font-size:0.88rem;">
            <i class="fa-solid fa-table me-2"></i>Grid1 — audifac2
        </span>
    </div>
    <div class="card-body p-2">
        <div class="table-responsive af-grid-scroll">
            <table id="tbl-audita" class="table table-hover table-bordered table-sm mb-0 nowrap" style="font-size:8pt;">
                <thead>
                    <tr>
                        <?php
                        $ci = 0;
                        $cn = count($columnas);
                        for ($ci = 0; $ci < $cn; $ci++) {
                            $col = $columnas[$ci];
                            $cls = !empty($col['hide']) ? ' d-none af-col-hidden' : '';
                            $chk2 = !empty($col['check2']) ? ' af-check2' : '';
                            $sort = isset($col['sort']) ? $col['sort'] : '';
                            echo '<th class="' . trim($cls . $chk2) . '" data-field="' . audita_h($col['field']) . '" data-sort="' . audita_h($sort) . '" data-colid="' . audita_h($col['id']) . '" title="' . audita_h($col['caption']) . '">';
                            echo audita_h($col['caption']);
                            echo '</th>';
                        }
                        ?>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-2" style="border-radius:10px;">
    <div class="card-body py-2 px-3">
        <div class="row g-2 small">
            <div class="col"><span class="text-muted">Cantidad</span><div id="TEXT1" class="fw-bold">0</div></div>
            <div class="col"><span class="text-muted">Total facturado</span><div id="TEXT2" class="fw-bold">0</div></div>
            <div class="col"><span class="text-muted">Total Importe</span><div id="TEXT3" class="fw-bold">0</div></div>
            <div class="col"><span class="text-muted">Coseguro Preestador</span><div id="TEXT8" class="fw-bold">0</div></div>
            <div class="col"><span class="text-muted">Coseguro OS</span><div id="TEXT4" class="fw-bold">0</div></div>
            <div class="col"><span class="text-muted">Diferencia  Coseguro</span><div id="TEXT7" class="fw-bold">0</div></div>
            <div class="col"><span class="text-muted">Excl Coseguro</span><div id="TEXT9" class="fw-bold">0</div></div>
            <div class="col"><span class="text-muted">Coseguro NO COB con DEB Cpr=0 c/Deb</span><div id="TEXT16" class="fw-bold">0</div></div>
            <div class="col"><span class="text-muted">Neto Fact Prestador</span><div id="TEXT10" class="fw-bold">0</div></div>
            <div class="col d-none"><span class="text-muted">Total neteo Neg cred</span><div id="TEXT11">0</div></div>
        </div>
        <div class="row g-2 mt-2">
            <div class="col-md-6">
                <label class="form-label small mb-0">coleyedeb</label>
                <textarea id="Edit1" class="form-control form-control-sm" rows="2" readonly></textarea>
            </div>
            <div class="col-md-6">
                <label class="form-label small mb-0">coleydeb2</label>
                <textarea id="Edit2" class="form-control form-control-sm" rows="2" readonly></textarea>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-3" style="border-radius:10px;">
    <div class="card-body py-2 px-3">
        <div class="d-flex flex-wrap gap-1">
            <?php
            $bi = 0;
            $bn = count($botones);
            for ($bi = 0; $bi < $bn; $bi++) {
                $b = $botones[$bi];
                $acc = isset($b['accion']) ? $b['accion'] : '';
                $form = isset($b['form']) ? $b['form'] : '';
                $ord  = isset($b['orden']) ? $b['orden'] : '';
                $cls  = 'btn btn-sm btn-outline-secondary';
                if ($acc === 'recalc') {
                    $cls = 'btn btn-sm btn-primary';
                } elseif ($acc === 'salir') {
                    $cls = 'btn btn-sm btn-outline-danger';
                }
                echo '<button type="button" class="' . $cls . ' af-btn"';
                echo ' data-name="' . audita_h($b['name']) . '"';
                echo ' data-accion="' . audita_h($acc) . '"';
                echo ' data-form="' . audita_h($form) . '"';
                echo ' data-orden="' . audita_h($ord) . '">';
                echo audita_h(audita_caption($b['caption']));
                echo '</button>';
            }
            ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/dialogs.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    'use strict';
    var $ = window.jQuery;
    if (!$) return;

    var CSRF = <?= json_encode($csrfToken, JSON_UNESCAPED_UNICODE) ?>;
    var COLS = <?= json_encode($columnas, JSON_UNESCAPED_UNICODE) ?>;
    var ordenActual = '';
    var dirActual = 'ASC';
    var filasCache = [];

    function apiUrl(action) {
        return 'index.php?route=audit-facturacion&action=' + encodeURIComponent(action);
    }

    function initParams() {
        return {
            obrasoc: document.getElementById('tobra').value,
            suc: document.getElementById('tsuc').value,
            factura: document.getElementById('tfac').value,
            prestador: document.getElementById('tpres').value,
            periodo: document.getElementById('tperi').value,
            TITULO: <?= json_encode($titulo, JSON_UNESCAPED_UNICODE) ?>,
            fueamed: document.getElementById('fueamed').value,
            tcodaud: document.getElementById('tcodaud').value,
            nombre: document.getElementById('tnompres').value,
            tapagada: document.getElementById('tpagada').value,
            tyacerrada: document.getElementById('tyacerrada').value,
            orden: ordenActual,
            dir: dirActual
        };
    }

    function showAlert(msg, ok) {
        var el = document.getElementById('af-alert');
        el.className = 'alert mb-2 py-2 ' + (ok ? 'alert-success' : 'alert-danger');
        el.innerHTML = '<i class="fa-solid ' + (ok ? 'fa-circle-check' : 'fa-triangle-exclamation') + ' me-1"></i>'
            + $('<div>').text(msg).html();
        el.classList.remove('d-none');
    }

    function cell(row, field) {
        if (!row || !field) return '';
        if (row[field] !== undefined && row[field] !== null) return row[field];
        var k;
        for (k in row) {
            if (Object.prototype.hasOwnProperty.call(row, k) && k.toLowerCase() === field.toLowerCase()) {
                return row[k];
            }
        }
        return '';
    }

    function fmtNum(v) {
        var n = parseFloat(v);
        if (isNaN(n)) return '0.00';
        return n.toFixed(2);
    }

    function fmtFecha(v) {
        if (v === null || v === undefined) return '';
        var s = String(v).trim();
        if (s === '' || s.indexOf('0000-00-00') === 0) return '';
        var m = s.match(/^(\d{4})-(\d{2})-(\d{2})/);
        if (m) return m[3] + '/' + m[2] + '/' + m[1];
        return s;
    }

    function pintarTotales(t) {
        if (!t) return;
        document.getElementById('TEXT1').textContent = fmtNum(t.text1);
        document.getElementById('TEXT2').textContent = fmtNum(t.text2);
        document.getElementById('TEXT3').textContent = fmtNum(t.text3);
        document.getElementById('TEXT4').textContent = fmtNum(t.text4);
        document.getElementById('TEXT7').textContent = fmtNum(t.text7);
        document.getElementById('TEXT8').textContent = fmtNum(t.text8);
        document.getElementById('TEXT9').textContent = fmtNum(t.text9);
        document.getElementById('TEXT10').textContent = fmtNum(t.text10);
        document.getElementById('TEXT11').textContent = fmtNum(t.text11);
        document.getElementById('TEXT16').textContent = fmtNum(t.text16);
    }

    function truthy(v) {
        return v === true || v === 1 || v === '1' || v === 'T' || v === '.T.' || v === 'true';
    }

    function pintarFilas(filas) {
        filasCache = filas || [];
        var tb = document.querySelector('#tbl-audita tbody');
        tb.innerHTML = '';
        var i, c, row, tr, td, val, col, hideCls, chk2;
        for (i = 0; i < filasCache.length; i++) {
            row = filasCache[i];
            tr = document.createElement('tr');
            tr.setAttribute('data-nroorden', cell(row, 'nroorden'));
            tr.setAttribute('data-idx', String(i));
            for (c = 0; c < COLS.length; c++) {
                col = COLS[c];
                td = document.createElement('td');
                hideCls = col.hide ? ' d-none af-col-hidden' : '';
                chk2 = col.check2 ? ' af-check2' : '';
                td.className = (hideCls + chk2).trim();
                val = cell(row, col.field);
                if (col.check) {
                    td.innerHTML = truthy(val) ? '<i class="fa-solid fa-check"></i>' : '';
                    td.className += ' text-center';
                } else if (col.field === 'cofacturad' || col.field === 'coimporte' || col.field === 'cocoseguro' || col.field === 'cosegpres' || col.field === 'difcpre' || col.field === 'liqneg') {
                    td.textContent = fmtNum(val);
                    td.className += ' text-end';
                } else if (col.field === 'cofeccons' || col.field === 'cofechacar' || /^(\d{4})-(\d{2})-(\d{2})/.test(String(val || ''))) {
                    td.textContent = fmtFecha(val);
                    td.className += ' text-nowrap';
                } else {
                    td.textContent = val === null || val === undefined ? '' : String(val);
                }
                tr.appendChild(td);
            }
            tb.appendChild(tr);
        }
        document.getElementById('af-total-badge').innerHTML =
            '<i class="fa-solid fa-list-ol me-1"></i>' + filasCache.length + ' filas';
        aplicarCheck2();
    }

    function aplicarCheck2() {
        var oculta = document.getElementById('Check2').checked;
        var nodes = document.querySelectorAll('#tbl-audita .af-check2');
        var i;
        for (i = 0; i < nodes.length; i++) {
            if (oculta) {
                nodes[i].classList.add('d-none');
            } else if (!nodes[i].classList.contains('af-col-hidden')) {
                nodes[i].classList.remove('d-none');
            }
        }
        document.querySelector('label[for="Check2"]');
        var lab = document.getElementById('Check2').parentNode;
        lab.lastChild.textContent = oculta ? ' Mostrar Columnas' : ' Ocultar Columnas';
    }

    function cargar() {
        $.ajax({
            url: apiUrl('listado'),
            method: 'GET',
            data: initParams(),
            dataType: 'json',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        }).done(function (res) {
            if (!res || !res.ok) {
                showAlert((res && res.error) || 'No se pudo leer audifac2.', false);
                return;
            }
            if (!res.tabla_ok) {
                var nomTabla = (res.tabla && String(res.tabla) !== '') ? res.tabla : 'audifac2';
                var det = (res.error && String(res.error) !== '') ? (': ' + res.error) : ' (SHOW COLUMNS vacío).';
                showAlert('No se pudo leer ' + nomTabla + det, false);
                pintarFilas([]);
                pintarTotales(res.totales);
                return;
            }
            pintarFilas(res.filas || []);
            pintarTotales(res.totales);
            if (res.truncado) {
                showAlert('Se muestran las primeras 4000 filas de audifac2.', true);
            }
        }).fail(function () {
            showAlert('Error de comunicación al leer audifac2.', false);
        });
    }

    function postAccion(action, extra) {
        var data = initParams();
        data.csrf_token = CSRF;
        var k;
        if (extra) {
            for (k in extra) {
                if (Object.prototype.hasOwnProperty.call(extra, k)) {
                    data[k] = extra[k];
                }
            }
        }
        return $.ajax({
            url: apiUrl(action),
            method: 'POST',
            data: data,
            dataType: 'json',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': CSRF }
        });
    }

    $('#tbl-audita tbody').on('dblclick', 'tr', function () {
        var nro = $(this).attr('data-nroorden');
        if (!nro) return;
        postAccion('toggle_coselecc', { nroorden: nro }).done(function (res) {
            if (res && res.ok) {
                cargar();
            } else {
                showAlert((res && res.error) || 'No se pudo cambiar coselecc.', false);
            }
        });
    });

    $('#tbl-audita tbody').on('click', 'tr', function () {
        var idx = parseInt($(this).attr('data-idx'), 10);
        if (isNaN(idx) || !filasCache[idx]) return;
        document.getElementById('Edit1').value = cell(filasCache[idx], 'coleyedeb') || '';
        document.getElementById('Edit2').value = cell(filasCache[idx], 'coleydeb2') || '';
        document.getElementById('af-motivo').textContent = cell(filasCache[idx], 'amotivo') || '';
        $('#tbl-audita tbody tr').removeClass('table-active');
        $(this).addClass('table-active');
    });

    $('#tbl-audita thead').on('click', 'th', function () {
        var sort = $(this).attr('data-sort');
        if (!sort) return;
        if (ordenActual === sort) {
            dirActual = dirActual === 'ASC' ? 'DESC' : 'ASC';
        } else {
            ordenActual = sort;
            dirActual = 'ASC';
        }
        cargar();
    });

    $('#Check2').on('change', function () {
        aplicarCheck2();
    });

    $('#Check3').on('change', function () {
        var marcar = this.checked ? 1 : 0;
        postAccion('marcar_todo', { marcar: marcar }).done(function (res) {
            if (!res || !res.ok) {
                showAlert((res && res.error) || 'No se pudo marcar sedacion.', false);
                return;
            }
            document.getElementById('Check3').parentNode.lastChild.textContent =
                marcar ? ' Desmarcar Todo' : ' Marcar Todo';
            cargar();
        });
    });

    $('#Check1').on('change', function () {
        var marcar = this.checked ? 1 : 0;
        postAccion('marcar_todo_a', { marcar: marcar }).done(function (res) {
            if (!res || !res.ok) {
                showAlert((res && res.error) || 'No se pudo marcar clasif A.', false);
                return;
            }
            document.getElementById('Check1').parentNode.lastChild.textContent =
                marcar ? ' Desmarcar' : ' Marcar Todo con A';
            cargar();
        });
    });

    $('#Spinner1').on('change', function () {
        var n = parseInt(this.value, 10);
        if (isNaN(n) || n < 6) n = 8;
        document.getElementById('tbl-audita').style.fontSize = n + 'pt';
    });

    $('.af-btn').on('click', function () {
        var acc = $(this).attr('data-accion');
        var form = $(this).attr('data-form');
        var ord = $(this).attr('data-orden');
        if (acc === 'salir') {
            window.location.href = 'index.php?route=dashboard';
            return;
        }
        if (acc === 'recalc') {
            $.ajax({
                url: apiUrl('recalc'),
                method: 'GET',
                data: initParams(),
                dataType: 'json',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            }).done(function (res) {
                if (!res || !res.ok) {
                    showAlert((res && res.error) || 'No se pudo resumar.', false);
                    return;
                }
                pintarTotales(res.totales);
            });
            return;
        }
        if (acc === 'visualizar') {
            $.ajax({
                url: apiUrl('visualizar'),
                method: 'GET',
                data: initParams(),
                dataType: 'json',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            }).done(function (res) {
                if (!res || !res.ok) {
                    showAlert((res && res.error) || 'No se pudo visualizar.', false);
                    return;
                }
                document.getElementById('vr-prestador').textContent = res.prestador || '';
                document.getElementById('vr-titulo').textContent = res.titulo || '';
                document.getElementById('vr-facturado').textContent = 'Total FACTURADO: ' + fmtNum(res.facturado);
                document.getElementById('vr-liquidado').textContent = 'Total LIQUIDADO: ' + fmtNum(res.liquidado);
                document.getElementById('vr-cosegpres').textContent = 'Coseguros Prestador: ' + fmtNum(res.cosegpres);
                document.getElementById('vr-cosegnocob').textContent = 'Coseguro No Cobrados: ' + fmtNum(res.cosegnocob);
                document.getElementById('vr-debitado').textContent = 'Total DEBITADO: ' + fmtNum(res.debitado);
                document.getElementById('vr-debito010').textContent = 'Total DEBITO(010): ' + fmtNum(res.debito010);
                document.getElementById('vr-credito032').textContent = 'Total CREDITO(032): ' + fmtNum(res.credito032);
                document.getElementById('vr-liqneg').textContent = 'Coseguros NO Decontados: ' + fmtNum(res.liqneg);
                document.getElementById('vr-neto').textContent = 'Neto Factura Prestador: ' + fmtNum(res.neto);
                document.getElementById('vr-aviso').textContent = res.aviso || '';
                abrirModal('modal-resumen');
            }).fail(function () {
                showAlert('Error de comunicación al visualizar.', false);
            });
            return;
        }
        if (acc === 'orden') {
            ordenActual = ord || 'nroorden';
            dirActual = 'ASC';
            cargar();
            return;
        }
        if (acc === 'cierre') {
            postAccion('cierre', {}).done(function (res) {
                showAlert((res && res.error) || 'Cierre no migrado.', false);
            });
            return;
        }
        if (acc === 'doform') {
            if (!form) {
                showAlert('Acción VFP sin DO FORM citado en AUDITA.SCX — no migrada.', false);
                return;
            }
            if (form === 'newcons') { abrirNewcons(); return; }
            if (form === 'cambpres') { abrirCambpres(); return; }
            if (form === 'ajusfact') { abrirAjusfact(); return; }
            if (form === 'ajusfafi') { abrirAjusfafi(); return; }
            if (form === 'cambafil') { abrirCambafil(); return; }
            if (form === 'debitar') { abrirDebitar(); return; }
            postAccion('do_form', { form: form }).done(function (res) {
                showAlert((res && res.error) || 'Form no migrado.', false);
            });
        }
    });

    function filaActiva() {
        var idx = parseInt($('#tbl-audita tbody tr.table-active').attr('data-idx'), 10);
        if (isNaN(idx) || !filasCache[idx]) return null;
        return filasCache[idx];
    }

    function abrirModal(id) {
        var el = document.getElementById(id);
        if (window.bootstrap && bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(el).show();
        }
    }
    function cerrarModal(id) {
        var el = document.getElementById(id);
        if (window.bootstrap && bootstrap.Modal) {
            var inst = bootstrap.Modal.getInstance(el);
            if (inst) inst.hide();
        }
    }

    function abrirNewcons() {
        var p = initParams();
        $('#nc-tobra').val(p.obrasoc);
        $('#nc-tpresta').val(p.prestador);
        $('#nc-tsuc').val(p.suc);
        $('#nc-tfac').val(p.factura);
        $('#nc-tperi').val(p.periodo);
        $('#nc-tcant').val(0);
        $('#nc-tfact').val(0);
        $('#nc-timpo').val(0);
        $('#nc-tcose').val(0);
        $('#nc-tcosep').val(0);
        $('#nc-chkexccos').prop('checked', false);
        $('#nc-err').addClass('d-none');
        abrirModal('modal-newcons');
    }

    function abrirCambpres() {
        var row = filaActiva();
        if (!row) {
            showAlert('Seleccione una fila (CAMBPRES).', false);
            return;
        }
        $('#cp-nroorden').val(cell(row, 'nroorden'));
        $('#cp-tpres').val(cell(row, 'cocodprest'));
        $('#cp-prestant').val(cell(row, 'cocodprest'));
        $('#cp-tnpres').val(cell(row, 'conombpres'));
        $('#cp-tcant').val(cell(row, 'cocantpres'));
        $('#cp-tfact').val(cell(row, 'cofacturad'));
        $('#cp-timpo').val(cell(row, 'coimporte'));
        $('#cp-tcose').val(cell(row, 'cocoseguro'));
        $('#cp-tcospres').val(cell(row, 'cosegpres'));
        $('#cp-chkexccos').prop('checked', truthy(cell(row, 'coexccos')));
        $('#cp-todas').prop('checked', false);
        $('#cp-ccanti').prop('checked', false);
        $('#cp-err').addClass('d-none');
        abrirModal('modal-cambpres');
    }

    function abrirAjusfact() {
        var p = initParams();
        $('#aj-tobra').val(p.obrasoc);
        $('#aj-tpresta').val(p.prestador);
        $('#aj-tsuc').val(p.suc);
        $('#aj-tfac').val(p.factura);
        $('#aj-tperi').val(p.periodo);
        $('#aj-tafil').val('1');
        $('#aj-tnomafil').val('Ajuste');
        $('#aj-tcant').val(1);
        $('#aj-tfact').val(0);
        $('#aj-timpo').val(0);
        $('#aj-tcose').val(0);
        $('#aj-tcosepre').val(0);
        $('#aj-err').addClass('d-none');
        abrirModal('modal-ajusfact');
    }

    function abrirAjusfafi() {
        var p = initParams();
        var row = filaActiva();
        $('#af2-tobra').val(p.obrasoc);
        $('#af2-tpresta').val(p.prestador);
        $('#af2-tsuc').val(p.suc);
        $('#af2-tfac').val(p.factura);
        $('#af2-tperi').val(p.periodo);
        if (row) {
            $('#af2-tafil').val(cell(row, 'coafiliado'));
            $('#af2-tnomafil').val(cell(row, 'conompac'));
            $('#af2-tplan').val(cell(row, 'coplan'));
        }
        $('#af2-tcant').val(1);
        $('#af2-tfact').val(0);
        $('#af2-timpo').val(0);
        $('#af2-tcose').val(0);
        $('#af2-tcosepre').val(0);
        $('#af2-err').addClass('d-none');
        abrirModal('modal-ajusfafi');
    }

    function abrirCambafil() {
        var row = filaActiva();
        if (!row) {
            showAlert('Seleccione una fila (CAMBAFIL).', false);
            return;
        }
        $('#ca-tobra').val(cell(row, 'coobrasoc'));
        $('#ca-nobra').val(cell(row, 'coobrasoc'));
        $('#ca-tafil').val(cell(row, 'coafiliado'));
        $('#ca-nafil').val(cell(row, 'coafiliado'));
        $('#ca-tnomafil').val(cell(row, 'conompac'));
        $('#ca-nplan').val(cell(row, 'coplan'));
        $('#ca-tfecha').val(cell(row, 'cofeccons'));
        $('#ca-conumero').val(cell(row, 'conumero'));
        $('#ca-tnomobra').val('');
        $('#ca-err').addClass('d-none');
        abrirModal('modal-cambafil');
    }

    function abrirDebitar() {
        var row = filaActiva();
        if (!row) {
            showAlert('Seleccione una fila (DEBITAR).', false);
            return;
        }
        $('#db-nroorden').val(cell(row, 'nroorden'));
        $('#db-prestador').val(cell(row, 'conomprest'));
        $('#db-prestacion').val(cell(row, 'conombpres'));
        $('#db-problema').val('');
        $('#db-debito').val('');
        $('#db-tleye').val('');
        $('#db-err').addClass('d-none');
        abrirModal('modal-debitar');
    }

    function bindForm(formId, action, errId, modalId) {
        $('#' + formId).on('submit', function (e) {
            e.preventDefault();
            var data = {};
            $(this).serializeArray().forEach(function (x) { data[x.name] = x.value; });
            $('#' + formId + ' input[type=checkbox]').each(function () {
                data[this.name] = this.checked ? 1 : 0;
            });
            postAccion(action, data).done(function (res) {
                if (!res || !res.ok) {
                    $('#' + errId).text((res && res.error) || 'No se pudo guardar.').removeClass('d-none');
                    return;
                }
                cerrarModal(modalId);
                cargar();
            }).fail(function () {
                $('#' + errId).text('Error de comunicación.').removeClass('d-none');
            });
        });
    }
    bindForm('form-newcons', 'newcons', 'nc-err', 'modal-newcons');
    bindForm('form-cambpres', 'cambpres', 'cp-err', 'modal-cambpres');
    bindForm('form-ajusfact', 'ajusfact', 'aj-err', 'modal-ajusfact');
    bindForm('form-ajusfafi', 'ajusfafi', 'af2-err', 'modal-ajusfafi');
    bindForm('form-cambafil', 'cambafil', 'ca-err', 'modal-cambafil');
    bindForm('form-debitar', 'debitar', 'db-err', 'modal-debitar');

    cargar();
});
</script>

<?php require_once __DIR__ . '/../../views/layouts/footer.php'; ?>
