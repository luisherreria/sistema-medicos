<?php
/**
 * views/tablas_generales_list.php
 * ABM dinámico de la tabla maestra `tablas`.
 *
 * @var string      $ref
 * @var array       $cfg
 * @var string|null $tatipo
 * @var string      $titulo
 * @var string[]    $campos
 * @var array       $labels
 * @var array       $configTablas
 * @var array       $registros
 * @var int         $total
 * @var int         $totalPaginas
 * @var int         $pagina
 * @var int         $limite
 * @var string      $busqueda
 * @var string      $ordenCol
 * @var string      $ordenDir
 * @var array       $sortLinks
 * @var array       $queryBase
 * @var bool        $puedeAgregar
 * @var bool        $puedeEditar
 * @var bool        $puedeBorrar
 * @var string      $csrfToken
 * @var string|null $dbError
 * @var string|null $flashOk
 * @var string|null $flashError
 */

$pageTitle  = $pageTitle  ?? 'COMEDICA — Tablas';
$breadcrumb = $breadcrumb ?? [['label' => 'Tablas']];

require_once __DIR__ . '/layouts/header.php';

$ref           = strtoupper((string) ($ref ?? $tipo ?? ''));
$cfg           = $cfg ?? [];
$tatipo        = array_key_exists('tatipo', $cfg) ? $cfg['tatipo'] : ($tatipo ?? null);
$titulo        = (string) ($titulo ?? 'Tablas');
$campos        = $campos ?? [];
$labels        = $labels ?? [];
$registros     = $registros ?? [];
$total         = (int) ($total ?? 0);
$totalPaginas  = max(1, (int) ($totalPaginas ?? 1));
$pagina        = max(1, (int) ($pagina ?? 1));
$limite        = (int) ($limite ?? 50);
$busqueda      = (string) ($busqueda ?? '');
$ordenCol      = (string) ($ordenCol ?? 'tacodigo');
$ordenDir      = (string) ($ordenDir ?? 'ASC');
$sortLinks     = $sortLinks ?? [];
$queryBase     = $queryBase ?? [];
$puedeAgregar  = (bool) ($puedeAgregar ?? false);
$puedeEditar   = (bool) ($puedeEditar ?? false);
$puedeBorrar   = (bool) ($puedeBorrar ?? false);
$csrfToken     = (string) ($csrfToken ?? '');
$dbError       = $dbError ?? null;
$flashOk       = $flashOk ?? null;
$flashError    = $flashError ?? null;
$colCount      = count($campos) + (($puedeEditar || $puedeBorrar) ? 1 : 0);
$archivoExport = 'tabla_' . strtolower($ref);

