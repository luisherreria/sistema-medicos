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
$puedeImportar   = (bool) ($puedeImportar ?? false);
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
            <button type="button" id="nn-btn-act-desc"
                    title="Actualiza la Descripcion - Grupo en Sucursales y Prestadores"
                    onclick="actualizarDescripcionGrupo()"
                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-md text-white text-xs font-semibold bg-slate-500 hover:bg-slate-600 border-0">
                <i class="fa-solid fa-arrows-rotate me-1"></i> Actualizar Descripción
            </button>
            <?php endif; ?>

            <?php if ($puedeImportar): ?>
            <button type="button" id="nn-btn-importar"
                    title="Importar Plantilla de excel practicas y prestador"
                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-md text-white text-xs font-semibold bg-teal-700 hover:bg-teal-800 border-0">
                <i class="fa-solid fa-file-import"></i> Importar Plantilla
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
                <tr class="nn-fila <?= $i % 2 === 0 ? 'bg-white' : 'bg-slate-50' ?> hover:bg-sky-50 border-b border-slate-100"
                    data-codigo="<?= htmlspecialchars(trim((string) ($row['tacodigo'] ?? ''))) ?>"
                    style="cursor:pointer;">
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
<?php if ($puedeEditar || $puedeAgregar): ?>
<?php require __DIR__ . '/nomencla/_modal_editar_nomenclador.php'; ?>
<?php endif; ?>
<?php if ($puedeImportar): ?>
<?php require __DIR__ . '/nomencla/_modal_importar.php'; ?>
<?php endif; ?>

<style>
#nn-tabla tr.nn-fila-sel { background: #dbeafe !important; }
</style>

<script>
window.CSRF_TOKEN = <?= json_encode($_SESSION['csrf_token'] ?? '') ?>;
function actualizarDescripcionGrupo() {
    if (!confirm('¿Desea actualizar las descripciones y grupos del Nomenclador en Sucursales (praespsu) y Prestadores (pracespe)?')) {
        return;
    }

    var btn = document.getElementById('nn-btn-act-desc');
    if (!btn) return;
    var oldHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Actualizando...';

    fetch('index.php?route=nomenclador&action=actualizar_descripciones', {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: 'csrf_token=' + encodeURIComponent(window.CSRF_TOKEN || '')
    })
    .then(function (r) { return r.json(); })
    .then(function (res) {
        btn.disabled = false;
        btn.innerHTML = oldHtml;
        if (res && res.ok) {
            alert('¡Proceso finalizado!\n\n' + res.msg);
        } else {
            alert('Error: ' + ((res && (res.error || res.msg)) || 'No se pudo completar la actualización.'));
        }
    })
    .catch(function () {
        btn.disabled = false;
        btn.innerHTML = oldHtml;
        alert('Error de comunicación con el servidor.');
    });
}
</script>

