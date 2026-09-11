<?php
/**
 * views/prestadores/_modal_editar_prestador.php
 * ─────────────────────────────────────────────────────────────────────────
 * Modal Bootstrap 5: Editar / Nuevo Prestador
 * Equivalente web de ECMED.SCT/SCX (VFP)
 * ─────────────────────────────────────────────────────────────────────────
 */
$csrfToken = $_SESSION['csrf_token'] ?? '';
?>

<!-- ╔═══════════════════════════════════════════════════════════════════╗
     ║  MODAL PRINCIPAL: EDITAR / NUEVO PRESTADOR                       ║
     ╚═══════════════════════════════════════════════════════════════════╝ -->
<div class="modal fade" id="modalEditarPrestador" tabindex="-1"
     aria-labelledby="modalEditarPrestadorLabel" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content" style="border-radius:12px;overflow:hidden;">

        <!-- ── Header ─────────────────────────────────────────────────── -->
        <div class="modal-header py-2 px-4"
             style="background:linear-gradient(90deg,#1a237e,#283593);color:#fff;border-bottom:none;">
            <div class="d-flex align-items-center gap-3">
                <div style="width:36px;height:36px;border-radius:50%;background:rgba(255,255,255,.18);
                            display:flex;align-items:center;justify-content:center;">
                    <i class="fa-solid fa-user-doctor"></i>
                </div>
                <div>
                    <h6 class="modal-title mb-0 fw-bold" id="modalEditarPrestadorLabel">
                        Datos del Prestador
                    </h6>
                    <small style="opacity:.8;font-size:.72rem;">
                        <span id="ep-header-accion" class="text-warning fw-semibold">—</span>
                        &nbsp;|&nbsp; Código:
                        <code id="ep-header-codigo" style="color:#90caf9;font-size:.8rem;">—</code>
                        &nbsp;<span id="ep-header-nombre" style="font-size:.75rem;opacity:.9;"></span>
                    </small>
                </div>
            </div>
            <button type="button" class="btn-close btn-close-white"
                    data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>

        <!-- ── Tabs ───────────────────────────────────────────────────── -->
        <ul class="nav nav-tabs px-3 pt-2" id="ep-tabs" role="tablist"
            style="background:#f0f4ff;font-size:.8rem;gap:2px;">
            <li class="nav-item">
                <button class="nav-link active py-1 px-3" id="ep-tab-id"
                        data-bs-toggle="tab" data-bs-target="#ep-pane-id" type="button">
                    <i class="fa-solid fa-id-card me-1"></i>Identificación
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link py-1 px-3" id="ep-tab-dir"
                        data-bs-toggle="tab" data-bs-target="#ep-pane-dir" type="button">
                    <i class="fa-solid fa-map-marker-alt me-1"></i>Dirección
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link py-1 px-3" id="ep-tab-cont"
                        data-bs-toggle="tab" data-bs-target="#ep-pane-cont" type="button">
                    <i class="fa-solid fa-envelope me-1"></i>Contacto / Mails
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link py-1 px-3" id="ep-tab-estado"
                        data-bs-toggle="tab" data-bs-target="#ep-pane-estado" type="button">
                    <i class="fa-solid fa-circle-check me-1"></i>Estado
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link py-1 px-3" id="ep-tab-adj"
                        data-bs-toggle="tab" data-bs-target="#ep-pane-adj" type="button">
                    <i class="fa-solid fa-paperclip me-1"></i>Adjuntos
                    <span id="ep-adj-badge" class="badge bg-secondary ms-1" style="font-size:.65rem;">0</span>
                </button>
            </li>
        </ul>

        <!-- ── Body ───────────────────────────────────────────────────── -->
        <div class="modal-body p-0">

            <!-- Spinner -->
            <div id="ep-loading" class="text-center py-5">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="text-muted mt-2 mb-0" style="font-size:.85rem;">Cargando…</p>
            </div>

            <!-- Formulario -->
            <div id="ep-form-wrap" style="display:none;">
            <form id="ep-form" novalidate autocomplete="off">
            <input type="hidden" name="csrf_token" id="ep-csrf"   value="<?= htmlspecialchars($csrfToken) ?>">
            <input type="hidden" name="accion"     id="ep-accion" value="modificar">
            <input type="hidden" name="codigo"     id="ep-codigo" value="">
            <input type="hidden" name="idweb"      id="ep-idweb"  value="0">

            <div class="tab-content px-4 py-3">

                <!-- ══════════ TAB 1: IDENTIFICACIÓN ══════════ -->
                <div class="tab-pane fade show active" id="ep-pane-id" role="tabpanel">
                    <div class="row g-2 mb-2">
                        <div class="col-md-2">
                            <label class="ep-lbl">Código</label>
                            <input type="text" id="ep-codigo-display"
                                   class="form-control form-control-sm fw-bold bg-light font-monospace"
                                   readonly tabindex="-1">
                        </div>
                        <div class="col-md-2">
                            <label class="ep-lbl"><span class="text-danger">*</span> CUIT</label>
                            <input type="text" name="cuit" id="ep-cuit"
                                   class="form-control form-control-sm font-monospace"
                                   maxlength="13" placeholder="Sin guiones">
                        </div>
                        <div class="col-md-2">
                            <label class="ep-lbl">Legajo</label>
                            <input type="number" name="legajo" id="ep-legajo"
                                   class="form-control form-control-sm">
                        </div>
                        <div class="col-md-3">
                            <label class="ep-lbl"><span class="text-danger">*</span> Categoría</label>
                            <select name="categ" id="ep-categ" class="form-select form-select-sm">
                                <option value="">— Seleccionar —</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="ep-lbl"><span class="text-danger">*</span> SubCategoría</label>
                            <select name="subcateg" id="ep-subcateg" class="form-select form-select-sm">
                                <option value="">— Seleccionar —</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-2 mb-2">
                        <div class="col-md-3">
                            <label class="ep-lbl">Matr. Nacional (MATRIMED)</label>
                            <input type="text" name="matrimed" id="ep-matrimed"
                                   class="form-control form-control-sm font-monospace">
                        </div>
                        <div class="col-md-3">
                            <label class="ep-lbl">Matr. Provincial</label>
                            <input type="text" name="matriprov" id="ep-matriprov"
                                   class="form-control form-control-sm font-monospace">
                        </div>
                        <div class="col-md-2">
                            <label class="ep-lbl">F. Alta</label>
                            <input type="date" name="fechaalta" id="ep-fechaalta"
                                   class="form-control form-control-sm">
                        </div>
                        <div class="col-md-2">
                            <label class="ep-lbl"><span class="text-danger">*</span> F. Baja</label>
                            <input type="date" name="fechabaja" id="ep-fechabaja"
                                   class="form-control form-control-sm">
                        </div>
                        <div class="col-md-2">
                            <label class="ep-lbl"><span class="text-danger">*</span> Convenio</label>
                            <select name="convenio" id="ep-convenio" class="form-select form-select-sm">
                                <option value="">— Seleccionar —</option>
                                <option value="PROPIO">PROPIO</option>
                                <option value="OTRO">OTRO</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-2 mb-2">
                        <div class="col-md-6">
                            <label class="ep-lbl"><span class="text-danger">*</span> Título / Nombre</label>
                            <input type="text" name="nombre" id="ep-nombre"
                                   class="form-control form-control-sm fw-semibold text-uppercase"
                                   required maxlength="100">
                        </div>
                        <div class="col-md-6">
                            <label class="ep-lbl">Razón Social / Nombre Fantasía</label>
                            <input type="text" name="nomfantas" id="ep-nomfantas"
                                   class="form-control form-control-sm" maxlength="100">
                        </div>
                    </div>

                    <div class="row g-2 mb-2">
                        <div class="col-md-3">
                            <label class="ep-lbl"><span class="text-danger">*</span> Grupo Web</label>
                            <select name="grupoweb" id="ep-grupoweb" class="form-select form-select-sm">
                                <option value="">— Seleccionar —</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="ep-lbl"><span class="text-danger">*</span> Empresa</label>
                            <select name="empresa" id="ep-empresa" class="form-select form-select-sm">
                                <option value="">— Seleccionar —</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="ep-lbl">Horario 1</label>
                            <input type="text" name="horario1" id="ep-horario1"
                                   class="form-control form-control-sm" maxlength="40"
                                   placeholder="Ej: Lun-Vie 9:00-13:00">
                        </div>
                    </div>
                </div><!-- /tab-id -->

                <!-- ══════════ TAB 2: DIRECCIÓN ══════════ -->
                <div class="tab-pane fade" id="ep-pane-dir" role="tabpanel">
                    <div class="row g-2 mb-2">
                        <div class="col-md-6">
                            <label class="ep-lbl"><span class="text-danger">*</span> Dirección</label>
                            <input type="text" name="direcc" id="ep-direcc"
                                   class="form-control form-control-sm" maxlength="60"
                                   data-original="">
                        </div>
                        <div class="col-md-3">
                            <label class="ep-lbl"><span class="text-danger">*</span> Localidad</label>
                            <select name="localidad" id="ep-localidad" class="form-select form-select-sm">
                                <option value="">— Seleccionar —</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="ep-lbl"><span class="text-danger">*</span> Zona</label>
                            <select name="zona" id="ep-zona" class="form-select form-select-sm">
                                <option value="">— Seleccionar —</option>
                            </select>
                        </div>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-md-3">
                            <label class="ep-lbl">Teléfono Consultorio</label>
                            <input type="text" name="telcons" id="ep-telcons"
                                   class="form-control form-control-sm font-monospace" maxlength="70">
                        </div>
                        <div class="col-md-3">
                            <label class="ep-lbl">Celular</label>
                            <input type="text" name="celular" id="ep-celular"
                                   class="form-control form-control-sm font-monospace" maxlength="15">
                        </div>
                        <div class="col-md-3">
                            <label class="ep-lbl">Fax</label>
                            <input type="text" name="fax" id="ep-fax"
                                   class="form-control form-control-sm font-monospace" maxlength="12">
                        </div>
                        <div class="col-md-3">
                            <label class="ep-lbl">Contacto (Nombre)</label>
                            <input type="text" name="contacto" id="ep-contacto"
                                   class="form-control form-control-sm" maxlength="100">
                        </div>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-md-3">
                            <label class="ep-lbl">Banco</label>
                            <select name="banco" id="ep-banco" class="form-select form-select-sm">
                                <option value="">— Seleccionar —</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="ep-lbl">Sucursal</label>
                            <input type="text" name="sucursal" id="ep-sucursal"
                                   class="form-control form-control-sm" maxlength="3">
                        </div>
                        <div class="col-md-4">
                            <label class="ep-lbl"><span class="text-danger">*</span> C.B.U. (22 dígitos)</label>
                            <input type="text" name="cbu" id="ep-cbu"
                                   class="form-control form-control-sm font-monospace"
                                   maxlength="22" placeholder="22 dígitos exactos">
                        </div>
                    </div>
                </div><!-- /tab-dir -->

                <!-- ══════════ TAB 3: CONTACTO / MAILS ══════════ -->
                <div class="tab-pane fade" id="ep-pane-cont" role="tabpanel">
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="ep-lbl">Mail principal</label>
                            <input type="text" name="mail" id="ep-mail"
                                   class="form-control form-control-sm">
                        </div>
                        <div class="col-md-6">
                            <label class="ep-lbl">Mail Contrataciones</label>
                            <input type="text" name="mailcontra" id="ep-mailcontra"
                                   class="form-control form-control-sm">
                        </div>
                        <div class="col-md-6">
                            <label class="ep-lbl">Mail Vencimientos</label>
                            <input type="text" name="mail_venc" id="ep-mail-venc"
                                   class="form-control form-control-sm">
                        </div>
                        <div class="col-md-6">
                            <label class="ep-lbl">Mail Débitos</label>
                            <input type="text" name="mail_deb" id="ep-mail-deb"
                                   class="form-control form-control-sm">
                        </div>
                        <div class="col-md-6">
                            <label class="ep-lbl">Mail Pagos</label>
                            <input type="text" name="mail_pago" id="ep-mail-pago"
                                   class="form-control form-control-sm">
                        </div>
                        <div class="col-md-6">
                            <label class="ep-lbl">Mail Autorizaciones</label>
                            <input type="text" name="mail_auto" id="ep-mail-auto"
                                   class="form-control form-control-sm">
                        </div>
                    </div>
                    <div class="form-text" style="font-size:.73rem;">
                        <i class="fa-solid fa-circle-info me-1 text-info"></i>
                        Separá múltiples correos con coma (,).
                    </div>
                </div><!-- /tab-cont -->

                <!-- ══════════ TAB 4: ESTADO ══════════ -->
                <div class="tab-pane fade" id="ep-pane-estado" role="tabpanel">
                    <div class="row g-2 mb-2">
                        <!-- SSS -->
                        <div class="col-md-3">
                            <div class="border rounded p-2 bg-light" style="font-size:.8rem;">
                                <div class="form-check mb-1">
                                    <input class="form-check-input" type="checkbox"
                                           name="sss" id="ep-sss" value="1">
                                    <label class="form-check-label fw-semibold" for="ep-sss">S.S.S. al día</label>
                                </div>
                                <label class="ep-lbl">Vence:</label>
                                <input type="date" name="sssvenc" id="ep-sssvenc"
                                       class="form-control form-control-sm mt-1">
                            </div>
                        </div>
                        <!-- Seguro -->
                        <div class="col-md-3">
                            <div class="border rounded p-2 bg-light" style="font-size:.8rem;">
                                <div class="form-check mb-1">
                                    <input class="form-check-input" type="checkbox"
                                           name="seguro" id="ep-seguro" value="1">
                                    <label class="form-check-label fw-semibold" for="ep-seguro">Seguro M. Praxis</label>
                                </div>
                                <label class="ep-lbl">Vence:</label>
                                <input type="date" name="segurovenc" id="ep-segurovenc"
                                       class="form-control form-control-sm mt-1">
                            </div>
                        </div>
                        <!-- Checkboxes -->
                        <div class="col-md-6">
                            <label class="ep-lbl d-block mb-1">Marcadores</label>
                            <div class="border rounded p-2 bg-light d-flex flex-wrap gap-3" style="font-size:.8rem;">
                                <div class="form-check mb-0">
                                    <input class="form-check-input" type="checkbox"
                                           name="issuspend" id="ep-issuspend" value="1">
                                    <label class="form-check-label text-danger fw-semibold" for="ep-issuspend">Suspendido</label>
                                </div>
                                <div class="form-check mb-0">
                                    <input class="form-check-input" type="checkbox"
                                           name="exclucart" id="ep-exclucart" value="1">
                                    <label class="form-check-label" for="ep-exclucart">Excl. Cartilla</label>
                                </div>
                                <div class="form-check mb-0">
                                    <input class="form-check-input" type="checkbox"
                                           name="exclucall" id="ep-exclucall" value="1">
                                    <label class="form-check-label" for="ep-exclucall">Excl. Call</label>
                                </div>
                                <div class="form-check mb-0">
                                    <input class="form-check-input" type="checkbox"
                                           name="isoncologo" id="ep-isoncologo" value="1">
                                    <label class="form-check-label text-primary fw-semibold" for="ep-isoncologo">Der. Oncológico</label>
                                </div>
                                <div class="form-check mb-0">
                                    <input class="form-check-input" type="checkbox"
                                           name="conflictivo" id="ep-conflictivo" value="1">
                                    <label class="form-check-label text-warning" for="ep-conflictivo">Conflictivo</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- MOTIVOBJ: visible sólo cuando la baja es pasada -->
                    <div id="ep-motivobj-wrap" class="row g-2 mb-2" style="display:none;">
                        <div class="col-12">
                            <div class="alert alert-warning py-2 px-3 mb-2" style="font-size:.8rem;">
                                <i class="fa-solid fa-triangle-exclamation me-1"></i>
                                La fecha de baja es pasada. <strong>El motivo de baja es obligatorio.</strong>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="ep-lbl"><span class="text-danger">*</span> Motivo de baja</label>
                            <input type="text" name="motivobj" id="ep-motivobj"
                                   class="form-control form-control-sm" maxlength="250"
                                   placeholder="Describa brevemente el motivo…">
                        </div>
                    </div>
                </div><!-- /tab-estado -->

                <!-- ══════════ TAB 5: ADJUNTOS ══════════ -->
                <div class="tab-pane fade" id="ep-pane-adj" role="tabpanel">
                    <div id="ep-dropzone"
                         class="text-center p-4 mb-3 rounded-3"
                         style="border:2px dashed #6ea8fe;background:#f0f6ff;cursor:pointer;">
                        <i class="fa-solid fa-cloud-arrow-up fa-2x text-primary mb-2 d-block" style="opacity:.6;"></i>
                        <p class="mb-1 fw-semibold" style="font-size:.82rem;">Arrastrá o hacé clic para seleccionar</p>
                        <p class="text-muted mb-0" style="font-size:.73rem;">PDF, DOC, XLS, JPG, MSG — máx 10 MB</p>
                        <input type="file" id="ep-file-input" class="d-none"
                               accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.msg">
                    </div>
                    <div id="ep-adj-upload-status" class="mb-2" style="display:none;font-size:.8rem;"></div>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover table-bordered mb-0" style="font-size:.8rem;">
                            <thead style="background:#e8edf5;font-size:.76rem;">
                                <tr>
                                    <th style="padding:6px 10px;">Fecha</th>
                                    <th style="padding:6px 10px;">Archivo</th>
                                    <th style="padding:6px 10px;text-align:center;">Tamaño</th>
                                    <th style="padding:6px 10px;">Usuario</th>
                                    <th style="width:70px;padding:6px 10px;text-align:center;">↓</th>
                                </tr>
                            </thead>
                            <tbody id="ep-adj-tbody">
                                <tr><td colspan="5" class="text-center text-muted py-3">Cargando…</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div><!-- /tab-adj -->

            </div><!-- /.tab-content -->
            </form>
            </div><!-- /#ep-form-wrap -->

        </div><!-- /.modal-body -->

        <!-- ── Alert ──────────────────────────────────────────────────── -->
        <div id="ep-alert" class="alert alert-dismissible mx-4 mb-0 py-2 px-3"
             role="alert" style="display:none;font-size:.82rem;border-radius:8px;">
            <span id="ep-alert-msg"></span>
            <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
        </div>

        <!-- ── Footer ─────────────────────────────────────────────────── -->
        <div class="modal-footer py-2 px-3 gap-1 flex-wrap"
             style="background:#f5f7fb;border-top:1px solid #dee2e6;">

            <!-- Botones de módulos relacionados (izquierda) -->
            <div class="d-flex gap-1 flex-wrap me-auto" id="ep-btns-modulos" style="display:none!important;">
                <button type="button" id="ep-btn-obs"
                        class="btn btn-sm btn-outline-secondary"
                        style="font-size:.75rem;" title="Observaciones del prestador">
                    <i class="fa-solid fa-note-sticky me-1"></i>Observaciones
                </button>
                <button type="button" id="ep-btn-infoliq"
                        class="btn btn-sm btn-outline-secondary"
                        style="font-size:.75rem;" title="Info de liquidaciones">
                    <i class="fa-solid fa-file-invoice-dollar me-1"></i>Info Liq.
                </button>
                <span style="border-left:1px solid #dee2e6;height:24px;align-self:center;margin:0 2px;"></span>
                <button type="button" id="ep-btn-os"
                        class="btn btn-sm btn-outline-info"
                        style="font-size:.75rem;" title="Obras Sociales Habilitadas">
                    <i class="fa-solid fa-building-columns me-1"></i>O.S.
                </button>
                <button type="button" id="ep-btn-suc"
                        class="btn btn-sm btn-outline-secondary"
                        style="font-size:.75rem;" title="Sucursales">
                    <i class="fa-solid fa-map-location-dot me-1"></i>Sucursales
                </button>
                <button type="button" id="ep-btn-prest"
                        class="btn btn-sm btn-outline-secondary disabled"
                        style="font-size:.75rem;" title="Prestaciones">
                    <i class="fa-solid fa-syringe me-1"></i>Prestaciones
                </button>
            </div>

            <!-- Guardar / Cancelar (derecha) -->
            <button type="button" id="ep-btn-cancelar"
                    class="btn btn-sm btn-secondary" data-bs-dismiss="modal">
                <i class="fa-solid fa-xmark me-1"></i>Cancelar
            </button>
            <button type="button" id="ep-btn-guardar" class="btn btn-sm btn-primary">
                <i class="fa-solid fa-floppy-disk me-1"></i>Guardar
            </button>
        </div>

    </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div>

