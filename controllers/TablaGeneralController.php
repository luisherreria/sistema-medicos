<?php
/**
 * controllers/TablaGeneralController.php
 * ABM ultra-dinámico multi-tabla.
 *
 * Ruta: index.php?route=tablas-generales&ref=SUB
 * Acciones AJAX: action=guardar | eliminar | obtener
 */

declare(strict_types=1);

require_once __DIR__ . '/../models/TablaGeneralModel.php';

class TablaGeneralController
{
    private const LIMITE_DEFAULT = 50;
    private const USER_ID_ADMIN  = 16;

    /**
     * @var array<string, array{tabla: string, tatipo: string|null, titulo: string, campos: string[]}>
     */
    public const CONFIG_TABLAS = [
        // ── Tabla maestra `tablas` (filtro tatipo) ────────────────────────
        'AAF' => ['tabla' => 'tablas', 'tatipo' => 'AAF', 'titulo' => 'Ajustes al Afiliado', 'campos' => ['tacodigo', 'tadescrip']],
        'AJU' => ['tabla' => 'tablas', 'tatipo' => 'AJU', 'titulo' => 'Ajustes Impositivos', 'campos' => ['tacodigo', 'tadescrip']],
        'AUM' => ['tabla' => 'tablas', 'tatipo' => 'AUM', 'titulo' => 'Cierre de Aumentos', 'campos' => ['tadescrip']],
        'BAN' => ['tabla' => 'tablas', 'tatipo' => 'BAN', 'titulo' => 'Bancos', 'campos' => ['tacodigo', 'tadescrip']],
        'CAT' => ['tabla' => 'tablas', 'tatipo' => 'CAT', 'titulo' => 'Tipos Categoria Prestadores', 'campos' => ['tacodigo', 'tadescrip']],
        'CER' => ['tabla' => 'tablas', 'tatipo' => 'CER', 'titulo' => 'Tipos Certificados Afiliados Control', 'campos' => ['tacodigo', 'tadescrip']],
        'CPO' => ['tabla' => 'tablas', 'tatipo' => 'CPO', 'titulo' => 'Guia de Codigos Postales con Localidades', 'campos' => ['tacodigo', 'tadescrip', 'taprovin']],
        'DEB' => ['tabla' => 'tablas', 'tatipo' => 'DEB', 'titulo' => 'Tipos de Debitos', 'campos' => ['tacodigo', 'tadescrip', 'tatipodeb', 'taselecc']],
        'EMP' => ['tabla' => 'tablas', 'tatipo' => 'EMP', 'titulo' => 'Empresas', 'campos' => ['tacodigo', 'tadescrip']],
        'ESP' => ['tabla' => 'tablas', 'tatipo' => 'ESP', 'titulo' => 'Tipos de Especializacion', 'campos' => ['tacodigo', 'tadescrip']],
        'GR1' => ['tabla' => 'tablas', 'tatipo' => 'GR1', 'titulo' => 'Grupos Carga Autorizaciones 1', 'campos' => ['tacodigo', 'tadescrip']],
        'GR2' => ['tabla' => 'tablas', 'tatipo' => 'GR2', 'titulo' => 'Grupos Carga Autorizaciones 2', 'campos' => ['tacodigo', 'tadescrip']],
        'GR3' => ['tabla' => 'tablas', 'tatipo' => 'GR3', 'titulo' => 'Grupos Carga Autorizaciones 3', 'campos' => ['tacodigo', 'tadescrip']],
        'GRW' => ['tabla' => 'tablas', 'tatipo' => 'GRW', 'titulo' => 'Grupos Web p/Prestadores', 'campos' => ['tacodigo', 'tadescrip']],
        'HOM' => ['tabla' => 'tablas', 'tatipo' => 'HOM', 'titulo' => 'Homologacion Nombres p/Nomenclador', 'campos' => ['tacodigo', 'tadescrip']],
        'LOC' => ['tabla' => 'tablas', 'tatipo' => 'LOC', 'titulo' => 'Localidades', 'campos' => ['tacodigo', 'tadescrip', 'taprovin', 'vista']],
        'LST' => ['tabla' => 'tablas', 'tatipo' => 'LST', 'titulo' => 'Valores Calculo Lista Ventas', 'campos' => ['tacodigo', 'tadescrip', 'taimporte']],
        'POL' => ['tabla' => 'tablas', 'tatipo' => 'POL', 'titulo' => 'Tipos Prestadores Categoria', 'campos' => ['tacodigo', 'tadescrip', 'taselecc']],
        'SBC' => ['tabla' => 'tablas', 'tatipo' => 'SBC', 'titulo' => 'Subcategorias p/Prestadores', 'campos' => ['tacodigo', 'tadescrip']],
        'SUB' => ['tabla' => 'tablas', 'tatipo' => 'SUB', 'titulo' => 'Subgrupo N.N.', 'campos' => ['tacodigo', 'tadescrip']],
        'TES' => ['tabla' => 'tablas', 'tatipo' => 'TES', 'titulo' => 'Tipos de Estudios', 'campos' => ['tacodigo', 'tadescrip']],
        'TCX' => ['tabla' => 'tablas', 'tatipo' => 'TCX', 'titulo' => 'Tipos CX p/Estadisticas', 'campos' => ['tacodigo', 'tadescrip']],
        'TXT' => ['tabla' => 'tablas', 'tatipo' => 'TXT', 'titulo' => 'Texto Fijos', 'campos' => ['tacodigo', 'tadescrip']],
        'URL' => ['tabla' => 'tablas', 'tatipo' => 'URL', 'titulo' => 'Url Referencias', 'campos' => ['tadescrip']],
        'ZON' => ['tabla' => 'tablas', 'tatipo' => 'ZON', 'titulo' => 'Zonas', 'campos' => ['tacodigo', 'tadescrip']],
        // ── Tablas independientes ─────────────────────────────────────────
        'GRUPOCARTI' => ['tabla' => 'grupocarti', 'tatipo' => null, 'titulo' => 'Grupos Cartilla N.N', 'campos' => ['tacodigo', 'tadescrip']],
        'GRUPONN'    => ['tabla' => 'gruponn',    'tatipo' => null, 'titulo' => 'Grupos N.N', 'campos' => ['tacodigo', 'tadescrip']],
        'GRUPOSUP'   => ['tabla' => 'gruposup',   'tatipo' => null, 'titulo' => 'Titulo Cartilla N.N', 'campos' => ['tacodigo', 'tadescrip']],
        'NOMENTP'    => ['tabla' => 'nomentp',    'tatipo' => null, 'titulo' => 'Tipo Normativa 650', 'campos' => ['tacodigo', 'tadescrip']],
        'DIAGNO'     => ['tabla' => 'diagno',     'tatipo' => null, 'titulo' => 'Actualizacion Diagnosticos', 'campos' => ['tacodigo', 'tadescrip', 'tagrupo', 'isonco', 'isvih']],
    ];

