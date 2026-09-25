/**
 * Validaciones globales COMEDICA.
 * Requiere jQuery. Se carga desde views/layouts/footer.php.
 */

// Premisa del sistema: Override global de alert() nativo a SweetAlert2
if (typeof window !== 'undefined') {
    // Guardamos una copia del alert original por seguridad
    window.nativeAlert = window.alert;

    // Sobrescribimos la función alert global
    window.alert = function (mensaje) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'warning',
                title: 'Atención',
                text: mensaje,
                confirmButtonColor: '#0d6efd',
                confirmButtonText: 'Aceptar'
            });
        } else {
            // Fallback seguro si la librería no llegó a cargar
            window.nativeAlert(mensaje);
        }
    };
}

// Premisa del sistema: Wrapper global para confirmaciones con SweetAlert2
window.confirmarAccion = function (titulo, mensaje, textoBoton, callbackAceptar) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: titulo || '¿Estás seguro?',
            text: mensaje,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: textoBoton || 'Sí, continuar',
            cancelButtonText: 'Cancelar'
        }).then(function (result) {
            if (result.isConfirmed && typeof callbackAceptar === 'function') {
                callbackAceptar();
            }
        });
    } else if (window.confirm(mensaje)) {
        if (typeof callbackAceptar === 'function') {
            callbackAceptar();
        }
    }
};
(function ($) {
    if (typeof $ === 'undefined') {
        return;
    }

    function rfpEstiloPeriodo($input, estado) {
        if (estado === 'error') {
            $input.css({
                'border-color': '#dc3545',
                'box-shadow': '0 0 0 0.25rem rgba(220, 53, 69, 0.25)',
                'color': '#dc3545'
            });
            return;
        }
        if (estado === 'ok') {
            $input.css({
                'border-color': '#198754',
                'box-shadow': 'none',
                'color': 'inherit'
            });
            return;
        }
        $input.css({
            'border-color': '',
            'box-shadow': '',
            'color': 'inherit'
        });
    }

    // Auto-formateo y validación visual en tiempo real para AA/MM
    $(document).on('input', '.validar-periodo', function () {
        var $input = $(this);
        var valor = $input.val().replace(/[^0-9]/g, '');

        if (valor.length > 2) {
            valor = valor.substring(0, 2) + '/' + valor.substring(2, 4);
        }
        $input.val(valor);

        if (valor.length === 5) {
            var partes = valor.split('/');
            var yy = parseInt(partes[0], 10);
            var mm = parseInt(partes[1], 10);
            var esValido = true;

            if (mm < 1 || mm > 12) {
                esValido = false;
            }

            var fechaActual = new Date();
            var anioActual = fechaActual.getFullYear() % 100;
            var anioAnterior = (fechaActual.getFullYear() - 1) % 100;
            if (yy > anioActual || yy < anioAnterior) {
                esValido = false;
            }

            rfpEstiloPeriodo($input, esValido ? 'ok' : 'error');
        } else {
            rfpEstiloPeriodo($input, 'neutral');
        }
    });

    $(document).on('blur', '.validar-periodo', function () {
        var $input = $(this);
        var valor = $input.val();

        if (valor.length > 0 && valor.length < 5) {
            $input.css('border-color', '#dc3545');
        }
    });
})(window.jQuery);