<!-- ── Mini-modal: Observaciones ─────────────────────────────────────── -->
<div class="modal fade" id="modalObservaciones" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content" style="border-radius:10px;overflow:hidden;">
        <div class="modal-header py-2 px-4" style="background:#37474f;color:#fff;border-bottom:none;">
            <h6 class="modal-title fw-bold mb-0">
                <i class="fa-solid fa-note-sticky me-2"></i>Observaciones
            </h6>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-3">
            <div id="obs-last-update" class="text-muted mb-2" style="font-size:.75rem;"></div>
            <textarea id="obs-memo" class="form-control" rows="8"
                      style="font-size:.83rem;resize:vertical;"
                      placeholder="Escriba sus observaciones aquí…"></textarea>
        </div>
        <div class="modal-footer py-2 px-3">
            <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="button" id="obs-btn-guardar" class="btn btn-sm btn-primary">
                <i class="fa-solid fa-floppy-disk me-1"></i>Guardar
            </button>
        </div>
    </div>
    </div>
</div>

<!-- ── Mini-modal: Info Liquidaciones ────────────────────────────────── -->
<div class="modal fade" id="modalInfoLiq" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content" style="border-radius:10px;overflow:hidden;">
        <div class="modal-header py-2 px-4" style="background:#37474f;color:#fff;border-bottom:none;">
            <h6 class="modal-title fw-bold mb-0">
                <i class="fa-solid fa-file-invoice-dollar me-2"></i>Info Liquidaciones
            </h6>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-3">
            <div id="infoliq-last-update" class="text-muted mb-2" style="font-size:.75rem;"></div>
            <textarea id="infoliq-memo" class="form-control" rows="8"
                      style="font-size:.83rem;resize:vertical;"
                      placeholder="Información sobre liquidaciones…"></textarea>
        </div>
        <div class="modal-footer py-2 px-3">
            <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="button" id="infoliq-btn-guardar" class="btn btn-sm btn-primary">
                <i class="fa-solid fa-floppy-disk me-1"></i>Guardar
            </button>
        </div>
    </div>
    </div>
