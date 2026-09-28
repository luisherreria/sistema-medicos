<?php
/**
 * Vista: Carga e importe de planillas CP manuscritas.
 *
 * @var string $pageTitle
 * @var array  $breadcrumb
 */
$pageTitle  = 'COMEDICA — Planillas CP manuscritas';
$breadcrumb = [
    ['label' => 'Carga de Datos'],
    ['label' => 'Registración de Facturas'],
    ['label' => 'Planillas CP manuscritas'],
];
$csrfToken = $_SESSION['csrf_token'] ?? '';

require_once __DIR__ . '/layouts/header.php';
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
<style>
    .header-cp { background-color: #1e3a8a; color: white; padding: 12px 20px; font-weight: bold; }
    .drop-zone-cp {
        border: 2px dashed #1e3a8a; border-radius: 10px; background-color: #ffffff;
        padding: 25px; text-align: center; cursor: pointer; margin: 15px 0;
        transition: all 0.2s;
    }
    .drop-zone-cp:hover { background-color: #eff6ff; }
    .table-cp th { background-color: #0f172a; color: white; font-size: 11px; text-transform: uppercase; }
    .table-cp td { vertical-align: middle; padding: 8px; }
    .badge-renglon { background-color: #dbeafe; color: #1e40af; font-weight: bold; padding: 4px 8px; border-radius: 6px; }
</style>

<div class="container my-2 px-0">
    <div class="card shadow-sm border-0 rounded-3">
        <div class="header-cp d-flex justify-content-between align-items-center">
            <span><i class="bi bi-pencil-square me-2"></i>Carga e Importe de Planillas CP Manuscritas</span>
            <span class="badge bg-light text-dark">Lote acotado: 1 a 2 archivos</span>
        </div>

        <div class="card-body">
            <div class="drop-zone-cp" id="dropZoneCP">
                <h6 class="text-primary fw-bold mb-1"><i class="bi bi-cloud-arrow-up-fill me-2 fs-4"></i>Arrastrá la Planilla Manuscrita (JPG, PNG o PDF)</h6>
                <small class="text-muted">El sistema contará los renglones escritos y multiplicará por el arancel de la práctica 42XXXX del prestador.</small>
                <input type="file" id="fileInputCP" class="d-none" accept="image/jpeg, image/png, application/pdf" multiple>
            </div>

            <div class="table-responsive mt-3">
                <table class="table table-bordered table-hover align-middle table-cp">
                    <thead>
                        <tr>
                            <th>Prestador</th>
                            <th style="width: 110px;">Cód. Prest.</th>
                            <th style="width: 130px;">Nro. Factura</th>
                            <th style="width: 110px;">Práctica</th>
                            <th style="width: 140px; text-align: center;">Renglones</th>
                            <th style="width: 130px; text-align: right;">Valor Práctica</th>
                            <th style="width: 150px; text-align: right;">Importe Calculado</th>
                            <th style="width: 80px; text-align: center;">Acción</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyCP">
                        <tr>
                            <td colspan="8" class="text-center text-muted py-3">No hay planillas manuscritas cargadas.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
const dropZone = document.getElementById('dropZoneCP');
const fileInput = document.getElementById('fileInputCP');
const tbody = document.getElementById('tbodyCP');
const CSRF = <?= json_encode($csrfToken) ?>;
const CP_URL = 'controllers/CpManuscritoController.php';

dropZone.addEventListener('click', () => fileInput.click());
dropZone.addEventListener('dragover', (e) => { e.preventDefault(); dropZone.style.background = '#eff6ff'; });
dropZone.addEventListener('dragleave', () => { dropZone.style.background = '#ffffff'; });
dropZone.addEventListener('drop', (e) => {
    e.preventDefault();
    dropZone.style.background = '#ffffff';
    if (e.dataTransfer.files.length) procesarPlanillas(e.dataTransfer.files);
});

fileInput.addEventListener('change', () => {
    if (fileInput.files.length) procesarPlanillas(fileInput.files);
});

function esc(s) {
    return String(s == null ? '' : s)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function procesarPlanillas(files) {
    if (files.length > 2) {
        alert('Por favor seleccione como máximo 2 archivos a la vez.');
        return;
    }

    if (tbody.querySelector('td[colspan="8"]')) {
        tbody.innerHTML = '';
    }

    Array.from(files).forEach(file => {
        const formData = new FormData();
        formData.append('archivo_cp', file);
        formData.append('csrf_token', CSRF);

        const trLoading = document.createElement('tr');
        trLoading.innerHTML = `<td colspan="8" class="text-center text-primary"><div class="spinner-border spinner-border-sm me-2"></div>Contando renglones y calculando arancel de: ${esc(file.name)}...</td>`;
        tbody.prepend(trLoading);

        fetch(CP_URL, { method: 'POST', body: formData, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(res => res.json())
        .then(data => {
            trLoading.remove();
            if (data.status === 'success') {
                const prestador = data.prestador || 'MELLUSO GABRIELA';
                const nroFac = data.nro_factura || '';
                const importe = data.importe || data.importe_calculado || '';
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td><input type="text" class="form-control form-control-sm fw-bold inp-prestador" value="${esc(prestador)}"></td>
                    <td><input type="text" class="form-control form-control-sm inp-cod" value="${esc(data.cod_prestador || '')}"></td>
                    <td><input type="text" class="form-control form-control-sm font-monospace inp-nro" value="${esc(nroFac)}"></td>
                    <td><span class="badge bg-secondary">${esc(data.cod_practica || '')}</span></td>
                    <td class="text-center"><span class="badge-renglon"><i class="bi bi-list-ol me-1"></i>${esc(data.cant_renglones || 20)} renglones</span></td>
                    <td class="text-end font-monospace">$${esc(data.valor_practica || '')}</td>
                    <td><input type="text" class="form-control form-control-sm text-end font-monospace fw-bold text-success inp-imp" value="${esc(importe)}"></td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove()"><i class="bi bi-trash"></i></button>
                    </td>
                `;
                tbody.prepend(tr);
            } else {
                alert('Error: ' + (data.mensaje || 'No se pudo procesar la planilla.'));
            }
        })
        .catch(() => {
            trLoading.remove();
            alert('Error de procesamiento en el servidor.');
        });
    });
    fileInput.value = '';
}
</script>
<?php require_once __DIR__ . '/layouts/footer.php'; ?>
