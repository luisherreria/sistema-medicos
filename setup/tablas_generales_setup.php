<?php
/**
 * setup/tablas_generales_setup.php
 * Agrega PK `id` si falta, crea `diagno`, inserta permisos extra.
 * Dialecto PHP 5.6. Solo localhost.
 *
 * Acceder: http://localhost/sistema-medicos/setup/tablas_generales_setup.php
 */

$host = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
if ($host === '' && isset($_SERVER['SERVER_ADDR'])) {
    $host = $_SERVER['SERVER_ADDR'];
}
if (!in_array($host, array('127.0.0.1', '::1', 'localhost'), true)) {
    http_response_code(403);
    die('Acceso denegado.');
}

require_once __DIR__ . '/../config/schema.php';
require_once __DIR__ . '/dbf_skip.php';

$db = appDb();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$log = array();

function tg_has_column(PDO $db, $table, $column)
{
    $stmt = $db->prepare(
        "SELECT COUNT(*) FROM information_schema.COLUMNS
          WHERE TABLE_SCHEMA = :s
            AND TABLE_NAME = :t
            AND COLUMN_NAME = :c"
    );
    $stmt->execute(array(':s' => DB_APP, ':t' => $table, ':c' => $column));
    return (int) $stmt->fetchColumn() > 0;
}

function tg_table_exists(PDO $db, $table)
{
    $stmt = $db->prepare(
        "SELECT COUNT(*) FROM information_schema.TABLES
          WHERE TABLE_SCHEMA = :s
            AND TABLE_NAME = :t"
    );
    $stmt->execute(array(':s' => DB_APP, ':t' => $table));
    return (int) $stmt->fetchColumn() > 0;
}

$satelites = array('grupocarti', 'gruponn', 'gruposup', 'nomentp', 'tablas');
foreach ($satelites as $t) {
    if (!tg_table_exists($db, $t)) {
        $log[] = array('skip', $t . ' no existe (no se crea; viene de VFP/MySQL live).');
        continue;
    }
    if (tg_has_column($db, $t, 'id')) {
        $log[] = array('ok', $t . '.id ya existe.');
        continue;
    }
    try {
        $db->exec("ALTER TABLE `{$t}` ADD COLUMN id INT NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST");
        $log[] = array('ok', $t . ': agregada columna id.');
    } catch (PDOException $e) {
        $log[] = array('err', $t . ': ' . $e->getMessage());
    }
}

try {
    $db->exec(
        "CREATE TABLE IF NOT EXISTS diagno (
          id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
          tacodigo VARCHAR(8) DEFAULT NULL,
          tadescrip VARCHAR(80) DEFAULT NULL,
          tagrupo VARCHAR(20) DEFAULT NULL,
          isonco TINYINT(1) DEFAULT 0,
          isvih TINYINT(1) DEFAULT 0
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );
    $log[] = array('ok', 'diagno: CREATE TABLE IF NOT EXISTS.');
} catch (PDOException $e) {
    $log[] = array('err', 'diagno: ' . $e->getMessage());
}

$sqlFile = __DIR__ . '/tablas_generales_setup.sql';
if (is_file($sqlFile)) {
    $sql = file_get_contents($sqlFile);
    $parts = preg_split('/;\s*\n/', $sql);
    foreach ($parts as $stmt) {
        $stmt = trim($stmt);
        if ($stmt === '' || preg_match('/^--/', $stmt) || stripos($stmt, 'ALTER TABLE') === 0) {
            continue;
        }
        if (stripos($stmt, 'CREATE TABLE') === 0) {
            continue;
        }
        try {
            $db->exec($stmt);
            $log[] = array('ok', substr($stmt, 0, 80));
        } catch (PDOException $e) {
            $log[] = array('err', substr($stmt, 0, 60) . ' → ' . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><title>Setup Tablas Generales</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body class="p-4">
<h4>Setup Tablas Generales</h4>
<p class="text-muted">No importa DBF de cierre/historial/export (ver <code>setup/dbf_skip.php</code>).</p>
<div style="font-family:monospace; font-size:0.82rem;">
<?php foreach ($log as $row):
    $status = $row[0];
    $msg    = $row[1];
?>
<div style="color:<?= $status === 'ok' ? '#198754' : ($status === 'skip' ? '#6c757d' : '#dc3545') ?>;">
    [<?= strtoupper($status) ?>] <?= htmlspecialchars($msg) ?>
</div>
<?php endforeach; ?>
</div>
<a href="../index.php?route=tablas-generales" class="btn btn-primary mt-3">Ir a Tablas Generales</a>
</body>
</html>
