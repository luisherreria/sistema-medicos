<?php
/**
 * controllers/CpManuscritoController.php
 * Carga Datos → Planillas CP manuscritas (Document AI).
 *
 * Acciones:
 *   index()     → Vista de carga (1–2 archivos)
 *   procesar()  → POST AJAX
 */

require_once __DIR__ . '/../models/PrestacionesModel.php';

class CpManuscritoController
{
    private $jsonKeyPath;
    private $processorId = 'c0b0aaf30803738b';
    private $location    = 'us';
    /** @var PDO */
    private $pdo;

    public function __construct($pdo = null)
    {
        require_once __DIR__ . '/../config/database.php';
        $this->pdo = $pdo instanceof PDO ? $pdo : getDBConnection();
        $this->jsonKeyPath = __DIR__ . '/../config/ocr-facturas-medicos-5f3f5eefebec.json';
    }

    public function index(): void
    {
        $this->requireAuth();
        $this->requirePermisoIngreso();
        require __DIR__ . '/../views/subir_cp_manuscrito.php';
    }

    public function procesar(): void
    {
        $this->requireAuth();
        $this->requirePermisoIngreso();
        header('Content-Type: application/json; charset=utf-8');

        if (strtoupper($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            echo json_encode(['status' => 'error', 'mensaje' => 'Método no permitido.']);
            exit;
        }

        $csrf = (string) ($_POST['csrf_token'] ?? '');
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $csrf)) {
            echo json_encode(['status' => 'error', 'mensaje' => 'Token de seguridad inválido. Recargue la página.']);
            exit;
        }

        if (!isset($_FILES['archivo_cp']) || !is_array($_FILES['archivo_cp'])) {
            echo json_encode(['status' => 'error', 'mensaje' => 'No se recibió el archivo de la planilla.']);
            exit;
        }

        echo json_encode($this->procesarManuscrito($_FILES['archivo_cp']), JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * @param array<string,mixed> $file
     * @return array<string,mixed>
     */
    public function procesarManuscrito($file)
    {
        $err = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($err !== UPLOAD_ERR_OK) {
            return ['status' => 'error', 'mensaje' => 'Error al subir el archivo (código ' . $err . ').'];
        }

        $nombre = (string) ($file['name'] ?? 'planilla');
        $tmp    = (string) ($file['tmp_name'] ?? '');
        $size   = (int) ($file['size'] ?? 0);
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            return ['status' => 'error', 'mensaje' => 'El archivo temporal no es válido.'];
        }
        if ($size <= 0 || $size > 12 * 1024 * 1024) {
            return ['status' => 'error', 'mensaje' => 'El archivo supera el máximo de 12 MB.'];
        }

        $extension = strtolower(pathinfo($nombre, PATHINFO_EXTENSION));
        $permitidas = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'pdf' => 'application/pdf'];
        if (!isset($permitidas[$extension])) {
            return ['status' => 'error', 'mensaje' => 'Formato no soportado. Use JPG, PNG o PDF.'];
        }

        if (!file_exists($this->jsonKeyPath)) {
            return ['status' => 'error', 'mensaje' => 'No se encontró el archivo de credenciales JSON.'];
        }

        $accessToken = $this->obtenerAccessToken();
        if ($accessToken === '') {
            return ['status' => 'error', 'mensaje' => 'Error de autenticación con Google Cloud.'];
        }

        $authConfig = json_decode((string) file_get_contents($this->jsonKeyPath), true);
        $projectId  = (string) ($authConfig['project_id'] ?? '');
        if ($projectId === '') {
            return ['status' => 'error', 'mensaje' => 'Credenciales de Google Cloud incompletas.'];
        }

