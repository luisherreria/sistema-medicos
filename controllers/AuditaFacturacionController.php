<?php
/**
 * controllers/AuditaFacturacionController.php
 * Form VFP AUDITA — menú Auditar Facturación.
 * Dialecto PHP 5.6.
 *
 * Init: obrasoc, suc, factura, prestador, periodo, TITULO, fueamed,
 * tcodaud, nombre, tapagada, tyacerrada.
 * Permiso MNU_AF_AUDITAR. Route audit-facturacion.
 */

require_once __DIR__ . '/../models/Permission.php';
require_once __DIR__ . '/../models/AuditaFacturacion.php';

class AuditaFacturacionController
{
    const CLAVE_PERMISO = 'MNU_AF_AUDITAR';

    public function index()
    {
        $this->requireModulo();

        $init = $this->initParams();
        $titulo = $init['titulo'];
        if ($titulo === '') {
            $titulo = 'Auditoria de facturacion';
        }

        $pageTitle  = 'COMEDICA — ' . $titulo;
        $breadcrumb = array(
            array('label' => 'Audit. Facturac.'),
            array('label' => 'Auditar Facturación'),
        );
        $csrfToken = isset($_SESSION['csrf_token']) ? (string) $_SESSION['csrf_token'] : '';
        $columnas  = $this->columnasGrid();
        $botones   = $this->botonesVisibles();

        require_once __DIR__ . '/../views/audita/index.php';
    }

    public function listado()
    {
        $this->requireModulo();
        header('Content-Type: application/json; charset=utf-8');
        $model = new AuditaFacturacion();
        try {
            $out = $model->listar($this->initParams());
            echo json_encode(array(
                'ok'        => true,
                'filas'     => $out['filas'],
                'totales'   => $out['totales'],
                'tabla_ok'  => $out['tabla_ok'],
            ), JSON_UNESCAPED_UNICODE);
        } catch (PDOException $e) {
            $this->jsonError('Error al leer audifac2: ' . $e->getMessage());
        }
        exit;
    }

    public function recalc()
    {
        $this->requireModulo();
        header('Content-Type: application/json; charset=utf-8');
        $model = new AuditaFacturacion();
        try {
            $tot = $model->recalc($this->initParams());
            echo json_encode(array('ok' => true, 'totales' => $tot), JSON_UNESCAPED_UNICODE);
        } catch (PDOException $e) {
            $this->jsonError('Error al resumar: ' . $e->getMessage());
        }
        exit;
    }

    public function marcarTodo()
    {
        $this->requireModulo();
        $this->requireCsrf();
        header('Content-Type: application/json; charset=utf-8');
        $marcar = isset($_POST['marcar']) ? ((int) $_POST['marcar'] === 1) : false;
        $model  = new AuditaFacturacion();
        try {
            $n = $model->marcarSedacion($this->initParams(), $marcar);
            echo json_encode(array('ok' => true, 'filas' => $n), JSON_UNESCAPED_UNICODE);
        } catch (PDOException $e) {
            $this->jsonError('Error al marcar: ' . $e->getMessage());
        }
        exit;
    }

    public function marcarTodoA()
    {
        $this->requireModulo();
        $this->requireCsrf();
        header('Content-Type: application/json; charset=utf-8');
        $marcar = isset($_POST['marcar']) ? ((int) $_POST['marcar'] === 1) : false;
        $model  = new AuditaFacturacion();
        try {
            $n = $model->marcarSedacionClasifA($this->initParams(), $marcar);
            echo json_encode(array('ok' => true, 'filas' => $n), JSON_UNESCAPED_UNICODE);
        } catch (PDOException $e) {
            $this->jsonError('Error al marcar: ' . $e->getMessage());
        }
        exit;
    }

    public function toggleCoselecc()
    {
        $this->requireModulo();
        $this->requireCsrf();
        header('Content-Type: application/json; charset=utf-8');
        $nro = isset($_POST['nroorden']) ? (int) $_POST['nroorden'] : 0;
        if ($nro <= 0) {
            $this->jsonError('nroorden inválido.');
        }
        $model = new AuditaFacturacion();
        try {
            $ok = $model->toggleCoselecc($nro);
            echo json_encode(array('ok' => $ok), JSON_UNESCAPED_UNICODE);
        } catch (PDOException $e) {
            $this->jsonError('Error al seleccionar: ' . $e->getMessage());
        }
        exit;
    }

