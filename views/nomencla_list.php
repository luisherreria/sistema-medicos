<?php
/**
 * views/nomencla_list.php
 * Listado principal — Nomenclador Nacional
 * Variables esperadas desde NomenclaController::index()
 *
 * @var array  $registros
 * @var int    $total
 * @var int    $totalPaginas
 * @var int    $pagina
 * @var int    $limite
 * @var string $busqueda
 * @var string $ordenCol
 * @var string $ordenDir
 * @var array  $sortLinks
 * @var array  $queryBase
 * @var bool   $puedeAgregar
 * @var bool   $puedeEditar
 * @var bool   $puedeBorrar
 * @var bool   $puedeActualizar
 * @var string|null $dbError
 */

$pageTitle  = $pageTitle  ?? 'COMEDICA — Nomenclador Nacional';
$breadcrumb = $breadcrumb ?? [['label' => 'Nomenclador Nacional']];

require_once __DIR__ . '/layouts/header.php';

$registros       = $registros ?? [];
$total           = (int) ($total ?? 0);
$totalPaginas    = max(1, (int) ($totalPaginas ?? 1));
$pagina          = max(1, (int) ($pagina ?? 1));
$limite          = (int) ($limite ?? 50);
$busqueda        = (string) ($busqueda ?? '');
$ordenCol        = (string) ($ordenCol ?? 'tacodigo');
$ordenDir        = (string) ($ordenDir ?? 'ASC');
$sortLinks       = $sortLinks ?? [];
$queryBase       = $queryBase ?? [];
$puedeAgregar    = (bool) ($puedeAgregar ?? false);
$puedeEditar     = (bool) ($puedeEditar ?? false);
$puedeBorrar     = (bool) ($puedeBorrar ?? false);
$puedeActualizar = (bool) ($puedeActualizar ?? false);
$dbError         = $dbError ?? null;

$cols = [
    'tacodigo'   => 'Código',
    'tatiponom'  => 'Tipo',
    'tadescrip'  => 'Descripción',
    'tagrupo'    => 'Grupo',
    'taimporte'  => 'Importe $',
    'capitulo'   => 'Cap.',
    'nomenclada' => 'Nomenclada',
    'subgrupo'   => 'SubGrupo',
    'tasauso'    => 'Tasa Uso',
    'complejida' => 'Complejidad',
    'grupocar'   => 'Grupo Cartilla',
    'gruposup'   => 'Título Cartilla',
    'vermodulo'  => 'VerModulo',
];

/**
 * Helper local solo de presentación: URL de paginación.
 */
$pagUrl = static function (int $p) use ($queryBase): string {
    $params = array_merge(['route' => 'nomenclador'], $queryBase, ['page' => $p]);
    foreach ($params as $k => $v) {
        if ($v === '' || $v === null) {
            unset($params[$k]);
        }
    }
    return 'index.php?' . http_build_query($params);
};

$sortIcon = static function (string $col) use ($ordenCol, $ordenDir): string {
    if ($ordenCol !== $col) {
        return '<i class="fa-solid fa-sort text-slate-400 ms-1" style="font-size:.65rem;"></i>';
    }
    $icon = $ordenDir === 'ASC' ? 'fa-sort-up' : 'fa-sort-down';
    return '<i class="fa-solid ' . $icon . ' text-sky-300 ms-1" style="font-size:.7rem;"></i>';
};
?>

<script>
window.tailwind && (window.tailwind.config = { corePlugins: { preflight: false } });
</script>
<script src="https://cdn.tailwindcss.com?plugins=forms"></script>
<script>
tailwind.config = { corePlugins: { preflight: false } };
</script>

