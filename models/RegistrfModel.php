<?php
/**
 * models/RegistrfModel.php
 * Acceso a datos de `registrf` (Registración de Facturas).
 *
 * La tabla proviene de VFP y el esquema real puede no incluir todos los
 * campos del formulario. El INSERT se arma dinámicamente con SHOW COLUMNS
 * para no volver a fallar por columnas inexistentes (COFECRECIB, etc.).
 */

require_once __DIR__ . '/../config/database.php';

class RegistrfModel
{
    private PDO $db;

    /** @var array<string,string>|null  UPPER => nombre real en MySQL */
    private ?array $columnasCache = null;

    private const COLS_SORT = [
        'COPERIODO', 'COOBRASOC', 'COPRESTADO', 'CONOMPREST',
        'COSUCFAC', 'CONROFAC', 'COFECFAC',
        'COTOTALFAC', 'COUSUARIO', 'COFECCARGA',
    ];

    public function __construct()
    {
        $this->db = getDBConnection();
    }

    // ══════════════════════════════════════════════════════════════════════
    //  COLUMNAS REALES
    // ══════════════════════════════════════════════════════════════════════

    /**
     * Columnas confirmadas en el listado original (existen en registrf).
     * El resto (COIVA, etc.) es opcional y se omite si no está.
     */
    private const COLS_CORE = [
        'COPERIODO', 'COOBRASOC', 'COCATEG', 'COPRESTADO', 'CONOMPREST',
        'COSUCFAC', 'CONROFAC', 'COFECFAC', 'COTOTALFAC', 'COUSUARIO', 'COFECCARGA',
    ];

    /** Nunca van al INSERT: no existen en registrf (error 1054). */
    private const COLS_NUNCA = [
        'COIMPORTE', 'IMPORTE',
    ];

    /**
     * @return array<string,string>  clave UPPER => nombre de columna tal cual en MySQL
     */
    public function columnas(): array
    {
        if ($this->columnasCache !== null) {
            return $this->columnasCache;
        }
        $this->columnasCache = [];
        try {
            foreach ($this->db->query('SHOW COLUMNS FROM registrf') as $row) {
                $real = (string)($row['Field'] ?? $row['field'] ?? $row['FIELD'] ?? '');
                if ($real !== '') {
                    $this->columnasCache[strtoupper($real)] = $real;
                }
            }
        } catch (PDOException $e) {
            error_log('RegistrfModel::columnas SHOW: ' . $e->getMessage());
        }
        if (!$this->columnasCache) {
            try {
                $stmt = $this->db->query(
                    "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
                     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'registrf'"
                );
                foreach ($stmt as $row) {
                    $real = (string)($row['COLUMN_NAME'] ?? $row['column_name'] ?? $row['COLUMN_NAME'] ?? '');
                    if ($real === '') {
                        $real = (string)reset($row);
                    }
                    if ($real !== '') {
                        $this->columnasCache[strtoupper($real)] = $real;
                    }
                }
            } catch (PDOException $e) {
                error_log('RegistrfModel::columnas INFO: ' . $e->getMessage());
            }
        }
        return $this->columnasCache;
    }

    public function tieneColumna(string $nombre): bool
    {
        return isset($this->columnas()[strtoupper($nombre)]);
    }

    public function col(string $nombre): string
    {
        $key = strtoupper($nombre);
        return $this->columnas()[$key] ?? $nombre;
    }

    // ══════════════════════════════════════════════════════════════════════
    //  LISTADO
    // ══════════════════════════════════════════════════════════════════════

    public function obtenerFacturas(
        string $busqueda  = '',
        int    $limit     = 50,
        int    $offset    = 0,
        string $orderCol  = 'COPERIODO',
        string $orderDir  = 'DESC',
        bool   $soloEliminados = false
    ): array {
        [$where, $params] = $this->buildWhere($busqueda, $soloEliminados);
        $orderExpr = $this->resolverOrderExpr($orderCol, $orderDir);

        $stmtCnt = $this->db->prepare("SELECT COUNT(*) FROM registrf{$where}");
        $stmtCnt->execute($params);
        $total = (int) $stmtCnt->fetchColumn();

        $fecCarga = $this->tieneColumna('COFECCARGA')
            ? "COALESCE(NULLIF({$this->col('COFECCARGA')}, '0000-00-00 00:00:00'),
                        NULLIF({$this->col('COFECCARGA')}, '0000-00-00'),
                        " . ($this->tieneColumna('COFECFAC') ? $this->col('COFECFAC') : 'NULL') . ")"
            : ($this->tieneColumna('COFECFAC') ? $this->col('COFECFAC') : 'NULL');

