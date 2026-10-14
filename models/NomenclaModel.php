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
     * Opciones de los combos del formulario de nomenclador.
     * Intenta las vistas pedidas y, si no existen, las tablas maestras reales.
     *
     * @return array<string, array<int, array{codigo:string, descripcion:string}>>
     */
    public function getCatalogoCombos(): array
    {
        $nomenclada = $this->opcionesDistintas('nomenclada');
        if ($nomenclada === []) {
            $nomenclada = [
                ['codigo' => 'SI', 'descripcion' => 'SI'],
                ['codigo' => 'NO', 'descripcion' => 'NO'],
            ];
        }

        return [
            'tagrupo'    => $this->opcionesDeFuente(['v_grupownn', 'gruponn']),
            'complejida' => $this->opcionesDistintas('complejida'),
            'tatiponom'  => $this->opcionesDistintas('tatiponom'),
            'nomenclada' => $nomenclada,
            'subgrupo'   => $this->opcionesDeFuente(
                ['v_subgrupo', 'tablas'],
                'tablas',
                "WHERE tatipo = 'SUB'"
            ),
            'grupocar'   => $this->opcionesDeFuente(['v_grupocarti', 'grupocarti']),
            'gruposup'   => $this->opcionesDeFuente(['v_titulocartilla', 'gruposup']),
            'tipo650'    => $this->opcionesDeFuente(
                ['tablas', 'nomentp'],
                'tablas',
                "WHERE tatipo = '650'"
            ),
            'thomologo'  => $this->opcionesDeFuente(
                ['tablas'],
                'tablas',
                "WHERE tatipo = 'HOM'"
            ),
        ];
    }

    /**
     * Registro único del nomenclador, con alias de los campos del formulario.
     *
     * @return array<string, mixed>|null
     */
    public function obtenerPorCodigo(string $tacodigo): ?array
    {
        $tacodigo = $this->normalizarCodigo($tacodigo);
        if ($tacodigo === '') {
            return null;
        }

        $sql = "SELECT
                    TRIM(tacodigo)   AS tacodigo,
                    TRIM(tadescrip)  AS tadescrip,
                    taimporte,
                    TRIM(capitulo)   AS capitulo,
                    valnbu           AS tvalnbu,
                    TRIM(tagrupo)    AS tagrupo,
                    TRIM(complejida) AS complejida,
                    TRIM(tatiponom)  AS tatiponom,
                    TRIM(nomenclada) AS nomenclada,
                    TRIM(tatipo)     AS tatipocod,
                    TRIM(subgrupo)   AS subgrupo,
                    TRIM(grupocar)   AS grupocar,
                    TRIM(gruposup)   AS gruposup,
                    TRIM(tipo)       AS tipo650,
                    TRIM(nombrehomo) AS thomologo,
                    vermodulo,
                    chkdesdob        AS desdobla,
                    chknbu           AS isnbu
                FROM nomencla
                WHERE TRIM(tacodigo) = :codigo
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':codigo' => $tacodigo]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    /**
     * Indica si el código ya está cargado en nomencla.
     *
     * @param string $tacodigo
     * @return bool
     */
    public function existeCodigo($tacodigo)
    {
        $tacodigo = strtoupper(trim((string) $tacodigo));
        if ($tacodigo === '') {
            return false;
        }

        $sql = "SELECT COUNT(*) FROM nomencla WHERE TRIM(tacodigo) = :tacodigo";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(':tacodigo' => $tacodigo));

        return ((int) $stmt->fetchColumn() > 0);
    }

    /**
     * Alta o modificación de una práctica. El código existente se actualiza;
     * un código nuevo se inserta.
     *
     * @param array<string, mixed> $datos
     */
    public function guardarNomenclador(array $datos, int $userId, string $usuario): string
    {
        $codigo = $this->normalizarCodigo((string) ($datos['tacodigo'] ?? ''));
        $nombre = trim((string) ($datos['tadescrip'] ?? ''));
        $grupocar = trim((string) ($datos['grupocar'] ?? ''));
        $titulocar = trim((string) ($datos['gruposup'] ?? ($datos['titulocar'] ?? '')));
        $tipo = trim((string) ($datos['tipo650'] ?? ''));

        if ($nombre === '') {
            throw new InvalidArgumentException(
                'Se detecto falta de nombre en el codigo ' . $codigo . "\nDebe revisar la misma"
            );
        }
        if ($grupocar !== '' && $titulocar === '') {
            throw new InvalidArgumentException(
                'Se detecto una Practica con Grupo Cartilla y no tiene Titulo Cartilla ' . $codigo . "\nDebe revisar la misma"
            );
        }
        if ($codigo === '') {
            throw new InvalidArgumentException("Se detecto falta de codigo \nDebe revisar la misma");
        }
        if ($tipo === '') {
            throw new InvalidArgumentException("Se detecto falta de Tipo Normativa 650  \nDebe revisar la misma");
        }

        $esNuevo = !empty($datos['es_nuevo']);
        $existe  = $this->obtenerPorCodigo($codigo) !== null;
        if ($esNuevo && ($existe || $this->existeCodigo($codigo))) {
            throw new InvalidArgumentException("El código '" . $codigo . "' ya existe en el Nomenclador. Por favor ingrese un código diferente.");
        }
        if (!$esNuevo && !$existe) {
            throw new InvalidArgumentException('La práctica no existe. No se puede modificar.');
        }

        $campos = [
            'tadescrip'  => $this->texto($this->mayusculas($datos['tadescrip'] ?? ''), 100),
            'taimporte'  => $this->decimal($datos['taimporte'] ?? 0),
            'capitulo'   => $this->texto($datos['capitulo'] ?? '', 2),
            'valnbu'     => $this->decimal($datos['tvalnbu'] ?? 0),
            'tagrupo'    => $this->texto($datos['tagrupo'] ?? '', 8),
            'complejida' => $this->texto($datos['complejida'] ?? '', 5),
            'tatiponom'  => $this->texto($datos['tatiponom'] ?? '', 10),
            'nomenclada' => $this->texto($datos['nomenclada'] ?? '', 2),
            'tatipo'     => 'PRA',
            'subgrupo'   => $this->texto($datos['subgrupo'] ?? '', 8),
            'grupocar'   => $this->texto($datos['grupocar'] ?? '', 8),
            'gruposup'   => $this->texto($datos['gruposup'] ?? ($datos['titulocar'] ?? ''), 9),
            'tipo'       => $this->texto($datos['tipo650'] ?? '', 20),
            'nombrehomo' => $this->texto($datos['thomologo'] ?? '', 80),
            'vermodulo'  => $this->flag($datos['vermodulo'] ?? ''),
            'chkdesdob'  => $this->flag($datos['desdobla'] ?? ''),
            'chknbu'     => $this->flag($datos['isnbu'] ?? ''),
        ];

        $usuarioCorto = substr(trim($usuario), 0, 10);
        $hora         = date('H:i:s');

        $this->db->beginTransaction();
        try {
            if ($existe) {
                $sets = [];
                $params = [':tacodigo' => $codigo];
                foreach ($campos as $col => $valor) {
                    $sets[] = "{$col} = :{$col}";
                    $params[":{$col}"] = $valor;
                }
                $sets[] = 'user = :user';
                $sets[] = 'fechaup = NOW()';
                $sets[] = 'timeup = :timeup';
                $params[':user'] = $usuarioCorto;
                $params[':timeup'] = $hora;

                $sql = 'UPDATE nomencla SET ' . implode(', ', $sets) . ' WHERE TRIM(tacodigo) = :tacodigo';
                $stmt = $this->db->prepare($sql);
                $stmt->execute($params);
                $modulo  = 'UPD_NOMENCLA';
                $detalle = 'Actualizacion nomenclador tacodigo=' . $codigo;
                $accion  = 'update';
            } else {
                $cols = ['tacodigo'];
                $vals = [':tacodigo'];
                $params = [':tacodigo' => $codigo];
                foreach ($campos as $col => $valor) {
                    $cols[] = $col;
                    $vals[] = ':' . $col;
                    $params[':' . $col] = $valor;
                }
                $cols[] = 'user';
                $cols[] = 'fechaup';
                $cols[] = 'timeup';
                $cols[] = 'feccreate';
                $vals[] = ':user';
                $vals[] = 'NOW()';
                $vals[] = ':timeup';
                $vals[] = 'NOW()';
                $params[':user'] = $usuarioCorto;
                $params[':timeup'] = $hora;

                $sql = 'INSERT INTO nomencla (' . implode(', ', $cols) . ') VALUES (' . implode(', ', $vals) . ')';
                $stmt = $this->db->prepare($sql);
                $stmt->execute($params);
                $modulo  = 'INS_NOMENCLA';
                $detalle = 'Alta nomenclador tacodigo=' . $codigo;
                $accion  = 'insert';
            }

            $this->registrarEvento($userId, $usuario, $codigo, $codigo, $modulo, $detalle);
            $this->db->commit();

            return $accion;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    private function normalizarCodigo(string $codigo): string
    {
        $codigo = trim($codigo);
        $codigo = preg_replace('/\s+/', '', $codigo) ?? '';
        $codigo = $this->mayusculas($codigo);
        if (strlen($codigo) > 6) {
            throw new InvalidArgumentException('El código no puede superar 6 caracteres.');
        }
        return $codigo;
    }

    /**
     * @param mixed $valor
     */
    private function mayusculas($valor): string
    {
        $texto = trim((string) $valor);
        if (function_exists('mb_strtoupper')) {
            return mb_strtoupper($texto, 'UTF-8');
        }
        return strtoupper($texto);
    }

    /**
     * @param mixed $valor
     */
    private function texto($valor, int $max): string
    {
        $texto = trim((string) $valor);
        if (strlen($texto) > $max) {
            $texto = substr($texto, 0, $max);
        }
        return $texto;
    }

    /**
     * @param mixed $valor
     */
    private function decimal($valor): string
    {
        $texto = str_replace(',', '.', trim((string) $valor));
        if ($texto === '' || !is_numeric($texto)) {
            return '0.00';
        }
        return number_format((float) $texto, 2, '.', '');
    }

    /**
     * @param mixed $valor
     */
    private function flag($valor): string
    {
        $texto = strtoupper(trim((string) $valor));
        return in_array($texto, ['1', 'S', 'SI', 'Y', 'TRUE', 'ON'], true) ? '1' : '0';
    }

    /**
     * @param string[] $candidatos
     * @return array<int, array{codigo:string, descripcion:string}>
     */
    private function opcionesDeFuente(array $candidatos, string $filtroSoloEn = '', string $where = ''): array
    {
        foreach ($candidatos as $tabla) {
            if (!$this->existeTabla($tabla)) {
                continue;
            }
            if ($where !== '' && $filtroSoloEn !== '' && $tabla !== $filtroSoloEn) {
                $whereSql = '';
            } else {
                $whereSql = $where;
            }
            try {
                $tablaSql = $this->tablaCatalogoSegura($tabla);
                $sql = "SELECT TRIM(tacodigo) AS codigo, TRIM(tadescrip) AS descripcion
                        FROM `{$tablaSql}`
                        {$whereSql}
                        ORDER BY tacodigo";
                $rows = $this->db->query($sql)->fetchAll();
                if ($rows) {
                    return $this->normalizarOpciones($rows);
                }
            } catch (PDOException $e) {
                error_log('NomenclaModel::opcionesDeFuente ' . $tabla . ' — ' . $e->getMessage());
            }
        }
        return [];
    }

    /**
     * @return array<int, array{codigo:string, descripcion:string}>
     */
    private function opcionesDistintas(string $columna): array
    {
        $permitidas = ['complejida', 'tatiponom', 'nomenclada', 'tatipo'];
        if (!in_array($columna, $permitidas, true)) {
            return [];
        }

        try {
            $sql = "SELECT DISTINCT TRIM(`{$columna}`) AS codigo
                    FROM nomencla
                    WHERE TRIM(COALESCE(`{$columna}`, '')) <> ''
                    ORDER BY codigo";
            $rows = $this->db->query($sql)->fetchAll();
        } catch (PDOException $e) {
            error_log('NomenclaModel::opcionesDistintas — ' . $e->getMessage());
            return [];
        }

        $out = [];
        foreach ($rows as $row) {
            $codigo = trim((string) ($row['codigo'] ?? ''));
            if ($codigo === '') {
                continue;
            }
            $out[] = ['codigo' => $codigo, 'descripcion' => $codigo];
        }
        return $out;
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array{codigo:string, descripcion:string}>
     */
    private function normalizarOpciones(array $rows): array
    {
        $out = [];
        foreach ($rows as $row) {
            $codigo = trim((string) ($row['codigo'] ?? ''));
            $descripcion = trim((string) ($row['descripcion'] ?? ''));
            if ($codigo === '' && $descripcion === '') {
                continue;
            }
            $out[] = [
                'codigo' => $codigo,
                'descripcion' => $descripcion !== '' ? $descripcion : $codigo,
            ];
        }
        return $out;
    }

    private function tablaCatalogoSegura(string $tabla): string
    {
        $permitidas = [
            'v_grupownn', 'v_subgrupo', 'v_grupocarti', 'v_titulocartilla',
            'gruponn', 'grupocarti', 'gruposup', 'nomentp', 'tablas',
        ];
        if (!in_array($tabla, $permitidas, true)) {
            throw new InvalidArgumentException('Tabla de catálogo no permitida.');
        }
        return $tabla;
    }

    /**
     * Copia descripción y grupo del nomenclador a sucursales y prestadores.
     * Equivale al SCAN/SEEK/REPLACE de praespsu y pracespe.
     * LEFT recorta al ancho real de cada columna, como el REPLACE de FoxPro.
     *
     * @return array{ok:bool, suc?:int, prac?:int, total?:int, error?:string}
     */
    public function actualizarDescripcionesYGrupos(): array
    {
        try {
            $this->db->beginTransaction();

            $sqlSu = "UPDATE praespsu p
                      INNER JOIN nomencla n ON TRIM(p.pepractica) = TRIM(n.tacodigo)
                      SET p.penombprac = LEFT(TRIM(n.tadescrip), 60),
                          p.pegrupo    = LEFT(TRIM(n.tagrupo), 8)";
            $stmtSu = $this->db->prepare($sqlSu);
            $stmtSu->execute();
            $filasSuc = $stmtSu->rowCount();

            $sqlPr = "UPDATE pracespe p
                      INNER JOIN nomencla n ON TRIM(p.pepractica) = TRIM(n.tacodigo)
                      SET p.penombprac = LEFT(TRIM(n.tadescrip), 80),
                          p.pegrupo    = LEFT(TRIM(n.tagrupo), 8)";
            $stmtPr = $this->db->prepare($sqlPr);
            $stmtPr->execute();
            $filasPrac = $stmtPr->rowCount();

            $this->db->commit();

            return array(
                'ok'    => true,
                'suc'   => $filasSuc,
                'prac'  => $filasPrac,
                'total' => $filasSuc + $filasPrac,
            );
        } catch (PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('NomenclaModel::actualizarDescripcionesYGrupos — ' . $e->getMessage());
            return array('ok' => false, 'error' => $e->getMessage());
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
