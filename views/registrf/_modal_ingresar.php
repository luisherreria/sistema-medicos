<?php
/**
 * views/registrf/_modal_ingresar.php
 * ─────────────────────────────────────────────────────────────────────────
 * Modal: Alta / Ingreso de Factura
 *
 * Layout idéntico al formulario VFP registrf.SCX:
 *   Fila 1:  Fecha · Período (AA/MM) · Prestador (código + nombre)
 *   Fila 2:  Obra Social (código + nombre)
 *   Fila 3:  Factura N° (suc-nro) · Fecha Recibido · Empresa · Tipo Factura
 *   Izq/Der: Cantidad  |  $ / Cant.Prest.
 *            Importe   |  Tiene Factura + [PRIORITARIO]
 *            I.V.A.    |  [PRESTADOR CON FACTURA]
 *            Coseguro
 *            Total
 *   Botones: Rpt Prioritarios · Recibos Ingresados  |  Guardar · Cancelar
 *
 * Funcionalidades:
 *   - Autocomplete Prestador con filtros: Ocultar ADEF / Ocultar baja +180 días
 *   - Alerta popup si el prestador es PRIORITARIO o tiene CONFACT
 *   - Leyendas "PRIORITARIO" / "PRESTADOR CON FACTURA" visibles en el form
 *   - Navegación con ENTER entre campos (orden visual igual al VFP)
 *   - Auto-pad: COSUCFAC = 4 dígitos  (3 → 0003)
 *               CONROFAC = 8 dígitos  (4578 → 00004578)
 *   - Período: formato AA/MM para mostrar (almacenado como AAMM sin slash)
 * ─────────────────────────────────────────────────────────────────────────
 */
$csrfToken     = $_SESSION['csrf_token'] ?? '';
$usuarioSesion = strtoupper($_SESSION['user']['username'] ?? 'SIS');
$periodoActual = date('ym'); // ej: 2608
?>

<!-- ╔══════════════════════════════════════════════════════════════════╗
     ║  MODAL: INGRESO DE FACTURA                                      ║
     ╚══════════════════════════════════════════════════════════════════╝ -->
