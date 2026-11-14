<?php
/**
 * models/PlantillaEmail.php
 * ABM de la tabla `t_plantillas_emails` (Cabecera Mails).
 * Dialecto PHP 5.6.
 *
 * Columnas: id, codigo, nombre_uso, asunto, cuerpo, destinatarios, activo
 */

require_once __DIR__ . '/../config/database.php';

class PlantillaEmail
{
    /** @var PDO */
    private $db;

    public function __construct()
    {
        $this->db = getDBConnection();
    }

    /**
     * Listado para la grilla (DataTables / filtros).
     *
     * @param  array $filtros
     * @return array
     */
    public function listar(array $filtros = array())
    {
        $busqueda  = isset($filtros['busqueda']) ? trim((string) $filtros['busqueda']) : '';
        $activo    = isset($filtros['activo']) ? trim((string) $filtros['activo']) : '';
        $pagina    = isset($filtros['pagina']) ? max(1, (int) $filtros['pagina']) : 1;
        $porPagina = isset($filtros['por_pagina']) ? max(1, min(500, (int) $filtros['por_pagina'])) : 100;

        $built  = $this->buildWhere($busqueda, $activo);
        $where  = $built[0];
        $params = $built[1];

        $stmtCnt = $this->db->prepare("SELECT COUNT(*) FROM t_plantillas_emails{$where}");
        $stmtCnt->execute($params);
        $total   = (int) $stmtCnt->fetchColumn();
        $paginas = $total > 0 ? (int) ceil($total / $porPagina) : 1;
        $offset  = ($pagina - 1) * $porPagina;

        $sql = "SELECT id, codigo, nombre_uso, asunto, destinatarios, activo
                FROM t_plantillas_emails
                {$where}
                ORDER BY nombre_uso ASC, codigo ASC
                LIMIT :limite OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limite', $porPagina, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return array(
            'total'   => $total,
            'paginas' => $paginas,
            'pagina'  => $pagina,
            'datos'   => $stmt->fetchAll(PDO::FETCH_ASSOC),
        );
    }

    /**
     * @param  int $id
     * @return array|null
     */
    public function obtener($id)
    {
        $id = (int) $id;
        if ($id <= 0) {
            return null;
        }

        $stmt = $this->db->prepare(
            "SELECT id, codigo, nombre_uso, asunto, cuerpo, destinatarios, activo
             FROM t_plantillas_emails
             WHERE id = :id
             LIMIT 1"
        );
        $stmt->execute(array(':id' => $id));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? $row : null;
    }

    /**
     * @param  array $datos
     * @return int
     */
    public function insertar(array $datos)
    {
        $stmt = $this->db->prepare(
            "INSERT INTO t_plantillas_emails
                (codigo, nombre_uso, asunto, cuerpo, destinatarios, activo)
             VALUES
                (:codigo, :nombre_uso, :asunto, :cuerpo, :destinatarios, :activo)"
        );
        $stmt->execute($this->bindDatos($datos));

        return (int) $this->db->lastInsertId();
    }

    /**
     * @param  int   $id
     * @param  array $datos
     * @return bool
     */
    public function actualizar($id, array $datos)
    {
        $id = (int) $id;
        if ($id <= 0) {
            return false;
        }

        $params = $this->bindDatos($datos);
        $params[':id'] = $id;

        $stmt = $this->db->prepare(
            "UPDATE t_plantillas_emails
             SET codigo        = :codigo,
                 nombre_uso    = :nombre_uso,
                 asunto        = :asunto,
                 cuerpo        = :cuerpo,
                 destinatarios = :destinatarios,
                 activo        = :activo
             WHERE id = :id"
        );
        $stmt->execute($params);

        return $stmt->rowCount() >= 0;
    }

    /**
     * @param  int $id
     * @return bool
     */
    public function eliminar($id)
    {
        $id = (int) $id;
        if ($id <= 0) {
            return false;
        }

        $stmt = $this->db->prepare("DELETE FROM t_plantillas_emails WHERE id = :id");
        $stmt->execute(array(':id' => $id));

        return $stmt->rowCount() > 0;
    }

    /**
     * @param  string $codigo
     * @param  int    $exceptoId
     * @return bool
     */
    public function existeCodigo($codigo, $exceptoId = 0)
    {
        $codigo = trim($codigo);
        if ($codigo === '') {
            return false;
        }

        $sql    = "SELECT COUNT(*) FROM t_plantillas_emails WHERE codigo = :codigo";
        $params = array(':codigo' => $codigo);

        if ((int) $exceptoId > 0) {
            $sql .= " AND id <> :id";
            $params[':id'] = (int) $exceptoId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * @param  array $datos
     * @return array
     */
    private function bindDatos(array $datos)
    {
        $codigo = isset($datos['codigo']) ? (string) $datos['codigo'] : '';
        $nombre = isset($datos['nombre_uso']) ? (string) $datos['nombre_uso'] : '';
        $asunto = isset($datos['asunto']) ? (string) $datos['asunto'] : '';
        $cuerpo = isset($datos['cuerpo']) ? (string) $datos['cuerpo'] : '';
        $dest   = isset($datos['destinatarios']) ? (string) $datos['destinatarios'] : '';

        return array(
            ':codigo'        => substr(trim($codigo), 0, 50),
            ':nombre_uso'    => substr(trim($nombre), 0, 150),
            ':asunto'        => substr(trim($asunto), 0, 255),
            ':cuerpo'        => $cuerpo,
            ':destinatarios' => substr(trim($dest), 0, 255),
            ':activo'        => !empty($datos['activo']) ? 1 : 0,
        );
    }

    /**
     * @param  string $busqueda
     * @param  string $activo
     * @return array  array(where, params)
     */
    private function buildWhere($busqueda, $activo)
    {
        $conds  = array();
        $params = array();

        if ($busqueda !== '') {
            $like = '%' . $busqueda . '%';
            $conds[] = "(nombre_uso LIKE :b1 OR codigo LIKE :b2 OR asunto LIKE :b3 OR destinatarios LIKE :b4)";
            $params[':b1'] = $like;
            $params[':b2'] = $like;
            $params[':b3'] = $like;
            $params[':b4'] = $like;
        }

        if ($activo === '1' || $activo === '0') {
            $conds[]           = "activo = :activo";
            $params[':activo'] = $activo;
        }

        $where = $conds ? (' WHERE ' . implode(' AND ', $conds)) : '';
        return array($where, $params);
    }
}