<div id="nn-app" class="space-y-3">

    <!-- ═══ BARRA SUPERIOR: acciones ═══════════════════════════════════════ -->
    <div class="flex flex-wrap items-center gap-2 bg-white border border-slate-200 rounded-xl px-3 py-2 shadow-sm">

        <div class="flex items-center gap-2 me-2 flex-shrink-0">
            <div class="w-8 h-8 rounded-lg flex items-center justify-center"
                 style="background:linear-gradient(135deg,#0d47a1,#1976d2);">
                <i class="fa-solid fa-book-medical text-white text-sm"></i>
            </div>
            <span class="font-bold text-slate-800 text-sm whitespace-nowrap">
                Actualización de Prácticas
            </span>
        </div>

        <div class="flex flex-wrap items-center gap-1">
            <?php if ($puedeAgregar): ?>
            <button type="button" id="nn-btn-agregar"
                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-md text-white text-xs font-semibold bg-emerald-600 hover:bg-emerald-700 border-0">
                <i class="fa-solid fa-plus"></i> Agregar
            </button>
            <?php endif; ?>

            <?php if ($puedeBorrar): ?>
            <button type="button" id="nn-btn-borrar"
                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-md text-white text-xs font-semibold bg-red-600 hover:bg-red-700 border-0">
                <i class="fa-solid fa-minus"></i> Borrar
            </button>
            <?php endif; ?>

            <?php if ($puedeEditar): ?>
            <button type="button" id="nn-btn-editar"
                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-md text-white text-xs font-semibold bg-blue-600 hover:bg-blue-700 border-0">
                <i class="fa-solid fa-pen-to-square"></i> Editar
            </button>
            <?php endif; ?>

            <?php if ($puedeActualizar): ?>
            <button type="button" id="nn-btn-actualizar"
                    title="Actualiza Descripcion de Practicas en Prestadores, Sucursales, Obra Social"
                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-md text-white text-xs font-semibold bg-slate-500 hover:bg-slate-600 border-0">
                <i class="fa-solid fa-arrows-rotate"></i> Actualizar Descripción
            </button>
            <?php endif; ?>
        </div>

        <div class="flex flex-wrap items-center gap-1 ms-auto">
            <button type="button" id="nn-btn-excel"
                    onclick="exportarTablaAExcel('nn-tabla', 'nomenclador_nacional')"
                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-md text-white text-xs font-semibold bg-green-800 hover:bg-green-900 border-0">
                <i class="fa-solid fa-file-excel"></i> Exportar Excel
            </button>
            <button type="button" id="nn-btn-pdf"
                    onclick="exportarTablaAPDF('nn-tabla', 'nomenclador_nacional')"
                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-md text-white text-xs font-semibold bg-red-800 hover:bg-red-900 border-0">
                <i class="fa-solid fa-file-pdf"></i> Imprimir PDF
            </button>
        </div>
    </div>

    <!-- ═══ BUSCADOR GLOBAL ════════════════════════════════════════════════ -->
    <form method="get" action="index.php" class="bg-white border border-slate-200 rounded-xl px-3 py-2 shadow-sm">
        <input type="hidden" name="route" value="nomenclador">
        <input type="hidden" name="sort" value="<?= htmlspecialchars($ordenCol) ?>">
        <input type="hidden" name="dir" value="<?= htmlspecialchars($ordenDir) ?>">
        <div class="flex flex-wrap items-center gap-2">
            <label for="nn-buscar" class="text-xs font-semibold text-slate-600 whitespace-nowrap">
                <i class="fa-solid fa-magnifying-glass me-1"></i>Buscar x Código o Nombre
            </label>
            <input type="text" id="nn-buscar" name="buscar"
                   value="<?= htmlspecialchars($busqueda) ?>"
                   placeholder="Código, descripción, grupo, tipo…"
                   class="flex-grow min-w-[220px] form-control form-control-sm text-sm border-slate-300 rounded-md">
            <button type="submit"
                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-md text-white text-xs font-semibold bg-sky-700 hover:bg-sky-800 border-0">
                <i class="fa-solid fa-search"></i> Buscar
            </button>
            <?php if ($busqueda !== ''): ?>
            <a href="index.php?route=nomenclador&sort=<?= urlencode($ordenCol) ?>&dir=<?= urlencode($ordenDir) ?>"
               class="text-xs text-slate-500 hover:text-slate-800 no-underline">
                Limpiar
            </a>
            <?php endif; ?>
            <span id="nn-resumen" class="text-xs text-slate-500 ms-auto">
                <?= number_format($total, 0, ',', '.') ?> registro<?= $total === 1 ? '' : 's' ?>
                · pág. <?= $pagina ?>/<?= $totalPaginas ?>
            </span>
        </div>
    </form>

    <?php if (!empty($dbError)): ?>
    <div class="alert alert-danger text-sm rounded-lg mb-0">
        <i class="fa-solid fa-triangle-exclamation me-1"></i>
        Error al consultar el nomenclador. Verifique la tabla <code>nomencla</code>.
    </div>
    <?php endif; ?>

    <!-- ═══ GRILLA ═════════════════════════════════════════════════════════ -->
    <div class="overflow-x-auto bg-white border border-slate-200 rounded-xl shadow-sm">
        <table id="nn-tabla" class="w-full text-sm text-left text-gray-500 border-collapse" style="min-width:1100px;">
            <thead>
                <tr class="bg-slate-800 text-white">
                    <?php foreach ($cols as $col => $label): ?>
                    <th class="p-2 font-semibold whitespace-nowrap" scope="col">
                        <a href="<?= htmlspecialchars($sortLinks[$col] ?? '#') ?>"
                           class="text-white no-underline hover:text-sky-200 inline-flex items-center">
                            <?= htmlspecialchars($label) ?>
                            <?= $sortIcon($col) ?>
                        </a>
                    </th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody id="nn-tbody">
            <?php if (empty($registros)): ?>
                <tr>
                    <td colspan="<?= count($cols) ?>" class="p-4 text-center text-slate-400">
                        No se encontraron prácticas<?= $busqueda !== '' ? ' para la búsqueda indicada' : '' ?>.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($registros as $i => $row): ?>
                <tr class="<?= $i % 2 === 0 ? 'bg-white' : 'bg-slate-50' ?> hover:bg-sky-50 border-b border-slate-100">
                    <td class="p-2 border border-slate-100 font-mono text-xs"><?= htmlspecialchars((string) ($row['tacodigo'] ?? '')) ?></td>
                    <td class="p-2 border border-slate-100"><?= htmlspecialchars((string) ($row['tatiponom'] ?? '')) ?></td>
                    <td class="p-2 border border-slate-100"><?= htmlspecialchars((string) ($row['tadescrip'] ?? '')) ?></td>
                    <td class="p-2 border border-slate-100"><?= htmlspecialchars((string) ($row['tagrupo'] ?? '')) ?></td>
                    <td class="p-2 border border-slate-100 text-right font-mono">
                        <?= number_format((float) ($row['taimporte'] ?? 0), 2, '.', ',') ?>
                    </td>
                    <td class="p-2 border border-slate-100"><?= htmlspecialchars((string) ($row['capitulo'] ?? '')) ?></td>
                    <td class="p-2 border border-slate-100 text-center"><?= htmlspecialchars((string) ($row['nomenclada'] ?? '')) ?></td>
                    <td class="p-2 border border-slate-100"><?= htmlspecialchars((string) ($row['subgrupo'] ?? '')) ?></td>
                    <td class="p-2 border border-slate-100"><?= htmlspecialchars((string) ($row['tasauso'] ?? '')) ?></td>
                    <td class="p-2 border border-slate-100"><?= htmlspecialchars((string) ($row['complejida'] ?? '')) ?></td>
                    <td class="p-2 border border-slate-100"><?= htmlspecialchars((string) ($row['grupocar'] ?? '')) ?></td>
                    <td class="p-2 border border-slate-100"><?= htmlspecialchars((string) ($row['gruposup'] ?? '')) ?></td>
                    <td class="p-2 border border-slate-100 text-center">
                        <input type="checkbox" disabled
                               <?= ((int) ($row['vermodulo'] ?? 0) === 1) ? 'checked' : '' ?>
                               class="rounded border-slate-300">
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- ═══ PAGINACIÓN ═════════════════════════════════════════════════════ -->
    <?php
    $ventana = 2;
    $desde   = max(1, $pagina - $ventana);
    $hasta   = min($totalPaginas, $pagina + $ventana);
    ?>
    <div id="nn-paginacion" class="flex flex-wrap items-center justify-between gap-2 bg-white border border-slate-200 rounded-xl px-3 py-2 shadow-sm">
        <div class="text-xs text-slate-500">
            Mostrando <?= $total === 0 ? 0 : (($pagina - 1) * $limite + 1) ?>–
            <?= min($pagina * $limite, $total) ?> de <?= number_format($total, 0, ',', '.') ?>
        </div>
        <div class="flex items-center gap-1 flex-wrap">
            <a href="<?= $pagina > 1 ? htmlspecialchars($pagUrl($pagina - 1)) : '#' ?>"
               class="px-2.5 py-1 rounded border text-xs no-underline <?= $pagina <= 1 ? 'pointer-events-none opacity-40 border-slate-200 text-slate-400' : 'border-slate-300 text-slate-700 hover:bg-slate-100' ?>">
                <i class="fa-solid fa-chevron-left me-1"></i>Anterior
            </a>

            <?php if ($desde > 1): ?>
                <a href="<?= htmlspecialchars($pagUrl(1)) ?>" class="px-2.5 py-1 rounded border border-slate-300 text-xs text-slate-700 no-underline hover:bg-slate-100">1</a>
                <?php if ($desde > 2): ?><span class="text-xs text-slate-400 px-1">…</span><?php endif; ?>
            <?php endif; ?>

            <?php for ($p = $desde; $p <= $hasta; $p++): ?>
                <?php if ($p === $pagina): ?>
                    <span class="px-2.5 py-1 rounded bg-slate-800 text-white text-xs font-semibold"><?= $p ?></span>
                <?php else: ?>
                    <a href="<?= htmlspecialchars($pagUrl($p)) ?>"
                       class="px-2.5 py-1 rounded border border-slate-300 text-xs text-slate-700 no-underline hover:bg-slate-100"><?= $p ?></a>
                <?php endif; ?>
            <?php endfor; ?>

            <?php if ($hasta < $totalPaginas): ?>
                <?php if ($hasta < $totalPaginas - 1): ?><span class="text-xs text-slate-400 px-1">…</span><?php endif; ?>
                <a href="<?= htmlspecialchars($pagUrl($totalPaginas)) ?>" class="px-2.5 py-1 rounded border border-slate-300 text-xs text-slate-700 no-underline hover:bg-slate-100"><?= $totalPaginas ?></a>
            <?php endif; ?>

            <a href="<?= $pagina < $totalPaginas ? htmlspecialchars($pagUrl($pagina + 1)) : '#' ?>"
               class="px-2.5 py-1 rounded border text-xs no-underline <?= $pagina >= $totalPaginas ? 'pointer-events-none opacity-40 border-slate-200 text-slate-400' : 'border-slate-300 text-slate-700 hover:bg-slate-100' ?>">
                Siguiente<i class="fa-solid fa-chevron-right ms-1"></i>
            </a>
        </div>
    </div>
