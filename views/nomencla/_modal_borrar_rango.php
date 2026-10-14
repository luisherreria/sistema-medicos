<?php
/**
 * views/nomencla/_modal_borrar_rango.php
 * Borrado de prácticas del nomenclador por rango de código.
 */
$csrfToken = $_SESSION['csrf_token'] ?? '';
?>

<div class="modal fade" id="modalBorrarRango" tabindex="-1"
     aria-labelledby="modalBorrarRangoLabel" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:12px; overflow:hidden;">
            <div class="modal-header py-2 px-3"
                 style="background:linear-gradient(90deg,#b91c1c,#dc2626); color:#fff; border-bottom:none;">
                <h6 class="modal-title mb-0 fw-bold" id="modalBorrarRangoLabel">
                    <i class="fa-solid fa-trash-can me-2"></i>Borrar prácticas por rango
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <form id="form-borrar-rango" autocomplete="off">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <p class="text-muted mb-3" style="font-size:.82rem;">
                        Se eliminan los códigos comprendidos entre ambos valores, inclusive.
                        Esta acción no puede deshacerse.
                    </p>
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label for="nn-borrar-desde" class="form-label fw-semibold mb-1" style="font-size:.8rem;">
                                Desde <span class="text-danger">*</span>
                            </label>
                            <input type="text" id="nn-borrar-desde" name="desde" maxlength="8" required
                                   class="form-control form-control-sm"
                                   placeholder="000001">
                        </div>
                        <div class="col-sm-6">
                            <label for="nn-borrar-hasta" class="form-label fw-semibold mb-1" style="font-size:.8rem;">
                                Hasta <span class="text-danger">*</span>
                            </label>
                            <input type="text" id="nn-borrar-hasta" name="hasta" maxlength="8" required
                                   class="form-control form-control-sm"
                                   placeholder="000099">
                        </div>
                    </div>
                    <div id="nn-borrar-error" class="text-danger mt-2" style="font-size:.78rem; display:none;"></div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" id="nn-borrar-submit" class="btn btn-sm btn-danger">
                        <i class="fa-solid fa-trash-can me-1"></i>Eliminar rango
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
