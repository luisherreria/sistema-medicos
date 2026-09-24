<?php
/**
 * models/PlantillaEmail.php
 * ABM de la tabla `t_plantillas_emails` (Cabecera Mails).
 *
 * Columnas: id, codigo, nombre_uso, asunto, cuerpo, destinatarios, activo
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

class PlantillaEmail
{
    private PDO $db;

    public function __construct()
    {
        $this->db = getDBConnection();
    }

    /**
     * Listado para la grilla (DataTables / filtros).
     *
     * @param  array{busqueda?:string,activo?:string,pagina?:int,por_pagina?:int} $filtros
     * @return array{total:int,paginas:int,pagina:int,datos:array}
     */
    public function listar(array $filtros = []): array
    {
        $busqueda  = trim((string) ($filtros['busqueda'] ?? ''));
        $activo    = trim((string) ($filtros['activo'] ?? ''));
        $pagina    = max(1, (int) ($filtros['pagina'] ?? 1));
        $porPagina = max(1, min(500, (int) ($filtros['por_pagina'] ?? 100)));

        [$where, $params] = $this->buildWhere($busqueda, $activo);

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

        return [
            'total'   => $total,
            'paginas' => $paginas,
            'pagina'  => $pagina,
            'datos'   => $stmt->fetchAll(PDO::FETCH_ASSOC),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function obtener(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $stmt = $this->db->prepare(
            "SELECT id, codigo, nombre_uso, asunto, cuerpo, destinatarios, activo
             FROM t_plantillas_emails
             WHERE id = :id
             LIMIT 1"
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? $row : null;
    }

    /**
     * @param  array<string, mixed> $datos
     */
    public function insertar(array $datos): int
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
     * @param  array<string, mixed> $datos
     */
    public function actualizar(int $id, array $datos): bool
    {
        if ($id <= 0) {
            return false;
        }

        $params       = $this->bindDatos($datos);
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

    public function eliminar(int $id): bool
    {
        if ($id <= 0) {
            return false;
        }

        $stmt = $this->db->prepare("DELETE FROM t_plantillas_emails WHERE id = :id");
        $stmt->execute([':id' => $id]);

        return $stmt->rowCount() > 0;
    }

    public function existeCodigo(string $codigo, int $exceptoId = 0): bool
    {
        $codigo = trim($codigo);
        if ($codigo === '') {
            return false;
        }

        $sql    = "SELECT COUNT(*) FROM t_plantillas_emails WHERE codigo = :codigo";
        $params = [':codigo' => $codigo];

        if ($exceptoId > 0) {
            $sql .= " AND id <> :id";
            $params[':id'] = $exceptoId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * @param  array<string, mixed> $datos
     * @return array<string, mixed>
     */
    private function bindDatos(array $datos): array
    {
        return [
            ':codigo'        => substr(trim((string) ($datos['codigo'] ?? '')), 0, 50),
            ':nombre_uso'    => substr(trim((string) ($datos['nombre_uso'] ?? '')), 0, 150),
            ':asunto'        => substr(trim((string) ($datos['asunto'] ?? '')), 0, 255),
            ':cuerpo'        => (string) ($datos['cuerpo'] ?? ''),
            ':destinatarios' => substr(trim((string) ($datos['destinatarios'] ?? '')), 0, 255),
            ':activo'        => !empty($datos['activo']) ? 1 : 0,
        ];
    }

    /**
     * @return array{0:string,1:array<string,string>}
     */
    private function buildWhere(string $busqueda, string $activo): array
    {
        $conds  = [];
        $params = [];

        if ($busqueda !== '') {
            $like = '%' . $busqueda . '%';
            $conds[] = "(nombre_uso LIKE :b1 OR codigo LIKE :b2 OR asunto LIKE :b3 OR destinatarios LIKE :b4)";
            $params[':b1'] = $like;
            $params[':b2'] = $like;
            $params[':b3'] = $like;
            $params[':b4'] = $like;
        }

        if ($activo === '1' || $activo === '0') {
            $conds[]          = "activo = :activo";
            $params[':activo'] = $activo;
        }

        $where = $conds ? (' WHERE ' . implode(' AND ', $conds)) : '';
        return [$where, $params];
    }
}