</div>

<style>
.ep-lbl { font-size:.76rem; font-weight:600; margin-bottom:2px; color:#495057; }
#ep-dropzone.drag-over { background:#cfe2ff!important; border-color:#0d6efd!important; }
</style>

<!-- ═══════════════════════════════════════════════════════════════════════
     JAVASCRIPT
═══════════════════════════════════════════════════════════════════════ -->
<script>
(function () {
'use strict';

/* ── Estado ─────────────────────────────────────────────────────────── */
var _codigo    = '';
var _nombre    = '';
var _baseUrl   = 'index.php';
var _csrf      = <?= json_encode($csrfToken) ?>;
var _catalogo  = null;  // cache de los combos
var _dirOriginal = ''; // para detectar cambio de dirección

/* ── DOM refs ────────────────────────────────────────────────────────── */
var $ = function (id) { return document.getElementById(id); };
var $modal      = $('modalEditarPrestador');
var $loading    = $('ep-loading');
var $formWrap   = $('ep-form-wrap');
var $form       = $('ep-form');
var $alert      = $('ep-alert');
var $alertMsg   = $('ep-alert-msg');
var $btnGuardar = $('ep-btn-guardar');
var $adjBadge   = $('ep-adj-badge');
var $adjTbody   = $('ep-adj-tbody');
var $dropzone   = $('ep-dropzone');
var $fileInput  = $('ep-file-input');
var $uploadSt   = $('ep-adj-upload-status');
var $btnsMod    = $('ep-btns-modulos');

/* ══════════════════════════════════════════════════════════════════════
 * APERTURA: MODIFICAR
 * ════════════════════════════════════════════════════════════════════ */
window.abrirModalEditarPrestador = function (codigo, nombre) {
    _codigo = String(codigo || '').trim();
    _nombre = String(nombre || '').trim();
    if (!_codigo) { alert('Seleccione un prestador.'); return; }

    prepararModal('Modificar', _codigo, _nombre, 'modificar');
    bootstrap.Modal.getOrCreateInstance($modal).show();

    // Cargar catálogo PRIMERO (opciones deben existir antes de set values),
    // luego los datos del prestador — así rellenarFormulario encuentra las opciones
    cargarCatalogo()
        .then(function () { return cargarDatos(); })
        .then(function () {
            $loading.style.display  = 'none';
            $formWrap.style.display = 'block';
            mostrarBotonesModulos(true);
        });
    cargarAdjuntos();
};

/* ══════════════════════════════════════════════════════════════════════
 * APERTURA: NUEVO
 * ════════════════════════════════════════════════════════════════════ */
window.abrirModalNuevoPrestador = function () {
    _codigo = '';
    _nombre = '';

    prepararModal('Nuevo Prestador', '—', '', 'nuevo');

    // Catálogo primero, luego defaults
    cargarCatalogo()
        .then(function () { return cargarDefaults(); })
        .then(function () {
            $loading.style.display  = 'none';
            $formWrap.style.display = 'block';
            mostrarBotonesModulos(false);
        });

    bootstrap.Modal.getOrCreateInstance($modal).show();
    $adjBadge.textContent = '0';
    $adjTbody.innerHTML   = '<tr><td colspan="5" class="text-center text-muted py-2" style="font-size:.8rem;">Guarde primero para adjuntar archivos.</td></tr>';
};

/* ── Inicializar UI común ─────────────────────────────────────────── */
function prepararModal(accion, codLabel, nomLabel, mode) {
    ocultarAlert();
    $loading.style.display  = 'block';
    $formWrap.style.display = 'none';
    $('ep-header-accion').textContent = accion;
    $('ep-header-codigo').textContent = codLabel;
    $('ep-header-nombre').textContent = nomLabel;
    $('ep-accion').value  = mode;
    $('ep-codigo').value  = _codigo;
    bootstrap.Tab.getOrCreateInstance($('ep-tab-id')).show();
    $form.querySelectorAll('input:not([type=hidden]),select,textarea').forEach(function (el) {
        if (el.type === 'checkbox') el.checked = false;
        else el.value = '';
    });
    $('ep-motivobj-wrap').style.display = 'none';
}

/* ── Catálogo (con cache) ─────────────────────────────────────────── */
function cargarCatalogo() {
    if (_catalogo) { poblarCombos(_catalogo); return Promise.resolve(); }
    return fetch(_baseUrl + '?route=prestadores&action=prestador_catalogo', {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function (r) { return r.json(); })
    .then(function (res) {
        if (!res.ok) return;
        _catalogo = res;
        poblarCombos(res);
    })
    .catch(function () {});
}

function poblarCombos(c) {
    // helper: llena un <select> preservando la selección actual
    function llenar(id, items, valKey, lblKey, blankText) {
        var sel = $(id);
        if (!sel) return;
        var cur = sel.value;
        sel.innerHTML = '<option value="">' + blankText + '</option>';
        (items || []).forEach(function (it) {
            var v = String(it[valKey] || '').trim();
            var l = String(it[lblKey] || it[valKey] || '').trim();
            if (!v) return;
            var opt = document.createElement('option');
            opt.value = v;
            // Si descripción es igual al valor, no la duplicamos
            opt.textContent = (l && l !== v) ? v + ' — ' + l : v;
            if (v === cur) opt.selected = true;
            sel.appendChild(opt);
        });
    }
    llenar('ep-categ',    c.categorias,  'clave',    'descripcion', '— Categoría —');
    // SUBCATEG y GRUPOWEB: el valor almacenado en ebamp ES la descripción
    llenar('ep-subcateg', c.grsub,       'TADESCRIP','TADESCRIP',   '— SubCategoría —');
    llenar('ep-grupoweb', c.grupoweb,    'clave',    'descripcion', '— Grupo Web —');
    llenar('ep-empresa',  c.empresas,    'clave',    'descripcion', '— Empresa —');
    llenar('ep-banco',    c.bancos,      'clave',    'descripcion', '— Banco —');

    // Localidades y zonas
    llenarLocalidades(c.localidades, c.zonas);
}

function llenarLocalidades(locs, zonas, filtroZona) {
    var selLoc  = $('ep-localidad');
    var selZona = $('ep-zona');
    var curLoc  = selLoc.value;
    var curZona = selZona.value;

    // Zonas
    selZona.innerHTML = '<option value="">— Zona —</option>';
    (zonas || []).forEach(function (z) {
        var opt = document.createElement('option');
        opt.value = String(z.TADESCRIP || '').trim();
        opt.textContent = String(z.TADESCRIP || '').trim();
        if (opt.value === curZona) opt.selected = true;
        selZona.appendChild(opt);
    });

    // Localidades (filtradas si hay zona Capital)
    selLoc.innerHTML = '<option value="">— Localidad —</option>';
    (locs || []).forEach(function (l) {
        var desc = String(l.TADESCRIP || '').trim();
        var prov = String(l.TAPROVIN  || '').trim();
        if (!desc) return;
        // Si filtro activo y no coincide, omitir
        if (filtroZona && prov.toLowerCase() !== filtroZona.toLowerCase()) return;
        var opt = document.createElement('option');
        opt.value          = desc;
        opt.textContent    = desc;
        opt.dataset.provin = prov;
        if (desc === curLoc) opt.selected = true;
        selLoc.appendChild(opt);
    });
}

/* ── Defaults para nuevo ──────────────────────────────────────────── */
function cargarDefaults() {
    return fetch(_baseUrl + '?route=prestadores&action=prestador_defaults', {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function (r) { return r.json(); })
    .then(function (res) {
        if (!res.ok) return;
        $('ep-codigo-display').value = res.matricula;
        $('ep-codigo').value         = res.matricula;
        $('ep-legajo').value         = res.legajo;
        $('ep-idweb').value          = res.idweb;
        $('ep-fechaalta').value      = res.fechaalta;
        $('ep-fechabaja').value      = res.fechabaja;
        // Defaults fijos
        $('ep-grupoweb').value = 'NO WEB';
        // Intentar seleccionar opción COME en empresa
        var selEmp = $('ep-empresa');
        for (var i = 0; i < selEmp.options.length; i++) {
            if (selEmp.options[i].value === 'COME') { selEmp.selectedIndex = i; break; }
        }
        $('ep-convenio').value = 'PROPIO';
    })
    .catch(function () {});
}

/* ── Cargar datos (modificar) ─────────────────────────────────────── */
function cargarDatos() {
    return fetch(_baseUrl + '?route=prestadores&action=prestador_get&codigo=' + encodeURIComponent(_codigo), {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function (r) { return r.json(); })
    .then(function (res) {
        if (!res.ok) {
            mostrarAlert('danger', res.error || 'Error al cargar datos.');
            return;
        }
        rellenarFormulario(res.data);
    })
    .catch(function (err) {
        mostrarAlert('danger', 'Error de comunicación al cargar datos.');
        console.error(err);
    });
}

/* ── Rellenar formulario ──────────────────────────────────────────── */
function rellenarFormulario(d) {
    // Helper
    function set(id, key, fallback) {
        var el = $(id); if (!el) return;
        var val = d[key] !== undefined ? d[key] : (fallback !== undefined ? fallback : '');
        if (el.type === 'checkbox') {
            el.checked = (val=='1'||val=='S'||val=='Y'||val==true||val=='T');
        } else if (el.type === 'date') {
            el.value = val ? String(val).substring(0, 10) : '';
        } else {
            el.value = val !== null ? String(val) : '';
        }
    }

    // Tab 1
    $('ep-codigo-display').value = d['CODIGO'] || _codigo;
    $('ep-codigo').value         = d['CODIGO'] || _codigo;
    $('ep-idweb').value          = d['IDWEB']  || '0';
    set('ep-cuit',      'CUIT');
    set('ep-legajo',    'LEGAJO');
    set('ep-categ',     'CATEG');
    set('ep-subcateg',  'SUBCATEG');
    set('ep-convenio',  'CONVENIO');
    set('ep-matrimed',  'MATRIMED',  d['MATRICULA']);
    set('ep-matriprov', 'MATRIPROV');
    set('ep-fechaalta', 'FECHAALTA');
    set('ep-fechabaja', 'FECHABAJA');
    set('ep-grupoweb',  'GRUPOWEB');
    set('ep-empresa',   'EMPRESA');
    set('ep-horario1',  'HORARIO1');
    set('ep-nombre',    'NOMBRE');
    set('ep-nomfantas', 'NOMFANTAS');
    // Tab 2
    set('ep-direcc',    'DIRECC');
    set('ep-localidad', 'LOCALIDAD');
    set('ep-zona',      'ZONA');
    set('ep-telcons',   'TELCONS');
    set('ep-celular',   'CELULAR');
    set('ep-fax',       'FAX');
    set('ep-contacto',  'CONTACTO');
    set('ep-banco',     'BANCO');
    set('ep-sucursal',  'SUCURSAL');
    set('ep-cbu',       'CBU');
    // Tab 3
    set('ep-mail',      'MAIL');
    set('ep-mail-venc', 'MAIL_VENC');
    set('ep-mail-deb',  'MAIL_DEB');
    set('ep-mail-pago', 'MAIL_PAGO');
    set('ep-mail-auto', 'MAIL_AUTO');
    set('ep-mailcontra','MAILCONTRA');
    // Tab 4
    set('ep-sss',       'SSS');
    set('ep-sssvenc',   'SSSVENC');
    set('ep-seguro',    'SEGURO');
    set('ep-segurovenc','SEGUROVENC');
    set('ep-issuspend', 'ISSUSPEND');
    set('ep-exclucart', 'EXCLUCART');
    set('ep-exclucall', 'EXCLUCALL');
    set('ep-isoncologo','ISONCOLOGO');
    set('ep-conflictivo','CONFLICTO');
    set('ep-motivobj',  'MOTIVOBJ');

    // Guardar dirección original para detect-change
    _dirOriginal = ($('ep-direcc').value || '').trim();

    // Mostrar/ocultar MOTIVOBJ según baja
    verificarMotivobj();

    // Actualizar header
    $('ep-header-nombre').textContent = d['NOMBRE'] || '';
    $('ep-header-codigo').textContent = d['CODIGO'] || _codigo;
}

/* ── Mostrar/ocultar MOTIVOBJ ─────────────────────────────────────── */
function verificarMotivobj() {
    var fbVal = $('ep-fechabaja').value;
    if (!fbVal) { $('ep-motivobj-wrap').style.display = 'none'; return; }
    var hoy = new Date(); hoy.setHours(0,0,0,0);
    var fb  = new Date(fbVal + 'T00:00:00');
    $('ep-motivobj-wrap').style.display = (fb <= hoy) ? '' : 'none';
}

$('ep-fechabaja').addEventListener('change', verificarMotivobj);

/* ── Cascada CATEG → CONVENIO (ADEF → forzar OTRO) ──────────────── */
$('ep-categ').addEventListener('change', function () {
    var convenioSel = $('ep-convenio');
    if (this.value === 'ADEF') {
        convenioSel.value = 'OTRO';
        convenioSel.disabled = true;
    } else {
        convenioSel.disabled = false;
    }
});

/* ── Cascada LOCALIDAD → ZONA (TAPROVIN) ─────────────────────────── */
$('ep-localidad').addEventListener('change', function () {
    var opt = this.options[this.selectedIndex];
    var prov = opt ? (opt.dataset.provin || '') : '';
    if (prov) { $('ep-zona').value = prov; }
});

/* ── Excl.Cartilla ↔ Suspendido ──────────────────────────────────── */
$('ep-exclucart').addEventListener('change', function () {
    if (this.checked) {
        if (confirm('¿Marcar también al prestador como SUSPENDIDO?')) $('ep-issuspend').checked = true;
    } else if ($('ep-issuspend').checked) {
        if (confirm('El prestador figura SUSPENDIDO. ¿Quitar la suspensión?')) $('ep-issuspend').checked = false;
    }
});

/* ══════════════════════════════════════════════════════════════════════
 * GUARDAR (validaciones VFP + envío AJAX)
 * ════════════════════════════════════════════════════════════════════ */
$btnGuardar.addEventListener('click', function () {
    var V = {
        cuit:      ($('ep-cuit').value      || '').trim(),
        subcateg:  ($('ep-subcateg').value  || '').trim(),
        empresa:   ($('ep-empresa').value   || '').trim(),
        categ:     ($('ep-categ').value     || '').trim(),
        localidad: ($('ep-localidad').value || '').trim(),
        zona:      ($('ep-zona').value      || '').trim(),
        cbu:       ($('ep-cbu').value       || '').trim(),
        nombre:    ($('ep-nombre').value    || '').trim(),
        direcc:    ($('ep-direcc').value    || '').trim(),
        grupoweb:  ($('ep-grupoweb').value  || '').trim(),
        fechabaja: ($('ep-fechabaja').value || '').trim(),
        convenio:  ($('ep-convenio').value  || '').trim(),
        matrimed:  ($('ep-matrimed').value  || '').trim(),
        matriprov: ($('ep-matriprov').value || '').trim(),
        motivobj:  ($('ep-motivobj').value  || '').trim(),
        isoncologo:$('ep-isoncologo').checked,
    };

    // ─── Reglas de campo vacío ──────────────────────────────────────
    function req(val, msg, tabId) {
        if (!val) {
            if (tabId) bootstrap.Tab.getOrCreateInstance($(tabId)).show();
            mostrarAlert('warning', '<i class="fa-solid fa-triangle-exclamation me-1"></i>' + msg);
            return false;
        }
        return true;
    }
    // Sólo NOMBRE y CATEG son obligatorios en ambos modos
    if (!req(V.nombre, 'NOMBRE no puede quedar vacío.',    'ep-tab-id')) return;
    if (!req(V.categ,  'CATEGORÍA no puede quedar vacía.', 'ep-tab-id')) return;

    // Para alta: más campos obligatorios
    if ($('ep-accion').value === 'nuevo') {
        if (!req(V.cuit,      'CUIT no puede quedar vacío.',          'ep-tab-id'))  return;
        if (!req(V.subcateg,  'SUBCATEGORÍA no puede quedar vacía.',  'ep-tab-id'))  return;
        if (!req(V.empresa,   'EMPRESA no puede quedar vacía.',       'ep-tab-id'))  return;
        if (!req(V.grupoweb,  'GRUPO WEB no puede quedar vacío.',     'ep-tab-id'))  return;
        if (!req(V.convenio,  'CONVENIO no puede quedar vacío.',      'ep-tab-id'))  return;
        if (!req(V.fechabaja, 'FECHA BAJA no puede quedar vacía.',    'ep-tab-id'))  return;
        if (!req(V.direcc,    'DIRECCIÓN no puede quedar vacía.',     'ep-tab-id'))  return;
        if (!req(V.localidad, 'LOCALIDAD no puede quedar vacía.',     'ep-tab-dir')) return;
        if (!req(V.zona,      'ZONA no puede quedar vacía.',          'ep-tab-dir')) return;
    }

    // CBU: si tiene valor, validar longitud exacta
    if (V.cbu.length > 0 && V.cbu.length !== 22) {
        bootstrap.Tab.getOrCreateInstance($('ep-tab-dir')).show();
        mostrarAlert('warning', 'CBU debe tener exactamente 22 dígitos (tiene ' + V.cbu.length + ').'); return;
    }

    // ─── CUIT no puede ser 000... para PROPIO ───────────────────────
    if (V.cuit.substring(0, 4) === '0000' && V.convenio === 'PROPIO') {
        bootstrap.Tab.getOrCreateInstance($('ep-tab-id')).show();
        mostrarAlert('warning', 'CUIT NO puede ser 0000XXXX para convenio PROPIO.'); return;
    }

    // ─── CP: matrícula obligatoria ──────────────────────────────────
    if (V.categ === 'CP') {
        if (!V.matrimed && !V.matriprov) {
            bootstrap.Tab.getOrCreateInstance($('ep-tab-id')).show();
            mostrarAlert('warning', 'Para categoría CP la matrícula Nac. o Prov. es obligatoria.'); return;
        }
        if (V.matrimed.substring(0, 4) === '0000' || V.matriprov.substring(0, 4) === '0000') {
            bootstrap.Tab.getOrCreateInstance($('ep-tab-id')).show();
            mostrarAlert('warning', 'Matrícula no puede comenzar con 0000 para categoría CP.'); return;
        }
    }

    // ─── MOTIVOBJ requerido si baja pasada ──────────────────────────
    var fbDate = V.fechabaja ? new Date(V.fechabaja + 'T00:00:00') : null;
    var hoy    = new Date(); hoy.setHours(0,0,0,0);
    if (fbDate && fbDate <= hoy && !V.motivobj) {
        bootstrap.Tab.getOrCreateInstance($('ep-tab-estado')).show();
        mostrarAlert('warning', 'El motivo de baja es obligatorio cuando la fecha es pasada.'); return;
    }

    // ─── Confirm: Oncólogo (sólo en alta) ─────────────────────────
    if ($('ep-accion').value === 'nuevo' && !V.isoncologo) {
        if (!confirm('¿Verificó si el prestador es oncológico?\n\nContinúe con "Aceptar" si está seguro.')) return;
    }

    // ─── Confirm: Convenio (sólo en alta) ──────────────────────────
    if ($('ep-accion').value === 'nuevo') {
        if (!confirm('¿Verificó que el CONVENIO seleccionado (' + V.convenio + ') es correcto?')) return;
    }

    // ─── Confirm: Dirección cambiada ────────────────────────────────
    if ($('ep-accion').value === 'modificar' && _dirOriginal && V.direcc !== _dirOriginal) {
        if (!confirm('Modificó la DIRECCIÓN.\n¿Verificó la Localidad y Zona correspondiente?')) return;
    }

    // ─── Enviar ──────────────────────────────────────────────────────
    setBusy(true);
    ocultarAlert();
    var fd = new FormData($form);
    fd.set('csrf_token', _csrf);

    fetch(_baseUrl + '?route=prestadores&action=prestador_guardar', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: fd,
    })
    .then(function (r) { return r.json(); })
    .then(function (res) {
        setBusy(false);
        if (res.ok) {
            // Cerrar modal y recargar grilla
            bootstrap.Modal.getInstance($modal)?.hide();
            if (typeof window.recargarGrillaPrestadores === 'function') {
                window.recargarGrillaPrestadores();
            }
        } else {
            mostrarAlert('danger', res.error || 'Error al guardar.');
        }
    })
    .catch(function (err) {
        setBusy(false);
        mostrarAlert('danger', 'Error de comunicación con el servidor.');
        console.error(err);
    });
});

/* ── Botones de módulos (OS, Obs, InfoLiq) ────────────────────────── */
function mostrarBotonesModulos(visible) {
    $btnsMod.style.display = visible ? 'flex' : 'none';
}

$('ep-btn-suc').addEventListener('click', function () {
    if (!_codigo) return;
    var nom = $('ep-nombre').value || _nombre;
    bootstrap.Modal.getInstance($modal)?.hide();
    setTimeout(function () {
        if (typeof window.abrirModalSucursales === 'function') {
            window.abrirModalSucursales(_codigo, nom);
        }
    }, 350);
});

$('ep-btn-os').addEventListener('click', function () {
    if (!_codigo) return;
    var nom = $('ep-nombre').value || _nombre;
    bootstrap.Modal.getInstance($modal)?.hide();
    setTimeout(function () {
        if (typeof window.abrirModalObrasSociales === 'function') {
            window.abrirModalObrasSociales(_codigo, nom);
        }
    }, 350);
});

$('ep-btn-obs').addEventListener('click', function () {
    abrirObs();
});
$('ep-btn-infoliq').addEventListener('click', function () {
    abrirInfoLiq();
});

/* ─── Observaciones ──────────────────────────────────────────────── */
function abrirObs() {
    $('obs-last-update').textContent = '';
    $('obs-memo').value = '';
    bootstrap.Modal.getOrCreateInstance($('modalObservaciones')).show();
    fetch(_baseUrl + '?route=prestadores&action=obs_get&codigo=' + encodeURIComponent(_codigo), {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function (r) { return r.json(); })
    .then(function (res) {
        if (res.ok) {
            $('obs-memo').value = res.memo || '';
            $('obs-last-update').textContent = res.fecha ? 'Última modificación: ' + res.fecha : '';
        }
    }).catch(function () {});
}
$('obs-btn-guardar').addEventListener('click', function () {
    var fd = new FormData();
    fd.append('csrf_token', _csrf);
    fd.append('codigo', _codigo);
    fd.append('memo', $('obs-memo').value);
    fetch(_baseUrl + '?route=prestadores&action=obs_save', {
        method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: fd
    })
    .then(function (r) { return r.json(); })
    .then(function (res) {
        if (res.ok) {
            bootstrap.Modal.getInstance($('modalObservaciones'))?.hide();
        } else {
            alert(res.error || 'Error al guardar observaciones.');
        }
    }).catch(function () { alert('Error de comunicación.'); });
});

/* ─── Info Liquidaciones ─────────────────────────────────────────── */
function abrirInfoLiq() {
    $('infoliq-last-update').textContent = '';
    $('infoliq-memo').value = '';
    bootstrap.Modal.getOrCreateInstance($('modalInfoLiq')).show();
    fetch(_baseUrl + '?route=prestadores&action=infoliq_get&codigo=' + encodeURIComponent(_codigo), {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function (r) { return r.json(); })
    .then(function (res) {
        if (res.ok) {
            $('infoliq-memo').value = res.memo || '';
            $('infoliq-last-update').textContent = res.fecha ? 'Última modificación: ' + res.fecha : '';
        }
    }).catch(function () {});
}
$('infoliq-btn-guardar').addEventListener('click', function () {
    var fd = new FormData();
    fd.append('csrf_token', _csrf);
    fd.append('codigo', _codigo);
    fd.append('memo', $('infoliq-memo').value);
    fetch(_baseUrl + '?route=prestadores&action=infoliq_save', {
        method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: fd
    })
    .then(function (r) { return r.json(); })
    .then(function (res) {
        if (res.ok) {
            bootstrap.Modal.getInstance($('modalInfoLiq'))?.hide();
        } else {
            alert(res.error || 'Error al guardar.');
        }
    }).catch(function () { alert('Error de comunicación.'); });
});

/* ═══════════════════════════════════════════════════════════════════
 * ADJUNTOS
 * ═════════════════════════════════════════════════════════════════ */
function cargarAdjuntos() {
    if (!_codigo) return;
    fetch(_baseUrl + '?route=prestadores&action=adj_listar&codigo=' + encodeURIComponent(_codigo), {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function (r) { return r.json(); })
    .then(function (res) { renderAdjuntos(res.data || []); })
    .catch(function () {
        $adjTbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">Error.</td></tr>';
    });
}

function renderAdjuntos(lista) {
    $adjBadge.textContent = lista.length;
    if (!lista.length) {
        $adjTbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-2" style="font-size:.8rem;">Sin adjuntos.</td></tr>';
        return;
    }
    var html = '';
    lista.forEach(function (a) {
        var nom  = (a.CORUTA || '').split('\\').pop() || '—';
        var tam  = a.COTAMANO ? fmtBytes(parseInt(a.COTAMANO, 10)) : '—';
        var fec  = a.COFECHA  ? a.COFECHA.substring(0,10).split('-').reverse().join('/') : '—';
        html += '<tr>'
            + '<td style="padding:5px 10px;">'  + esc(fec)  + '</td>'
            + '<td style="padding:5px 10px;max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="' + esc(nom) + '">'
            +   '<i class="fa-solid fa-file me-1 text-muted"></i>' + esc(nom) + '</td>'
            + '<td style="padding:5px 10px;text-align:center;">' + tam + '</td>'
            + '<td style="padding:5px 10px;">'  + esc(a.COUSUARIO || a.user || '—') + '</td>'
            + '<td style="padding:5px 10px;text-align:center;">'
            +   '<a href="contratos/' + encodeURIComponent(nom) + '" target="_blank"'
            +      ' class="btn btn-sm btn-outline-primary py-0 px-2"><i class="fa-solid fa-download"></i></a>'
            + '</td></tr>';
    });
    $adjTbody.innerHTML = html;
}

function subirArchivo(file) {
    if (file.size > 10 * 1024 * 1024) {
        mostrarUploadSt('danger', 'El archivo supera el límite de 10 MB.'); return;
    }
    mostrarUploadSt('info', '<span class="spinner-border spinner-border-sm me-1"></span>Subiendo «' + esc(file.name) + '»…');
    var fd = new FormData();
    fd.append('csrf_token', _csrf);
    fd.append('codigo', _codigo);
    fd.append('nombre_prestador', $('ep-nombre').value || '');
    fd.append('archivo', file);
    fetch(_baseUrl + '?route=prestadores&action=adj_subir', {
        method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: fd
    })
    .then(function (r) { return r.json(); })
    .then(function (res) {
        if (res.ok) { mostrarUploadSt('success', '«' + esc(file.name) + '» subido.'); cargarAdjuntos(); }
        else { mostrarUploadSt('danger', res.error || 'Error al subir.'); }
    })
    .catch(function () { mostrarUploadSt('danger', 'Error de comunicación.'); });
}

$dropzone.addEventListener('click', function () { $fileInput.click(); });
$fileInput.addEventListener('change', function () {
    if ($fileInput.files.length) subirArchivo($fileInput.files[0]);
    $fileInput.value = '';
});
$dropzone.addEventListener('dragover',  function (e) { e.preventDefault(); $dropzone.classList.add('drag-over'); });
$dropzone.addEventListener('dragleave', function ()  { $dropzone.classList.remove('drag-over'); });
$dropzone.addEventListener('drop', function (e) {
    e.preventDefault(); $dropzone.classList.remove('drag-over');
    if (e.dataTransfer.files.length) subirArchivo(e.dataTransfer.files[0]);
});

/* ── Helpers ─────────────────────────────────────────────────────── */
function setBusy(b) {
    $btnGuardar.disabled  = b;
    $btnGuardar.innerHTML = b
        ? '<span class="spinner-border spinner-border-sm me-1"></span>Guardando…'
        : '<i class="fa-solid fa-floppy-disk me-1"></i>Guardar';
}
function mostrarAlert(tipo, msg) {
    $alert.className = 'alert alert-' + tipo + ' alert-dismissible mx-4 mb-2 py-2 px-3';
    $alertMsg.innerHTML = msg;
    $alert.style.display = 'block';
    $alert.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}
function ocultarAlert()          { $alert.style.display = 'none'; }
function mostrarUploadSt(t, m)   {
    $uploadSt.className    = 'alert alert-' + t + ' py-2 px-3 mb-2';
    $uploadSt.innerHTML    = m;
    $uploadSt.style.display= 'block';
}
function esc(s) {
    var d = document.createElement('div');
    d.appendChild(document.createTextNode(String(s || '')));
    return d.innerHTML;
}
function fmtBytes(b) {
    if (b < 1024)    return b + ' B';
    if (b < 1048576) return (b/1024).toFixed(1) + ' KB';
    return (b/1048576).toFixed(1) + ' MB';
}

})();
</script>
