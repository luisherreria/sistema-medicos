<?php
/**
 * views/nomencla/_modal_importar.php
 * Importación de plantilla al nomenclador y al prestador.
 */
$csrfToken = $_SESSION['csrf_token'] ?? '';
?>

<div class="modal fade" id="modalImportarNomenclador" tabindex="-1" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="border-radius:12px; overflow:hidden;">
            <div class="modal-header py-2 px-3 text-white"
                 style="background:linear-gradient(135deg,#0f766e,#0d9488); border-bottom:none;">
                <h6 class="modal-title mb-0 fw-bold">
                    <i class="fa-solid fa-file-import me-2"></i>Importar plantilla de prácticas
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="nn-imp-csrf" value="<?= htmlspecialchars($csrfToken) ?>">

                <div id="dropZoneImport" class="border border-2 border-dashed rounded-3 p-4 text-center mb-3"
                     style="border-color:#99f6e4 !important; background:#f0fdfa; cursor:pointer;">
                    <i class="fa-solid fa-cloud-arrow-up fa-2x text-teal mb-2" style="color:#0f766e;"></i>
                    <div class="fw-semibold">Arrastre la plantilla aquí o haga clic para elegirla</div>
                    <div class="text-muted" style="font-size:.78rem;">Excel, CSV, PDF o imagen. Las columnas pueden venir en cualquier orden.</div>
                    <div id="nn-imp-archivo" class="mt-2 fw-semibold" style="font-size:.8rem;"></div>
                    <input type="file" id="nn-imp-file" class="d-none"
                           accept=".xlsx,.csv,.pdf,.jpg,.jpeg,.png,application/pdf,text/csv">
                </div>

                <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 border rounded-3 p-3 mb-3 bg-light">
                    <div class="small text-secondary" style="line-height:1.45;">
                        <div class="fw-semibold text-dark mb-1">Columnas de la plantilla. Puede ser Excel, CSV o PDF.</div>
                        TACODIGO (ej. 420101)<br>
                        TADESCRIP = nombre práctica<br>
                        TAIMPORTE<br>
                        VALNBU = valor base<br>
                        TAGRUPO<br>
                        SUBGRUPO<br>
                        CAPITULO<br>
                        NOMENCLADA = SI/NO<br>
                        COMPLEJIDAD = ALTA/MEDIA/BAJA<br>
                        GRUPOCAR<br>
                        GRUPOSUP<br>
                        PRESTADOR = código, matrícula o cód. prestador
                    </div>
                    <a href="/sistema-medicos/public/img/ejemplo_practicas.csv"
                       class="btn btn-primary btn-sm align-self-center"
                       target="_blank" rel="noopener"
                       download="ejemplo_practicas.csv">Ver ejemplo</a>
                </div>

                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" id="nn-imp-switch" name="cargar_prestador">
                    <label class="form-check-label fw-semibold" for="nn-imp-switch">Desea cargar prácticas también al Prestador</label>
                </div>

                <div id="nn-imp-prestador-box" class="mb-2" style="display:none;">
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <label for="nn-imp-prestador" class="mb-0 fw-semibold" style="font-size:.85rem; min-width:9.5rem;">Código de prestador</label>
                        <input type="text" id="nn-imp-prestador" class="form-control form-control-sm font-monospace" maxlength="11" style="max-width:140px;" placeholder="Código" autocomplete="off">
                        <input type="text" id="nn-imp-prestador-nombre" class="form-control form-control-sm bg-light" readonly placeholder="Nombre del prestador" style="min-width:220px; flex:1;">
                        <button type="button" id="nn-imp-validar" class="btn btn-outline-secondary btn-sm">Verificar</button>
                    </div>
                    <div id="nn-imp-prestador-msg" class="small mt-1"></div>
                </div>

                <div id="nn-imp-error" class="text-danger small" style="display:none;"></div>
                <div id="nn-imp-resumen" class="small mt-2"></div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-outline-secondary px-3" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" id="nn-imp-procesar" class="btn btn-primary px-4 shadow-sm">
                    <i class="fa-solid fa-upload me-1"></i>Importar
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalImportarSucursales" tabindex="-1" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="border-radius:12px; overflow:hidden;">
            <div class="modal-header py-2 px-3 text-white"
                 style="background:linear-gradient(135deg,#0d47a1,#1976d2); border-bottom:none;">
                <h6 class="modal-title mb-0 fw-bold">
                    <i class="fa-solid fa-code-branch me-2"></i>Sucursales del prestador
                </h6>
                <button type="button" class="btn-close btn-close-white" id="nn-imp-suc-cerrar" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted">El prestador tiene más de una sucursal. Elija dónde grabar las prácticas.</p>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="nn-imp-marcar-todo">
                    <label class="form-check-label fw-semibold" for="nn-imp-marcar-todo">Marcar todo</label>
                </div>
                <div class="table-responsive border rounded">
                    <table class="table table-sm table-hover mb-0" id="nn-imp-tabla-suc">
                        <thead class="table-light">
                            <tr>
                                <th style="width:42px;"></th>
                                <th>Código</th>
                                <th>Nombre</th>
                                <th>Dirección</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
                <div id="nn-imp-suc-aviso" class="alert alert-warning small mt-3 mb-0" style="display:none;">
                    No se grabarán prácticas en las sucursales. El evento ha sido registrado.
                </div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-outline-secondary px-3" id="nn-imp-suc-cancelar">Cerrar</button>
                <button type="button" class="btn btn-primary px-4 shadow-sm" id="nn-imp-suc-aceptar">Aceptar</button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var zona = document.getElementById('dropZoneImport');
    var file = document.getElementById('nn-imp-file');
    var nombre = document.getElementById('nn-imp-archivo');
    var sw = document.getElementById('nn-imp-switch');
    var box = document.getElementById('nn-imp-prestador-box');
    var archivoElegido = null;

    if (!zona || !file) return;

    function mostrarError(texto) {
        var el = document.getElementById('nn-imp-error');
        el.textContent = texto || '';
        el.style.display = texto ? 'block' : 'none';
    }

    zona.addEventListener('click', function () { file.click(); });
    zona.addEventListener('dragover', function (ev) {
        ev.preventDefault();
        zona.style.background = '#ccfbf1';
    });
    zona.addEventListener('dragleave', function () { zona.style.background = '#f0fdfa'; });
    zona.addEventListener('drop', function (ev) {
        ev.preventDefault();
        zona.style.background = '#f0fdfa';
        if (ev.dataTransfer && ev.dataTransfer.files && ev.dataTransfer.files[0]) {
            archivoElegido = ev.dataTransfer.files[0];
            nombre.textContent = archivoElegido.name;
            mostrarError('');
        }
    });
    file.addEventListener('change', function () {
        archivoElegido = file.files && file.files[0] ? file.files[0] : null;
        nombre.textContent = archivoElegido ? archivoElegido.name : '';
    });

    sw.addEventListener('change', function () {
        box.style.display = sw.checked ? '' : 'none';
    });

    function buscarPrestador() {
        var cod = (document.getElementById('nn-imp-prestador').value || '').trim();
        var msg = document.getElementById('nn-imp-prestador-msg');
        var nom = document.getElementById('nn-imp-prestador-nombre');
        if (!cod) {
            nom.value = '';
            msg.innerHTML = '<span class="text-danger">Ingrese código, matrícula o cód. prestador.</span>';
            return;
        }
        fetch('index.php?route=nomenclador&action=importar_prestador&cod=' + encodeURIComponent(cod), {
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function (r) { return r.json(); })
        .then(function (json) {
            if (json && json.ok && json.prestador) {
                document.getElementById('nn-imp-prestador').value = json.prestador.codigo || cod;
                nom.value = json.prestador.nombre || '';
                msg.innerHTML = '';
            } else {
                nom.value = '';
                msg.innerHTML = '<span class="text-danger">' + ((json && json.msg) || 'Prestador inexistente.') + '</span>';
            }
        })
        .catch(function () {
            nom.value = '';
            msg.innerHTML = '<span class="text-danger">No se pudo verificar el prestador.</span>';
        });
    }

    document.getElementById('nn-imp-validar').addEventListener('click', buscarPrestador);
    document.getElementById('nn-imp-prestador').addEventListener('blur', buscarPrestador);
    document.getElementById('nn-imp-prestador').addEventListener('keydown', function (ev) {
        if (ev.key === 'Enter') {
            ev.preventDefault();
            buscarPrestador();
        }
    });

    function abrirSucursales(lista) {
        var tbody = document.querySelector('#nn-imp-tabla-suc tbody');
        tbody.innerHTML = '';
        document.getElementById('nn-imp-suc-aviso').style.display = 'none';
        document.getElementById('nn-imp-marcar-todo').checked = false;
        (lista || []).forEach(function (suc) {
            var tr = document.createElement('tr');
            tr.innerHTML = '<td><input type="checkbox" class="form-check-input nn-imp-suc" value="' +
                String(suc.codigo || '').replace(/"/g, '&quot;') + '"></td>' +
                '<td class="font-monospace">' + (suc.codigo || '') + '</td>' +
                '<td>' + (suc.nombre || '') + '</td>' +
                '<td>' + (suc.direccion || '') + '</td>';
            tbody.appendChild(tr);
        });
        var el = document.getElementById('modalImportarSucursales');
        if (window.bootstrap) window.bootstrap.Modal.getOrCreateInstance(el).show();
    }

    document.getElementById('nn-imp-marcar-todo').addEventListener('change', function () {
        var on = this.checked;
        document.querySelectorAll('.nn-imp-suc').forEach(function (cb) { cb.checked = on; });
    });

    function omitirSucursales() {
        var datos = new FormData();
        datos.append('csrf_token', document.getElementById('nn-imp-csrf').value);
        fetch('index.php?route=nomenclador&action=importar_omitir_sucursales', {
            method: 'POST',
            body: datos,
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function (r) { return r.json(); })
        .then(function (json) {
            var texto = (json && json.msg) || 'No se grabarán prácticas en las sucursales. El evento ha sido registrado.';
            document.getElementById('nn-imp-suc-aviso').style.display = 'block';
            document.getElementById('nn-imp-suc-aviso').textContent = texto;
            alert(texto);
        });
    }

    document.getElementById('nn-imp-suc-cerrar').addEventListener('click', function () {
        omitirSucursales();
        var el = document.getElementById('modalImportarSucursales');
        var inst = window.bootstrap && window.bootstrap.Modal.getInstance(el);
        if (inst) inst.hide();
    });
    document.getElementById('nn-imp-suc-cancelar').addEventListener('click', function () {
        omitirSucursales();
        var el = document.getElementById('modalImportarSucursales');
        var inst = window.bootstrap && window.bootstrap.Modal.getInstance(el);
        if (inst) inst.hide();
    });

    document.getElementById('nn-imp-suc-aceptar').addEventListener('click', function () {
        var elegidas = [];
        document.querySelectorAll('.nn-imp-suc:checked').forEach(function (cb) { elegidas.push(cb.value); });
        if (!elegidas.length) {
            omitirSucursales();
            return;
        }
        var datos = new FormData();
        datos.append('csrf_token', document.getElementById('nn-imp-csrf').value);
        elegidas.forEach(function (cod) { datos.append('sucursales[]', cod); });
        fetch('index.php?route=nomenclador&action=importar_sucursales', {
            method: 'POST',
            body: datos,
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function (r) { return r.json(); })
        .then(function (json) {
            if (!json || !json.ok) {
                document.getElementById('nn-imp-suc-aviso').style.display = 'block';
                document.getElementById('nn-imp-suc-aviso').textContent = (json && json.msg) || 'No se pudieron grabar las sucursales.';
                return;
            }
            var el = document.getElementById('modalImportarSucursales');
            var inst = window.bootstrap && window.bootstrap.Modal.getInstance(el);
            if (inst) inst.hide();
            alert(json.msg);
        });
    });

    document.getElementById('nn-imp-procesar').addEventListener('click', function () {
        mostrarError('');
        document.getElementById('nn-imp-resumen').textContent = '';
        if (!archivoElegido) {
            mostrarError('Arrastre o elija una plantilla.');
            return;
        }
        var btn = this;
        btn.disabled = true;
        var datos = new FormData();
        datos.append('csrf_token', document.getElementById('nn-imp-csrf').value);
        datos.append('archivo', archivoElegido);
        datos.append('cargar_prestador', sw.checked ? '1' : '0');
        datos.append('prestador', document.getElementById('nn-imp-prestador').value || '');
        fetch('index.php?route=nomenclador&action=importar_procesar', {
            method: 'POST',
            body: datos,
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function (r) { return r.json(); })
        .then(function (json) {
            if (!json || !json.ok) {
                mostrarError((json && json.msg) || 'No se pudo importar.');
                return;
            }
            var texto = json.msg + ' Nomenclador: ' + (json.nomencla_insertados || 0) +
                '. Prestador: ' + (json.pracespe_insertados || 0) + '.';
            if (json.errores && json.errores.length) {
                texto += ' ' + json.errores.join(' ');
            }
            document.getElementById('nn-imp-resumen').textContent = texto;
            if (window.nnRecargarGrilla) window.nnRecargarGrilla();
            if (json.sucursales_requeridas) {
                abrirSucursales(json.sucursales_lista || []);
            } else {
                alert(json.msg);
            }
        })
        .catch(function () {
            mostrarError('Error de comunicación con el servidor.');
        })
        .finally(function () { btn.disabled = false; });
    });
})();
</script>
