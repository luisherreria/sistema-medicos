<?php
/**
 * Diálogos VFP citados por AUDITA.DO FORM (extractos SCX).
 * newcons, cambpres, AJUSFACT, AJUSFAFI, CAMBAFIL, debitar.
 */
if (!function_exists('audita_h')) {
    function audita_h($s)
    {
        return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
    }
}
?>

<div class="modal fade" id="modal-newcons" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form id="form-newcons">
                <div class="modal-header py-2 text-white" style="background:#2b579a;">
                    <h6 class="modal-title mb-0">Nueva Consulta</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2">
                        <div class="col-md-3"><label class="small">Obra Social :</label>
                            <input class="form-control form-control-sm" name="tobra" id="nc-tobra" readonly></div>
                        <div class="col-md-9"><label class="small">&nbsp;</label>
                            <input class="form-control form-control-sm" name="tnomobra" id="nc-tnomobra" readonly></div>
                        <div class="col-md-3"><label class="small">Fecha :</label>
                            <input type="date" class="form-control form-control-sm" name="tfecha" id="nc-tfecha"></div>
                        <div class="col-md-3"><label class="small">Afiliado:</label>
                            <input class="form-control form-control-sm" name="tafiliado" id="nc-tafiliado"></div>
                        <div class="col-md-4"><label class="small">&nbsp;</label>
                            <input class="form-control form-control-sm" name="tnomafil" id="nc-tnomafil" readonly></div>
                        <div class="col-md-2"><label class="small">Plan :</label>
                            <input class="form-control form-control-sm" name="tplan" id="nc-tplan"></div>
                        <div class="col-md-3"><label class="small">Prestacion :</label>
                            <input class="form-control form-control-sm" name="tpres" id="nc-tpres"></div>
                        <div class="col-md-9"><label class="small">&nbsp;</label>
                            <input class="form-control form-control-sm" name="tnpres" id="nc-tnpres" readonly></div>
                        <div class="col-md-2"><label class="small">Cantidad :</label>
                            <input class="form-control form-control-sm" name="tcant" id="nc-tcant" value="0"></div>
                        <div class="col-md-3"><label class="small">Facturado :</label>
                            <input class="form-control form-control-sm" name="tfact" id="nc-tfact" value="0"></div>
                        <div class="col-md-3"><label class="small">Importe</label>
                            <input class="form-control form-control-sm" name="timpo" id="nc-timpo" value="0"></div>
                        <div class="col-md-3"><label class="small">Coseguro Prestador :</label>
                            <input class="form-control form-control-sm" name="tcosep" id="nc-tcosep" value="0"></div>
                        <div class="col-md-3"><label class="small">Coseguro :</label>
                            <input class="form-control form-control-sm" name="tcose" id="nc-tcose" value="0"></div>
                        <div class="col-md-6 d-flex align-items-end">
                            <label class="small mb-0"><input type="checkbox" name="chkexccos" id="nc-chkexccos" value="1"> Exclusion de Coseguro</label>
                        </div>
                    </div>
                    <input type="hidden" name="tpresta" id="nc-tpresta">
                    <input type="hidden" name="tsuc" id="nc-tsuc">
                    <input type="hidden" name="tfac" id="nc-tfac">
                    <input type="hidden" name="tperi" id="nc-tperi">
                    <input type="hidden" name="empresa" id="nc-empresa">
                    <div id="nc-err" class="alert alert-danger py-1 mt-2 d-none small"></div>
                </div>
                <div class="modal-footer py-2">
                    <button type="submit" class="btn btn-sm btn-primary">Aceptar</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modal-cambpres" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="form-cambpres">
                <div class="modal-header py-2 text-white" style="background:#2b579a;">
                    <h6 class="modal-title mb-0">Datos de la Prestacion</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2">
                        <div class="col-4"><label class="small">Prestacion :</label>
                            <input class="form-control form-control-sm" name="tpres" id="cp-tpres"></div>
                        <div class="col-8"><label class="small">Detalle</label>
                            <input class="form-control form-control-sm" name="tnpres" id="cp-tnpres" readonly></div>
                        <div class="col-4"><label class="small">Cantidad :</label>
                            <input class="form-control form-control-sm" name="tcant" id="cp-tcant"></div>
                        <div class="col-12">
                            <label class="small mb-0"><input type="checkbox" name="ccanti" id="cp-ccanti" value="1"> Cambiar cantidad para todas las coincidencias de Prestacion Iguales</label>
                        </div>
                        <div class="col-12">
                            <label class="small mb-0"><input type="checkbox" name="todas" id="cp-todas" value="1"> Cambiar Valores/Prestacion para todas las Prestaciones Iguales (No toca valores de coseguros)</label>
                        </div>
                        <div class="col-6"><label class="small">Facturado :</label>
                            <input class="form-control form-control-sm" name="tfact" id="cp-tfact"></div>
                        <div class="col-6"><label class="small">Importe</label>
                            <input class="form-control form-control-sm" name="timpo" id="cp-timpo"></div>
                        <div class="col-6"><label class="small">Coseguro Prestador :</label>
                            <input class="form-control form-control-sm" name="tcospres" id="cp-tcospres"></div>
                        <div class="col-6"><label class="small">Coseguro :</label>
                            <input class="form-control form-control-sm" name="tcose" id="cp-tcose"></div>
                        <div class="col-12">
                            <label class="small mb-0"><input type="checkbox" name="chkexccos" id="cp-chkexccos" value="1"> Exclusion de Coseguro</label>
                        </div>
                        <p class="small text-muted mb-0">Recuerde que no deberia modificar el valor del COSEGURO, salvo ajustes de valores anteriores. Si tiene las fechas de Internacion deberia ser 0 salvo que el prestador lo coloque.</p>
                    </div>
                    <input type="hidden" name="nroorden" id="cp-nroorden">
                    <input type="hidden" name="prestant" id="cp-prestant">
                    <input type="hidden" name="ttagrupo" id="cp-ttagrupo">
                    <div id="cp-err" class="alert alert-danger py-1 mt-2 d-none small"></div>
                </div>
                <div class="modal-footer py-2">
                    <button type="submit" class="btn btn-sm btn-primary">Aceptar</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modal-ajusfact" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form id="form-ajusfact">
                <div class="modal-header py-2 text-white" style="background:#2b579a;">
                    <h6 class="modal-title mb-0">Ajuste de Valor Impositivo</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2">
                        <div class="col-md-3"><label class="small">Obra Social :</label>
                            <input class="form-control form-control-sm" name="tobra" id="aj-tobra" readonly></div>
                        <div class="col-md-9"><label class="small">&nbsp;</label>
                            <input class="form-control form-control-sm" name="tnomobra" id="aj-tnomobra" readonly></div>
                        <div class="col-md-3"><label class="small">Afiliado:</label>
                            <input class="form-control form-control-sm" name="tafil" id="aj-tafil" value="1" readonly></div>
                        <div class="col-md-9"><label class="small">&nbsp;</label>
                            <input class="form-control form-control-sm" name="tnomafil" id="aj-tnomafil" value="Ajuste" readonly></div>
                        <div class="col-md-3"><label class="small">Fecha :</label>
                            <input type="date" class="form-control form-control-sm" name="tfecha" id="aj-tfecha"></div>
                        <div class="col-md-3"><label class="small">Prestacion :</label>
                            <input class="form-control form-control-sm" name="tpres" id="aj-tpres"></div>
                        <div class="col-md-6"><label class="small">&nbsp;</label>
                            <input class="form-control form-control-sm" name="tnpres" id="aj-tnpres" readonly></div>
                        <div class="col-md-2"><label class="small">Cantidad :</label>
                            <input class="form-control form-control-sm" name="tcant" id="aj-tcant" value="1"></div>
                        <div class="col-md-2"><label class="small">Facturado :</label>
                            <input class="form-control form-control-sm" name="tfact" id="aj-tfact" value="0"></div>
                        <div class="col-md-2"><label class="small">'= '   Importe</label>
                            <input class="form-control form-control-sm" name="timpo" id="aj-timpo" value="0"></div>
                        <div class="col-md-3"><label class="small">CosegPrest :</label>
                            <input class="form-control form-control-sm" name="tcosepre" id="aj-tcosepre" value="0"></div>
                        <div class="col-md-3"><label class="small">Coseguro :</label>
                            <input class="form-control form-control-sm" name="tcose" id="aj-tcose" value="0"></div>
                    </div>
                    <p class="small text-muted mt-2 mb-0">verifique los valores ingresado en facturado o Importe hay valor de tope maximo para ajustes. tambien (004 - 041 - 002 importe = 0)</p>
                    <p class="small text-muted mb-0">Para aplicar NC debe utilizarce COD 003 y luego aplicar el debito 153 mas la aclaracion que corresponda</p>
                    <input type="hidden" name="tpresta" id="aj-tpresta">
                    <input type="hidden" name="tsuc" id="aj-tsuc">
                    <input type="hidden" name="tfac" id="aj-tfac">
                    <input type="hidden" name="tperi" id="aj-tperi">
                    <input type="hidden" name="tplan" id="aj-tplan">
                    <div id="aj-err" class="alert alert-danger py-1 mt-2 d-none small"></div>
                </div>
                <div class="modal-footer py-2">
                    <button type="submit" class="btn btn-sm btn-primary">Aceptar</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modal-ajusfafi" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form id="form-ajusfafi">
                <div class="modal-header py-2 text-white" style="background:#2b579a;">
                    <h6 class="modal-title mb-0">Ingreso de Ajustes de prestacion</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2">
                        <div class="col-md-3"><label class="small">Obra Social :</label>
                            <input class="form-control form-control-sm" name="tobra" id="af2-tobra" readonly></div>
                        <div class="col-md-9"><label class="small">&nbsp;</label>
                            <input class="form-control form-control-sm" name="tnomobra" id="af2-tnomobra" readonly></div>
                        <div class="col-md-3"><label class="small">Fecha :</label>
                            <input type="date" class="form-control form-control-sm" name="tfecha" id="af2-tfecha"></div>
                        <div class="col-md-3"><label class="small">Afiliado:</label>
                            <input class="form-control form-control-sm" name="tafil" id="af2-tafil"></div>
                        <div class="col-md-4"><label class="small">&nbsp;</label>
                            <input class="form-control form-control-sm" name="tnomafil" id="af2-tnomafil" readonly></div>
                        <div class="col-md-2"><label class="small">Plan :</label>
                            <input class="form-control form-control-sm" name="tplan" id="af2-tplan"></div>
                        <div class="col-md-3"><label class="small">Prestacion :</label>
                            <input class="form-control form-control-sm" name="tpres" id="af2-tpres"></div>
                        <div class="col-md-9"><label class="small">&nbsp;</label>
                            <input class="form-control form-control-sm" name="tnpres" id="af2-tnpres" readonly></div>
                        <div class="col-md-2"><label class="small">Cantidad :</label>
                            <input class="form-control form-control-sm" name="tcant" id="af2-tcant" value="1"></div>
                        <div class="col-md-2"><label class="small">Facturado :</label>
                            <input class="form-control form-control-sm" name="tfact" id="af2-tfact" value="0"></div>
                        <div class="col-md-3"><label class="small">' = '   Importe</label>
                            <input class="form-control form-control-sm" name="timpo" id="af2-timpo" value="0"></div>
                        <div class="col-md-2"><label class="small">CosegPrest :</label>
                            <input class="form-control form-control-sm" name="tcosepre" id="af2-tcosepre" value="0"></div>
                        <div class="col-md-3"><label class="small">Coseguro :</label>
                            <input class="form-control form-control-sm" name="tcose" id="af2-tcose" value="0"></div>
                    </div>
                    <p class="small text-muted mt-2 mb-0">verifique los valores ingresado en facturado o Importe hay valor de tope maximo para ajustes. tambien (041 - 002 - 027 Importe = 0)</p>
                    <input type="hidden" name="tpresta" id="af2-tpresta">
                    <input type="hidden" name="tsuc" id="af2-tsuc">
                    <input type="hidden" name="tfac" id="af2-tfac">
                    <input type="hidden" name="tperi" id="af2-tperi">
                    <div id="af2-err" class="alert alert-danger py-1 mt-2 d-none small"></div>
                </div>
                <div class="modal-footer py-2">
                    <button type="submit" class="btn btn-sm btn-primary">Aceptar</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modal-cambafil" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width:555px;">
        <div class="modal-content">
            <form id="form-cambafil">
                <div class="modal-header py-2 text-white" style="background:#2b579a;">
                    <h6 class="modal-title mb-0">Cambio de Afiliado de Obra Social</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2">
                        <div class="col-md-3"><label class="small">Obra Social :</label>
                            <input class="form-control form-control-sm text-uppercase" name="tobra" id="ca-tobra" maxlength="8"></div>
                        <div class="col-md-9"><label class="small">&nbsp;</label>
                            <input class="form-control form-control-sm" name="tnomobra" id="ca-tnomobra" readonly></div>
                        <div class="col-md-3"><label class="small">Afiliado:</label>
                            <input class="form-control form-control-sm" name="tafil" id="ca-tafil"></div>
                        <div class="col-md-9"><label class="small">&nbsp;</label>
                            <input class="form-control form-control-sm" name="tnomafil" id="ca-tnomafil" readonly></div>
                    </div>
                    <input type="hidden" name="nobra" id="ca-nobra">
                    <input type="hidden" name="nafil" id="ca-nafil">
                    <input type="hidden" name="nplan" id="ca-nplan">
                    <input type="hidden" name="tfecha" id="ca-tfecha">
                    <input type="hidden" name="conumero" id="ca-conumero">
                    <div id="ca-err" class="alert alert-danger py-1 mt-2 d-none small"></div>
                </div>
                <div class="modal-footer py-2">
                    <button type="submit" class="btn btn-sm btn-primary">Acepta</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancela</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modal-debitar" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered" style="max-width:631px;">
        <div class="modal-content">
            <form id="form-debitar">
                <div class="modal-header py-2 text-white" style="background:#2b579a;">
                    <h6 class="modal-title mb-0">Seleccione el Codigo de Debito</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2">
                        <div class="col-md-3"><label class="small">Prestador :</label></div>
                        <div class="col-md-9">
                            <input class="form-control form-control-sm" name="prestador" id="db-prestador" readonly></div>
                        <div class="col-md-3"><label class="small">Prestacion :</label></div>
                        <div class="col-md-9">
                            <input class="form-control form-control-sm" name="prestacion" id="db-prestacion" readonly></div>
                        <div class="col-md-3"><label class="small">Cod. Debito :</label></div>
                        <div class="col-md-3">
                            <input class="form-control form-control-sm text-uppercase" name="debito" id="db-debito" maxlength="8"></div>
                        <div class="col-12"><label class="small">Leyenda :</label>
                            <input class="form-control form-control-sm" name="tleye" id="db-tleye"></div>
                    </div>
                    <input type="hidden" name="nroorden" id="db-nroorden">
                    <input type="hidden" name="problema" id="db-problema">
                    <div id="db-err" class="alert alert-danger py-1 mt-2 d-none small"></div>
                </div>
                <div class="modal-footer py-2">
                    <button type="submit" class="btn btn-sm btn-primary">Aceptar</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</div>
