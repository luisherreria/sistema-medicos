<?php
/**
 * controllers/AuditPadController.php
 * Pad Audit. Facturac. (extractos SCX): autscerr, liqaaud, LIQSCERR,
 * reaudifact, avideb. Dialecto PHP 5.6.
 *
 * Verificar/Aceptar/Enviar no tocan consulta, detalle, DETCLOSE, liquida, mail.
 */

require_once __DIR__ . '/../models/Permission.php';

class AuditPadController
{
    public function index()
    {
        $cfg = $this->configActual();
        $this->requireClave($cfg['clave']);

        $pageTitle  = 'COMEDICA — ' . $cfg['caption'];
        $breadcrumb = array(
            array('label' => 'Audit. Facturac.'),
            array('label' => $cfg['menu']),
        );
        $csrfToken = isset($_SESSION['csrf_token']) ? (string) $_SESSION['csrf_token'] : '';
        $slug      = $cfg['slug'];

        require_once __DIR__ . '/../views/audita/pad.php';
    }

    /**
     * Validaciones attested del Click; el trabajo (consulta/liquida/mail) queda stub.
     */
    public function accion()
    {
        $cfg = $this->configActual();
        $this->requireClave($cfg['clave']);
        $this->requireCsrf();
        header('Content-Type: application/json; charset=utf-8');

        $boton = isset($_POST['boton']) ? trim((string) $_POST['boton']) : '';
        $err = $this->validarClick($cfg['form'], $boton, $_POST);
        if ($err !== '') {
            echo json_encode(array('ok' => false, 'error' => $err), JSON_UNESCAPED_UNICODE);
            exit;
        }
        echo json_encode(array(
            'ok'        => false,
            'pendiente' => true,
            'error'     => $cfg['stub'],
        ), JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * @param  string $form
     * @param  string $boton
     * @param  array  $p
     * @return string
     */
    private function validarClick($form, $boton, array $p)
    {
        $g = function ($k) use ($p) {
            return isset($p[$k]) ? trim((string) $p[$k]) : '';
        };
        if ($form === 'autscerr') {
            $per = $g('periodo');
            $aa = (int) substr($per . '00', 0, 2);
            $mm = (int) substr($per, -2);
            if ($aa < 1 || $mm < 1) {
                return 'Debe seleccionar un Periodo Valido';
            }
            if ($g('tobra') === '') {
                return 'Debe seleccionar una Obra Social';
            }
            return '';
        }
        if ($form === 'liqaaud') {
            if ($g('tperi') === '') {
                return 'Debe Seleccionar un Periodo';
            }
            return '';
        }
        if ($form === 'LIQSCERR') {
            if ($g('tperi') === '') {
                return 'Debe Seleccionar un Periodo';
            }
            return '';
        }
        if ($form === 'reaudifact') {
            if ($boton === 'reporte') {
                return '';
            }
            if ($g('tpresta') === '') {
                return 'Debe seleccionar un Prestador';
            }
            $per = $g('periodo');
            $aa = (int) substr($per . '00', 0, 2);
            $mm = (int) substr($per, -2);
            if ($aa < 1 || $mm < 1) {
                return 'Debe seleccionar un Periodo Valido';
            }
            return '';
        }
        if ($form === 'avideb') {
            $check1 = !empty($p['check1']);
            if ($boton === 'enviar' && $check1) {
                return 'Verifique seleccion de Tipo de Envio!';
            }
            if ($boton === 'enviados') {
                if ($g('periodo') === '') {
                    return 'El periodo NO puede ser vacio!';
                }
                if ($g('presta') === '') {
                    return 'El prestador NO puede ser vacio!';
                }
            }
            return '';
        }
        return 'Formulario no citado.';
    }

    /**
     * @return array
     */
    private function configActual()
    {
        $route = isset($_GET['route']) ? trim((string) $_GET['route']) : '';
        $map = $this->mapa();
        if (isset($map[$route])) {
            return $map[$route];
        }
        foreach ($map as $cfg) {
            if ($cfg['clave'] === $route) {
                return $cfg;
            }
        }
        return $map['cierre-automatico'];
    }

    /**
     * @return array
     */
    private function mapa()
    {
        return array(
            'cierre-automatico' => array(
                'slug'    => 'cierre-automatico',
                'clave'   => 'MNU_AF_CIERRE_AUTO',
                'form'    => 'autscerr',
                'caption' => 'Cierre Automatico de Facturas',
                'menu'    => 'Cierre Automático',
                'icon'    => 'fa-solid fa-lock',
                'stub'    => 'Verificar no migrado (CONSULTA/DETALLE INTO audifact y DO FORM selefacts).',
                'fields'  => array(
                    array('name' => 'periodo', 'label' => 'Periodo :', 'value' => 'AA/MM', 'max' => 5),
                    array('name' => 'tobra', 'label' => 'Obra Social :', 'max' => 8, 'upper' => true),
                    array('name' => 'tnomobra', 'label' => '', 'readonly' => true),
                ),
                'hidden'  => array('tclave' => '', 'isconfact' => '', 'tconfact' => ''),
                'checks'  => array(),
                'buttons' => array(
                    array('boton' => 'verificar', 'caption' => 'Verificar', 'primary' => true),
                    array('boton' => 'termina', 'caption' => 'Termina', 'salir' => true),
                ),
            ),
            'liq-auditar' => array(
                'slug'    => 'liq-auditar',
                'clave'   => 'MNU_AF_LIQ_AUDITAR',
                'form'    => 'liqaaud',
                'caption' => 'Liquidaciones a Auditar',
                'menu'    => 'Liquidaciones a Auditar',
                'icon'    => 'fa-solid fa-file-circle-question',
                'stub'    => 'Acepta no migrado (liquida → liqaux y DO FORM vliqaaud).',
                'fields'  => array(
                    array('name' => 'tperi', 'label' => 'Periodo :', 'value' => 'AA/MM', 'max' => 5),
                    array('name' => 'tpre', 'label' => 'Prestador :', 'max' => 8, 'upper' => true),
                    array('name' => 'tnompre', 'label' => '', 'readonly' => true),
                    array('name' => 'tpre2', 'label' => '', 'max' => 8, 'upper' => true),
                    array('name' => 'tnompre2', 'label' => '', 'readonly' => true),
                    array('name' => 'tpre3', 'label' => '', 'max' => 8, 'upper' => true),
                    array('name' => 'tnompre3', 'label' => '', 'readonly' => true),
                    array('name' => 'tobra', 'label' => 'Obra Social :', 'max' => 8, 'upper' => true),
                    array('name' => 'tnomobra', 'label' => '', 'readonly' => true),
                    array('name' => 'exobra1', 'label' => 'Exclusiones de O.S.', 'value' => 'OSADEF', 'max' => 8, 'upper' => true),
                    array('name' => 'exobra2', 'label' => '', 'max' => 8, 'upper' => true),
                    array('name' => 'exobra3', 'label' => '', 'max' => 8, 'upper' => true),
                    array('name' => 'exobra4', 'label' => '', 'max' => 8, 'upper' => true),
                    array('name' => 'exobra5', 'label' => '', 'max' => 8, 'upper' => true),
                ),
                'hidden'  => array('opc2' => '1'),
                'checks'  => array(
                    array('name' => 'option1', 'caption' => 'Todos', 'checked' => true),
                    array('name' => 'option2', 'caption' => 'Medicos y Especialistas', 'checked' => false),
                    array('name' => 'option3', 'caption' => 'Centro de Diagnosticos', 'checked' => false),
                    array('name' => 'option4', 'caption' => 'Policonsultorios', 'checked' => false),
                    array('name' => 'option5', 'caption' => 'Sanatorial', 'checked' => false),
                ),
                'buttons' => array(
                    array('boton' => 'acepta', 'caption' => 'Acepta', 'primary' => true),
                    array('boton' => 'termina', 'caption' => 'Termina', 'salir' => true),
                ),
            ),
            'liq-sin-cerrar' => array(
                'slug'    => 'liq-sin-cerrar',
                'clave'   => 'MNU_AF_LIQ_SIN_CERRAR',
                'form'    => 'LIQSCERR',
                'caption' => 'Liquidaciones',
                'menu'    => 'Liquidaciones sin Cerrar',
                'icon'    => 'fa-solid fa-file-circle-exclamation',
                'stub'    => 'Aceptar no migrado (detcons1 / liquida; DETCLOSE omitido).',
                'fields'  => array(
                    array('name' => 'tperi', 'label' => 'Periodo Desde :', 'max' => 5),
                    array('name' => 'tperih', 'label' => 'Hasta :', 'max' => 5),
                    array('name' => 'tpre', 'label' => 'Prestador :', 'max' => 8, 'upper' => true),
                    array('name' => 'tnompre', 'label' => '', 'readonly' => true),
                    array('name' => 'tobra', 'label' => 'Obra Social :', 'max' => 8, 'upper' => true),
                    array('name' => 'tnomobra', 'label' => '', 'readonly' => true),
                    array('name' => 'tipo', 'label' => 'Tipo Prestador :', 'max' => 8, 'upper' => true),
                    array('name' => 'exobra1', 'label' => 'Exclusiones de O.Sociales', 'value' => 'OSADEF', 'max' => 8, 'upper' => true),
                    array('name' => 'exobra2', 'label' => '', 'max' => 8, 'upper' => true),
                    array('name' => 'exobra3', 'label' => '', 'max' => 8, 'upper' => true),
                    array('name' => 'exobra4', 'label' => '', 'max' => 8, 'upper' => true),
                    array('name' => 'exobra5', 'label' => '', 'max' => 8, 'upper' => true),
                ),
                'hidden'  => array('opc2' => '1', 'talta' => ''),
                'checks'  => array(),
                'buttons' => array(
                    array('boton' => 'aceptar', 'caption' => 'Aceptar', 'primary' => true),
                    array('boton' => 'terminar', 'caption' => 'Terminar', 'salir' => true),
                ),
            ),
            'reimp-liquidaciones' => array(
                'slug'    => 'reimp-liquidaciones',
                'clave'   => 'MNU_AF_REIMP_LIQ',
                'form'    => 'reaudifact',
                'caption' => 'ReImpresion Liquidacion',
                'menu'    => 'Reimpresión de Liquidaciones',
                'icon'    => 'fa-solid fa-print',
                'stub'    => 'Aceptar/Reporte no migrado (consulta / liquida / DO FORM reselefact).',
                'fields'  => array(
                    array('name' => 'periodo', 'label' => 'Periodo :', 'value' => 'AA/MM', 'max' => 5),
                    array('name' => 'tpresta', 'label' => 'Prestador :', 'max' => 8, 'upper' => true),
                    array('name' => 'nomprest', 'label' => '', 'readonly' => true),
                    array('name' => 'tconfact', 'label' => '', 'readonly' => true),
                    array('name' => 'tvalereci', 'label' => '', 'readonly' => true),
                ),
                'hidden'  => array('isconfact' => '', 'valereci' => ''),
                'checks'  => array(),
                'buttons' => array(
                    array('boton' => 'aceptar', 'caption' => 'Aceptar', 'primary' => true),
                    array('boton' => 'reporte', 'caption' => 'Reporte', 'primary' => false),
                    array('boton' => 'termina', 'caption' => 'Cancelar', 'salir' => true),
                ),
            ),
            'envio-debitos' => array(
                'slug'    => 'envio-debitos',
                'clave'   => 'MNU_AF_ENV_DEBITO',
                'form'    => 'avideb',
                'caption' => 'Avisos por debitos',
                'menu'    => 'Envío avisos de débito',
                'icon'    => 'fa-solid fa-envelope',
                'stub'    => 'Enviar no migrado (pdffiles / ebamp.mail_deb / CDO / adjunto).',
                'fields'  => array(
                    array('name' => 'periodo', 'label' => 'Periodo :', 'value' => 'AA/MM', 'max' => 5, 'disabled' => true, 'id' => 'pad-periodo'),
                    array('name' => 'presta', 'label' => 'Prestador :', 'max' => 11, 'upper' => true),
                    array('name' => 'nomprest', 'label' => '', 'readonly' => true),
                ),
                'hidden'  => array(),
                'checks'  => array(
                    array('name' => 'check1', 'caption' => 'Avisos de Debitos ENVIADOS', 'checked' => false, 'id' => 'pad-check1'),
                ),
                'buttons' => array(
                    array('boton' => 'enviar', 'caption' => 'Enviar', 'primary' => true, 'id' => 'pad-btn-enviar'),
                    array('boton' => 'enviados', 'caption' => 'Enviar', 'primary' => true, 'id' => 'pad-btn-enviados', 'hidden' => true),
                    array('boton' => 'salir', 'caption' => 'Salir', 'salir' => true),
                ),
            ),
        );
    }

    /**
     * @param string $clave
     */
    private function requireClave($clave)
    {
        if (empty($_SESSION['user'])) {
            if ($this->isAjax()) {
                $this->jsonError('Sesión expirada. Recargue la página.', 401);
            }
            header('Location: ' . $this->baseUrl() . '?route=login');
            exit;
        }
        $permisos = isset($_SESSION['permisos']) ? $_SESSION['permisos'] : array();
        if (!Permission::tiene($permisos, $clave)) {
            if ($this->isAjax()) {
                $this->jsonError('Sin permiso.', 403);
            }
            $_SESSION['flash_warning'] = 'No tiene permiso para acceder a este módulo.';
            header('Location: ' . $this->baseUrl() . '?route=dashboard');
            exit;
        }
    }

    private function requireCsrf()
    {
        $token = '';
        if (isset($_POST['csrf_token'])) {
            $token = (string) $_POST['csrf_token'];
        } elseif (isset($_SERVER['HTTP_X_CSRF_TOKEN'])) {
            $token = (string) $_SERVER['HTTP_X_CSRF_TOKEN'];
        }
        $esper = isset($_SESSION['csrf_token']) ? (string) $_SESSION['csrf_token'] : '';
        if ($esper === '' || $token === '' || !hash_equals($esper, $token)) {
            $this->jsonError('Token de seguridad inválido. Recargue la página.', 403);
        }
    }

    private function isAjax()
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower((string) $_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * @param string $msg
     * @param int    $code
     */
    private function jsonError($msg, $code = 200)
    {
        if ((int) $code !== 200) {
            http_response_code((int) $code);
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(array('ok' => false, 'error' => $msg), JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function baseUrl()
    {
        if (function_exists('getBaseUrl')) {
            return getBaseUrl();
        }
        $https  = isset($_SERVER['HTTPS']) ? $_SERVER['HTTPS'] : '';
        $scheme = (!empty($https) && $https !== 'off') ? 'https' : 'http';
        $host   = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
        $scriptName = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '';
        $script = rtrim((string) dirname($scriptName), '/\\');
        return $scheme . '://' . $host . $script . '/index.php';
    }
}
