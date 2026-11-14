<?php
/**
 * controllers/TablaGeneralController.php
 * ABM dinámico multi-tabla (Archivos → Tablas Generales).
 *
 * Dialecto PHP 5.6. Portado de cursor/fix-menu-accordion-stuck.
 *
 * Ruta: index.php?route=tablas-generales&ref=LOC
 * Acciones AJAX: action=guardar | eliminar | obtener
 */

require_once __DIR__ . '/../models/TablaGeneralModel.php';
require_once __DIR__ . '/../models/Permission.php';

class TablaGeneralController
{
    const LIMITE_DEFAULT = 50;
    const USER_ID_ADMIN  = 16;

    /**
     * @var array
     */
    const CONFIG_TABLAS = array(
        'AAF' => array('tabla' => 'tablas', 'tatipo' => 'AAF', 'titulo' => 'Ajustes al Afiliado', 'campos' => array('tacodigo', 'tadescrip')),
        'AJU' => array('tabla' => 'tablas', 'tatipo' => 'AJU', 'titulo' => 'Ajustes Impositivos', 'campos' => array('tacodigo', 'tadescrip')),
        'AUM' => array('tabla' => 'tablas', 'tatipo' => 'AUM', 'titulo' => 'Cierre de Aumentos', 'campos' => array('tadescrip')),
        'BAN' => array('tabla' => 'tablas', 'tatipo' => 'BAN', 'titulo' => 'Bancos', 'campos' => array('tacodigo', 'tadescrip')),
        'CAT' => array('tabla' => 'tablas', 'tatipo' => 'CAT', 'titulo' => 'Tipos Categoria Prestadores', 'campos' => array('tacodigo', 'tadescrip')),
        'CER' => array('tabla' => 'tablas', 'tatipo' => 'CER', 'titulo' => 'Tipos Certificados Afiliados Control', 'campos' => array('tacodigo', 'tadescrip')),
        'CPO' => array('tabla' => 'tablas', 'tatipo' => 'CPO', 'titulo' => 'Guia de Codigos Postales con Localidades', 'campos' => array('tacodigo', 'tadescrip', 'taprovin')),
        'DEB' => array('tabla' => 'tablas', 'tatipo' => 'DEB', 'titulo' => 'Tipos de Debitos', 'campos' => array('tacodigo', 'tadescrip', 'tatipodeb', 'taselecc')),
        'EMP' => array('tabla' => 'tablas', 'tatipo' => 'EMP', 'titulo' => 'Empresas', 'campos' => array('tacodigo', 'tadescrip')),
        'ESP' => array('tabla' => 'tablas', 'tatipo' => 'ESP', 'titulo' => 'Tipos de Especializacion', 'campos' => array('tacodigo', 'tadescrip')),
        'GR1' => array('tabla' => 'tablas', 'tatipo' => 'GR1', 'titulo' => 'Grupos Carga Autorizaciones 1', 'campos' => array('tacodigo', 'tadescrip')),
        'GR2' => array('tabla' => 'tablas', 'tatipo' => 'GR2', 'titulo' => 'Grupos Carga Autorizaciones 2', 'campos' => array('tacodigo', 'tadescrip')),
        'GR3' => array('tabla' => 'tablas', 'tatipo' => 'GR3', 'titulo' => 'Grupos Carga Autorizaciones 3', 'campos' => array('tacodigo', 'tadescrip')),
        'GRW' => array('tabla' => 'tablas', 'tatipo' => 'GRW', 'titulo' => 'Grupos Web p/Prestadores', 'campos' => array('tacodigo', 'tadescrip')),
        'HOM' => array('tabla' => 'tablas', 'tatipo' => 'HOM', 'titulo' => 'Homologacion Nombres p/Nomenclador', 'campos' => array('tacodigo', 'tadescrip')),
        'LOC' => array('tabla' => 'tablas', 'tatipo' => 'LOC', 'titulo' => 'Localidades', 'campos' => array('tacodigo', 'tadescrip', 'taprovin', 'vista')),
        'LST' => array('tabla' => 'tablas', 'tatipo' => 'LST', 'titulo' => 'Valores Calculo Lista Ventas', 'campos' => array('tacodigo', 'tadescrip', 'taimporte')),
        'POL' => array('tabla' => 'tablas', 'tatipo' => 'POL', 'titulo' => 'Tipos Prestadores Categoria', 'campos' => array('tacodigo', 'tadescrip', 'taselecc')),
        'SBC' => array('tabla' => 'tablas', 'tatipo' => 'SBC', 'titulo' => 'Subcategorias p/Prestadores', 'campos' => array('tacodigo', 'tadescrip')),
        'SUB' => array('tabla' => 'tablas', 'tatipo' => 'SUB', 'titulo' => 'Subgrupo N.N.', 'campos' => array('tacodigo', 'tadescrip')),
        'TES' => array('tabla' => 'tablas', 'tatipo' => 'TES', 'titulo' => 'Tipos de Estudios', 'campos' => array('tacodigo', 'tadescrip')),
        'TCX' => array('tabla' => 'tablas', 'tatipo' => 'TCX', 'titulo' => 'Tipos CX p/Estadisticas', 'campos' => array('tacodigo', 'tadescrip')),
        'TXT' => array('tabla' => 'tablas', 'tatipo' => 'TXT', 'titulo' => 'Texto Fijos', 'campos' => array('tacodigo', 'tadescrip')),
        'URL' => array('tabla' => 'tablas', 'tatipo' => 'URL', 'titulo' => 'Url Referencias', 'campos' => array('tadescrip')),
        'ZON' => array('tabla' => 'tablas', 'tatipo' => 'ZON', 'titulo' => 'Zonas', 'campos' => array('tacodigo', 'tadescrip')),
        'GRUPOCARTI' => array('tabla' => 'grupocarti', 'tatipo' => null, 'titulo' => 'Grupos Cartilla N.N', 'campos' => array('tacodigo', 'tadescrip')),
        'GRUPONN'    => array('tabla' => 'gruponn',    'tatipo' => null, 'titulo' => 'Grupos N.N', 'campos' => array('tacodigo', 'tadescrip')),
        'GRUPOSUP'   => array('tabla' => 'gruposup',   'tatipo' => null, 'titulo' => 'Titulo Cartilla N.N', 'campos' => array('tacodigo', 'tadescrip')),
        'NOMENTP'    => array('tabla' => 'nomentp',    'tatipo' => null, 'titulo' => 'Tipo Normativa 650', 'campos' => array('tacodigo', 'tadescrip')),
        'DIAGNO'     => array('tabla' => 'diagno',     'tatipo' => null, 'titulo' => 'Actualizacion Diagnosticos', 'campos' => array('tacodigo', 'tadescrip', 'tagrupo', 'isonco', 'isvih')),
    );