    /** Slug de menú antiguo → ref canónico. */
    private const ROUTE_A_REF = [
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
    ];

    /** Permiso de menú asociado a cada ref (para filtrar a no-admin). */
    private const REF_A_CLAVE = [
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
    ];

    /** @var array<string, string> */
    public const LABELS_CAMPOS = [
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
    ];

    public function index(): void
    {
        $this->requireAuth();

        $configTablas = self::CONFIG_TABLAS;
        $ref          = $this->resolverRefORedirigir(false, true);

        if ($ref === '') {
            $this->hub();
            return;
        }

        $cfg = $configTablas[$ref];

        $busqueda = trim((string) ($_GET['buscar'] ?? ''));
        $pagina   = max(1, (int) ($_GET['page'] ?? 1));
        $ordenCol = strtolower(trim((string) ($_GET['sort'] ?? 'tacodigo')));
        $ordenDir = strtoupper(trim((string) ($_GET['dir'] ?? 'ASC')));
        $limite   = self::LIMITE_DEFAULT;

        if (!in_array($ordenCol, $cfg['campos'], true) && $ordenCol !== 'id') {
            $ordenCol = in_array('tacodigo', $cfg['campos'], true) ? 'tacodigo' : $cfg['campos'][0];
        }
        $ordenDir = $ordenDir === 'DESC' ? 'DESC' : 'ASC';

        $userId = $this->currentUserId();
        if (empty($_SESSION['user_id'])) {
            $_SESSION['user_id'] = $userId;
        }
        $esAdmin      = ($userId === self::USER_ID_ADMIN);
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
            $registros = [];
            $total     = 0;
            $dbError   = $e->getMessage();
        }

