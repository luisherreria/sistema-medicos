<?php
/**
 * controllers/AuditaFacturacionController.php
 * Form VFP AUDITA — menú Auditar Facturación.
 * Dialecto PHP 5.6.
 *
 * Flujo VFP: AUDIFACT (periodo/prestador) → SELEFACT (grid audifact) → AUDITA.
 * Init AUDITA: obrasoc, suc, factura, prestador, periodo, TITULO, fueamed,
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
        $forzarGrid = isset($_GET['grid']) && (string) $_GET['grid'] === '1';
        $paso = isset($_GET['paso']) ? strtolower(trim((string) $_GET['paso'])) : '';
        $hayFactura = ($init['obrasoc'] !== '' && $init['factura'] !== ''
            && $init['prestador'] !== '' && $init['periodo'] !== '');
        if ($hayFactura || $forzarGrid) {
            $columnas = $this->columnasGrid();
            $botones  = $this->botonesVisibles();
            require_once __DIR__ . '/../views/audita/index.php';
            return;
        }
        if ($paso === 'selefact' && $init['prestador'] !== '') {
            $this->pantallaSelefact($init, $pageTitle, $breadcrumb, $csrfToken);
            return;
        }
        require_once __DIR__ . '/../views/audita/audifact.php';
    }

    /**
     * AUDIFACT.tpresta Valid.
     */
    public function buscarPrestador()
    {
        $this->requireModulo();
        header('Content-Type: application/json; charset=utf-8');
        $tpresta = isset($_GET['prestador']) ? trim((string) $_GET['prestador']) : '';
        if ($tpresta === '' && isset($_POST['prestador'])) {
            $tpresta = trim((string) $_POST['prestador']);
        }
        try {
            $out = (new AuditaFacturacion())->buscarPrestador($tpresta);
            echo json_encode($out, JSON_UNESCAPED_UNICODE);
        } catch (PDOException $e) {
            $this->jsonError('Error al leer ebamp: ' . $e->getMessage());
        }
        exit;
    }

    public function listado()
    {
        $this->requireModulo();
        header('Content-Type: application/json; charset=utf-8');
        $model = new AuditaFacturacion();
        try {
            $out = $model->listar($this->initParams());
            $err = '';
            if (empty($out['tabla_ok'])) {
                $err = isset($out['error']) ? (string) $out['error'] : $model->ultimoError();
            }
            echo json_encode(array(
                'ok'        => true,
                'filas'     => $out['filas'],
                'totales'   => $out['totales'],
                'tabla_ok'  => $out['tabla_ok'],
                'truncado'  => !empty($out['truncado']),
                'tabla'     => $model->tablaNombre(),
                'error'     => $err,
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

    /**
     * btnver Visualizar — cntResumen1. Sin COPY TO imprime ni FRX.
     */
    public function visualizar()
    {
        $this->requireModulo();
        header('Content-Type: application/json; charset=utf-8');
        $init = $this->initParams();
        $model = new AuditaFacturacion();
        try {
            $out = $model->visualizar($init, $init['nombre']);
            echo json_encode($out, JSON_UNESCAPED_UNICODE);
        } catch (PDOException $e) {
            $this->jsonError('Error al visualizar: ' . $e->getMessage());
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
        $abiertos = array(
            'newcons'  => 1,
            'cambpres' => 1,
            'ajusfact' => 1,
            'ajusfafi' => 1,
            'cambafil' => 1,
            'debitar'  => 1,
        );
        if (isset($abiertos[$form])) {
            echo json_encode(array(
                'ok'   => true,
                'form' => $form,
            ), JSON_UNESCAPED_UNICODE);
            exit;
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
     * NEWCONS.Command1 — APPEND en audifac2 (extract). No consulta/detalle/ebamp.
     */
    public function guardarNewcons()
    {
        $this->requireModulo();
        $this->requireCsrf();
        header('Content-Type: application/json; charset=utf-8');
        $tafil = isset($_POST['tafiliado']) ? trim((string) $_POST['tafiliado']) : '';
        $fecha = isset($_POST['tfecha']) ? trim((string) $_POST['tfecha']) : '';
        $tpres = isset($_POST['tpres']) ? trim((string) $_POST['tpres']) : '';
        $tcant = isset($_POST['tcant']) ? (float) $_POST['tcant'] : 0;
        $tfact = isset($_POST['tfact']) ? (float) $_POST['tfact'] : 0;
        $timpo = isset($_POST['timpo']) ? (float) $_POST['timpo'] : 0;
        $tcose = isset($_POST['tcose']) ? (float) $_POST['tcose'] : 0;
        $tcosep = isset($_POST['tcosep']) ? (float) $_POST['tcosep'] : 0;
        $exc   = !empty($_POST['chkexccos']);
        $tplan = isset($_POST['tplan']) ? trim((string) $_POST['tplan']) : '';
        if (strlen($tafil) < 3) {
            $this->jsonError('Afiliado: longitud mínima 3 (NEWCONS.Command1).');
        }
        if (strlen($fecha) < 3) {
            $this->jsonError('Fecha vacía (NEWCONS.Command1).');
        }
        if ($tcant > 5 && ($tpres === 'PREST' || $tpres === 'MEDICA')) {
            $this->jsonError('Supera el Valor Maximo Permitido (5 max)');
        }
        if ($tfact > 150 && ($tpres === 'PREST' || $tpres === 'MEDICA')) {
            $this->jsonError('Supera el Valor Maximo Permitido (150 max)');
        }
        if ($exc) {
            $tcosep = 0;
        }
        $codebito = isset($_POST['debito']) ? trim((string) $_POST['debito']) : '';
        $cocadena = '';
        if ($timpo < $tfact) {
            $codebito = '010';
            $cocadena = 'DIFERENCIA DE IMPORTE';
        }
        $init = $this->initParams();
        $fila = array(
            'cocodprest' => $tpres,
            'conombpres' => isset($_POST['tnpres']) ? trim((string) $_POST['tnpres']) : '',
            'cocantpres' => $tcant,
            'cofacturad' => $tfact,
            'coimporte'  => $timpo,
            'cocoseguro' => $tcose,
            'cosegpres'  => $tcosep,
            'coexccos'   => $exc ? 1 : 0,
            'coobrasoc'  => isset($_POST['tobra']) ? trim((string) $_POST['tobra']) : $init['obrasoc'],
            'coprestado' => isset($_POST['tpresta']) ? trim((string) $_POST['tpresta']) : $init['prestador'],
            'coafiliado' => $tafil,
            'conompac'   => isset($_POST['tnomafil']) ? trim((string) $_POST['tnomafil']) : '',
            'coplan'     => $tplan,
            'cosucfac'   => isset($_POST['tsuc']) ? trim((string) $_POST['tsuc']) : $init['suc'],
            'conrofac'   => isset($_POST['tfac']) ? trim((string) $_POST['tfac']) : $init['factura'],
            'coperiodo'  => isset($_POST['tperi']) ? trim((string) $_POST['tperi']) : $init['periodo'],
            'cofeccons'  => $fecha,
            'codebito'   => $codebito,
            'coautoriz'  => isset($_POST['autoriza']) ? trim((string) $_POST['autoriza']) : '',
            'coleyedeb'  => isset($_POST['tleyedeb']) ? trim((string) $_POST['tleyedeb']) : '',
            'cocadena'   => $cocadena,
            'cofuerapa'  => (strpos($tplan, 'FP') !== false) ? 1 : 0,
            'coempreact' => isset($_POST['empresa']) ? trim((string) $_POST['empresa']) : '',
            'cousuario'  => $this->operador(),
            'cofechacar' => date('Y-m-d'),
            'cohoracar'  => date('H:i:s'),
        );
        try {
            $nro = (new AuditaFacturacion())->insertarFila($fila);
            echo json_encode(array('ok' => true, 'nroorden' => $nro), JSON_UNESCAPED_UNICODE);
        } catch (PDOException $e) {
            $this->jsonError('Error al agregar consulta: ' . $e->getMessage());
        }
        exit;
    }

    /**
     * CAMBPRES.Command1 — REPLACE fila / todas iguales.
     */
    public function guardarCambpres()
    {
        $this->requireModulo();
        $this->requireCsrf();
        header('Content-Type: application/json; charset=utf-8');
        $nro     = isset($_POST['nroorden']) ? (int) $_POST['nroorden'] : 0;
        $tpres   = isset($_POST['tpres']) ? trim((string) $_POST['tpres']) : '';
        $tnpres  = isset($_POST['tnpres']) ? trim((string) $_POST['tnpres']) : '';
        $tcant   = isset($_POST['tcant']) ? (float) $_POST['tcant'] : 0;
        $tfact   = isset($_POST['tfact']) ? (float) $_POST['tfact'] : 0;
        $timpo   = isset($_POST['timpo']) ? (float) $_POST['timpo'] : 0;
        $tcose   = isset($_POST['tcose']) ? (float) $_POST['tcose'] : 0;
        $tcosep  = isset($_POST['tcospres']) ? (float) $_POST['tcospres'] : 0;
        $exc     = !empty($_POST['chkexccos']);
        $todas   = !empty($_POST['todas']);
        $ccanti  = !empty($_POST['ccanti']);
        $prestant = isset($_POST['prestant']) ? trim((string) $_POST['prestant']) : $tpres;
        $ttagrupo = isset($_POST['ttagrupo']) ? strtoupper(trim((string) $_POST['ttagrupo'])) : '';
        if ($tcant > 5 && ($tpres === 'PREST' || $tpres === 'MEDICA')) {
            $this->jsonError('Supera el Valor Maximo Permitido (5 max)');
        }
        if ($exc) {
            $tcosep = 0;
        }
        if ($tcosep > $tcose) {
            $tcose = $tcosep;
        }
        if ($timpo > $tfact) {
            $timpo = $tfact;
        }
        $op = strtoupper($this->operador());
        if ($tpres === '027' && $op !== 'DIEG' && $op !== 'LUIS' && $op !== 'NOE') {
            $timpo = 0;
        }
        $codebito = '';
        $cocadena = '';
        $dniafil  = '';
        if ($timpo < $tfact) {
            $codebito = '010';
            $cocadena = 'DIFERENCIA DE IMPORTE';
        }
        if ($ttagrupo === 'MEDICAME' || $ttagrupo === 'CONTRAST' || $op === 'DIEG' || $op === 'DIE'
            || ($tpres === '027' && ($op === 'DIEG' || $op === 'LUIS'))
        ) {
            $dniafil = 'AJVALIMP';
        }
        if ($todas) {
            $campos = array(
                'cocodprest' => $tpres,
                'conombpres' => $tnpres,
                'cofacturad' => $tfact,
                'coimporte'  => $timpo,
            );
            if ($codebito !== '') {
                $campos['codebito'] = $codebito;
                $campos['cocadena'] = $cocadena;
            }
            if ($dniafil !== '') {
                $campos['dniafil'] = $dniafil;
            }
        } else {
            $campos = array(
                'cocodprest' => $tpres,
                'conombpres' => $tnpres,
                'cocantpres' => $tcant,
                'cofacturad' => $tfact,
                'coimporte'  => $timpo,
                'cocoseguro' => $tcose,
                'cosegpres'  => $tcosep,
                'coexccos'   => $exc ? 1 : 0,
            );
            if ($codebito !== '') {
                $campos['codebito'] = $codebito;
                $campos['cocadena'] = $cocadena;
            }
            if ($dniafil !== '') {
                $campos['dniafil'] = $dniafil;
            }
        }
        try {
            $n = (new AuditaFacturacion())->actualizarPrestacion($nro, $campos, $todas, $ccanti, $prestant);
            echo json_encode(array('ok' => true, 'filas' => $n), JSON_UNESCAPED_UNICODE);
        } catch (PDOException $e) {
            $this->jsonError('Error al cambiar prestación: ' . $e->getMessage());
        }
        exit;
    }

    /**
     * AJUSFACT.Command1 — APPEND audifac2.
     */
    public function guardarAjusfact()
    {
        $this->requireModulo();
        $this->requireCsrf();
        header('Content-Type: application/json; charset=utf-8');
        $tpres = isset($_POST['tpres']) ? trim((string) $_POST['tpres']) : '';
        if (strlen($tpres) < 2) {
            $this->jsonError('Prestacion: longitud mínima 2 (AJUSFACT.Command1).');
        }
        $tfact = isset($_POST['tfact']) ? (float) $_POST['tfact'] : 0;
        $timpo = isset($_POST['timpo']) ? (float) $_POST['timpo'] : 0;
        if (($tpres === '002' || $tpres === '004') && $tfact > 0 && $timpo > 0) {
            $timpo = 0;
        }
        $op = strtoupper($this->operador());
        if ($op === 'DIEG' || $op === 'DIE' || $op === 'LUIS') {
            // usa timpo del form
        } elseif ($tpres === '041' || $tpres === '002' || $tpres === '004' || $tpres === '027') {
            $timpo = 0;
        } else {
            $timpo = $tfact;
        }
        if ($tpres === '002') {
            $timpo = isset($_POST['timpo']) ? (float) $_POST['timpo'] : 0;
        }
        $codebito = '';
        $cocadena = '';
        if ($tpres !== '004' && $tpres !== '005' && $tpres !== '006' && $tpres !== '003') {
            $codebito = '161';
            $cocadena = 'PRACTICA NO CONVENIDA CON OS';
        }
        if ($tpres === '014') {
            $codebito = '171';
            $cocadena = 'NO CORRESPONDE FACTURAR A COMEDICA SA';
        }
        $init = $this->initParams();
        $fila = array(
            'cocodprest' => $tpres,
            'conombpres' => isset($_POST['tnpres']) ? trim((string) $_POST['tnpres']) : '',
            'cocantpres' => isset($_POST['tcant']) ? (float) $_POST['tcant'] : 1,
            'cofacturad' => $tfact,
            'coimporte'  => $timpo,
            'cocoseguro' => isset($_POST['tcose']) ? (float) $_POST['tcose'] : 0,
            'cosegpres'  => isset($_POST['tcosepre']) ? (float) $_POST['tcosepre'] : 0,
            'coobrasoc'  => isset($_POST['tobra']) ? trim((string) $_POST['tobra']) : $init['obrasoc'],
            'coprestado' => isset($_POST['tpresta']) ? trim((string) $_POST['tpresta']) : $init['prestador'],
            'coafiliado' => isset($_POST['tafil']) ? trim((string) $_POST['tafil']) : '1',
            'conompac'   => isset($_POST['tnomafil']) ? trim((string) $_POST['tnomafil']) : 'Ajuste',
            'coplan'     => isset($_POST['tplan']) ? trim((string) $_POST['tplan']) : '',
            'cosucfac'   => isset($_POST['tsuc']) ? trim((string) $_POST['tsuc']) : $init['suc'],
            'conrofac'   => isset($_POST['tfac']) ? trim((string) $_POST['tfac']) : $init['factura'],
            'coperiodo'  => isset($_POST['tperi']) ? trim((string) $_POST['tperi']) : $init['periodo'],
            'cofeccons'  => isset($_POST['tfecha']) ? trim((string) $_POST['tfecha']) : '',
            'codebito'   => $codebito,
            'cocadena'   => $cocadena,
            'cousuario'  => $this->operador(),
            'cofechacar' => date('Y-m-d'),
            'cohoracar'  => date('H:i:s'),
            'afilcont'   => 0,
            'amotivo'    => '',
            'cofuerapa'  => 1,
        );
        try {
            $nro = (new AuditaFacturacion())->insertarFila($fila);
            echo json_encode(array('ok' => true, 'nroorden' => $nro), JSON_UNESCAPED_UNICODE);
        } catch (PDOException $e) {
            $this->jsonError('Error al ajustar valor: ' . $e->getMessage());
        }
        exit;
    }

    /**
     * AJUSFAFI.Command3 — APPEND audifac2.
     */
    public function guardarAjusfafi()
    {
        $this->requireModulo();
        $this->requireCsrf();
        header('Content-Type: application/json; charset=utf-8');
        $tpres = isset($_POST['tpres']) ? trim((string) $_POST['tpres']) : '';
        $tfact = isset($_POST['tfact']) ? (float) $_POST['tfact'] : 0;
        $timpo = isset($_POST['timpo']) ? (float) $_POST['timpo'] : 0;
        if ($tpres === '041' || $tpres === '002') {
            $timpo = 0;
        }
        $op = strtoupper($this->operador());
        if ($op === 'DIEG' || $op === 'DIE' || $op === 'LUIS') {
            // timpo del form (salvo 041/002 ya a 0)
        } elseif ($tpres === '041' || $tpres === '002' || $tpres === '027') {
            $timpo = 0;
        } else {
            $timpo = $tfact;
        }
        $codebito = '';
        $cocadena = '';
        if ($tpres !== '004' && $tpres !== '005' && $tpres !== '006' && $tpres !== '003') {
            $codebito = '161';
            $cocadena = 'PRACTICA NO CONVENIDA CON OS';
        }
        if ($tpres === '014') {
            $codebito = '171';
            $cocadena = 'NO CORRESPONDE FACTURAR A COMEDICA SA';
        }
        $init = $this->initParams();
        $fila = array(
            'cocodprest' => $tpres,
            'conombpres' => isset($_POST['tnpres']) ? trim((string) $_POST['tnpres']) : '',
            'cocantpres' => isset($_POST['tcant']) ? (float) $_POST['tcant'] : 1,
            'cofacturad' => $tfact,
            'coimporte'  => $timpo,
            'cocoseguro' => isset($_POST['tcose']) ? (float) $_POST['tcose'] : 0,
            'cosegpres'  => isset($_POST['tcosepre']) ? (float) $_POST['tcosepre'] : 0,
            'coobrasoc'  => isset($_POST['tobra']) ? trim((string) $_POST['tobra']) : $init['obrasoc'],
            'coprestado' => isset($_POST['tpresta']) ? trim((string) $_POST['tpresta']) : $init['prestador'],
            'coafiliado' => isset($_POST['tafil']) ? trim((string) $_POST['tafil']) : '',
            'conompac'   => isset($_POST['tnomafil']) ? trim((string) $_POST['tnomafil']) : '',
            'coplan'     => isset($_POST['tplan']) ? trim((string) $_POST['tplan']) : '',
            'cosucfac'   => isset($_POST['tsuc']) ? trim((string) $_POST['tsuc']) : $init['suc'],
            'conrofac'   => isset($_POST['tfac']) ? trim((string) $_POST['tfac']) : $init['factura'],
            'coperiodo'  => isset($_POST['tperi']) ? trim((string) $_POST['tperi']) : $init['periodo'],
            'cofeccons'  => isset($_POST['tfecha']) ? trim((string) $_POST['tfecha']) : '',
            'codebito'   => $codebito,
            'cocadena'   => $cocadena,
            'cousuario'  => $this->operador(),
            'cofechacar' => date('Y-m-d'),
            'cohoracar'  => date('H:i:s'),
            'afilcont'   => 0,
            'amotivo'    => '',
            'cofuerapa'  => 1,
        );
        try {
            $nro = (new AuditaFacturacion())->insertarFila($fila);
            echo json_encode(array('ok' => true, 'nroorden' => $nro), JSON_UNESCAPED_UNICODE);
        } catch (PDOException $e) {
            $this->jsonError('Error al ajustar prestación afiliado: ' . $e->getMessage());
        }
        exit;
    }

    /**
     * CAMBAFIL.Command1 — misma OS REPLACE audifac2 FOR CONUMERO.
     * Sin consulta/detalle. OSPETEL y cambio de OS no migrados.
     */
    public function guardarCambafil()
    {
        $this->requireModulo();
        $this->requireCsrf();
        header('Content-Type: application/json; charset=utf-8');
        $tobra = strtoupper(trim(isset($_POST['tobra']) ? (string) $_POST['tobra'] : ''));
        $nobra = strtoupper(trim(isset($_POST['nobra']) ? (string) $_POST['nobra'] : ''));
        $tafil = trim(isset($_POST['tafil']) ? (string) $_POST['tafil'] : '');
        $tnomafil = trim(isset($_POST['tnomafil']) ? (string) $_POST['tnomafil'] : '');
        $nplan = trim(isset($_POST['nplan']) ? (string) $_POST['nplan'] : '');
        $conumero = trim(isset($_POST['conumero']) ? (string) $_POST['conumero'] : '');
        if ($tobra === 'OSPETEL' || $nobra === 'OSPETEL') {
            $this->jsonError('EL CAMPO OBRA SOCIAL NO PUEDE SER OSPETEL - CAMBAFIL - AVISAR A SISTEMAS');
        }
        if ($tobra !== $nobra) {
            $this->jsonError('Cambio de obra social no migrado (consulta/detalle omitidos).');
        }
        if ($conumero === '') {
            $this->jsonError('CONUMERO vacío (CAMBAFIL.Command1 FOR CONUMERO).');
        }
        $campos = array(
            'coafiliado' => $tafil,
            'conompac'   => $tnomafil,
            'coplan'     => $nplan,
            'cofuerapa'  => 0,
        );
        try {
            $n = (new AuditaFacturacion())->cambiarAfiliado($conumero, $campos);
            echo json_encode(array('ok' => true, 'filas' => $n), JSON_UNESCAPED_UNICODE);
        } catch (PDOException $e) {
            $this->jsonError('Error al cambiar afiliado: ' . $e->getMessage());
        }
        exit;
    }

    /**
     * AUDITA.Command8 + DEBITAR.Command1: REPLACE codebito/coleyedeb/cocadena/conomdebi.
     * pideleyd no migrado: se usa tleye del form DEBITAR.
     */
    public function guardarDebitar()
    {
        $this->requireModulo();
        $this->requireCsrf();
        header('Content-Type: application/json; charset=utf-8');
        $nro = isset($_POST['nroorden']) ? (int) $_POST['nroorden'] : 0;
        $tdebi = strtoupper(trim(isset($_POST['debito']) ? (string) $_POST['debito'] : ''));
        $tleye = trim(isset($_POST['tleye']) ? (string) $_POST['tleye'] : '');
        if ($nro < 1) {
            $this->jsonError('Seleccione una fila (DEBITAR).');
        }
        if (strlen($tdebi) < 2) {
            $this->jsonError('Ingrese un valor correcto');
        }
        try {
            $n = (new AuditaFacturacion())->debitarFila($nro, $tdebi, $tleye);
            echo json_encode(array('ok' => true, 'filas' => $n), JSON_UNESCAPED_UNICODE);
        } catch (PDOException $e) {
            $this->jsonError('Error al debitar: ' . $e->getMessage());
        }
        exit;
    }

    /**
     * SELEFACT: Grid1 RecordSource audifact (COPY consulta del Command1).
     *
     * @param array  $init
     * @param string $pageTitle
     * @param array  $breadcrumb
     * @param string $csrfToken
     */
    private function pantallaSelefact(array $init, $pageTitle, array $breadcrumb, $csrfToken)
    {
        $periodo = $init['periodo'];
        $tpresta = $init['prestador'];
        $nombre  = $init['nombre'];
        $tconfact = isset($_GET['tconfact']) ? trim((string) $_GET['tconfact']) : '';
        $infoliq = '';
        $errorTabla = '';
        $tablaOk = true;
        $tablaUsada = '';
        $facturas = array();
        $model = new AuditaFacturacion();
        try {
            $pre = $model->buscarPrestador($tpresta);
            if (!empty($pre['ok'])) {
                if ($nombre === '') {
                    $nombre = $pre['nombre'];
                }
                $tpresta = $pre['tpresta'];
                $infoliq = $pre['infoliq'];
                if ($tconfact === '' && !empty($pre['confact'])) {
                    $tconfact = 'PRESTADOR CON FACTURA';
                }
            }
            $sel = $model->listarCabeceras($tpresta, $periodo);
            $facturas = $sel['filas'];
            $tablaOk = !empty($sel['tabla_ok']);
            $tablaUsada = isset($sel['tabla']) ? (string) $sel['tabla'] : '';
            $errorTabla = isset($sel['error']) ? (string) $sel['error'] : '';
        } catch (PDOException $e) {
            $tablaOk = false;
            $errorTabla = $e->getMessage();
        }
        if ($errorTabla === '') {
            $errorTabla = $model->ultimoError();
        }
        require_once __DIR__ . '/../views/audita/selefact.php';
    }

    /**
     * @return string
     */
    private function operador()
    {
        $u = isset($_SESSION['user']) ? $_SESSION['user'] : array();
        if (isset($u['username']) && $u['username'] !== '') {
            return strtoupper((string) $u['username']);
        }
        if (isset($u['nombre']) && $u['nombre'] !== '') {
            return strtoupper((string) $u['nombre']);
        }
        return '';
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
            array('name' => 'Command38', 'caption' => 'Salir sin Cambios', 'accion' => 'salir', 'menu' => 'nueva', 'col' => 0, 'fila' => 0),
            array('name' => 'Command1',  'caption' => 'Agregar  Consulta', 'accion' => 'doform', 'form' => 'newcons', 'menu' => 'nueva', 'col' => 1, 'fila' => 0),
            array('name' => 'Command4',  'caption' => 'Modificar Afiliado', 'accion' => 'doform', 'form' => 'cambafil'),
            array('name' => 'Command2',  'caption' => 'Agregar Prestacion', 'accion' => 'doform', 'form' => 'cambpres', 'menu' => 'nueva', 'col' => 3, 'fila' => 0),
            array('name' => 'Command5',  'caption' => 'Cambia Prestacion', 'accion' => 'doform', 'form' => 'cambpres', 'menu' => 'popup'),
            array('name' => 'Command8',  'caption' => 'Debitar', 'accion' => 'doform', 'form' => 'debitar', 'menu' => 'popup'),
            array('name' => 'Command18', 'caption' => 'Historia Clinica', 'accion' => 'doform', 'form' => 'vehiscli'),
            array('name' => 'Command28', 'caption' => 'Ver Control', 'accion' => 'doform', 'form' => 'veinfo'),
            array('name' => 'Command12', 'caption' => 'Ajustes de ValorImpositivo', 'accion' => 'doform', 'form' => 'ajusfact'),
            array('name' => 'btnverautorizaciones', 'caption' => 'Selecciona Autorizacion', 'accion' => 'doform', 'form' => 'pedirauto1'),
            array('name' => 'Command45', 'caption' => 'Ajusta Valor Importe', 'accion' => 'doform', 'form' => 'ajusfact'),
            array('name' => 'btnver',    'caption' => 'Visualizar', 'accion' => 'visualizar'),
            array('name' => 'Command3',  'caption' => 'Borrar Consulta', 'accion' => 'doform', 'form' => '', 'menu' => 'popup'),
            array('name' => 'Command10', 'caption' => 'Recalcular Importes', 'accion' => 'doform', 'form' => '', 'menu' => 'nueva', 'col' => 2, 'fila' => 0),
            array('name' => 'Command6',  'caption' => 'Borra Prestacion', 'accion' => 'doform', 'form' => '', 'menu' => 'popup'),
            array('name' => 'Command7',  'caption' => 'Cambiar Valores', 'accion' => 'doform', 'form' => '', 'menu' => 'popup'),
            array('name' => 'Command13', 'caption' => 'Borrar Debito', 'accion' => 'doform', 'form' => '', 'menu' => 'popup'),
            array('name' => 'Command19', 'caption' => 'Historico de Valores', 'accion' => 'doform', 'form' => 'verprac'),
            array('name' => 'Command9',  'caption' => 'Imprimir', 'accion' => 'doform', 'form' => ''),
            array('name' => 'Command33', 'caption' => 'Ajustes de Prestaciones sobre el afiliado', 'accion' => 'doform', 'form' => 'ajusfafi'),
            array('name' => 'Command46', 'caption' => 'Ajusta Valor Importe MULTI', 'accion' => 'doform', 'form' => 'ajusfact'),
            array('name' => 'Command40', 'caption' => 'Buscar', 'accion' => 'doform', 'form' => 'busca'),
            array('name' => 'Command27', 'caption' => 'Buscar Contrato', 'accion' => 'doform', 'form' => 'frmadjcontrato'),
            array('name' => 'Command43', 'caption' => 'Sacar Debitos Multi', 'accion' => 'doform', 'form' => ''),
            array('name' => 'Command42', 'caption' => 'Internaciones', 'accion' => 'doform', 'form' => 'cargainter'),
            array('name' => 'Command50', 'caption' => 'Otras Liq .mismo Prestador', 'accion' => 'doform', 'form' => 'otraliqm'),
            array('name' => 'Command21', 'caption' => 'Ordenar x Ingreso', 'accion' => 'orden', 'orden' => 'nroorden', 'menu' => 'nueva', 'col' => 1, 'fila' => 1),
            array('name' => 'Command25', 'caption' => 'Agregar leyenda', 'accion' => 'doform', 'form' => 'pideleyd', 'menu' => 'popup', 'sep_after' => true),
            array('name' => 'Command24', 'caption' => 'Debitar  Agrupados', 'accion' => 'doform', 'form' => 'debitar'),
            array('name' => 'Command36', 'caption' => 'Quita todos los CosegurosPre', 'accion' => 'doform', 'form' => ''),
            array('name' => 'Command20', 'caption' => 'Prestaciones  Liq Anteriores', 'accion' => 'doform', 'form' => 'vedetant'),
            array('name' => 'btnvalorespra', 'caption' => 'Valores Practicas', 'accion' => 'doform', 'form' => 'verprac'),
            array('name' => 'Command32', 'caption' => 'Re Sumando Pantalla', 'accion' => 'recalc'),
            array('name' => 'Command47', 'caption' => 'Ajusta Valor Importe Prestador', 'accion' => 'doform', 'form' => 'ajusfact'),
            array('name' => 'Command26', 'caption' => 'Composicion Modular', 'accion' => 'doform', 'form' => ''),
            array('name' => 'Command14', 'caption' => 'Revisar Nombre Prestacion', 'accion' => 'doform', 'form' => 'chgprest'),
            array('name' => 'Command11', 'caption' => 'Confirmar Cierre', 'accion' => 'cierre', 'menu' => 'nueva', 'col' => 3, 'fila' => 1),
            array('name' => 'Command31', 'caption' => 'Cambia Diagnostico', 'accion' => 'doform', 'form' => 'chgdiag2', 'menu' => 'popup'),
            array('name' => 'Command52', 'caption' => 'Cambia Fecha', 'accion' => 'doform', 'form' => 'pidefec2', 'menu' => 'popup', 'sep_after' => true),
            array('name' => 'busca',     'caption' => 'Buscar Afiliado', 'accion' => 'doform', 'form' => 'busca'),
            array('name' => 'Command37', 'caption' => 'Salir Conservando Cambios', 'accion' => 'cierre', 'menu' => 'nueva', 'col' => 0, 'fila' => 1),
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
        $ok = Permission::tiene($permisos, self::CLAVE_PERMISO)
            || Permission::tieneRuta($permisos, 'audit-facturacion');
        if (!$ok) {
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
