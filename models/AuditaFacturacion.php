<?php
/**
 * models/AuditaFacturacion.php
 * Form VFP AUDITA — alias Grid1 RecordSource `audifac2`.
 * Dialecto PHP 5.6.
 *
 * Campos tomados del extract AUDITA.SCX/SCT (ControlSource / METHODS).
 * El SELECT real se recorta con SHOW COLUMNS: no se inventan columnas.
 */

require_once __DIR__ . '/../config/database.php';

class AuditaFacturacion
{
    const TABLA = 'audifac2';

    /** @var PDO */
    private $db;

    public function __construct()
    {
        $this->db = getDBConnection();
    }

    /**
     * Campos citados en Grid1 / Init / Command32 / Command1.
     *
     * @return string[]
     */
    public static function camposCitados()
    {
        return array(
            'nroorden',
            'coselecc',
            'cofeccons',
            'coafiliado',
            'conompac',
            'cocodprest',
            'conombpres',
            'cocantpres',
            'cofacturad',
            'coimporte',
            'cocoseguro',
            'codebito',
            'coleyedeb',
            'conumero',
            'coautoriz',
            'coplan',
            'cointercon',
            'nropresup',
            'clasif',
            'nrointer',
            'conommot',
            'sedacion',
            'cocadena',
            'refacsuc',
            'refacnro',
            'cosegpres',
            'coexccos',
            'codebaver',
            'codebvisto',
            'coadh',
            'liqneg',
            'expediente',
            'ubnbu',
            'dniafil',
            'coliquidad',
            'coleydeb2',
            'coprestado',
            'coobrasoc',
            'cosucfac',
            'conrofac',
            'coperiodo',
            'conomprest',
            'coempreact',
            'amotivo',
        );
    }

