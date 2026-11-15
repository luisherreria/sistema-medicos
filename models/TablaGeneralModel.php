<?php
/**
 * models/TablaGeneralModel.php
 * CRUD dinámico: tabla maestra `tablas` (filtro tatipo) o tablas independientes.
 * Dialecto PHP 5.6. PK asumida: id INT AUTO_INCREMENT.
 */

require_once __DIR__ . '/../config/schema.php';

class TablaGeneralModel
{
    /** @var PDO */
    private $db;

    /** Tablas físicas permitidas (anti-injection). */
    const TABLAS_PERMITIDAS = array(
        'tablas', 'grupocarti', 'gruponn', 'gruposup', 'nomentp', 'diagno',
    );

    /** Columnas editables permitidas. */
    const CAMPOS_PERMITIDOS = array(
        'tacodigo', 'tadescrip', 'taprovin', 'vista',
        'tatipodeb', 'taselecc', 'taimporte',
        'tagrupo', 'isonco', 'isvih',
    );

    public function __construct()
    {
        $this->db = appDb();
    }

    /**
     * @param  string      $tabla
     * @param  string|null $tatipo
     * @param  string      $busqueda
     * @param  int         $pagina
     * @param  int         $limite
     * @param  array       $campos
     * @param  string      $ordenCol
     * @param  string      $ordenDir
     * @return array
     */
    public function listar($tabla, $tatipo, $busqueda = '', $pagina = 1, $limite = 50, array $campos = array(), $ordenCol = 'tacodigo', $ordenDir = 'ASC')
    {
        $tabla  = $this->tablaSegura($tabla);
        $pagina = max(1, (int) $pagina);
        $limite = max(1, min(500, (int) $limite));
        $offset = ($pagina - 1) * $limite;

        $ordenCol = $this->columnaSegura($ordenCol, $campos);
        $ordenDir = strtoupper($ordenDir) === 'DESC' ? 'DESC' : 'ASC';

        $built  = $this->buildWhere($tatipo, $busqueda, $campos);
        $where  = $built[0];
        $params = $built[1];
        $select = $this->sqlSelect($campos);

        $sql = "SELECT {$select}
                FROM `{$tabla}`
                {$where}
                ORDER BY {$ordenCol} {$ordenDir}, id ASC
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
     * @param  string      $tabla
     * @param  string|null $tatipo
     * @param  string      $busqueda
     * @param  array       $campos
     * @return int
     */
    public function contar($tabla, $tatipo, $busqueda = '', array $campos = array())
    {
        $tabla = $this->tablaSegura($tabla);
        $built = $this->buildWhere($tatipo, $busqueda, $campos);
        $where  = $built[0];
        $params = $built[1];

        $stmt = $this->db->prepare("SELECT COUNT(*) FROM `{$tabla}` {$where}");
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    /**
     * @param  string      $tabla
     * @param  int         $id
     * @param  string|null $tatipo
     * @param  array       $campos
     * @return array|null
     */
    public function obtener($tabla, $id, $tatipo = null, array $campos = array())
    {
        $tabla  = $this->tablaSegura($tabla);
        $select = $this->sqlSelect($campos);
        $sql    = "SELECT {$select} FROM `{$tabla}` WHERE id = :id";
        $params = array(':id' => (int) $id);

        if ($tatipo !== null && $tatipo !== '') {
            $sql .= ' AND tatipo = :tatipo';
            $params[':tatipo'] = $tatipo;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    /**
     * @param  string      $tabla
     * @param  array       $datos
     * @param  string|null $tatipo
     * @return int
     */
    public function insertar($tabla, array $datos, $tatipo = null)
    {
        $tabla     = $this->tablaSegura($tabla);
        $filtrados = $this->filtrarCampos($datos);

        if ($tatipo !== null && $tatipo !== '') {
            $filtrados['tatipo'] = $tatipo;
        }

        if (empty($filtrados)) {
            throw new InvalidArgumentException('No hay campos para insertar.');
        }

        $cols = array();
        $phs  = array();
        $params = array();
        foreach ($filtrados as $col => $val) {
            $cols[] = '`' . $col . '`';
            $phs[]  = ':' . $col;
            $params[':' . $col] = $val;
        }

        $sql  = 'INSERT INTO `' . $tabla . '` (' . implode(', ', $cols) . ')
                 VALUES (' . implode(', ', $phs) . ')';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (int) $this->db->lastInsertId();
    }

    /**
     * @param  string $tabla
     * @param  int    $id
     * @param  array  $datos
     * @return bool
     */
    public function actualizar($tabla, $id, array $datos)
    {
        $tabla = $this->tablaSegura($tabla);
        $id    = (int) $id;
        if ($id <= 0) {
            return false;
        }

        $filtrados = $this->filtrarCampos($datos);
        unset($filtrados['tatipo'], $filtrados['id']);
        if (empty($filtrados)) {
            return false;
        }

        $sets   = array();
        $params = array(':id' => $id);
        foreach ($filtrados as $col => $val) {
            $sets[] = '`' . $col . '` = :' . $col;
            $params[':' . $col] = $val;
        }

        $sql  = 'UPDATE `' . $tabla . '` SET ' . implode(', ', $sets) . ' WHERE id = :id';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->rowCount() >= 0;
    }

    /**
     * @param  string      $tabla
     * @param  int         $id
     * @param  string|null $tatipo
     * @return bool
     */
    public function eliminar($tabla, $id, $tatipo = null)
    {
        $tabla = $this->tablaSegura($tabla);
        $id    = (int) $id;
        if ($id <= 0) {
            return false;
        }

        $sql    = 'DELETE FROM `' . $tabla . '` WHERE id = :id';
        $params = array(':id' => $id);

        if ($tatipo !== null && $tatipo !== '') {
            $sql .= ' AND tatipo = :tatipo';
            $params[':tatipo'] = $tatipo;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->rowCount() > 0;
    }

    /**
     * @param  string      $tabla
     * @param  string|null $tatipo
     * @param  string      $codigo
     * @param  int         $exceptoId
     * @return bool
     */
    public function existeCodigo($tabla, $tatipo, $codigo, $exceptoId = 0)
    {
        $tabla  = $this->tablaSegura($tabla);
        $codigo = trim($codigo);
        if ($codigo === '') {
            return false;
        }

        $sql    = 'SELECT COUNT(*) FROM `' . $tabla . '` WHERE tacodigo = :codigo';
        $params = array(':codigo' => $codigo);

        if ($tatipo !== null && $tatipo !== '') {
            $sql .= ' AND tatipo = :tatipo';
            $params[':tatipo'] = $tatipo;
        }
        if ((int) $exceptoId > 0) {
            $sql .= ' AND id <> :id';
            $params[':id'] = (int) $exceptoId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * @param  string $tabla
     * @return string
     */
    private function tablaSegura($tabla)
    {
        $tabla = strtolower(trim($tabla));
        if (!in_array($tabla, self::TABLAS_PERMITIDAS, true)) {
            throw new InvalidArgumentException('Tabla no permitida: ' . $tabla);
        }
        return $tabla;
    }

    /**
     * @param  array $campos
     * @return string
     */
    private function sqlSelect(array $campos)
    {
        $parts = array('id');
        $seen  = array('id' => true);
        foreach ($campos as $c) {
            $c = strtolower((string) $c);
            if (isset($seen[$c]) || !in_array($c, self::CAMPOS_PERMITIDOS, true)) {
                continue;
            }
            $seen[$c] = true;
            $parts[]  = $c;
        }
        return implode(', ', $parts);
    }

    /**
     * @param  array $datos
     * @return array
     */
    private function filtrarCampos(array $datos)
    {
        $out = array();
        foreach ($datos as $col => $val) {
            $col = strtolower((string) $col);
            if ($col === 'id' || $col === 'tatipo') {
                continue;
            }
            if (!in_array($col, self::CAMPOS_PERMITIDOS, true)) {
                continue;
            }
            $out[$col] = $this->normalizarValor($col, $val);
        }
        return $out;
    }

    /**
     * @param  string $col
     * @param  mixed  $val
     * @return mixed
     */
    private function normalizarValor($col, $val)
    {
        $checks = array('vista', 'taselecc', 'isonco', 'isvih');
        if ($val === null) {
            return in_array($col, $checks, true) ? 0 : '';
        }
        if ($col === 'taimporte') {
            $num = str_replace(',', '.', (string) $val);
            return is_numeric($num) ? round((float) $num, 2) : 0.0;
        }
        if (in_array($col, $checks, true)) {
            return ((int) $val) ? 1 : 0;
        }
        $str = trim((string) $val);
        if ($col === 'tacodigo') {
            return substr($str, 0, 38);
        }
        if ($col === 'tadescrip') {
            return substr($str, 0, 100);
        }
        if ($col === 'taprovin') {
            return substr($str, 0, 20);
        }
        if ($col === 'tatipodeb') {
            return substr($str, 0, 1);
        }
        if ($col === 'tagrupo') {
            return substr($str, 0, 20);
        }
        return $str;
    }

    /**
     * @param  string $col
     * @param  array  $campos
     * @return string
     */
    private function columnaSegura($col, array $campos)
    {
        $col = strtolower($col);
        $ok  = array_merge(array('id'), self::CAMPOS_PERMITIDOS);
        if (!in_array($col, $ok, true)) {
            return in_array('tacodigo', $campos, true) ? 'tacodigo' : 'id';
        }
        return $col;
    }

    /**
     * @param  string|null $tatipo
     * @param  string      $busqueda
     * @param  array       $camposBusqueda
     * @return array       array(where, params)
     */
    private function buildWhere($tatipo, $busqueda, array $camposBusqueda)
    {
        $where  = '';
        $params = array();

        if ($tatipo !== null && $tatipo !== '') {
            $where  = 'WHERE tatipo = :tatipo';
            $params[':tatipo'] = $tatipo;
        }

        $busqueda = trim($busqueda);
        if ($busqueda === '') {
            return array($where, $params);
        }

        $colsLike = array();
        foreach ($camposBusqueda as $c) {
            $c = strtolower((string) $c);
            if (in_array($c, self::CAMPOS_PERMITIDOS, true)) {
                $colsLike[] = $c;
            }
        }
        if (empty($colsLike)) {
            $colsLike = array('tacodigo', 'tadescrip');
        }

        $parts = array();
        $like  = '%' . $busqueda . '%';
        foreach ($colsLike as $i => $col) {
            $ph = ':q' . $i;
            $parts[] = "CAST({$col} AS CHAR) LIKE {$ph}";
            $params[$ph] = $like;
        }
        $parts[] = 'CAST(id AS CHAR) LIKE :qid';
        $params[':qid'] = $like;

        $clause = '(' . implode(' OR ', $parts) . ')';
        $where  = ($where === '') ? ('WHERE ' . $clause) : ($where . ' AND ' . $clause);

        return array($where, $params);
    }
}
