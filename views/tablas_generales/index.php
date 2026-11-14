<?php
/**
 * views/tablas_generales/index.php
 * ABM dinámico de la tabla maestra `tablas` (o satélites).
 * Dialecto PHP 5.6. UI Bootstrap 5 (mismo patrón que Cabecera Mails).
 */

$pageTitle   = isset($pageTitle) ? $pageTitle : 'COMEDICA — Tablas';
$breadcrumb  = isset($breadcrumb) ? $breadcrumb : array(array('label' => 'Tablas'));
$ref         = isset($ref) ? strtoupper((string) $ref) : '';
$cfg         = isset($cfg) ? $cfg : array();
$tatipo      = array_key_exists('tatipo', $cfg) ? $cfg['tatipo'] : (isset($tatipo) ? $tatipo : null);
$titulo      = isset($titulo) ? (string) $titulo : 'Tablas';
$campos      = isset($campos) ? $campos : array();
$labels      = isset($labels) ? $labels : array();
$registros   = isset($registros) ? $registros : array();
$total       = isset($total) ? (int) $total : 0;
$totalPaginas = isset($totalPaginas) ? max(1, (int) $totalPaginas) : 1;
$pagina      = isset($pagina) ? max(1, (int) $pagina) : 1;
$busqueda    = isset($busqueda) ? (string) $busqueda : '';
$ordenCol    = isset($ordenCol) ? (string) $ordenCol : 'tacodigo';
$ordenDir    = isset($ordenDir) ? (string) $ordenDir : 'ASC';
$sortLinks   = isset($sortLinks) ? $sortLinks : array();
$queryBase   = isset($queryBase) ? $queryBase : array();
$puedeAgregar = !empty($puedeAgregar);
$puedeEditar  = !empty($puedeEditar);
$puedeBorrar  = !empty($puedeBorrar);
$csrfToken   = isset($csrfToken) ? (string) $csrfToken : '';
$dbError     = isset($dbError) ? $dbError : null;
$flashOk     = isset($flashOk) ? $flashOk : null;
$flashError  = isset($flashError) ? $flashError : null;

$checks = array('vista' => true, 'taselecc' => true, 'isonco' => true, 'isvih' => true);

$pagUrl = function ($p) use ($queryBase) {
    $params = array_merge(array('route' => 'tablas-generales'), $queryBase, array('page' => $p));
    foreach ($params as $k => $v) {
        if ($v === '' || $v === null) {
            unset($params[$k]);
        }
    }
    return 'index.php?' . http_build_query($params);
};

require_once __DIR__ . '/../layouts/header.php';
?>

