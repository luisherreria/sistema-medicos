<?php
/**
 * Reporte resu1-2 — Totales Acumulados x Obra Social (impresión).
 *
 * @var string $titulo
 * @var array  $filtros
 * @var array  $obras
 * @var array  $total
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
    <style>
        @media print { @page { size: portrait; margin: 10mm; } }
        th { font-size: 10px; }
        td { font-size: 11px; }
    </style>
</head>
<body>
<button class="no-print" type="button" onclick="window.print()">Imprimir</button>
<button class="no-print" type="button" onclick="window.close()">Cerrar</button>
<?php rfRptCabecera($titulo, $filtros); ?>

<?php if (!$obras): ?>
    <div class="empty">No hay facturas para los filtros seleccionados.</div>
<?php else: ?>
    <?php foreach ($obras as $os): ?>
        <div class="rpt-os">
            OBRA SOCIAL : <?= rfRptEsc($os['codigo']) ?>
            <?php if (!empty($os['nombre'])): ?>
                &nbsp; <?= rfRptEsc($os['nombre']) ?>
            <?php endif; ?>
        </div>
        <table>
            <thead>
            <tr>
                <th>Subcategoría</th>
                <th class="num">Cantidad</th>
                <th class="num">Total</th>
                <th class="num">Consultas</th>
                <th class="num">Importe</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($os['subs'] as $sub): ?>
                <tr>
                    <td><?= rfRptEsc($sub['subcateg']) ?></td>
                    <td class="num"><?= rfRptEntero($sub['cantidad']) ?></td>
                    <td class="num"><?= rfRptNum($sub['total']) ?></td>
                    <td class="num"><?= rfRptEntero($sub['consultas']) ?></td>
                    <td class="num"><?= rfRptNum($sub['importe']) ?></td>
                </tr>
            <?php endforeach; ?>
            <tr class="totales">
                <td>
                    TOTALES <?= rfRptEsc($os['codigo']) ?>
                    &nbsp;·&nbsp; Cons. Vestida: <?= rfRptNum($os['cons_vestida'] ?? 0) ?>
                </td>
                <td class="num"><?= rfRptEntero($os['cantidad']) ?></td>
                <td class="num"><?= rfRptNum($os['total']) ?></td>
                <td class="num"><?= rfRptEntero($os['consultas']) ?></td>
                <td class="num"><?= rfRptNum($os['importe']) ?></td>
            </tr>
            </tbody>
        </table>
    <?php endforeach; ?>

    <table style="margin-top:16px;">
        <tbody>
        <tr class="tot-gral">
            <td>
                TOTAL GENERAL
                &nbsp;·&nbsp; Cons. Vestida: <?= rfRptNum($total['cons_vestida'] ?? 0) ?>
            </td>
            <td class="num"><?= rfRptEntero($total['cantidad'] ?? 0) ?></td>
            <td class="num"><?= rfRptNum($total['total'] ?? 0) ?></td>
            <td class="num"><?= rfRptEntero($total['consultas'] ?? 0) ?></td>
            <td class="num"><?= rfRptNum($total['importe'] ?? 0) ?></td>
        </tr>
        </tbody>
    </table>
<?php endif; ?>
</body>
</html>
