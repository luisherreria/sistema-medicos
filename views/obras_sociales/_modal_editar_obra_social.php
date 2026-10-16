<?php
/**
 * views/obras_sociales/_modal_editar_obra_social.php
 * Ficha de alta y edición de cartilla.obrasoc.
 *
 * @var string $csrfToken
 */
$csrfToken = (string) ($csrfToken ?? '');
?>
<style>
#modalObraSocial .os-zona-activa {
    outline: 1px dashed #90caf9;
    border-radius: 0.5rem;
}
#modalObraSocial .os-drop-on {
    outline: 2px dashed #1976d2;
    outline-offset: 2px;
    background: #e3f2fd;
    border-radius: 0.5rem;
}
#modalObraSocial .os-logo {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    overflow: hidden;
    border: 1px solid #e2e8f0;
    background: #1976d2;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 1rem;
    line-height: 1;
    flex-shrink: 0;
    position: relative;
    box-shadow: 0 1px 2px rgba(15, 23, 42, .08);
}
#modalObraSocial .os-logo img {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    object-fit: contain;
    background: #fff;
    padding: 2px;
    display: none;
}
</style>
<div class="modal fade" id="modalObraSocial" tabindex="-1" aria-labelledby="modalObraSocialTitulo" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable" style="max-width:1320px;width:96vw">
        <div class="modal-content" style="height:100%">
            <form id="frmObraSocial" autocomplete="off" class="d-flex flex-column h-100" style="min-height:0;overflow:hidden">
                <div class="modal-header flex-shrink-0">
                    <div class="d-flex align-items-center gap-2 min-w-0">
                        <div class="os-logo" id="osLogoWrap" title="Logo de la obra social">
                            <span id="osAvatarInicial">O</span>
                            <img id="osLogoImg" alt="" src="">
                        </div>
                        <h5 class="modal-title text-truncate" id="modalObraSocialTitulo">Datos de la Obra Social</h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body" style="overflow-y:auto;flex:1 1 auto;min-height:0">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="modo" id="osModo" value="alta">

                    <h6 class="text-uppercase small fw-bold text-muted border-bottom pb-2 mb-3">Identificación y contacto</h6>
                    <div class="d-flex flex-nowrap align-items-center gap-2 mb-3">
                        <div style="flex:0 0 9rem;min-width:9rem">
                            <div class="form-floating">
                                <input type="text" class="form-control rounded-3 text-uppercase" id="osCodigo"
                                       name="TACODIGO" maxlength="8" placeholder="Código" required>
                                <label for="osCodigo"><i class="fa-solid fa-hashtag me-1 text-muted"></i> Código</label>
                            </div>
                        </div>
                        <div style="flex:1 1 60ch;max-width:60ch;min-width:16rem">
                            <div class="form-floating">
                                <input type="text" class="form-control rounded-3" id="osNombre"
                                       name="TADESCRIP" maxlength="40" placeholder="Nombre" required>
                                <label for="osNombre"><i class="fa-solid fa-building me-1 text-muted"></i> Nombre / Razón Social</label>
                            </div>
                        </div>
                        <div style="flex:0 0 11rem;min-width:11rem">
                            <div class="form-floating">
                                <input type="date" class="form-control rounded-3 w-100" id="osFechaIni"
                                       name="TAFECHAINI" placeholder="Desde" style="min-width:0">
                                <label for="osFechaIni"><i class="fa-solid fa-calendar me-1 text-muted"></i> Vig. desde</label>
                            </div>
                        </div>
                        <div style="flex:0 0 11rem;min-width:11rem">
                            <div class="form-floating">
                                <input type="date" class="form-control rounded-3 w-100" id="osFechaFin"
                                       name="TAFECHAFIN" placeholder="Hasta" style="min-width:0">
                                <label for="osFechaFin"><i class="fa-solid fa-calendar-xmark me-1 text-muted"></i> Vig. hasta</label>
                            </div>
                        </div>
                        <div class="d-flex flex-column justify-content-between flex-shrink-0" style="height:48px;padding:1px 0 0">
                            <div class="form-check form-switch mb-0 d-flex align-items-center" style="min-height:0;padding-left:0">
                                <input class="form-check-input position-static" type="checkbox" role="switch" id="osPorPrest" name="XPREST" value="T" style="margin:0;float:none">
                                <label class="form-check-label text-nowrap ms-2 mb-0" for="osPorPrest" style="line-height:1">Por prestación</label>
                            </div>
                            <div class="form-check form-switch mb-0 d-flex align-items-center" style="min-height:0;padding-left:0">
                                <input class="form-check-input position-static" type="checkbox" role="switch" id="osConvAdm" name="CONVADM" value="T" style="margin:0;float:none">
                                <label class="form-check-label text-nowrap ms-2 mb-0" for="osConvAdm" style="line-height:1">Convenio administrativo solamente</label>
                            </div>
                        </div>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-md-5">
                            <div class="form-floating">
                                <input type="text" class="form-control rounded-3" id="osDireccion"
                                       name="TADIR" maxlength="40" placeholder="Dirección">
                                <label for="osDireccion"><i class="fa-solid fa-location-dot me-1 text-muted"></i> Dirección</label>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-floating">
                                <input type="text" class="form-control rounded-3" id="osCp"
                                       name="TACP" maxlength="10" placeholder="C.P.">
                                <label for="osCp"><i class="fa-solid fa-signs-post me-1 text-muted"></i> Código postal</label>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-floating">
                                <input type="text" class="form-control rounded-3" id="osCuit"
                                       name="TACUIT" maxlength="13" placeholder="CUIT">
                                <label for="osCuit"><i class="fa-solid fa-id-card me-1 text-muted"></i> C.U.I.T.</label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-floating">
                                <select class="form-select rounded-3" id="osCondIva" name="TACONDIVA">
                                    <option value="">— Seleccione opción —</option>
                                    <option value="Resp. Inscripto">Responsable inscripto</option>
                                    <option value="Monotributo">Monotributo</option>
                                    <option value="Exento">Exento</option>
                                    <option value="Cons. final">Consumidor final</option>
                                    <option value="No responsable">No responsable</option>
                                </select>
                                <label for="osCondIva"><i class="fa-solid fa-file-invoice me-1 text-muted"></i> Condición I.V.A.</label>
                            </div>
                        </div>
                    </div>
                    <div class="row g-2 mb-4">
                        <div class="col-md-3">
                            <div class="form-floating">
                                <input type="text" class="form-control rounded-3" id="osTelefono"
                                       name="TATELEF" maxlength="30" placeholder="Teléfonos">
                                <label for="osTelefono"><i class="fa-solid fa-phone me-1 text-muted"></i> Teléfonos</label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-floating">
                                <input type="text" class="form-control rounded-3" id="osContacto"
                                       name="TACONTACTO" maxlength="30" placeholder="Contacto">
                                <label for="osContacto"><i class="fa-solid fa-user me-1 text-muted"></i> Contacto</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="text" class="form-control rounded-3" id="osEmail"
                                       name="TAEMAIL" maxlength="150" placeholder="Mail (mas de uno separar por ;)">
                                <label for="osEmail"><i class="fa-solid fa-envelope me-1 text-muted"></i> Mail (mas de uno separar por ;)</label>
                            </div>
                        </div>
                    </div>

                    <h6 class="text-uppercase small fw-bold text-muted border-bottom pb-2 mb-3">Valores, cápitas y coseguros</h6>
                    <div class="row g-2 mb-3 flex-nowrap align-items-center">
                        <div class="col" style="flex:1 1 20ch;max-width:20ch">
                            <div class="form-floating">
                                <input type="number" class="form-control rounded-3" id="osCosegCon"
                                       name="TACOSEGCON" min="0" step="0.01" placeholder="0.00">
                                <label for="osCosegCon"><i class="fa-solid fa-stethoscope me-1 text-muted"></i> Coseg. cons.</label>
                            </div>
                        </div>
                        <div class="col" style="flex:1 1 20ch;max-width:20ch">
                            <div class="form-floating">
                                <input type="number" class="form-control rounded-3" id="osCosegPra"
                                       name="TACOSEGPRA" min="0" step="0.01" placeholder="0.00">
                                <label for="osCosegPra"><i class="fa-solid fa-notes-medical me-1 text-muted"></i> Coseg. práct.</label>
                            </div>
                        </div>
                        <div class="col" style="flex:1 1 20ch;max-width:20ch">
                            <div class="form-floating">
                                <input type="number" class="form-control rounded-3" id="osCosegDet"
                                       name="TACOSEGDET" min="0" step="0.01" placeholder="0.00">
                                <label for="osCosegDet"><i class="fa-solid fa-vial me-1 text-muted"></i> Coseg. determ.</label>
                            </div>
                        </div>
                        <div class="col" style="flex:1 1 20ch;max-width:20ch">
                            <div class="form-floating">
                                <input type="number" class="form-control rounded-3" id="osCapita"
                                       name="TAVALCAPIT" min="0" step="0.01" placeholder="0.00">
                                <label for="osCapita"><i class="fa-solid fa-dollar-sign me-1 text-muted"></i> Valor cápita</label>
                            </div>
                        </div>
                        <div class="col" style="flex:1 1 20ch;max-width:20ch">
                            <div class="form-floating">
                                <input type="number" class="form-control rounded-3" id="osRefact"
                                       name="TAREFACT" min="0" step="0.01" placeholder="0.00">
                                <label for="osRefact"><i class="fa-solid fa-percent me-1 text-muted"></i> % Refact.</label>
                            </div>
                        </div>
                        <div class="col" style="flex:0 0 12ch;max-width:12ch;min-width:0">
                            <div class="form-floating">
                                <select class="form-select rounded-3" id="osGrupo" name="GRUPO_OS" style="min-width:0">
                                    <option value="">—</option>
                                    <option value="SI">SI</option>
                                    <option value="NO">NO</option>
                                </select>
                                <label for="osGrupo"><i class="fa-solid fa-layer-group me-1 text-muted"></i> Grupo</label>
                            </div>
                        </div>
                        <div class="col" style="flex:0 0 12ch;max-width:12ch;min-width:0">
                            <div class="form-floating">
                                <input type="text" class="form-control rounded-3 text-uppercase" id="osMesaOp"
                                       name="TAMESAOP" maxlength="2" placeholder="Mesa" style="min-width:0">
                                <label for="osMesaOp"><i class="fa-solid fa-table me-1 text-muted"></i> MesaOp</label>
                            </div>
                        </div>
                        <div class="col d-flex flex-column justify-content-center gap-1" style="flex:1 1 0;min-width:15rem;max-width:none">
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" role="switch" id="osVerPlan" name="ISVERPLAN" value="T">
                                <label class="form-check-label" for="osVerPlan" style="font-size:.8rem;line-height:1.15">Verificación de plan para coseguro y cobertura</label>
                            </div>
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" role="switch" id="osExcCart" name="TAEXCCAR" value="T">
                                <label class="form-check-label" for="osExcCart" style="font-size:.8rem;line-height:1.15">Exclusión de cartilla</label>
                            </div>
                        </div>
                    </div>

                    <h6 class="text-uppercase small fw-bold text-muted border-bottom pb-2 mb-3">Mails y rutas</h6>
                    <div class="form-floating mb-2">
                        <input type="text" class="form-control rounded-3" id="osEmailCar"
                               name="TAMAILCAR" maxlength="150" placeholder="Mail cartilla (mas de uno separar por ;)">
                        <label for="osEmailCar"><i class="fa-solid fa-envelope-open me-1 text-muted"></i> Mail cartilla (mas de uno separar por ;)</label>
                    </div>

                    <div class="d-flex gap-2 mb-3 align-items-stretch">
                        <div class="border rounded-3 p-2" style="flex:1 1 0;width:0;min-width:0">
                            <div class="form-check form-switch mb-1">
                                <input class="form-check-input" type="checkbox" role="switch" id="osMailCoseg" name="MAILCOSEG" value="T">
                                <label class="form-check-label" for="osMailCoseg">Informa coseguros por mail</label>
                            </div>
                            <div class="small text-muted mb-1">Ruta y nombre archivo coseguros</div>
                            <div class="d-flex gap-2 align-items-center os-zona-archivo" id="dropCoseg">
                                <div class="form-floating flex-grow-1" style="min-width:0">
                                    <input type="text" class="form-control rounded-3" id="osDirCoseg"
                                           name="DIRCOSEG" maxlength="100" placeholder="dircoseg" readonly>
                                    <label for="osDirCoseg"><i class="fa-solid fa-file-lines me-1 text-muted"></i> dircoseg</label>
                                </div>
                                <button type="button" class="btn btn-outline-secondary btn-input-align flex-shrink-0" id="btnRutaCoseg" title="Elegir o arrastrar archivo" disabled>
                                    <i class="fa-solid fa-folder-open"></i>
                                </button>
                                <button type="button" class="btn btn-outline-primary btn-input-align flex-shrink-0 d-none" id="btnVerCoseg" title="Ver archivo">
                                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                </button>
                                <input type="file" id="fileCoseg" class="d-none" tabindex="-1">
                            </div>
                            <div class="form-floating mt-2">
                                <input type="text" class="form-control rounded-3" id="osLinkCoseg"
                                       name="LINKCOSEG" maxlength="150" placeholder="Hipervínculo">
                                <label for="osLinkCoseg"><i class="fa-solid fa-link me-1 text-muted"></i> Hipervínculo coseguro (laboratorios)</label>
                            </div>
                        </div>

                        <div class="border rounded-3 p-2" style="flex:1 1 0;width:0;min-width:0">
                            <div class="form-check form-switch mb-1">
                                <input class="form-check-input" type="checkbox" role="switch" id="osMailNorma" name="MAILNORMA" value="T">
                                <label class="form-check-label" for="osMailNorma">Informa normas op. por mail</label>
                            </div>
                            <div class="small text-muted mb-1">Ruta y nombre archivo normas operativas</div>
                            <div class="d-flex gap-2 align-items-center os-zona-archivo" id="dropNorma">
                                <div class="form-floating flex-grow-1" style="min-width:0">
                                    <input type="text" class="form-control rounded-3" id="osDirNorma"
                                           name="DIRNORMA" maxlength="150" placeholder="dirnorma" readonly>
                                    <label for="osDirNorma"><i class="fa-solid fa-book me-1 text-muted"></i> dirnorma</label>
                                </div>
                                <button type="button" class="btn btn-outline-secondary btn-input-align flex-shrink-0" id="btnRutaNorma" title="Elegir o arrastrar archivo" disabled>
                                    <i class="fa-solid fa-folder-open"></i>
                                </button>
                                <button type="button" class="btn btn-outline-primary btn-input-align flex-shrink-0 d-none" id="btnVerNorma" title="Ver archivo">
                                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                </button>
                                <input type="file" id="fileNorma" class="d-none" tabindex="-1">
                            </div>
                        </div>

                        <div class="border rounded-3 p-2 bg-white d-flex flex-column" style="flex:1 1 0;width:0;min-width:0;align-self:stretch">
                            <label for="osPadron" class="d-flex align-items-center gap-1 mb-1 fw-semibold" style="font-size:11px;color:#0d47a1">
                                <i class="fa-solid fa-folder-open"></i> Nombre del Padrón (padrón en uso)
                            </label>
                            <div class="d-flex align-items-center gap-2">
                                <input type="text" class="form-control form-control-sm rounded-3 flex-grow-1" id="osPadron"
                                       name="TAPADRON" maxlength="80" placeholder="nombre del padrón"
                                       style="min-width:0;background:#f8fafc;font-size:.75rem">
                                <button type="button" class="btn btn-success flex-shrink-0 d-flex align-items-center justify-content-center" id="btnVerificarPadron"
                                        title="Verificar padrón y cantidad de afiliados"
                                        data-bs-toggle="tooltip" data-bs-placement="top"
                                        style="width:2.15rem;height:31px;padding:0;border-radius:.5rem">
                                    <i class="fa-solid fa-check"></i>
                                </button>
                            </div>
                            <div class="d-flex gap-2 mt-auto pt-2">
                                <div class="border rounded-3 px-2 py-1 bg-white text-center d-flex flex-column justify-content-center" style="flex:1 1 0;min-width:0">
                                    <div class="text-uppercase text-muted fw-medium" style="font-size:10px;letter-spacing:.04em;line-height:1.1">Afiliados directos</div>
                                    <input type="number" class="form-control border-0 bg-transparent text-center fw-bold p-0 shadow-none" id="osAfiliados"
                                           name="TACANTAFIL" min="0" step="1" placeholder="0"
                                           style="height:1.35rem;min-height:0;font-size:1.15rem;line-height:1;box-shadow:none;color:#0f172a">
                                </div>
                                <div class="border rounded-3 px-2 py-1 bg-white text-center d-flex flex-column justify-content-center" style="flex:1 1 0;min-width:0">
                                    <div class="text-uppercase text-muted fw-medium" style="font-size:10px;letter-spacing:.04em;line-height:1.1">Afiliados adherentes</div>
                                    <div id="osAfilAdh" class="fw-bold" style="height:1.35rem;font-size:1.15rem;line-height:1.35rem;color:#0f172a">—</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <p class="text-muted small mb-0">
                        La vigencia hasta inclusive mantiene la obra social vigente. Una fecha anterior a hoy la da de baja
                        y deja de listarse hasta activar «Mostrar dadas de baja / inactivas».
                        En un alta sin fecha, se graba 31/12/2099.
                        Con el interruptor encendido se puede escribir la ruta o arrastrar el archivo: se guarda en uploads/obrassoc y el nombre queda en el campo.
                    </p>
                </div>
                <div class="modal-footer flex-shrink-0 d-flex flex-wrap justify-content-between gap-2">
                    <div class="d-flex flex-wrap align-items-stretch gap-2">
                        <button type="button" class="btn btn-outline-secondary" id="btnOsPlanes">
                            <i class="fa-solid fa-table-list me-1"></i> Planes
                        </button>
                        <div class="d-flex flex-column gap-1">
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="btnOsPlanesOs">
                                <i class="fa-solid fa-layer-group me-1"></i> Planes OS
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="btnOsNombrePrest">
                                Nombre OS a Prestador
                            </button>
                        </div>
                        <button type="button" class="btn btn-outline-secondary" id="btnOsPadrones">
                            <i class="fa-solid fa-users me-1"></i> Padrones
                        </button>
                        <button type="button" class="btn btn-outline-secondary" id="btnOsCopiarPrest">
                            Copiar Prestadores a
                        </button>
                    </div>
                    <div class="d-flex gap-2 ms-auto">
                        <button type="submit" class="btn btn-success" id="btnGuardarOS">
                            <i class="fa-solid fa-check me-1"></i> Aceptar
                        </button>
                        <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">
                            <i class="fa-solid fa-xmark me-1"></i> Cancelar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
