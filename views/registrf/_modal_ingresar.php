<?php
/**
 * views/registrf/_modal_ingresar.php
 * ─────────────────────────────────────────────────────────────────────────
 * Modal: Alta / Ingreso de Factura — equivalente web del formulario VFP
 * registrf.SCT / registrf.SCX
 * ─────────────────────────────────────────────────────────────────────────
 */
$csrfToken     = $_SESSION['csrf_token'] ?? '';
$usuarioSesion = strtoupper($_SESSION['user']['username'] ?? 'SIS');
?>

<!-- ╔══════════════════════════════════════════════════════════════════╗
     ║  MODAL: INGRESO DE FACTURA                                      ║
     ╚══════════════════════════════════════════════════════════════════╝ -->
<div class="modal fade" id="modalIngresarFactura" tabindex="-1"
     aria-labelledby="modalIngresarFacturaLabel" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="false">

    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content" style="border-radius:12px; overflow:hidden;">

        <!-- ── Header ──────────────────────────────────────────────────── -->
        <div class="modal-header py-2 px-4"
             style="background:linear-gradient(90deg,#0d47a1,#1976d2); color:#fff; border-bottom:none;">
            <div class="d-flex align-items-center gap-3">
                <div style="width:38px;height:38px;border-radius:50%;
                            background:rgba(255,255,255,.2);
                            display:flex;align-items:center;justify-content:center;">
                    <i class="fa-solid fa-file-invoice-dollar"></i>
                </div>
                <div>
                    <h6 class="modal-title mb-0 fw-bold" id="modalIngresarFacturaLabel">
                        Registración de Facturas
                    </h6>
                    <small style="opacity:.85; font-size:0.73rem;">Ingreso Carátula Factura Prestador</small>
                </div>
            </div>
            <button type="button" class="btn-close btn-close-white"
                    data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>

        <!-- ── Body ────────────────────────────────────────────────────── -->
        <div class="modal-body px-4 py-3">
        <form id="form-ingresar-factura" autocomplete="off" novalidate>
        <input type="hidden" name="csrf_token"  value="<?= $csrfToken ?>">
        <input type="hidden" id="fac-cocateg"   name="COCATEG"   value="">
        <input type="hidden" id="fac-conomprest" name="CONOMPREST" value="">

        <!-- ═══ FILA 1: Fecha · Período · Prestador ═══════════════════ -->
        <div class="row g-2 mb-2 align-items-end">

            <div class="col-auto">
                <label class="form-label mb-1" style="font-size:.75rem;font-weight:600;">Fecha</label>
                <input type="date" id="fac-cofecha" name="COFECHA"
                       class="form-control form-control-sm"
                       style="width:132px;font-size:.82rem;"
                       value="<?= date('Y-m-d') ?>">
            </div>

            <div class="col-auto">
                <label class="form-label mb-1" style="font-size:.75rem;font-weight:600;">Período</label>
                <input type="text" id="fac-coperiodo" name="COPERIODO"
                       class="form-control form-control-sm text-center"
                       style="width:72px;font-size:.82rem;font-family:monospace;letter-spacing:1px;"
                       maxlength="4" placeholder="<?= date('ym') ?>" value="<?= date('ym') ?>">
                <div style="font-size:.67rem;color:#94a3b8;text-align:center;">AAMM</div>
            </div>

            <!-- Prestador -->
            <div class="col position-relative">
                <label class="form-label mb-1" style="font-size:.75rem;font-weight:600;">Prestador</label>
                <div class="input-group input-group-sm">
                    <input type="text" id="fac-coprestado" name="COPRESTADO"
                           class="form-control"
                           style="max-width:90px;font-size:.82rem;font-family:monospace;font-weight:600;"
                           placeholder="Código" maxlength="15">
                    <input type="text" id="fac-prest-nombre-display"
                           class="form-control" style="font-size:.82rem;background:#f8fafc;"
                           placeholder="Nombre del prestador…" readonly>
                    <button type="button" id="fac-btn-buscar-prest"
                            class="btn btn-sm btn-outline-primary" title="Buscar (F3)">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </button>
                </div>
                <div id="fac-prest-suggs" class="list-group shadow"
                     style="display:none;position:absolute;z-index:2000;max-height:200px;
                            overflow-y:auto;min-width:360px;font-size:.8rem;border-radius:6px;"></div>
            </div>
        </div>

        <!-- ═══ FILA 2: Obra Social ════════════════════════════════════ -->
        <div class="row g-2 mb-2 align-items-end">
            <div class="col position-relative">
                <label class="form-label mb-1" style="font-size:.75rem;font-weight:600;">Obra Social</label>
                <div class="input-group input-group-sm">
                    <input type="text" id="fac-coobrasoc" name="COOBRASOC"
                           class="form-control"
                           style="max-width:90px;font-size:.82rem;font-family:monospace;font-weight:600;"
                           placeholder="Código" maxlength="10">
                    <input type="text" id="fac-os-nombre-display"
                           class="form-control" style="font-size:.82rem;background:#f8fafc;"
                           placeholder="Nombre de la obra social…" readonly>
                    <button type="button" id="fac-btn-buscar-os"
                            class="btn btn-sm btn-outline-primary" title="Buscar OS">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </button>
                </div>
                <div id="fac-os-suggs" class="list-group shadow"
                     style="display:none;position:absolute;z-index:2000;max-height:180px;
                            overflow-y:auto;min-width:340px;font-size:.8rem;border-radius:6px;"></div>
            </div>
        </div>

        <!-- ═══ FILA 3: N° Factura · Fecha Recibido · Tipo · Fecha Fac ═ -->
        <div class="row g-2 mb-2 align-items-end">

            <div class="col-auto">
                <label class="form-label mb-1" style="font-size:.75rem;font-weight:600;">Factura N°</label>
                <div class="input-group input-group-sm">
                    <input type="text" id="fac-cosucfac" name="COSUCFAC"
                           class="form-control text-center"
                           style="width:65px;font-size:.82rem;font-family:monospace;"
                           placeholder="0001" maxlength="4">
                    <span class="input-group-text px-1">–</span>
                    <input type="text" id="fac-conrofac" name="CONROFAC"
                           class="form-control"
                           style="width:108px;font-size:.82rem;font-family:monospace;"
                           placeholder="00000000" maxlength="10">
                </div>
            </div>

            <div class="col-auto">
                <label class="form-label mb-1" style="font-size:.75rem;font-weight:600;">Fecha Recibido</label>
                <input type="date" id="fac-cofecrecib" name="COFECRECIB"
                       class="form-control form-control-sm"
                       style="width:140px;font-size:.82rem;" value="<?= date('Y-m-d') ?>">
            </div>

            <div class="col-auto">
                <label class="form-label mb-1 d-block" style="font-size:.75rem;font-weight:600;">Factura</label>
                <div class="d-flex gap-3 align-items-center" style="padding-top:3px;">
                    <div class="form-check mb-0">
                        <input class="form-check-input" type="radio" name="COTIPO"
                               id="fac-tipo-fisica" value="F" checked>
                        <label class="form-check-label" for="fac-tipo-fisica" style="font-size:.8rem;">Física</label>
                    </div>
                    <div class="form-check mb-0">
                        <input class="form-check-input" type="radio" name="COTIPO"
                               id="fac-tipo-online" value="O">
                        <label class="form-check-label" for="fac-tipo-online" style="font-size:.8rem;">On-Line</label>
                    </div>
                </div>
            </div>

            <div class="col-auto">
                <label class="form-label mb-1" style="font-size:.75rem;font-weight:600;">Fecha Factura</label>
                <input type="date" id="fac-cofecfac" name="COFECFAC"
                       class="form-control form-control-sm"
                       style="width:140px;font-size:.82rem;" value="<?= date('Y-m-d') ?>">
            </div>
        </div>

        <!-- ═══ Separador ══════════════════════════════════════════════ -->
        <hr class="my-2" style="border-color:#e2e8f0;">

        <!-- ═══ FILA 4: Importes ════════════════════════════════════════ -->
        <div class="row g-2 mb-1 align-items-end">

            <div class="col-auto">
                <label class="form-label mb-1" style="font-size:.75rem;font-weight:600;">Cantidad</label>
                <input type="number" id="fac-cocantidad" name="COCANTIDAD"
                       class="form-control form-control-sm text-end"
                       style="width:80px;font-size:.82rem;" min="0" step="1" value="0">
            </div>

            <div class="col-auto">
                <label class="form-label mb-1" style="font-size:.75rem;font-weight:600;">Importe</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text px-1" style="font-size:.78rem;">$</span>
                    <input type="number" id="fac-coimporte" name="COIMPORTE"
                           class="form-control text-end fac-calc"
                           style="width:110px;font-size:.82rem;font-family:monospace;"
                           min="0" step="0.01" value="0.00">
                </div>
            </div>

            <div class="col-auto">
                <label class="form-label mb-1" style="font-size:.75rem;font-weight:600;">I.V.A.</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text px-1" style="font-size:.78rem;">$</span>
                    <input type="number" id="fac-coiva" name="COIVA"
                           class="form-control text-end fac-calc"
                           style="width:110px;font-size:.82rem;font-family:monospace;"
                           min="0" step="0.01" value="0.00">
                </div>
            </div>

            <div class="col-auto">
                <label class="form-label mb-1" style="font-size:.75rem;font-weight:600;">Coseguro</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text px-1" style="font-size:.78rem;">$</span>
                    <input type="number" id="fac-cocoseguro" name="COCOSEGURO"
                           class="form-control text-end fac-calc"
                           style="width:110px;font-size:.82rem;font-family:monospace;"
                           min="0" step="0.01" value="0.00">
                </div>
            </div>

            <div class="col-auto">
                <label class="form-label mb-1 fw-bold" style="font-size:.75rem;color:#1d4ed8;">Total</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text px-1" style="font-size:.78rem;background:#eff6ff;color:#1d4ed8;font-weight:700;">$</span>
                    <input type="number" id="fac-cototalfac" name="COTOTALFAC"
                           class="form-control text-end fw-bold"
                           style="width:130px;font-size:.85rem;font-family:monospace;
                                  background:#eff6ff;color:#1d4ed8;border-color:#bfdbfe;"
                           readonly>
                </div>
            </div>
        </div>

        <!-- ═══ FILA 5: Monto $ · Cant. Prest. · Tiene Factura ════════ -->
        <div class="row g-2 mb-1 align-items-end">

            <div class="col-auto">
                <label class="form-label mb-1" style="font-size:.75rem;font-weight:600;">Monto $</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text px-1" style="font-size:.78rem;">$</span>
                    <input type="number" id="fac-comonto" name="COMONTO"
                           class="form-control text-end"
                           style="width:120px;font-size:.82rem;font-family:monospace;"
                           min="0" step="0.01" value="0.00">
                </div>
            </div>

            <div class="col-auto">
                <label class="form-label mb-1" style="font-size:.75rem;font-weight:600;">Cant. Prest.</label>
                <input type="number" id="fac-cocantprest" name="COCANTPREST"
                       class="form-control form-control-sm text-end"
                       style="width:90px;font-size:.82rem;"
                       min="0" step="1" value="0">
            </div>

            <div class="col-auto ms-3">
                <label class="form-label mb-1 d-block" style="font-size:.75rem;font-weight:600;">Tiene Factura</label>
                <div class="d-flex gap-3 align-items-center" style="padding-top:3px;">
                    <div class="form-check mb-0">
                        <input class="form-check-input" type="radio" name="COTIENEFAC"
                               id="fac-tienefac-si" value="S" checked>
                        <label class="form-check-label" for="fac-tienefac-si"
                               style="font-size:.8rem;font-weight:600;color:#16a34a;">SÍ</label>
                    </div>
                    <div class="form-check mb-0">
                        <input class="form-check-input" type="radio" name="COTIENEFAC"
                               id="fac-tienefac-no" value="N">
                        <label class="form-check-label" for="fac-tienefac-no"
                               style="font-size:.8rem;font-weight:600;color:#dc2626;">NO</label>
                    </div>
                </div>
            </div>

            <div class="col-auto ms-auto">
                <label class="form-label mb-1" style="font-size:.75rem;font-weight:600;">Usuario</label>
                <input type="text" name="COUSUARIO"
                       class="form-control form-control-sm"
                       style="width:100px;font-size:.82rem;background:#f8fafc;"
                       value="<?= htmlspecialchars($usuarioSesion) ?>" readonly>
            </div>
        </div>

        <!-- ═══ Alertas ════════════════════════════════════════════════ -->
        <div id="fac-alert" class="alert alert-danger d-none mt-2 py-2 px-3"
             style="font-size:.82rem;border-radius:8px;">
            <i class="fa-solid fa-triangle-exclamation me-1"></i>
            <span id="fac-alert-msg"></span>
        </div>

        </form>
        </div><!-- /.modal-body -->

        <!-- ── Footer ──────────────────────────────────────────────────── -->
        <div class="modal-footer py-2 px-4 d-flex justify-content-end gap-2"
             style="background:#f8fafc;border-top:1px solid #e2e8f0;">
            <button type="button" class="btn btn-sm btn-outline-secondary"
                    data-bs-dismiss="modal" style="font-size:.78rem;">
                <i class="fa-solid fa-xmark me-1"></i>Cancelar
            </button>
            <button type="button" id="fac-btn-guardar"
                    class="btn btn-sm btn-primary fw-semibold"
                    style="font-size:.78rem;min-width:100px;">
                <i class="fa-solid fa-floppy-disk me-1"></i>Guardar
            </button>
        </div>

    </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div>