    /** Slug de menú antiguo → ref canónico. */
    const ROUTE_A_REF = array(
        'localidades'            => 'LOC',
        'zonas'                  => 'ZON',
        'grupos-web'             => 'GRW',
        'empresas'               => 'EMP',
        'bancos'                 => 'BAN',
        'cierre-aumentos-tabla'  => 'AUM',
        'tipos-certificados'     => 'CER',
        'valores-venta'          => 'LST',
        'url-referencias'        => 'URL',
        'tipos-cx'               => 'TCX',
        'subcategorias'          => 'SBC',
        'homologacion'           => 'HOM',
        'grupos-autorizaciones'  => 'GR1',
        'motivos-debitos'        => 'DEB',
        'subgrupos-nomenclador'  => 'SUB',
        'ajustes-impuestos'      => 'AJU',
        'ajustes-afiliados'      => 'AAF',
        'textos-rechazo'         => 'TXT',
        'grupos-cartilla'        => 'GRUPOCARTI',
        'grupos-nomenclador'     => 'GRUPONN',
        'titulo-cartilla'        => 'GRUPOSUP',
        'diagnosticos'           => 'DIAGNO',
        'normativa-650'          => 'NOMENTP',
    );

    /** Permiso de menú asociado a cada ref. */
    const REF_A_CLAVE = array(
        'AAF' => 'MNU_ARC_TAB_AJ_AFI',
        'AJU' => 'MNU_ARC_TAB_AJ_IMP',
        'AUM' => 'MNU_ARC_TAB_CIERRE_AUM',
        'BAN' => 'MNU_ARC_TAB_BANCOS',
        'CAT' => 'MNU_ARC_TAB_SUBCAT',
        'CER' => 'MNU_ARC_TAB_TIPO_CERT',
        'CPO' => 'MNU_ARC_TAB_LOCALIDADES',
        'DEB' => 'MNU_ARC_TAB_MOT_DEB',
        'EMP' => 'MNU_ARC_TAB_EMPRESAS',
        'ESP' => 'MNU_ARC_TAB_ESP',
        'GR1' => 'MNU_ARC_TAB_GRP_AUT',
        'GR2' => 'MNU_ARC_TAB_GRP_AUT',
        'GR3' => 'MNU_ARC_TAB_GRP_AUT',
        'GRW' => 'MNU_ARC_TAB_GRUPO_WEB',
        'HOM' => 'MNU_ARC_TAB_HOMOL',
        'LOC' => 'MNU_ARC_TAB_LOCALIDADES',
        'LST' => 'MNU_ARC_TAB_VAL_VENTA',
        'POL' => 'MNU_ARC_TAB_SUBCAT',
        'SBC' => 'MNU_ARC_TAB_SUBCAT',
        'SUB' => 'MNU_ARC_TAB_SUBGRP_NN',
        'TES' => 'MNU_ARC_TAB_TIPOS_CX',
        'TCX' => 'MNU_ARC_TAB_TIPOS_CX',
        'TXT' => 'MNU_ARC_TAB_TXT_REC',
        'URL' => 'MNU_ARC_TAB_URL_REF',
        'ZON' => 'MNU_ARC_TAB_ZONAS',
        'GRUPOCARTI' => 'MNU_ARC_TAB_GRP_CARTILLA',
        'GRUPONN'    => 'MNU_ARC_TAB_GRP_NN',
        'GRUPOSUP'   => 'MNU_ARC_TAB_TIT_CARTILLA',
        'NOMENTP'    => 'MNU_ARC_TAB_NOMENTP',
        'DIAGNO'     => 'MNU_ARC_TAB_DIAG',
    );