<div id="tg-app"
     data-ref="<?= htmlspecialchars($ref) ?>"
     data-csrf="<?= htmlspecialchars($csrfToken) ?>">

    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <div>
            <h5 class="mb-1 fw-bold text-primary">
                <i class="fa-solid fa-table-list me-2"></i><?= htmlspecialchars($titulo) ?>
            </h5>
            <p class="text-muted mb-0" style="font-size:0.82rem;">
                Archivos &rsaquo; Tablas Generales &rsaquo; <?= htmlspecialchars($titulo) ?>
                <?php if ($tatipo): ?>
                    <span class="font-monospace ms-1">tatipo=<?= htmlspecialchars((string) $tatipo) ?></span>
                <?php endif; ?>
            </p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <?php if ($puedeAgregar): ?>
            <button type="button" id="tg-btn-agregar" class="btn btn-sm btn-success">
                <i class="fa-solid fa-plus me-1"></i>Agregar
            </button>
            <?php endif; ?>
            <?php if ($puedeEditar): ?>
            <button type="button" id="tg-btn-editar" class="btn btn-sm btn-primary">
                <i class="fa-solid fa-pen-to-square me-1"></i>Editar
            </button>
            <?php endif; ?>
            <?php if ($puedeBorrar): ?>
            <button type="button" id="tg-btn-borrar" class="btn btn-sm btn-danger">
                <i class="fa-solid fa-minus me-1"></i>Borrar
            </button>
            <?php endif; ?>
            <a href="index.php?route=tablas-generales" class="btn btn-sm btn-outline-secondary">
                <i class="fa-solid fa-table me-1"></i>Todas
            </a>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-3" style="border-radius:10px;">
        <div class="card-body py-2 px-3">
            <form method="get" action="index.php" class="row g-2 align-items-center">
                <input type="hidden" name="route" value="tablas-generales">
                <input type="hidden" name="ref" value="<?= htmlspecialchars($ref) ?>">
                <input type="hidden" name="sort" value="<?= htmlspecialchars($ordenCol) ?>">
                <input type="hidden" name="dir" value="<?= htmlspecialchars($ordenDir) ?>">
                <div class="col-md-7">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light">
                            <i class="fa-solid fa-magnifying-glass text-muted"></i>
                        </span>
                        <input type="text" id="tg-buscar" name="buscar" class="form-control"
                               value="<?= htmlspecialchars($busqueda) ?>"
                               placeholder="Código, descripción…">
                    </div>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        <i class="fa-solid fa-search me-1"></i>Buscar
                    </button>
                </div>
                <div class="col-md-2 text-md-end">
                    <span class="badge bg-secondary px-3 py-2">
                        <?= number_format($total, 0, ',', '.') ?> registro<?= $total === 1 ? '' : 's' ?>
                    </span>
                </div>
            </form>
        </div>
    </div>

    <?php if (!empty($flashOk)): ?>
    <div class="alert alert-success py-2"><?= htmlspecialchars((string) $flashOk) ?></div>
    <?php endif; ?>
    <?php if (!empty($flashError)): ?>
    <div class="alert alert-danger py-2"><?= htmlspecialchars((string) $flashError) ?></div>
    <?php endif; ?>
    <?php if (!empty($dbError)): ?>
    <div class="alert alert-danger py-2">
        <i class="fa-solid fa-triangle-exclamation me-1"></i>
        Error al consultar la tabla. Si falta la columna <code>id</code>, ejecute
        <code>setup/tablas_generales_setup.sql</code>.
    </div>
    <?php endif; ?>

    <div id="tg-alert" class="alert d-none mb-3 py-2"></div>

    <div class="card card-module mb-3">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table id="tabla-dinamica" class="table table-hover table-striped table-sm mb-0">
                    <thead>
                        <tr>
                            <?php foreach ($campos as $col):
                                $lab = isset($labels[$col]) ? $labels[$col] : strtoupper($col);
                                $href = isset($sortLinks[$col]) ? $sortLinks[$col] : '#';
                                $icon = 'fa-sort text-muted';
                                if ($ordenCol === $col) {
                                    $icon = ($ordenDir === 'ASC') ? 'fa-sort-up text-primary' : 'fa-sort-down text-primary';
                                }
                            ?>
                            <th>
                                <a href="<?= htmlspecialchars($href) ?>" class="text-decoration-none text-dark">
                                    <?= htmlspecialchars($lab) ?>
                                    <i class="fa-solid <?= $icon ?> ms-1" style="font-size:.65rem;"></i>
                                </a>
                            </th>
                            <?php endforeach; ?>
                            <?php if ($puedeEditar || $puedeBorrar): ?>
                            <th class="text-center no-export" style="width:90px;">Acciones</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($registros)): ?>
                        <tr>
                            <td colspan="<?= count($campos) + (($puedeEditar || $puedeBorrar) ? 1 : 0) ?>"
                                class="text-center text-muted py-4">
                                Sin registros.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($registros as $row):
                            $rid = isset($row['id']) ? (int) $row['id'] : 0;
                        ?>
                        <tr class="tg-row" data-id="<?= $rid ?>" style="cursor:pointer;">
                            <?php foreach ($campos as $col):
                                $val = isset($row[$col]) ? $row[$col] : '';
                            ?>
                            <td>
                                <?php if (isset($checks[$col])): ?>
                                    <?php if ((int) $val === 1): ?>
                                        <span class="badge bg-success">Sí</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">No</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <?= htmlspecialchars((string) $val) ?>
                                <?php endif; ?>
                            </td>
                            <?php endforeach; ?>
                            <?php if ($puedeEditar || $puedeBorrar): ?>
                            <td class="text-center no-export">
                                <?php if ($puedeEditar): ?>
                                <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2 tg-row-edit"
                                        data-id="<?= $rid ?>" title="Editar">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                <?php endif; ?>
                                <?php if ($puedeBorrar): ?>
                                <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2 tg-row-del"
                                        data-id="<?= $rid ?>" title="Borrar">
                                    <i class="fa-solid fa-trash"></i>
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
        </div>
    </div>

    <?php if ($totalPaginas > 1): ?>
    <nav>
        <ul class="pagination pagination-sm justify-content-end">
            <li class="page-item<?= $pagina <= 1 ? ' disabled' : '' ?>">
                <a class="page-link" href="<?= htmlspecialchars($pagUrl(max(1, $pagina - 1))) ?>">Anterior</a>
            </li>
            <li class="page-item disabled">
                <span class="page-link">pág. <?= $pagina ?>/<?= $totalPaginas ?></span>
            </li>
            <li class="page-item<?= $pagina >= $totalPaginas ? ' disabled' : '' ?>">
                <a class="page-link" href="<?= htmlspecialchars($pagUrl(min($totalPaginas, $pagina + 1))) ?>">Siguiente</a>
            </li>
        </ul>
    </nav>
    <?php endif; ?>
</div>