    /**
     * Botones cuyo Click hace DO FORM (extract). No se abren esos SCX aquí.
     */
    public function doForm()
    {
        $this->requireModulo();
        header('Content-Type: application/json; charset=utf-8');
        $form = isset($_POST['form']) ? trim((string) $_POST['form']) : '';
        $mapa = $this->mapaDoForm();
        if ($form === '' || !isset($mapa[$form])) {
            $this->jsonError('Formulario no citado en AUDITA.SCX.');
        }
        echo json_encode(array(
            'ok'        => false,
            'pendiente' => true,
            'form'      => $mapa[$form],
            'error'     => 'Form VFP `' . $mapa[$form] . '` no migrado (DO FORM en AUDITA).',
        ), JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Command11 / Command37: escriben DETCLOSE / CONCLOSE. No se consultan en este corte.
     */
    public function cierre()
    {
        $this->requireModulo();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(array(
            'ok'        => false,
            'pendiente' => true,
            'error'     => 'Confirmar Cierre / Salir Conservando Cambios no migrado (DETCLOSE/CONCLOSE omitidos).',
        ), JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * @return array
     */
    private function initParams()
    {
        $g = $_GET;
        if (isset($_POST['obrasoc']) || isset($_POST['factura'])) {
            $g = array_merge($g, $_POST);
        }
        $titulo = isset($g['TITULO']) ? trim((string) $g['TITULO']) : '';
        if ($titulo === '' && isset($g['titulo'])) {
            $titulo = trim((string) $g['titulo']);
        }
        return array(
            'obrasoc'    => isset($g['obrasoc']) ? trim((string) $g['obrasoc']) : '',
            'suc'        => isset($g['suc']) ? trim((string) $g['suc']) : '',
            'factura'    => isset($g['factura']) ? trim((string) $g['factura']) : '',
            'prestador'  => isset($g['prestador']) ? trim((string) $g['prestador']) : '',
            'periodo'    => isset($g['periodo']) ? trim((string) $g['periodo']) : '',
            'titulo'     => $titulo,
            'fueamed'    => isset($g['fueamed']) ? trim((string) $g['fueamed']) : '',
            'tcodaud'    => isset($g['tcodaud']) ? trim((string) $g['tcodaud']) : '',
            'nombre'     => isset($g['nombre']) ? trim((string) $g['nombre']) : '',
            'tapagada'   => isset($g['tapagada']) ? trim((string) $g['tapagada']) : '',
            'tyacerrada' => isset($g['tyacerrada']) ? trim((string) $g['tyacerrada']) : '',
            'orden'      => isset($g['orden']) ? trim((string) $g['orden']) : '',
            'dir'        => isset($g['dir']) ? trim((string) $g['dir']) : 'ASC',
        );
    }

    /**
     * Grid1 Column1–34 (Init pisa algunos Caption).
     *
     * @return array
     */
    private function columnasGrid()
    {
        return array(
            array('id' => 'Column15', 'caption' => 'Sel', 'field' => 'cointercon', 'order' => 1, 'hide' => false, 'check' => true, 'check2' => false),
            array('id' => 'Column1',  'caption' => 'F.Consulta', 'field' => 'cofeccons', 'order' => 2, 'hide' => false, 'sort' => 'cofeccons', 'check2' => false),
            array('id' => 'Column2',  'caption' => 'Afiliado', 'field' => 'coafiliado', 'order' => 3, 'hide' => false, 'sort' => 'coafiliado', 'check2' => false),
            array('id' => 'Column3',  'caption' => 'Paciente', 'field' => 'conompac', 'order' => 4, 'hide' => false, 'sort' => 'conompac', 'check2' => false),
            array('id' => 'Column14', 'caption' => 'Plan', 'field' => 'coplan', 'order' => 5, 'hide' => true, 'check2' => true),
            array('id' => 'Column4',  'caption' => 'Prest.', 'field' => 'cocodprest', 'order' => 6, 'hide' => false, 'sort' => 'cocodprest', 'check2' => false),
            array('id' => 'Column5',  'caption' => 'Nombre Prestacion', 'field' => 'conombpres', 'order' => 7, 'hide' => false, 'check2' => false),
            array('id' => 'Column6',  'caption' => 'Cant', 'field' => 'cocantpres', 'order' => 8, 'hide' => false, 'check2' => false),
            array('id' => 'Column7',  'caption' => 'Facturado', 'field' => 'cofacturad', 'order' => 9, 'hide' => false, 'check2' => false),
            array('id' => 'Column8',  'caption' => 'Importe', 'field' => 'coimporte', 'order' => 10, 'hide' => false, 'check2' => false),
            array('id' => 'Column24', 'caption' => 'CosegPre', 'field' => 'cosegpres', 'order' => 11, 'hide' => false, 'check2' => false),
            array('id' => 'Column9',  'caption' => 'Coseg', 'field' => 'cocoseguro', 'order' => 12, 'hide' => false, 'check2' => false),
            array('id' => 'Column25', 'caption' => 'DifCPre-Cos', 'field' => 'difcpre', 'order' => 13, 'hide' => false, 'expr' => true, 'check2' => false),
            array('id' => 'Column26', 'caption' => 'ExclCoseg', 'field' => 'coexccos', 'order' => 14, 'hide' => false, 'check' => true, 'check2' => false),
            array('id' => 'Column10', 'caption' => 'Debito', 'field' => 'codebito', 'order' => 15, 'hide' => false, 'sort' => 'codebito', 'check2' => false),
            array('id' => 'Column11', 'caption' => 'Leyenda', 'field' => 'coleyedeb', 'order' => 16, 'hide' => false, 'check2' => false),
            array('id' => 'Column17', 'caption' => 'Cont.', 'field' => 'clasif', 'order' => 17, 'hide' => false, 'sort' => 'clasif', 'check2' => true),
            array('id' => 'Column20', 'caption' => 'Print', 'field' => 'sedacion', 'order' => 18, 'hide' => false, 'check' => true, 'check2' => false),
            array('id' => 'Column16', 'caption' => 'Presupuesto', 'field' => 'nropresup', 'order' => 19, 'hide' => false, 'check2' => true),
            array('id' => 'Column13', 'caption' => 'Autoriz', 'field' => 'coautoriz', 'order' => 20, 'hide' => false, 'check2' => true),
            array('id' => 'Column19', 'caption' => 'Diagnostico', 'field' => 'conommot', 'order' => 21, 'hide' => false, 'check2' => true),
            array('id' => 'Column21', 'caption' => 'LeyendaDebito', 'field' => 'cocadena', 'order' => 22, 'hide' => false, 'check2' => false),
            array('id' => 'Column18', 'caption' => 'Intercons', 'field' => 'nrointer', 'order' => 23, 'hide' => false, 'check2' => true),
            array('id' => 'Column23', 'caption' => 'RefacNro', 'field' => 'refacnro', 'order' => 24, 'hide' => false, 'check2' => true),
            array('id' => 'Column22', 'caption' => 'ReFacSuc', 'field' => 'refacsuc', 'order' => 25, 'hide' => false, 'check2' => true),
            array('id' => 'Column12', 'caption' => 'Nº Cons.', 'field' => 'conumero', 'order' => 26, 'hide' => true, 'check2' => false),
            array('id' => 'Column27', 'caption' => 'DebAVer', 'field' => 'codebaver', 'order' => 27, 'hide' => false, 'check2' => false),
            array('id' => 'Column28', 'caption' => 'DebVistos', 'field' => 'codebvisto', 'order' => 28, 'hide' => false, 'check2' => false),
            array('id' => 'Column29', 'caption' => 'MarcaInt', 'field' => 'coadh', 'order' => 29, 'hide' => false, 'check2' => false),
            array('id' => 'Column30', 'caption' => 'Neteo Neg', 'field' => 'liqneg', 'order' => 30, 'hide' => false, 'check2' => false),
            array('id' => 'Column31', 'caption' => 'Expediente', 'field' => 'expediente', 'order' => 31, 'hide' => false, 'check2' => false),
            array('id' => 'Column32', 'caption' => 'UBNBu', 'field' => 'ubnbu', 'order' => 32, 'hide' => false, 'check2' => false),
            array('id' => 'Column33', 'caption' => 'ControlChange', 'field' => 'dniafil', 'order' => 33, 'hide' => false, 'check2' => false),
            array('id' => 'Column34', 'caption' => 'CHeck', 'field' => 'coliquidad', 'order' => 34, 'hide' => false, 'check2' => false),
        );
    }

    /**
     * Botones Visible != .F. del extract (Caption tal cual).
     *
     * @return array
     */
    private function botonesVisibles()
    {
        return array(
            array('name' => 'Command15', 'caption' => 'Enviar a Auditoria  Medica', 'accion' => 'doform', 'form' => ''),
            array('name' => 'Command16', 'caption' => 'Comentario A. Medica', 'accion' => 'doform', 'form' => 'comentam'),
            array('name' => 'Command38', 'caption' => 'Salir sin Cambios', 'accion' => 'salir'),
            array('name' => 'Command1',  'caption' => 'Agregar  Consulta', 'accion' => 'doform', 'form' => 'newcons'),
            array('name' => 'Command4',  'caption' => 'Modificar Afiliado', 'accion' => 'doform', 'form' => 'cambafil'),
            array('name' => 'Command2',  'caption' => 'Agregar Prestacion', 'accion' => 'doform', 'form' => 'cambpres'),
            array('name' => 'Command5',  'caption' => 'Cambia Prestacion', 'accion' => 'doform', 'form' => 'cambpres'),
            array('name' => 'Command8',  'caption' => 'Debitar', 'accion' => 'doform', 'form' => 'debitar'),
            array('name' => 'Command18', 'caption' => 'Historia Clinica', 'accion' => 'doform', 'form' => 'vehiscli'),
            array('name' => 'Command28', 'caption' => 'Ver Control', 'accion' => 'doform', 'form' => 'veinfo'),
            array('name' => 'Command12', 'caption' => 'Ajustes de ValorImpositivo', 'accion' => 'doform', 'form' => 'ajusfact'),
            array('name' => 'btnverautorizaciones', 'caption' => 'Selecciona Autorizacion', 'accion' => 'doform', 'form' => 'pedirauto1'),
            array('name' => 'Command45', 'caption' => 'Ajusta Valor Importe', 'accion' => 'doform', 'form' => 'ajusfact'),
            array('name' => 'btnver',    'caption' => 'Visualizar', 'accion' => 'doform', 'form' => ''),
            array('name' => 'Command3',  'caption' => 'Borrar Consulta', 'accion' => 'doform', 'form' => ''),
            array('name' => 'Command10', 'caption' => 'Recalcular Importes', 'accion' => 'doform', 'form' => ''),
            array('name' => 'Command6',  'caption' => 'Borra Prestacion', 'accion' => 'doform', 'form' => ''),
            array('name' => 'Command7',  'caption' => 'Cambiar Valores', 'accion' => 'doform', 'form' => ''),
            array('name' => 'Command13', 'caption' => 'Borrar Debito', 'accion' => 'doform', 'form' => ''),
            array('name' => 'Command19', 'caption' => 'Historico de Valores', 'accion' => 'doform', 'form' => 'verprac'),
            array('name' => 'Command9',  'caption' => 'Imprimir', 'accion' => 'doform', 'form' => ''),
            array('name' => 'Command33', 'caption' => 'Ajustes de Prestaciones sobre el afiliado', 'accion' => 'doform', 'form' => 'ajusfafi'),
            array('name' => 'Command46', 'caption' => 'Ajusta Valor Importe MULTI', 'accion' => 'doform', 'form' => 'ajusfact'),
            array('name' => 'Command40', 'caption' => 'Buscar', 'accion' => 'doform', 'form' => 'busca'),
            array('name' => 'Command27', 'caption' => 'Buscar Contrato', 'accion' => 'doform', 'form' => 'frmadjcontrato'),
            array('name' => 'Command43', 'caption' => 'Sacar Debitos Multi', 'accion' => 'doform', 'form' => ''),
            array('name' => 'Command42', 'caption' => 'Internaciones', 'accion' => 'doform', 'form' => 'cargainter'),
            array('name' => 'Command50', 'caption' => 'Otras Liq .mismo Prestador', 'accion' => 'doform', 'form' => 'otraliqm'),
            array('name' => 'Command21', 'caption' => 'Ordenar x Ingreso', 'accion' => 'orden', 'orden' => 'nroorden'),
            array('name' => 'Command25', 'caption' => 'Agregar leyenda', 'accion' => 'doform', 'form' => 'pideleyd'),
            array('name' => 'Command24', 'caption' => 'Debitar  Agrupados', 'accion' => 'doform', 'form' => 'debitar'),
            array('name' => 'Command36', 'caption' => 'Quita todos los CosegurosPre', 'accion' => 'doform', 'form' => ''),
            array('name' => 'Command20', 'caption' => 'Prestaciones  Liq Anteriores', 'accion' => 'doform', 'form' => 'vedetant'),
            array('name' => 'btnvalorespra', 'caption' => 'Valores Practicas', 'accion' => 'doform', 'form' => 'verprac'),
            array('name' => 'Command32', 'caption' => 'Re Sumando Pantalla', 'accion' => 'recalc'),
            array('name' => 'Command47', 'caption' => 'Ajusta Valor Importe Prestador', 'accion' => 'doform', 'form' => 'ajusfact'),
            array('name' => 'Command26', 'caption' => 'Composicion Modular', 'accion' => 'doform', 'form' => ''),
            array('name' => 'Command14', 'caption' => 'Revisar Nombre Prestacion', 'accion' => 'doform', 'form' => 'chgprest'),
            array('name' => 'Command11', 'caption' => 'Confirmar Cierre', 'accion' => 'cierre'),
            array('name' => 'Command31', 'caption' => 'Cambia Diagnostico', 'accion' => 'doform', 'form' => 'chgdiag2'),
            array('name' => 'Command52', 'caption' => 'Cambia Fecha', 'accion' => 'doform', 'form' => 'pidefec2'),
            array('name' => 'busca',     'caption' => 'Buscar Afiliado', 'accion' => 'doform', 'form' => 'busca'),
            array('name' => 'Command37', 'caption' => 'Salir Conservando Cambios', 'accion' => 'cierre'),
            array('name' => 'Command29', 'caption' => 'Sacar Coseg > Importe', 'accion' => 'doform', 'form' => ''),
            array('name' => 'Command54', 'caption' => 'Aj Coseguros =', 'accion' => 'doform', 'form' => ''),
            array('name' => 'Command48', 'caption' => 'Ajusta Valor Imp MULTI Prestador', 'accion' => 'doform', 'form' => 'ajusfact'),
            array('name' => 'Command56', 'caption' => 'Ver Contrato', 'accion' => 'doform', 'form' => 'frmadjcontrato'),
            array('name' => 'Command53', 'caption' => 'Borra MARCA INTERNACION', 'accion' => 'doform', 'form' => ''),
            array('name' => 'btnverautorizaciones1', 'caption' => 'Selecciona Autorizacion', 'accion' => 'doform', 'form' => 'pedirauto1'),
            array('name' => 'btnotra',   'caption' => 'Otras Liq .mismo Prestador', 'accion' => 'doform', 'form' => 'otraliqm'),
            array('name' => 'Command49', 'caption' => 'Practica', 'accion' => 'doform', 'form' => 'esprac1'),
            array('name' => 'Command39', 'caption' => 'Marcar OS Autirizacion', 'accion' => 'doform', 'form' => ''),
            array('name' => 'Command55', 'caption' => 'Adjuntos', 'accion' => 'doform', 'form' => 'frmadjuntos'),
            array('name' => 'Command57', 'caption' => 'Borra  INTERNACION', 'accion' => 'doform', 'form' => ''),
        );
    }

    /**
     * @return array
     */
    private function mapaDoForm()
    {
        return array(
            'newcons' => 'newcons',
            'cambpres' => 'cambpres',
            'cambafil' => 'cambafil',
            'debitar' => 'debitar',
            'pideleyd' => 'pideleyd',
            'ajusfact' => 'ajusfact',
            'comentam' => 'comentam',
            'verprac' => 'verprac',
            'vehiscli' => 'vehiscli',
            'vedetaut' => 'vedetAut',
            'vedetant' => 'vedetant',
            'veinfo' => 'VEINFO',
            'veauto2' => 'veauto2',
            'chgdiag2' => 'chgdiag2',
            'busca' => 'busca',
            'chgprest' => 'chgprest',
            'ajusfafi' => 'AJUSFAFI',
            'cargainter' => 'CARGAINTER',
            'pedirauto1' => 'pedirauto1',
            'esprac1' => 'esprac1',
            'pidefec2' => 'pidefec2',
            'otraliqm' => 'otraliqm',
            'seledfac' => 'seledfac',
            'audita2' => 'audita2',
            'frmadjuntos' => 'frmAdjuntos',
            'frmadjcontrato' => 'frmAdjcontrato',
        );
    }

    private function requireModulo()
    {
        if (empty($_SESSION['user'])) {
            if ($this->isAjax()) {
                $this->jsonError('Sesión expirada. Recargue la página.', 401);
            }
            header('Location: ' . $this->baseUrl() . '?route=login');
            exit;
        }
        $permisos = isset($_SESSION['permisos']) ? $_SESSION['permisos'] : array();
        if (!Permission::tiene($permisos, self::CLAVE_PERMISO)) {
            if ($this->isAjax()) {
                $this->jsonError('Sin permiso para Auditar Facturación.', 403);
            }
            $_SESSION['flash_warning'] = 'No tiene permiso para acceder a Auditar Facturación.';
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
