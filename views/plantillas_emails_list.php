<?php
/**
 * views/plantillas_emails_list.php
 * ABM Cabecera Mails — t_plantillas_emails
 */

$pageTitle  = $pageTitle  ?? 'COMEDICA — Cabecera Mails';
$breadcrumb = $breadcrumb ?? [['label' => 'Cabecera Mails']];

require_once __DIR__ . '/layouts/header.php';

$registros    = $registros ?? [];
$total        = (int) ($total ?? 0);
$totalPaginas = max(1, (int) ($totalPaginas ?? 1));
$pagina       = max(1, (int) ($pagina ?? 1));
$limite       = (int) ($limite ?? 50);
$busqueda     = (string) ($busqueda ?? '');
$puedeAgregar = (bool) ($puedeAgregar ?? false);
$puedeEditar  = (bool) ($puedeEditar ?? false);
$puedeBorrar  = (bool) ($puedeBorrar ?? false);
$csrfToken    = (string) ($csrfToken ?? '');
$dbError      = $dbError ?? null;

$pagUrl = static function (int $p) use ($busqueda): string {
    $params = ['route' => 'plantillas-emails', 'page' => $p];
    if ($busqueda !== '') {
        $params['buscar'] = $busqueda;
    }
    return 'index.php?' . http_build_query($params);
};
?>

<script>
window.tailwind && (window.tailwind.config = { corePlugins: { preflight: false } });
</script>
<script src="https://cdn.tailwindcss.com?plugins=forms"></script>
<script>tailwind.config = { corePlugins: { preflight: false } };</script>

