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

    /** @var string|null nombre real en MySQL */
    private $tablaReal = null;

    /** @var string */
    private $tablaError = '';

    public function __construct()
    {
        $this->db = getDBConnection();
    }

    /**
     * @return string
     */
    public function ultimoError()
    {
        return $this->tablaError;
    }

    /**
     * audifac2 es el alias VFP (USE c:\bases\AUDIFAC2). En MySQL el nombre puede
     * ser audifact / audif2 / detalle si el importador usó el DBF original.
     *
     * @return string
     */
    public function tablaNombre()
    {
        if ($this->tablaReal !== null) {
            return $this->tablaReal;
        }
        $this->tablaReal = '';
        $this->tablaError = '';
        $map = array();
        try {
            $stmt = $this->db->query('SHOW TABLES');
        } catch (PDOException $e) {
            $this->tablaError = $e->getMessage();
            return '';
        }
        if ($stmt) {
            while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
                if (!isset($row[0]) || (string) $row[0] === '') {
                    continue;
                }
                $map[strtolower((string) $row[0])] = (string) $row[0];
            }
        }
        if (empty($map)) {
            $this->tablaError = 'SHOW TABLES no devolvió tablas en vfpmedicos.';
            return '';
        }

        $candidatos = array('audifac2', 'audifact', 'audifac', 'audif2', 'detalle');
        foreach ($map as $low => $actual) {
            if (strpos($low, 'audifac') !== false || $low === 'audif2') {
                $candidatos[] = $low;
            }
        }

        $mejor = '';
        $mejorHits = 0;
        $vistos = array();
        $revisadas = array();
        $i = 0;
        $n = count($candidatos);
        for ($i = 0; $i < $n; $i++) {
            $c = $candidatos[$i];
            if (!isset($map[$c]) || isset($vistos[$c])) {
                continue;
            }
            $vistos[$c] = true;
            $actual = $map[$c];
            $cols = $this->columnasDeTabla($actual);
            $hits = $this->cuentaCamposAudita($cols);
            $revisadas[] = $actual . ' (' . $hits . ' campos de AUDITA)';
            if ($hits >= 5) {
                $this->tablaReal = $actual;
                $this->tablaError = '';
                return $this->tablaReal;
            }
            if ($hits > $mejorHits) {
                $mejorHits = $hits;
                $mejor = $actual;
            }
        }

        if ($mejor !== '' && $mejorHits > 0) {
            $this->tablaReal = $mejor;
            $this->tablaError = '';
            return $this->tablaReal;
        }

        $parecidas = array();
        foreach ($map as $low => $actual) {
            if (strpos($low, 'audi') !== false || strpos($low, 'fact') !== false
                || strpos($low, 'detal') !== false || $low === 'consulta'
            ) {
                $parecidas[] = $actual;
            }
        }
        $this->tablaError = 'No está la tabla audifac2 en vfpmedicos (VFP: c:\\bases\\AUDIFAC2).';
        if (!empty($revisadas)) {
            $this->tablaError .= ' Revisadas: ' . implode(', ', $revisadas) . '.';
        }
        if (!empty($parecidas)) {
            $this->tablaError .= ' Parecidas: ' . implode(', ', $parecidas) . '.';
        } else {
            $this->tablaError .= ' Importe el DBF AUDIFAC2 (no se crea el esquema acá).';
        }
        return '';
    }

    /**
     * @param  string $nombre
     * @return string[]
     */
    private function columnasDeTabla($nombre)
    {
        $safe = str_replace(array('`', '.', ' ', ';'), '', (string) $nombre);
        if ($safe === '') {
            return array();
        }
        try {
            $stmt = $this->db->query('SHOW COLUMNS FROM `' . $safe . '`');
        } catch (PDOException $e) {
            $this->tablaError = $e->getMessage();
            return array();
        }
        $out = array();
        if ($stmt) {
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $field = '';
                if (isset($row['Field'])) {
                    $field = $row['Field'];
                } elseif (isset($row['field'])) {
                    $field = $row['field'];
                }
                if ($field !== '') {
                    $out[] = strtolower((string) $field);
                }
            }
        }
        return $out;
    }

    /**
     * @param  string[] $cols
     * @return int
     */
    private function cuentaCamposAudita($cols)
    {
        $set = array();
        $i = 0;
        $n = count($cols);
        for ($i = 0; $i < $n; $i++) {
            $set[strtolower($cols[$i])] = true;
        }
        $citados = self::camposCitados();
        $hits = 0;
        $nCit = count($citados);
        for ($i = 0; $i < $nCit; $i++) {
            if (isset($set[strtolower($citados[$i])])) {
                $hits++;
            }
        }
        return $hits;
    }

    /**
     * @return string
     */
    private function tablaSql()
    {
        $t = $this->tablaNombre();
        if ($t === '') {
            $t = self::TABLA;
        }
        return '`' . str_replace(array('`', '.'), '', $t) . '`';
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
            'cofuerapa',
            'afilcont',
            'cousuario',
            'cofechacar',
            'cohoracar',
            'cocateg',
            'conomdebi',
        );
    }

    /**
     * Columnas reales de audifac2 (minúsculas). Vacío si la tabla no existe.
     *
     * @return string[]
     */
    public function columnasReales()
    {
        $t = $this->tablaNombre();
        if ($t === '') {
            return array();
        }
        try {
            $stmt = $this->db->query('SHOW COLUMNS FROM ' . $this->tablaSql());
        } catch (PDOException $e) {
            $this->tablaError = $e->getMessage();
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
                'tabla'     => $this->tablaNombre(),
                'error'     => $this->ultimoError(),
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

        $sql  = 'SELECT `' . implode('`,`', $selectCols) . '` FROM ' . $this->tablaSql() . '' . $where . $orderSql
            . ' LIMIT 4001';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $filas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $truncado = count($filas) > 4000;
        if ($truncado) {
            $filas = array_slice($filas, 0, 4000);
        }
        $filas = $this->anexarDifcpre($filas);

        return array(
            'filas'     => $filas,
            'totales'   => $this->totalesDeFilas($filas, $reales),
            'tabla_ok'  => true,
            'truncado'  => $truncado,
            'columnas'  => $selectCols,
        );
    }

    /**
     * Selector de facturas (menú sin Init): GROUP BY campos de AUDITA.Init.
     * Sustituye AUDIFACT.SCX, que no está en este corte.
     *
     * @return array {filas, tabla_ok}
     */
    public function listarFacturas()
    {
        $reales = $this->columnasReales();
        if (empty($reales)) {
            return array(
                'filas'     => array(),
                'tabla_ok'  => false,
                'tabla'     => $this->tablaNombre(),
                'error'     => $this->ultimoError(),
            );
        }
        $grupo = array('coprestado', 'coobrasoc', 'cosucfac', 'conrofac', 'coperiodo');
        $gCols = array();
        $g = 0;
        for ($g = 0; $g < 5; $g++) {
            $real = $this->colReal($grupo[$g], $reales);
            if ($real !== null) {
                $gCols[] = $real;
            }
        }
        if (empty($gCols)) {
            if ($this->tablaError === '') {
                $this->tablaError = 'La tabla ' . $this->tablaNombre()
                    . ' no tiene coprestado/coobrasoc/conrofac (columnas Init de AUDITA).';
            }
            return array(
                'filas'     => array(),
                'tabla_ok'  => false,
                'tabla'     => $this->tablaNombre(),
                'error'     => $this->ultimoError(),
            );
        }
        $select = array();
        $i = 0;
        $n = count($gCols);
        for ($i = 0; $i < $n; $i++) {
            $select[] = '`' . $gCols[$i] . '`';
        }
        $nom = $this->colReal('conomprest', $reales);
        if ($nom !== null) {
            $select[] = 'MAX(`' . $nom . '`) AS `conomprest`';
        }
        $orderCol = $this->colReal('coperiodo', $reales);
        if ($orderCol === null) {
            $orderCol = $gCols[0];
        }
        $sql = 'SELECT ' . implode(',', $select) . ' FROM ' . $this->tablaSql() . ' GROUP BY `'
            . implode('`,`', $gCols) . '` ORDER BY `' . $orderCol . '` DESC LIMIT 500';
        try {
            $stmt = $this->db->query($sql);
        } catch (PDOException $e) {
            $this->tablaError = $e->getMessage();
            return array(
                'filas'     => array(),
                'tabla_ok'  => false,
                'tabla'     => $this->tablaNombre(),
                'error'     => $this->ultimoError(),
            );
        }
        $filas = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : array();
        return array(
            'filas'     => $filas,
            'tabla_ok'  => true,
            'tabla'     => $this->tablaNombre(),
            'error'     => '',
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
        $sql = 'UPDATE ' . $this->tablaSql() . ' SET `sedacion` = :sed ' . $where;
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
        $sql = 'UPDATE ' . $this->tablaSql() . ' SET `sedacion` = :sed ' . $where;
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
        $sql = 'UPDATE ' . $this->tablaSql() . '
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
     * INSERT en audifac2. Solo columnas que existan (SHOW COLUMNS).
     * No toca consulta/detalle/ebamp/detauto.
     *
     * @param  array $campos
     * @return int nroorden o 0
     */
    public function insertarFila(array $campos)
    {
        $reales = $this->columnasReales();
        if (empty($reales)) {
            return 0;
        }
        if ($this->colReal('nroorden', $reales) !== null && (!isset($campos['nroorden']) || $campos['nroorden'] === '')) {
            $stmt = $this->db->query('SELECT COALESCE(MAX(`nroorden`),0) + 1 FROM ' . $this->tablaSql() . '');
            $campos['nroorden'] = (int) $stmt->fetchColumn();
        }
        $cols   = array();
        $ph     = array();
        $params = array();
        foreach ($campos as $k => $v) {
            $real = $this->colReal($k, $reales);
            if ($real === null) {
                continue;
            }
            $cols[] = '`' . $real . '`';
            $ph[] = ':' . $real;
            $params[':' . $real] = $v;
        }
        if (empty($cols)) {
            return 0;
        }
        $sql = 'INSERT INTO ' . $this->tablaSql() . ' (' . implode(',', $cols) . ') VALUES (' . implode(',', $ph) . ')';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        if (isset($campos['nroorden'])) {
            return (int) $campos['nroorden'];
        }
        return $stmt->rowCount() > 0 ? 1 : 0;
    }

    /**
     * CAMBPRES.Command1: REPLACE sobre la fila actual (nroorden).
     * Si $todas: REPLACE ALL ... FOR cocodprest = prestant (sin tocar coseguros).
     *
     * @param  int   $nroorden
     * @param  array $campos
     * @param  bool  $todas
     * @param  bool  $ccanti
     * @param  string $prestant
     * @return int
     */
    public function actualizarPrestacion($nroorden, array $campos, $todas, $ccanti, $prestant)
    {
        $reales = $this->columnasReales();
        if (empty($reales)) {
            return 0;
        }
        $set = array();
        $params = array();
        foreach ($campos as $k => $v) {
            $real = $this->colReal($k, $reales);
            if ($real === null) {
                continue;
            }
            $set[] = '`' . $real . '` = :' . $real;
            $params[':' . $real] = $v;
        }
        if (empty($set)) {
            return 0;
        }
        if ($todas && $this->colReal('cocodprest', $reales) !== null && $prestant !== '') {
            $sql = 'UPDATE ' . $this->tablaSql() . ' SET ' . implode(',', $set)
                . ' WHERE TRIM(`cocodprest`) = :prestant';
            $params[':prestant'] = $prestant;
        } elseif ($this->colReal('nroorden', $reales) !== null) {
            $sql = 'UPDATE ' . $this->tablaSql() . ' SET ' . implode(',', $set)
                . ' WHERE `nroorden` = :nro LIMIT 1';
            $params[':nro'] = (int) $nroorden;
        } else {
            return 0;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $n = $stmt->rowCount();
        if ($ccanti && $this->colReal('cocantpres', $reales) !== null
            && $this->colReal('cocodprest', $reales) !== null && $prestant !== ''
            && isset($campos['cocantpres'])
        ) {
            $st2 = $this->db->prepare(
                'UPDATE ' . $this->tablaSql() . ' SET `cocantpres` = :cant WHERE TRIM(`cocodprest`) = :prestant'
            );
            $st2->execute(array(
                ':cant'     => $campos['cocantpres'],
                ':prestant' => $prestant,
            ));
            $n += $st2->rowCount();
        }
        return $n;
    }

    /**
     * CAMBAFIL.Command1 misma OS: REPLACE audifac2 FOR CONUMERO.
     * Sin consulta/detalle. El cambio de OS (DELE audifac2) no se ejecuta aquí.
     *
     * @param  string $conumero
     * @param  array  $campos coafiliado, conompac, coplan, cofuerapa
     * @return int
     */
    public function cambiarAfiliado($conumero, array $campos)
    {
        $reales = $this->columnasReales();
        if ($this->colReal('conumero', $reales) === null || $conumero === '') {
            return 0;
        }
        $set = array();
        $params = array(':c' => $conumero);
        foreach ($campos as $k => $v) {
            $real = $this->colReal($k, $reales);
            if ($real === null) {
                continue;
            }
            $set[] = '`' . $real . '` = :' . $real;
            $params[':' . $real] = $v;
        }
        if (empty($set)) {
            return 0;
        }
        $sql = 'UPDATE ' . $this->tablaSql() . ' SET ' . implode(',', $set) . ' WHERE `conumero` = :c';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    /**
     * AUDITA.Command8 después de DEBITAR: REPLACE codebito / coleyedeb / cocadena / conomdebi.
     *
     * @param  int    $nroorden
     * @param  string $tdebi
     * @param  string $tleyenda
     * @return int
     */
    public function debitarFila($nroorden, $tdebi, $tleyenda)
    {
        $reales = $this->columnasReales();
        if ($this->colReal('nroorden', $reales) === null) {
            return 0;
        }
        $tdebi = trim($tdebi);
        $tleyenda = trim($tleyenda);
        $tnomdeb = $tleyenda;
        $cocadena = ($tdebi === '032') ? 'DIFERENCIA DE IMPORTE' : $tnomdeb;
        $coleyedeb = ($tdebi === '032') ? ($tleyenda . '. CREDITO A FAVOR X COSEGURO') : $tleyenda;
        $campos = array(
            'codebito'  => $tdebi,
            'cocadena'  => $cocadena,
            'coleyedeb' => $coleyedeb,
            'conomdebi' => $tleyenda,
        );
        $set = array();
        $params = array(':nro' => (int) $nroorden);
        foreach ($campos as $k => $v) {
            $real = $this->colReal($k, $reales);
            if ($real === null) {
                continue;
            }
            $set[] = '`' . $real . '` = :' . $real;
            $params[':' . $real] = $v;
        }
        if (empty($set)) {
            return 0;
        }
        $sql = 'UPDATE ' . $this->tablaSql() . ' SET ' . implode(',', $set)
            . ' WHERE `nroorden` = :nro LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
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
