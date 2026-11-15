<?php
/**
 * Form VFP AUDIFACT — menú do audifact with 'CP'.
 * Command1 → SELEFACT. Dialecto PHP 5.6.
 */
if (!function_exists('audita_h')) {
    function audita_h($s)
    {
        return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
    }
}

$init      = isset($init) ? $init : array();
$csrfToken = isset($csrfToken) ? (string) $csrfToken : '';
$periodo   = isset($init['periodo']) && $init['periodo'] !== '' ? $init['periodo'] : 'AA/MM';
$tpresta   = isset($init['prestador']) ? $init['prestador'] : '';
$nombre    = isset($init['nombre']) ? $init['nombre'] : '';
$pageTitle = isset($pageTitle) ? $pageTitle : 'COMEDICA — Auditoria de Facturación';
$breadcrumb = isset($breadcrumb) ? $breadcrumb : array(
    array('label' => 'Audit. Facturac.'),
    array('label' => 'Auditar Facturación'),
);

require_once __DIR__ . '/../../models/Permission.php';
require_once __DIR__ . '/../../views/layouts/header.php';
?>

<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
    <div>
        <h5 class="mb-1 fw-bold text-primary">
            <i class="fa-solid fa-file-invoice-dollar me-2"></i>Auditoria de Facturación
        </h5>
        <p class="text-muted mb-0" style="font-size:0.82rem;">
            Audit. Facturac. &rsaquo; Auditar Facturación &mdash; form AUDIFACT
        </p>
    </div>
</div>

<div id="af-alert" class="alert d-none mb-3 py-2" role="alert"></div>

<div class="card border-0 shadow-sm" style="border-radius:10px; max-width:520px;">
    <div class="card-header py-2 text-white" style="background:#2b579a;">
        <span class="small fw-semibold">Factura Prestador</span>
    </div>
    <div class="card-body">
        <form id="form-audifact" method="get" action="index.php">
            <input type="hidden" name="route" value="audit-facturacion">
            <input type="hidden" name="paso" value="selefact">
            <input type="hidden" name="nombre" id="af-nombre-hid" value="<?= audita_h($nombre) ?>">
            <input type="hidden" name="tconfact" id="af-tconfact-hid" value="">

            <div class="row g-2 mb-2 align-items-center">
                <div class="col-sm-4"><label class="small mb-0" for="af-periodo">Periodo :</label></div>
                <div class="col-sm-4">
                    <input class="form-control form-control-sm" id="af-periodo" name="periodo"
                           maxlength="5" placeholder="AA/MM" value="<?= audita_h($periodo) ?>">
                </div>
            </div>
            <div class="row g-2 mb-2 align-items-center">
                <div class="col-sm-4"><label class="small mb-0" for="af-tpresta">Prestador :</label></div>
                <div class="col-sm-4">
                    <input class="form-control form-control-sm text-uppercase" id="af-tpresta" name="prestador"
                           maxlength="11" value="<?= audita_h($tpresta) ?>">
                </div>
                <div class="col-sm-4">
                    <input class="form-control form-control-sm" id="af-nomprest" value="<?= audita_h($nombre) ?>" disabled>
                </div>
            </div>

            <div id="af-tconfact" class="form-control form-control-sm mb-2 bg-light" style="min-height:36px;"></div>
            <div id="af-tvalereci" class="form-control form-control-sm mb-2 bg-light" style="min-height:36px;"></div>
            <div id="af-conurl" class="form-control form-control-sm mb-3 bg-light" style="min-height:36px;"></div>

            <div class="d-flex flex-wrap gap-2">
                <button type="button" id="af-backup" class="btn btn-sm btn-outline-secondary">Backup</button>
                <button type="submit" id="af-auditar" class="btn btn-sm btn-primary ms-auto">Auditar</button>
                <a href="index.php?route=dashboard" class="btn btn-sm btn-outline-secondary">Salir</a>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    'use strict';
    var $ = window.jQuery;
    if (!$) return;

    function showAlert(msg, ok) {
        var el = $('#af-alert');
        el.removeClass('d-none alert-success alert-danger alert-warning')
            .addClass(ok ? 'alert-success' : 'alert-danger')
            .text(msg);
    }

    function periodoValido(v) {
        v = String(v || '').replace(/\s/g, '');
        if (v.length < 5) return false;
        var a = parseInt(v.substring(0, 2), 10);
        var b = parseInt(v.substring(3, 5), 10);
        return a > 0 && b > 0;
    }

    function aplicarPrestador(res) {
        if (!res || !res.ok) {
            $('#af-nomprest').val('');
            $('#af-nombre-hid').val('');
            $('#af-tconfact').text('');
            $('#af-tvalereci').text('');
            $('#af-tconfact-hid').val('');
            showAlert((res && res.error) || 'Seleccione un Prestador para continuar...', false);
            return false;
        }
        $('#af-tpresta').val(res.tpresta || '');
        $('#af-nomprest').val(res.nombre || '');
        $('#af-nombre-hid').val(res.nombre || '');
        if (res.confact) {
            $('#af-tconfact').text('PRESTADOR CON FACTURA');
            $('#af-tconfact-hid').val('PRESTADOR CON FACTURA');
            showAlert('Verificar que la haya entregado, Gracias', true);
        } else {
            $('#af-tconfact').text('');
            $('#af-tconfact-hid').val('');
            $('#af-alert').addClass('d-none');
        }
        if (res.valereci) {
            $('#af-tvalereci').text('FACTURA VALE COMO RECIBO');
        } else {
            $('#af-tvalereci').text('');
        }
        return true;
    }

    function buscarPrestador(cb) {
        var p = $.trim($('#af-tpresta').val());
        if (p === '') {
            aplicarPrestador({ok: false, error: 'Seleccione un Prestador para continuar...'});
            if (cb) cb(false);
            return;
        }
        $.ajax({
            url: 'index.php?route=audit-facturacion&action=buscar_prestador',
            method: 'GET',
            data: {prestador: p},
            dataType: 'json',
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        }).done(function (res) {
            var ok = aplicarPrestador(res);
            if (cb) cb(ok);
        }).fail(function () {
            showAlert('Error al leer ebamp.', false);
            if (cb) cb(false);
        });
    }

    $('#af-tpresta').on('blur', function () {
        if ($.trim(this.value) !== '') buscarPrestador();
    }).on('keydown', function (e) {
        if (e.which === 13) {
            e.preventDefault();
            buscarPrestador(function (ok) {
                if (ok) $('#form-audifact').trigger('submit');
            });
        }
        if (e.which === 27) {
            window.location.href = 'index.php?route=dashboard';
        }
    });

    $('#form-audifact').on('submit', function (e) {
        if ($.trim($('#af-tpresta').val()) === '') {
            e.preventDefault();
            showAlert('Seleccione un Prestador para continuar...', false);
            $('#af-periodo').focus();
            return false;
        }
        if (!periodoValido($('#af-periodo').val())) {
            e.preventDefault();
            showAlert('Debe seleccionar un Periodo Valido', false);
            $('#af-periodo').focus();
            return false;
        }
        return true;
    });

    $('#af-backup').on('click', function () {
        showAlert('Backup no migrado (copia detconsu/consulta/liquida de 7 días).', false);
    });
});
</script>

<?php require_once __DIR__ . '/../../views/layouts/footer.php'; ?>
