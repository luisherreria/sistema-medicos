<?php
/**
 * Google Document AI (Form Parser) — PHP 5.6.
 * Processor: c0b0aaf30803738b
 */

function rfpDocumentAiJsonKeyPath()
{
    return dirname(__FILE__) . '/../config/ocr-facturas-medicos-5f3f5eefebec.json';
}

function rfpDocumentAiB64Url($s)
{
    return str_replace(array('+', '/', '='), array('-', '_', ''), base64_encode($s));
}

/**
 * @return string Access token o vacío
 */
function rfpDocumentAiAccessToken()
{
    $jsonKeyPath = rfpDocumentAiJsonKeyPath();
    if (!is_file($jsonKeyPath)) {
        return '';
    }
    $authConfig = json_decode(file_get_contents($jsonKeyPath), true);
    if (!is_array($authConfig) || empty($authConfig['private_key']) || empty($authConfig['client_email'])) {
        return '';
    }

    $header   = json_encode(array('alg' => 'RS256', 'typ' => 'JWT'));
    $now      = time();
    $claimSet = json_encode(array(
        'iss'   => $authConfig['client_email'],
        'scope' => 'https://www.googleapis.com/auth/cloud-platform',
        'aud'   => $authConfig['token_uri'],
        'exp'   => $now + 3600,
        'iat'   => $now,
    ));
    $signatureInput = rfpDocumentAiB64Url($header) . '.' . rfpDocumentAiB64Url($claimSet);
    $signature = '';
    openssl_sign($signatureInput, $signature, $authConfig['private_key'], 'SHA256');
    $jwt = $signatureInput . '.' . rfpDocumentAiB64Url($signature);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $authConfig['token_uri']);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array(
        'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
        'assertion'  => $jwt,
    )));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $tokenRes = json_decode(curl_exec($ch), true);
    curl_close($ch);

    return (isset($tokenRes['access_token']) && $tokenRes['access_token']) ? (string) $tokenRes['access_token'] : '';
}

/**
 * Extrae texto de PDF/imagen con Document AI.
 *
 * @param string $filePath
 * @param string $mimeType
 * @return string
 */
function rfpDocumentAiTexto($filePath, $mimeType)
{
    $jsonKeyPath = rfpDocumentAiJsonKeyPath();
    if (!is_file($jsonKeyPath)) {
        throw new Exception('No se encontró el archivo de credenciales JSON de Google.');
    }
    $token = rfpDocumentAiAccessToken();
    if ($token === '') {
        throw new Exception('Error de autenticación con Google Cloud.');
    }
    $authConfig = json_decode(file_get_contents($jsonKeyPath), true);
    $projectId  = isset($authConfig['project_id']) ? $authConfig['project_id'] : '';
    if ($projectId === '') {
        throw new Exception('Credenciales de Google Cloud incompletas.');
    }
    $processorId = 'c0b0aaf30803738b';
    $location    = 'us';
    $endpoint    = 'https://' . $location . '-documentai.googleapis.com/v1/projects/'
        . $projectId . '/locations/' . $location . '/processors/' . $processorId . ':process';

    $bin = file_get_contents($filePath);
    if ($bin === false || $bin === '') {
        throw new Exception('No se pudo leer el archivo para Document AI.');
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $endpoint);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(array(
        'rawDocument' => array(
            'content'  => base64_encode($bin),
            'mimeType' => $mimeType,
        ),
    )));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
    curl_setopt($ch, CURLOPT_TIMEOUT, 120);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json',
    ));
    $apiResponse = curl_exec($ch);
    $httpCode    = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr     = curl_error($ch);
    curl_close($ch);

    if ($apiResponse === false || $httpCode !== 200) {
        $detalle = ($curlErr !== '') ? $curlErr : ('HTTP ' . $httpCode);
        throw new Exception('Error de Document AI: ' . $detalle);
    }
    $res = json_decode($apiResponse, true);
    $texto = (isset($res['document']['text']) && $res['document']['text'] !== '')
        ? (string) $res['document']['text']
        : '';
    if (trim($texto) === '') {
        throw new Exception('Document AI no devolvió texto legible.');
    }
    return $texto;
}

/**
 * Cuenta renglones de atención (misma lógica que CP manuscrito).
 *
 * @param string $texto
 * @return int
 */
function rfpContarRenglonesCp($texto)
{
    $cant = 0;
    $lineas = preg_split("/\r\n|\r|\n/", (string) $texto);
    foreach ($lineas as $l) {
        $lClean = trim($l);
        if ($lClean === '') {
            continue;
        }
        if (preg_match('/(FECHA|NOMBRE Y APELLIDO|AFILIADO|COSEGURO|DIAGNOSTICO|FIRMA|GINECOLOG|OSSEC|PLANILLA|PSICOANALISTA|M\.P\.|MP\.)/i', $lClean)) {
            continue;
        }
        if (preg_match('/MELLUS|GABRIELA|BABRIELA|22568/i', $lClean)) {
            continue;
        }
        $digits = preg_replace('/\D/', '', $lClean);
        if (strlen($digits) >= 10 && strlen($digits) <= 16) {
            $cant++;
        }
    }
    if ($cant < 8 || $cant > 40) {
        return 20;
    }
    return $cant;
}