<div id="pe-app" class="space-y-3" data-csrf="<?= htmlspecialchars($csrfToken) ?>">
    <div class="flex flex-wrap items-center gap-2 bg-white border border-slate-200 rounded-xl px-3 py-2 shadow-sm">
        <div class="w-8 h-8 rounded-lg flex items-center justify-center"
             style="background:linear-gradient(135deg,#0d47a1,#1976d2);">
            <i class="fa-solid fa-envelope-open-text text-white text-sm"></i>
        </div>
        <span class="font-bold text-slate-800 text-sm">Cabecera Mails</span>

        <div class="flex flex-wrap items-center gap-1">
            <?php if ($puedeAgregar): ?>
            <button type="button" id="pe-btn-agregar"
                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-md text-white text-xs font-semibold bg-emerald-600 hover:bg-emerald-700 border-0">
                <i class="fa-solid fa-plus"></i> Agregar
            </button>
            <?php endif; ?>
        </div>

        <div class="flex flex-wrap items-center gap-1 ms-auto">
            <button type="button" onclick="exportarTablaAExcel('tabla-dinamica', 'cabecera_mails')"
                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-md text-white text-xs font-semibold bg-green-800 hover:bg-green-900 border-0">
                <i class="fa-solid fa-file-excel"></i> Excel
            </button>
            <button type="button" onclick="exportarTablaAPDF('tabla-dinamica', 'cabecera_mails')"
                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-md text-white text-xs font-semibold bg-red-800 hover:bg-red-900 border-0">
                <i class="fa-solid fa-file-pdf"></i> PDF
            </button>
        </div>
    </div>

    <form method="get" action="index.php" class="bg-white border border-slate-200 rounded-xl px-3 py-2 shadow-sm">
        <input type="hidden" name="route" value="plantillas-emails">
        <div class="flex flex-wrap items-center gap-2">
            <label class="text-xs font-semibold text-slate-600" for="pe-buscar">Buscar</label>
            <input type="text" id="pe-buscar" name="buscar" value="<?= htmlspecialchars($busqueda) ?>"
                   class="flex-grow min-w-[220px] form-control form-control-sm">
            <button type="submit" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-md text-white text-xs font-semibold bg-sky-700 border-0">
                <i class="fa-solid fa-search"></i> Buscar
            </button>
            <span class="text-xs text-slate-500 ms-auto"><?= number_format($total, 0, ',', '.') ?> registro(s)</span>
        </div>
    </form>

    <?php if (!empty($dbError)): ?>
    <div class="alert alert-danger text-sm rounded-lg py-2">Error al consultar <code>t_plantillas_emails</code>.</div>
    <?php endif; ?>
    <div id="pe-alert" class="hidden alert text-sm rounded-lg mb-0 py-2"></div>

    <div class="overflow-x-auto bg-white border border-slate-200 rounded-xl shadow-sm">
        <table id="tabla-dinamica" class="w-full text-sm text-left text-gray-600 border-collapse">
            <thead>
                <tr class="bg-slate-800 text-white">
                    <th class="p-2">Código</th>
                    <th class="p-2">Nombre / uso</th>
                    <th class="p-2">Asunto</th>
                    <th class="p-2">Destinatarios</th>
                    <th class="p-2 text-center">Activo</th>
                    <?php if ($puedeEditar || $puedeBorrar): ?>
                    <th class="p-2 text-center" data-export="skip">Acciones</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($registros)): ?>
                <tr><td colspan="6" class="p-4 text-center text-slate-400">No hay plantillas.</td></tr>
            <?php else: ?>
                <?php foreach ($registros as $i => $row): ?>
                <tr class="<?= $i % 2 === 0 ? 'bg-white' : 'bg-slate-50' ?> border-b border-slate-100">
                    <td class="p-2 font-mono text-xs"><?= htmlspecialchars((string) ($row['codigo'] ?? '')) ?></td>
                    <td class="p-2"><?= htmlspecialchars((string) ($row['nombre_uso'] ?? '')) ?></td>
                    <td class="p-2"><?= htmlspecialchars((string) ($row['asunto'] ?? '')) ?></td>
                    <td class="p-2 text-xs"><?= htmlspecialchars((string) ($row['destinatarios'] ?? '')) ?></td>
                    <td class="p-2 text-center">
                        <input type="checkbox" disabled <?= ((int) ($row['activo'] ?? 0) === 1) ? 'checked' : '' ?>>
                    </td>
                    <?php if ($puedeEditar || $puedeBorrar): ?>
                    <td class="p-2 text-center whitespace-nowrap" data-export="skip">
                        <?php if ($puedeEditar): ?>
                        <button type="button" class="pe-edit inline-flex items-center justify-center w-7 h-7 rounded bg-blue-600 text-white border-0"
                                data-id="<?= (int) $row['id'] ?>" title="Editar"><i class="fa-solid fa-pen text-xs"></i></button>
                        <?php endif; ?>
                        <?php if ($puedeBorrar): ?>
                        <button type="button" class="pe-del inline-flex items-center justify-center w-7 h-7 rounded bg-red-600 text-white border-0 ms-1"
                                data-id="<?= (int) $row['id'] ?>" title="Borrar"><i class="fa-solid fa-trash text-xs"></i></button>
                        <?php endif; ?>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="flex items-center justify-between bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs text-slate-500">
        <span>Pág. <?= $pagina ?> / <?= $totalPaginas ?></span>
        <div class="flex gap-1">
            <a href="<?= $pagina > 1 ? htmlspecialchars($pagUrl($pagina - 1)) : '#' ?>"
               class="px-2 py-1 rounded border <?= $pagina <= 1 ? 'pointer-events-none opacity-40' : 'border-slate-300 text-slate-700 no-underline' ?>">Anterior</a>
            <a href="<?= $pagina < $totalPaginas ? htmlspecialchars($pagUrl($pagina + 1)) : '#' ?>"
               class="px-2 py-1 rounded border <?= $pagina >= $totalPaginas ? 'pointer-events-none opacity-40' : 'border-slate-300 text-slate-700 no-underline' ?>">Siguiente</a>
        </div>
    </div>
</div>