<div class="modal fade" id="tg-modal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:12px; overflow:hidden;">
            <form id="tg-form" autocomplete="off">
                <div class="modal-header py-2 px-3 text-white"
                     style="background:linear-gradient(90deg,#0d47a1,#1976d2);">
                    <h6 class="modal-title fw-semibold mb-0" id="tg-modal-title">
                        <i class="fa-solid fa-plus me-1"></i>Nuevo registro
                    </h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <input type="hidden" name="ref" value="<?= htmlspecialchars($ref) ?>">
                    <input type="hidden" name="id" id="tg-id" value="">
                    <div class="row g-3">
                    <?php foreach ($campos as $col):
                        $label = isset($labels[$col]) ? $labels[$col] : strtoupper($col);
                    ?>
                        <?php if (isset($checks[$col])): ?>
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch"
                                       id="tg-<?= htmlspecialchars($col) ?>" name="<?= htmlspecialchars($col) ?>" value="1">
                                <label class="form-check-label small" for="tg-<?= htmlspecialchars($col) ?>">
                                    <?= htmlspecialchars($label) ?>
                                </label>
                            </div>
                        </div>
                        <?php elseif ($col === 'taimporte'): ?>
                        <div class="col-12">
                            <label class="form-label small fw-semibold" for="tg-<?= htmlspecialchars($col) ?>">
                                <?= htmlspecialchars($label) ?>
                            </label>
                            <input type="number" step="0.01"
                                   id="tg-<?= htmlspecialchars($col) ?>" name="<?= htmlspecialchars($col) ?>"
                                   class="form-control form-control-sm">
                        </div>
                        <?php elseif ($col === 'tatipodeb'): ?>
                        <div class="col-12">
                            <label class="form-label small fw-semibold" for="tg-<?= htmlspecialchars($col) ?>">
                                <?= htmlspecialchars($label) ?>
                            </label>
                            <input type="text" maxlength="1"
                                   id="tg-<?= htmlspecialchars($col) ?>" name="<?= htmlspecialchars($col) ?>"
                                   class="form-control form-control-sm font-monospace text-uppercase">
                        </div>
                        <?php else: ?>
                        <div class="col-12">
                            <label class="form-label small fw-semibold" for="tg-<?= htmlspecialchars($col) ?>">
                                <?= htmlspecialchars($label) ?>
                                <?= ($col === 'tacodigo' || $col === 'tadescrip') ? '<span class="text-danger">*</span>' : '' ?>
                            </label>
                            <input type="text"
                                   maxlength="<?= $col === 'tacodigo' ? '38' : ($col === 'taprovin' || $col === 'tagrupo' ? '20' : '100') ?>"
                                   id="tg-<?= htmlspecialchars($col) ?>" name="<?= htmlspecialchars($col) ?>"
                                   class="form-control form-control-sm"
                                   <?= ($col === 'tacodigo' || $col === 'tadescrip') ? 'required' : '' ?>>
                        </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    </div>
                    <div id="tg-form-error" class="alert alert-danger py-2 px-3 mt-3 mb-0 d-none small"></div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" id="tg-btn-guardar" class="btn btn-sm btn-primary">
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
        el.className = 'alert mb-3 py-2 ' + (ok ? 'alert-success' : 'alert-danger');
        el.innerHTML = '<i class="fa-solid ' + (ok ? 'fa-circle-check' : 'fa-triangle-exclamation') + ' me-1"></i>' + msg;
        el.classList.remove('d-none');
    }

    function getModal() {
        if (!modal && typeof bootstrap !== 'undefined') {
            modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        }
        return modal;
    }

    function selectRow(tr) {
        var rows = document.querySelectorAll('#tabla-dinamica tbody tr.tg-row');
        for (var i = 0; i < rows.length; i++) {
            rows[i].classList.remove('selected');
        }
        if (!tr) { selectedId = 0; return; }
        tr.classList.add('selected');
        selectedId = parseInt(tr.getAttribute('data-id'), 10) || 0;
    }

    var rowEls = document.querySelectorAll('#tabla-dinamica tbody tr.tg-row');
    for (var r = 0; r < rowEls.length; r++) {
        (function (tr) {
            tr.addEventListener('click', function (e) {
                if (e.target.closest && e.target.closest('button')) return;
                selectRow(tr);
            });
            tr.addEventListener('dblclick', function (e) {
                if (e.target.closest && e.target.closest('button')) return;
                var btnEditar = document.getElementById('tg-btn-editar');
                if (btnEditar) abrirEdicion(parseInt(tr.getAttribute('data-id'), 10) || 0);
            });
        })(rowEls[r]);
    }

    function resetForm() {
        form.reset();
        document.getElementById('tg-id').value = '';
        var err = document.getElementById('tg-form-error');
        err.classList.add('d-none');
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

    var editBtns = document.querySelectorAll('.tg-row-edit');
    for (var e = 0; e < editBtns.length; e++) {
        editBtns[e].addEventListener('click', function (ev) {
            ev.stopPropagation();
            abrirEdicion(parseInt(this.getAttribute('data-id'), 10) || 0);
        });
    }
    var delBtns = document.querySelectorAll('.tg-row-del');
    for (var d = 0; d < delBtns.length; d++) {
        delBtns[d].addEventListener('click', function (ev) {
            ev.stopPropagation();
            borrarId(parseInt(this.getAttribute('data-id'), 10) || 0);
        });
    }

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        var err = document.getElementById('tg-form-error');
        err.classList.add('d-none');

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
                    err.classList.remove('d-none');
                    return;
                }
                getModal() && getModal().hide();
                window.location.reload();
            })
            .catch(function () {
                btn.disabled = false;
                err.textContent = 'Error de comunicación al guardar.';
                err.classList.remove('d-none');
            });
    });
})();
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
