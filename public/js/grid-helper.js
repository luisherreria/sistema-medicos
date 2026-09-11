/**
 * public/js/grid-helper.js
 * ─────────────────────────────────────────────────────────────────────────
 * Inicialización automática de DataTables para el Sistema Médico COMEDICA.
 *
 * USO: Agregar la clase `datatable-medical` a cualquier <table> HTML.
 *   <table class="table datatable-medical" id="miTabla">...</table>
 *
 * La tabla se inicializa automáticamente con la configuración estándar
 * al cargar el DOM. No se requiere ningún JS adicional por vista.
 *
 * PERSONALIZACIÓN POR TABLA:
 *   Usar atributos data-* en el <table> para sobreescribir valores:
 *     data-page-length="50"      → Filas por página inicial
 *     data-order='[[2,"asc"]]'   → Ordenamiento inicial (JSON)
 *     data-no-buttons="true"     → Ocultar botones de exportación
 *     data-no-search="true"      → Ocultar buscador global
 * ─────────────────────────────────────────────────────────────────────────
 */

(function ($) {
    'use strict';

    // ── Traducción completa al español ────────────────────────────────────
    var LANG_ES = {
        sEmptyTable:      "No hay datos disponibles en la tabla",
        sInfo:            "Mostrando _START_ a _END_ de _TOTAL_ resultados",
        sInfoEmpty:       "Mostrando 0 a 0 de 0 resultados",
        sInfoFiltered:    "(filtrado de _MAX_ registros totales)",
        sInfoPostFix:     "",
        sInfoThousands:   ".",
        sLengthMenu:      "_MENU_ filas",
        sLoadingRecords:  "Cargando...",
        sProcessing:      '<div class="spinner-border spinner-border-sm text-primary" role="status"></div> Procesando...',
        sSearch:          "",
        sSearchPlaceholder: "Buscar...",
        sUrl:             "",
        sZeroRecords:     "No se encontraron registros coincidentes",
        oPaginate: {
            sFirst:    '<i class="fa-solid fa-angles-left"></i>',
            sLast:     '<i class="fa-solid fa-angles-right"></i>',
            sNext:     '<i class="fa-solid fa-angle-right"></i>',
            sPrevious: '<i class="fa-solid fa-angle-left"></i>'
        },
        oAria: {
            sSortAscending:  ": Activar para ordenar la columna de manera ascendente",
            sSortDescending: ": Activar para ordenar la columna de manera descendente"
        },
        buttons: {
            copyTitle:  'Copiado al portapapeles',
            copySuccess: {
                _: '%d filas copiadas',
                1: '1 fila copiada'
            }
        }
    };

    // ── Menú de filas por página estándar ────────────────────────────────
    var LENGTH_MENU = [
        [10, 25, 50, 100, 200, 500, 1000, -1],
        [10, 25, 50, 100, 200, 500, 1000, 'Mostrar todas']
    ];

    // ── Botones de exportación estándar ──────────────────────────────────
    function buildButtons(tableId) {
        var title = document.title || 'COMEDICA';
        return [
            {
                extend:    'excelHtml5',
                text:      '<i class="fa-solid fa-file-excel me-1"></i>Excel',
                className: 'btn btn-success btn-sm',
                title:     title,
                exportOptions: { columns: ':visible:not(.no-export)' }
            },
            {
                extend:    'csvHtml5',
                text:      '<i class="fa-solid fa-file-csv me-1"></i>CSV',
                className: 'btn btn-warning btn-sm',
                title:     title,
                exportOptions: { columns: ':visible:not(.no-export)' }
            },
            {
                extend:    'pdfHtml5',
                text:      '<i class="fa-solid fa-file-pdf me-1"></i>PDF',
                className: 'btn btn-danger btn-sm',
                title:     title,
                orientation: 'landscape',
                pageSize:    'A4',
                exportOptions: { columns: ':visible:not(.no-export)' }
            },
            {
                extend:    'print',
                text:      '<i class="fa-solid fa-print me-1"></i>Imprimir',
                className: 'btn btn-secondary btn-sm',
                title:     title,
                autoPrint:  false,
                exportOptions: { columns: ':visible:not(.no-export)' }
            }
        ];
    }

    // ── Inicializador principal ───────────────────────────────────────────
    function initGrids() {
        $('table.datatable-medical').each(function () {
            var $table = $(this);

            // Evitar doble inicialización
            if ($.fn.DataTable.isDataTable($table)) {
                return;
            }

            // Leer overrides desde atributos data-*
            var pageLength   = parseInt($table.data('page-length'), 10) || 25;
            var orderRaw     = $table.data('order');
            var order        = orderRaw ? JSON.parse(orderRaw) : [[0, 'asc']];
            var noButtons    = $table.data('no-buttons') === true || $table.data('no-buttons') === 'true';
            var noSearch     = $table.data('no-search') === true || $table.data('no-search') === 'true';

            // Configuración DOM con o sin botones
            var domLayout = noButtons
                ? (noSearch ? 'rt<"d-flex justify-content-between align-items-center mt-2"lip>'
                             : 'f<"mb-2">rt<"d-flex justify-content-between align-items-center mt-2"lip>')
                : 'B<"d-flex justify-content-between align-items-center mb-2"lf>rt<"d-flex justify-content-between align-items-center mt-2"ip>';

            var config = {
                language:   LANG_ES,
                pageLength: pageLength,
                lengthMenu: LENGTH_MENU,
                order:      order,
                responsive: true,
                dom:        domLayout,
                buttons:    noButtons ? [] : buildButtons($table.attr('id') || 'tabla'),
                initComplete: function () {
                    // Añadir placeholder al buscador si no está deshabilitado
                    if (!noSearch) {
                        $('#' + this.api().tables().nodes().to$().attr('id') + '_filter input')
                            .addClass('form-control form-control-sm')
                            .attr('placeholder', 'Buscar...');
                    }
                }
            };

            $table.DataTable(config);

            // Envolver en un contenedor responsive si no lo está ya
            if (!$table.parent().hasClass('table-responsive')) {
                $table.wrap('<div class="table-responsive datatable-wrapper"></div>');
            }
        });
    }

    // ── Ejecutar al cargar el DOM ─────────────────────────────────────────
    $(document).ready(function () {
        initGrids();
    });

    // ── Exponer helper global para inicialización manual ─────────────────
    window.MedicalGrid = {
        /**
         * Inicializar una tabla específica por selector.
         * @param {string} selector  Selector jQuery (ej: '#miTabla')
         * @param {object} options   Opciones adicionales de DataTables
         */
        init: function (selector, options) {
            var $table = $(selector);
            if (!$table.length) return;

            if ($.fn.DataTable.isDataTable($table)) {
                $table.DataTable().destroy();
            }

            var defaults = {
                language:   LANG_ES,
                pageLength: 25,
                lengthMenu: LENGTH_MENU,
                responsive: true,
                dom:        'B<"d-flex justify-content-between align-items-center mb-2"lf>rt<"d-flex justify-content-between align-items-center mt-2"ip>',
                buttons:    buildButtons(selector)
            };

            return $table.DataTable($.extend(true, {}, defaults, options || {}));
        },

        /**
         * Destruir una instancia DataTable.
         * @param {string} selector
         */
        destroy: function (selector) {
            var $table = $(selector);
            if ($.fn.DataTable.isDataTable($table)) {
                $table.DataTable().destroy();
            }
        },

        /**
         * Recargar (redraw) una instancia DataTable.
         * @param {string} selector
         */
        reload: function (selector) {
            var $table = $(selector);
            if ($.fn.DataTable.isDataTable($table)) {
                $table.DataTable().ajax.reload(null, false);
            }
        }
    };

})(jQuery);