    const LABELS_CAMPOS = array(
        'tacodigo'  => 'Código',
        'tadescrip' => 'Descripción',
        'taprovin'  => 'Provincia',
        'vista'     => 'Vista',
        'tatipodeb' => 'Tipo débito',
        'taselecc'  => 'Selección',
        'taimporte' => 'Importe',
        'tagrupo'   => 'Grupo',
        'isonco'    => 'Oncológico',
        'isvih'     => 'VIH',
    );

    public function index()
    {
        $this->requireAuth();

        $configTablas = self::CONFIG_TABLAS;
        $ref          = $this->resolverRefORedirigir(false, true);

        if ($ref === '') {
            $this->hub();
            return;
        }

        if (!$this->puedeVerRef($ref)) {
            $this->denegar();
            return;
        }

        $cfg = $configTablas[$ref];

        $busqueda = isset($_GET['buscar']) ? trim((string) $_GET['buscar']) : '';
        $pagina   = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
        $ordenCol = isset($_GET['sort']) ? strtolower(trim((string) $_GET['sort'])) : 'tacodigo';
        $ordenDir = isset($_GET['dir']) ? strtoupper(trim((string) $_GET['dir'])) : 'ASC';
        $limite   = self::LIMITE_DEFAULT;

        if (!in_array($ordenCol, $cfg['campos'], true) && $ordenCol !== 'id') {
            $ordenCol = in_array('tacodigo', $cfg['campos'], true) ? 'tacodigo' : $cfg['campos'][0];
        }
        $ordenDir = ($ordenDir === 'DESC') ? 'DESC' : 'ASC';

        $esAdmin      = $this->esAdmin();
        $puedeAgregar = $esAdmin;
        $puedeEditar  = $esAdmin;
        $puedeBorrar  = $esAdmin;

        $tabla  = $cfg['tabla'];
        $tatipo = $cfg['tatipo'];

        $model   = new TablaGeneralModel();
        $dbError = null;
        try {
            $registros = $model->listar($tabla, $tatipo, $busqueda, $pagina, $limite, $cfg['campos'], $ordenCol, $ordenDir);
            $total     = $model->contar($tabla, $tatipo, $busqueda, $cfg['campos']);
        } catch (PDOException $e) {
            error_log('TablaGeneralController::index — ' . $e->getMessage());
            $registros = array();
            $total     = 0;
            $dbError   = $e->getMessage();
        }

        $totalPaginas = $total > 0 ? (int) ceil($total / $limite) : 1;
        if ($pagina > $totalPaginas) {
            $pagina = $totalPaginas;
        }

        $campos    = $cfg['campos'];
        $titulo    = $cfg['titulo'];
        $labels    = self::LABELS_CAMPOS;
        $csrfToken = isset($_SESSION['csrf_token']) ? (string) $_SESSION['csrf_token'] : '';
        $flashOk   = isset($_SESSION['flash_ok']) ? $_SESSION['flash_ok'] : null;
        $flashError = isset($_SESSION['flash_error']) ? $_SESSION['flash_error'] : null;
        unset($_SESSION['flash_ok'], $_SESSION['flash_error']);

        $queryBase = array(
            'ref'    => $ref,
            'buscar' => $busqueda,
            'sort'   => $ordenCol,
            'dir'    => $ordenDir,
        );
        $sortLinks = array();
        foreach ($campos as $col) {
            $nextDir = ($ordenCol === $col && $ordenDir === 'ASC') ? 'DESC' : 'ASC';
            $sortLinks[$col] = $this->buildQueryUrl(array(
                'ref'    => $ref,
                'buscar' => $busqueda,
                'sort'   => $col,
                'dir'    => $nextDir,
                'page'   => 1,
            ));
        }

        $pageTitle  = 'COMEDICA — ' . $titulo;
        $breadcrumb = array(
            array('label' => 'Archivos'),
            array('label' => 'Tablas Generales', 'url' => 'index.php?route=tablas-generales'),
            array('label' => $titulo),
        );

        require_once __DIR__ . '/../views/tablas_generales/index.php';
    }