    /**
     * Columnas reales de audifac2 (minúsculas). Vacío si la tabla no existe.
     *
     * @return string[]
     */
    public function columnasReales()
    {
        try {
            $stmt = $this->db->query('SHOW COLUMNS FROM `' . self::TABLA . '`');
        } catch (PDOException $e) {
            return array();
        }
        $out = array();
        if ($stmt) {
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if (isset($row['Field'])) {
                    $out[] = strtolower((string) $row['Field']);
                }
            }
        }
        return $out;
    }

    /**
     * @param  string   $nombre
     * @param  string[] $reales
     * @return string|null
     */
    public function colReal($nombre, $reales)
    {
        $needle = strtolower($nombre);
        $i = 0;
        $n = count($reales);
        for ($i = 0; $i < $n; $i++) {
            if ($reales[$i] === $needle) {
                return $reales[$i];
            }
        }
        return null;
    }

    /**
     * Filtros = parámetros de AUDITA.Init mapeados a campos citados en METHODS.
     *
     * @param  array $filtros obrasoc, suc, factura, prestador, periodo
     * @return array {filas, totales, tabla_ok, columnas}
     */
    public function listar(array $filtros = array())
    {
        $reales = $this->columnasReales();
        if (empty($reales)) {
            return array(
                'filas'     => array(),
                'totales'   => $this->totalesVacios(),
                'tabla_ok'  => false,
                'columnas'  => array(),
            );
        }

        $selectCols = array();
        $citados = self::camposCitados();
        $c = 0;
        $nCit = count($citados);
        for ($c = 0; $c < $nCit; $c++) {
            $real = $this->colReal($citados[$c], $reales);
            if ($real !== null) {
                $selectCols[] = $real;
            }
        }
        if (empty($selectCols)) {
            return array(
                'filas'     => array(),
                'totales'   => $this->totalesVacios(),
                'tabla_ok'  => true,
                'columnas'  => $reales,
            );
        }

        $built  = $this->buildWhere($filtros, $reales);
        $where  = $built[0];
        $params = $built[1];

        $orderCol = isset($filtros['orden']) ? strtolower(trim((string) $filtros['orden'])) : '';
        $orderSql = '';
        if ($orderCol !== '' && $this->colReal($orderCol, $reales) !== null) {
            $dir = (isset($filtros['dir']) && strtoupper((string) $filtros['dir']) === 'DESC') ? 'DESC' : 'ASC';
            $orderSql = ' ORDER BY `' . $this->colReal($orderCol, $reales) . '` ' . $dir;
        } elseif ($this->colReal('nroorden', $reales) !== null) {
            $orderSql = ' ORDER BY `nroorden` ASC';
        }

        $sql  = 'SELECT `' . implode('`,`', $selectCols) . '` FROM `' . self::TABLA . '`' . $where . $orderSql;
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $filas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $filas = $this->anexarDifcpre($filas);

        return array(
            'filas'     => $filas,
            'totales'   => $this->totalesDeFilas($filas, $reales),
            'tabla_ok'  => true,
            'columnas'  => $selectCols,
        );
    }

    /**
     * Recalc Command32 sobre el mismo filtro.
     *
     * @param  array $filtros
     * @return array
     */
    public function recalc(array $filtros = array())
    {
        $list = $this->listar($filtros);
        return $list['totales'];
    }

    /**
     * Check3: REPLACE ALL sedacion WITH .T. FOR !empty(codebito).
     *
     * @param  array $filtros
     * @param  bool  $marcar
     * @return int
     */
    public function marcarSedacion(array $filtros, $marcar)
    {
        $reales = $this->columnasReales();
        if ($this->colReal('sedacion', $reales) === null) {
            return 0;
        }
        $built  = $this->buildWhere($filtros, $reales);
        $where  = $built[0];
        $params = $built[1];
        $val    = $marcar ? 1 : 0;
        if ($marcar && $this->colReal('codebito', $reales) !== null) {
            if ($where === '') {
                $where = ' WHERE TRIM(IFNULL(`codebito`,\'\')) <> \'\'';
            } else {
                $where .= ' AND TRIM(IFNULL(`codebito`,\'\')) <> \'\'';
            }
        }
        $sql = 'UPDATE `' . self::TABLA . '` SET `sedacion` = :sed ' . $where;
        $stmt = $this->db->prepare($sql);
        $params[':sed'] = $val;
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    /**
     * Check1: REPLACE ALL sedacion WITH .T. FOR LIKE("*A*",clasif).
     *
     * @param  array $filtros
     * @param  bool  $marcar
     * @return int
     */
    public function marcarSedacionClasifA(array $filtros, $marcar)
    {
        $reales = $this->columnasReales();
        if ($this->colReal('sedacion', $reales) === null) {
            return 0;
        }
        $built  = $this->buildWhere($filtros, $reales);
        $where  = $built[0];
        $params = $built[1];
        $val    = $marcar ? 1 : 0;
        if ($marcar && $this->colReal('clasif', $reales) !== null) {
            if ($where === '') {
                $where = ' WHERE `clasif` LIKE :clasifa';
            } else {
                $where .= ' AND `clasif` LIKE :clasifa';
            }
            $params[':clasifa'] = '%A%';
        }
        $sql = 'UPDATE `' . self::TABLA . '` SET `sedacion` = :sed ' . $where;
        $stmt = $this->db->prepare($sql);
        $params[':sed'] = $val;
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    /**
     * DblClick celdas: REPLACE coselecc WITH !coselecc (por nroorden).
     *
     * @param  int $nroorden
     * @return bool
     */
    public function toggleCoselecc($nroorden)
    {
        $reales = $this->columnasReales();
        if ($this->colReal('coselecc', $reales) === null
            || $this->colReal('nroorden', $reales) === null
        ) {
            return false;
        }
        $sql = 'UPDATE `' . self::TABLA . '`
                   SET `coselecc` = CASE WHEN `coselecc` THEN 0 ELSE 1 END
                 WHERE `nroorden` = :nro
                 LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(':nro' => (int) $nroorden));
        return $stmt->rowCount() > 0;
    }

    /**
     * @param  array    $filtros
     * @param  string[] $reales
     * @return array    [whereSql, params]
     */
    private function buildWhere(array $filtros, array $reales)
    {
        $map = array(
            'obrasoc'   => 'coobrasoc',
            'suc'       => 'cosucfac',
            'factura'   => 'conrofac',
            'prestador' => 'coprestado',
            'periodo'   => 'coperiodo',
        );
        $parts  = array();
        $params = array();
        foreach ($map as $param => $col) {
            $real = $this->colReal($col, $reales);
            $val  = isset($filtros[$param]) ? trim((string) $filtros[$param]) : '';
            if ($real === null || $val === '') {
                continue;
            }
            $ph = ':' . $param;
            $parts[] = '`' . $real . '` = ' . $ph;
            $params[$ph] = $val;
        }
        if (empty($parts)) {
            return array('', array());
        }
        return array(' WHERE ' . implode(' AND ', $parts), $params);
    }

    /**
     * Column25.ControlSource (expresión IIF, no es campo de tabla).
     *
     * @param  array $filas
     * @return array
     */
    private function anexarDifcpre(array $filas)
    {
        $i = 0;
        $n = count($filas);
        for ($i = 0; $i < $n; $i++) {
            $exc = (int) $this->cell($filas[$i], 'coexccos');
            $cpr = (float) $this->cell($filas[$i], 'cosegpres');
            $deb = trim((string) $this->cell($filas[$i], 'codebito'));
            $cos = (float) $this->cell($filas[$i], 'cocoseguro');
            if ($exc || ($cpr < 1 && strlen($deb) > 2 && $deb !== '010')) {
                $filas[$i]['difcpre'] = 0;
            } else {
                $filas[$i]['difcpre'] = $cpr - $cos;
            }
        }
        return $filas;
    }

    /**
     * SUM de Command32.Click (extract).
     *
     * @param  array    $filas
     * @param  string[] $reales
     * @return array
     */
    private function totalesDeFilas(array $filas, array $reales)
    {
        $t = $this->totalesVacios();
        $sumCos = 0;
        $hasCant = $this->colReal('cocantpres', $reales) !== null;
        $hasFac  = $this->colReal('cofacturad', $reales) !== null;
        $hasImp  = $this->colReal('coimporte', $reales) !== null;
        $hasCos  = $this->colReal('cocoseguro', $reales) !== null;
        $hasCpr  = $this->colReal('cosegpres', $reales) !== null;
        $hasExc  = $this->colReal('coexccos', $reales) !== null;
        $hasDeb  = $this->colReal('codebito', $reales) !== null;
        $hasNeg  = $this->colReal('liqneg', $reales) !== null;

        $i = 0;
        $n = count($filas);
        for ($i = 0; $i < $n; $i++) {
            $row = $filas[$i];
            $cant = $hasCant ? (float) $this->cell($row, 'cocantpres') : 0;
            $fac  = $hasFac ? (float) $this->cell($row, 'cofacturad') : 0;
            $imp  = $hasImp ? (float) $this->cell($row, 'coimporte') : 0;
            $cos  = $hasCos ? (float) $this->cell($row, 'cocoseguro') : 0;
            $cpr  = $hasCpr ? (float) $this->cell($row, 'cosegpres') : 0;
            $exc  = $hasExc ? (int) $this->cell($row, 'coexccos') : 0;
            $deb  = $hasDeb ? trim((string) $this->cell($row, 'codebito')) : '';
            $neg  = $hasNeg ? (float) $this->cell($row, 'liqneg') : 0;

            $t['text1'] += $cant;
            $t['text2'] += $fac * $cant;
            $t['text3'] += $imp * $cant;
            if (!$exc) {
                $sumCos += $cos * $cant;
                $t['text8'] += $cpr * $cant;
            } else {
                $t['text9'] += $cos * $cant;
            }
            if (!$exc && $cpr < 1 && strlen($deb) > 2 && $deb !== '010') {
                $t['text16'] += $cos * $cant;
            }
            $t['text11'] += $neg;
        }
        $t['text4'] = $sumCos - $t['text16'];
        $t['text7'] = $t['text8'] - $t['text4'];
        $t['text10'] = $t['text2'] - $t['text8'];
        return $t;
    }

    /**
     * @return array
     */
    private function totalesVacios()
    {
        return array(
            'text1'  => 0,
            'text2'  => 0,
            'text3'  => 0,
            'text4'  => 0,
            'text7'  => 0,
            'text8'  => 0,
            'text9'  => 0,
            'text10' => 0,
            'text11' => 0,
            'text16' => 0,
        );
    }

    /**
     * @param  array  $row
     * @param  string $col
     * @return mixed
     */
    private function cell(array $row, $col)
    {
        if (isset($row[$col])) {
            return $row[$col];
        }
        $keys = array_keys($row);
        $i = 0;
        $n = count($keys);
        for ($i = 0; $i < $n; $i++) {
            if (strtolower($keys[$i]) === strtolower($col)) {
                return $row[$keys[$i]];
            }
        }
        return '';
    }
}
