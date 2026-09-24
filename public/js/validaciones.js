/**
 * Validaciones globales COMEDICA.
 * Requiere jQuery. Se carga desde views/layouts/footer.php.
 */
(function ($) {
    if (typeof $ === 'undefined') {
        return;
    }

    $(document).on('input', '.validar-periodo', function () {
        var input = $(this);
        var val = input.val().replace(/[^0-9]/g, '');

        if (val.length > 2) {
            val = val.substring(0, 2) + '/' + val.substring(2, 4);
        }
        input.val(val);
    });

    $(document).on('change', '.validar-periodo', function () {
        var valor = $(this).val().trim();
        if (valor === '') {
            return;
        }

        if (valor.length !== 5 || valor.indexOf('/') === -1) {
            alert('El formato del período debe ser AA/MM (Ej: 26/08).');
            $(this).val('').focus();
            return;
        }

        var partes = valor.split('/');
        var aa = parseInt(partes[0], 10);
        var mm = parseInt(partes[1], 10);

        var fechaActual = new Date();
        var anioActual = fechaActual.getFullYear() % 100;
        var anioAnterior = (fechaActual.getFullYear() - 1) % 100;

        if (aa > anioActual || aa < anioAnterior) {
            var aaAnt = anioAnterior < 10 ? ('0' + anioAnterior) : anioAnterior;
            alert('Error: El año del período (' + partes[0] + ') no es válido. Solo se permite el año actual (' + anioActual + ') o el anterior (' + aaAnt + ').');
            $(this).val('').focus();
            return;
        }

        if (mm < 1 || mm > 12) {
            alert('Error: El mes ingresado (' + partes[1] + ') no es válido. Debe estar comprendido entre 01 y 12.');
            $(this).val('').focus();
            return;
        }
    });
})(window.jQuery);