        $sql = "SELECT
                    " . ($this->tieneColumna('id') ? $this->col('id') . ' AS id,' : 'NULL AS id,') . "
                    TRIM({$this->col('COPERIODO')})  AS COPERIODO,
                    TRIM({$this->col('COOBRASOC')})  AS COOBRASOC,
                    TRIM({$this->col('COPRESTADO')}) AS COPRESTADO,
                    TRIM({$this->col('CONOMPREST')}) AS CONOMPREST,
                    TRIM({$this->col('COSUCFAC')})   AS COSUCFAC,
                    TRIM({$this->col('CONROFAC')})   AS CONROFAC,
                    " . ($this->tieneColumna('COFECFAC') ? $this->col('COFECFAC') : 'NULL') . " AS COFECFAC,
                    " . ($this->tieneColumna('COTOTALFAC') ? $this->col('COTOTALFAC') : 'NULL') . " AS COTOTALFAC,
                    TRIM({$this->col('COUSUARIO')})  AS COUSUARIO,
                    {$fecCarga} AS COFECCARGA,
                    " . ($this->tieneColumna('archivo_pdf')
                        ? "TRIM({$this->col('archivo_pdf')}) AS archivo_pdf"
                        : "NULL AS archivo_pdf") . "
                FROM registrf
                {$where}
                ORDER BY {$orderExpr}
                LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'total' => $total,
            'datos' => $stmt->fetchAll(PDO::FETCH_ASSOC),
        ];
    }

    // ══════════════════════════════════════════════════════════════════════
    //  INSERT dinámico
    // ══════════════════════════════════════════════════════════════════════

    /**
     * Inserta una factura usando únicamente columnas que existen en `registrf`.
     *
     * @param array<string,mixed> $campos  clave = nombre lógico UPPER (COPERIODO, …)
     */
    public function insertar(array $campos): void
    {
        $descubiertas = $this->columnas();

        $existentes = [];
        foreach ($campos as $logico => $valor) {
            $key = strtoupper((string)$logico);
            if (in_array($key, self::COLS_NUNCA, true)) {
                continue;
            }
            if ($descubiertas) {
                if (!isset($descubiertas[$key])) {
                    continue;
                }
                $existentes[$descubiertas[$key]] = $valor;
            } elseif (in_array($key, self::COLS_CORE, true)) {
                $existentes[$key] = $valor;
            }
        }

        if ($descubiertas && isset($descubiertas['COFECCARGA']) && !isset($existentes[$descubiertas['COFECCARGA']])) {
            $existentes[$descubiertas['COFECCARGA']] = date('Y-m-d H:i:s');
        }

        if (!$existentes) {
            throw new PDOException('No hay columnas coincidentes para insertar en registrf.');
        }

        $this->ejecutarInsert($existentes);
    }

    /** @param array<string,mixed> $existentes */
    private function ejecutarInsert(array $existentes): void
    {
        foreach (self::COLS_NUNCA as $nunca) {
            foreach (array_keys($existentes) as $col) {
                if (strtoupper((string)$col) === $nunca) {
                    unset($existentes[$col]);
                }
            }
        }
        for ($intento = 0; $intento < 20; $intento++) {
            if (!$existentes) {
                throw new PDOException('No quedan columnas válidas para insertar en registrf.');
            }
            $cols = array_keys($existentes);
            $phs  = [];
            $bind = [];
            $i    = 0;
            foreach ($existentes as $val) {
                $ph = ':p' . $i++;
                $phs[] = $ph;
                $bind[$ph] = $val;
            }
            $sql = 'INSERT INTO registrf (' . implode(', ', $cols) . ') VALUES (' . implode(', ', $phs) . ')';
            try {
                $this->db->prepare($sql)->execute($bind);
                return;
            } catch (PDOException $e) {
                if (!preg_match("/Unknown column '([^']+)'/i", $e->getMessage(), $m)) {
                    throw $e;
                }
                $mala = strtoupper($m[1]);
                foreach (array_keys($existentes) as $col) {
                    if (strtoupper((string)$col) === $mala) {
                        unset($existentes[$col]);
                    }
                }
                if (isset($this->columnasCache[$mala])) {
                    unset($this->columnasCache[$mala]);
                }
            }
        }
        throw new PDOException('No se pudo insertar la factura: columnas incompatibles.');
    }

    // ══════════════════════════════════════════════════════════════════════
    //  REPORTES (mismo patrón que VFP RPT_CONFLIF)
    // ══════════════════════════════════════════════════════════════════════

    /**
     * Facturas del período cruzadas con ebamp.
     *
     * VFP:
     *   INNER JOIN ebamp ON ALLTRIM(registrf.coprestado) = ALLTRIM(ebamp.matricula)
     *   WHERE <flag> AND coperiodo = período
     *   ORDER BY conomprest
     *
     * Se une también por codigo por si coprestado guarda el código interno.
     *
     * @param string $periodo  AAMM (ej: 2607)
     * @param string $tipo     'prioritario' | 'conflicto' | 'recibos'
     * @return array<int,array<string,mixed>>
     */
    public function reportePorFlag(string $periodo, string $tipo): array
    {
        $periodoNorm  = strtoupper(trim(str_replace(['/', '-'], '', $periodo)));
        $periodoSlash = strlen($periodoNorm) === 4
            ? substr($periodoNorm, 0, 2) . '/' . substr($periodoNorm, 2, 2)
            : $periodoNorm;
        $colPer = $this->col('COPERIODO');
        $fechaExpr = $this->tieneColumna('COFECHA')
            ? $this->col('COFECHA')
            : ($this->tieneColumna('COFECFAC') ? $this->col('COFECFAC') : 'NULL');
        $fecFacExpr = $this->tieneColumna('COFECFAC') ? $this->col('COFECFAC') : 'NULL';

        $ebamp = $this->columnasEbamp();
        $flags = [];
        if ($tipo === 'conflicto' || $tipo === 'prioritario') {
            if (isset($ebamp['conflicto'])) {
                $flags[] = "TRIM(IFNULL(e.conflicto,'')) NOT IN ('', '0', 'N', 'F', '.F.')";
            }
        }

        $flagSql = $flags ? 'AND (' . implode(' OR ', $flags) . ')' : '';

        $wherePeriodo = "(
            REPLACE(REPLACE(TRIM(r.{$colPer}), '/', ''), '-', '') = :pnorm
            OR TRIM(r.{$colPer}) = :pslash
            OR TRIM(r.{$colPer}) = :pnorm2
            OR RIGHT(REPLACE(REPLACE(TRIM(r.{$colPer}), '/', ''), '-', ''), 4) = :pnorm3
        )";

        $sql = "SELECT
                    TRIM(r.{$colPer})                  AS coperiodo,
                    {$fechaExpr}                       AS cofecha,
                    TRIM(r.{$this->col('COPRESTADO')}) AS coprestado,
                    TRIM(r.{$this->col('CONOMPREST')}) AS conomprest,
                    TRIM(r.{$this->col('COSUCFAC')})   AS cosucfac,
                    TRIM(r.{$this->col('CONROFAC')})   AS conrofac,
                    {$fecFacExpr}                      AS cofecfac,
                    TRIM(IFNULL(e.nombre,''))          AS nombre_ebamp
                FROM registrf r
                LEFT JOIN ebamp e
                    ON TRIM(r.{$this->col('COPRESTADO')}) = TRIM(e.matricula)
                    OR TRIM(r.{$this->col('COPRESTADO')}) = TRIM(e.codigo)
                WHERE {$wherePeriodo}
                  {$flagSql}
                ORDER BY r.{$this->col('CONOMPREST')} ASC";

        $bind = [
            ':pnorm'  => $periodoNorm,
            ':pslash' => $periodoSlash,
            ':pnorm2' => $periodoNorm,
            ':pnorm3' => $periodoNorm,
        ];

        $stmt = $this->db->prepare($sql);
        $stmt->execute($bind);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Si el filtro de flags no trajo nada, listar todas las facturas del período
        if (!$rows && $tipo !== 'recibos' && $flagSql !== '') {
            $stmt = $this->db->prepare(str_replace($flagSql, '', $sql));
            $stmt->execute($bind);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        return $rows;
    }

    /** @return array<string,true> */
    private function columnasEbamp(): array
    {
        static $cols = null;
        if ($cols !== null) {
            return $cols;
        }
        $cols = [];
        try {
            foreach ($this->db->query('SHOW COLUMNS FROM ebamp') as $row) {
                $name = (string)($row['Field'] ?? $row['field'] ?? $row['FIELD'] ?? '');
                if ($name !== '') {
                    $cols[strtolower($name)] = true;
                }
            }
        } catch (PDOException $e) {
            error_log('RegistrfModel::columnasEbamp: ' . $e->getMessage());
        }
        return $cols;
    }

    // ══════════════════════════════════════════════════════════════════════
    //  HELPERS
    // ══════════════════════════════════════════════════════════════════════

    /**
     * @return array{0:string,1:array<string,string>}
     */
    private function buildWhere(string $busqueda, bool $soloEliminados = false): array
    {
        $filtros = [];
        $params  = [];

        if ($this->tieneColumna('eliminado')) {
            $colEli = $this->col('eliminado');
            if ($soloEliminados) {
                $filtros[] = "{$colEli} = 1";
            } else {
                $filtros[] = "({$colEli} = 0 OR {$colEli} IS NULL)";
            }
        }

        if ($busqueda !== '') {
            $val = '%' . $busqueda . '%';
            $colPer = $this->col('COPERIODO');
            $or = "({$this->col('CONOMPREST')} LIKE :b1"
                . " OR {$this->col('CONROFAC')} LIKE :b2"
                . " OR {$this->col('COOBRASOC')} LIKE :b3"
                . " OR {$this->col('COPRESTADO')} LIKE :b4"
                . " OR {$colPer} LIKE :b5";
            $params = [':b1' => $val, ':b2' => $val, ':b3' => $val, ':b4' => $val, ':b5' => $val];

            $periodoNorm = $this->normalizarBusquedaPeriodo($busqueda);
            if ($periodoNorm !== '') {
                $or .= " OR REPLACE(REPLACE(REPLACE(TRIM({$colPer}), '/', ''), '-', ''), '.', '') = :bper";
                $params[':bper'] = $periodoNorm;
            }
            $or .= ')';
            $filtros[] = $or;
        }

        $where = $filtros ? (' WHERE ' . implode(' AND ', $filtros)) : '';
        return [$where, $params];
    }

    /**
     * Convierte "26/07", "26-07", "26.07" o "2607" a AAMM ("2607").
     * Si no parece un período, devuelve cadena vacía.
     */
    private function normalizarBusquedaPeriodo(string $q): string
    {
        $q = trim($q);
        if (preg_match('/^(\d{2})[\/\-\.\s]+(\d{1,2})$/', $q, $m)) {
            $mm = str_pad($m[2], 2, '0', STR_PAD_LEFT);
            if ((int) $mm >= 1 && (int) $mm <= 12) {
                return $m[1] . $mm;
            }
        }
        if (preg_match('/^\d{4}$/', $q)) {
            $mm = (int) substr($q, 2, 2);
            if ($mm >= 1 && $mm <= 12) {
                return $q;
            }
        }
        return '';
    }

    public function obtenerPorId(int $id): ?array
    {
        if ($id <= 0 || !$this->tieneColumna('id')) {
            return null;
        }
        $stmt = $this->db->prepare('SELECT * FROM registrf WHERE ' . $this->col('id') . ' = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row !== false ? $row : null;
    }

    public function setEliminado(int $id, int $valor): bool
    {
        if ($id <= 0 || !$this->tieneColumna('id') || !$this->tieneColumna('eliminado')) {
            return false;
        }
        $stmt = $this->db->prepare(
            'UPDATE registrf SET ' . $this->col('eliminado') . ' = :eli WHERE ' . $this->col('id') . ' = :id'
        );
        $stmt->execute([
            ':eli' => $valor ? 1 : 0,
            ':id'  => $id,
        ]);
        return $stmt->rowCount() > 0;
    }

    /**
     * @param array<string,mixed> $campos
     */
    public function actualizar(int $id, array $campos): bool
    {
        if ($id <= 0 || !$this->tieneColumna('id')) {
            return false;
        }
        $descubiertas = $this->columnas();
        $sets = [];
        $bind = [':id' => $id];
        $i = 0;
        foreach ($campos as $logico => $valor) {
            $key = strtoupper((string) $logico);
            if ($key === 'ID' || $key === 'ELIMINADO' || in_array($key, self::COLS_NUNCA, true)) {
                continue;
            }
            if (!isset($descubiertas[$key])) {
                continue;
            }
            $ph = ':u' . $i++;
            $sets[] = $descubiertas[$key] . ' = ' . $ph;
            $bind[$ph] = $valor;
        }
        if (!$sets) {
            return false;
        }
        $sql = 'UPDATE registrf SET ' . implode(', ', $sets)
             . ' WHERE ' . $this->col('id') . ' = :id';
        $this->db->prepare($sql)->execute($bind);
        return true;
    }

    private function resolverOrderExpr(string $col, string $dir): string
    {
        $col = in_array(strtoupper($col), self::COLS_SORT, true) ? strtoupper($col) : 'COPERIODO';
        $dir = strtoupper($dir) === 'ASC' ? 'ASC' : 'DESC';

        if ($col === 'COFECCARGA' && $this->tieneColumna('COFECCARGA')) {
            $primary = "COALESCE(NULLIF({$this->col('COFECCARGA')},'0000-00-00 00:00:00'),"
                     . ($this->tieneColumna('COFECFAC') ? $this->col('COFECFAC') : 'NULL')
                     . ") {$dir}";
        } else {
            $primary = $this->tieneColumna($col)
                ? "{$this->col($col)} {$dir}"
                : "{$this->col('COPERIODO')} {$dir}";
        }

        $secondary = [];
        if ($col !== 'COPERIODO'  && $this->tieneColumna('COPERIODO'))  $secondary[] = $this->col('COPERIODO')  . ' DESC';
        if ($col !== 'CONOMPREST' && $this->tieneColumna('CONOMPREST')) $secondary[] = $this->col('CONOMPREST') . ' ASC';
        if ($col !== 'COOBRASOC'  && $this->tieneColumna('COOBRASOC'))  $secondary[] = $this->col('COOBRASOC')  . ' ASC';

        return implode(', ', array_merge([$primary], $secondary));
    }

    // ══════════════════════════════════════════════════════════════════════
    //  LISTADOS (resu1-1 / resu1-2)
    // ══════════════════════════════════════════════════════════════════════

    /**
     * Convierte "26/07" o "2607" a AAMM. Vacío si no es período válido.
     */
    public function normalizarPeriodo(string $q): string
    {
        return $this->normalizarBusquedaPeriodo($q);
    }

    /** Expresión SQL que deja COPERIODO como AAMM (2607) aunque venga 26/07. */
    private function exprPeriodoAamm(string $col): string
    {
        return "LPAD(REPLACE(REPLACE(REPLACE(TRIM({$col}), '/', ''), '-', ''), '.', ''), 4, '0')";
    }

    /**
     * @return array<int,string>
     */
    public function listarTiposPrestador(): array
    {
        $out = [];
        $ebamp = $this->columnasEbamp();
        if (!isset($ebamp['tipo'])) {
            return $out;
        }
        try {
            $stmt = $this->db->query(
                "SELECT DISTINCT TRIM(tipo) AS tipo
                 FROM ebamp
                 WHERE TRIM(IFNULL(tipo,'')) <> ''
                 ORDER BY tipo ASC"
            );
            foreach ($stmt as $row) {
                $t = trim((string) ($row['tipo'] ?? ''));
                if ($t !== '' && !in_array($t, $out, true)) {
                    $out[] = $t;
                }
            }
        } catch (PDOException $e) {
            error_log('listarTiposPrestador: ' . $e->getMessage());
        }
        return $out;
    }

    /**
     * Detallado x Obra Social (resu1-1).
     *
     * @param array{desde:string,hasta:string,os?:string,prestador?:string,tipo?:string} $f
     * @return array<int,array<string,mixed>>
     */
    public function reporteDetalladoOs(array $f): array
    {
        $desde = $this->normalizarPeriodo((string) ($f['desde'] ?? ''));
        $hasta = $this->normalizarPeriodo((string) ($f['hasta'] ?? ''));
        if ($desde === '' || $hasta === '') {
            return [];
        }
        if ($desde > $hasta) {
            $tmp = $desde;
            $desde = $hasta;
            $hasta = $tmp;
        }

        $colPer = $this->col('COPERIODO');
        $perExpr = $this->exprPeriodoAamm('r.' . $colPer);
        $where = ["{$perExpr} BETWEEN :desde AND :hasta"];
        $bind = [':desde' => $desde, ':hasta' => $hasta];

        if ($this->tieneColumna('eliminado')) {
            $colEli = $this->col('eliminado');
            $where[] = "(r.{$colEli} = 0 OR r.{$colEli} IS NULL)";
        }

        $os = strtoupper(trim((string) ($f['os'] ?? '')));
        if ($os !== '') {
            $where[] = "TRIM(r.{$this->col('COOBRASOC')}) = :os";
            $bind[':os'] = $os;
        }

        $prest = trim((string) ($f['prestador'] ?? ''));
        if ($prest !== '') {
            $where[] = "(TRIM(r.{$this->col('COPRESTADO')}) = :pr
                OR TRIM(CAST(e.codigo AS CHAR)) = :pr2
                OR TRIM(CAST(e.matricula AS CHAR)) = :pr3)";
            $bind[':pr'] = $prest;
            $bind[':pr2'] = $prest;
            $bind[':pr3'] = $prest;
        }

        $tipo = trim((string) ($f['tipo'] ?? ''));
        $tipoReg = $this->tieneColumna('COTIPOPRE')
            ? 'TRIM(r.' . $this->col('COTIPOPRE') . ')'
            : "''";
        $tipoExpr = "TRIM(IFNULL(NULLIF({$tipoReg}, ''), IFNULL(e.tipo, '')))";
        if ($tipo !== '') {
            if (strtoupper($tipo) === 'CABECERA') {
                $where[] = "(UPPER(LEFT({$tipoExpr}, 3)) = 'MED' OR UPPER({$tipoExpr}) = 'CABECERA')";
            } else {
                $where[] = "({$tipoExpr} = :tipo OR TRIM(IFNULL(e.tipo,'')) = :tipo2)";
                $bind[':tipo'] = $tipo;
                $bind[':tipo2'] = $tipo;
            }
        }

        $fecFac = $this->tieneColumna('COFECFAC') ? 'r.' . $this->col('COFECFAC') : 'NULL';
        $imp = $this->tieneColumna('COIMPFAC') ? 'r.' . $this->col('COIMPFAC') : '0';
        $iva = $this->tieneColumna('COIVAFAC') ? 'r.' . $this->col('COIVAFAC') : '0';
        $csg = $this->tieneColumna('COCSGFAC') ? 'r.' . $this->col('COCSGFAC') : '0';
        $tot = $this->tieneColumna('COTOTALFAC') ? 'r.' . $this->col('COTOTALFAC') : '0';
        $cant = $this->tieneColumna('COCANTIDAD') ? 'r.' . $this->col('COCANTIDAD') : '0';
        $factura = $this->tieneColumna('COFACTURA') ? 'TRIM(r.' . $this->col('COFACTURA') . ')' : "''";
        $nomObra = $this->tieneColumna('CONOMOBRA') ? 'TRIM(r.' . $this->col('CONOMOBRA') . ')' : "''";
        $leyenda = $this->tieneColumna('COLEYENDA') ? 'TRIM(r.' . $this->col('COLEYENDA') . ')' : "''";
        $ebamp = $this->columnasEbamp();
        $subSel = isset($ebamp['subcateg']) ? 'TRIM(IFNULL(e.subcateg,\'\'))' : "''";
        $zonaSel = isset($ebamp['zona']) ? 'TRIM(IFNULL(e.zona,\'\'))' : "''";
        $nomEbamp = isset($ebamp['nombre']) ? 'TRIM(IFNULL(e.nombre,\'\'))' : "''";
        $tipoEbamp = isset($ebamp['tipo']) ? 'TRIM(IFNULL(e.tipo,\'\'))' : "''";

        $sql = "SELECT
                    {$fecFac} AS cofecfac,
                    TRIM(r.{$this->col('COSUCFAC')}) AS cosucfac,
                    TRIM(r.{$this->col('CONROFAC')}) AS conrofac,
                    TRIM(r.{$colPer}) AS coperiodo,
                    TRIM(r.{$this->col('COOBRASOC')}) AS coobrasoc,
                    {$nomObra} AS conomobra,
                    TRIM(IFNULL(o.TADESCRIP, '')) AS os_nombre,
                    TRIM(r.{$this->col('COPRESTADO')}) AS coprestado,
                    TRIM(r.{$this->col('CONOMPREST')}) AS conomprest_reg,
                    {$nomEbamp} AS nombre_ebamp,
                    {$subSel} AS subcateg,
                    {$zonaSel} AS zona,
                    {$leyenda} AS coleyenda_reg,
                    {$factura} AS cofactura,
                    IFNULL({$cant}, 0) AS cocantidad,
                    IFNULL({$imp}, 0) AS coimpfac,
                    IFNULL({$iva}, 0) AS coivafac,
                    IFNULL({$csg}, 0) AS cocsgfac,
                    IFNULL({$tot}, 0) AS cototalfac,
                    {$tipoReg} AS cotipopre,
                    {$tipoEbamp} AS tipo_ebamp
                FROM registrf r
                LEFT JOIN ebamp e
                    ON TRIM(r.{$this->col('COPRESTADO')}) = TRIM(CAST(e.matricula AS CHAR))
                    OR TRIM(r.{$this->col('COPRESTADO')}) = TRIM(CAST(e.codigo AS CHAR))
                LEFT JOIN obrasoc o
                    ON TRIM(r.{$this->col('COOBRASOC')}) = TRIM(o.TACODIGO)
                WHERE " . implode(' AND ', $where) . "
                ORDER BY {$subSel} ASC, r.{$this->col('COOBRASOC')} ASC,
                         r.{$this->col('CONOMPREST')} ASC, {$fecFac} ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($bind);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!is_array($rows)) {
            return [];
        }

        foreach ($rows as &$r) {
            $nombre = trim((string) ($r['nombre_ebamp'] ?? ''));
            if ($nombre === '') {
                $nombre = trim((string) ($r['conomprest_reg'] ?? ''));
            }
            $r['conomprest'] = $nombre;

            $sub = strtoupper(trim((string) ($r['subcateg'] ?? '')));
            $r['subcateg'] = $sub !== '' ? $sub : 'SIN SUBCATEGORIA';

            $zona = trim((string) ($r['zona'] ?? ''));
            $r['coleyenda'] = $zona !== '' ? $zona : trim((string) ($r['coleyenda_reg'] ?? ''));

            $tipoRaw = trim((string) ($r['cotipopre'] ?? ''));
            if ($tipoRaw === '') {
                $tipoRaw = trim((string) ($r['tipo_ebamp'] ?? ''));
            }
            if (strncasecmp($tipoRaw, 'MED', 3) === 0) {
                $tipoRaw = 'Cabecera';
            }
            $r['cotipopre'] = $tipoRaw;

            $r['cocantcalc'] = (float) ($r['cocantidad'] ?? 0);
            $r['copesos']    = (float) ($r['cototalfac'] ?? 0);
            $r['coimpfac']   = (float) ($r['coimpfac'] ?? 0);
            $r['coivafac']   = (float) ($r['coivafac'] ?? 0);
            $r['cocsgfac']   = (float) ($r['cocsgfac'] ?? 0);
            $r['cototalfac'] = (float) ($r['cototalfac'] ?? 0);

            $osNom = trim((string) ($r['os_nombre'] ?? ''));
            if ($osNom === '') {
                $osNom = trim((string) ($r['conomobra'] ?? ''));
            }
            $r['os_nombre'] = $osNom;
        }
        unset($r);

        return $rows;
    }

    /**
     * @param array<int,array<string,mixed>> $filas
     * @return array<string,array<string,mixed>>
     */
    public function agruparDetalladoPorSubgrupo(array $filas): array
    {
        $out = [];
        foreach ($filas as $r) {
            $key = (string) ($r['subcateg'] ?? 'SIN SUBCATEGORIA');
            if (!isset($out[$key])) {
                $out[$key] = [
                    'nombre'    => $key,
                    'filas'     => [],
                    'cantidad'  => 0.0,
                    'importe'   => 0.0,
                    'iva'       => 0.0,
                    'coseguro'  => 0.0,
                    'total'     => 0.0,
                    'consultas' => 0.0,
                    'pesos'     => 0.0,
                ];
            }
            $out[$key]['filas'][] = $r;
            $out[$key]['cantidad']  += (float) $r['cocantcalc'];
            $out[$key]['importe']   += (float) $r['coimpfac'];
            $out[$key]['iva']       += (float) $r['coivafac'];
            $out[$key]['coseguro']  += (float) $r['cocsgfac'];
            $out[$key]['total']     += (float) $r['cototalfac'];
            $out[$key]['consultas'] += (float) $r['cocantcalc'];
            $out[$key]['pesos']     += (float) $r['copesos'];
        }
        return $out;
    }

    /**
     * Totales acumulados x OS (resu1-2).
     *
     * @param array{desde:string,hasta:string,os?:string,prestador?:string,tipo?:string} $f
     * @return array{obras:array<int,array<string,mixed>>,total:array<string,float>}
     */
    public function reporteTotalesAcumOs(array $f): array
    {
        $filas = $this->reporteDetalladoOs($f);
        $obras = [];
        foreach ($filas as $r) {
            $os = trim((string) ($r['coobrasoc'] ?? ''));
            if ($os === '') {
                $os = 'S/OS';
            }
            if (!isset($obras[$os])) {
                $obras[$os] = [
                    'codigo'    => $os,
                    'nombre'    => (string) ($r['os_nombre'] ?? ''),
                    'subs'      => [],
                    'cantidad'  => 0.0,
                    'total'     => 0.0,
                    'consultas' => 0.0,
                    'importe'   => 0.0,
                ];
            }
            $sub = (string) ($r['subcateg'] ?? 'SIN SUBCATEGORIA');
            if (!isset($obras[$os]['subs'][$sub])) {
                $obras[$os]['subs'][$sub] = [
                    'subcateg'  => $sub,
                    'cantidad'  => 0.0,
                    'total'     => 0.0,
                    'consultas' => 0.0,
                    'importe'   => 0.0,
                ];
            }
            $cant = (float) $r['cocantcalc'];
            $cons = $this->subcategSumaConsultas($sub) ? $cant : 0.0;
            $tot  = (float) $r['copesos'];
            $imp  = (float) $r['coimpfac'];

            $obras[$os]['subs'][$sub]['cantidad']  += $cant;
            $obras[$os]['subs'][$sub]['total']     += $tot;
            $obras[$os]['subs'][$sub]['consultas'] += $cons;
            $obras[$os]['subs'][$sub]['importe']   += $imp;

            $obras[$os]['cantidad']  += $cant;
            $obras[$os]['total']     += $tot;
            $obras[$os]['consultas'] += $cons;
            $obras[$os]['importe']   += $imp;
        }

        $lista = array_values($obras);
        foreach ($lista as &$osRow) {
            $osRow['subs'] = array_values($osRow['subs']);
            $osRow['cons_vestida'] = $osRow['consultas'] > 0
                ? $osRow['total'] / $osRow['consultas']
                : 0.0;
        }
        unset($osRow);

        $total = [
            'cantidad'  => 0.0,
            'total'     => 0.0,
            'consultas' => 0.0,
            'importe'   => 0.0,
        ];
        foreach ($lista as $osRow) {
            $total['cantidad']  += $osRow['cantidad'];
            $total['total']     += $osRow['total'];
            $total['consultas'] += $osRow['consultas'];
            $total['importe']   += $osRow['importe'];
        }
        $total['cons_vestida'] = $total['consultas'] > 0
            ? $total['total'] / $total['consultas']
            : 0.0;

        return ['obras' => $lista, 'total' => $total];
    }

    private function subcategSumaConsultas(string $sub): bool
    {
        $u = strtoupper(trim($sub));
        $ok = [
            'CABECERA',
            'ESPECIALIZADOS',
            'ESPECIALISTAS',
            'MAS DE UNA ESPECIALIDAD',
            'UNIVALENTE/ESPECIALIZADOS',
            'UNIVALENTE/ESPECIALI',
        ];
        foreach ($ok as $k) {
            if ($u === $k || strpos($u, $k) === 0 || strpos($k, $u) === 0) {
                return true;
            }
        }
        return false;
    }
}