$pagUrl = static function (int $p) use ($queryBase): string {
    $params = array_merge(['route' => 'tablas-generales'], $queryBase, ['page' => $p]);
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

$esCheck = static function (string $col): bool {
    return $col === 'vista' || $col === 'taselecc' || $col === 'isonco' || $col === 'isvih';
};
?>

<script>
window.tailwind && (window.tailwind.config = { corePlugins: { preflight: false } });
</script>
<script src="https://cdn.tailwindcss.com?plugins=forms"></script>
<script>
tailwind.config = { corePlugins: { preflight: false } };
</script>

<div id="tg-app" class="space-y-3"
     data-ref="<?= htmlspecialchars($ref) ?>"
     data-csrf="<?= htmlspecialchars($csrfToken) ?>">

    <!-- ═══ BARRA SUPERIOR ════════════════════════════════════════════════ -->
    <div class="flex flex-wrap items-center gap-2 bg-white border border-slate-200 rounded-xl px-3 py-2 shadow-sm">
        <div class="flex items-center gap-2 me-2 flex-shrink-0">
            <div class="w-8 h-8 rounded-lg flex items-center justify-center"
                 style="background:linear-gradient(135deg,#0d47a1,#1976d2);">
                <i class="fa-solid fa-table-list text-white text-sm"></i>
            </div>
            <div>
                <span class="font-bold text-slate-800 text-sm whitespace-nowrap d-block">
                    <?= htmlspecialchars($titulo) ?>
                </span>
                <span class="text-[11px] text-slate-500 font-mono">ref = <?= htmlspecialchars($ref) ?><?= $tatipo ? ' · tatipo = ' . htmlspecialchars((string) $tatipo) : '' ?></span>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-1">
            <?php if ($puedeAgregar): ?>
            <button type="button" id="tg-btn-agregar"
                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-md text-white text-xs font-semibold bg-emerald-600 hover:bg-emerald-700 border-0">
                <i class="fa-solid fa-plus"></i> Agregar
            </button>
            <?php endif; ?>

            <?php if ($puedeEditar): ?>
            <button type="button" id="tg-btn-editar"
                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-md text-white text-xs font-semibold bg-blue-600 hover:bg-blue-700 border-0">
                <i class="fa-solid fa-pen-to-square"></i> Editar
            </button>
            <?php endif; ?>

            <?php if ($puedeBorrar): ?>
            <button type="button" id="tg-btn-borrar"
                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-md text-white text-xs font-semibold bg-red-600 hover:bg-red-700 border-0">
                <i class="fa-solid fa-minus"></i> Borrar
            </button>
            <?php endif; ?>
        </div>

        <div class="flex flex-wrap items-center gap-1 ms-auto">
            <button type="button"
                    onclick="exportarTablaAExcel('tabla-dinamica', 'Reporte')"
                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-md text-white text-xs font-semibold bg-green-800 hover:bg-green-900 border-0">
                <i class="fa-solid fa-file-excel"></i> Exportar Excel
            </button>
            <button type="button"
                    onclick="exportarTablaAPDF('tabla-dinamica', 'Reporte')"
                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-md text-white text-xs font-semibold bg-red-800 hover:bg-red-900 border-0">
                <i class="fa-solid fa-file-pdf"></i> Imprimir PDF
            </button>
        </div>
    </div>

    <!-- ═══ BUSCADOR ══════════════════════════════════════════════════════ -->
    <form method="get" action="index.php" class="bg-white border border-slate-200 rounded-xl px-3 py-2 shadow-sm">
        <input type="hidden" name="route" value="tablas-generales">
        <input type="hidden" name="ref" value="<?= htmlspecialchars($ref) ?>">
        <input type="hidden" name="sort" value="<?= htmlspecialchars($ordenCol) ?>">
        <input type="hidden" name="dir" value="<?= htmlspecialchars($ordenDir) ?>">
        <div class="flex flex-wrap items-center gap-2">
            <label for="tg-buscar" class="text-xs font-semibold text-slate-600 whitespace-nowrap">
                <i class="fa-solid fa-magnifying-glass me-1"></i>Buscar
            </label>
            <input type="text" id="tg-buscar" name="buscar"
                   value="<?= htmlspecialchars($busqueda) ?>"
                   placeholder="Código, descripción…"
                   class="flex-grow min-w-[220px] form-control form-control-sm text-sm border-slate-300 rounded-md">
            <button type="submit"
                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-md text-white text-xs font-semibold bg-sky-700 hover:bg-sky-800 border-0">
                <i class="fa-solid fa-search"></i> Buscar
            </button>
            <?php if ($busqueda !== ''): ?>
            <a href="index.php?route=tablas-generales&ref=<?= urlencode($ref) ?>"
               class="text-xs text-slate-500 hover:text-slate-800 no-underline">Limpiar</a>
            <?php endif; ?>
            <span class="text-xs text-slate-500 ms-auto">
                <?= number_format($total, 0, ',', '.') ?> registro<?= $total === 1 ? '' : 's' ?>
                · pág. <?= $pagina ?>/<?= $totalPaginas ?>
            </span>
        </div>
    </form>

    <?php if (!empty($flashOk)): ?>
    <div class="alert alert-success text-sm rounded-lg mb-0 py-2">
        <i class="fa-solid fa-circle-check me-1"></i><?= htmlspecialchars((string) $flashOk) ?>
    </div>
    <?php endif; ?>
    <?php if (!empty($flashError)): ?>
    <div class="alert alert-danger text-sm rounded-lg mb-0 py-2">
        <i class="fa-solid fa-triangle-exclamation me-1"></i><?= htmlspecialchars((string) $flashError) ?>
    </div>
    <?php endif; ?>
    <?php if (!empty($dbError)): ?>
    <div class="alert alert-danger text-sm rounded-lg mb-0">
        <i class="fa-solid fa-triangle-exclamation me-1"></i>
        Error al consultar la tabla <code>tablas</code>.
    </div>
    <?php endif; ?>

    <div id="tg-alert" class="hidden alert text-sm rounded-lg mb-0 py-2"></div>

    <!-- ═══ GRILLA ════════════════════════════════════════════════════════ -->
    <div class="overflow-x-auto bg-white border border-slate-200 rounded-xl shadow-sm">
        <table id="tabla-dinamica" class="w-full text-sm text-left text-gray-600 border-collapse">
            <thead>
                <tr class="bg-slate-800 text-white">
                    <?php foreach ($campos as $col): ?>
                    <th class="p-2 font-semibold whitespace-nowrap" scope="col">
                        <a href="<?= htmlspecialchars($sortLinks[$col] ?? '#') ?>"
                           class="text-white no-underline hover:text-sky-200 inline-flex items-center">
                            <?= htmlspecialchars($labels[$col] ?? strtoupper($col)) ?>
                            <?= $sortIcon($col) ?>
                        </a>
                    </th>
                    <?php endforeach; ?>
                    <?php if ($puedeEditar || $puedeBorrar): ?>
                    <th class="p-2 font-semibold whitespace-nowrap text-center" data-export="skip">Acciones</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($registros)): ?>
                <tr>
                    <td colspan="<?= max(1, $colCount) ?>" class="p-4 text-center text-slate-400">
                        No se encontraron registros<?= $busqueda !== '' ? ' para la búsqueda indicada' : '' ?>.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($registros as $i => $row): ?>
                <tr class="tg-row <?= $i % 2 === 0 ? 'bg-white' : 'bg-slate-50' ?> hover:bg-sky-50 border-b border-slate-100 cursor-pointer"
                    data-id="<?= (int) ($row['id'] ?? 0) ?>">
                    <?php foreach ($campos as $col): ?>
                        <?php if ($esCheck($col)): ?>
                        <td class="p-2 border border-slate-100 text-center">
                            <input type="checkbox" disabled <?= ((int) ($row[$col] ?? 0) === 1) ? 'checked' : '' ?>
                                   class="rounded border-slate-300">
                        </td>
                        <?php elseif ($col === 'taimporte'): ?>
                        <td class="p-2 border border-slate-100 text-right font-mono">
                            <?= number_format((float) ($row[$col] ?? 0), 2, ',', '.') ?>
                        </td>
                        <?php elseif ($col === 'tacodigo'): ?>
                        <td class="p-2 border border-slate-100 font-mono text-xs">
                            <?= htmlspecialchars((string) ($row[$col] ?? '')) ?>
                        </td>
                        <?php else: ?>
                        <td class="p-2 border border-slate-100">
                            <?= htmlspecialchars((string) ($row[$col] ?? '')) ?>
                        </td>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    <?php if ($puedeEditar || $puedeBorrar): ?>
                    <td class="p-2 border border-slate-100 text-center whitespace-nowrap" data-export="skip">
                        <?php if ($puedeEditar): ?>
                        <button type="button" class="tg-row-edit inline-flex items-center justify-center w-7 h-7 rounded bg-blue-600 hover:bg-blue-700 text-white border-0"
                                title="Editar" data-id="<?= (int) $row['id'] ?>">
                            <i class="fa-solid fa-pen text-xs"></i>
                        </button>
                        <?php endif; ?>
                        <?php if ($puedeBorrar): ?>
                        <button type="button" class="tg-row-del inline-flex items-center justify-center w-7 h-7 rounded bg-red-600 hover:bg-red-700 text-white border-0 ms-1"
                                title="Borrar" data-id="<?= (int) $row['id'] ?>">
                            <i class="fa-solid fa-trash text-xs"></i>
                        </button>
                        <?php endif; ?>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- ═══ PAGINACIÓN ════════════════════════════════════════════════════ -->
    <?php
    $ventana = 2;
    $desde   = max(1, $pagina - $ventana);
    $hasta   = min($totalPaginas, $pagina + $ventana);
    ?>
    <div class="flex flex-wrap items-center justify-between gap-2 bg-white border border-slate-200 rounded-xl px-3 py-2 shadow-sm">
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