</div>

<?php if ($puedeBorrar): ?>
<?php require __DIR__ . '/nomencla/_modal_borrar_rango.php'; ?>
<?php endif; ?>

<script>
(function () {
    ['nn-btn-agregar', 'nn-btn-editar', 'nn-btn-actualizar'].forEach(function (id) {
        var btn = document.getElementById(id);
        if (!btn) return;
        btn.addEventListener('click', function () {
            alert('Acción pendiente de implementar: ' + (btn.textContent || '').trim());
        });
    });

    var btnBorrar = document.getElementById('nn-btn-borrar');
    var form = document.getElementById('form-borrar-rango');
    if (!btnBorrar || !form) return;

    btnBorrar.addEventListener('click', function () {
        form.reset();
        var err = document.getElementById('nn-borrar-error');
        if (err) err.style.display = 'none';
        var modalEl = document.getElementById('modalBorrarRango');
        if (modalEl && window.bootstrap) {
            window.bootstrap.Modal.getOrCreateInstance(modalEl).show();
        }
    });

    function mostrarError(texto) {
        var err = document.getElementById('nn-borrar-error');
        if (!err) return;
        err.textContent = texto;
        err.style.display = texto ? 'block' : 'none';
    }

    function confirmar(texto) {
        if (window.Swal) {
            return window.Swal.fire({
                icon: 'warning',
                title: 'Confirmar borrado',
                text: texto,
                showCancelButton: true,
                confirmButtonText: 'Sí',
                cancelButtonText: 'No',
                confirmButtonColor: '#b91c1c',
                reverseButtons: true,
                focusCancel: true
            }).then(function (r) { return !!r.isConfirmed; });
        }
        return Promise.resolve(window.confirm(texto));
    }

    function recargarGrilla() {
        return fetch(window.location.href, {
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function (r) { return r.text(); })
        .then(function (html) {
            var doc = new DOMParser().parseFromString(html, 'text/html');
            ['nn-tbody', 'nn-paginacion', 'nn-resumen'].forEach(function (id) {
                var nuevo = doc.getElementById(id);
                var actual = document.getElementById(id);
                if (nuevo && actual) actual.replaceWith(nuevo);
            });
        });
    }

    form.addEventListener('submit', function (ev) {
        ev.preventDefault();
        mostrarError('');

        var desde = (document.getElementById('nn-borrar-desde').value || '').trim();
        var hasta = (document.getElementById('nn-borrar-hasta').value || '').trim();
        if (!desde || !hasta) {
            mostrarError('Los campos Desde y Hasta son obligatorios.');
            return;
        }
        if (desde > hasta) {
            mostrarError('El código Desde no puede ser mayor que el código Hasta.');
            return;
        }

        var submit = document.getElementById('nn-borrar-submit');
        confirmar('¿Confirma el borrado en el NOMENCLADOR? Esta acción no podrá deshacerse.')
            .then(function (okNomencla) {
                if (!okNomencla) return null;
                return confirmar('¿Confirma también el borrado en el prestador y de sus sucursales?');
            })
            .then(function (okPrestadores) {
                if (okPrestadores === null || okPrestadores === undefined) return;
                if (submit) submit.disabled = true;

                var datos = new FormData(form);
                datos.set('desde', desde);
                datos.set('hasta', hasta);
                datos.set('confirmar_prestadores', okPrestadores ? '1' : '0');

                return fetch('index.php?route=nomenclador&action=borrar_rango', {
                    method: 'POST',
                    body: datos,
                    credentials: 'same-origin',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(function (r) { return r.json().then(function (j) { return { status: r.status, body: j }; }); })
                .then(function (res) {
                    if (!res.body || !res.body.ok) {
                        throw new Error((res.body && res.body.msg) || 'No se pudo completar el borrado.');
                    }
                    var modalEl = document.getElementById('modalBorrarRango');
                    if (modalEl && window.bootstrap) {
                        var inst = window.bootstrap.Modal.getInstance(modalEl);
                        if (inst) inst.hide();
                    }
                    return recargarGrilla().then(function () {
                        if (window.Swal) {
                            window.Swal.fire({
                                icon: 'success',
                                title: 'Listo',
                                text: res.body.msg,
                                confirmButtonColor: '#0d47a1'
                            });
                        }
                    });
                });
            })
            .catch(function (err) {
                mostrarError(err && err.message ? err.message : 'No se pudo completar el borrado.');
            })
            .finally(function () {
                if (submit) submit.disabled = false;
            });
    });
})();
</script>

<?php require_once __DIR__ . '/layouts/footer.php'; ?>
