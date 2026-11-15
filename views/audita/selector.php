<?php
/**
 * Selector de facturas en audifac2 (menú sin parámetros Init de AUDITA).
 * AUDIFACT.SCX no está migrado; se agrupan columnas attested del Init.
 */
if (!function_exists('audita_h')) {
    function audita_h($s)
    {
        return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
    }
}

$facturas   = isset($facturas) ? $facturas : array();
$tablaOk    = isset($tablaOk) ? (bool) $tablaOk : true;
$errorTabla = isset($errorTabla) ? $errorTabla : '';
$tablaUsada = isset($tablaUsada) && (string) $tablaUsada !== '' ? (string) $tablaUsada : 'audifac2';
$pageTitle  = isset($pageTitle) ? $pageTitle : 'COMEDICA — Auditar Facturación';
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
            <i class="fa-solid fa-magnifying-glass-dollar me-2"></i>Auditar Facturación
        </h5>
        <p class="text-muted mb-0" style="font-size:0.82rem;">
            Elija una factura de <code><?= audita_h($tablaUsada) ?></code> para abrir AUDITA
        </p>
    </div>
    <a href="index.php?route=audit-facturacion&amp;grid=1" class="btn btn-sm btn-outline-secondary">
        Abrir grilla sin filtro
    </a>
</div>

<?php if (!$tablaOk) { ?>
    <div class="alert alert-danger">
        No se pudo leer <code><?= audita_h($tablaUsada !== '' ? $tablaUsada : 'audifac2') ?></code><?= $errorTabla !== '' ? ': ' . audita_h($errorTabla) : '.' ?>
    </div>
<?php } elseif (empty($facturas)) { ?>
    <div class="alert alert-info">
        No hay facturas en <code><?= audita_h($tablaUsada) ?></code>. Cargue una liquidación a auditar o abra la grilla sin filtro.
    </div>
<?php } else { ?>
<div class="card border-0 shadow-sm" style="border-radius:10px;">
    <div class="card-header py-2 text-white" style="background:#2b579a;">
        <span class="small fw-semibold">Facturas a auditar (<?= (int) count($facturas) ?>)</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0">
                <thead>
                    <tr>
                        <th>Prestador</th>
                        <th>Nombre</th>
                        <th>Obra Social</th>
                        <th>Suc</th>
                        <th>Factura</th>
                        <th>Periodo</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $i = 0;
                $n = count($facturas);
                for ($i = 0; $i < $n; $i++) {
                    $r = $facturas[$i];
                    $pre = isset($r['coprestado']) ? $r['coprestado'] : (isset($r['COPRESTADO']) ? $r['COPRESTADO'] : '');
                    $nom = isset($r['conomprest']) ? $r['conomprest'] : (isset($r['CONOMPREST']) ? $r['CONOMPREST'] : '');
                    $os  = isset($r['coobrasoc']) ? $r['coobrasoc'] : (isset($r['COOBRASOC']) ? $r['COOBRASOC'] : '');
                    $suc = isset($r['cosucfac']) ? $r['cosucfac'] : (isset($r['COSUCFAC']) ? $r['COSUCFAC'] : '');
                    $fac = isset($r['conrofac']) ? $r['conrofac'] : (isset($r['CONROFAC']) ? $r['CONROFAC'] : '');
                    $per = isset($r['coperiodo']) ? $r['coperiodo'] : (isset($r['COPERIODO']) ? $r['COPERIODO'] : '');
                    $href = 'index.php?route=audit-facturacion'
                        . '&prestador=' . rawurlencode((string) $pre)
                        . '&nombre=' . rawurlencode((string) $nom)
                        . '&obrasoc=' . rawurlencode((string) $os)
                        . '&suc=' . rawurlencode((string) $suc)
                        . '&factura=' . rawurlencode((string) $fac)
                        . '&periodo=' . rawurlencode((string) $per);
                    echo '<tr>';
                    echo '<td>' . audita_h($pre) . '</td>';
                    echo '<td>' . audita_h($nom) . '</td>';
                    echo '<td>' . audita_h($os) . '</td>';
                    echo '<td>' . audita_h($suc) . '</td>';
                    echo '<td>' . audita_h($fac) . '</td>';
                    echo '<td>' . audita_h($per) . '</td>';
                    echo '<td><a class="btn btn-sm btn-primary" href="' . audita_h($href) . '">Auditar</a></td>';
                    echo '</tr>';
                }
                ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php } ?>

<?php require_once __DIR__ . '/../../views/layouts/footer.php'; ?>