    public function obtener()
    {
        $this->requireAuth();
        $ref = $this->resolverRefORedirigir(true);
        if (!$this->puedeVerRef($ref)) {
            $this->json(array('ok' => false, 'error' => 'Sin permiso.'), 403);
        }

        $cfg = self::CONFIG_TABLAS[$ref];
        $id  = isset($_GET['id']) ? (int) $_GET['id'] : 0;

        if ($id <= 0) {
            $this->json(array('ok' => false, 'error' => 'Identificador inválido.'), 400);
        }

        $model = new TablaGeneralModel();
        $row   = $model->obtener($cfg['tabla'], $id, $cfg['tatipo'], $cfg['campos']);
        if ($row === null) {
            $this->json(array('ok' => false, 'error' => 'Registro no encontrado.'), 404);
        }

        $this->json(array('ok' => true, 'registro' => $row));
    }

    public function guardar()
    {
        $this->requireAuth();
        $this->requireCsrf();

        $ref = $this->resolverRefORedirigir(true);
        $cfg = self::CONFIG_TABLAS[$ref];
        $id  = isset($_POST['id']) ? (int) $_POST['id'] : 0;

        $esAdmin = $this->esAdmin();
        if ($id > 0 && !$esAdmin) {
            $this->json(array('ok' => false, 'error' => 'Sin permiso para editar.'), 403);
        }
        if ($id <= 0 && !$esAdmin) {
            $this->json(array('ok' => false, 'error' => 'Sin permiso para agregar.'), 403);
        }

        $datos = $this->datosDesdePost($cfg['campos']);

        if (in_array('tacodigo', $cfg['campos'], true)) {
            $cod = isset($datos['tacodigo']) ? trim((string) $datos['tacodigo']) : '';
            if ($cod === '') {
                $this->json(array('ok' => false, 'error' => 'El código es obligatorio.'), 422);
            }
        }
        if (in_array('tadescrip', $cfg['campos'], true)) {
            $des = isset($datos['tadescrip']) ? trim((string) $datos['tadescrip']) : '';
            if ($des === '') {
                $this->json(array('ok' => false, 'error' => 'La descripción es obligatoria.'), 422);
            }
        }

        $model  = new TablaGeneralModel();
        $tabla  = $cfg['tabla'];
        $tatipo = $cfg['tatipo'];

        try {
            if (in_array('tacodigo', $cfg['campos'], true)) {
                $codigo = isset($datos['tacodigo']) ? (string) $datos['tacodigo'] : '';
                if ($model->existeCodigo($tabla, $tatipo, $codigo, $id)) {
                    $this->json(array('ok' => false, 'error' => 'Ya existe un registro con ese código.'), 422);
                }
            }

            if ($id > 0) {
                $existente = $model->obtener($tabla, $id, $tatipo, $cfg['campos']);
                if ($existente === null) {
                    $this->json(array('ok' => false, 'error' => 'Registro no encontrado.'), 404);
                }
                $model->actualizar($tabla, $id, $datos);
                $this->json(array('ok' => true, 'mensaje' => 'Registro actualizado.', 'id' => $id));
            }

            $nuevoId = $model->insertar($tabla, $datos, $tatipo);
            $this->json(array('ok' => true, 'mensaje' => 'Registro creado.', 'id' => $nuevoId));
        } catch (PDOException $e) {
            error_log('TablaGeneralController::guardar — ' . $e->getMessage());
            $this->json(array('ok' => false, 'error' => 'No se pudo guardar el registro.'), 500);
        } catch (InvalidArgumentException $e) {
            $this->json(array('ok' => false, 'error' => $e->getMessage()), 422);
        }
    }

