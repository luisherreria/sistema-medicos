<?php
/**
 * views/nomencla/_modal_editar_nomenclador.php
 * Alta / modificación de una práctica del Nomenclador Nacional.
 */
$csrfToken = $_SESSION['csrf_token'] ?? '';
?>

<div class="modal fade" id="modalEditarNomenclador" tabindex="-1"
     aria-labelledby="modalEditarNomencladorLabel" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="border-radius:12px; overflow:hidden;">
            <div class="modal-header py-2 px-3"
                 style="background:linear-gradient(135deg,#0d47a1,#1976d2); color:#fff; border-bottom:none;">
                <h6 class="modal-title mb-0 fw-bold" id="modalEditarNomencladorLabel">
                    <i class="fa-solid fa-book-medical me-2"></i>Actualización Datos Nomenclador
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <form id="form-editar-nomenclador" autocomplete="off">
                <div class="modal-body" style="font-size:.82rem;">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <input type="hidden" name="accion" id="nn-edit-accion" value="editar">
                    <input type="hidden" name="modo" id="nn-edit-modo" value="edicion">

                    <div class="row g-2 mb-2">
                        <div class="col-md-3">
                            <label for="nn-edit-tacodigo" class="form-label mb-1 fw-semibold">Código</label>
                            <input type="text" class="form-control form-control-sm font-monospace fw-bold text-primary" id="nn-edit-tacodigo"
                                   name="tacodigo" maxlength="6" required>
                        </div>
                        <div class="col-md-9">
                            <label for="nn-edit-tadescrip" class="form-label mb-1 fw-semibold">Nombre</label>
                            <input type="text" class="form-control form-control-sm" id="nn-edit-tadescrip"
                                   name="tadescrip" maxlength="100">
                        </div>
                    </div>

                    <div class="row g-2 mb-2">
                        <div class="col-md-4">
                            <label for="nn-edit-tagrupo" class="form-label mb-1 fw-semibold">Grupo</label>
                            <select class="form-select form-select-sm nn-combo" id="nn-edit-tagrupo"
                                    name="tagrupo" data-catalogo="tagrupo"></select>
                        </div>
                        <div class="col-md-4">
                            <label for="nn-edit-complejida" class="form-label mb-1 fw-semibold">Complejidad</label>
                            <select class="form-select form-select-sm nn-combo" id="nn-edit-complejida"
                                    name="complejida" data-catalogo="complejida"></select>
                        </div>
                        <div class="col-md-4">
                            <label for="nn-edit-tatiponom" class="form-label mb-1 fw-semibold">Tipo Nom.</label>
                            <select class="form-select form-select-sm nn-combo" id="nn-edit-tatiponom"
                                    name="tatiponom" data-catalogo="tatiponom"></select>
                        </div>
                    </div>

                    <div class="row g-2 mb-2">
                        <div class="col-md-4">
                            <label for="nn-edit-taimporte" class="form-label mb-1 fw-semibold">Importe</label>
                            <input type="text" class="form-control form-control-sm text-end fw-bold" id="nn-edit-taimporte"
                                   name="taimporte" inputmode="decimal">
                            <small class="text-muted"><i class="fa-solid fa-circle-info text-info me-1"></i>Verifique valor en N.N.</small>
                        </div>
                        <div class="col-md-4">
                            <label for="nn-edit-nomenclada" class="form-label mb-1 fw-semibold">Nomenclada</label>
                            <select class="form-select form-select-sm nn-combo" id="nn-edit-nomenclada"
                                    name="nomenclada" data-catalogo="nomenclada"></select>
                        </div>
                        <div class="col-md-4">
                            <label for="nn-edit-capitulo" class="form-label mb-1 fw-semibold">Capítulo</label>
                            <input type="text" class="form-control form-control-sm" id="nn-edit-capitulo"
                                   name="capitulo" maxlength="2">
                        </div>
                    </div>

                    <div class="row g-2 mb-2">
                        <div class="col-md-6">
                            <label for="nn-edit-tvalnbu" class="form-label mb-1 fw-semibold">Unidad Básica NBU</label>
                            <input type="text" class="form-control form-control-sm text-end fw-bold" id="nn-edit-tvalnbu"
                                   name="tvalnbu" inputmode="decimal">
                            <small class="text-muted"><i class="fa-solid fa-circle-info text-info me-1"></i>Verifique Unidad Basica en CUBRA</small>
                        </div>
                        <div class="col-md-6">
                            <label for="nn-edit-subgrupo" class="form-label mb-1 fw-semibold">SubGrupo</label>
                            <select class="form-select form-select-sm nn-combo" id="nn-edit-subgrupo"
                                    name="subgrupo" data-catalogo="subgrupo"></select>
                        </div>
                    </div>

                    <div class="row g-2 mb-2">
                        <div class="col-md-4">
                            <label for="nn-edit-grupocar" class="form-label mb-1 fw-semibold">Grupo Cartilla</label>
                            <select class="form-select form-select-sm nn-combo" id="nn-edit-grupocar"
                                    name="grupocar" data-catalogo="grupocar"></select>
                        </div>
                        <div class="col-md-4">
                            <label for="nn-edit-gruposup" class="form-label mb-1 fw-semibold">Título Cartilla</label>
                            <select class="form-select form-select-sm nn-combo" id="nn-edit-gruposup"
                                    name="gruposup" data-catalogo="gruposup"></select>
                        </div>
                        <div class="col-md-4">
                            <label for="nn-edit-tipo650" class="form-label mb-1 fw-semibold">Tipo Normativa 650</label>
                            <select class="form-select form-select-sm nn-combo" id="nn-edit-tipo650"
                                    name="tipo650" data-catalogo="tipo650"></select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="nn-edit-thomologo" class="form-label mb-1 fw-semibold">Nombre Homologado</label>
                        <select class="form-select form-select-sm nn-combo" id="nn-edit-thomologo"
                                name="thomologo" data-catalogo="thomologo" data-valor="descripcion"></select>
                    </div>

                    <div class="bg-light rounded-3 p-3 border mb-3 d-flex flex-column gap-2">
                        <label class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" id="nn-edit-vermodulo" name="vermodulo" value="1">
                            <span class="form-check-label">Marcar como módulo para control (Deb 169)</span>
                        </label>
                        <label class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" id="nn-edit-desdobla" name="desdobla" value="1">
                            <span class="form-check-label">Marcar para desdoblar por la cantidad</span>
                        </label>
                        <label class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" id="nn-edit-isnbu" name="isnbu" value="1">
                            <span class="form-check-label">Marcar para NBU (caso contrario es NN o Importe Fijo)</span>
                        </label>
                    </div>

                    <div id="nn-edit-error" class="text-danger mt-2" style="display:none;"></div>
                </div>
                <div class="modal-footer py-2 d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-outline-secondary px-3" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" id="btn-guardar-nomenclador" class="btn btn-primary px-4 shadow-sm d-inline-flex align-items-center">
                        <i class="fa-solid fa-check me-1"></i>Aceptar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