<script>
(function () {
    'use strict';

    window.abrirModalIngresarFactura = function () {
        resetFac();
        bootstrap.Modal.getOrCreateInstance(
            document.getElementById('modalIngresarFactura')
        ).show();
        setTimeout(function () { document.getElementById('fac-coprestado').focus(); }, 300);
    };

    function resetFac() {
        ['fac-coimporte','fac-coiva','fac-cocoseguro','fac-cototalfac','fac-comonto'].forEach(
            function (id) { document.getElementById(id).value = '0.00'; }
        );
        ['fac-cocantidad','fac-cocantprest'].forEach(
            function (id) { document.getElementById(id).value = '0'; }
        );
        document.getElementById('fac-coprestado').value           = '';
        document.getElementById('fac-prest-nombre-display').value = '';
        document.getElementById('fac-coobrasoc').value            = '';
        document.getElementById('fac-os-nombre-display').value    = '';
        document.getElementById('fac-cocateg').value              = '';
        document.getElementById('fac-conomprest').value           = '';
        document.getElementById('fac-cosucfac').value             = '';
        document.getElementById('fac-conrofac').value             = '';
        var hoy  = new Date().toISOString().substring(0, 10);
        document.getElementById('fac-cofecha').value    = hoy;
        document.getElementById('fac-cofecrecib').value = hoy;
        document.getElementById('fac-cofecfac').value   = hoy;
        var d = new Date();
        document.getElementById('fac-coperiodo').value  =
            String(d.getFullYear()).substring(2) + String(d.getMonth() + 1).padStart(2, '0');
        document.getElementById('fac-tipo-fisica').checked   = true;
        document.getElementById('fac-tienefac-si').checked   = true;
        ocultarAlertFac();
    }

    /* Cálculo automático del total */
    function calcTotal() {
        var t = (parseFloat(document.getElementById('fac-coimporte').value)  || 0)
              + (parseFloat(document.getElementById('fac-coiva').value)      || 0)
              + (parseFloat(document.getElementById('fac-cocoseguro').value) || 0);
        document.getElementById('fac-cototalfac').value = t.toFixed(2);
    }
    document.querySelectorAll('.fac-calc').forEach(function (el) {
        el.addEventListener('input', calcTotal);
    });

    /* ── Autocomplete Prestador ───────────────────────────────────────── */
    var prestTimer;
    function buscarPrestador(q) {
        if (!q || q.length < 2) { ocultarSuggs('fac-prest-suggs'); return; }
        clearTimeout(prestTimer);
        prestTimer = setTimeout(function () {
            fetch('index.php?route=registro-facturas&action=buscar_prestador&q=' + encodeURIComponent(q),
                  { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); })
            .then(function (res) { if (res.ok) renderSuggsPrest(res.datos || []); });
        }, 250);
    }
    function renderSuggsPrest(rows) {
        var $d = document.getElementById('fac-prest-suggs');
        $d.innerHTML = '';
        if (!rows.length) { ocultarSuggs('fac-prest-suggs'); return; }
        rows.forEach(function (r) {
            var a = document.createElement('a');
            a.href = '#'; a.className = 'list-group-item list-group-item-action py-1 px-2';
            a.style.cssText = 'font-size:.79rem;border-bottom:1px solid #f1f5f9;';
            a.innerHTML = '<span class="fw-semibold text-primary font-monospace">' + esc(r.codigo) + '</span>'
                        + ' — ' + esc(r.nombre)
                        + (r.categ ? ' <span class="badge bg-secondary bg-opacity-50 ms-1" style="font-size:.65rem;">' + esc(r.categ) + '</span>' : '');
            a.addEventListener('click', function (e) {
                e.preventDefault();
                document.getElementById('fac-coprestado').value           = r.codigo;
                document.getElementById('fac-prest-nombre-display').value = r.nombre;
                document.getElementById('fac-conomprest').value           = r.nombre;
                document.getElementById('fac-cocateg').value              = r.categ || '';
                ocultarSuggs('fac-prest-suggs');
                document.getElementById('fac-coobrasoc').focus();
            });
            $d.appendChild(a);
        });
        $d.style.display = 'block';
    }
    var $inpPrest = document.getElementById('fac-coprestado');
    $inpPrest.addEventListener('input', function () { buscarPrestador(this.value.trim()); });
    $inpPrest.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') { e.preventDefault(); buscarPrestador(this.value.trim()); }
    });
    document.getElementById('fac-btn-buscar-prest').addEventListener('click', function () {
        buscarPrestador($inpPrest.value.trim() || ' ');
    });

    /* ── Autocomplete Obra Social ─────────────────────────────────────── */
    var osTimer;
    function buscarOS(q) {
        if (!q) { ocultarSuggs('fac-os-suggs'); return; }
        clearTimeout(osTimer);
        osTimer = setTimeout(function () {
            fetch('index.php?route=registro-facturas&action=buscar_os&q=' + encodeURIComponent(q),
                  { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (!res.ok) return;
                var $d = document.getElementById('fac-os-suggs');
                $d.innerHTML = '';
                if (!res.datos.length) { ocultarSuggs('fac-os-suggs'); return; }
                res.datos.forEach(function (r) {
                    var a = document.createElement('a');
                    a.href = '#'; a.className = 'list-group-item list-group-item-action py-1 px-2';
                    a.style.cssText = 'font-size:.79rem;border-bottom:1px solid #f1f5f9;';
                    a.innerHTML = '<span class="fw-semibold text-info font-monospace">' + esc(r.cosoc) + '</span>'
                                + ' — ' + esc(r.nombre);
                    a.addEventListener('click', function (e) {
                        e.preventDefault();
                        document.getElementById('fac-coobrasoc').value          = r.cosoc;
                        document.getElementById('fac-os-nombre-display').value  = r.nombre;
                        ocultarSuggs('fac-os-suggs');
                        document.getElementById('fac-cosucfac').focus();
                    });
                    $d.appendChild(a);
                });
                $d.style.display = 'block';
            });
        }, 250);
    }
    var $inpOS = document.getElementById('fac-coobrasoc');
    $inpOS.addEventListener('input', function () { buscarOS(this.value.trim()); });
    $inpOS.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') { e.preventDefault(); buscarOS(this.value.trim()); }
    });
    document.getElementById('fac-btn-buscar-os').addEventListener('click', function () {
        buscarOS($inpOS.value.trim() || ' ');
    });

    /* Cerrar sugerencias al click fuera */
    document.addEventListener('click', function (e) {
        ['fac-prest-suggs','fac-os-suggs'].forEach(function (id) {
            var $d = document.getElementById(id);
            if ($d && !$d.contains(e.target)) ocultarSuggs(id);
        });
    });
    function ocultarSuggs(id) {
        var el = document.getElementById(id);
        if (el) el.style.display = 'none';
    }

    /* ── Guardar ──────────────────────────────────────────────────────── */
    document.getElementById('fac-btn-guardar').addEventListener('click', function () {
        ocultarAlertFac();
        var errores = [];
        if (!document.getElementById('fac-coprestado').value.trim())
            errores.push('Ingresá el código del Prestador.');
        if (!document.getElementById('fac-coobrasoc').value.trim())
            errores.push('Ingresá el código de Obra Social.');
        if (!document.getElementById('fac-cosucfac').value.trim() || !document.getElementById('fac-conrofac').value.trim())
            errores.push('Completá el N° de Factura (punto de venta y número).');
        if (parseFloat(document.getElementById('fac-cototalfac').value) === 0)
            errores.push('El Total no puede ser cero.');
        if (errores.length) { mostrarAlertFac(errores.join(' ')); return; }

        var btn = this;
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
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalIngresarFactura')).hide();
            var toast = document.createElement('div');
            toast.className = 'alert alert-success position-fixed shadow-sm';
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
    });

    function mostrarAlertFac(msg) {
        document.getElementById('fac-alert-msg').textContent = msg;
        document.getElementById('fac-alert').classList.remove('d-none');
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