    public function eliminar()
    {
        $this->requireAuth();
        $this->requireCsrf();

        $ref = $this->resolverRefORedirigir(true);
        $cfg = self::CONFIG_TABLAS[$ref];
        $id  = 0;
        if (isset($_POST['id'])) {
            $id = (int) $_POST['id'];
        } elseif (isset($_GET['id'])) {
            $id = (int) $_GET['id'];
        }

        if (!$this->esAdmin()) {
            $this->json(array('ok' => false, 'error' => 'Sin permiso para borrar.'), 403);
        }
        if ($id <= 0) {
            $this->json(array('ok' => false, 'error' => 'Identificador inválido.'), 400);
        }

        $model = new TablaGeneralModel();
        try {
            $ok = $model->eliminar($cfg['tabla'], $id, $cfg['tatipo']);
            if (!$ok) {
                $this->json(array('ok' => false, 'error' => 'No se encontró el registro o ya fue eliminado.'), 404);
            }
            $this->json(array('ok' => true, 'mensaje' => 'Registro eliminado.'));
        } catch (PDOException $e) {
            error_log('TablaGeneralController::eliminar — ' . $e->getMessage());
            $this->json(array('ok' => false, 'error' => 'No se pudo eliminar el registro.'), 500);
        }
    }

    /**
     * Ítems del submenú Archivos → Tablas Generales.
     *
     * @return array
     */
    public static function menuItems()
    {
        $out = array(
            array(
                'ref'    => 'CABECERA_MAILS',
                'titulo' => 'Cabecera Mails',
                'clave'  => 'MNU_ARC_TAB_CABECERA_MAILS',
                'ruta'   => 'cabecera-mails',
            ),
        );
        foreach (self::CONFIG_TABLAS as $ref => $cfg) {
            $out[] = array(
                'ref'    => $ref,
                'titulo' => $cfg['titulo'],
                'clave'  => isset(self::REF_A_CLAVE[$ref]) ? self::REF_A_CLAVE[$ref] : ('MNU_ARC_TAB_' . $ref),
                'ruta'   => 'tablas-generales&ref=' . $ref,
            );
        }
        return $out;
    }

    /**
     * @return string[]
     */
    public static function clavesMenu()
    {
        return array_values(array_unique(array_values(self::REF_A_CLAVE)));
    }

    /**
     * @return array
     */
    public static function configTablas()
    {
        return self::CONFIG_TABLAS;
    }

    /**
     * @param  bool   $ajax
     * @param  bool   $permitirVacio
     * @return string
     */
    private function resolverRefORedirigir($ajax = false, $permitirVacio = false)
    {
        $ref = '';
        if (isset($_GET['ref'])) {
            $ref = strtoupper(trim((string) $_GET['ref']));
        } elseif (isset($_POST['ref'])) {
            $ref = strtoupper(trim((string) $_POST['ref']));
        } elseif (isset($_GET['tipo'])) {
            $ref = strtoupper(trim((string) $_GET['tipo']));
        } elseif (isset($_POST['tipo'])) {
            $ref = strtoupper(trim((string) $_POST['tipo']));
        }

        if ($ref === '' || !isset(self::CONFIG_TABLAS[$ref])) {
            $route = isset($_GET['route']) ? trim((string) $_GET['route']) : '';
            if (isset(self::ROUTE_A_REF[$route])) {
                $ref = self::ROUTE_A_REF[$route];
                if (!$ajax) {
                    header('Location: ' . $this->baseUrl() . '?route=tablas-generales&ref=' . rawurlencode($ref));
                    exit;
                }
            }
        }

        if ($ref === '' || !isset(self::CONFIG_TABLAS[$ref])) {
            if ($ajax) {
                $this->json(array('ok' => false, 'error' => 'Debe indicar un módulo válido (?ref=).'), 400);
            }
            if ($permitirVacio) {
                return '';
            }
            header('Location: ' . $this->baseUrl() . '?route=tablas-generales');
            exit;
        }

        return $ref;
    }

