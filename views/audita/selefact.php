<?php
/**
 * Form VFP SELEFACT — Facturas a Auditar prestador.
 * Grid1 RecordSource audifact. Command1 DO FORM AUDITA.
 */
if (!function_exists('audita_h')) {
    function audita_h($s)
    {
        return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
    }
}
if (!function_exists('audita_cell')) {
    function audita_cell($row, $col)
    {
        if (isset($row[$col])) {
            return $row[$col];
        }
        $keys = array_keys($row);
        $i = 0;
        $n = count($keys);
        for ($i = 0; $i < $n; $i++) {
            if (strtolower($keys[$i]) === strtolower($col)) {
                return $row[$keys[$i]];
            }
        }
        return '';
    }
}

$facturas   = isset($facturas) ? $facturas : array();
$tablaOk    = isset($tablaOk) ? (bool) $tablaOk : true;
$errorTabla = isset($errorTabla) ? $errorTabla : '';
$tablaUsada = isset($tablaUsada) && (string) $tablaUsada !== '' ? (string) $tablaUsada : 'audifact';
$tpresta    = isset($tpresta) ? $tpresta : '';
$periodo    = isset($periodo) ? $periodo : '';
$nombre     = isset($nombre) ? $nombre : '';
$tconfact   = isset($tconfact) ? $tconfact : '';
$infoliq    = isset($infoliq) ? $infoliq : '';
$verCierre  = !empty($verCierre);
$pageTitle  = isset($pageTitle) ? $pageTitle : 'COMEDICA — Facturas a Auditar prestador';
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
            <i class="fa-solid fa-list me-2"></i>Facturas a Auditar prestador
        </h5>
        <p class="text-muted mb-0" style="font-size:0.82rem;">
            Audit. Facturac. &rsaquo; form SELEFACT / <?= audita_h($tablaUsada) ?>
            &mdash; <?= audita_h($tpresta) ?> <?= audita_h($nombre) ?>
            <?= $periodo !== '' ? ' &mdash; periodo ' . audita_h($periodo) : '' ?>
        </p>
    </div>
    <a href="index.php?route=audit-facturacion" class="btn btn-sm btn-outline-secondary">Volver a AUDIFACT</a>
</div>

<?php if ($tconfact !== '') { ?>
    <div class="alert alert-info py-2"><?= audita_h($tconfact) ?></div>
<?php } ?>

