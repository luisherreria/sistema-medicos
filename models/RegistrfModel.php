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
                $real = (string)$row['Field'];
                $this->columnasCache[strtoupper($real)] = $real;
            }
        } catch (PDOException $e) {
            error_log('RegistrfModel::columnas: ' . $e->getMessage());
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
        string $orderDir  = 'DESC'
    ): array {
        [$where, $params] = $this->buildWhere($busqueda);
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
                    TRIM({$this->col('COPERIODO')})  AS COPERIODO,
                    TRIM({$this->col('COOBRASOC')})  AS COOBRASOC,
                    TRIM({$this->col('COPRESTADO')}) AS COPRESTADO,
                    TRIM({$this->col('CONOMPREST')}) AS CONOMPREST,
                    TRIM({$this->col('COSUCFAC')})   AS COSUCFAC,
                    TRIM({$this->col('CONROFAC')})   AS CONROFAC,
                    " . ($this->tieneColumna('COFECFAC') ? $this->col('COFECFAC') : 'NULL') . " AS COFECFAC,
                    " . ($this->tieneColumna('COTOTALFAC') ? $this->col('COTOTALFAC') : 'NULL') . " AS COTOTALFAC,
                    TRIM({$this->col('COUSUARIO')})  AS COUSUARIO,
                    {$fecCarga} AS COFECCARGA
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
        $existentes = [];
        foreach ($campos as $logico => $valor) {
            if ($this->tieneColumna($logico)) {
                $existentes[$this->col($logico)] = $valor;
            }
        }

        if ($this->tieneColumna('COFECCARGA') && !isset($existentes[$this->col('COFECCARGA')])) {
            $existentes[$this->col('COFECCARGA')] = date('Y-m-d H:i:s');
        }

        if (!$existentes) {
            throw new PDOException('No hay columnas coincidentes para insertar en registrf.');
        }

        $cols = array_keys($existentes);
        $phs  = [];
        $bind = [];
        $i    = 0;
        foreach ($existentes as $col => $val) {
            $ph = ':p' . $i++;
            $phs[] = $ph;
            $bind[$ph] = $val;
        }

        $sql = 'INSERT INTO registrf (' . implode(', ', $cols) . ') VALUES (' . implode(', ', $phs) . ')';
        $this->db->prepare($sql)->execute($bind);
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
        $periodo = strtoupper(trim(str_replace('/', '', $periodo)));
        $fechaExpr = $this->tieneColumna('COFECHA')
            ? $this->col('COFECHA')
            : ($this->tieneColumna('COFECFAC') ? $this->col('COFECFAC') : 'NULL');
        $fecFacExpr = $this->tieneColumna('COFECFAC') ? $this->col('COFECFAC') : 'NULL';

        $ebamp = $this->columnasEbamp();
        $flagSql = '';
        if ($tipo === 'conflicto' && isset($ebamp['conflicto'])) {
            $flagSql = "AND TRIM(IFNULL(e.conflicto,'')) NOT IN ('', '0', 'N', 'F')";
        } elseif ($tipo === 'prioritario' && isset($ebamp['recomenda'])) {
            $flagSql = "AND TRIM(IFNULL(e.recomenda,'')) NOT IN ('', '0', 'N', 'F')";
        } elseif ($tipo === 'prioritario' && isset($ebamp['conflicto'])) {
            // fallback: si no hay recomenda, usar el mismo criterio de RPT_CONFLIF
            $flagSql = "AND TRIM(IFNULL(e.conflicto,'')) NOT IN ('', '0', 'N', 'F')";
        }

        $sql = "SELECT
                    TRIM(r.{$this->col('COPERIODO')})  AS coperiodo,
                    {$fechaExpr}                       AS cofecha,
                    TRIM(r.{$this->col('COPRESTADO')}) AS coprestado,
                    TRIM(r.{$this->col('CONOMPREST')}) AS conomprest,
                    TRIM(r.{$this->col('COSUCFAC')})   AS cosucfac,
                    TRIM(r.{$this->col('CONROFAC')})   AS conrofac,
                    {$fecFacExpr}                      AS cofecfac,
                    TRIM(IFNULL(e.nombre,''))          AS nombre_ebamp
                FROM registrf r
                INNER JOIN ebamp e
                    ON TRIM(r.{$this->col('COPRESTADO')}) = TRIM(e.matricula)
                    OR TRIM(r.{$this->col('COPRESTADO')}) = TRIM(e.codigo)
                WHERE TRIM(r.{$this->col('COPERIODO')}) = :periodo
                  {$flagSql}
                ORDER BY r.{$this->col('CONOMPREST')} ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':periodo' => $periodo]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
                $cols[strtolower((string)$row['Field'])] = true;
            }
        } catch (PDOException $e) {
            error_log('RegistrfModel::columnasEbamp: ' . $e->getMessage());
        }
        return $cols;
    }

    // ══════════════════════════════════════════════════════════════════════
    //  HELPERS
    // ══════════════════════════════════════════════════════════════════════

    private function buildWhere(string $busqueda): array
    {
        if ($busqueda === '') {
            return ['', []];
        }
        $val    = '%' . $busqueda . '%';
        $where  = " WHERE ({$this->col('CONOMPREST')} LIKE :b1"
                . " OR {$this->col('CONROFAC')} LIKE :b2"
                . " OR {$this->col('COOBRASOC')} LIKE :b3"
                . " OR {$this->col('COPRESTADO')} LIKE :b4)";
        $params = [':b1' => $val, ':b2' => $val, ':b3' => $val, ':b4' => $val];
        return [$where, $params];
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
}
