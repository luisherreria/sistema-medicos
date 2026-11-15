<?php
/**
 * setup/copiar_login_vfpcomedica.php
 * Copia users / permisos / roles / tablas PHP de vfpmedicos → vfpcomedica
 * si no existen. No pisa tablas Fox ya importadas (ebamp, consulta, …).
 * Dialecto PHP 5.6. Solo localhost.
 *
 * http://localhost/sistema-medicos/setup/copiar_login_vfpcomedica.php
 */

$host = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
if ($host === '' && isset($_SERVER['SERVER_ADDR'])) {
    $host = $_SERVER['SERVER_ADDR'];
}
if (!in_array($host, array('127.0.0.1', '::1', 'localhost'), true)) {
    http_response_code(403);
    die('Acceso denegado.');
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/schema.php';

$db = getDBConnection();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$src = DB_LOGIN_ORIGEN;
$dst = DB_APP;
$log = array();

function vc_ident($schema, $table)
{
    $s = preg_replace('/[^A-Za-z0-9_]/', '', (string) $schema);
    $t = preg_replace('/[^A-Za-z0-9_]/', '', (string) $table);
    if ($s === '' || $t === '') {
        return '';
    }
    return '`' . $s . '`.`' . $t . '`';
}

function vc_table_exists(PDO $db, $schema, $table)
{
    $stmt = $db->prepare(
        'SELECT COUNT(*) FROM information_schema.TABLES
          WHERE TABLE_SCHEMA = :s AND TABLE_NAME = :t'
    );
    $stmt->execute(array(':s' => $schema, ':t' => $table));
    return (int) $stmt->fetchColumn() > 0;
}

function vc_table_rows(PDO $db, $schema, $table)
{
    $ident = vc_ident($schema, $table);
    if ($ident === '') {
        return 0;
    }
    try {
        $n = $db->query('SELECT COUNT(*) FROM ' . $ident)->fetchColumn();
        return (int) $n;
    } catch (PDOException $e) {
        return 0;
    }
}

function vc_list(PDO $db, $schema, $type)
{
    $stmt = $db->prepare(
        'SELECT TABLE_NAME FROM information_schema.TABLES
          WHERE TABLE_SCHEMA = :s AND TABLE_TYPE = :ty
          ORDER BY TABLE_NAME'
    );
    $stmt->execute(array(':s' => $schema, ':ty' => $type));
    $out = array();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $out[] = $row['TABLE_NAME'];
    }
    return $out;
}

$db->exec('CREATE DATABASE IF NOT EXISTS `' . preg_replace('/[^A-Za-z0-9_]/', '', $dst) . '`
           DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');

$login = array('users', 'permisos', 'users_permissions', 'roles');
$phpApp = array(
    't_plantillas_emails',
    'telegram_chats',
    'telegram_messages',
    'telegram_contactos',
    'prestadores_observaciones',
    'prestadores_info_liquidaciones',
    'novedade',
    'excluos',
    'auditoria_log',
    'eventos',
);

$prioridad = array_merge($login, $phpApp);
$hechas = array();
$i = 0;
$n = count($prioridad);
for ($i = 0; $i < $n; $i++) {
    $t = $prioridad[$i];
    $hechas[$t] = true;
    if (!vc_table_exists($db, $src, $t)) {
        $log[] = array('skip', $t . ': no está en ' . $src);
        continue;
    }
    if (vc_table_exists($db, $dst, $t) && vc_table_rows($db, $dst, $t) > 0) {
        $log[] = array('ok', $t . ': ya tiene datos en ' . $dst);
        continue;
    }
    try {
        if (vc_table_exists($db, $dst, $t) && vc_table_rows($db, $dst, $t) === 0) {
            $db->exec('INSERT INTO ' . vc_ident($dst, $t) . ' SELECT * FROM ' . vc_ident($src, $t));
            $log[] = array('ok', $t . ': insertadas ' . vc_table_rows($db, $dst, $t) . ' filas.');
            continue;
        }
        $db->exec('CREATE TABLE ' . vc_ident($dst, $t) . ' LIKE ' . vc_ident($src, $t));
        $db->exec('INSERT INTO ' . vc_ident($dst, $t) . ' SELECT * FROM ' . vc_ident($src, $t));
        $log[] = array('ok', $t . ': copiada (' . vc_table_rows($db, $dst, $t) . ' filas).');
    } catch (PDOException $e) {
        $log[] = array('err', $t . ': ' . $e->getMessage());
    }
}

$srcTables = vc_list($db, $src, 'BASE TABLE');
$nt = count($srcTables);
for ($i = 0; $i < $nt; $i++) {
    $t = $srcTables[$i];
    if (isset($hechas[$t])) {
        continue;
    }
    if (vc_table_exists($db, $dst, $t)) {
        continue;
    }
    try {
        $db->exec('CREATE TABLE ' . vc_ident($dst, $t) . ' LIKE ' . vc_ident($src, $t));
        $db->exec('INSERT INTO ' . vc_ident($dst, $t) . ' SELECT * FROM ' . vc_ident($src, $t));
        $log[] = array('ok', $t . ': copiada (faltaba en sandbox).');
    } catch (PDOException $e) {
        $log[] = array('err', $t . ': ' . $e->getMessage());
    }
}

$srcViews = vc_list($db, $src, 'VIEW');
$nv = count($srcViews);
for ($i = 0; $i < $nv; $i++) {
    $t = $srcViews[$i];
    if (vc_table_exists($db, $dst, $t)) {
        continue;
    }
    try {
        $stmt = $db->query('SHOW CREATE VIEW ' . vc_ident($src, $t));
        $row = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : false;
        $ddl = '';
        if ($row && isset($row['Create View'])) {
            $ddl = $row['Create View'];
        }
        if ($ddl === '') {
            $log[] = array('skip', $t . ': vista sin DDL.');
            continue;
        }
        $ddl = preg_replace('/^CREATE\s+.*\s+VIEW/i', 'CREATE VIEW', $ddl);
        $ddl = str_replace('`' . $src . '`', '`' . $dst . '`', $ddl);
        $ddl = preg_replace('/\b' . preg_quote($src, '/') . '\s*\./', $dst . '.', $ddl);
        $db->exec($ddl);
        $log[] = array('ok', $t . ': vista creada en ' . $dst);
    } catch (PDOException $e) {
        $log[] = array('err', 'vista ' . $t . ': ' . $e->getMessage());
    }
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">
<head><meta charset="utf-8"><title>Copiar login → vfpcomedica</title></head>
<body>
<h1>Copiar login/permisos a <?php echo htmlspecialchars($dst); ?></h1>
<p>Origen: <code><?php echo htmlspecialchars($src); ?></code>. No se pisan tablas Fox con datos.</p>
<ul>
<?php
$j = 0;
$nj = count($log);
for ($j = 0; $j < $nj; $j++) {
    echo '<li><strong>' . htmlspecialchars($log[$j][0]) . '</strong> '
        . htmlspecialchars($log[$j][1]) . '</li>';
}
?>
</ul>
<p><a href="../index.php">Volver</a></p>
</body>
</html>