<div class="modal fade" id="pe-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-xl">
            <form id="pe-form">
                <div class="modal-header text-white" style="background:linear-gradient(90deg,#0d47a1,#1976d2);">
                    <h5 class="modal-title text-sm font-semibold" id="pe-modal-title">Nueva plantilla</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body space-y-2">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <input type="hidden" name="id" id="pe-id" value="">
                    <div class="row g-2">
                        <div class="col-md-4">
                            <label class="text-xs font-semibold">Código *</label>
                            <input type="text" name="codigo" id="pe-codigo" maxlength="50" class="form-control form-control-sm font-mono" required>
                        </div>
                        <div class="col-md-8">
                            <label class="text-xs font-semibold">Nombre / uso *</label>
                            <input type="text" name="nombre_uso" id="pe-nombre_uso" maxlength="100" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-12">
                            <label class="text-xs font-semibold">Asunto *</label>
                            <input type="text" name="asunto" id="pe-asunto" maxlength="255" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-12">
                            <label class="text-xs font-semibold">Cuerpo *</label>
                            <textarea name="cuerpo" id="pe-cuerpo" rows="6" class="form-control form-control-sm font-mono" required></textarea>
                        </div>
                        <div class="col-md-12">
                            <label class="text-xs font-semibold">Destinatarios *</label>
                            <input type="text" name="destinatarios" id="pe-destinatarios" maxlength="255" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-6">
                            <label class="text-xs font-semibold">CC</label>
                            <input type="text" name="cc" id="pe-cc" maxlength="255" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-6">
                            <label class="text-xs font-semibold">CCO</label>
                            <input type="text" name="cco" id="pe-cco" maxlength="255" class="form-control form-control-sm">
                        </div>
                        <div class="col-12 form-check ms-2">
                            <input class="form-check-input" type="checkbox" name="activo" id="pe-activo" value="1" checked>
                            <label class="form-check-label text-sm" for="pe-activo">Activo</label>
                        </div>
                    </div>
                    <div id="pe-form-error" class="hidden text-xs text-red-600 bg-red-50 border border-red-200 rounded px-2 py-1"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sm text-white" style="background:#1565c0;">
                        <i class="fa-solid fa-floppy-disk me-1"></i>Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function () {
    var app = document.getElementById('pe-app');
    if (!app) return;
    var csrf = app.getAttribute('data-csrf') || '';
    var modalEl = document.getElementById('pe-modal');
    var modal = null;
    var form = document.getElementById('pe-form');
    var campos = ['codigo','nombre_uso','asunto','cuerpo','destinatarios','cc','cco'];

    function api(action, extra) {
        return 'index.php?route=plantillas-emails&action=' + encodeURIComponent(action) + (extra || '');
    }
    function getModal() {
        if (!modal && typeof bootstrap !== 'undefined') modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        return modal;
    }
    function showAlert(msg, ok) {
        var el = document.getElementById('pe-alert');
        el.className = 'alert text-sm rounded-lg mb-0 py-2 ' + (ok ? 'alert-success' : 'alert-danger');
        el.innerHTML = msg;
        el.classList.remove('hidden');
    }
    function resetForm() {
        form.reset();
        document.getElementById('pe-id').value = '';
        document.getElementById('pe-activo').checked = true;
        document.getElementById('pe-form-error').classList.add('hidden');
    }
    function abrirAlta() {
        resetForm();
        document.getElementById('pe-modal-title').textContent = 'Nueva plantilla';
        getModal() && getModal().show();
    }
    function abrirEdicion(id) {
        fetch(api('editar', '&id=' + encodeURIComponent(id)))
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.ok) { showAlert(data.error || 'No se pudo cargar.', false); return; }
                resetForm();
                document.getElementById('pe-id').value = data.registro.id || '';
                campos.forEach(function (c) {
                    var el = document.getElementById('pe-' + c);
                    if (el) el.value = data.registro[c] == null ? '' : data.registro[c];
                });
                document.getElementById('pe-activo').checked = parseInt(data.registro.activo, 10) === 1;
                document.getElementById('pe-modal-title').textContent = 'Editar plantilla';
                getModal() && getModal().show();
            });
    }
    var btnAdd = document.getElementById('pe-btn-agregar');
    if (btnAdd) btnAdd.addEventListener('click', abrirAlta);
    document.querySelectorAll('.pe-edit').forEach(function (b) {
        b.addEventListener('click', function () { abrirEdicion(b.getAttribute('data-id')); });
    });
    document.querySelectorAll('.pe-del').forEach(function (b) {
        b.addEventListener('click', function () {
            if (!confirm('¿Eliminar esta plantilla?')) return;
            var fd = new FormData();
            fd.append('csrf_token', csrf);
            fd.append('id', b.getAttribute('data-id'));
            fetch(api('eliminar'), { method: 'POST', body: fd })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (!data.ok) { showAlert(data.error || 'No se pudo eliminar.', false); return; }
                    window.location.reload();
                });
        });
    });
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        var err = document.getElementById('pe-form-error');
        var fd = new FormData(form);
        fd.set('activo', document.getElementById('pe-activo').checked ? '1' : '0');
        fetch(api('guardar'), { method: 'POST', body: fd })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.ok) { err.textContent = data.error || 'Error al guardar.'; err.classList.remove('hidden'); return; }
                getModal() && getModal().hide();
                window.location.reload();
            });
    });
})();
</script>

<?php require_once __DIR__ . '/layouts/footer.php'; ?>