        $endpoint = 'https://' . $this->location . '-documentai.googleapis.com/v1/projects/'
            . $projectId . '/locations/' . $this->location . '/processors/' . $this->processorId . ':process';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $endpoint);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'rawDocument' => [
                'content'  => base64_encode((string) file_get_contents($tmp)),
                'mimeType' => $permitidas[$extension],
            ],
        ]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json',
        ]);
        $apiResponse = curl_exec($ch);
        $httpCode    = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr     = curl_error($ch);
        curl_close($ch);

        if ($apiResponse === false || $httpCode !== 200) {
            $detalle = $curlErr !== '' ? $curlErr : ('HTTP ' . $httpCode);
            return ['status' => 'error', 'mensaje' => 'Error de Document AI: ' . $detalle];
        }

        $res = json_decode((string) $apiResponse, true);
        $textoCompleto = (string) ($res['document']['text'] ?? '');
        if (trim($textoCompleto) === '') {
            return ['status' => 'error', 'mensaje' => 'Document AI no devolvió texto legible.'];
        }

        // OCR de afiliados se ignora. Melluso = dra → ebamp → primer 42 de pracespe × renglones.
        $model    = new PrestacionesModel($this->pdo);
        $planilla = $model->armarPlanillaMelluso($textoCompleto);

        return [
            'status'            => 'success',
            'archivo'           => $nombre,
            'prestador'         => $planilla['prestador'],
            'cod_prestador'     => $planilla['cod_prestador'],
            'cod_practica'      => $planilla['cod_practica'],
            'fecha'             => $planilla['fecha'],
            'nro_factura'       => $planilla['nro_factura'],
            'sucursal'          => $planilla['sucursal'],
            'importe'           => number_format($planilla['importe'], 2, '.', ''),
            'importe_calculado' => number_format($planilla['importe'], 2, '.', ''),
            'cant_renglones'    => $planilla['cant_renglones'],
            'valor_practica'    => number_format($planilla['valor_unidad'], 2, '.', ''),
            'periodo'           => $planilla['periodo'],
        ];
    }

    private function obtenerAccessToken(): string
    {
        $authConfig = json_decode((string) file_get_contents($this->jsonKeyPath), true);
        if (!is_array($authConfig) || empty($authConfig['private_key']) || empty($authConfig['client_email'])) {
            return '';
        }

        $header   = json_encode(['alg' => 'RS256', 'typ' => 'JWT']);
        $now      = time();
        $claimSet = json_encode([
            'iss'   => $authConfig['client_email'],
            'scope' => 'https://www.googleapis.com/auth/cloud-platform',
            'aud'   => $authConfig['token_uri'],
            'exp'   => $now + 3600,
            'iat'   => $now,
        ]);
        $b64 = static function ($s) {
            return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($s));
        };
        $signatureInput = $b64($header) . '.' . $b64($claimSet);
        $signature = '';
        openssl_sign($signatureInput, $signature, $authConfig['private_key'], 'SHA256');
        $jwt = $signatureInput . '.' . $b64($signature);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $authConfig['token_uri']);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion'  => $jwt,
        ]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        $tokenRes = json_decode((string) curl_exec($ch), true);
        curl_close($ch);

        return (string) ($tokenRes['access_token'] ?? '');
    }

    private function requireAuth(): void
    {
        if (empty($_SESSION['user'])) {
            if ($this->isAjax()) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['status' => 'error', 'mensaje' => 'Sesión expirada.']);
                exit;
            }
            header('Location: index.php?route=login');
            exit;
        }
    }

    private function requirePermisoIngreso(): void
    {
        $permisos = $_SESSION['permisos'] ?? [];
        $ok = ['MNU_CD_FAC_INGRESO', 'MNU_REG_FACTURAS', 'MNU_CD_PREST_INGRESO'];
        foreach ($permisos as $p) {
            if (isset($p['CLAVE']) && in_array($p['CLAVE'], $ok, true)) {
                return;
            }
        }
        if ($this->isAjax()) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['status' => 'error', 'mensaje' => 'Sin permiso para esta operación.']);
            exit;
        }
        $_SESSION['flash_warning'] = 'No tiene permiso para acceder al módulo solicitado.';
        header('Location: index.php?route=dashboard');
        exit;
    }

    private function isAjax(): bool
    {
        $xrw = strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? ''));
        if ($xrw === 'xmlhttprequest') {
            return true;
        }
        return isset($_FILES['archivo_cp']);
    }
}

$cpScript = isset($_SERVER['SCRIPT_FILENAME']) ? realpath((string) $_SERVER['SCRIPT_FILENAME']) : false;
if ($cpScript !== false && $cpScript === realpath(__FILE__)) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    date_default_timezone_set('America/Argentina/Buenos_Aires');
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    header('Content-Type: application/json; charset=utf-8');
    require_once __DIR__ . '/../config/database.php';
    if (empty($_SESSION['user'])) {
        echo json_encode(['status' => 'error', 'mensaje' => 'Sesión expirada.']);
        exit;
    }
    if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? '')) !== 'POST' || empty($_FILES['archivo_cp'])) {
        echo json_encode(['status' => 'error', 'mensaje' => 'No se recibió el archivo de la planilla.']);
        exit;
    }
    $csrfPost = (string) ($_POST['csrf_token'] ?? '');
    if (!hash_equals((string) ($_SESSION['csrf_token'] ?? ''), $csrfPost)) {
        echo json_encode(['status' => 'error', 'mensaje' => 'Token de seguridad inválido. Recargue la página.']);
        exit;
    }
    try {
        $controller = new CpManuscritoController();
        echo json_encode($controller->procesarManuscrito($_FILES['archivo_cp']), JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'mensaje' => 'Error al procesar la planilla.']);
    }
    exit;
}