<!-- ═══ MODAL AGREGAR / EDITAR ════════════════════════════════════════════ -->
<div class="modal fade" id="tg-modal" tabindex="-1" aria-labelledby="tg-modal-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-xl overflow-hidden">
            <form id="tg-form" autocomplete="off">
                <div class="modal-header text-white" style="background:linear-gradient(90deg,#0d47a1,#1976d2);">
                    <h5 class="modal-title text-sm font-semibold" id="tg-modal-title">
                        <i class="fa-solid fa-plus me-1"></i>Nuevo registro
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body space-y-3">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <input type="hidden" name="ref" value="<?= htmlspecialchars($ref) ?>">
                    <input type="hidden" name="id" id="tg-id" value="">
                    <?php if ($tatipo !== null && $tatipo !== ''): ?>
                    <input type="hidden" name="tatipo" value="<?= htmlspecialchars((string) $tatipo) ?>">
                    <?php endif; ?>

                    <?php foreach ($campos as $col):
                        $label = $labels[$col] ?? strtoupper($col);
                    ?>
                        <?php if ($esCheck($col)): ?>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="1"
                                   id="tg-<?= htmlspecialchars($col) ?>" name="<?= htmlspecialchars($col) ?>">
                            <label class="form-check-label text-sm" for="tg-<?= htmlspecialchars($col) ?>">
                                <?= htmlspecialchars($label) ?>
                            </label>
                        </div>
                        <?php elseif ($col === 'taimporte'): ?>
                        <div>
                            <label class="text-xs font-semibold text-slate-600" for="tg-<?= htmlspecialchars($col) ?>">
                                <?= htmlspecialchars($label) ?>
                            </label>
                            <input type="number" step="0.01" min="0"
                                   id="tg-<?= htmlspecialchars($col) ?>" name="<?= htmlspecialchars($col) ?>"
                                   class="form-control form-control-sm">
                        </div>
                        <?php elseif ($col === 'tacodigo'): ?>
                        <div>
                            <label class="text-xs font-semibold text-slate-600" for="tg-<?= htmlspecialchars($col) ?>">
                                <?= htmlspecialchars($label) ?> <span class="text-red-500">*</span>
                            </label>
                            <input type="text" maxlength="38"
                                   id="tg-<?= htmlspecialchars($col) ?>" name="<?= htmlspecialchars($col) ?>"
                                   class="form-control form-control-sm font-mono" required>
                        </div>
                        <?php elseif ($col === 'taprovin'): ?>
                        <div>
                            <label class="text-xs font-semibold text-slate-600" for="tg-<?= htmlspecialchars($col) ?>">
                                <?= htmlspecialchars($label) ?>
                            </label>
                            <input type="text" maxlength="20"
                                   id="tg-<?= htmlspecialchars($col) ?>" name="<?= htmlspecialchars($col) ?>"
                                   class="form-control form-control-sm">
                        </div>
                        <?php elseif ($col === 'tagrupo'): ?>
                        <div>
                            <label class="text-xs font-semibold text-slate-600" for="tg-<?= htmlspecialchars($col) ?>">
                                <?= htmlspecialchars($label) ?>
                            </label>
                            <input type="text" maxlength="20"
                                   id="tg-<?= htmlspecialchars($col) ?>" name="<?= htmlspecialchars($col) ?>"
                                   class="form-control form-control-sm">
                        </div>
                        <?php elseif ($col === 'tatipodeb'): ?>
                        <div>
                            <label class="text-xs font-semibold text-slate-600" for="tg-<?= htmlspecialchars($col) ?>">
                                <?= htmlspecialchars($label) ?>
                            </label>
                            <input type="text" maxlength="1"
                                   id="tg-<?= htmlspecialchars($col) ?>" name="<?= htmlspecialchars($col) ?>"
                                   class="form-control form-control-sm font-mono uppercase">
                        </div>
                        <?php else: ?>
                        <div>
                            <label class="text-xs font-semibold text-slate-600" for="tg-<?= htmlspecialchars($col) ?>">
                                <?= htmlspecialchars($label) ?>
                                <?= $col === 'tadescrip' ? '<span class="text-red-500">*</span>' : '' ?>
                            </label>
                            <input type="text" maxlength="80"
                                   id="tg-<?= htmlspecialchars($col) ?>" name="<?= htmlspecialchars($col) ?>"
                                   class="form-control form-control-sm"
                                   <?= $col === 'tadescrip' ? 'required' : '' ?>>
                        </div>
                        <?php endif; ?>
                    <?php endforeach; ?>

                    <div id="tg-form-error" class="hidden text-xs text-red-600 bg-red-50 border border-red-200 rounded px-2 py-1"></div>
                </div>
                <div class="modal-footer bg-slate-50">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" id="tg-btn-guardar"
                            class="btn btn-sm text-white" style="background:#1565c0;">
                        <i class="fa-solid fa-floppy-disk me-1"></i>Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    #tabla-dinamica tbody tr.tg-row.selected { background: #dbeafe !important; }