<script>
(function () {
    window.nnRecargarGrilla = function () {
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
    };

    var codigoSeleccionado = '';
    var btnImportar = document.getElementById('nn-btn-importar');
    if (btnImportar) {
        btnImportar.addEventListener('click', function () {
            var el = document.getElementById('modalImportarNomenclador');
            if (el && window.bootstrap) {
                window.bootstrap.Modal.getOrCreateInstance(el).show();
            }
        });
    }

    var codigoSeleccionado = '';
    var catalogoCache = null;
    var formEdit = document.getElementById('form-editar-nomenclador');

    function marcarFila(tr) {
        document.querySelectorAll('#nn-tabla tr.nn-fila-sel').forEach(function (row) {
            row.classList.remove('nn-fila-sel');
        });
        if (tr) tr.classList.add('nn-fila-sel');
        codigoSeleccionado = tr ? (tr.getAttribute('data-codigo') || '') : '';
    }

    var tabla = document.getElementById('nn-tabla');
    if (tabla) {
        tabla.addEventListener('click', function (ev) {
            var tr = ev.target.closest ? ev.target.closest('tr.nn-fila') : null;
            if (tr) marcarFila(tr);
        });
        tabla.addEventListener('dblclick', function (ev) {
            var tr = ev.target.closest ? ev.target.closest('tr.nn-fila') : null;
            if (!tr) return;
            marcarFila(tr);
            abrirEdicion(codigoSeleccionado);
        });
    }

    function aviso(texto) {
        if (window.Swal) {
            window.Swal.fire({ icon: 'info', text: texto, confirmButtonColor: '#0d47a1' });
        } else {
            alert(texto);
        }
    }

    function errorEdit(texto) {
        var err = document.getElementById('nn-edit-error');
        if (!err) return;
        err.textContent = texto || '';
        err.style.display = texto ? 'block' : 'none';
    }

    function esMarcado(valor) {
        var t = String(valor == null ? '' : valor).trim().toUpperCase();
        return t === '1' || t === 'S' || t === 'SI' || t === 'Y' || t === 'TRUE';
    }

    function cargarCatalogo() {
        if (catalogoCache) return Promise.resolve(catalogoCache);
        return fetch('index.php?route=nomenclador&action=catalogo_nomenclador', {
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function (r) { return r.json(); })
        .then(function (json) {
            if (!json || !json.ok) throw new Error((json && json.msg) || 'No se pudieron cargar los catálogos.');
            catalogoCache = json.data || {};
            return catalogoCache;
        });
    }

    function llenarCombo(select, opciones, valor) {
        var usaDescripcion = select.getAttribute('data-valor') === 'descripcion';
        var html = '<option value=""></option>';
        var encontrado = false;
        (opciones || []).forEach(function (op) {
            var codigo = op.codigo == null ? '' : String(op.codigo);
            var descripcion = op.descripcion == null ? '' : String(op.descripcion);
            var value = usaDescripcion ? (descripcion || codigo) : codigo;
            var label = descripcion && descripcion !== codigo ? (codigo + ' — ' + descripcion) : (descripcion || codigo);
            if (value === String(valor || '')) encontrado = true;
            html += '<option value="' + value.replace(/"/g, '&quot;') + '">' + label.replace(/</g, '&lt;') + '</option>';
        });
        if (valor && !encontrado) {
            html += '<option value="' + String(valor).replace(/"/g, '&quot;') + '">' + String(valor).replace(/</g, '&lt;') + '</option>';
        }
        select.innerHTML = html;
        select.value = valor || '';
    }

    function aplicarCatalogo(catalogo, datos) {
        datos = datos || {};
        document.querySelectorAll('#form-editar-nomenclador select.nn-combo').forEach(function (select) {
            var clave = select.getAttribute('data-catalogo');
            llenarCombo(select, catalogo[clave] || [], datos[select.name] || '');
        });
    }

    function abrirModal() {
        var modalEl = document.getElementById('modalEditarNomenclador');
        if (modalEl && window.bootstrap) {
            window.bootstrap.Modal.getOrCreateInstance(modalEl).show();
        }
    }

    function abrirEdicion(codigo) {
        if (!formEdit) return;
        if (!codigo) {
            aviso('Seleccione una práctica de la grilla.');
            return;
        }
        errorEdit('');
        document.getElementById('nn-edit-accion').value = 'editar';
        document.getElementById('nn-edit-modo').value = 'edicion';
        var titulo = document.getElementById('modalEditarNomencladorLabel');
        if (titulo) titulo.innerHTML = '<i class="fa-solid fa-book-medical me-2"></i>Actualización Datos Nomenclador';
        var inputCodigo = document.getElementById('nn-edit-tacodigo');
        inputCodigo.readOnly = true;
        inputCodigo.classList.remove('is-invalid', 'is-valid', 'border-danger');
        habilitarGuardar(true);

        Promise.all([
            cargarCatalogo(),
            fetch('index.php?route=nomenclador&action=obtener_nomenclador&tacodigo=' + encodeURIComponent(codigo), {
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            }).then(function (r) { return r.json(); })
        ]).then(function (res) {
            var datos = res[1];
            if (!datos || !datos.ok) throw new Error((datos && datos.msg) || 'No se encontró la práctica.');
            var row = datos.data || {};
            formEdit.reset();
            document.getElementById('nn-edit-accion').value = 'editar';
            document.getElementById('nn-edit-modo').value = 'edicion';
            inputCodigo.readOnly = true;
            inputCodigo.value = row.tacodigo || '';
            document.getElementById('nn-edit-tadescrip').value = row.tadescrip || '';
            document.getElementById('nn-edit-taimporte').value = row.taimporte != null ? Number(row.taimporte).toFixed(2) : '0.00';
            document.getElementById('nn-edit-capitulo').value = row.capitulo || '';
            document.getElementById('nn-edit-tvalnbu').value = row.tvalnbu != null ? Number(row.tvalnbu).toFixed(2) : '0.00';
            aplicarCatalogo(res[0], row);
            document.getElementById('nn-edit-vermodulo').checked = esMarcado(row.vermodulo);
            document.getElementById('nn-edit-desdobla').checked = esMarcado(row.desdobla);
            document.getElementById('nn-edit-isnbu').checked = esMarcado(row.isnbu);
            abrirModal();
        }).catch(function (err) {
            aviso(err && err.message ? err.message : 'No se pudo abrir la práctica.');
        });
    }

    var codigoDuplicado = false;

    function habilitarGuardar(habilitar) {
        var btn = document.getElementById('btn-guardar-nomenclador');
        if (btn) btn.disabled = !habilitar;
    }

    function marcarCodigoInvalido(codigo) {
        var inputCodigo = document.getElementById('nn-edit-tacodigo');
        codigoDuplicado = true;
        if (inputCodigo) {
            inputCodigo.classList.add('is-invalid', 'border-danger');
            inputCodigo.classList.remove('is-valid');
        }
        habilitarGuardar(false);
        var msg = "El código '" + codigo + "' ya existe en el Nomenclador. Por favor ingrese un código diferente.";
        errorEdit(msg);
        if (window.Swal) {
            window.Swal.fire({ icon: 'warning', title: 'Código existente', text: msg, confirmButtonColor: '#0d47a1' });
        }
    }

    function marcarCodigoValido() {
        var inputCodigo = document.getElementById('nn-edit-tacodigo');
        codigoDuplicado = false;
        if (inputCodigo) {
            inputCodigo.classList.remove('is-invalid', 'border-danger');
            inputCodigo.classList.add('is-valid');
        }
        errorEdit('');
        habilitarGuardar(true);
    }

    function validarCodigoBlur() {
        var accion = document.getElementById('nn-edit-accion');
        var inputCodigo = document.getElementById('nn-edit-tacodigo');
        if (!accion || !inputCodigo || accion.value !== 'nuevo') return;
        var codigo = (inputCodigo.value || '').trim();
        if (!codigo) {
            codigoDuplicado = false;
            inputCodigo.classList.remove('is-invalid', 'is-valid', 'border-danger');
            errorEdit('');
            habilitarGuardar(true);
            return;
        }
        fetch('index.php?route=nomenclador&action=validar_codigo&tacodigo=' + encodeURIComponent(codigo), {
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function (r) { return r.json(); })
        .then(function (json) {
            if (json && json.existe) {
                marcarCodigoInvalido(json.tacodigo || codigo);
            } else {
                marcarCodigoValido();
            }
        })
        .catch(function () {
            errorEdit('No se pudo validar el código.');
        });
    }

    function abrirAlta() {
        if (!formEdit) return;
        errorEdit('');
        codigoDuplicado = false;
        formEdit.reset();
        document.getElementById('nn-edit-accion').value = 'nuevo';
        document.getElementById('nn-edit-modo').value = 'alta';
        var inputCodigo = document.getElementById('nn-edit-tacodigo');
        inputCodigo.readOnly = false;
        inputCodigo.disabled = false;
        inputCodigo.value = '';
        inputCodigo.classList.remove('is-invalid', 'is-valid', 'border-danger');
        habilitarGuardar(true);
        var titulo = document.getElementById('modalEditarNomencladorLabel');
        if (titulo) titulo.innerHTML = '<i class="fa-solid fa-plus-circle me-2"></i>Nuevo Registro de Nomenclador';
        cargarCatalogo().then(function (catalogo) {
            aplicarCatalogo(catalogo, {});
            document.getElementById('nn-edit-accion').value = 'nuevo';
            document.getElementById('nn-edit-modo').value = 'alta';
            document.getElementById('nn-edit-taimporte').value = '0.00';
            document.getElementById('nn-edit-tvalnbu').value = '0.00';
            abrirModal();
            inputCodigo.focus();
        }).catch(function (err) {
            aviso(err && err.message ? err.message : 'No se pudieron cargar los catálogos.');
        });
    }

    var btnEditar = document.getElementById('nn-btn-editar');
    if (btnEditar) btnEditar.addEventListener('click', function () { abrirEdicion(codigoSeleccionado); });
    var btnAgregar = document.getElementById('nn-btn-agregar');
    if (btnAgregar) btnAgregar.addEventListener('click', abrirAlta);

    var inputCodigoLive = document.getElementById('nn-edit-tacodigo');
    var inputNombreLive = document.getElementById('nn-edit-tadescrip');
    function aMayusculas() {
        var start = this.selectionStart;
        var end = this.selectionEnd;
        this.value = (this.value || '').toUpperCase();
        if (start != null && this.setSelectionRange) this.setSelectionRange(start, end);
    }
    if (inputCodigoLive) {
        inputCodigoLive.addEventListener('input', aMayusculas);
        inputCodigoLive.addEventListener('keyup', aMayusculas);
        inputCodigoLive.addEventListener('input', function () {
            if ((document.getElementById('nn-edit-accion') || {}).value !== 'nuevo') return;
            codigoDuplicado = false;
            inputCodigoLive.classList.remove('is-invalid', 'is-valid', 'border-danger');
            errorEdit('');
            habilitarGuardar(true);
        });
        inputCodigoLive.addEventListener('blur', validarCodigoBlur);
    }
    if (inputNombreLive) {
        inputNombreLive.addEventListener('input', aMayusculas);
        inputNombreLive.addEventListener('keyup', aMayusculas);
    }

    function validarAntesDeGuardar(codigo) {
        var nombre = (document.getElementById('nn-edit-tadescrip').value || '').trim();
        var grupocar = (document.getElementById('nn-edit-grupocar').value || '').trim();
        var titulocar = (document.getElementById('nn-edit-gruposup').value || '').trim();
        var tipo = (document.getElementById('nn-edit-tipo650').value || '').trim();

        if (!nombre) {
            return 'Se detecto falta de nombre en el codigo ' + codigo + '\nDebe revisar la misma';
        }
        if (grupocar && !titulocar) {
            return 'Se detecto una Practica con Grupo Cartilla y no tiene Titulo Cartilla ' + codigo + '\nDebe revisar la misma';
        }
        if (!codigo) {
            return 'Se detecto falta de codigo \nDebe revisar la misma';
        }
        if (!tipo) {
            return 'Se detecto falta de Tipo Normativa 650  \nDebe revisar la misma';
        }
        return '';
    }

    if (formEdit) {
        formEdit.addEventListener('submit', function (ev) {
            ev.preventDefault();
            errorEdit('');
            var codigo = (document.getElementById('nn-edit-tacodigo').value || '').trim().toUpperCase();
            var msgVal = validarAntesDeGuardar(codigo);
            if (msgVal) {
                errorEdit(msgVal);
                if (window.Swal) {
                    window.Swal.fire({
                        icon: 'warning',
                        title: 'Debe revisar la misma',
                        html: msgVal.replace(/\n/g, '<br>'),
                        confirmButtonColor: '#0d47a1'
                    });
                } else {
                    alert(msgVal);
                }
                return;
            }
            if (codigoDuplicado || document.getElementById('nn-edit-tacodigo').classList.contains('is-invalid')) {
                marcarCodigoInvalido(codigo);
                return;
            }
            var submit = document.getElementById('btn-guardar-nomenclador');
            if (submit) submit.disabled = true;
            var datos = new FormData(formEdit);
            if (!document.getElementById('nn-edit-vermodulo').checked) datos.set('vermodulo', '0');
            if (!document.getElementById('nn-edit-desdobla').checked) datos.set('desdobla', '0');
            if (!document.getElementById('nn-edit-isnbu').checked) datos.set('isnbu', '0');

            fetch('index.php?route=nomenclador&action=guardar_nomenclador', {
                method: 'POST',
                body: datos,
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(function (r) { return r.json(); })
            .then(function (json) {
                if (!json || !json.ok) throw new Error((json && json.msg) || 'No se pudo guardar.');
                var modalEl = document.getElementById('modalEditarNomenclador');
                if (modalEl && window.bootstrap) {
                    var inst = window.bootstrap.Modal.getInstance(modalEl);
                    if (inst) inst.hide();
                }
                return window.nnRecargarGrilla().then(function () {
                    codigoSeleccionado = '';
                    if (window.Swal) {
                        window.Swal.fire({ icon: 'success', title: 'Listo', text: json.msg, confirmButtonColor: '#0d47a1' });
                    }
                });
            })
            .catch(function (err) {
                errorEdit(err && err.message ? err.message : 'No se pudo guardar.');
            })
            .finally(function () {
                if (submit && !codigoDuplicado) submit.disabled = false;
            });
        });
    }

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
                    return window.nnRecargarGrilla().then(function () {
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