<div class="modal fade" id="modalIngresarFactura" tabindex="-1"
     aria-labelledby="modalIngresarFacturaLabel" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="false">

    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content" style="border-radius:10px; overflow:hidden;">

        <!-- ── Header ──────────────────────────────────────────────────── -->
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

        <!-- ── Body ────────────────────────────────────────────────────── -->
        <div class="modal-body px-4 py-3" style="background:#f0f4f8;">
        <form id="form-ingresar-factura" autocomplete="off" novalidate>
        <input type="hidden" name="csrf_token"   value="<?= $csrfToken ?>">
        <input type="hidden" id="fac-cocateg"    name="COCATEG"    value="">
        <input type="hidden" id="fac-conomprest" name="CONOMPREST" value="">
        <input type="hidden" id="fac-recomenda"  value="">
        <input type="hidden" id="fac-confact"    value="">

        <!-- ════════════════════════════════════════════════════════════
             FILA 1: Fecha · Período · Prestador
             ════════════════════════════════════════════════════════════ -->
        <div class="row g-2 mb-2 align-items-end">

            <!-- Fecha -->
            <div class="col-auto">
                <label class="rf-lbl">Fecha :</label>
                <input type="date" id="fac-cofecha" name="COFECHA"
                       class="form-control form-control-sm rf-inp"
                       style="width:130px;"
                       value="<?= date('Y-m-d') ?>"
                       data-next="fac-coperiodo-display">
            </div>

            <!-- Período AA/MM (se muestra con slash, se guarda sin él) -->
            <div class="col-auto">
                <label class="rf-lbl">Período :</label>
                <input type="text" id="fac-coperiodo-display"
                       class="form-control form-control-sm rf-inp text-center"
                       style="width:68px; font-family:monospace; letter-spacing:1px;"
                       maxlength="5"
                       placeholder="<?= substr($periodoActual,0,2) . '/' . substr($periodoActual,2,2) ?>"
                       value="<?= substr($periodoActual,0,2) . '/' . substr($periodoActual,2,2) ?>"
                       title="Formato AA/MM  ej: 26/08"
                       data-next="fac-coprestado">
                <input type="hidden" id="fac-coperiodo" name="COPERIODO" value="<?= $periodoActual ?>">
            </div>

            <!-- Prestador: código + nombre -->
            <div class="col position-relative">
                <label class="rf-lbl">Prestador :</label>
                <div class="input-group input-group-sm">
                    <input type="text" id="fac-coprestado" name="COPRESTADO"
                           class="form-control rf-inp"
                           style="max-width:100px; font-family:monospace; font-weight:700;"
                           placeholder="Código"
                           maxlength="15"
                           data-next="fac-prest-buscar-inline">
                    <input type="text" id="fac-prest-nombre-display"
                           class="form-control rf-inp"
                           style="background:#e8f4fd; color:#1e293b;"
                           placeholder="(buscar: ingresá código o nombre)"
                           data-next="fac-coobrasoc"
                           readonly>
                </div>
                <!-- Dropdown de sugerencias de prestador -->
                <div id="fac-prest-suggs-wrap" style="display:none; position:absolute; z-index:3000;
                     left:0; right:0; background:#fff; border:1px solid #cbd5e1;
                     border-radius:0 0 8px 8px; box-shadow:0 6px 18px rgba(0,0,0,.12);">
                    <!-- Filtros -->
                    <div class="d-flex gap-3 px-2 py-1 border-bottom" style="background:#f8fafc;">
                        <div class="form-check form-check-inline mb-0">
                            <input class="form-check-input" type="checkbox"
                                   id="fac-prest-ocultar-adef" checked>
                            <label class="form-check-label" for="fac-prest-ocultar-adef"
                                   style="font-size:.71rem; background:#fce4ec; padding:1px 5px; border-radius:4px;">
                                Ocultar ADEF
                            </label>
                        </div>
                        <div class="form-check form-check-inline mb-0">
                            <input class="form-check-input" type="checkbox"
                                   id="fac-prest-ocultar-baja" checked>
                            <label class="form-check-label" for="fac-prest-ocultar-baja"
                                   style="font-size:.71rem; background:#ffebee; padding:1px 5px; border-radius:4px; color:#b71c1c;">
                                Ocultar dados de baja (+180 días)
                            </label>
                        </div>
                    </div>
                    <!-- Lista de sugerencias -->
                    <div id="fac-prest-suggs" class="list-group list-group-flush"
                         style="max-height:180px; overflow-y:auto; font-size:.79rem;"></div>
                </div>
            </div>

        </div><!-- /fila 1 -->

        <!-- ════════════════════════════════════════════════════════════
             FILA 2: Obra Social
             ════════════════════════════════════════════════════════════ -->
        <div class="row g-2 mb-2 align-items-end">

            <div class="col position-relative">
                <label class="rf-lbl">Obra Social :</label>
                <div class="input-group input-group-sm">
                    <input type="text" id="fac-coobrasoc" name="COOBRASOC"
                           class="form-control rf-inp"
                           style="max-width:100px; font-family:monospace; font-weight:700;"
                           placeholder="Código"
                           maxlength="10"
                           data-next="fac-os-nombre-display">
                    <input type="text" id="fac-os-nombre-display"
                           class="form-control rf-inp"
                           style="background:#e8f4fd; color:#1e293b;"
                           placeholder="(buscar: código o nombre de obra social)"
                           data-next="fac-cosucfac"
                           readonly>
                </div>
                <!-- Dropdown sugerencias OS -->
                <div id="fac-os-suggs" class="list-group shadow"
                     style="display:none; position:absolute; z-index:2000; left:0; right:0;
                            max-height:160px; overflow-y:auto; font-size:.79rem;
                            border-radius:0 0 8px 8px;"></div>
            </div>

        </div><!-- /fila 2 -->

        <!-- ════════════════════════════════════════════════════════════
             FILA 3: Factura N° · Fecha Recibido · Empresa · Tipo
             ════════════════════════════════════════════════════════════ -->
        <div class="row g-2 mb-3 align-items-end">

            <!-- Factura N° -->
            <div class="col-auto">
                <label class="rf-lbl">Factura N° :</label>
                <div class="input-group input-group-sm">
                    <input type="text" id="fac-cosucfac" name="COSUCFAC"
                           class="form-control rf-inp text-center"
                           style="width:60px; font-family:monospace; font-weight:600;"
                           placeholder="0001"
                           maxlength="4"
                           data-next="fac-conrofac">
                    <span class="input-group-text px-1" style="font-size:.82rem;">–</span>
                    <input type="text" id="fac-conrofac" name="CONROFAC"
                           class="form-control rf-inp"
                           style="width:105px; font-family:monospace; font-weight:600;"
                           placeholder="00000000"
                           maxlength="10"
                           data-next="fac-cofecrecib">
                </div>
            </div>

            <!-- Fecha Recibido -->
            <div class="col-auto">
                <label class="rf-lbl">Fecha Recibido :</label>
                <input type="date" id="fac-cofecrecib" name="COFECRECIB"
                       class="form-control form-control-sm rf-inp"
                       style="width:140px;"
                       value="<?= date('Y-m-d') ?>"
                       data-next="fac-empresa-display">
            </div>

            <!-- Empresa (auto desde ebamp) -->
            <div class="col-auto">
                <label class="rf-lbl">Empresa :</label>
                <input type="text" id="fac-empresa-display"
                       class="form-control form-control-sm rf-inp"
                       style="width:80px; background:#e8f4fd; font-family:monospace; font-weight:700;"
                       placeholder="—"
                       data-next="fac-tipo-fisica"
                       readonly>
                <input type="hidden" id="fac-empresa" name="COEMPRESA" value="">
            </div>

            <!-- Tipo Factura -->
            <div class="col-auto">
                <label class="rf-lbl d-block">Factura :</label>
                <div class="d-flex gap-3 align-items-center" style="padding-top:3px;">
                    <div class="form-check mb-0">
                        <input class="form-check-input" type="radio" name="COTIPO"
                               id="fac-tipo-fisica" value="F" checked
                               data-next="fac-tipo-online">
                        <label class="form-check-label" for="fac-tipo-fisica" style="font-size:.8rem;">Física</label>
                    </div>
                    <div class="form-check mb-0">
                        <input class="form-check-input" type="radio" name="COTIPO"
                               id="fac-tipo-online" value="O"
                               data-next="fac-cofecfac">
                        <label class="form-check-label" for="fac-tipo-online" style="font-size:.8rem;">On-Line</label>
                    </div>
                </div>
            </div>

            <!-- Fecha Factura (oculta en VFP pero necesaria para el registro) -->
            <div class="col-auto">
                <label class="rf-lbl">Fecha Factura :</label>
                <input type="date" id="fac-cofecfac" name="COFECFAC"
                       class="form-control form-control-sm rf-inp"
                       style="width:140px;"
                       value="<?= date('Y-m-d') ?>"
                       data-next="fac-cocantidad">
            </div>

        </div><!-- /fila 3 -->

        <!-- ════════════════════════════════════════════════════════════
             IMPORTES — layout columna izquierda / derecha (igual al VFP)
             ════════════════════════════════════════════════════════════ -->
        <div class="row g-0">

            <!-- ─── COLUMNA IZQUIERDA: Cantidad, Importe, IVA, Coseguro, Total ─ -->
            <div class="col-auto" style="min-width:220px;">
                <table style="border-collapse:separate; border-spacing:0 4px;">
                    <tr>
                        <td class="rf-td-label">Cantidad :</td>
                        <td>
                            <input type="number" id="fac-cocantidad" name="COCANTIDAD"
                                   class="form-control form-control-sm rf-inp text-end rf-num"
                                   style="width:120px;"
                                   min="0" step="1" value="0"
                                   data-next="fac-coimporte">
                        </td>
                    </tr>
                    <tr>
                        <td class="rf-td-label">Importe :</td>
                        <td>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text px-1 rf-prefix">$</span>
                                <input type="number" id="fac-coimporte" name="COIMPORTE"
                                       class="form-control rf-inp text-end rf-num fac-calc"
                                       style="width:112px;"
                                       min="0" step="0.01" value=""
                                       placeholder="0,00"
                                       data-next="fac-coiva">
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="rf-td-label">I.V.A. :</td>
                        <td>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text px-1 rf-prefix">$</span>
                                <input type="number" id="fac-coiva" name="COIVA"
                                       class="form-control rf-inp text-end rf-num fac-calc"
                                       style="width:112px;"
                                       min="0" step="0.01" value=""
                                       placeholder="0,00"
                                       data-next="fac-cocoseguro">
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="rf-td-label">Coseguro :</td>
                        <td>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text px-1 rf-prefix">$</span>
                                <input type="number" id="fac-cocoseguro" name="COCOSEGURO"
                                       class="form-control rf-inp text-end rf-num fac-calc"
                                       style="width:112px;"
                                       min="0" step="0.01" value=""
                                       placeholder="0,00"
                                       data-next="fac-comonto">
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="rf-td-label fw-bold" style="color:#1d4ed8;">Total :</td>
                        <td>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text px-1 rf-prefix"
                                      style="background:#dbeafe; color:#1d4ed8; font-weight:700;">$</span>
                                <input type="number" id="fac-cototalfac" name="COTOTALFAC"
                                       class="form-control text-end fw-bold"
                                       style="width:112px; background:#eff6ff; color:#1d4ed8;
                                              border-color:#bfdbfe; font-family:monospace;"
                                       readonly>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- ─── COLUMNA DERECHA: $ / Cant.Prest. / Tiene Factura / Leyendas ─ -->
            <div class="col ps-4">
                <table style="border-collapse:separate; border-spacing:0 4px;">
                    <tr>
                        <td class="rf-td-label">$ :</td>
                        <td>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text px-1 rf-prefix">$</span>
                                <input type="number" id="fac-comonto" name="COMONTO"
                                       class="form-control rf-inp text-end rf-num"
                                       style="width:120px;"
                                       min="0" step="0.01" value=""
                                       placeholder="0,00"
                                       data-next="fac-cocantprest">
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="rf-td-label">Cant. Prest. :</td>
                        <td>
                            <input type="number" id="fac-cocantprest" name="COCANTPREST"
                                   class="form-control form-control-sm rf-inp text-end rf-num"
                                   style="width:120px;"
                                   min="0" step="1" value="0"
                                   data-next="fac-tienefac-si">
                        </td>
                    </tr>
                    <tr>
                        <td class="rf-td-label">Tiene Factura :</td>
                        <td>
                            <div class="d-flex gap-2 align-items-center" style="padding-top:2px;">
                                <div class="form-check mb-0">
                                    <input class="form-check-input" type="radio" name="COTIENEFAC"
                                           id="fac-tienefac-si" value="S" checked
                                           data-next="fac-btn-guardar">
                                    <label class="form-check-label fw-bold"
                                           for="fac-tienefac-si"
                                           style="font-size:.82rem; color:#16a34a;">SI</label>
                                </div>
                                <div class="form-check mb-0">
                                    <input class="form-check-input" type="radio" name="COTIENEFAC"
                                           id="fac-tienefac-no" value="N"
                                           data-next="fac-btn-guardar">
                                    <label class="form-check-label fw-bold"
                                           for="fac-tienefac-no"
                                           style="font-size:.82rem; color:#dc2626;">NO</label>
                                </div>
                            </div>
                        </td>
                        <!-- Leyenda PRIORITARIO (visible si recomenda != '') -->
                        <td class="ps-3">
                            <span id="fac-leyenda-prio"
                                  style="display:none; font-size:.95rem; font-weight:900;
                                         color:#1565c0; letter-spacing:.5px;">
                                PRIORITARIO
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2"></td>
                        <!-- Leyenda PRESTADOR CON FACTURA -->
                        <td class="ps-3">
                            <span id="fac-leyenda-factura"
                                  style="display:none; font-size:.85rem; font-weight:800;
                                         color:#1565c0; letter-spacing:.3px;">
                                PRESTADOR CON FACTURA
                            </span>
                        </td>
                    </tr>
                </table>
            </div>

        </div><!-- /importes -->

        <!-- Alertas / Errores -->
        <div id="fac-alert" class="alert alert-danger d-none mt-2 py-2 px-3"
             style="font-size:.82rem; border-radius:8px;">
            <i class="fa-solid fa-triangle-exclamation me-1"></i>
            <span id="fac-alert-msg"></span>
        </div>

        </form>
        </div><!-- /.modal-body -->

        <!-- ── Footer ──────────────────────────────────────────────────── -->
        <div class="modal-footer py-2 px-4 d-flex justify-content-between align-items-center gap-2"
             style="background:#f0f4f8; border-top:1px solid #e2e8f0;">

            <!-- Izquierda: acciones del VFP -->
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-sm btn-outline-secondary"
                        style="font-size:.76rem;" title="Reporte de prestadores prioritarios">
                    <i class="fa-solid fa-star me-1 text-warning"></i>Rpt Prioritarios
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary"
                        style="font-size:.76rem;" title="Ver recibos ingresados">
                    <i class="fa-solid fa-receipt me-1"></i>Recibos Ingresados
                </button>
            </div>

            <!-- Derecha: guardar / cancelar -->
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

    </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div>

