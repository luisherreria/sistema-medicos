<?php
/**
 * views/tablas_generales_hub.php
 * Portada de Archivos → Tablas Generales
 *
 * @var array $modulos
 * @var bool  $puedeAgregar
 * @var bool  $puedeEditar
 * @var bool  $puedeBorrar
 */

$pageTitle  = $pageTitle  ?? 'COMEDICA — Tablas Generales';
$breadcrumb = $breadcrumb ?? [['label' => 'Tablas Generales']];
$modulos    = $modulos ?? [];

require_once __DIR__ . '/layouts/header.php';
?>

<script>
window.tailwind && (window.tailwind.config = { corePlugins: { preflight: false } });
</script>
<script src="https://cdn.tailwindcss.com?plugins=forms"></script>
<script>
tailwind.config = { corePlugins: { preflight: false } };
</script>

<div class="space-y-3">
    <div class="flex items-center gap-2 bg-white border border-slate-200 rounded-xl px-3 py-2 shadow-sm">
        <div class="w-8 h-8 rounded-lg flex items-center justify-center"
             style="background:linear-gradient(135deg,#0d47a1,#1976d2);">
            <i class="fa-solid fa-table-list text-white text-sm"></i>
        </div>
        <div>
            <div class="font-bold text-slate-800 text-sm">Tablas Generales</div>
            <div class="text-[11px] text-slate-500">Seleccione un ABM para cargar, editar o exportar</div>
        </div>
        <span class="ms-auto text-xs text-slate-500"><?= count($modulos) ?> módulos</span>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3">
        <?php foreach ($modulos as $m):
            $ref    = isset($m['ref']) ? $m['ref'] : '';
            $titulo = isset($m['titulo']) ? $m['titulo'] : $ref;
            $href   = 'index.php?route=tablas-generales&ref=' . rawurlencode($ref);
        ?>
        <a href="<?= htmlspecialchars($href) ?>"
           class="no-underline bg-white border border-slate-200 rounded-xl px-3 py-3 shadow-sm hover:border-sky-400 hover:shadow-md transition">
            <div class="flex items-start gap-2">
                <i class="fa-solid fa-angle-right text-sky-700 mt-1"></i>
                <div>
                    <div class="text-sm font-semibold text-slate-800"><?= htmlspecialchars($titulo) ?></div>
                    <div class="text-[11px] text-slate-400 font-mono">ref=<?= htmlspecialchars($ref) ?></div>
                </div>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
</div>

<?php require_once __DIR__ . '/layouts/footer.php'; ?>
