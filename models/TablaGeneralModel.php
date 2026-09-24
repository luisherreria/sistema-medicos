<?php
/**
 * models/TablaGeneralModel.php
 * CRUD ultra-dinámico: tabla maestra `tablas` (filtro tatipo) o tablas independientes.
 * PK asumida: id INT AUTO_INCREMENT.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

class TablaGeneralModel
{
    /** @var PDO */
    private $db;

    /** Tablas físicas permitidas (anti-injection). */
    private const TABLAS_PERMITIDAS = [
        'tablas', 'grupocarti', 'gruponn', 'gruposup', 'nomentp', 'diagno',
    ];

    /** Columnas editables permitidas. */
    private const CAMPOS_PERMITIDOS = [
        'tacodigo', 'tadescrip', 'taprovin', 'vista',
        'tatipodeb', 'taselecc', 'taimporte',
        'tagrupo', 'isonco', 'isvih',
    ];

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * @param  string      $tabla
     * @param  string|null $tatipo
     * @param  string[]    $campos
     * @return array<int, array<string, mixed>>
     */
    public function listar(
        string $tabla,
        $tatipo,
        string $busqueda = '',
        int $pagina = 1,
        int $limite = 50,
        array $campos = [],
        string $ordenCol = 'tacodigo',
        string $ordenDir = 'ASC'
    ): array {
        $tabla  = $this->tablaSegura($tabla);
        $pagina = max(1, $pagina);
        $limite = max(1, min(500, $limite));
        $offset = ($pagina - 1) * $limite;

        $ordenCol = $this->columnaSegura($ordenCol, $campos);
        $ordenDir = strtoupper($ordenDir) === 'DESC' ? 'DESC' : 'ASC';

        [$where, $params] = $this->buildWhere($tatipo, $busqueda, $campos);
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
     * @param string|null $tatipo
     * @param string[]    $campos
     */
    public function contar(string $tabla, $tatipo, string $busqueda = '', array $campos = []): int
    {
        $tabla = $this->tablaSegura($tabla);
        [$where, $params] = $this->buildWhere($tatipo, $busqueda, $campos);

        $stmt = $this->db->prepare("SELECT COUNT(*) FROM `{$tabla}` {$where}");
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    /**
     * @param  string|null $tatipo
     * @param  string[]    $campos
     * @return array<string, mixed>|null
     */
    public function obtener(string $tabla, int $id, $tatipo = null, array $campos = [])
    {
        $tabla  = $this->tablaSegura($tabla);
        $select = $this->sqlSelect($campos);
        $sql    = "SELECT {$select} FROM `{$tabla}` WHERE id = :id";
        $params = [':id' => $id];

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
     * INSERT dinámico. Si $tatipo no es null, se incluye siempre.
     *
     * @param  array<string, mixed> $datos
     * @param  string|null          $tatipo
     */
    public function insertar(string $tabla, array $datos, $tatipo = null): int
    {
        $tabla     = $this->tablaSegura($tabla);
        $filtrados = $this->filtrarCampos($datos);

        if ($tatipo !== null && $tatipo !== '') {
            $filtrados['tatipo'] = $tatipo;
        }

        if (empty($filtrados)) {
            throw new InvalidArgumentException('No hay campos para insertar.');
        }

        $cols = $phs = $params = [];
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
     * @param array<string, mixed> $datos
     */
    public function actualizar(string $tabla, int $id, array $datos): bool
    {
        $tabla = $this->tablaSegura($tabla);
        if ($id <= 0) {
            return false;
        }

        $filtrados = $this->filtrarCampos($datos);
        unset($filtrados['tatipo'], $filtrados['id']);
        if (empty($filtrados)) {
            return false;
        }

        $sets   = [];
        $params = [':id' => $id];
        foreach ($filtrados as $col => $val) {
            $sets[] = "`{$col}` = :{$col}";
            $params[':' . $col] = $val;
        }

        $sql  = 'UPDATE `' . $tabla . '` SET ' . implode(', ', $sets) . ' WHERE id = :id';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->rowCount() >= 0;
    }

    /**
     * @param string|null $tatipo
     */
    public function eliminar(string $tabla, int $id, $tatipo = null): bool
    {
        $tabla = $this->tablaSegura($tabla);
        if ($id <= 0) {
            return false;
        }

        $sql    = 'DELETE FROM `' . $tabla . '` WHERE id = :id';
        $params = [':id' => $id];

        if ($tatipo !== null && $tatipo !== '') {
            $sql .= ' AND tatipo = :tatipo';
            $params[':tatipo'] = $tatipo;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->rowCount() > 0;
    }

    /**
     * @param string|null $tatipo
     */
    public function existeCodigo(string $tabla, $tatipo, string $codigo, int $exceptoId = 0): bool
    {
        $tabla  = $this->tablaSegura($tabla);
        $codigo = trim($codigo);
        if ($codigo === '') {
            return false;
        }

        $sql    = 'SELECT COUNT(*) FROM `' . $tabla . '` WHERE tacodigo = :codigo';
        $params = [':codigo' => $codigo];

        if ($tatipo !== null && $tatipo !== '') {
            $sql .= ' AND tatipo = :tatipo';
            $params[':tatipo'] = $tatipo;
        }
        if ($exceptoId > 0) {
            $sql .= ' AND id <> :id';
            $params[':id'] = $exceptoId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn() > 0;
    }

    private function tablaSegura(string $tabla): string
    {
        $tabla = strtolower(trim($tabla));
        if (!in_array($tabla, self::TABLAS_PERMITIDAS, true)) {
            throw new InvalidArgumentException('Tabla no permitida: ' . $tabla);
        }
        return $tabla;
    }

    /**
     * @param  string[] $campos
     */
    private function sqlSelect(array $campos): string
    {
        $parts = ['id'];
        $seen  = ['id' => true];
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
     * @param  array<string, mixed> $datos
     * @return array<string, mixed>
     */
    private function filtrarCampos(array $datos): array
    {
        $out = [];
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

    /** @param mixed $val */
    private function normalizarValor(string $col, $val)
    {
        $checks = ['vista', 'taselecc', 'isonco', 'isvih'];
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
     * @param string[] $campos
     */
    private function columnaSegura(string $col, array $campos): string
    {
        $col = strtolower($col);
        $ok  = array_merge(['id'], self::CAMPOS_PERMITIDOS);
        if (!in_array($col, $ok, true)) {
            return in_array('tacodigo', $campos, true) ? 'tacodigo' : 'id';
        }
        return $col;
    }

    /**
     * @param  string|null $tatipo
     * @param  string[]    $camposBusqueda
     * @return array{0: string, 1: array<string, string>}
     */
    private function buildWhere($tatipo, string $busqueda, array $camposBusqueda): array
    {
        $where  = '';
        $params = [];

        if ($tatipo !== null && $tatipo !== '') {
            $where  = 'WHERE tatipo = :tatipo';
            $params[':tatipo'] = $tatipo;
        }

        $busqueda = trim($busqueda);
        if ($busqueda === '') {
            return [$where, $params];
        }

        $colsLike = [];
        foreach ($camposBusqueda as $c) {
            $c = strtolower((string) $c);
            if (in_array($c, self::CAMPOS_PERMITIDOS, true)) {
                $colsLike[] = $c;
            }
        }
        if (empty($colsLike)) {
            $colsLike = ['tacodigo', 'tadescrip'];
        }

        $parts = [];
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

        return [$where, $params];
    }
}
