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
     * Borra prácticas del nomenclador por rango de código (inclusive)
     * y, si se confirma, las prácticas asociadas en prestadores y sucursales.
     *
     * Tablas: nomencla.tacodigo, pracespe.pepractica, praespsu.pepractica.
     * Auditoría: auditoria_log (y eventos, si esa tabla existe en la misma BD).
     *
     * @return array{
     *   nomencla:int,
     *   pracespe:int,
     *   praespsu:int
     * }
     */
    public function borrarPorRango(
        string $desde,
        string $hasta,
        bool $confirmarPrestadores,
        int $userId,
        string $usuario
    ): array {
        $desde = $this->normalizarCodigoRango($desde);
        $hasta = $this->normalizarCodigoRango($hasta);

        if ($desde === '' || $hasta === '') {
            throw new InvalidArgumentException('Los campos Desde y Hasta son obligatorios.');
        }
        if (strcmp($desde, $hasta) > 0) {
            throw new InvalidArgumentException('El código Desde no puede ser mayor que el código Hasta.');
        }

        $detalle = 'tacodigo desde=' . $desde . ' tacodigo hasta=' . $hasta . ' XBJ_NOMENCLA';
        $params  = [':desde' => $desde, ':hasta' => $hasta];
        $where   = 'TRIM(tacodigo) >= :desde AND TRIM(tacodigo) <= :hasta';
        $wherePe = 'TRIM(pepractica) >= :desde AND TRIM(pepractica) <= :hasta';

        $this->db->beginTransaction();
        try {
            $stmtNom = $this->db->prepare("DELETE FROM nomencla WHERE {$where}");
            $stmtNom->execute($params);
            $nNomencla = $stmtNom->rowCount();
            $this->registrarEvento($userId, $usuario, $desde, $hasta, 'XBJ_NOMENCLA', $detalle);

            $nPracespe = 0;
            $nPraespsu = 0;
            if ($confirmarPrestadores) {
                $stmtPe = $this->db->prepare("DELETE FROM pracespe WHERE {$wherePe}");
                $stmtPe->execute($params);
                $nPracespe = $stmtPe->rowCount();
                $this->registrarEvento(
                    $userId,
                    $usuario,
                    $desde,
                    $hasta,
                    'NOMENCLA/PRACESPE',
                    $detalle . ' NOMENCLA/PRACESPE'
                );

                $stmtSu = $this->db->prepare("DELETE FROM praespsu WHERE {$wherePe}");
                $stmtSu->execute($params);
                $nPraespsu = $stmtSu->rowCount();
                $this->registrarEvento(
                    $userId,
                    $usuario,
                    $desde,
                    $hasta,
                    'NOMENCLA/PRAESPSU',
                    $detalle . ' NOMENCLA/PRAESPSU'
                );
            }

            $this->db->commit();

            return [
                'nomencla' => $nNomencla,
                'pracespe' => $nPracespe,
                'praespsu' => $nPraespsu,
            ];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Deja el código comparable con tacodigo / pepractica (char, sin espacios).
     */
    private function normalizarCodigoRango(string $codigo): string
    {
        $codigo = trim($codigo);
        $codigo = preg_replace('/\s+/', '', $codigo) ?? '';
        if (strlen($codigo) > 8) {
            throw new InvalidArgumentException('El código no puede superar 8 caracteres.');
        }
        return $codigo;
    }

    /**
     * Inserta la auditoría dentro de la transacción abierta.
     * auditoria_log es la tabla de la BD vfpmedicos. Si existe `eventos`,
     * también se registra ahí (descripcion corta + detalle en extra).
     */
    private function registrarEvento(
        int $userId,
        string $usuario,
        string $desde,
        string $hasta,
        string $modulo,
        string $detalle
    ): void {
        $stmt = $this->db->prepare(
            "INSERT INTO auditoria_log
                (codigo_prestador, usuario_email, fecha_hora, modulo, accion_detalle)
             VALUES
                (:codigo, :usuario, NOW(), :modulo, :detalle)"
        );
        $stmt->execute([
            ':codigo'   => substr($desde . '..' . $hasta, 0, 50),
            ':usuario'  => substr($usuario, 0, 255),
            ':modulo'   => substr($modulo, 0, 100),
            ':detalle'  => substr($detalle, 0, 255),
        ]);

        if (!$this->existeTabla('eventos')) {
            return;
        }

        $stmtEv = $this->db->prepare(
            "INSERT INTO eventos (tipo, descripcion, id_usuario, borrado, extra)
             VALUES (:tipo, :descripcion, :id_usuario, 0, :extra)"
        );
        $stmtEv->execute([
            ':tipo'        => substr($modulo, 0, 50),
            ':descripcion' => substr($modulo, 0, 50),
            ':id_usuario'  => max(0, $userId),
            ':extra'       => json_encode([
                'detalle' => $detalle,
                'desde'   => $desde,
                'hasta'   => $hasta,
                'usuario' => $usuario,
            ], JSON_UNESCAPED_UNICODE),
        ]);
    }

    private function existeTabla(string $tabla): bool
    {
        static $cache = [];
        $key = strtolower($tabla);
        if (array_key_exists($key, $cache)) {
            return $cache[$key];
        }

        $stmt = $this->db->prepare(
            "SELECT COUNT(*)
             FROM information_schema.tables
             WHERE table_schema = DATABASE()
               AND table_name = :tabla"
        );
        $stmt->execute([':tabla' => $tabla]);
        $cache[$key] = (int) $stmt->fetchColumn() > 0;

        return $cache[$key];
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
