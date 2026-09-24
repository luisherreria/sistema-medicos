<?php
/**
 * models/NomenclaModel.php
 * Acceso a datos del Nomenclador Nacional (tabla `nomencla`).
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

class NomenclaModel
{
    private PDO $db;

    /** Columnas permitidas para ORDER BY (whitelist anti-injection). */
    private const COLS_ORDEN = [
        'tacodigo', 'tatiponom', 'tadescrip', 'tagrupo', 'taimporte',
        'capitulo', 'nomenclada', 'subgrupo', 'tasauso', 'complejida',
        'grupocar', 'gruposup', 'vermodulo',
    ];

    /** Campos de texto usados en búsqueda LIKE. */
    private const COLS_BUSQUEDA = [
        'tacodigo', 'tatiponom', 'tadescrip', 'tagrupo', 'capitulo',
        'nomenclada', 'subgrupo', 'tasauso', 'complejida', 'grupocar', 'gruposup',
    ];

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Listado paginado del nomenclador.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listarNomenclador(
        string $busqueda = '',
        int $pagina = 1,
        int $limite = 50,
        string $ordenCol = 'tacodigo',
        string $ordenDir = 'ASC'
    ): array {
        $pagina = max(1, $pagina);
        $limite = max(1, min(500, $limite));
        $offset = ($pagina - 1) * $limite;

        $ordenCol = in_array(strtolower($ordenCol), self::COLS_ORDEN, true)
            ? strtolower($ordenCol)
            : 'tacodigo';
        $ordenDir = strtoupper($ordenDir) === 'DESC' ? 'DESC' : 'ASC';

        [$where, $params] = $this->buildWhereBusqueda($busqueda);

        $sql = "SELECT
                    tacodigo,
                    tatiponom,
                    tadescrip,
                    tagrupo,
                    taimporte,
                    capitulo,
                    nomenclada,
                    subgrupo,
                    tasauso,
                    complejida,
                    grupocar,
                    gruposup,
                    vermodulo
                FROM nomencla
                {$where}
                ORDER BY {$ordenCol} {$ordenDir}
                LIMIT :limite OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val, PDO::PARAM_STR);
        }
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Total de registros (con el mismo filtro de búsqueda).
     */
    public function contarNomenclador(string $busqueda = ''): int
    {
        [$where, $params] = $this->buildWhereBusqueda($busqueda);

        $stmt = $this->db->prepare("SELECT COUNT(*) FROM nomencla {$where}");
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Valida si un usuario tiene un permiso activo por CLAVE
     * (ej: editar_nomenclador, borrar_nomenclador, MNU_ARC_NOMENCLADOR).
     *
     * Tablas: users_permissions + permisos
     */
    public function tienePermiso(int $userId, string $clave): bool
    {
        if ($userId <= 0 || $clave === '') {
            return false;
        }

        $sql = "SELECT COUNT(*)
                FROM users_permissions up
                INNER JOIN permisos p ON up.PERMISSION_ID = p.CLAVE
                WHERE up.USER_ID = :user_id
                  AND p.CLAVE    = :clave
                  AND up.ADDED   = 1
                  AND (up.REMOVED = 0 OR up.REMOVED IS NULL)";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':user_id' => $userId,
                ':clave'   => $clave,
            ]);
            return (int) $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log('NomenclaModel::tienePermiso() — ' . $e->getMessage());
            return false;
        }
    }

    /**
     * @return array{0: string, 1: array<string, string>}
     */
    private function buildWhereBusqueda(string $busqueda): array
    {
        $busqueda = trim($busqueda);
        if ($busqueda === '') {
            return ['', []];
        }

        $like   = '%' . $busqueda . '%';
        $parts  = [];
        $params = [];

        foreach (self::COLS_BUSQUEDA as $i => $col) {
            $ph = ':q' . $i;
            $parts[] = "CAST({$col} AS CHAR) LIKE {$ph}";
            $params[$ph] = $like;
        }

        $parts[] = 'CAST(taimporte AS CHAR) LIKE :q_imp';
        $params[':q_imp'] = $like;

        return ['WHERE (' . implode(' OR ', $parts) . ')', $params];
    }
}
