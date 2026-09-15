<?php
/**
 * views/registrf/_modal_ingresar.php
 * Alta / Ingreso de Factura — layout VFP registrf.SCX
 */
$csrfToken     = $_SESSION['csrf_token'] ?? '';
$periodoActual = date('ym');
$periodoDisp   = substr($periodoActual, 0, 2) . '/' . substr($periodoActual, 2, 2);
?>

<div class="modal fade" id="modalIngresarFactura" tabindex="-1"
     aria-labelledby="modalIngresarFacturaLabel" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="false">

    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content" style="border-radius:10px; overflow:hidden; position:relative;">

        <div class="modal-header py-2 px-4"
             style="background:linear-gradient(90deg,#0d47a1,#1976d2); color:#fff; border-bottom:none;">
            <div class="d-flex align-items-center gap-3">
                <div style="width:36px;height:36px;border-radius:50%;
                            background:rgba(255,255,255,.2);
                            display:flex;align-items:center;justify-content:center;">
                    <i class="fa-solid fa-file-invoice-dollar"></i>
                </div>
                <div>
                    <h6 class="modal-title mb-0 fw-bold" id="modalIngresarFacturaLabel">
                        Registracion de Facturas
                    </h6>
                    <small style="opacity:.82; font-size:0.71rem;">Ingreso Caratula Facturas Prestador</small>
                </div>
            </div>
            <button type="button" class="btn-close btn-close-white"
                    data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>

        <div class="modal-body px-4 py-3" style="background:#f0f4f8;">
        <form id="form-ingresar-factura" autocomplete="off" novalidate onsubmit="return false;">
        <input type="hidden" name="csrf_token"   value="<?= htmlspecialchars($csrfToken) ?>">
        <input type="hidden" id="fac-cocateg"    name="COCATEG"    value="">
        <input type="hidden" id="fac-conomprest" name="CONOMPREST" value="">
        <input type="hidden" id="fac-recomenda"  value="">
        <input type="hidden" id="fac-confact"    value="">
        <input type="hidden" id="fac-valereci"   value="">

        <!-- Fila 1: Fecha · Período · Prestador -->
        <div class="row g-2 mb-2 align-items-end">
            <div class="col-auto">
                <label class="rf-lbl">Fecha :</label>
                <input type="date" id="fac-cofecha" name="COFECHA"
                       class="form-control form-control-sm rf-inp"
                       style="width:130px; background:#e8f4fd; cursor:default;"
                       value="<?= date('Y-m-d') ?>" readonly tabindex="-1">
            </div>
            <div class="col-auto">
                <label class="rf-lbl">Período :</label>
                <input type="text" id="fac-coperiodo-display"
                       class="form-control form-control-sm rf-inp text-center"
                       style="width:72px; font-family:monospace; letter-spacing:1px;"
                       maxlength="5"
                       placeholder="<?= htmlspecialchars($periodoDisp) ?>"
                       value="<?= htmlspecialchars($periodoDisp) ?>"
                       title="Formato AA/MM  ej: 26/08"
                       data-next="fac-coprestado">
                <input type="hidden" id="fac-coperiodo" name="COPERIODO" value="<?= htmlspecialchars($periodoActual) ?>">
            </div>
            <div class="col position-relative">
                <label class="rf-lbl">Prestador :</label>
                <div class="input-group input-group-sm">
                    <input type="text" id="fac-coprestado" name="COPRESTADO"
                           class="form-control rf-inp"
                           style="max-width:110px; font-family:monospace; font-weight:700;"
                           placeholder="Código" maxlength="15"
                           data-next="fac-coobrasoc">
                    <input type="text" id="fac-prest-nombre-display"
                           class="form-control rf-inp"
                           style="background:#e8f4fd; color:#1e293b;"
                           placeholder="Enter: completa nombre, OS y leyendas"
                           readonly>
                </div>
                <div id="fac-prest-suggs-wrap" style="display:none; position:absolute; z-index:3000;
                     left:0; right:0; background:#fff; border:1px solid #cbd5e1;
                     border-radius:0 0 8px 8px; box-shadow:0 6px 18px rgba(0,0,0,.12);">
                    <div class="d-flex gap-3 px-2 py-1 border-bottom" style="background:#f8fafc;">
                        <div class="form-check form-check-inline mb-0">
                            <input class="form-check-input" type="checkbox" id="fac-prest-ocultar-adef" checked>
                            <label class="form-check-label" for="fac-prest-ocultar-adef"
                                   style="font-size:.71rem; background:#fce4ec; padding:1px 5px; border-radius:4px;">
                                Ocultar ADEF
                            </label>
                        </div>
                        <div class="form-check form-check-inline mb-0">
                            <input class="form-check-input" type="checkbox" id="fac-prest-ocultar-baja" checked>
                            <label class="form-check-label" for="fac-prest-ocultar-baja"
                                   style="font-size:.71rem; background:#ffebee; padding:1px 5px; border-radius:4px; color:#b71c1c;">
                                Ocultar dados de baja (+180 días)
                            </label>
                        </div>
                    </div>
                    <div id="fac-prest-suggs" class="list-group list-group-flush"
                         style="max-height:180px; overflow-y:auto; font-size:.79rem;"></div>
                </div>
            </div>
        </div>

        <!-- Fila 2: Obra Social -->
        <div class="row g-2 mb-2 align-items-end">
            <div class="col position-relative">
                <label class="rf-lbl">Obra Social :</label>
                <div class="input-group input-group-sm">
                    <input type="text" id="fac-coobrasoc" name="COOBRASOC"
                           class="form-control rf-inp"
                           style="max-width:110px; font-family:monospace; font-weight:700;"
                           placeholder="Código" maxlength="10">
                    <input type="text" id="fac-os-nombre-display"
                           class="form-control rf-inp"
                           style="background:#e8f4fd; color:#1e293b;"
                           placeholder="Enter: completa nombre de la obra social"
                           readonly>
                </div>
                <div id="fac-os-suggs" class="list-group shadow"
                     style="display:none; position:absolute; z-index:2000; left:0; right:0;
                            max-height:160px; overflow-y:auto; font-size:.79rem;
                            border-radius:0 0 8px 8px;"></div>
            </div>
        </div>

        <!-- Fila 3: Factura · Fecha Recibido · Empresa · Tipo -->
        <div class="row g-2 mb-3 align-items-end">
            <div class="col-auto">
                <label class="rf-lbl">Factura N° :</label>
                <div class="input-group input-group-sm">
                    <input type="text" id="fac-cosucfac" name="COSUCFAC"
                           class="form-control rf-inp text-center"
                           style="width:60px; font-family:monospace; font-weight:600;"
                           placeholder="0001" maxlength="4" data-next="fac-conrofac">
                    <span class="input-group-text px-1" style="font-size:.82rem;">–</span>
                    <input type="text" id="fac-conrofac" name="CONROFAC"
                           class="form-control rf-inp"
                           style="width:105px; font-family:monospace; font-weight:600;"
                           placeholder="00000000" maxlength="10" data-next="fac-cofecrecib">
                </div>
            </div>
            <div class="col-auto">
                <label class="rf-lbl">Fecha Recibido :</label>
                <input type="date" id="fac-cofecrecib" name="COFECRECIB"
                       class="form-control form-control-sm rf-inp"
                       style="width:140px;"
                       value="<?= date('Y-m-d') ?>"
                       data-next="fac-cofecfac">
            </div>
            <div class="col-auto">
                <label class="rf-lbl">Fecha Factura :</label>
                <input type="date" id="fac-cofecfac" name="COFECFAC"
                       class="form-control form-control-sm rf-inp"
                       style="width:140px;"
                       value="<?= date('Y-m-d') ?>"
                       data-next="fac-tipo-fisica">
            </div>
            <div class="col-auto">
                <label class="rf-lbl">Empresa :</label>
                <input type="text" id="fac-empresa-display"
                       class="form-control form-control-sm rf-inp"
                       style="width:80px; background:#e8f4fd; font-family:monospace; font-weight:700;"
                       placeholder="—" readonly>
                <input type="hidden" id="fac-empresa" name="COEMPRESA" value="">
            </div>
            <div class="col-auto">
                <label class="rf-lbl d-block">Factura :</label>
                <div class="d-flex gap-3 align-items-center" style="padding-top:3px;">
                    <div class="form-check mb-0">
                        <input class="form-check-input" type="radio" name="COTIPO"
                               id="fac-tipo-fisica" value="F" checked data-next="fac-tipo-online">
                        <label class="form-check-label" for="fac-tipo-fisica" style="font-size:.8rem;">Física</label>
                    </div>
                    <div class="form-check mb-0">
                        <input class="form-check-input" type="radio" name="COTIPO"
                               id="fac-tipo-online" value="O" data-next="fac-cocantidad">
                        <label class="form-check-label" for="fac-tipo-online" style="font-size:.8rem;">On-Line</label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Importes: dos columnas alineadas + leyendas VFP -->
        <div class="rf-imp-wrap">
            <div class="rf-imp-col">
                <div class="rf-imp-row">
                    <span class="rf-imp-lbl">Cantidad :</span>
                    <span class="rf-imp-pref"></span>
                    <input type="number" id="fac-cocantidad" name="COCANTIDAD"
                           class="form-control form-control-sm rf-inp text-end rf-num"
                           min="0" step="1" value="0" data-next="fac-coimporte">
                </div>
                <div class="rf-imp-row">
                    <span class="rf-imp-lbl">Importe :</span>
                    <span class="rf-imp-pref">$</span>
                    <input type="number" id="fac-coimporte" name="IMPORTE"
                           class="form-control form-control-sm rf-inp text-end rf-num fac-calc"
                           min="0" step="0.01" value="" placeholder="0,00" data-next="fac-coiva">
                </div>
                <div class="rf-imp-row">
                    <span class="rf-imp-lbl">I.V.A. :</span>
                    <span class="rf-imp-pref">$</span>
                    <input type="number" id="fac-coiva" name="COIVA"
                           class="form-control form-control-sm rf-inp text-end rf-num fac-calc"
                           min="0" step="0.01" value="" placeholder="0,00" data-next="fac-cocoseguro">
                </div>
                <div class="rf-imp-row">
                    <span class="rf-imp-lbl">Coseguro :</span>
                    <span class="rf-imp-pref">$</span>
                    <input type="number" id="fac-cocoseguro" name="COCOSEGURO"
                           class="form-control form-control-sm rf-inp text-end rf-num fac-calc"
                           min="0" step="0.01" value="" placeholder="0,00" data-next="fac-comonto">
                </div>
                <div class="rf-imp-row">
                    <span class="rf-imp-lbl fw-bold" style="color:#1d4ed8;">Total :</span>
                    <span class="rf-imp-pref" style="background:#dbeafe;color:#1d4ed8;font-weight:700;">$</span>
                    <input type="number" id="fac-cototalfac" name="COTOTALFAC"
                           class="form-control form-control-sm text-end fw-bold"
                           style="background:#eff6ff;color:#1d4ed8;border-color:#bfdbfe;font-family:monospace;"
                           readonly>
                </div>
            </div>

            <div class="rf-imp-col">
                <div class="rf-imp-row">
                    <span class="rf-imp-lbl">$ :</span>
                    <span class="rf-imp-pref">$</span>
                    <input type="number" id="fac-comonto" name="COMONTO"
                           class="form-control form-control-sm rf-inp text-end rf-num"
                           min="0" step="0.01" value="" placeholder="0,00" data-next="fac-cocantprest">
                </div>
                <div class="rf-imp-row">
                    <span class="rf-imp-lbl">Cant. Prest. :</span>
                    <span class="rf-imp-pref"></span>
                    <input type="number" id="fac-cocantprest" name="COCANTPREST"
                           class="form-control form-control-sm rf-inp text-end rf-num"
                           min="0" step="1" value="0" data-next="fac-tienefac-si">
                </div>
                <div class="rf-imp-row">
                    <span class="rf-imp-lbl">Tiene Factura :</span>
                    <span class="rf-imp-pref"></span>
                    <div class="d-flex gap-3 align-items-center" style="height:31px;">
                        <div class="form-check mb-0">
                            <input class="form-check-input" type="radio" name="COTIENEFAC"
                                   id="fac-tienefac-si" value="S" checked data-next="fac-btn-guardar">
                            <label class="form-check-label fw-bold" for="fac-tienefac-si"
                                   style="font-size:.82rem; color:#16a34a;">SI</label>
                        </div>
                        <div class="form-check mb-0">
                            <input class="form-check-input" type="radio" name="COTIENEFAC"
                                   id="fac-tienefac-no" value="N" data-next="fac-btn-guardar">
                            <label class="form-check-label fw-bold" for="fac-tienefac-no"
                                   style="font-size:.82rem; color:#dc2626;">NO</label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="rf-leyendas" aria-live="polite">
                <div id="fac-leyenda-prio" class="rf-leyenda">PRIORITARIO</div>
                <div id="fac-leyenda-factura" class="rf-leyenda">PRESTADOR CON FACTURA</div>
                <div id="fac-leyenda-valereci" class="rf-leyenda">FACTURA VALE COMO RECIBO</div>
            </div>
        </div>

        <div id="fac-msg-vfp" class="alert alert-warning d-none mt-3 py-2 px-3"
             style="font-size:.82rem; border-radius:8px;"></div>
        <div id="fac-alert" class="alert alert-danger d-none mt-3 py-2 px-3"
             style="font-size:.82rem; border-radius:8px;">
            <i class="fa-solid fa-triangle-exclamation me-1"></i>
            <span id="fac-alert-msg"></span>
        </div>

        </form>
        </div>

        <div class="modal-footer py-2 px-4 d-flex justify-content-between align-items-center gap-2"
             style="background:#f0f4f8; border-top:1px solid #e2e8f0;">
            <div class="d-flex gap-2 flex-wrap">
                <button type="button" id="fac-btn-rpt-prio"
                        class="btn btn-sm btn-outline-secondary" style="font-size:.76rem;">
                    <i class="fa-solid fa-star me-1 text-warning"></i>Rpt Prioritarios
                </button>
                <button type="button" id="fac-btn-rpt-conf"
                        class="btn btn-sm btn-outline-secondary" style="font-size:.76rem;"
                        title="Prestadores con flag Conflicto en ebamp (RPT_CONFLIF)">
                    <i class="fa-solid fa-triangle-exclamation me-1 text-warning"></i>Rpt Conflictivos
                </button>
                <button type="button" id="fac-btn-rpt-recibos"
                        class="btn btn-sm btn-outline-secondary" style="font-size:.76rem;">
                    <i class="fa-solid fa-receipt me-1"></i>Recibos Ingresados
                </button>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-sm btn-outline-danger"
                        data-bs-dismiss="modal" style="font-size:.76rem;">
                    <i class="fa-solid fa-xmark me-1"></i>Terminar
                </button>
                <button type="button" id="fac-btn-guardar"
                        class="btn btn-sm btn-primary fw-semibold"
                        style="font-size:.78rem; min-width:110px;">
                    <i class="fa-solid fa-floppy-disk me-1"></i>Guardar
                </button>
            </div>
        </div>

        <div id="rf-rpt-overlay">
            <div class="rf-rpt-head">
                <h6 class="mb-0" id="rf-rpt-titulo">Reporte</h6>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-light" id="rf-rpt-print">
                        <i class="fa-solid fa-print me-1"></i>Imprimir
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-light" id="rf-rpt-cerrar">Cerrar</button>
                </div>
            </div>
            <div id="rf-rpt-body"></div>
        </div>

    </div>
    </div>
