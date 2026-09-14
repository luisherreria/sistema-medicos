<?php
/**
 * models/RegistrfModel.php
 * ─────────────────────────────────────────────────────────────────────────
 * Acceso a datos de la tabla `registrf` (Registración de Facturas).
 *
 * Columnas usadas en el listado:
 *   COPERIODO   AAMM  (ej: 2607 = Julio 2026)
 *   COOBRASOC   Código Obra Social
 *   COPRESTADO  Código del Prestador
 *   CONOMPREST  Nombre del Prestador
 *   COSUCFAC    Punto de Venta / Sucursal
 *   CONROFAC    Número de Factura
 *   COFECFAC    Fecha de Factura
 *   COFECRECIB  Fecha Recibido
 *   COTOTALFAC  Total
 *   COUSUARIO   Usuario que cargó
 *   COFECCARGA  Fecha/hora de carga
 * ─────────────────────────────────────────────────────────────────────────
 */

require_once __DIR__ . '/../config/database.php';

class RegistrfModel
{
    private PDO $db;

    /** Columnas habilitadas para ordenamiento (lista blanca). */
    private const COLS_SORT = [
        'COPERIODO', 'COOBRASOC', 'COPRESTADO', 'CONOMPREST',
        'COSUCFAC', 'CONROFAC', 'COFECFAC', 'COFECRECIB',
        'COTOTALFAC', 'COUSUARIO', 'COFECCARGA',
    ];

    public function __construct()
    {
        $this->db = getDBConnection();
    }

    // ══════════════════════════════════════════════════════════════════════
    //  LISTADO con búsqueda, ordenamiento y paginación
    // ══════════════════════════════════════════════════════════════════════

    /**
     * @param string $busqueda  Texto libre (busca en CONOMPREST, CONROFAC, COOBRASOC).
     * @param int    $limit     Registros por página.
     * @param int    $offset    Offset para paginación.
     * @param string $orderCol  Columna para ordenar (validada contra COLS_SORT).
     * @param string $orderDir  'ASC' | 'DESC'
     * @return array  { total: int, datos: array[] }
     */
    public function obtenerFacturas(
        string $busqueda  = '',
        int    $limit     = 50,
        int    $offset    = 0,
        string $orderCol  = 'COFECCARGA',
        string $orderDir  = 'DESC'
    ): array {
        [$where, $params] = $this->buildWhere($busqueda);
        $orderExpr = $this->resolverOrderExpr($orderCol, $orderDir);

        // ── Total ─────────────────────────────────────────────────────────
        $stmtCnt = $this->db->prepare("SELECT COUNT(*) FROM registrf{$where}");
        $stmtCnt->execute($params);
        $total = (int) $stmtCnt->fetchColumn();

        // ── Datos ─────────────────────────────────────────────────────────
        $sql = "SELECT
                    TRIM(COPERIODO)  AS COPERIODO,
                    TRIM(COOBRASOC)  AS COOBRASOC,
                    TRIM(COPRESTADO) AS COPRESTADO,
                    TRIM(CONOMPREST) AS CONOMPREST,
                    TRIM(COSUCFAC)   AS COSUCFAC,
                    TRIM(CONROFAC)   AS CONROFAC,
                    COFECFAC,
                    COFECRECIB,
                    COTOTALFAC,
                    TRIM(COUSUARIO)  AS COUSUARIO,
                    COALESCE(
                        NULLIF(COFECCARGA, '0000-00-00 00:00:00'),
                        NULLIF(COFECCARGA, '0000-00-00'),
                        COFECFAC
                    ) AS COFECCARGA
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
    //  INSERT
    // ══════════════════════════════════════════════════════════════════════

    public function insertar(array $d): void
    {
        $this->db->prepare(
            "INSERT INTO registrf
                (COFECHA, COPERIODO, COOBRASOC, COCATEG, COPRESTADO, CONOMPREST,
                 COSUCFAC, CONROFAC, COFECFAC, COFECRECIB, COTIPO,
                 COCANTIDAD, COIMPORTE, COIVA, COCOSEGURO, COTOTALFAC,
                 COMONTO, COCANTPREST, COTIENEFAC, COUSUARIO, COFECCARGA)
             VALUES
                (:fecha,:periodo,:obrasoc,:categ,:prestado,:nomprest,
                 :sucfac,:nrofac,:fecfac,:fecrecib,:tipo,
                 :cantidad,:importe,:iva,:coseguro,:totalfac,
                 :monto,:cantprest,:tienefac,:usuario,NOW())"
        )->execute($d);
    }

    // ══════════════════════════════════════════════════════════════════════
    //  HELPERS PRIVADOS
    // ══════════════════════════════════════════════════════════════════════

    private function buildWhere(string $busqueda): array
    {
        if ($busqueda === '') {
            return ['', []];
        }
        $val    = '%' . $busqueda . '%';
        $where  = " WHERE (CONOMPREST LIKE :b1 OR CONROFAC LIKE :b2 OR COOBRASOC LIKE :b3 OR COPRESTADO LIKE :b4)";
        $params = [':b1' => $val, ':b2' => $val, ':b3' => $val, ':b4' => $val];
        return [$where, $params];
    }

    private function resolverOrderExpr(string $col, string $dir): string
    {
        $col = in_array($col, self::COLS_SORT, true) ? $col : 'COFECCARGA';
        $dir = strtoupper($dir) === 'ASC' ? 'ASC' : 'DESC';

        if ($col === 'COFECCARGA') {
            return "COALESCE(NULLIF(COFECCARGA,'0000-00-00 00:00:00'),COFECFAC) {$dir}";
        }
        return "{$col} {$dir}";
    }
}
