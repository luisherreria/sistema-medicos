/**
 * public/js/exportador.js
 * ─────────────────────────────────────────────────────────────────────────
 * Exportación reutilizable de tablas HTML a PDF y Excel (cliente).
 *
 * Requiere (cargados en el layout):
 *   - html2pdf.js  → window.html2pdf
 *   - SheetJS      → window.XLSX
 *
 * Uso:
 *   exportarTablaAPDF('mi-tabla', 'reporte.pdf');
 *   exportarTablaAExcel('mi-tabla', 'reporte.xlsx');
 *
 * Marcar columnas a omitir en el reporte:
 *   <th data-export="skip">Acciones</th>
 *   <td data-export="skip">...</td>
 * También se omiten columnas cuyo encabezado sea "Acciones".
 * ─────────────────────────────────────────────────────────────────────────
 */
(function (global) {
    'use strict';

    /**
     * Clona la tabla y quita columnas de acciones / data-export="skip".
     * Los checkboxes se reemplazan por texto SI/NO.
     *
     * @param {string} tablaId
     * @returns {HTMLTableElement|null}
     */
    function clonarTablaParaExport(tablaId) {
        var original = document.getElementById(tablaId);
        if (!original || original.tagName !== 'TABLE') {
            console.error('exportador: no se encontró la tabla #' + tablaId);
            return null;
        }

        var clone = original.cloneNode(true);

        // Índices de columnas a eliminar (por data-export="skip" o título "Acciones")
        var skipIdx = {};
        var ths = clone.querySelectorAll('thead th, thead td');
        ths.forEach(function (th, idx) {
            var label = (th.textContent || '').replace(/\s+/g, ' ').trim().toLowerCase();
            if (th.getAttribute('data-export') === 'skip' || label === 'acciones' || label === 'acción' || label === 'accion') {
                skipIdx[idx] = true;
            }
        });

        clone.querySelectorAll('tr').forEach(function (tr) {
            var cells = tr.children;
            // Recorrer de atrás hacia adelante para no desalinear índices
            for (var i = cells.length - 1; i >= 0; i--) {
                var cell = cells[i];
                if (skipIdx[i] || cell.getAttribute('data-export') === 'skip') {
                    tr.removeChild(cell);
                }
            }
        });

        // Checkboxes → SI / NO
        clone.querySelectorAll('input[type="checkbox"]').forEach(function (cb) {
            var span = document.createElement('span');
            span.textContent = cb.checked ? 'SI' : 'NO';
            if (cb.parentNode) {
                cb.parentNode.replaceChild(span, cb);
            }
        });

        return clone;
    }

    /**
     * Genera un PDF de la tabla y lo abre en una pestaña nueva (blob URL).
     *
     * @param {string} tablaId
     * @param {string} [nombreArchivo='exportacion.pdf']
     */
    function exportarTablaAPDF(tablaId, nombreArchivo) {
        if (typeof html2pdf === 'undefined') {
            alert('html2pdf.js no está disponible.');
            return;
        }

        var table = clonarTablaParaExport(tablaId);
        if (!table) return;

        nombreArchivo = (nombreArchivo || 'exportacion.pdf').replace(/\.pdf$/i, '') + '.pdf';
        var titulo = nombreArchivo.replace(/\.pdf$/i, '').replace(/[_-]+/g, ' ');

        var wrap = document.createElement('div');
        wrap.style.padding = '12px';
        wrap.style.fontSize = '9px';
        wrap.style.fontFamily = 'Segoe UI, Arial, sans-serif';
        wrap.style.color = '#111';

        var h = document.createElement('h3');
        h.textContent = titulo;
        h.style.margin = '0 0 10px 0';
        h.style.fontSize = '14px';
        wrap.appendChild(h);
        wrap.appendChild(table);

        // Abrir ventana en el mismo gesto del click (evita bloqueo de popups)
        var win = window.open('about:blank', '_blank');
        if (win) {
            win.document.write('<p style="font-family:sans-serif;padding:16px;">Generando PDF…</p>');
        }

        html2pdf()
            .set({
                margin: 8,
                filename: nombreArchivo,
                image: { type: 'jpeg', quality: 0.95 },
                html2canvas: { scale: 2, useCORS: true },
                jsPDF: { unit: 'mm', format: 'a4', orientation: 'landscape' }
            })
            .from(wrap)
            .outputPdf('bloburl')
            .then(function (blobUrl) {
                if (win && !win.closed) {
                    win.location.href = blobUrl;
                } else {
                    window.open(blobUrl, '_blank');
                }
            })
            .catch(function (err) {
                console.error('exportarTablaAPDF:', err);
                if (win && !win.closed) {
                    win.close();
                }
                alert('No se pudo generar el PDF.');
            });
    }

    /**
     * Descarga la tabla como archivo .xlsx (SheetJS).
     *
     * @param {string} tablaId
     * @param {string} [nombreArchivo='exportacion.xlsx']
     */
    function exportarTablaAExcel(tablaId, nombreArchivo) {
        if (typeof XLSX === 'undefined') {
            alert('SheetJS (XLSX) no está disponible.');
            return;
        }

        var table = clonarTablaParaExport(tablaId);
        if (!table) return;

        nombreArchivo = (nombreArchivo || 'exportacion.xlsx').replace(/\.xlsx$/i, '') + '.xlsx';
        var sheetName = nombreArchivo.replace(/\.xlsx$/i, '').substring(0, 31) || 'Hoja1';

        var wb = XLSX.utils.table_to_book(table, { sheet: sheetName });
        XLSX.writeFile(wb, nombreArchivo);
    }

    // API global
    global.exportarTablaAPDF = exportarTablaAPDF;
    global.exportarTablaAExcel = exportarTablaAExcel;
    global.clonarTablaParaExport = clonarTablaParaExport;

})(typeof window !== 'undefined' ? window : this);
