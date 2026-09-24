<?php
/**
 * models/PlantillasEmailsModel.php
 * ABM de t_plantillas_emails (cabecera / plantillas de mails).
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

class PlantillasEmailsModel
{
    /** @var PDO */
    private $db;

    private const CAMPOS = [
        'codigo', 'nombre_uso', 'asunto', 'cuerpo',
        'destinatarios', 'cc', 'cco', 'activo',
    ];

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listar(string $busqueda = '', int $pagina = 1, int $limite = 50): array
    {
        $pagina = max(1, $pagina);
        $limite = max(1, min(200, $limite));
        $offset = ($pagina - 1) * $limite;

        [$where, $params] = $this->buildWhere($busqueda);

        $sql = "SELECT id, codigo, nombre_uso, asunto, cuerpo, destinatarios, cc, cco, activo
                FROM t_plantillas_emails
                {$where}
                ORDER BY codigo ASC, id ASC
                LIMIT :limite OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, PDO::PARAM_STR);
        }
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function contar(string $busqueda = ''): int
    {
        [$where, $params] = $this->buildWhere($busqueda);
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM t_plantillas_emails {$where}");
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    /** @return array<string, mixed>|null */
    public function obtener(int $id)
    {
        $stmt = $this->db->prepare(
            'SELECT id, codigo, nombre_uso, asunto, cuerpo, destinatarios, cc, cco, activo
             FROM t_plantillas_emails WHERE id = :id'
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row !== false ? $row : null;
    }

    /** @param array<string, mixed> $datos */
    public function insertar(array $datos): int
    {
        $datos = $this->filtrar($datos);
        $cols = $phs = $params = [];
        foreach ($datos as $col => $val) {
            $cols[] = '`' . $col . '`';
            $phs[]  = ':' . $col;
            $params[':' . $col] = $val;
        }
        $sql = 'INSERT INTO t_plantillas_emails (' . implode(', ', $cols) . ')
                VALUES (' . implode(', ', $phs) . ')';
        $this->db->prepare($sql)->execute($params);
        return (int) $this->db->lastInsertId();
    }

    /** @param array<string, mixed> $datos */
    public function actualizar(int $id, array $datos): bool
    {
        if ($id <= 0) {
            return false;
        }
        $datos = $this->filtrar($datos);
        unset($datos['id']);
        if (empty($datos)) {
            return false;
        }
        $sets   = [];
        $params = [':id' => $id];
        foreach ($datos as $col => $val) {
            $sets[] = "`{$col}` = :{$col}";
            $params[':' . $col] = $val;
        }
        $sql = 'UPDATE t_plantillas_emails SET ' . implode(', ', $sets) . ' WHERE id = :id';
        $this->db->prepare($sql)->execute($params);
        return true;
    }

    public function eliminar(int $id): bool
    {
        if ($id <= 0) {
            return false;
        }
        $stmt = $this->db->prepare('DELETE FROM t_plantillas_emails WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public function existeCodigo(string $codigo, int $exceptoId = 0): bool
    {
        $sql    = 'SELECT COUNT(*) FROM t_plantillas_emails WHERE codigo = :codigo';
        $params = [':codigo' => $codigo];
        if ($exceptoId > 0) {
            $sql .= ' AND id <> :id';
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
    private function filtrar(array $datos): array
    {
        $out = [];
        foreach (self::CAMPOS as $col) {
            if (!array_key_exists($col, $datos)) {
                continue;
            }
            $val = $datos[$col];
            if ($col === 'activo') {
                $out[$col] = ((int) $val) ? 1 : 0;
                continue;
            }
            $str = trim((string) $val);
            if ($col === 'cc' || $col === 'cco') {
                $out[$col] = ($str === '') ? null : $str;
                continue;
            }
            $out[$col] = $str;
        }
        return $out;
    }

    /**
     * @return array{0: string, 1: array<string, string>}
     */
    private function buildWhere(string $busqueda): array
    {
        $busqueda = trim($busqueda);
        if ($busqueda === '') {
            return ['', []];
        }
        $like = '%' . $busqueda . '%';
        return [
            'WHERE (codigo LIKE :q OR nombre_uso LIKE :q OR asunto LIKE :q OR destinatarios LIKE :q)',
            [':q' => $like],
        ];
    }
}
