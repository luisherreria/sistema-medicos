<?php
/**
 * Bootstrap compartido del módulo Carga Automática de Facturas PDF.
 * Sintaxis clásica PHP 5.6.
 */

date_default_timezone_set('America/Argentina/Buenos_Aires');
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['csrf_token']) || $_SESSION['csrf_token'] === '') {
    if (function_exists('random_bytes')) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    } else {
        $_SESSION['csrf_token'] = md5(uniqid(mt_rand(), true));
    }
}

require_once dirname(__FILE__) . '/../config/database.php';

if (!class_exists('Permission')) {
    require_once dirname(__FILE__) . '/../models/Permission.php';
}

/**
 * @return PDO
 */
function rfpDb()
{
    return getDBConnection();
}

function rfpRequireAuth()
{
    if (empty($_SESSION['user'])) {
        if (rfpIsAjax()) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(array('ok' => false, 'error' => 'Sesión expirada.'));
            exit;
        }
        header('Location: index.php?route=login');
        exit;
    }
}

function rfpRequirePermiso()
{
    $permisos = isset($_SESSION['permisos']) ? $_SESSION['permisos'] : array();
    foreach ($permisos as $p) {
        if (isset($p['CLAVE']) && ($p['CLAVE'] === 'MNU_CD_FAC_INGRESO' || $p['CLAVE'] === 'MNU_REG_FACTURAS')) {
            return;
        }
    }
    if (rfpIsAjax()) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(array('ok' => false, 'error' => 'Sin permiso para esta operación.'));
        exit;
    }
    $_SESSION['flash_warning'] = 'No tiene permiso para acceder al módulo solicitado.';
    header('Location: index.php?route=dashboard');
    exit;
}

function rfpIsAjax()
{
    $h = isset($_SERVER['HTTP_X_REQUESTED_WITH']) ? $_SERVER['HTTP_X_REQUESTED_WITH'] : '';
    return strtolower($h) === 'xmlhttprequest';
}

function rfpCsrfOk()
{
    $ses  = isset($_SESSION['csrf_token']) ? $_SESSION['csrf_token'] : '';
    $post = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if ($ses === '' || $post === '') {
        return false;
    }
    return hash_equals($ses, $post);
}

function rfpUsuario()
{
    $u = '';
    if (isset($_SESSION['user']['username'])) {
        $u = $_SESSION['user']['username'];
    }
    return strtoupper(substr($u !== '' ? $u : 'SIS', 0, 5));
}

function rfpUsuarioLargo()
{
    $u = '';
    if (isset($_SESSION['user']['username'])) {
        $u = $_SESSION['user']['username'];
    }
    return strtoupper(substr($u !== '' ? $u : 'SIS', 0, 20));
}

function rfpJsonError($msg)
{
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(400);
    echo json_encode(array('ok' => false, 'error' => $msg), JSON_UNESCAPED_UNICODE);
    exit;
}