<!-- ══════════════════════════════════════════════════════════════════════
     ESTILOS DEL MODAL
     ══════════════════════════════════════════════════════════════════════ -->
<style>
.rf-lbl {
    font-size: .74rem;
    font-weight: 600;
    color: #374151;
    margin-bottom: 2px;
    display: block;
}
.rf-inp {
    font-size: .82rem !important;
}
.rf-td-label {
    font-size: .76rem;
    font-weight: 600;
    color: #374151;
    padding-right: 8px;
    white-space: nowrap;
    vertical-align: middle;
}
.rf-prefix {
    font-size: .76rem;
    background: #f1f5f9;
}
.rf-num {
    font-family: monospace;
}
</style>

<!-- ══════════════════════════════════════════════════════════════════════
     JAVASCRIPT DEL MODAL
     ══════════════════════════════════════════════════════════════════════ -->
<script>
(function () {
    'use strict';

    // ════════════════════════════════════════════════════════════════════
    //  ABRIR / RESETEAR
    // ════════════════════════════════════════════════════════════════════
    window.abrirModalIngresarFactura = function () {
        resetFac();
        bootstrap.Modal.getOrCreateInstance(
            document.getElementById('modalIngresarFactura')
        ).show();
        setTimeout(function () { document.getElementById('fac-cofecha').focus(); }, 320);
    };

    function resetFac() {
        // Importes
        document.getElementById('fac-cototalfac').value = '';
        ['fac-coimporte','fac-coiva','fac-cocoseguro','fac-comonto'].forEach(function (id) {
            document.getElementById(id).value = '';
        });
        ['fac-cocantidad','fac-cocantprest'].forEach(function (id) {
            document.getElementById(id).value = '0';
        });
        // Lookups
        document.getElementById('fac-coprestado').value           = '';
        document.getElementById('fac-prest-nombre-display').value = '';
        document.getElementById('fac-coobrasoc').value            = '';
        document.getElementById('fac-os-nombre-display').value    = '';
        document.getElementById('fac-cocateg').value              = '';
        document.getElementById('fac-conomprest').value           = '';
        document.getElementById('fac-recomenda').value            = '';
        document.getElementById('fac-confact').value              = '';
        document.getElementById('fac-empresa-display').value      = '';
        document.getElementById('fac-empresa').value              = '';
        // Factura
        document.getElementById('fac-cosucfac').value             = '';
        document.getElementById('fac-conrofac').value             = '';
        // Fechas
        var hoy = new Date().toISOString().substring(0, 10);
        document.getElementById('fac-cofecha').value    = hoy;
        document.getElementById('fac-cofecrecib').value = hoy;
        document.getElementById('fac-cofecfac').value   = hoy;
        // Período: AA/MM del mes actual
        var d = new Date();
        var aa = String(d.getFullYear()).substring(2);
        var mm = String(d.getMonth() + 1).padStart(2, '0');
        document.getElementById('fac-coperiodo-display').value = aa + '/' + mm;
        document.getElementById('fac-coperiodo').value         = aa + mm;
        // Radios
        document.getElementById('fac-tipo-fisica').checked  = true;
        document.getElementById('fac-tienefac-si').checked  = true;
        // Leyendas
        setLeyendas(false, false);
        // Alert
        ocultarAlertFac();
        ocultarSuggsPrest();
        ocultarSuggsOS();
    }

    // ════════════════════════════════════════════════════════════════════
    //  PERÍODO — formateo AA/MM ↔ AAMM
    //  El usuario escribe "2608" o "26/08"; siempre guardamos "2608" en hidden
    // ════════════════════════════════════════════════════════════════════
    var $perDisp = document.getElementById('fac-coperiodo-display');
    var $perHid  = document.getElementById('fac-coperiodo');

    $perDisp.addEventListener('blur', function () {
        var v = this.value.replace(/\//g, '');        // "2608"
        if (v.length === 4) {
            this.value   = v.substring(0, 2) + '/' + v.substring(2, 4);  // "26/08"
            $perHid.value = v;
        }
    });
    $perDisp.addEventListener('input', function () {
        var v = this.value.replace(/[^0-9]/g, '');
        $perHid.value = v.substring(0, 4);
    });

    // ════════════════════════════════════════════════════════════════════
    //  CÁLCULO AUTOMÁTICO DEL TOTAL
    // ════════════════════════════════════════════════════════════════════
    function calcTotal() {
        var imp  = parseFloat(document.getElementById('fac-coimporte').value)  || 0;
        var iva  = parseFloat(document.getElementById('fac-coiva').value)      || 0;
        var cos  = parseFloat(document.getElementById('fac-cocoseguro').value) || 0;
        document.getElementById('fac-cototalfac').value = (imp + iva + cos).toFixed(2);
    }
    document.querySelectorAll('.fac-calc').forEach(function (el) {
        el.addEventListener('input', calcTotal);
    });

    // ════════════════════════════════════════════════════════════════════
    //  AUTO-PAD FACTURA: COSUCFAC (4 dígitos) y CONROFAC (8 dígitos)
    // ════════════════════════════════════════════════════════════════════
    function padFac(el, len) {
        var v = el.value.trim().replace(/[^0-9]/g, '');
        if (v.length > 0) {
            el.value = v.padStart(len, '0');
        }
    }
    document.getElementById('fac-cosucfac').addEventListener('blur', function () { padFac(this, 4); });
    document.getElementById('fac-conrofac').addEventListener('blur', function () { padFac(this, 8); });

    // ════════════════════════════════════════════════════════════════════
    //  NAVEGACIÓN CON ENTER — pasa al campo definido en data-next=
    // ════════════════════════════════════════════════════════════════════
    document.getElementById('form-ingresar-factura').addEventListener('keydown', function (e) {
        if (e.key !== 'Enter') return;
        var nextId = e.target.dataset.next;
        if (!nextId) return;
        e.preventDefault();

        // Auto-pad antes de saltar en campos de factura
        if (e.target.id === 'fac-cosucfac') padFac(e.target, 4);
        if (e.target.id === 'fac-conrofac') padFac(e.target, 8);
        // Parsear período antes de saltar
        if (e.target.id === 'fac-coperiodo-display') $perDisp.dispatchEvent(new Event('blur'));

        var $next = document.getElementById(nextId);
        if ($next) {
            $next.focus();
            if ($next.select) $next.select();
        }
    });

    // ════════════════════════════════════════════════════════════════════
    //  LEYENDAS PRIORITARIO / PRESTADOR CON FACTURA
    // ════════════════════════════════════════════════════════════════════
    function setLeyendas(esPrio, esFactura) {
        document.getElementById('fac-leyenda-prio').style.display    = esPrio    ? '' : 'none';
        document.getElementById('fac-leyenda-factura').style.display = esFactura ? '' : 'none';
    }

    function alertaSeleccionPrestador(recomenda, confact) {
        var msgs = [];
        if (recomenda && recomenda !== '' && recomenda !== '0' && recomenda.toLowerCase() !== 'n') {
            msgs.push('Prestador Prioritario, colocar en caja correspondiente, Gracias');
        }
        if (confact && confact !== '' && confact !== '0' && confact.toLowerCase() !== 'n') {
            msgs.push('Prestador Con Factura, Verificar que la haya entregado, Gracias');
        }
        if (msgs.length) {
            setTimeout(function () {
                msgs.forEach(function (msg) { alert(msg); });
            }, 200);
        }
        setLeyendas(
            recomenda && recomenda !== '' && recomenda !== '0' && recomenda.toLowerCase() !== 'n',
            confact   && confact   !== '' && confact   !== '0' && confact.toLowerCase()   !== 'n'
        );
    }

    // ════════════════════════════════════════════════════════════════════
    //  AUTOCOMPLETE PRESTADOR
    // ════════════════════════════════════════════════════════════════════
    var prestTimer;

    function buscarPrestador(q) {
        clearTimeout(prestTimer);
        if (!q || q.trim() === '') { ocultarSuggsPrest(); return; }
        prestTimer = setTimeout(function () {
            var adef = document.getElementById('fac-prest-ocultar-adef').checked ? '1' : '0';
            var baja = document.getElementById('fac-prest-ocultar-baja').checked ? '1' : '0';
            fetch('index.php?route=registro-facturas&action=buscar_prestador'
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
                    seleccionarPrestador(r);
                });
                $list.appendChild(a);
            });
        }
        $wrap.style.display = 'block';
    }

    function seleccionarPrestador(r) {
        document.getElementById('fac-coprestado').value           = r.codigo;
        document.getElementById('fac-prest-nombre-display').value = r.nombre;
        document.getElementById('fac-conomprest').value           = r.nombre;
        document.getElementById('fac-cocateg').value              = r.categ    || '';
        document.getElementById('fac-recomenda').value            = r.recomenda || '';
        document.getElementById('fac-confact').value              = r.confact   || '';
        document.getElementById('fac-empresa-display').value      = r.empresa   || '';
        document.getElementById('fac-empresa').value              = r.empresa   || '';
        ocultarSuggsPrest();
        alertaSeleccionPrestador(r.recomenda || '', r.confact || '');
        document.getElementById('fac-coobrasoc').focus();
    }

    function ocultarSuggsPrest() {
        document.getElementById('fac-prest-suggs-wrap').style.display = 'none';
    }

    var $inpPrest = document.getElementById('fac-coprestado');
    $inpPrest.addEventListener('input', function () { buscarPrestador(this.value); });
    $inpPrest.addEventListener('focus', function () {
        if (this.value.trim().length >= 2) buscarPrestador(this.value);
    });

    // También buscar por nombre desde el campo de nombre
    document.getElementById('fac-prest-nombre-display').addEventListener('click', function () {
        this.removeAttribute('readonly');
        this.placeholder = 'Escribí para buscar…';
        this.style.background = '#fff';
        this.addEventListener('input', function () { buscarPrestador(this.value); }, { once: false });
        this.id = 'fac-prest-buscar-inline';
    });

    // Recargar sugerencias al cambiar filtros
    ['fac-prest-ocultar-adef','fac-prest-ocultar-baja'].forEach(function (id) {
        document.getElementById(id).addEventListener('change', function () {
            var q = document.getElementById('fac-coprestado').value;
            buscarPrestador(q || ' ');
        });
    });

    // ════════════════════════════════════════════════════════════════════
    //  AUTOCOMPLETE OBRA SOCIAL
    // ════════════════════════════════════════════════════════════════════
    var osTimer;

    function buscarOS(q) {
        clearTimeout(osTimer);
        if (!q) { ocultarSuggsOS(); return; }
        osTimer = setTimeout(function () {
            fetch('index.php?route=registro-facturas&action=buscar_os&q=' + encodeURIComponent(q),
                  { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (!res.ok) return;
                var $d = document.getElementById('fac-os-suggs');
                $d.innerHTML = '';
                if (!res.datos.length) { ocultarSuggsOS(); return; }
                res.datos.forEach(function (r) {
                    var a = document.createElement('a');
                    a.href = '#';
                    a.className = 'list-group-item list-group-item-action py-1 px-2';
                    a.style.borderBottom = '1px solid #f1f5f9';
                    a.innerHTML = '<span class="fw-bold font-monospace" style="color:#0284c7;font-size:.78rem;">'
                                + esc(r.cosoc) + '</span> — ' + esc(r.nombre);
                    a.addEventListener('mousedown', function (e) {
                        e.preventDefault();
                        document.getElementById('fac-coobrasoc').value          = r.cosoc;
                        document.getElementById('fac-os-nombre-display').value  = r.nombre;
                        ocultarSuggsOS();
                        document.getElementById('fac-cosucfac').focus();
                    });
                    $d.appendChild(a);
                });
                $d.style.display = 'block';
            });
        }, 280);
    }

    function ocultarSuggsOS() {
        document.getElementById('fac-os-suggs').style.display = 'none';
    }

    var $inpOS = document.getElementById('fac-coobrasoc');
    $inpOS.addEventListener('input', function () { buscarOS(this.value.trim()); });
    $inpOS.addEventListener('focus', function () {
        if (this.value.trim().length >= 1) buscarOS(this.value.trim());
    });

    // Cerrar sugerencias al click fuera
    document.addEventListener('click', function (e) {
        if (!e.target.closest('#fac-prest-suggs-wrap')
            && !e.target.closest('#fac-coprestado')
            && e.target.id !== 'fac-prest-buscar-inline') {
            ocultarSuggsPrest();
        }
        if (!e.target.closest('#fac-os-suggs') && e.target !== $inpOS) {
            ocultarSuggsOS();
        }
    });

    // ════════════════════════════════════════════════════════════════════
    //  GUARDAR
    // ════════════════════════════════════════════════════════════════════
    document.getElementById('fac-btn-guardar').addEventListener('click', guardar);

    function guardar() {
        ocultarAlertFac();

        var errores = [];
        if (!document.getElementById('fac-coprestado').value.trim())
            errores.push('Ingresá el código del Prestador.');
        if (!document.getElementById('fac-coobrasoc').value.trim())
            errores.push('Ingresá el código de Obra Social.');

        var suc = document.getElementById('fac-cosucfac').value.trim();
        var nro = document.getElementById('fac-conrofac').value.trim();
        padFac(document.getElementById('fac-cosucfac'), 4);
        padFac(document.getElementById('fac-conrofac'), 8);
        if (!suc || !nro)
            errores.push('Completá el N° de Factura (punto de venta y número).');

        if (errores.length) { mostrarAlertFac(errores.join(' ')); return; }

        var btn = document.getElementById('fac-btn-guardar');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Guardando…';

        fetch('index.php?route=registro-facturas&action=guardar', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: new FormData(document.getElementById('form-ingresar-factura'))
        })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-floppy-disk me-1"></i>Guardar';

            if (!res.ok) { mostrarAlertFac(res.error || 'Error al guardar.'); return; }

            bootstrap.Modal.getOrCreateInstance(
                document.getElementById('modalIngresarFactura')
            ).hide();

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

    // ── Alert helpers ──────────────────────────────────────────────────
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
