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
        $candidatos = array('audifac2', 'audifact', 'audifac', 'audif2', 'detalle', 'detconsu');
        $mejor = '';
        $mejorHits = 0;
        $revisadas = array();
        $i = 0;
        $n = count($candidatos);
        for ($i = 0; $i < $n; $i++) {
            $fis = $this->tablaFisica($candidatos[$i]);
            if ($fis === '') {
                continue;
            }
            $cols = $this->columnasDeTabla($fis);
            $hits = $this->cuentaCamposAudita($cols);
            $revisadas[] = $fis . ' (' . $hits . ' campos de AUDITA)';
            if ($hits >= 5) {
                $this->tablaReal = $fis;
                $this->tablaError = '';
                return $this->tablaReal;
            }
            if ($hits > $mejorHits) {
                $mejorHits = $hits;
                $mejor = $fis;
            }
        }
        if ($mejor !== '' && $mejorHits > 0) {
            $this->tablaReal = $mejor;
            $this->tablaError = '';
            return $this->tablaReal;
        }
        $this->tablaError = 'No está audifac2 en vfpmedicos/cartilla/vfpcomedica (VFP: c:\\bases\\AUDIFAC2).';
        if (!empty($revisadas)) {
            $this->tablaError .= ' Revisadas: ' . implode(', ', $revisadas) . '.';
        }
        return '';
    }

    /**
     * @param  string $nombre
     * @return string[]
     */
    private function columnasDeTabla($nombre)
    {
        $ident = $this->sqlIdent($nombre);
        if ($ident === '') {
            return array();
        }
        try {
            $stmt = $this->db->query('SHOW COLUMNS FROM ' . $ident);
        } catch (PDOException $e) {
            $this->tablaError = $e->getMessage();
            return array();
        }
        if (!$stmt) {
            $info = $this->db->errorInfo();
            if (isset($info[2]) && (string) $info[2] !== '') {
                $this->tablaError = (string) $info[2];
            }
            return array();
        }
        $out = array();
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
        return $this->sqlIdent($t);
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

    /**
     * AUDIFACT.tpresta Valid: SEEK ebamp ORDER matricula.
     * ayudaPreN no migrado: si no hay fila, error.
     *
     * @param  string $tpresta
     * @return array
     */
    public function buscarPrestador($tpresta)
    {
        $tpresta = strtoupper(trim((string) $tpresta));
        $out = array(
            'ok'       => false,
            'tpresta'  => $tpresta,
            'nombre'   => '',
            'confact'  => false,
            'valereci' => false,
            'infoliq'  => '',
            'error'    => '',
        );
        if ($tpresta === '') {
            $out['error'] = 'Seleccione un Prestador para continuar...';
            return $out;
        }
        $ebamp = $this->tablaFisica('ebamp');
        if ($ebamp === '') {
            $det = $this->ultimoError();
            $out['error'] = 'No está ebamp en vfpmedicos, cartilla ni vfpcomedica.'
                . ($det !== '' ? ' ' . $det : '');
            return $out;
        }
        $cols = $this->columnasDeTabla($ebamp);
        $cMat = $this->colEn($cols, 'matricula');
        $cCod = $this->colEn($cols, 'codigo');
        if ($cMat === null && $cCod === null) {
            $out['error'] = 'ebamp no tiene matricula/codigo.';
            return $out;
        }
        $cNom  = $this->colEn($cols, 'nombre');
        $cConf = $this->colEn($cols, 'confact');
        $cVal  = $this->colEn($cols, 'valereci');
        $cInfo = $this->colEn($cols, 'infoliq');
        $cCat  = $this->colEn($cols, 'categ');
        $select = array();
        if ($cMat !== null) {
            $select[] = '`' . $cMat . '` AS `matricula`';
        }
        if ($cCod !== null) {
            $select[] = '`' . $cCod . '` AS `codigo`';
        }
        if ($cNom !== null) {
            $select[] = '`' . $cNom . '` AS `nombre`';
        }
        if ($cConf !== null) {
            $select[] = '`' . $cConf . '` AS `confact`';
        }
        if ($cVal !== null) {
            $select[] = '`' . $cVal . '` AS `valereci`';
        }
        if ($cInfo !== null) {
            $select[] = '`' . $cInfo . '` AS `infoliq`';
        }
        $wheres = array();
        $params = array();
        if ($cMat !== null) {
            $wheres[] = 'TRIM(`' . $cMat . '`) = :p';
            $params[':p'] = $tpresta;
        }
        if ($cCod !== null) {
            $wheres[] = 'TRIM(`' . $cCod . '`) = :pcod';
            $params[':pcod'] = $tpresta;
        }
        $sql = 'SELECT ' . implode(',', $select) . ' FROM ' . $this->sqlIdent($ebamp)
            . ' WHERE (' . implode(' OR ', $wheres) . ')';
        if ($cCat !== null) {
            $sql .= ' AND TRIM(IFNULL(`' . $cCat . '`,\'\')) <> \'MECM\'';
        }
        $sql .= ' LIMIT 5';
        try {
            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                $info = $this->db->errorInfo();
                $out['error'] = isset($info[2]) ? (string) $info[2] : 'Error al leer ebamp.';
                return $out;
            }
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->tablaError = $e->getMessage();
            $out['error'] = $e->getMessage();
            return $out;
        }
        if (empty($rows)) {
            $out['error'] = 'Prestador no encontrado (ayudaPreN no migrado).';
            return $out;
        }
        $row = $rows[0];
        $out['ok'] = true;
        if (isset($row['matricula']) && trim((string) $row['matricula']) !== '') {
            $out['tpresta'] = trim((string) $row['matricula']);
        } elseif (isset($row['codigo']) && trim((string) $row['codigo']) !== '') {
            $out['tpresta'] = trim((string) $row['codigo']);
        } else {
            $out['tpresta'] = $tpresta;
        }
        $out['nombre'] = isset($row['nombre']) ? (string) $row['nombre'] : '';
        $out['confact'] = $this->esFlag(isset($row['confact']) ? $row['confact'] : '');
        $out['valereci'] = $this->esFlag(isset($row['valereci']) ? $row['valereci'] : '');
        $out['infoliq'] = isset($row['infoliq']) ? (string) $row['infoliq'] : '';
        return $out;
    }

    /**
     * AUDIFACT.Command1: COPY consulta → audifact UNIQUE
     * coprestado+coperiodo+coobrasoc+cosucfac+conrofac.
     * Lee consulta / audifact / detalle. No escribe DBF de trabajo.
     *
     * @param  string $tpresta
     * @param  string $tperiodo
     * @return array
     */
    public function listarCabeceras($tpresta, $tperiodo)
    {
        $tpresta  = trim((string) $tpresta);
        $tperiodo = trim((string) $tperiodo);
        $vacio = array(
            'filas'     => array(),
            'tabla_ok'  => false,
            'tabla'     => '',
            'error'     => '',
        );
        if ($tpresta === '') {
            $vacio['error'] = 'Seleccione un Prestador para continuar...';
            return $vacio;
        }
        $candidatos = array('consulta', 'audifact', 'detalle', 'detconsu', 'audifac2');
        $i = 0;
        $n = count($candidatos);
        $ultimo = '';
        for ($i = 0; $i < $n; $i++) {
            $fis = $this->tablaFisica($candidatos[$i]);
            if ($fis === '') {
                continue;
            }
            $ultimo = $fis;
            $cols = $this->columnasDeTabla($fis);
            $cPre = $this->colEn($cols, 'coprestado');
            $cOs  = $this->colEn($cols, 'coobrasoc');
            $cSuc = $this->colEn($cols, 'cosucfac');
            $cFac = $this->colEn($cols, 'conrofac');
            if ($cPre === null || $cOs === null || $cSuc === null || $cFac === null) {
                continue;
            }
            $grupo = array($cPre, $cOs, $cSuc, $cFac);
            $cPer = $this->colEn($cols, 'coperiodo');
            if ($cPer !== null) {
                $grupo[] = $cPer;
            }
            $select = array();
            $g = 0;
            $ng = count($grupo);
            for ($g = 0; $g < $ng; $g++) {
                $select[] = '`' . $grupo[$g] . '`';
            }
            $extra = array(
                'conomprest', 'cofecfac', 'conetofact', 'coivafact',
                'cototalfac', 'cosegurofa', 'cofecnac', 'cargarap', 'refacnro',
            );
            $e = 0;
            for ($e = 0; $e < 9; $e++) {
                $real = $this->colEn($cols, $extra[$e]);
                if ($real !== null && !in_array($real, $grupo, true)) {
                    $select[] = 'MAX(`' . $real . '`) AS `' . $real . '`';
                }
            }
            $sql = 'SELECT ' . implode(',', $select) . ' FROM ' . $this->sqlIdent($fis)
                . ' WHERE TRIM(`' . $cPre . '`) = :p';
            $params = array(':p' => $tpresta);
            $sql .= ' GROUP BY `' . implode('`,`', $grupo) . '`';
            if ($cPer !== null) {
                $sql .= ' ORDER BY `' . $cPer . '` DESC, `' . $cFac . '` ASC';
            } else {
                $sql .= ' ORDER BY `' . $cFac . '` ASC';
            }
            $sql .= ' LIMIT 500';
            try {
                $stmt = $this->db->prepare($sql);
                $stmt->execute($params);
                $filas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                $this->tablaError = $e->getMessage();
                continue;
            }
            return array(
                'filas'    => $filas,
                'tabla_ok' => true,
                'tabla'    => $fis,
                'error'    => '',
            );
        }
        $vacio['tabla'] = $ultimo;
        $vacio['error'] = $this->tablaError !== ''
            ? $this->tablaError
            : 'No se encuentran registros para ese periodo, prestador... (consulta/audifact).';
        return $vacio;
    }

    /**
     * @param  string $nombre
     * @return string
     */
    /**
     * Login sigue en vfpmedicos. Las DBF Fox pueden estar en cartilla o vfpcomedica.
     *
     * @return string[]
     */
    private function schemasFox()
    {
        return array('', 'cartilla', 'vfpcomedica');
    }

    /**
     * @param  string $qualified vfpcomedica.ebamp o ebamp
     * @return string
     */
    private function sqlIdent($qualified)
    {
        $parts = explode('.', (string) $qualified);
        $bits = array();
        $i = 0;
        $n = count($parts);
        for ($i = 0; $i < $n; $i++) {
            $p = preg_replace('/[^A-Za-z0-9_]/', '', $parts[$i]);
            if ($p !== '') {
                $bits[] = '`' . $p . '`';
            }
        }
        if (empty($bits)) {
            return '';
        }
        return implode('.', $bits);
    }

    /**
     * @param  string $nombre
     * @return string nombre calificado si hace falta (vfpcomedica.ebamp)
     */
    private function tablaFisica($nombre)
    {
        $base = preg_replace('/[^A-Za-z0-9_]/', '', (string) $nombre);
        if ($base === '') {
            return '';
        }
        $schemas = $this->schemasFox();
        $ultimoErr = '';
        $s = 0;
        $ns = count($schemas);
        for ($s = 0; $s < $ns; $s++) {
            $q = ($schemas[$s] === '') ? $base : ($schemas[$s] . '.' . $base);
            $ident = $this->sqlIdent($q);
            if ($ident === '') {
                continue;
            }
            try {
                $probe = $this->db->query('SELECT 1 FROM ' . $ident . ' LIMIT 1');
                if ($probe) {
                    return $q;
                }
            } catch (PDOException $e) {
                $ultimoErr = $e->getMessage();
            }
        }
        if ($ultimoErr !== '') {
            $this->tablaError = $ultimoErr;
        }
        return '';
    }

    /**
     * @param  string[] $cols
     * @param  string   $nombre
     * @return string|null
     */
    private function colEn($cols, $nombre)
    {
        return $this->colReal($nombre, $cols);
    }

    /**
     * @param  mixed $v
     * @return bool
     */
    private function esFlag($v)
    {
        if ($v === true || $v === 1) {
            return true;
        }
        $s = strtoupper(trim((string) $v));
        return ($s === '1' || $s === 'T' || $s === '.T.' || $s === 'S' || $s === 'SI' || $s === 'TRUE');
    }
}