function rfpJsonOk($extra)
{
    header('Content-Type: application/json; charset=utf-8');
    $out = is_array($extra) ? $extra : array();
    $out['ok'] = true;
    echo json_encode($out, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Crea t_facturas_temp como clon de registrf si no existe o si es el esquema viejo.
 */
function rfpAsegurarTabla()
{
    $db = rfpDb();
    try {
        $existe = false;
        $stmt = $db->query("SHOW TABLES LIKE 't_facturas_temp'");
        if ($stmt && $stmt->fetch(PDO::FETCH_NUM)) {
            $existe = true;
        }

        $esClon = false;
        if ($existe) {
            $cols = rfpColumnasTemp($db);
            $esClon = isset($cols['COPRESTADO']) && isset($cols['CONOMPREST']);
            if (!$esClon) {
                $db->exec('DROP TABLE IF EXISTS t_facturas_temp');
                $existe = false;
            }
        }

        if (!$existe) {
            $db->exec('CREATE TABLE t_facturas_temp LIKE registrf');
            $db->exec('ALTER TABLE t_facturas_temp ADD COLUMN archivo_pdf VARCHAR(255) DEFAULT NULL');
            $db->exec('ALTER TABLE t_facturas_temp ADD COLUMN marcado TINYINT(1) DEFAULT 0');
            $db->exec('ALTER TABLE t_facturas_temp ADD COLUMN texto_ocr TEXT DEFAULT NULL');
            rfpAsegurarArchivoPdfRegistrf($db);
            return;
        }

        $cols = rfpColumnasTemp($db);
        if (!isset($cols['ARCHIVO_PDF'])) {
            $db->exec('ALTER TABLE t_facturas_temp ADD COLUMN archivo_pdf VARCHAR(255) DEFAULT NULL');
        }
        if (!isset($cols['MARCADO'])) {
            $db->exec('ALTER TABLE t_facturas_temp ADD COLUMN marcado TINYINT(1) DEFAULT 0');
        }
        if (!isset($cols['TEXTO_OCR'])) {
            $db->exec('ALTER TABLE t_facturas_temp ADD COLUMN texto_ocr TEXT DEFAULT NULL');
        }

        rfpAsegurarArchivoPdfRegistrf($db);
    } catch (PDOException $e) {
        error_log('rfpAsegurarTabla: ' . $e->getMessage());
        throw $e;
    }
}

/**
 * La tabla oficial necesita archivo_pdf para conservar el nombre del PDF confirmado.
 */
function rfpAsegurarArchivoPdfRegistrf($db)
{
    $oficial = rfpColumnasOficial($db);
    if (!isset($oficial['ARCHIVO_PDF'])) {
        $db->exec('ALTER TABLE registrf ADD COLUMN archivo_pdf VARCHAR(255) DEFAULT NULL');
    }
}

/**
 * @return array UPPER => nombre real
 */
function rfpColumnasTemp($db)
{
    $out = array();
    foreach ($db->query('SHOW COLUMNS FROM t_facturas_temp') as $row) {
        $real = '';
        if (isset($row['Field'])) {
            $real = $row['Field'];
        } elseif (isset($row['field'])) {
            $real = $row['field'];
        }
        if ($real !== '') {
            $out[strtoupper($real)] = $real;
        }
    }
    return $out;
}

/**
 * Columnas de registrf (oficial).
 *
 * @return array UPPER => nombre real
 */
function rfpColumnasOficial($db)
{
    $out = array();
    foreach ($db->query('SHOW COLUMNS FROM registrf') as $row) {
        $real = '';
        if (isset($row['Field'])) {
            $real = $row['Field'];
        } elseif (isset($row['field'])) {
            $real = $row['field'];
        }
        if ($real !== '') {
            $out[strtoupper($real)] = $real;
        }
    }
    return $out;
}

/**
 * Extrae texto con class.pdf2text.php (PHP puro, sin shell_exec).
 */
function rfpExtraerTextoPdf($targetFile)
{
    if (!class_exists('PDF2Text')) {
        require_once dirname(__FILE__) . '/../class.pdf2text.php';
    }
    $ex = new PDF2Text();
    $ex->setFilename($targetFile);
    $ex->decodePDF();
    return $ex->output();
}

/**
 * Convierte AA/MM → AAMM (almacenamiento VFP).
 */
function rfpPeriodoAamm($periodo)
{
    $p = strtoupper(trim(isset($periodo) ? $periodo : ''));
    $p = str_replace(array(' ', '-'), '', $p);
    if (preg_match('/^(\d{2})\/(\d{2})$/', $p, $m)) {
        $mm = (int) $m[2];
        if ($mm >= 1 && $mm <= 12) {
            return $m[1] . $m[2];
        }
    }
    if (preg_match('/^(\d{2})(\d{2})$/', $p, $m)) {
        $mm = (int) $m[2];
        if ($mm >= 1 && $mm <= 12) {
            return $m[1] . $m[2];
        }
    }
    return '';
}

/**
 * Convierte un importe con formato argentino (1.234.567,89) a float.
 * Quita miles (.) y pasa la coma decimal a punto.
 *
 * @param mixed $valor
 * @return float
 */
function rfpLimpiarImporte($valor)
{
    $s = trim(str_replace(array('$', ' '), '', (string) $valor));
    if ($s === '') {
        return 0.0;
    }
    if (strpos($s, ',') !== false) {
        $s = str_replace('.', '', $s);
        $s = str_replace(',', '.', $s);
    } elseif (substr_count($s, '.') > 1) {
        $s = str_replace('.', '', $s);
    }
    return (float) $s;
}

/**
 * Importe OCR/PDF de la fila, siempre formateado (nunca value vacío).
 *
 * @param array $r
 * @return string
 */
function rfpImporteMostrar($r)
{
    $r = is_array($r) ? $r : array();
    $claves = array('TOTAL', 'COTOTALFAC', 'COIMPFAC', 'IMPORTE', 'importe');
    $raw = '';
    foreach ($claves as $k) {
        if (isset($r[$k]) && $r[$k] !== null && $r[$k] !== '') {
            $raw = $r[$k];
            break;
        }
    }
    return number_format(rfpLimpiarImporte($raw), 2, ',', '.');
}

/**
 * Tope de carga desde t_control.valorreg.
 *
 * @param PDO $db
 * @return float
 */
function rfpValorReg($db)
{
    try {
        $stmt = $db->query('SELECT valorreg FROM t_control LIMIT 1');
        $row  = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && isset($row['valorreg'])) {
            return rfpLimpiarImporte($row['valorreg']);
        }
    } catch (Exception $e) {
        error_log('rfpValorReg: ' . $e->getMessage());
    }
    return 0.0;
}

/**
 * Convierte AAMM → AA/MM (visualización).
 */
function rfpPeriodoDisplay($aamm)
{
    $p = trim(isset($aamm) ? $aamm : '');
    if (preg_match('/^(\d{2})\/(\d{2})$/', $p)) {
        return $p;
    }
    if (strlen($p) === 4 && ctype_digit($p)) {
        return substr($p, 0, 2) . '/' . substr($p, 2, 2);
    }
    return $p;
}

function rfpDirUploads()
{
    $dir = dirname(__FILE__) . '/../public/uploads/facturas_pdf/';
    if (!is_dir($dir)) {
        if (!@mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new Exception('No se pudo crear la carpeta de uploads.');
        }
    }
    return $dir;
}

function rfpH($s)
{
    $s = isset($s) ? (string) $s : '';
    if ($s === '') {
        return '';
    }
    // ENT_SUBSTITUTE evita que un byte inválido del OCR deje el atributo data-texto vacío
    if (defined('ENT_SUBSTITUTE')) {
        return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
    $out = @htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
    if ($out === '' && $s !== '') {
        if (function_exists('iconv')) {
            $tmp = @iconv('UTF-8', 'UTF-8//IGNORE', $s);
            if ($tmp !== false) {
                $s = $tmp;
            }
        }
        $out = @htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
    }
    return $out;
}

/**
 * HTML de una fila de la grilla de pendientes (mismo markup en listado y AJAX).
 *
 * @param array $r
 * @return string
 */
function rfpHtmlFilaPendiente($r)
{
    $id  = isset($r['id']) ? (int) $r['id'] : 0;
    $fec = '';
    if (!empty($r['F_FACTURA']) && $r['F_FACTURA'] !== '0000-00-00') {
        $p = explode('-', substr($r['F_FACTURA'], 0, 10));
        if (count($p) === 3) {
            $fec = $p[2] . '/' . $p[1] . '/' . $p[0];
        }
    }
    $nro = isset($r['NRO_FACTURA']) ? $r['NRO_FACTURA'] : '';
    $suc = isset($r['SUCURSAL']) ? $r['SUCURSAL'] : '';
    if ($suc !== '' && $nro !== '' && strpos($nro, '-') === false) {
        $nroMostrar = $suc . '-' . $nro;
    } else {
        $nroMostrar = $nro;
    }
    $total = rfpImporteMostrar($r);
    $perDisp  = rfpPeriodoDisplay(isset($r['PERIODO']) ? $r['PERIODO'] : '');
    $chk      = (!empty($r['marcado'])) ? ' checked' : '';
    $prestOcr = isset($r['PRESTADOR']) ? $r['PRESTADOR'] : '';
    $archPdf  = isset($r['archivo_pdf']) ? $r['archivo_pdf'] : '';
    $codPrest = isset($r['COD_PREST']) ? $r['COD_PREST'] : '';
    $os       = isset($r['O_SOCIAL']) ? $r['O_SOCIAL'] : '';
    $textoOcr = isset($r['texto_ocr']) ? $r['texto_ocr'] : '';

    $html  = '<tr data-id="' . $id . '" data-prestador-ocr="' . rfpH($prestOcr) . '">';
    $html .= '<td class="text-center">';
    $html .= '<input type="checkbox" class="rfp-check" value="' . $id . '"' . $chk . '>';
    $html .= '</td>';
    $html .= '<td title="' . rfpH($prestOcr) . '">' . rfpH($prestOcr) . '</td>';
    $html .= '<td><input type="text" class="form-control form-control-sm rfp-inp inp-cod edit-codprest"';
    $html .= ' value="' . rfpH($codPrest) . '" placeholder="Código" maxlength="18"></td>';
    $html .= '<td>' . rfpH($fec) . '</td>';
    $html .= '<td><input type="text" class="form-control form-control-sm rfp-inp inp-nro"';
    $html .= ' value="' . rfpH($nroMostrar) . '" placeholder="N° factura" maxlength="18"></td>';
    $html .= '<td><input type="text" name="importe[]" class="form-control form-control-sm rfp-inp inp-imp text-end"';
    $html .= ' value="' . rfpH($total) . '" data-importe="' . rfpH($total) . '"';
    $html .= ' placeholder="0,00" maxlength="18"></td>';
    $html .= '<td><input type="text" class="form-control form-control-sm rfp-inp inp-os edit-obrasoc"';
    $html .= ' value="' . rfpH($os) . '" placeholder="Código / nombre" maxlength="40"></td>';
    $html .= '<td><input type="text" class="form-control form-control-sm rfp-inp inp-per edit-periodo validar-periodo"';
    $html .= ' value="' . rfpH($perDisp) . '" placeholder="AA/MM" maxlength="5"></td>';
    $html .= '<td class="text-center">';
    $html .= '<button type="button" class="btn btn-sm btn-outline-secondary rfp-btn-ocr"';
    $html .= ' data-texto="' . rfpH($textoOcr) . '" title="Ver texto OCR">';
    $html .= '<i class="fa-solid fa-magnifying-glass"></i> Ver OCR</button></td>';
    $html .= '<td class="text-center text-nowrap">';
    if ($archPdf !== '') {
        $html .= '<a href="public/uploads/facturas_pdf/' . htmlspecialchars($archPdf) . '"';
        $html .= ' target="_blank" class="btn btn-sm btn-primary" title="Ver PDF">📄</a> ';
    }
    $html .= '<button type="button" class="rfp-btn-del" data-id="' . $id . '" title="Eliminar">';
    $html .= '<i class="fa-solid fa-trash"></i></button></td>';
    $html .= '</tr>';

    return $html;
}

/**
 * Descarta basura binaria (JPEG) y acepta texto de OCR / PDF.
 */
function rfpUtf8($s)
{
    $s = isset($s) ? (string) $s : '';
    if ($s === '') {
        return '';
    }
    if (function_exists('mb_check_encoding') && mb_check_encoding($s, 'UTF-8')) {
        return $s;
    }
    if (function_exists('iconv')) {
        $tmp = @iconv('UTF-8', 'UTF-8//IGNORE', $s);
        if ($tmp !== false && $tmp !== '') {
            return $tmp;
        }
        $tmp = @iconv('Windows-1252', 'UTF-8//IGNORE', $s);
        if ($tmp !== false && $tmp !== '') {
            return $tmp;
        }
    }
    if (function_exists('mb_convert_encoding')) {
        $tmp = @mb_convert_encoding($s, 'UTF-8', 'UTF-8, Windows-1252, ISO-8859-1');
        if ($tmp !== false && $tmp !== '') {
            return $tmp;
        }
    }
    return utf8_encode($s);
}

function rfpTextoUtil($texto)
{
    $t = trim(isset($texto) ? $texto : '');
    if (strlen($t) < 12) {
        return false;
    }
    $ok = @preg_match_all('/[A-Za-z0-9ÁÉÍÓÚÑáéíóúñ]/u', $t);
    if ($ok === false) {
        $ok = preg_match_all('/[A-Za-z0-9]/', $t);
    }
    return ($ok / max(strlen($t), 1)) > 0.40;
}