<?php if (!$tablaOk) { ?>
    <div class="alert alert-danger">
        No se pudo leer <code><?= audita_h($tablaUsada) ?></code><?= $errorTabla !== '' ? ': ' . audita_h($errorTabla) : '.' ?>
    </div>
<?php } elseif (empty($facturas)) { ?>
    <div class="alert alert-info">
        No se encuentran registros para ese periodo, prestador...
    </div>
<?php } else { ?>
<div class="row g-3">
    <div class="col-lg-9">
        <div class="card border-0 shadow-sm" style="border-radius:10px;">
            <div class="card-header py-2 text-white d-flex justify-content-between" style="background:#2b579a;">
                <span class="small fw-semibold">Grid1 — <?= audita_h($tablaUsada) ?> (<?= (int) count($facturas) ?>)</span>
                <span class="small">Con Refacturacion</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0" id="sf-grid">
                        <thead>
                            <tr>
                                <th>Periodo</th>
                                <th>Codigo</th>
                                <th>Prestador</th>
                                <th>Obra Soc.</th>
                                <th>Suc.F.</th>
                                <th>Nº Factura</th>
                                <th>Fecha</th>
                                <th>Neto</th>
                                <th>I.V.A</th>
                                <th>Total</th>
                                <th>Coseguro</th>
                                <th>FecCierre</th>
                                <th>Pagada</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        $i = 0;
                        $n = count($facturas);
                        for ($i = 0; $i < $n; $i++) {
                            $r = $facturas[$i];
                            $os  = (string) audita_cell($r, 'coobrasoc');
                            $suc = (string) audita_cell($r, 'cosucfac');
                            $fac = (string) audita_cell($r, 'conrofac');
                            $pre = (string) audita_cell($r, 'coprestado');
                            $per = (string) audita_cell($r, 'coperiodo');
                            $nom = (string) audita_cell($r, 'conomprest');
                            if ($nom === '') {
                                $nom = $nombre;
                            }
                            $refac = trim((string) audita_cell($r, 'refacnro'));
                            $pagada = audita_cell($r, 'cargarap');
                            $isPag = ($pagada === true || $pagada === 1 || $pagada === '1'
                                || strtoupper((string) $pagada) === 'T' || (string) $pagada === '.T.');
                            $titulo = $per . ' - ' . trim($nom) . ' / ' . $os . ' Factura :  ' . trim($suc) . '-' . $fac;
                            $href = 'index.php?route=audit-facturacion'
                                . '&obrasoc=' . rawurlencode($os)
                                . '&suc=' . rawurlencode($suc)
                                . '&factura=' . rawurlencode($fac)
                                . '&prestador=' . rawurlencode($pre)
                                . '&periodo=' . rawurlencode($per)
                                . '&nombre=' . rawurlencode($nom)
                                . '&TITULO=' . rawurlencode($titulo);
                            $trStyle = ($refac !== '') ? ' style="background:#4aaaee;"' : '';
                            $ospetel = (strtoupper(trim($os)) === 'OSPETEL') ? '1' : '0';
                            $matchPer = ($periodo !== '' && strtoupper(trim($per)) === strtoupper(trim($periodo))) ? '1' : '0';
                            echo '<tr class="sf-row" data-href="' . audita_h($href) . '" data-ospetel="' . $ospetel . '" data-match="' . $matchPer . '"' . $trStyle . '>';
                            echo '<td>' . audita_h($per) . '</td>';
                            echo '<td>' . audita_h($pre) . '</td>';
                            echo '<td>' . audita_h($nom) . '</td>';
                            echo '<td>' . audita_h($os) . '</td>';
                            echo '<td>' . audita_h($suc) . '</td>';
                            echo '<td>' . audita_h($fac) . '</td>';
                            echo '<td>' . audita_h((string) audita_cell($r, 'cofecfac')) . '</td>';
                            echo '<td>' . audita_h((string) audita_cell($r, 'conetofact')) . '</td>';
                            echo '<td>' . audita_h((string) audita_cell($r, 'coivafact')) . '</td>';
                            echo '<td>' . audita_h((string) audita_cell($r, 'cototalfac')) . '</td>';
                            echo '<td>' . audita_h((string) audita_cell($r, 'cosegurofa')) . '</td>';
                            echo '<td>' . audita_h((string) audita_cell($r, 'cofecnac')) . '</td>';
                            echo '<td>' . ($isPag ? 'Sí' : '') . '</td>';
                            echo '</tr>';
                        }
                        ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-white d-flex flex-wrap gap-2">
                <button type="button" id="sf-seleccionar" class="btn btn-sm btn-primary">Seleccionar</button>
                <a href="index.php?route=audit-facturacion" class="btn btn-sm btn-outline-secondary">Salir</a>
            </div>
        </div>
    </div>
    <div class="col-lg-3">
        <div class="card border-0 shadow-sm mb-3" style="border-radius:10px;">
            <div class="card-header py-2 small fw-semibold" style="background:#e8eef7;">
                Particularidades del prestador
            </div>
            <div class="card-body">
                <textarea class="form-control form-control-sm" rows="6" readonly><?= audita_h($infoliq) ?></textarea>
            </div>
        </div>
        <?php if ($verCierre) { ?>
            <button type="button" id="sf-verif" class="btn btn-sm btn-outline-primary w-100 mb-2">Verificar cierre automatico</button>
            <button type="button" id="sf-cierre" class="btn btn-sm btn-outline-secondary w-100 mb-2 d-none">Confirme cierre Automatico</button>
            <div id="sf-cierre-msg" class="small text-danger d-none">Debe revisar la factura tiene mucha diferencia.</div>
        <?php } ?>
    </div>
</div>
<?php } ?>

<div id="sf-alert" class="alert alert-danger d-none mt-3 py-2"></div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    'use strict';
    var $ = window.jQuery;
    if (!$) return;
    var sel = null;

    function abrirFila($tr) {
        if (!$tr || !$tr.length) return;
        if ($tr.attr('data-ospetel') === '1') {
            $('#sf-alert').removeClass('d-none').text('EL CAMPO OBRA SOCIAL NO PUEDE SER OSPETEL - SELECCIONAR - ');
            return;
        }
        window.location.href = $tr.attr('data-href');
    }

    $('#sf-grid tbody').on('click', 'tr.sf-row', function () {
        $('#sf-grid tbody tr').removeClass('table-primary');
        $(this).addClass('table-primary');
        sel = $(this);
    }).on('dblclick', 'tr.sf-row', function () {
        abrirFila($(this));
    });

    $('#sf-seleccionar').on('click', function () {
        if (!sel) {
            $('#sf-alert').removeClass('d-none').text('Seleccione una factura.');
            return;
        }
        abrirFila(sel);
    });

    $('#sf-verif, #sf-cierre').on('click', function () {
        $('#sf-alert').removeClass('d-none').text('Cierre automático no migrado (consulta/detalle/DETCLOSE).');
    });

    var $loc = $('#sf-grid tbody tr.sf-row[data-match="1"]').first();
    if ($loc.length) {
        $loc.trigger('click');
    }
});
</script>

<?php require_once __DIR__ . '/../../views/layouts/footer.php'; ?>