</div>

<style>
.rf-lbl { font-size:.74rem; font-weight:600; color:#374151; margin-bottom:2px; display:block; }
.rf-inp { font-size:.82rem !important; }
.rf-num { font-family: ui-monospace, monospace; }
.rf-imp-wrap {
    display: flex;
    flex-wrap: wrap;
    gap: 28px 36px;
    align-items: flex-start;
}
.rf-imp-col { min-width: 260px; }
.rf-imp-row {
    display: grid;
    grid-template-columns: 108px 22px 128px;
    align-items: center;
    column-gap: 6px;
    margin-bottom: 6px;
}
.rf-imp-lbl {
    font-size: .76rem;
    font-weight: 600;
    color: #374151;
    text-align: right;
    white-space: nowrap;
}
.rf-imp-pref {
    font-size: .76rem;
    color: #475569;
    text-align: center;
    height: 31px;
    line-height: 31px;
    border-radius: 4px;
}
.rf-leyendas {
    min-width: 220px;
    padding-top: 2px;
    display: flex;
    flex-direction: column;
    gap: 4px;
}
.rf-leyenda {
    display: none;
    font-size: .88rem;
    font-weight: 800;
    color: #1565c0;
    letter-spacing: .4px;
    line-height: 1.25;
}
#rf-rpt-overlay {
    display: none;
    position: absolute;
    inset: 0;
    z-index: 40;
    background: #fff;
    flex-direction: column;
}
#rf-rpt-overlay.abierto { display: flex; }
.rf-rpt-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 10px 16px;
    background: #1e293b;
    color: #fff;
    flex-shrink: 0;
}
#rf-rpt-body { flex: 1; overflow: auto; min-height: 0; }
</style>

