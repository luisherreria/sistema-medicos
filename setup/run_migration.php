<?php
/**
 * setup/run_migration.php
 * Ejecuta ebamp_migration.sql sobre la BD local.
 * SOLO para uso en desarrollo/XAMPP.
 * Acceder desde: http://localhost/sistema-medicos/setup/run_migration.php
 */

// Seguridad básica: solo desde localhost
$host = $_SERVER['REMOTE_ADDR'] ?? $_SERVER['SERVER_ADDR'] ?? '';
if (!in_array($host, ['127.0.0.1', '::1', 'localhost'])) {
    http_response_code(403);
    die('Acceso denegado.');
}

require_once __DIR__ . '/../config/database.php';

$sqlFile = __DIR__ . '/ebamp_migration.sql';
if (!file_exists($sqlFile)) {
    die('No se encontró el archivo: ' . $sqlFile);
}

$sql = file_get_contents($sqlFile);

// Separar sentencias por ; (ignorando -- comentarios y líneas vacías)
$statements = array_filter(
    array_map('trim',
        preg_split('/;\s*\n/', $sql)
    )
);

$db = getDBConnection();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$ok  = 0;
$err = 0;
$log = [];

foreach ($statements as $stmt) {
    $stmt = trim($stmt);
    // Ignorar comentarios puros y líneas vacías
    if ($stmt === '' || preg_match('/^--/', $stmt)) continue;

    try {
        $db->exec($stmt);
        $ok++;
        $short = substr($stmt, 0, 60);
        $log[] = ['ok', $short . (strlen($stmt) > 60 ? '…' : '')];
    } catch (PDOException $e) {
        $err++;
        $short = substr($stmt, 0, 60);
        $log[] = ['err', $short . ' → ' . $e->getMessage()];
    }
}

// Verificar conteos
$counts = [];
foreach (['ebamp', 'obramed', 'obrasoc', 'novedade'] as $t) {
    try {
        $cnt = $db->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
        $counts[$t] = $cnt;
    } catch (Exception $e) {
        $counts[$t] = 'ERROR: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><title>Migración ebamp</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body class="p-4">
<h4 class="mb-3">Resultado de la Migración</h4>
<div class="alert alert-<?= $err ? 'warning' : 'success' ?>">
    <?= $ok ?> sentencias OK — <?= $err ?> errores
</div>

<h6>Conteo de filas:</h6>
<table class="table table-sm table-bordered w-auto mb-4">
<?php foreach ($counts as $t => $c): ?>
<tr><td><code><?= $t ?></code></td><td><?= $c ?></td></tr>
<?php endforeach; ?>
</table>

<h6>Log detallado:</h6>
<div style="font-family:monospace; font-size:0.78rem; max-height:400px; overflow-y:auto; background:#f8f9fa; border:1px solid #dee2e6; padding:12px; border-radius:6px;">
<?php foreach ($log as [$status, $msg]): ?>
<div style="color:<?= $status === 'ok' ? '#198754' : '#dc3545' ?>;">
    [<?= strtoupper($status) ?>] <?= htmlspecialchars($msg) ?>
</div>
<?php endforeach; ?>
</div>

<a href="../index.php?route=prestadores" class="btn btn-primary mt-3">
    <i class="fa-solid fa-stethoscope me-1"></i>Ir al módulo Prestadores
</a>
</body></html>
