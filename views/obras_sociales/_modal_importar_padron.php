<?php
/**
 * views/obras_sociales/_modal_importar_padron.php
 * Prelectura e importación de un padrón a vfpmedicos.padron_activos.
 */
?>
<style>
#modalImportarPadron .os-drop-padron {
    border: 1px dashed #90caf9;
    border-radius: 0.75rem;
    background: #f8fafc;
    min-height: 92px;
    cursor: pointer;
}
#modalImportarPadron .os-drop-padron.os-drop-on {
    outline: 2px dashed #1976d2;
    background: #e3f2fd;
}
</style>
<div class="modal fade" id="modalImportarPadron" tabindex="-1" aria-labelledby="modalImportarPadronTitulo" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <form id="frmImportarPadron" autocomplete="off">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalImportarPadronTitulo">
                        <i class="fa-solid fa-file-import me-1"></i> Importar padrón
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2 mb-3">
                        <div class="col-md-4">
                            <div class="form-floating">
                                <input type="text" class="form-control rounded-3 text-uppercase" id="impObrasoc"
                                       name="obrasoc" maxlength="8" placeholder="Obra social" list="listaImportOs" required>
                                <label for="impObrasoc"><i class="fa-solid fa-building-columns me-1 text-muted"></i> Obra social</label>
                            </div>
                            <datalist id="listaImportOs"></datalist>
                            <div class="form-text" id="impObraNombre">Seleccione un código vigente.</div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-floating">
                                <input type="text" class="form-control rounded-3" id="impNpadron"
                                       name="npadron" maxlength="80" placeholder="Nombre del padrón" required>
                                <label for="impNpadron"><i class="fa-solid fa-file-lines me-1 text-muted"></i> Nombre del padrón</label>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-floating">
                                <input type="text" class="form-control rounded-3" id="impPeriodo"
                                       name="periodo" maxlength="5" placeholder="AA/MM" inputmode="numeric" required>
                                <label for="impPeriodo"><i class="fa-solid fa-calendar me-1 text-muted"></i> Período</label>
                            </div>
                            <div class="form-text text-danger d-none" id="impPeriodoError"></div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-floating">
                                <input type="date" class="form-control rounded-3" id="impFbaj" name="fbaj" placeholder="Fecha de baja">
                                <label for="impFbaj"><i class="fa-solid fa-calendar-xmark me-1 text-muted"></i> Fecha de baja</label>
                            </div>
                        </div>
                    </div>

                    <div class="os-drop-padron d-flex flex-column align-items-center justify-content-center text-center p-3 mb-3" id="dropPadron">
                        <i class="fa-solid fa-cloud-arrow-up text-primary mb-1"></i>
                        <div class="small">Arrastre el padrón o haga clic para elegirlo</div>
                        <div class="text-muted" style="font-size:11px">DBF, XLSX, XLS, CSV o TXT</div>
                        <div class="small fw-semibold mt-1" id="impArchivoNombre"></div>
                        <input type="file" id="filePadronImp" class="d-none" accept=".dbf,.xlsx,.xls,.csv,.txt">
                    </div>

                    <div id="impResumen" class="d-none">
                        <div class="alert alert-info py-2 mb-2" id="impResumenTexto"></div>
                        <div class="table-responsive">
                            <table class="table table-sm table-striped align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Afiliado</th>
                                        <th>Adh.</th>
                                        <th>Nombre</th>
                                        <th>Documento</th>
                                        <th>Plan</th>
                                        <th>Estado</th>
                                        <th>F. baja</th>
                                    </tr>
                                </thead>
                                <tbody id="impPreviewBody"></tbody>
                            </table>
                        </div>
                        <p class="text-muted small mt-2 mb-0">Vista previa de los primeros 10 registros activos. Las bajas (FBAJ con fecha o estado B/BAJA) no se importan.</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-success" id="btnConfirmarPadron" disabled>
                        <i class="fa-solid fa-check me-1"></i> Confirmar importación
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