        $totalPaginas = $total > 0 ? (int) ceil($total / $limite) : 1;
        if ($pagina > $totalPaginas) {
            $pagina = $totalPaginas;
        }

        $campos     = $cfg['campos'];
        $titulo     = $cfg['titulo'];
        $labels     = self::LABELS_CAMPOS;
        $csrfToken  = (string) ($_SESSION['csrf_token'] ?? '');
        $flashOk    = $_SESSION['flash_ok'] ?? null;
        $flashError = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_ok'], $_SESSION['flash_error']);

        $sortLinks = [];
        foreach ($campos as $col) {
            $nextDir = ($ordenCol === $col && $ordenDir === 'ASC') ? 'DESC' : 'ASC';
            $sortLinks[$col] = $this->buildQueryUrl([
                'ref'    => $ref,
                'buscar' => $busqueda,
                'page'   => 1,
                'sort'   => $col,
                'dir'    => $nextDir,
            ]);
        }

        $queryBase = [
            'ref'    => $ref,
            'buscar' => $busqueda,
            'sort'   => $ordenCol,
            'dir'    => $ordenDir,
        ];

        $pageTitle  = 'COMEDICA — ' . $titulo;
        $breadcrumb = [
            ['label' => 'Archivos'],
            ['label' => 'Tablas Generales', 'url' => 'index.php?route=tablas-generales&ref=' . rawurlencode($ref)],
            ['label' => $titulo],
        ];

