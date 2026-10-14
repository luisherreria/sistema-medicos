<?php
/**
 * controllers/NomenclaController.php
 * Módulo: Archivos → Nomenclador Nacional
 *
 * Acciones:
 *   index() → Listado principal (búsqueda, orden, paginación)
 *
 * Permisos sensibles (ejemplo):
 *   editar_nomenclador, borrar_nomenclador, agregar_nomenclador
 * Acceso al módulo: MNU_ARC_NOMENCLADOR
 */

declare(strict_types=1);

require_once __DIR__ . '/../models/NomenclaModel.php';

class NomenclaController
{
    private const LIMITE_DEFAULT = 50;

    private const COLS_SORT = [
        'tacodigo', 'tatiponom', 'tadescrip', 'tagrupo', 'taimporte',
        'capitulo', 'nomenclada', 'subgrupo', 'tasauso', 'complejida',
        'grupocar', 'gruposup', 'vermodulo',
    ];

    /**
     * Listado principal del Nomenclador Nacional.
     */
    public function index(): void
    {
        $this->requireAuth();
        $this->requirePermiso('MNU_ARC_NOMENCLADOR');

        $busqueda = trim((string) ($_GET['buscar'] ?? ''));
        $pagina   = max(1, (int) ($_GET['page'] ?? 1));
        $ordenCol = trim((string) ($_GET['sort'] ?? 'tacodigo'));
        $ordenDir = strtoupper(trim((string) ($_GET['dir'] ?? 'ASC')));
        $limite   = self::LIMITE_DEFAULT;

        if (!in_array(strtolower($ordenCol), self::COLS_SORT, true)) {
            $ordenCol = 'tacodigo';
        } else {
            $ordenCol = strtolower($ordenCol);
        }
        $ordenDir = $ordenDir === 'DESC' ? 'DESC' : 'ASC';

        $model = new NomenclaModel();

        // Usuario de sesión (asumido / real). El requerimiento usa ID 16 de ejemplo.
        $userId = (int) (
            $_SESSION['user_id']
            ?? $_SESSION['user']['id']
            ?? $_SESSION['user']['ID']
            ?? 16
        );
        if (empty($_SESSION['user_id'])) {
            $_SESSION['user_id'] = $userId;
        }

        // Permisos de botones de acción (consulta BD + fallback sesión)
        $puedeAgregar    = $this->checkPermisoAccion($model, $userId, 'agregar_nomenclador');
        $puedeEditar     = $this->checkPermisoAccion($model, $userId, 'editar_nomenclador');
        $puedeBorrar     = $this->checkPermisoAccion($model, $userId, 'borrar_nomenclador');
        $puedeActualizar = $puedeEditar
            || $this->checkPermisoAccion($model, $userId, 'actualizar_nomenclador');
        $puedeImportar   = $this->checkPermisoAccion($model, $userId, 'importar_nomenclador');

        // Si tiene el permiso de módulo, permitir ver; acciones siguen gated arriba.
        // En desarrollo: si no existen claves granulares, heredar del módulo.
        if (!$puedeAgregar && !$puedeEditar && !$puedeBorrar && !$puedeImportar) {
            $tieneModulo = $model->tienePermiso($userId, 'MNU_ARC_NOMENCLADOR')
                || $this->tienePermisoSesion('MNU_ARC_NOMENCLADOR');
            if ($tieneModulo) {
                $puedeAgregar = $puedeEditar = $puedeBorrar = $puedeActualizar = $puedeImportar = true;
            }
        }

        $dbError = null;
        try {
            $registros = $model->listarNomenclador($busqueda, $pagina, $limite, $ordenCol, $ordenDir);
            $total     = $model->contarNomenclador($busqueda);
        } catch (PDOException $e) {
            error_log('NomenclaController::index — ' . $e->getMessage());
            $registros = [];
            $total     = 0;
            $dbError   = $e->getMessage();
        }

        $totalPaginas = $total > 0 ? (int) ceil($total / $limite) : 1;
        if ($pagina > $totalPaginas) {
            $pagina = $totalPaginas;
            try {
                $registros = $model->listarNomenclador($busqueda, $pagina, $limite, $ordenCol, $ordenDir);
            } catch (PDOException $e) {
                error_log('NomenclaController::index reconsulta — ' . $e->getMessage());
                $registros = [];
            }
        }

        // Enlaces de ordenamiento: alternar ASC/DESC por columna
        $sortLinks = [];
        foreach (self::COLS_SORT as $col) {
            $nextDir = ($ordenCol === $col && $ordenDir === 'ASC') ? 'DESC' : 'ASC';
            $sortLinks[$col] = $this->buildQueryUrl([
                'buscar' => $busqueda,
                'page'   => 1,
                'sort'   => $col,
                'dir'    => $nextDir,
            ]);
        }

        $queryBase = [
            'buscar' => $busqueda,
            'sort'   => $ordenCol,
            'dir'    => $ordenDir,
        ];

        $pageTitle  = 'COMEDICA — Nomenclador Nacional';
        $breadcrumb = [
            ['label' => 'Archivos'],
            ['label' => 'Nomenclador Nacional'],
        ];

        require_once __DIR__ . '/../views/nomencla_list.php';
    }

