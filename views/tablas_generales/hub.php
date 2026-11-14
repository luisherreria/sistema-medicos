<?php
/**
 * views/tablas_generales/hub.php
 * Portada de Archivos → Tablas Generales
 *
 * @var array $modulos
 */

$pageTitle  = isset($pageTitle) ? $pageTitle : 'COMEDICA — Tablas Generales';
$breadcrumb = isset($breadcrumb) ? $breadcrumb : array(array('label' => 'Tablas Generales'));
$modulos    = isset($modulos) ? $modulos : array();

require_once __DIR__ . '/../layouts/header.php';
?>

<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
    <div>
        <h5 class="mb-1 fw-bold text-primary">
            <i class="fa-solid fa-table-list me-2"></i>Tablas Generales
        </h5>
        <p class="text-muted mb-0" style="font-size:0.82rem;">
            Archivos &rsaquo; Tablas Generales &mdash; Seleccione un ABM
        </p>
    </div>
    <span class="badge bg-secondary px-3 py-2">
        <?= count($modulos) ?> módulo<?= count($modulos) === 1 ? '' : 's' ?>
    </span>
</div>

<div class="row g-3">
    <?php foreach ($modulos as $m):
        $ref    = isset($m['ref']) ? $m['ref'] : '';
        $titulo = isset($m['titulo']) ? $m['titulo'] : $ref;
        $ruta   = isset($m['ruta']) ? $m['ruta'] : ('tablas-generales&ref=' . $ref);
        $href   = 'index.php?route=' . $ruta;
    ?>
    <div class="col-sm-6 col-lg-4 col-xl-3">
        <a href="<?= htmlspecialchars($href) ?>"
           class="text-decoration-none">
            <div class="card border-0 shadow-sm h-100" style="border-radius:12px;">
                <div class="card-body d-flex align-items-start gap-2 py-3">
                    <span class="d-flex align-items-center justify-content-center flex-shrink-0"
                          style="width:36px;height:36px;border-radius:8px;background:#e3f2fd;color:#1565c0;">
                        <i class="fa-solid fa-angle-right"></i>
                    </span>
                    <div>
                        <div class="fw-semibold text-dark" style="font-size:0.88rem;">
                            <?= htmlspecialchars($titulo) ?>
                        </div>
                        <div class="text-muted font-monospace" style="font-size:0.7rem;">
                            <?= htmlspecialchars($ref) ?>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </div>
    <?php endforeach; ?>
</div>

<?php if (empty($modulos)): ?>
<div class="text-center py-5">
    <i class="fa-solid fa-lock fa-2x text-muted mb-3 d-block"></i>
    <p class="text-muted mb-0">No hay tablas generales asignadas a su usuario.</p>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