<script>
(function () {
    'use strict';

    var ROUTE = 'registro-facturas';
    var osDelPrestador = [];
    var pendientesAlertas = [];

    window.abrirModalIngresarFactura = function () {
        resetFac();
        bootstrap.Modal.getOrCreateInstance(
            document.getElementById('modalIngresarFactura')
        ).show();
        setTimeout(function () { document.getElementById('fac-coperiodo-display').focus(); }, 320);
    };

    function resetFac() {
        osDelPrestador = [];
        document.getElementById('fac-cototalfac').value = '';
        ['fac-coimporte','fac-coiva','fac-cocoseguro','fac-comonto'].forEach(function (id) {
            document.getElementById(id).value = '';
        });
        ['fac-cocantidad','fac-cocantprest'].forEach(function (id) {
            document.getElementById(id).value = '0';
        });
        document.getElementById('fac-coprestado').value           = '';
        document.getElementById('fac-prest-nombre-display').value = '';
        document.getElementById('fac-coobrasoc').value            = '';
        document.getElementById('fac-os-nombre-display').value    = '';
        document.getElementById('fac-cocateg').value              = '';
        document.getElementById('fac-conomprest').value           = '';
        document.getElementById('fac-recomenda').value            = '';
        document.getElementById('fac-confact').value              = '';
        document.getElementById('fac-valereci').value             = '';
        document.getElementById('fac-empresa-display').value      = '';
        document.getElementById('fac-empresa').value              = '';
        document.getElementById('fac-cosucfac').value             = '';
        document.getElementById('fac-conrofac').value             = '';
        var hoy = new Date().toISOString().substring(0, 10);
        document.getElementById('fac-cofecha').value    = hoy;
        document.getElementById('fac-cofecrecib').value = hoy;
        document.getElementById('fac-cofecfac').value   = hoy;
        var d = new Date();
        var aa = String(d.getFullYear()).substring(2);
        var mm = String(d.getMonth() + 1).padStart(2, '0');
        document.getElementById('fac-coperiodo-display').value = aa + '/' + mm;
        document.getElementById('fac-coperiodo').value         = aa + mm;
        document.getElementById('fac-tipo-fisica').checked  = true;
        document.getElementById('fac-tienefac-si').checked  = true;
        setLeyendas(false, false, false);
        pendientesAlertas = [];
        var box = document.getElementById('fac-msg-vfp');
        if (box) { box.classList.add('d-none'); box.textContent = ''; }
        document.getElementById('rf-rpt-overlay').classList.remove('abierto');
        ocultarAlertFac();
        ocultarSuggsPrest();
        ocultarSuggsOS();
    }

    var $perDisp = document.getElementById('fac-coperiodo-display');
    var $perHid  = document.getElementById('fac-coperiodo');

    function syncPeriodo() {
        var v = $perDisp.value.replace(/[^0-9]/g, '').substring(0, 4);
        $perHid.value = v;
        if (v.length === 4) {
            $perDisp.value = v.substring(0, 2) + '/' + v.substring(2, 4);
        }
        return v;
    }
    $perDisp.addEventListener('blur', syncPeriodo);
    $perDisp.addEventListener('input', function () {
        $perHid.value = this.value.replace(/[^0-9]/g, '').substring(0, 4);
    });

    function periodoActual() {
        return syncPeriodo();
    }

    function calcTotal() {
        var imp  = parseFloat(document.getElementById('fac-coimporte').value)  || 0;
        var iva  = parseFloat(document.getElementById('fac-coiva').value)      || 0;
        var cos  = parseFloat(document.getElementById('fac-cocoseguro').value) || 0;
        document.getElementById('fac-cototalfac').value = (imp + iva + cos).toFixed(2);
    }
    document.querySelectorAll('.fac-calc').forEach(function (el) {
        el.addEventListener('input', calcTotal);
    });

    function padFac(el, len) {
        var v = el.value.trim().replace(/[^0-9]/g, '');
        if (v.length > 0) el.value = v.padStart(len, '0');
    }
    document.getElementById('fac-cosucfac').addEventListener('blur', function () { padFac(this, 4); });
    document.getElementById('fac-conrofac').addEventListener('blur', function () { padFac(this, 8); });

    document.getElementById('form-ingresar-factura').addEventListener('submit', function (e) {
        e.preventDefault();
    });

    function focusId(id) {
        var el = document.getElementById(id);
        if (!el) return;
        el.focus();
        if (el.select) el.select();
    }

    function avanzarDesdePrestador() {
        var osCod = document.getElementById('fac-coobrasoc').value.trim();
        var osNom = document.getElementById('fac-os-nombre-display').value.trim();
        if (osCod && osNom) {
            focusId('fac-cosucfac');
        } else {
            focusId('fac-coobrasoc');
        }
    }

    function flushAlertasPrestador() {
        if (!pendientesAlertas.length) return;
        var msgs = pendientesAlertas.slice();
        pendientesAlertas = [];
        msgs.forEach(function (msg) { window.alert(msg); });
        avanzarDesdePrestador();
    }

    function onEnterPrestador(e) {
        if (e.key !== 'Enter') return;
        e.preventDefault();
        e.stopPropagation();
        if (e.stopImmediatePropagation) e.stopImmediatePropagation();
        clearTimeout(prestTimer);
        var id = e.target.id;
        if (id === 'fac-prest-nombre-display') {
            var first = document.querySelector('#fac-prest-suggs a.list-group-item');
            if (first) {
                first.dispatchEvent(new MouseEvent('mousedown', { bubbles: true }));
                return;
            }
        }
        ocultarSuggsPrest();
        var cod = document.getElementById('fac-coprestado').value.trim();
        var nom = document.getElementById('fac-prest-nombre-display').value.trim();
        if (!cod) return;
        if (nom) {
            avanzarDesdePrestador();
            flushAlertasPrestador();
            return;
        }
        resolverPrestadorExacto(function (ok) {
            if (!ok && !document.getElementById('fac-prest-nombre-display').value.trim()) {
                mostrarAlertFac('Prestador no encontrado. Verificá el código.');
                focusId('fac-coprestado');
                return;
            }
            avanzarDesdePrestador();
            flushAlertasPrestador();
        });
    }

    document.getElementById('fac-coprestado').addEventListener('keydown', onEnterPrestador, true);
    document.getElementById('fac-prest-nombre-display').addEventListener('keydown', onEnterPrestador, true);

    document.getElementById('form-ingresar-factura').addEventListener('keydown', function (e) {
        if (e.key !== 'Enter') return;
        var id = e.target.id;
        if (id === 'fac-coprestado' || id === 'fac-prest-nombre-display') return;
        e.preventDefault();
        e.stopPropagation();

        if (id === 'fac-coobrasoc') {
            var osWrap = document.getElementById('fac-os-suggs');
            var osFirst = osWrap ? osWrap.querySelector('a.list-group-item') : null;
            if (osWrap && osWrap.style.display !== 'none' && osFirst) {
                osFirst.dispatchEvent(new MouseEvent('mousedown', { bubbles: true }));
                return;
            }
            resolverOSExacto(function () { focusId('fac-cosucfac'); });
            return;
        }
        if (id === 'fac-coperiodo-display') syncPeriodo();
        if (id === 'fac-cosucfac') padFac(e.target, 4);
        if (id === 'fac-conrofac') padFac(e.target, 8);

        var nextId = e.target.dataset.next;
        if (nextId) focusId(nextId);
    }, true);

    function setLeyendas(esPrio, esFactura, esVale) {
        document.getElementById('fac-leyenda-prio').style.display     = esPrio    ? 'block' : 'none';
        document.getElementById('fac-leyenda-factura').style.display  = esFactura ? 'block' : 'none';
        document.getElementById('fac-leyenda-valereci').style.display = esVale    ? 'block' : 'none';
    }

    function flagOn(v, fromApi) {
        if (fromApi === true) return true;
        if (fromApi === false) return false;
        v = String(v || '').trim().toUpperCase();
        return v !== '' && v !== '0' && v !== 'N' && v !== 'F' && v !== '.F.' && v !== 'NO';
    }

    function alertaSeleccionPrestador(r) {
        var prio  = flagOn(r.recomenda, r.isprioritario);
        var fact  = flagOn(r.confact,   r.isconfact);
        var vale  = flagOn(r.valereci,  r.isvalereci);
        var msgs  = [];
        if (prio) msgs.push('Prestador Prioritario, colocar en caja correspondiente, Gracias');
        if (fact) msgs.push('Prestador Con Factura, Verificar que la haya entregado, Gracias');
        if (vale) msgs.push('Prestador Factura vale como Recibo,Gracias');
        setLeyendas(prio, fact, vale);
        pendientesAlertas = msgs;
        var box = document.getElementById('fac-msg-vfp');
        if (msgs.length) {
            box.textContent = msgs.join('  ·  ');
            box.classList.remove('d-none');
        } else {
            box.classList.add('d-none');
            box.textContent = '';
        }
    }

    var prestTimer;

    function buscarPrestador(q) {
        clearTimeout(prestTimer);
        if (!q || q.trim() === '') { ocultarSuggsPrest(); return; }
        prestTimer = setTimeout(function () {
            var adef = document.getElementById('fac-prest-ocultar-adef').checked ? '1' : '0';
            var baja = document.getElementById('fac-prest-ocultar-baja').checked ? '1' : '0';
            fetch('index.php?route=' + ROUTE + '&action=buscar_prestador'
                    + '&q=' + encodeURIComponent(q.trim())
                    + '&ocultar_adef=' + adef
                    + '&ocultar_baja=' + baja,
                  { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (res.ok) renderSuggsPrest(res.datos || []);
            })
            .catch(function () {});
        }, 280);
    }

    function renderSuggsPrest(rows) {
        var $list = document.getElementById('fac-prest-suggs');
        var $wrap = document.getElementById('fac-prest-suggs-wrap');
        $list.innerHTML = '';
        if (!rows.length) {
            $list.innerHTML = '<div class="px-3 py-2 text-muted" style="font-size:.78rem;">Sin resultados.</div>';
        } else {
            rows.forEach(function (r) {
                var a = document.createElement('a');
                a.href = '#';
                a.className = 'list-group-item list-group-item-action py-1 px-2';
                a.style.borderBottom = '1px solid #f1f5f9';
                var baja = r.fechabaja && r.fechabaja !== '0000-00-00' && r.fechabaja !== '0000-00-00 00:00:00'
                    ? ' <span style="font-size:.65rem; color:#b91c1c; font-weight:600;">[BAJA]</span>' : '';
                a.innerHTML = '<span class="fw-bold text-primary font-monospace" style="font-size:.78rem;">'
                    + esc(r.codigo) + '</span>'
                    + ' — ' + esc(r.nombre) + baja
                    + (r.categ ? ' <span class="badge bg-secondary bg-opacity-40 ms-1" style="font-size:.62rem;">' + esc(r.categ) + '</span>' : '');
                a.addEventListener('mousedown', function (e) {
                    e.preventDefault();
                    seleccionarPrestador(r, true);
                });
                $list.appendChild(a);
            });
        }
        $wrap.style.display = 'block';
    }

    function seleccionarPrestador(r, avanzar) {
        document.getElementById('fac-coprestado').value           = r.codigo || r.matricula || '';
        document.getElementById('fac-prest-nombre-display').value = r.nombre || '';
        document.getElementById('fac-conomprest').value           = r.nombre || '';
        document.getElementById('fac-cocateg').value              = r.categ     || '';
        document.getElementById('fac-recomenda').value            = r.recomenda || '';
        document.getElementById('fac-confact').value              = r.confact   || '';
        document.getElementById('fac-valereci').value             = r.valereci  || '';
        document.getElementById('fac-empresa-display').value      = r.empresa   || '';
        document.getElementById('fac-empresa').value              = r.empresa   || '';
        ocultarSuggsPrest();
        alertaSeleccionPrestador(r);
        completarObrasSociales(r.codigo || r.matricula || '');
        if (avanzar) {
            avanzarDesdePrestador();
            flushAlertasPrestador();
        }
    }

    function completarObrasSociales(codigo) {
        osDelPrestador = [];
        if (!codigo) return;
        fetch('index.php?route=' + ROUTE + '&action=os_prestador&codigo=' + encodeURIComponent(codigo),
              { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            if (!res.ok) return;
            osDelPrestador = res.datos || [];
            if (osDelPrestador.length === 1) {
                document.getElementById('fac-coobrasoc').value         = osDelPrestador[0].cosoc;
                document.getElementById('fac-os-nombre-display').value = osDelPrestador[0].nombre;
            } else if (osDelPrestador.length > 1 && !document.getElementById('fac-coobrasoc').value.trim()) {
                renderSuggsOS(osDelPrestador);
            }
        })
        .catch(function () {});
    }

    function resolverPrestadorExacto(done) {
        var cod = document.getElementById('fac-coprestado').value.trim();
        if (!cod) { if (done) done(false); return; }
        fetch('index.php?route=' + ROUTE + '&action=buscar_prestador'
              + '&q=' + encodeURIComponent(cod) + '&exact=1&ocultar_adef=0&ocultar_baja=0',
              { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            if (res.ok && res.datos && res.datos.length === 1) {
                seleccionarPrestador(res.datos[0], false);
                if (done) done(true);
                return;
            }
            if (done) done(false);
        })
        .catch(function () { if (done) done(false); });
    }

    function ocultarSuggsPrest() {
        document.getElementById('fac-prest-suggs-wrap').style.display = 'none';
    }

    var $inpPrest = document.getElementById('fac-coprestado');
    $inpPrest.addEventListener('input', function () {
        document.getElementById('fac-prest-nombre-display').value = '';
        document.getElementById('fac-conomprest').value = '';
        buscarPrestador(this.value);
    });
    $inpPrest.addEventListener('focus', function () {
        if (this.value.trim().length >= 2) buscarPrestador(this.value);
    });
    $inpPrest.addEventListener('blur', function () {
        var cod = this.value.trim();
        if (!cod) return;
        if (document.getElementById('fac-prest-nombre-display').value.trim()) return;
        resolverPrestadorExacto();
    });

    document.getElementById('fac-prest-nombre-display').addEventListener('click', function () {
        this.removeAttribute('readonly');
        this.placeholder = 'Escribí para buscar…';
        this.style.background = '#fff';
        this.addEventListener('input', function () { buscarPrestador(this.value); });
    });

    ['fac-prest-ocultar-adef','fac-prest-ocultar-baja'].forEach(function (id) {
        document.getElementById(id).addEventListener('change', function () {
            buscarPrestador(document.getElementById('fac-coprestado').value || ' ');
        });
    });

    var osTimer;

    function buscarOS(q) {
        clearTimeout(osTimer);
        if (!q) { ocultarSuggsOS(); return; }
        osTimer = setTimeout(function () {
            var prest = document.getElementById('fac-coprestado').value.trim();
            var url = 'index.php?route=' + ROUTE + '&action=buscar_os&q=' + encodeURIComponent(q);
            if (prest) url += '&prestador=' + encodeURIComponent(prest);
            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (!res.ok) return;
                renderSuggsOS(res.datos || []);
            });
        }, 280);
    }

    function renderSuggsOS(rows) {
        var $d = document.getElementById('fac-os-suggs');
        $d.innerHTML = '';
        if (!rows.length) { ocultarSuggsOS(); return; }
        rows.forEach(function (r) {
            var a = document.createElement('a');
            a.href = '#';
            a.className = 'list-group-item list-group-item-action py-1 px-2';
            a.style.borderBottom = '1px solid #f1f5f9';
            a.innerHTML = '<span class="fw-bold font-monospace" style="color:#0284c7;font-size:.78rem;">'
                        + esc(r.cosoc) + '</span> — ' + esc(r.nombre);
            a.addEventListener('mousedown', function (e) {
                e.preventDefault();
                document.getElementById('fac-coobrasoc').value         = r.cosoc;
                document.getElementById('fac-os-nombre-display').value = r.nombre;
                ocultarSuggsOS();
                document.getElementById('fac-cosucfac').focus();
            });
            $d.appendChild(a);
        });
        $d.style.display = 'block';
    }

    function ocultarSuggsOS() {
        document.getElementById('fac-os-suggs').style.display = 'none';
    }

    function resolverOSExacto(done) {
        var cod = document.getElementById('fac-coobrasoc').value.trim();
        if (!cod) { if (done) done(); return; }
        var local = osDelPrestador.filter(function (r) {
            return String(r.cosoc || '').trim().toUpperCase() === cod.toUpperCase();
        });
        if (local.length === 1) {
            document.getElementById('fac-coobrasoc').value         = local[0].cosoc;
            document.getElementById('fac-os-nombre-display').value = local[0].nombre;
            ocultarSuggsOS();
            if (done) done();
            return;
        }
        fetch('index.php?route=' + ROUTE + '&action=buscar_os&q=' + encodeURIComponent(cod) + '&exact=1',
              { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            if (res.ok && res.datos && res.datos.length === 1) {
                document.getElementById('fac-coobrasoc').value         = res.datos[0].cosoc;
                document.getElementById('fac-os-nombre-display').value = res.datos[0].nombre;
                ocultarSuggsOS();
            }
            if (done) done();
        })
        .catch(function () { if (done) done(); });
    }

    var $inpOS = document.getElementById('fac-coobrasoc');
    $inpOS.addEventListener('input', function () {
        document.getElementById('fac-os-nombre-display').value = '';
        buscarOS(this.value.trim());
    });
    $inpOS.addEventListener('focus', function () {
        if (osDelPrestador.length > 1 && !this.value.trim()) {
            renderSuggsOS(osDelPrestador);
            return;
        }
        if (this.value.trim().length >= 1) buscarOS(this.value.trim());
    });
    $inpOS.addEventListener('blur', function () {
        if (document.getElementById('fac-os-nombre-display').value.trim()) return;
        resolverOSExacto();
    });

    document.addEventListener('click', function (e) {
        if (!e.target.closest('#fac-prest-suggs-wrap') && e.target.id !== 'fac-coprestado') {
            ocultarSuggsPrest();
        }
        if (!e.target.closest('#fac-os-suggs') && e.target !== $inpOS) {
            ocultarSuggsOS();
        }
    });

    document.getElementById('fac-btn-guardar').addEventListener('click', guardar);

    function guardar() {
        ocultarAlertFac();
        syncPeriodo();
        padFac(document.getElementById('fac-cosucfac'), 4);
        padFac(document.getElementById('fac-conrofac'), 8);
        calcTotal();

        var errores = [];
        if (!document.getElementById('fac-coperiodo').value.trim())
            errores.push('Debe colocar el Período...');
        if (!document.getElementById('fac-coprestado').value.trim())
            errores.push('Ingresá el código del Prestador.');
        if (!document.getElementById('fac-coobrasoc').value.trim())
            errores.push('Ingresá el código de Obra Social.');
        if (!document.getElementById('fac-cosucfac').value.trim() || !document.getElementById('fac-conrofac').value.trim())
            errores.push('Completá el N° de Factura (punto de venta y número).');
        if (errores.length) { mostrarAlertFac(errores.join(' ')); return; }

        var btn = document.getElementById('fac-btn-guardar');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Guardando…';

        fetch('index.php?route=' + ROUTE + '&action=guardar', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: (function () {
                var fd = new FormData(document.getElementById('form-ingresar-factura'));
                fd.delete('COIMPORTE');
                return fd;
            })()
        })
        .then(function (r) { return r.json().then(function (j) { return { okHttp: r.ok, json: j }; }); })
        .then(function (pack) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-floppy-disk me-1"></i>Guardar';
            var res = pack.json || {};
            if (!res.ok) { mostrarAlertFac(res.error || 'Error al guardar.'); return; }

            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalIngresarFactura')).hide();
            var toast = document.createElement('div');
            toast.className = 'alert alert-success position-fixed shadow';
            toast.style.cssText = 'top:70px;right:20px;z-index:9999;font-size:.85rem;padding:10px 18px;border-radius:8px;';
            toast.innerHTML = '<i class="fa-solid fa-check-circle me-2"></i>Factura registrada correctamente.';
            document.body.appendChild(toast);
            setTimeout(function () { toast.remove(); }, 3500);
            if (typeof window.rfCargar === 'function') window.rfCargar(1);
        })
        .catch(function () {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-floppy-disk me-1"></i>Guardar';
            mostrarAlertFac('Error de comunicación con el servidor.');
        });
    }

    function abrirReporte(action, extra, titulo) {
        var per = periodoActual();
        if (!per) {
            mostrarAlertFac('Debe colocar el Período...');
            document.getElementById('fac-coperiodo-display').focus();
            return;
        }
        var overlay = document.getElementById('rf-rpt-overlay');
        var $body = document.getElementById('rf-rpt-body');
        var $tit  = document.getElementById('rf-rpt-titulo');
        $tit.textContent = titulo || 'Reporte';
        $body.innerHTML = '<div class="text-center py-5 text-muted"><span class="spinner-border spinner-border-sm me-2"></span>Generando…</div>';
        overlay.classList.add('abierto');

        var url = 'index.php?route=' + ROUTE + '&action=' + action
            + '&periodo=' + encodeURIComponent(per)
            + '&format=json';
        if (extra) url += extra;

        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
            .then(function (r) { return r.text().then(function (t) { return { okHttp: r.ok, t: t }; }); })
            .then(function (pack) {
                var res;
                try { res = JSON.parse(pack.t); }
                catch (err) {
                    $body.innerHTML = '<div class="alert alert-danger m-3">No se pudo leer el reporte. Recargá la página e intentá de nuevo.</div>';
                    return;
                }
                if (!res.ok) {
                    $body.innerHTML = '<div class="alert alert-danger m-3">' + esc(res.error || 'No se pudo generar el reporte.') + '</div>';
                    return;
                }
                var perDisp = per.length === 4 ? per.substring(0, 2) + '/' + per.substring(2, 4) : per;
                var rows = res.datos || [];
                var html = '<div class="px-3 pt-3 pb-1 text-muted" style="font-size:.78rem;">Período <strong>'
                    + esc(perDisp) + '</strong> · ' + rows.length + ' registro(s)</div>';
                if (!rows.length) {
                    html += '<div class="text-center text-muted py-5">No hay facturas para el período ' + esc(perDisp) + '.</div>';
                } else {
                    html += '<div class="table-responsive"><table class="table table-sm table-striped mb-0" style="font-size:.8rem;">'
                        + '<thead style="background:#1e293b;color:#fff;"><tr>'
                        + '<th>Período</th><th>Fecha</th><th>Código</th><th>Prestador</th><th>Suc</th><th>N° Factura</th><th>F. Factura</th>'
                        + '</tr></thead><tbody>';
                    rows.forEach(function (r) {
                        html += '<tr>'
                            + '<td class="font-monospace">' + esc(r.coperiodo || perDisp) + '</td>'
                            + '<td class="font-monospace">' + esc(fmtRptFecha(r.cofecha)) + '</td>'
                            + '<td class="font-monospace">' + esc(r.coprestado || '') + '</td>'
                            + '<td>' + esc(r.conomprest || r.nombre_ebamp || '') + '</td>'
                            + '<td class="font-monospace">' + esc(r.cosucfac || '') + '</td>'
                            + '<td class="font-monospace">' + esc(r.conrofac || '') + '</td>'
                            + '<td class="font-monospace">' + esc(fmtRptFecha(r.cofecfac)) + '</td>'
                            + '</tr>';
                    });
                    html += '</tbody></table></div>';
                }
                $body.innerHTML = html;
            })
            .catch(function () {
                $body.innerHTML = '<div class="alert alert-danger m-3">Error de comunicación al generar el reporte.</div>';
            });
    }

    function fmtRptFecha(s) {
        if (!s || String(s).indexOf('0000-00-00') === 0) return '—';
        var p = String(s).substring(0, 10).split('-');
        return p.length === 3 ? p[2] + '/' + p[1] + '/' + p[0] : String(s);
    }

    document.getElementById('fac-btn-rpt-prio').addEventListener('click', function () {
        abrirReporte('rpt_prioritarios', '&tipo=prioritario', 'Rpt Prioritarios');
    });
    document.getElementById('fac-btn-rpt-conf').addEventListener('click', function () {
        abrirReporte('rpt_prioritarios', '&tipo=conflicto', 'Rpt Conflictivos');
    });
    document.getElementById('fac-btn-rpt-recibos').addEventListener('click', function () {
        abrirReporte('rpt_recibos', '', 'Recibos Ingresados');
    });
    document.getElementById('rf-rpt-cerrar').addEventListener('click', function () {
        document.getElementById('rf-rpt-overlay').classList.remove('abierto');
    });
    document.getElementById('rf-rpt-print').addEventListener('click', function () {
        var html = document.getElementById('rf-rpt-body').innerHTML;
        var w = window.open('', '_blank');
        if (!w) { window.print(); return; }
        w.document.write('<html><head><title>Reporte</title>'
            + '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">'
            + '</head><body class="p-3">' + html + '</body></html>');
        w.document.close();
        w.focus();
        w.print();
    });

    function mostrarAlertFac(msg) {
        document.getElementById('fac-alert-msg').textContent = msg;
        document.getElementById('fac-alert').classList.remove('d-none');
        document.getElementById('fac-alert').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
    function ocultarAlertFac() {
        document.getElementById('fac-alert').classList.add('d-none');
    }
    function esc(s) {
        var d = document.createElement('div');
        d.appendChild(document.createTextNode(s || ''));
        return d.innerHTML;
    }
})();
</script>