    /**
     * POST AJAX ?route=nomenclador&action=borrar_rango
     * Campos: desde, hasta, confirmar_prestadores, csrf_token
     */
    public function borrarPorRango(): void
    {
        $this->requireAuthJson();
        header('Content-Type: application/json; charset=utf-8');

        if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            http_response_code(405);
            echo json_encode(['ok' => false, 'msg' => 'Método no permitido.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $token = (string) ($_POST['csrf_token'] ?? '');
        $esper = (string) ($_SESSION['csrf_token'] ?? '');
        if ($esper === '' || !hash_equals($esper, $token)) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'msg' => 'Token de seguridad inválido. Recargue la página.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $model  = new NomenclaModel();
        $userId = (int) ($_SESSION['user_id'] ?? $_SESSION['user']['id'] ?? 0);
        if (!$this->checkPermisoAccion($model, $userId, 'borrar_nomenclador')
            && !$model->tienePermiso($userId, 'MNU_ARC_NOMENCLADOR')
            && !$this->tienePermisoSesion('MNU_ARC_NOMENCLADOR')) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'msg' => 'Sin permiso para borrar prácticas del nomenclador.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $desde = trim((string) ($_POST['desde'] ?? ''));
        $hasta = trim((string) ($_POST['hasta'] ?? ''));
        if ($desde === '' || $hasta === '') {
            http_response_code(422);
            echo json_encode(['ok' => false, 'msg' => 'Los campos Desde y Hasta son obligatorios.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $confirmar = filter_var($_POST['confirmar_prestadores'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $usuario   = trim((string) (
            $_SESSION['user']['username']
            ?? $_SESSION['user']['full_name']
            ?? ('user-' . $userId)
        ));

        try {
            $model->borrarPorRango($desde, $hasta, $confirmar, $userId, $usuario);
            $msg = $confirmar
                ? 'Prácticas eliminadas correctamente del nomenclador, prestadores y sucursales.'
                : 'Prácticas eliminadas correctamente del nomenclador.';
            echo json_encode(['ok' => true, 'msg' => $msg], JSON_UNESCAPED_UNICODE);
        } catch (InvalidArgumentException $e) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'msg' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            error_log('NomenclaController::borrarPorRango — ' . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'ok'  => false,
                'msg' => 'No se pudo completar el borrado. La operación fue revertida.',
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    /**
     * GET/POST ?route=nomenclador&action=obtener_nomenclador&tacodigo=
     */
    public function obtenerNomenclador(): void
    {
        $this->requireAuthJson();
        header('Content-Type: application/json; charset=utf-8');
        $this->requirePermisoJson('MNU_ARC_NOMENCLADOR');

        $codigo = trim((string) ($_POST['tacodigo'] ?? $_GET['tacodigo'] ?? ''));
        if ($codigo === '') {
            http_response_code(422);
            echo json_encode(['ok' => false, 'msg' => 'Indique el código de la práctica.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        try {
            $model = new NomenclaModel();
            $row = $model->obtenerPorCodigo($codigo);
            if ($row === null) {
                http_response_code(404);
                echo json_encode(['ok' => false, 'msg' => 'No se encontró la práctica.'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            echo json_encode(['ok' => true, 'data' => $row], JSON_UNESCAPED_UNICODE);
        } catch (InvalidArgumentException $e) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'msg' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            error_log('NomenclaController::obtenerNomenclador — ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['ok' => false, 'msg' => 'No se pudo leer la práctica.'], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    /**
     * GET ?route=nomenclador&action=catalogo_nomenclador
     */
    public function catalogoNomenclador(): void
    {
        $this->requireAuthJson();
        header('Content-Type: application/json; charset=utf-8');
        $this->requirePermisoJson('MNU_ARC_NOMENCLADOR');

        try {
            $model = new NomenclaModel();
            echo json_encode([
                'ok'   => true,
                'data' => $model->getCatalogoCombos(),
            ], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            error_log('NomenclaController::catalogoNomenclador — ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['ok' => false, 'msg' => 'No se pudieron cargar los catálogos.'], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    /**
     * GET/POST ?route=nomenclador&action=validar_codigo&tacodigo=
     */
    public function validarCodigo(): void
    {
        $this->requireAuthJson();
        header('Content-Type: application/json; charset=utf-8');

        $tacodigo = trim((string) ($_GET['tacodigo'] ?? $_POST['tacodigo'] ?? ''));
        if ($tacodigo === '') {
            echo json_encode(array('existe' => false));
            exit;
        }

        try {
            $model = new NomenclaModel();
            $existe = $model->existeCodigo($tacodigo);
            echo json_encode(array(
                'existe'   => $existe,
                'tacodigo' => function_exists('mb_strtoupper')
                    ? mb_strtoupper($tacodigo, 'UTF-8')
                    : strtoupper($tacodigo),
            ));
        } catch (Throwable $e) {
            error_log('NomenclaController::validarCodigo — ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(array('existe' => false, 'msg' => 'No se pudo validar el código.'));
        }
        exit;
    }

    /**
     * POST ?route=nomenclador&action=guardar_nomenclador
     */
    public function guardarNomenclador(): void
    {
        $this->requireAuthJson();
        header('Content-Type: application/json; charset=utf-8');

        if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            http_response_code(405);
            echo json_encode(['ok' => false, 'msg' => 'Método no permitido.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $token = (string) ($_POST['csrf_token'] ?? '');
        $esper = (string) ($_SESSION['csrf_token'] ?? '');
        if ($esper === '' || !hash_equals($esper, $token)) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'msg' => 'Token de seguridad inválido. Recargue la página.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $model     = new NomenclaModel();
        $userId    = (int) ($_SESSION['user_id'] ?? $_SESSION['user']['id'] ?? 0);
        $accionFrm = trim((string) ($_POST['accion'] ?? ''));
        $esNuevo   = $accionFrm === 'nuevo' || (($_POST['modo'] ?? '') === 'alta');
        $clave   = $esNuevo ? 'agregar_nomenclador' : 'editar_nomenclador';
        if (!$this->puedeAccion($model, $userId, $clave)) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'msg' => 'Sin permiso para guardar la práctica.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $usuario = trim((string) (
            $_SESSION['user']['username']
            ?? $_SESSION['user']['full_name']
            ?? ('user-' . $userId)
        ));

        $datos = [
            'tacodigo'   => $_POST['tacodigo'] ?? '',
            'tadescrip'  => $_POST['tadescrip'] ?? '',
            'taimporte'  => $_POST['taimporte'] ?? '',
            'capitulo'   => $_POST['capitulo'] ?? '',
            'tvalnbu'    => $_POST['tvalnbu'] ?? '',
            'tagrupo'    => $_POST['tagrupo'] ?? '',
            'complejida' => $_POST['complejida'] ?? '',
            'tatiponom'  => $_POST['tatiponom'] ?? '',
            'nomenclada' => $_POST['nomenclada'] ?? '',
            'subgrupo'   => $_POST['subgrupo'] ?? '',
            'grupocar'   => $_POST['grupocar'] ?? '',
            'gruposup'   => $_POST['gruposup'] ?? ($_POST['titulocar'] ?? ''),
            'tipo650'    => $_POST['tipo650'] ?? '',
            'thomologo'  => $_POST['thomologo'] ?? '',
            'vermodulo'  => $_POST['vermodulo'] ?? '',
            'desdobla'   => $_POST['desdobla'] ?? '',
            'isnbu'      => $_POST['isnbu'] ?? '',
            'es_nuevo'   => $esNuevo,
        ];

        if ($esNuevo) {
            $codigoPost = trim((string) ($datos['tacodigo'] ?? ''));
            if ($codigoPost !== '' && $model->existeCodigo($codigoPost)) {
                $codigoMsg = function_exists('mb_strtoupper')
                    ? mb_strtoupper($codigoPost, 'UTF-8')
                    : strtoupper($codigoPost);
                http_response_code(422);
                echo json_encode(array(
                    'ok'  => false,
                    'msg' => "El código '" . $codigoMsg . "' ya existe en el Nomenclador. Por favor ingrese un código diferente.",
                ), JSON_UNESCAPED_UNICODE);
                exit;
            }
        }

        try {
            $accion = $model->guardarNomenclador($datos, $userId, $usuario);
            $msg = $accion === 'insert'
                ? 'Práctica registrada correctamente en el Nomenclador.'
                : 'Práctica del Nomenclador actualizada correctamente.';
            echo json_encode(['ok' => true, 'msg' => $msg], JSON_UNESCAPED_UNICODE);
        } catch (InvalidArgumentException $e) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'msg' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            error_log('NomenclaController::guardarNomenclador — ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['ok' => false, 'msg' => 'No se pudo guardar la práctica. La operación fue revertida.'], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    /**
     * POST ?route=nomenclador&action=actualizar_descripciones
     * Copia tadescrip y tagrupo de nomencla hacia praespsu y pracespe.
     */
    public function actualizarDescripciones(): void
    {
        $this->requireAuthJson();
        header('Content-Type: application/json; charset=utf-8');

        if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            http_response_code(405);
            echo json_encode(array('ok' => false, 'error' => 'Método no permitido.'), JSON_UNESCAPED_UNICODE);
            exit;
        }

        $token = (string) ($_POST['csrf_token'] ?? '');
        $esper = (string) ($_SESSION['csrf_token'] ?? '');
        if ($esper === '' || !hash_equals($esper, $token)) {
            http_response_code(403);
            echo json_encode(array('ok' => false, 'error' => 'Token de seguridad inválido. Recargue la página.'), JSON_UNESCAPED_UNICODE);
            exit;
        }

        $model  = new NomenclaModel();
        $userId = (int) ($_SESSION['user_id'] ?? $_SESSION['user']['id'] ?? 0);
        if (!$this->puedeAccion($model, $userId, 'actualizar_nomenclador')
            && !$this->puedeAccion($model, $userId, 'editar_nomenclador')
            && !$this->tienePermisoSesion('MNU_ARC_NOMENCLADOR')) {
            http_response_code(403);
            echo json_encode(array('ok' => false, 'error' => 'Sin permiso para actualizar descripciones.'), JSON_UNESCAPED_UNICODE);
            exit;
        }

        $res = $model->actualizarDescripcionesYGrupos();
        if (!empty($res['ok'])) {
            echo json_encode(array(
                'ok'  => true,
                'msg' => "Se actualizaron correctamente:\n- " . (int) $res['suc'] . " registros en Sucursales (praespsu).\n- " . (int) $res['prac'] . " registros en Prestadores (pracespe).",
            ), JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(array(
                'ok'    => false,
                'error' => 'Error al ejecutar la actualización: ' . (string) ($res['error'] ?? ''),
            ), JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    /**
     * Stub de acción sensible: editar / borrar (ejemplo de validación previa).
     * No ejecuta cambios; solo demuestra el gate de permisos.
     */
    public function accionSensibles(): void
    {
        $this->requireAuth();
        $this->requirePermiso('MNU_ARC_NOMENCLADOR');

        $accion = trim((string) ($_GET['accion'] ?? ''));
        $model  = new NomenclaModel();
        $userId = (int) ($_SESSION['user_id'] ?? 16);

        $mapa = [
            'borrar' => 'borrar_nomenclador',
            'editar' => 'editar_nomenclador',
        ];

        if (!isset($mapa[$accion])) {
            http_response_code(400);
            echo 'Acción no válida.';
            exit;
        }

        $clave = $mapa[$accion];
        if (!$model->tienePermiso($userId, $clave) && !$this->tienePermisoSesion($clave)) {
            // Fallback módulo (misma lógica que index)
            if (!$model->tienePermiso($userId, 'MNU_ARC_NOMENCLADOR')
                && !$this->tienePermisoSesion('MNU_ARC_NOMENCLADOR')) {
                http_response_code(403);
                echo 'Sin permiso para esta operación.';
                exit;
            }
        }

        // Aquí iría la lógica real de editar/borrar.
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'ok'      => true,
            'mensaje' => "Permiso '{$clave}' validado para usuario {$userId}.",
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Combina consulta en BD + permisos en sesión.
     */
    private function checkPermisoAccion(NomenclaModel $model, int $userId, string $clave): bool
    {
        return $model->tienePermiso($userId, $clave) || $this->tienePermisoSesion($clave);
    }

    private function tienePermisoSesion(string $clave): bool
    {
        foreach (($_SESSION['permisos'] ?? []) as $p) {
            if (isset($p['CLAVE']) && $p['CLAVE'] === $clave) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param array<string, scalar> $params
     */
    private function buildQueryUrl(array $params): string
    {
        $params = array_merge(['route' => 'nomenclador'], $params);
        // Quitar vacíos excepto page
        foreach ($params as $k => $v) {
            if ($v === '' || $v === null) {
                unset($params[$k]);
            }
        }
        return 'index.php?' . http_build_query($params);
    }

    private function requireAuth(): void
    {
        if (empty($_SESSION['user'])) {
            header('Location: ' . $this->baseUrl() . '?route=login');
            exit;
        }
    }

    private function puedeAccion(NomenclaModel $model, int $userId, string $clave): bool
    {
        return $this->checkPermisoAccion($model, $userId, $clave)
            || $model->tienePermiso($userId, 'MNU_ARC_NOMENCLADOR')
            || $this->tienePermisoSesion('MNU_ARC_NOMENCLADOR');
    }

    private function requirePermisoJson(string $clave): void
    {
        if ($this->tienePermisoSesion($clave)) {
            return;
        }
        http_response_code(403);
        echo json_encode(['ok' => false, 'msg' => 'Sin permiso para esta operación.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function requireAuthJson(): void
    {
        if (empty($_SESSION['user'])) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'msg' => 'Sesión expirada. Recargue la página.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    private function requirePermiso(string $clave): void
    {
        if ($this->tienePermisoSesion($clave)) {
            return;
        }
        $_SESSION['flash_warning'] = 'No tiene permiso para acceder al Nomenclador Nacional.';
        header('Location: ' . $this->baseUrl() . '?route=dashboard');
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
