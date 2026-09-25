<?php
/**
 * Modal de filtros para listados de Registración (resu1-1 / resu1-2).
 */
if (!class_exists('DateHelper')) {
    require_once __DIR__ . '/../../classes/DateHelper.php';
}
if (!class_exists('RegistrfModel')) {
    require_once __DIR__ . '/../../models/RegistrfModel.php';
}
$rfPeriodoDef = DateHelper::getPeriodoAnterior();
$rfTiposPrest = [];
try {
    $rfTiposPrest = (new RegistrfModel())->listarTiposPrestador();
} catch (Exception $e) {
    $rfTiposPrest = [];
}
?>
<div class="modal fade" id="modalListadosRf" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:10px;overflow:hidden;">
            <div class="modal-header py-2 px-3"
                 style="background:linear-gradient(90deg,#0d47a1,#1976d2);color:#fff;border-bottom:none;">
                <h6 class="modal-title mb-0 fw-bold" id="rf-lst-titulo">Listados de Registración</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body" style="background:#f8fafc;">
                <input type="hidden" id="rf-lst-tipo-rpt" value="detallado">
                <div class="row g-2">
                    <div class="col-6">
                        <label class="form-label mb-0" style="font-size:.75rem;color:#64748b;">Desde (AA/MM)</label>
                        <input type="text" id="rf-lst-desde" class="form-control form-control-sm text-center rf-periodo"
                               maxlength="5" placeholder="26/07" inputmode="numeric" autocomplete="off"
                               value="<?= htmlspecialchars($rfPeriodoDef) ?>"
                               style="font-family:monospace;letter-spacing:1px;">
                    </div>
                    <div class="col-6">
                        <label class="form-label mb-0" style="font-size:.75rem;color:#64748b;">Hasta (AA/MM)</label>
                        <input type="text" id="rf-lst-hasta" class="form-control form-control-sm text-center rf-periodo"
                               maxlength="5" placeholder="26/07" inputmode="numeric" autocomplete="off"
                               value="<?= htmlspecialchars($rfPeriodoDef) ?>"
                               style="font-family:monospace;letter-spacing:1px;">
                    </div>
                    <div class="col-12">
                        <label class="form-label mb-0" style="font-size:.75rem;color:#64748b;">Obra Social</label>
                        <div class="input-group input-group-sm">
                            <input type="text" id="rf-lst-os" class="form-control"
                                   placeholder="Todas" maxlength="8"
                                   style="max-width:110px;font-family:monospace;font-weight:700;">
                            <input type="text" id="rf-lst-os-nom" class="form-control" placeholder="Todas" readonly
                                   style="background:#e8f4fd;">
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label mb-0" style="font-size:.75rem;color:#64748b;">Prestador</label>
                        <div class="input-group input-group-sm">
                            <input type="text" id="rf-lst-prest" class="form-control"
                                   placeholder="Todos" maxlength="18"
                                   style="max-width:110px;font-family:monospace;font-weight:700;">
                            <input type="text" id="rf-lst-prest-nom" class="form-control" placeholder="Todos" readonly
                                   style="background:#e8f4fd;">
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label mb-0" style="font-size:.75rem;color:#64748b;">Tipo de Prestador</label>
                        <select id="rf-lst-tipopre" class="form-select form-select-sm">
                            <option value="">Todas</option>
                            <option value="Cabecera">Cabecera</option>
                            <?php foreach ($rfTiposPrest as $tp): ?>
                                <option value="<?= htmlspecialchars($tp, ENT_QUOTES, 'UTF-8') ?>">
                                    <?= htmlspecialchars($tp, ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div id="rf-lst-err" class="alert alert-danger d-none mt-2 py-1 px-2" style="font-size:.78rem;"></div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" id="rf-lst-aceptar" class="btn btn-sm btn-primary fw-semibold">
                    <i class="fa-solid fa-check me-1"></i>Aceptar
                </button>
            </div>
        </div>
    </div>
</div>
<script>
(function () {
    if (window.__rfPeriodoUx) {
        return;
    }
    window.__rfPeriodoUx = true;

    function rfSelectPeriodo(el) {
        try { el.select(); } catch (eSel) {}
    }

    function rfMaskPeriodoLive(el, ev) {
        var tipo = (ev && ev.inputType) ? ev.inputType : '';
        var v = String(el.value || '').replace(/[^\d/]/g, '');
        if (tipo.indexOf('delete') === 0 || tipo === 'deleteByCut' || tipo === 'historyUndo' || tipo === 'historyRedo') {
            if (el.value !== v.substring(0, 5)) {
                el.value = v.substring(0, 5);
            }
            return;
        }
        if (/^\d{2}\/\d{0,2}$/.test(v)) {
            if (el.value !== v.substring(0, 5)) {
                el.value = v.substring(0, 5);
            }
            return;
        }
        var d = v.replace(/\D/g, '').substring(0, 4);
        var next = (d.length > 2) ? (d.substring(0, 2) + '/' + d.substring(2)) : d;
        if (el.value !== next) {
            el.value = next;
        }
    }

    function rfNormPeriodoInput(el) {
        var d = String(el.value || '').replace(/\D/g, '').substring(0, 4);
        if (d.length === 0) {
            el.value = '';
            el.classList.remove('is-invalid');
            return false;
        }
        if (d.length === 4) {
            var mm = parseInt(d.substring(2, 4), 10);
            if (mm >= 1 && mm <= 12) {
                el.value = d.substring(0, 2) + '/' + ('0' + mm).slice(-2);
                el.classList.remove('is-invalid');
                return true;
            }
            el.value = d.substring(0, 2) + '/' + d.substring(2, 4);
            el.classList.add('is-invalid');
            return false;
        }
        if (d.length > 2) {
            el.value = d.substring(0, 2) + '/' + d.substring(2);
        } else {
            el.value = d;
        }
        el.classList.add('is-invalid');
        return false;
    }

    window.rfSelectPeriodo = rfSelectPeriodo;
    window.rfMaskPeriodoLive = rfMaskPeriodoLive;
    window.rfNormPeriodoInput = rfNormPeriodoInput;

    function bindPeriodo(el) {
        if (!el || el.getAttribute('data-rf-periodo-bound') === '1') {
            return;
        }
        el.setAttribute('data-rf-periodo-bound', '1');
        el.addEventListener('focus', function () {
            var self = this;
            setTimeout(function () { rfSelectPeriodo(self); }, 0);
        });
        el.addEventListener('click', function () {
            rfSelectPeriodo(this);
        });
        el.addEventListener('mouseup', function (e) {
            e.preventDefault();
            rfSelectPeriodo(this);
        });
        el.addEventListener('input', function (e) {
            rfMaskPeriodoLive(this, e);
        });
        el.addEventListener('blur', function () {
            rfNormPeriodoInput(this);
        });
        el.addEventListener('keydown', function (e) {
            var k = e.key || '';
            if (k === 'Backspace' || k === 'Delete' || e.which === 8 || e.which === 46) {
                return;
            }
        });
    }

    document.querySelectorAll('.rf-periodo, #rf-lst-desde, #rf-lst-hasta').forEach(bindPeriodo);
})();
</script>
