<?php
/**
 * Reporte resu1-1 — Detallado x Obra Social (impresión).
 *
 * @var string $titulo
 * @var array  $filtros
 * @var array  $grupos
 */
require_once __DIR__ . '/_rpt_common.php';
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?= rfRptEsc($titulo) ?></title>
    <?php rfRptEstilos(); ?>
</head>
<body>
<button class="no-print" type="button" onclick="window.print()">Imprimir</button>
<button class="no-print" type="button" onclick="window.close()">Cerrar</button>
<?php rfRptCabecera($titulo, $filtros); ?>

<?php if (!$grupos): ?>
    <div class="empty">No hay facturas para los filtros seleccionados.</div>
<?php else: ?>
    <?php foreach ($grupos as $g): ?>
        <div class="rpt-grupo">SUBGRUPO: <?= rfRptEsc($g['nombre']) ?></div>
        <table>
            <thead>
            <tr>
                <th>Fecha</th>
                <th>Factura</th>
                <th>Período</th>
                <th>Obra Social</th>
                <th>Prestador</th>
                <th>Rec. Fact.</th>
                <th class="num">Cantidad</th>
                <th class="num">Importe</th>
                <th class="num">I.V.A.</th>
                <th class="num">Coseguro</th>
                <th class="num">Total</th>
                <th class="num">Consultas</th>
                <th class="num">Importe</th>
                <th>Tipo</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($g['filas'] as $r): ?>
                <?php
                $suc = trim((string) ($r['cosucfac'] ?? ''));
                $nro = trim((string) ($r['conrofac'] ?? ''));
                $fac = ($suc !== '' && $nro !== '') ? ($suc . '-' . $nro) : ($nro ?: $suc);
                $os  = trim((string) ($r['coobrasoc'] ?? ''));
                if (!empty($r['os_nombre'])) {
                    $os .= ' ' . $r['os_nombre'];
                }
                ?>
                <tr>
                    <td class="mono"><?= rfRptEsc(rfRptFecha($r['cofecfac'] ?? '')) ?></td>
                    <td class="mono"><?= rfRptEsc($fac) ?></td>
                    <td class="mono"><?= rfRptEsc(rfRptPeriodo($r['coperiodo'] ?? '')) ?></td>
                    <td><?= rfRptEsc($os) ?></td>
                    <td><?= rfRptEsc($r['conomprest'] ?? '') ?></td>
                    <td class="mono"><?= rfRptEsc($r['cofactura'] ?? '') ?></td>
                    <td class="num"><?= rfRptEntero($r['cocantcalc'] ?? 0) ?></td>
                    <td class="num"><?= rfRptNum($r['coimpfac'] ?? 0) ?></td>
                    <td class="num"><?= rfRptNum($r['coivafac'] ?? 0) ?></td>
                    <td class="num"><?= rfRptNum($r['cocsgfac'] ?? 0) ?></td>
                    <td class="num"><?= rfRptNum($r['cototalfac'] ?? 0) ?></td>
                    <td class="num"><?= rfRptEntero($r['cocantcalc'] ?? 0) ?></td>
                    <td class="num"><?= rfRptNum($r['copesos'] ?? 0) ?></td>
                    <td><?= rfRptEsc($r['cotipopre'] ?? '') ?></td>
                </tr>
            <?php endforeach; ?>
            <tr class="totales">
                <td colspan="6">TOTALES <?= rfRptEsc($g['nombre']) ?></td>
                <td class="num"><?= rfRptEntero($g['cantidad']) ?></td>
                <td class="num"><?= rfRptNum($g['importe']) ?></td>
                <td class="num"><?= rfRptNum($g['iva']) ?></td>
                <td class="num"><?= rfRptNum($g['coseguro']) ?></td>
                <td class="num"><?= rfRptNum($g['total']) ?></td>
                <td class="num"><?= rfRptEntero($g['consultas']) ?></td>
                <td class="num"><?= rfRptNum($g['pesos']) ?></td>
                <td></td>
            </tr>
            </tbody>
        </table>
    <?php endforeach; ?>
<?php endif; ?>
</body>
</html>