        require_once __DIR__ . '/../views/tablas_generales_list.php';
    }

    public function obtener(): void
    {
        $this->requireAuth();
        $ref = $this->resolverRefORedirigir(true);
        $cfg = self::CONFIG_TABLAS[$ref];
        $id  = (int) ($_GET['id'] ?? 0);

        if ($id <= 0) {
            $this->json(['ok' => false, 'error' => 'Identificador inválido.'], 400);
        }

        $model = new TablaGeneralModel();
        $row   = $model->obtener($cfg['tabla'], $id, $cfg['tatipo'], $cfg['campos']);
        if ($row === null) {
            $this->json(['ok' => false, 'error' => 'Registro no encontrado.'], 404);
        }

        $this->json(['ok' => true, 'registro' => $row]);
    }

    public function guardar(): void
    {
        $this->requireAuth();
        $this->requireCsrf();

        $ref = $this->resolverRefORedirigir(true);
        $cfg = self::CONFIG_TABLAS[$ref];
        $id  = (int) ($_POST['id'] ?? 0);

        $esAdmin = ($this->currentUserId() === self::USER_ID_ADMIN);
        if ($id > 0 && !$esAdmin) {
            $this->json(['ok' => false, 'error' => 'Sin permiso para editar.'], 403);
        }
        if ($id <= 0 && !$esAdmin) {
            $this->json(['ok' => false, 'error' => 'Sin permiso para agregar.'], 403);
        }

        $datos = $this->datosDesdePost($cfg['campos']);

        if (in_array('tacodigo', $cfg['campos'], true) && trim((string) ($datos['tacodigo'] ?? '')) === '') {
            $this->json(['ok' => false, 'error' => 'El código es obligatorio.'], 422);
        }
        if (in_array('tadescrip', $cfg['campos'], true) && trim((string) ($datos['tadescrip'] ?? '')) === '') {
            $this->json(['ok' => false, 'error' => 'La descripción es obligatoria.'], 422);
        }

        $model  = new TablaGeneralModel();
        $tabla  = $cfg['tabla'];
        $tatipo = $cfg['tatipo'];

        try {
            if (in_array('tacodigo', $cfg['campos'], true)) {
                $codigo = (string) ($datos['tacodigo'] ?? '');
                if ($model->existeCodigo($tabla, $tatipo, $codigo, $id)) {
                    $this->json(['ok' => false, 'error' => 'Ya existe un registro con ese código.'], 422);
                }
            }

            if ($id > 0) {
                $existente = $model->obtener($tabla, $id, $tatipo, $cfg['campos']);
                if ($existente === null) {
                    $this->json(['ok' => false, 'error' => 'Registro no encontrado.'], 404);
                }
                $model->actualizar($tabla, $id, $datos);
                $this->json(['ok' => true, 'mensaje' => 'Registro actualizado.', 'id' => $id]);
            }

            $nuevoId = $model->insertar($tabla, $datos, $tatipo);
            $this->json(['ok' => true, 'mensaje' => 'Registro creado.', 'id' => $nuevoId]);
        } catch (PDOException $e) {
            error_log('TablaGeneralController::guardar — ' . $e->getMessage());
            $this->json(['ok' => false, 'error' => 'No se pudo guardar el registro.'], 500);
        } catch (InvalidArgumentException $e) {
            $this->json(['ok' => false, 'error' => $e->getMessage()], 422);
        }
    }

    public function eliminar(): void
    {
        $this->requireAuth();
        $this->requireCsrf();

        $ref = $this->resolverRefORedirigir(true);
        $cfg = self::CONFIG_TABLAS[$ref];
        $id  = (int) ($_POST['id'] ?? $_GET['id'] ?? 0);

        if ($this->currentUserId() !== self::USER_ID_ADMIN) {
            $this->json(['ok' => false, 'error' => 'Sin permiso para borrar.'], 403);
        }
        if ($id <= 0) {
            $this->json(['ok' => false, 'error' => 'Identificador inválido.'], 400);
        }

        $model = new TablaGeneralModel();
        try {
            $ok = $model->eliminar($cfg['tabla'], $id, $cfg['tatipo']);
            if (!$ok) {
                $this->json(['ok' => false, 'error' => 'No se encontró el registro o ya fue eliminado.'], 404);
            }
            $this->json(['ok' => true, 'mensaje' => 'Registro eliminado.']);
        } catch (PDOException $e) {
            error_log('TablaGeneralController::eliminar — ' . $e->getMessage());
            $this->json(['ok' => false, 'error' => 'No se pudo eliminar el registro.'], 500);
        }
    }

    /**
     * Ítems del submenú Archivos → Tablas Generales.
     *
     * @return array<int, array{ref: string, titulo: string, clave: string, ruta: string}>
     */
    public static function menuItems(): array
    {
        $out = [];
        foreach (self::CONFIG_TABLAS as $ref => $cfg) {
            $out[] = [
                'ref'    => $ref,
                'titulo' => $cfg['titulo'],
                'clave'  => isset(self::REF_A_CLAVE[$ref]) ? self::REF_A_CLAVE[$ref] : ('MNU_ARC_TAB_' . $ref),
                'ruta'   => 'tablas-generales&ref=' . $ref,
            ];
        }

        // ABM propio (no usa la tabla maestra `tablas`)
        $out[] = [
            'ref'    => 'CABECERA_MAILS',
            'titulo' => 'Cabecera Mails',
            'clave'  => 'MNU_ARC_TAB_CABECERA_MAILS',
            'ruta'   => 'plantillas-emails',
        ];

        return $out;
    }

    /** @return string[] */
    public static function clavesMenu(): array
    {
        return array_values(array_unique(array_values(self::REF_A_CLAVE)));
    }

    public static function configTablas(): array
    {
        return self::CONFIG_TABLAS;
    }

    private function resolverRefORedirigir(bool $ajax = false, bool $permitirVacio = false): string
    {
        $ref = strtoupper(trim((string) ($_GET['ref'] ?? $_POST['ref'] ?? $_GET['tipo'] ?? $_POST['tipo'] ?? '')));

        if ($ref === '' || !isset(self::CONFIG_TABLAS[$ref])) {
            $route = trim((string) ($_GET['route'] ?? ''));
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
                $this->json(['ok' => false, 'error' => 'Debe indicar un módulo válido (?ref=).'], 400);
            }
            if ($permitirVacio) {
                return '';
            }
            header('Location: ' . $this->baseUrl() . '?route=tablas-generales');
            exit;
        }

        return $ref;
    }

    /**
     * Portada: listado de todos los ABM de Tablas Generales.
     */
    private function hub(): void
    {
        $userId = $this->currentUserId();
        if (empty($_SESSION['user_id'])) {
            $_SESSION['user_id'] = $userId;
        }
        $esAdmin      = ($userId === self::USER_ID_ADMIN);
        $puedeAgregar = $esAdmin;
        $puedeEditar  = $esAdmin;
        $puedeBorrar  = $esAdmin;
        $modulos      = self::menuItems();
        $pageTitle    = 'COMEDICA — Tablas Generales';
        $breadcrumb   = [
            ['label' => 'Archivos'],
            ['label' => 'Tablas Generales'],
        ];

        require_once __DIR__ . '/../views/tablas_generales_hub.php';
    }

    /**
     * @param  string[] $campos
     * @return array<string, mixed>
     */
    private function datosDesdePost(array $campos): array
    {
        $checks = ['vista', 'taselecc', 'isonco', 'isvih'];
        $datos  = [];
        foreach ($campos as $col) {
            if (in_array($col, $checks, true)) {
                $raw = $_POST[$col] ?? '0';
                $datos[$col] = ($raw === '1' || $raw === 1 || $raw === 'on' || $raw === true) ? 1 : 0;
                continue;
            }
            $datos[$col] = $_POST[$col] ?? '';
        }
        return $datos;
    }

    /** @param array<string, scalar> $params */
    private function buildQueryUrl(array $params): string
    {
        $params = array_merge(['route' => 'tablas-generales'], $params);
        foreach ($params as $k => $v) {
            if ($v === '' || $v === null) {
                unset($params[$k]);
            }
        }
        return 'index.php?' . http_build_query($params);
    }

    private function currentUserId(): int
    {
        $userId = (int) (
            $_SESSION['user_id']
            ?? $_SESSION['user']['id']
            ?? $_SESSION['user']['ID']
            ?? 0
        );
        if ($userId <= 0) {
            $userId = self::USER_ID_ADMIN;
        }
        if (empty($_SESSION['user_id'])) {
            $_SESSION['user_id'] = $userId;
        }
        return $userId;
    }

    private function requireAuth(): void
    {
        if (empty($_SESSION['user'])) {
            header('Location: ' . $this->baseUrl() . '?route=login');
            exit;
        }
    }

    private function requireCsrf(): void
    {
        $token = (string) ($_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        $esper = (string) ($_SESSION['csrf_token'] ?? '');
        if ($esper === '' || $token === '' || !hash_equals($esper, $token)) {
            $this->json(['ok' => false, 'error' => 'Token de seguridad inválido. Recargue la página.'], 403);
        }
    }

    /** @param array<string, mixed> $payload */
    private function json(array $payload, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function baseUrl(): string
    {
        if (function_exists('getBaseUrl')) {
            return getBaseUrl();
        }
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $script = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
        return "{$scheme}://{$host}{$script}/index.php";
    }
}