</style>

<script>
(function () {
    var app  = document.getElementById('tg-app');
    if (!app) return;

    var ref  = app.getAttribute('data-ref') || '';
    var csrf = app.getAttribute('data-csrf') || '';
    var selectedId = 0;
    var modalEl = document.getElementById('tg-modal');
    var modal   = null;
    var form    = document.getElementById('tg-form');
    var campos  = <?= json_encode(array_values($campos), JSON_UNESCAPED_UNICODE) ?>;
    var checks  = { vista: true, taselecc: true, isonco: true, isvih: true };

    function apiUrl(action, extra) {
        var q = 'index.php?route=tablas-generales&ref=' + encodeURIComponent(ref) + '&action=' + encodeURIComponent(action);
        if (extra) q += extra;
        return q;
    }

    function showAlert(msg, ok) {
        var el = document.getElementById('tg-alert');
        if (!el) return;
        el.className = 'alert text-sm rounded-lg mb-0 py-2 ' + (ok ? 'alert-success' : 'alert-danger');
        el.innerHTML = '<i class="fa-solid ' + (ok ? 'fa-circle-check' : 'fa-triangle-exclamation') + ' me-1"></i>' + msg;
        el.classList.remove('hidden');
    }

    function getModal() {
        if (!modal && typeof bootstrap !== 'undefined') {
            modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        }
        return modal;
    }

    function selectRow(tr) {
        document.querySelectorAll('#tabla-dinamica tbody tr.tg-row').forEach(function (r) {
            r.classList.remove('selected');
        });
        if (!tr) { selectedId = 0; return; }
        tr.classList.add('selected');
        selectedId = parseInt(tr.getAttribute('data-id'), 10) || 0;
    }

    document.querySelectorAll('#tabla-dinamica tbody tr.tg-row').forEach(function (tr) {
        tr.addEventListener('click', function (e) {
            if (e.target.closest('button')) return;
            selectRow(tr);
        });
        tr.addEventListener('dblclick', function (e) {
            if (e.target.closest('button')) return;
            var btnEditar = document.getElementById('tg-btn-editar');
            if (btnEditar) abrirEdicion(parseInt(tr.getAttribute('data-id'), 10) || 0);
        });
    });

    function resetForm() {
        form.reset();
        document.getElementById('tg-id').value = '';
        var err = document.getElementById('tg-form-error');
        err.classList.add('hidden');
        err.textContent = '';
        campos.forEach(function (c) {
            var el = document.getElementById('tg-' + c);
            if (!el) return;
            if (checks[c]) el.checked = false;
            else el.value = '';
        });
    }

    function abrirAlta() {
        resetForm();
        document.getElementById('tg-modal-title').innerHTML = '<i class="fa-solid fa-plus me-1"></i>Nuevo registro';
        getModal() && getModal().show();
        var first = form.querySelector('input:not([type=hidden]):not([type=checkbox])');
        if (first) setTimeout(function () { first.focus(); }, 250);
    }

    function abrirEdicion(id) {
        if (!id) {
            showAlert('Seleccione un registro para editar.', false);
            return;
        }
        fetch(apiUrl('obtener', '&id=' + encodeURIComponent(id)), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.ok || !data.registro) {
                    showAlert(data.error || 'No se pudo cargar el registro.', false);
                    return;
                }
                resetForm();
                document.getElementById('tg-id').value = data.registro.id || '';
                campos.forEach(function (c) {
                    var el = document.getElementById('tg-' + c);
                    if (!el) return;
                    var val = data.registro[c];
                    if (checks[c]) el.checked = parseInt(val, 10) === 1;
                    else el.value = val == null ? '' : val;
                });
                document.getElementById('tg-modal-title').innerHTML = '<i class="fa-solid fa-pen-to-square me-1"></i>Editar registro';
                getModal() && getModal().show();
            })
            .catch(function () { showAlert('Error de comunicación al cargar el registro.', false); });
    }

    function borrarId(id) {
        if (!id) {
            showAlert('Seleccione un registro para borrar.', false);
            return;
        }
        if (!confirm('¿Eliminar el registro #' + id + '? Esta acción no se puede deshacer.')) return;

        var fd = new FormData();
        fd.append('csrf_token', csrf);
        fd.append('ref', ref);
        fd.append('id', String(id));

        fetch(apiUrl('eliminar'), { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.ok) {
                    showAlert(data.error || 'No se pudo eliminar.', false);
                    return;
                }
                window.location.reload();
            })
            .catch(function () { showAlert('Error de comunicación al eliminar.', false); });
    }

    var btnAgregar = document.getElementById('tg-btn-agregar');
    if (btnAgregar) btnAgregar.addEventListener('click', abrirAlta);

    var btnEditar = document.getElementById('tg-btn-editar');
    if (btnEditar) btnEditar.addEventListener('click', function () { abrirEdicion(selectedId); });

    var btnBorrar = document.getElementById('tg-btn-borrar');
    if (btnBorrar) btnBorrar.addEventListener('click', function () { borrarId(selectedId); });

    document.querySelectorAll('.tg-row-edit').forEach(function (b) {
        b.addEventListener('click', function (e) {
            e.stopPropagation();
            abrirEdicion(parseInt(b.getAttribute('data-id'), 10) || 0);
        });
    });
    document.querySelectorAll('.tg-row-del').forEach(function (b) {
        b.addEventListener('click', function (e) {
            e.stopPropagation();
            borrarId(parseInt(b.getAttribute('data-id'), 10) || 0);
        });
    });

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        var err = document.getElementById('tg-form-error');
        err.classList.add('hidden');

        var fd = new FormData(form);
        campos.forEach(function (c) {
            if (!checks[c]) return;
            var el = document.getElementById('tg-' + c);
            fd.set(c, el && el.checked ? '1' : '0');
        });

        var btn = document.getElementById('tg-btn-guardar');
        btn.disabled = true;

        fetch(apiUrl('guardar'), { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json().then(function (j) { return { okHttp: r.ok, j: j }; }); })
            .then(function (res) {
                btn.disabled = false;
                if (!res.j.ok) {
                    err.textContent = res.j.error || 'No se pudo guardar.';
                    err.classList.remove('hidden');
                    return;
                }
                getModal() && getModal().hide();
                window.location.reload();
            })
            .catch(function () {
                btn.disabled = false;
                err.textContent = 'Error de comunicación al guardar.';
                err.classList.remove('hidden');
            });
    });
})();
</script>

<?php require_once __DIR__ . '/layouts/footer.php'; ?>
