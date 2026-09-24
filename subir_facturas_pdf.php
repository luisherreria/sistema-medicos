<?php
/**
 * subir_facturas_pdf.php
 * Dropzone: al completar la cola redirige a registro_facturas_pdf.php
 */
require_once dirname(__FILE__) . '/includes/facturas_pdf_common.php';

rfpRequireAuth();
rfpRequirePermiso();
rfpAsegurarTabla();

$pageTitle  = 'COMEDICA — Subir Facturas PDF';
$breadcrumb = array(
    array('label' => 'Carga de Datos'),
    array('label' => 'Registración de Facturas', 'url' => 'index.php?route=registro-facturas'),
    array('label' => 'Subir Facturas PDF'),
);
$csrf = isset($_SESSION['csrf_token']) ? $_SESSION['csrf_token'] : '';

require_once dirname(__FILE__) . '/views/layouts/header.php';
?>

<link rel="stylesheet" href="https://unpkg.com/dropzone@5.9.3/dist/min/dropzone.min.css">

<div class="rfp-topbar d-flex align-items-center justify-content-between flex-wrap gap-2">
    <div class="d-flex align-items-center gap-2">
        <div class="rfp-topbar-icon"><i class="fa-solid fa-file-arrow-up"></i></div>
        <h1 class="rfp-topbar-title mb-0">Subir Facturas PDF</h1>
    </div>
    <a href="index.php?route=registro-facturas-pdf"
       class="btn btn-sm text-white fw-semibold"
       style="background:rgba(255,255,255,.18);border:1px solid rgba(255,255,255,.45);font-size:.78rem;">
        <i class="fa-solid fa-list me-1"></i>Volver a Pendientes
    </a>
</div>

<p class="text-muted mb-2" style="font-size:.8rem;">
    Arrastrá uno o varios PDF. Si el archivo es un <strong>escaneo</strong> (sin texto),
    el navegador lee la imagen con OCR y después se abre la grilla de pendientes.
</p>

<div id="rfp-ocr-status" class="alert alert-info py-2 d-none" style="font-size:.8rem;"></div>

<form action="procesar_pdf.php" class="dropzone" id="dz-facturas"></form>

<style>
.rfp-topbar {
    background: linear-gradient(90deg, #0d47a1 0%, #1565c0 60%, #1976d2 100%);
    color: #fff;
    border-radius: 10px;
    padding: 12px 16px;
    margin-bottom: 14px;
    box-shadow: 0 2px 8px rgba(13, 71, 161, .28);
}
.rfp-topbar-icon {
    width: 34px; height: 34px; border-radius: 8px;
    background: rgba(255,255,255,.18);
    display: flex; align-items: center; justify-content: center;
}
.rfp-topbar-title { font-size: .95rem; font-weight: 700; }
#dz-facturas {
    border: 2px dashed #93c5fd;
    background: #f8fbff;
    border-radius: 12px;
    min-height: 220px;
}
#dz-facturas .dz-message { font-weight: 600; color: #1e293b; }
</style>

<?php require_once dirname(__FILE__) . '/views/layouts/footer.php'; ?>

<script src="https://unpkg.com/dropzone@5.9.3/dist/min/dropzone.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/tesseract.js@4.1.4/dist/tesseract.min.js"></script>
<script>
(function () {
    if (typeof Dropzone === 'undefined') return;
    Dropzone.autoDiscover = false;
    var CSRF = <?= json_encode($csrf) ?>;
    var el = document.getElementById('dz-facturas');
    if (el && el.dropzone) {
        el.dropzone.destroy();
    }

    if (window.pdfjsLib) {
        pdfjsLib.GlobalWorkerOptions.workerSrc =
            'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
    }

    var $st = document.getElementById('rfp-ocr-status');
    function setStatus(msg, tipo) {
        if (!$st) return;
        $st.className = 'alert alert-' + (tipo || 'info') + ' py-2';
        $st.style.fontSize = '.8rem';
        $st.innerHTML = msg;
        $st.classList.remove('d-none');
    }

    function extraerTextoPdf(file) {
        if (!window.pdfjsLib) {
            return Promise.resolve('');
        }
        return file.arrayBuffer().then(function (buf) {
            return pdfjsLib.getDocument({ data: buf }).promise;
        }).then(function (pdf) {
            return pdf.getPage(1).then(function (page) {
                return page.getTextContent().then(function (tc) {
                    var txt = '';
                    if (tc && tc.items) {
                        for (var i = 0; i < tc.items.length; i++) {
                            txt += (tc.items[i].str || '') + ' ';
                        }
                    }
                    txt = txt.replace(/\s+/g, ' ').trim();
                    if (txt.length >= 30) {
                        return txt;
                    }
                    setStatus('<i class="fa-solid fa-spinner fa-spin me-1"></i>Escaneo detectado. Leyendo imagen de <strong>'
                        + file.name.replace(/</g, '') + '</strong>… esto puede tardar unos segundos.', 'warning');
                    var viewport = page.getViewport({ scale: 2.6 });
                    var canvas = document.createElement('canvas');
                    canvas.width = viewport.width;
                    canvas.height = viewport.height;
                    var ctx = canvas.getContext('2d');
                    return page.render({ canvasContext: ctx, viewport: viewport }).promise.then(function () {
                        if (typeof Tesseract === 'undefined') {
                            return '';
                        }
                        return Tesseract.recognize(canvas, 'spa', {
                            logger: function (m) {
                                if (m && m.status === 'recognizing text' && m.progress) {
                                    var pct = Math.round(m.progress * 100);
                                    setStatus('Reconociendo texto… ' + pct + '%', 'warning');
                                }
                            },
                            tessedit_pageseg_mode: 6
                        }).then(function (res) {
                            return (res && res.data && res.data.text) ? res.data.text : '';
                        });
                    });
                });
            });
        }).catch(function () {
            return '';
        });
    }

    var colaOcr = [];
    var ocrAndando = false;

    function siguienteOcr(dz) {
        if (ocrAndando) return;
        if (!colaOcr.length) {
            setStatus('Subiendo archivos…', 'info');
            dz.processQueue();
            return;
        }
        ocrAndando = true;
        var file = colaOcr.shift();
        setStatus('Preparando <strong>' + file.name.replace(/</g, '') + '</strong>…', 'info');
        extraerTextoPdf(file).then(function (texto) {
            file.ocrText = texto || '';
        }).catch(function () {
            file.ocrText = '';
        }).then(function () {
            ocrAndando = false;
            siguienteOcr(dz);
        });
    }

    new Dropzone('#dz-facturas', {
        url: 'procesar_pdf.php',
        paramName: 'file',
        acceptedFiles: 'application/pdf,.pdf',
        maxFilesize: 10,
        parallelUploads: 1,
        autoProcessQueue: false,
        addRemoveLinks: false,
        dictDefaultMessage: '⚡ Arrastrá los PDF de facturas aquí o hacé clic para seleccionar',
        dictInvalidFileType: 'Solo se aceptan archivos PDF.',
        dictFileTooBig: 'El archivo es demasiado grande (máx. 10 MB).',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        init: function () {
            var dz = this;
            this.on('addedfile', function (file) {
                colaOcr.push(file);
                siguienteOcr(dz);
            });
            this.on('sending', function (file, xhr, formData) {
                formData.append('csrf_token', CSRF);
                formData.append('texto_ocr', file.ocrText || '');
            });
            this.on('queuecomplete', function () {
                window.location.href = 'index.php?route=registro-facturas-pdf';
            });
        }
    });
})();
</script>
