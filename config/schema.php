<?php
/**
 * config/schema.php
 * Base de la app: vfpcomedica. Toda tabla se califica `vfpcomedica`.`nombre`.
 * Dialecto PHP 5.6.
 *
 * Login/roles/permisos que no estén en vfpcomedica se copian desde vfpmedicos
 * (setup/copiar_login_vfpcomedica.php). No usar cartilla ni vfpmedicos en SQL.
 */

if (!defined('DB_APP')) {
    define('DB_APP', 'vfpcomedica');
}
if (!defined('DB_LOGIN_ORIGEN')) {
    define('DB_LOGIN_ORIGEN', 'vfpmedicos');
}

if (!function_exists('dbTable')) {
    /**
     * @param  string $nombre
     * @return string `vfpcomedica`.`tabla`
     */
    function dbTable($nombre)
    {
        $base = preg_replace('/[^A-Za-z0-9_]/', '', (string) $nombre);
        if ($base === '') {
            return '';
        }
        return '`' . DB_APP . '`.`' . $base . '`';
    }
}

if (!function_exists('dbAppTables')) {
    /**
     * Nombres físicos usados por la app (más largos primero).
     *
     * @return string[]
     */
    function dbAppTables()
    {
        static $sorted = null;
        if ($sorted !== null) {
            return $sorted;
        }
        $t = array(
            'users_permissions',
            't_plantillas_emails',
            'prestadores_info_liquidaciones',
            'prestadores_observaciones',
            'telegram_contactos',
            'telegram_messages',
            'telegram_chats',
            'auditoria_log',
            'v_categorias',
            'v_localidad',
            'v_grupoweb',
            'v_empresas',
            'v_bancos',
            'v_grsub',
            'v_zonas',
            'permisos',
            'users',
            'roles',
            'grupocarti',
            'gruposup',
            'gruponn',
            'nomentp',
            'diagno',
            'tablas',
            'ebamp',
            'obramed',
            'obrasoc',
            'sucprest',
            'praespsu',
            'pracespe',
            'adjctto',
            'excluos',
            'novedade',
            'consulta',
            'audifact',
            'audifac2',
            'audif2',
            'detalle',
            'detconsu',
            'eventos',
        );
        $out = array();
        $i = 0;
        $n = count($t);
        for ($i = 0; $i < $n; $i++) {
            $name = preg_replace('/[^A-Za-z0-9_]/', '', $t[$i]);
            if ($name !== '') {
                $out[] = $name;
            }
        }
        usort($out, 'dbAppTablesLen');
        $sorted = $out;
        return $sorted;
    }
}

if (!function_exists('dbAppTablesLen')) {
    /**
     * @param  string $a
     * @param  string $b
     * @return int
     */
    function dbAppTablesLen($a, $b)
    {
        $la = strlen($a);
        $lb = strlen($b);
        if ($la === $lb) {
            return 0;
        }
        return ($la > $lb) ? -1 : 1;
    }
}

if (!function_exists('dbSql')) {
    /**
     * Reescribe SQL para usar solo vfpcomedica.tabla.
     *
     * @param  string $sql
     * @return string
     */
    function dbSql($sql)
    {
        $sql = (string) $sql;
        $db  = DB_APP;
        $sql = preg_replace('/\b(?:cartilla|vfpmedicos)\s*\.\s*/i', $db . '.', $sql);
        $tables = dbAppTables();
        $i = 0;
        $n = count($tables);
        for ($i = 0; $i < $n; $i++) {
            $t = $tables[$i];
            $ident = dbTable($t);
            $sql = preg_replace(
                '/(?<![A-Za-z0-9_`\\.])`?' . preg_quote($t, '/') . '`?(?![A-Za-z0-9_`])/i',
                $ident,
                $sql
            );
        }
        return $sql;
    }
}

if (!class_exists('AppPdo', false)) {
    /**
     * PDO decorator: prepare/query/exec califican tablas a vfpcomedica.
     */
    class AppPdo
    {
        /** @var PDO */
        private $pdo;

        public function __construct($pdo)
        {
            $this->pdo = $pdo;
        }

        public function prepare($statement, $options = array())
        {
            return $this->pdo->prepare(dbSql($statement), $options);
        }

        public function query($statement)
        {
            $args = func_get_args();
            $args[0] = dbSql($statement);
            return call_user_func_array(array($this->pdo, 'query'), $args);
        }

        public function exec($statement)
        {
            return $this->pdo->exec(dbSql($statement));
        }

        public function lastInsertId($name = null)
        {
            return $this->pdo->lastInsertId($name);
        }

        public function setAttribute($key, $value)
        {
            return $this->pdo->setAttribute($key, $value);
        }

        public function beginTransaction()
        {
            return $this->pdo->beginTransaction();
        }

        public function commit()
        {
            return $this->pdo->commit();
        }

        public function rollBack()
        {
            return $this->pdo->rollBack();
        }

        public function errorInfo()
        {
            return $this->pdo->errorInfo();
        }

        public function quote($string, $type = PDO::PARAM_STR)
        {
            return $this->pdo->quote($string, $type);
        }

        public function __call($name, $args)
        {
            return call_user_func_array(array($this->pdo, $name), $args);
        }
    }
}

if (!function_exists('appDb')) {
    /**
     * Conexión de la app. El DSN de config/database.php puede seguir en
     * vfpmedicos; el SQL siempre apunta a vfpcomedica.
     *
     * @return AppPdo
     */
    function appDb()
    {
        static $wrap = null;
        if ($wrap !== null) {
            return $wrap;
        }
        require_once __DIR__ . '/database.php';
        $wrap = new AppPdo(getDBConnection());
        return $wrap;
    }
}
