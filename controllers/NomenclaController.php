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

        // Si tiene el permiso de módulo, permitir ver; acciones siguen gated arriba.
        // En desarrollo: si no existen claves granulares, heredar del módulo.
        if (!$puedeAgregar && !$puedeEditar && !$puedeBorrar) {
            $tieneModulo = $model->tienePermiso($userId, 'MNU_ARC_NOMENCLADOR')
                || $this->tienePermisoSesion('MNU_ARC_NOMENCLADOR');
            if ($tieneModulo) {
                $puedeAgregar = $puedeEditar = $puedeBorrar = $puedeActualizar = true;
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