    private function hub()
    {
        $esAdmin      = $this->esAdmin();
        $puedeAgregar = $esAdmin;
        $puedeEditar  = $esAdmin;
        $puedeBorrar  = $esAdmin;

        $modulos = array();
        $permisos = isset($_SESSION['permisos']) ? $_SESSION['permisos'] : array();
        foreach (self::menuItems() as $m) {
            $clave = isset($m['clave']) ? $m['clave'] : '';
            if ($esAdmin || ($clave !== '' && Permission::tiene($permisos, $clave))) {
                $modulos[] = $m;
            }
        }

        $pageTitle  = 'COMEDICA — Tablas Generales';
        $breadcrumb = array(
            array('label' => 'Archivos'),
            array('label' => 'Tablas Generales'),
        );

        require_once __DIR__ . '/../views/tablas_generales/hub.php';
    }

    /**
     * @param  array $campos
     * @return array
     */
    private function datosDesdePost(array $campos)
    {
        $checks = array('vista', 'taselecc', 'isonco', 'isvih');
        $datos  = array();
        foreach ($campos as $col) {
            if (in_array($col, $checks, true)) {
                $raw = isset($_POST[$col]) ? $_POST[$col] : '0';
                $datos[$col] = ($raw === '1' || $raw === 1 || $raw === 'on' || $raw === true) ? 1 : 0;
                continue;
            }
            $datos[$col] = isset($_POST[$col]) ? $_POST[$col] : '';
        }
        return $datos;
    }

    /**
     * @param  array $params
     * @return string
     */
    private function buildQueryUrl(array $params)
    {
        $params = array_merge(array('route' => 'tablas-generales'), $params);
        foreach ($params as $k => $v) {
            if ($v === '' || $v === null) {
                unset($params[$k]);
            }
        }
        return 'index.php?' . http_build_query($params);
    }

    /**
     * @return int
     */
    private function currentUserId()
    {
        if (!empty($_SESSION['user']['id'])) {
            return (int) $_SESSION['user']['id'];
        }
        if (!empty($_SESSION['user_id'])) {
            return (int) $_SESSION['user_id'];
        }
        return 0;
    }

    /**
     * @return bool
     */
    private function esAdmin()
    {
        $userId = $this->currentUserId();
        $idRol  = 0;
        if (isset($_SESSION['user']['id_rol'])) {
            $idRol = (int) $_SESSION['user']['id_rol'];
        }
        return ($userId === self::USER_ID_ADMIN || $idRol === self::USER_ID_ADMIN);
    }

    /**
     * @param  string $ref
     * @return bool
     */
    private function puedeVerRef($ref)
    {
        if ($this->esAdmin()) {
            return true;
        }
        $clave = isset(self::REF_A_CLAVE[$ref]) ? self::REF_A_CLAVE[$ref] : '';
        if ($clave === '') {
            return false;
        }
        $permisos = isset($_SESSION['permisos']) ? $_SESSION['permisos'] : array();
        return Permission::tiene($permisos, $clave);
    }

    private function denegar()
    {
        $_SESSION['flash_warning'] = 'No tiene permiso para acceder al módulo solicitado.';
        header('Location: ' . $this->baseUrl() . '?route=dashboard');
        exit;
    }

    private function requireAuth()
    {
        if (empty($_SESSION['user'])) {
            header('Location: ' . $this->baseUrl() . '?route=login');
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
            $this->json(array('ok' => false, 'error' => 'Token de seguridad inválido. Recargue la página.'), 403);
        }
    }

    /**
     * @param array $payload
     * @param int   $code
     */
    private function json(array $payload, $code = 200)
    {
        http_response_code((int) $code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * @return string
     */
    private function baseUrl()
    {
        if (function_exists('getBaseUrl')) {
            return getBaseUrl();
        }
        $https  = isset($_SERVER['HTTPS']) ? $_SERVER['HTTPS'] : '';
        $scheme = (!empty($https) && $https !== 'off') ? 'https' : 'http';
        $host   = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
        $scriptName = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '';
        $script = rtrim(dirname($scriptName), '/\\');
        return $scheme . '://' . $host . $script . '/index.php';
    }
}
