<?php
/**
 * obtener_fila_factura.php
 * Devuelve únicamente el HTML del <tr> de un registro de t_facturas_temp.
 * PHP 5.6 — PDO, array(), isset().
 */

require_once dirname(__FILE__) . '/includes/facturas_pdf_common.php';
require_once dirname(__FILE__) . '/models/FacturasTempModel.php';

rfpRequireAuth();
rfpRequirePermiso();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    header('HTTP/1.1 400 Bad Request');
    echo '';
    exit;
}

try {
    $model = new FacturasTempModel();
    $r = $model->obtenerPorId($id);
    if (!$r) {
        header('HTTP/1.1 404 Not Found');
        echo '';
        exit;
    }
    header('Content-Type: text/html; charset=utf-8');
    echo rfpHtmlFilaPendiente($r);
    exit;
} catch (Exception $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo '';
    exit;
}
