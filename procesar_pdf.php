<?php
/**
 * procesar_pdf.php
 * Recibe un PDF (Dropzone), extrae texto vía OCR.space, inserta en t_facturas_temp.
 * PHP 5.6 — array() / isset() ternarios / PDO try-catch.
 */

require_once dirname(__FILE__) . '/includes/facturas_pdf_common.php';
require_once dirname(__FILE__) . '/lib/FacturaPdfParser.php';
require_once dirname(__FILE__) . '/models/FacturasTempModel.php';

rfpRequireAuth();
rfpRequirePermiso();

header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', '0');
ob_start();

try {
    if (!rfpCsrfOk()) {
        throw new Exception('Token de seguridad inválido.');
    }

    $field = isset($_FILES['file']) ? 'file' : (isset($_FILES['pdfs']) ? 'pdfs' : '');
    if ($field === '') {
        throw new Exception('No se recibió ningún archivo PDF.');
    }

    $file = $_FILES[$field];
    if (is_array($file['name'])) {
        $file = array(
            'name'     => $file['name'][0],
            'type'     => $file['type'][0],
            'tmp_name' => $file['tmp_name'][0],
            'error'    => $file['error'][0],
            'size'     => $file['size'][0],
        );
    }

    $nombreOrig = isset($file['name']) ? $file['name'] : 'factura.pdf';

    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Error de subida del archivo.');
    }
    if (!isset($file['size']) || $file['size'] <= 0) {
        throw new Exception('El archivo está vacío.');
    }
    if ($file['size'] > 10 * 1024 * 1024) {
        throw new Exception('El PDF supera los 10 MB.');
    }
    $ext = strtolower(pathinfo($nombreOrig, PATHINFO_EXTENSION));
    if ($ext !== 'pdf') {
        throw new Exception('Solo se aceptan archivos PDF.');
    }

    $fh  = fopen($file['tmp_name'], 'rb');
    $sig = $fh ? fread($fh, 5) : '';
    if ($fh) {
        fclose($fh);
    }
    if ($sig !== '%PDF-') {
        throw new Exception('El archivo no es un PDF válido.');
    }

    $safe = preg_replace('/[^A-Za-z0-9._-]/', '_', basename($nombreOrig));
    if ($safe === '' || strtolower(substr($safe, -4)) !== '.pdf') {
        $safe = 'factura.pdf';
    }
    $safe = date('YmdHis') . '_' . mt_rand(1000, 9999) . '_' . $safe;

    $targetFile = rfpDirUploads() . $safe;
    if (!move_uploaded_file($file['tmp_name'], $targetFile)) {
        throw new Exception('No se pudo guardar el archivo en el servidor.');
    }

    // ── Extracción de texto vía API OCR.space (cURL PHP 5.6) ─────────────
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.ocr.space/parse/image');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 90);
    curl_setopt($ch, CURLOPT_POSTFIELDS, array(
        'apikey'             => 'helloworld',
        'language'           => 'spa',
        'isOverlayRequired'  => 'false',
        'file'               => new CURLFile($targetFile, 'application/pdf', basename($targetFile)),
    ));

    $rawJson = curl_exec($ch);
    $curlErr = curl_error($ch);
    curl_close($ch);

    if ($rawJson === false || $rawJson === '') {
        throw new Exception('Error al consultar OCR.space: ' . $curlErr);
    }

    $respuestaJson = json_decode($rawJson, true);
    $texto = '';
    if (isset($respuestaJson['ParsedResults'][0]['ParsedText'])) {
        $texto = $respuestaJson['ParsedResults'][0]['ParsedText'];
    } elseif (isset($respuestaJson['ErrorMessage'])) {
        $errApi = $respuestaJson['ErrorMessage'];
        if (is_array($errApi)) {
            $errApi = implode(' ', $errApi);
        }
        throw new Exception('OCR.space: ' . $errApi);
    }

    $parser = new FacturaPdfParser();
    $campos = $parser->parsear($texto);

    $prestador = '';
    $codPrest  = '';
    $razonCab  = isset($campos['razon_social']) ? trim($campos['razon_social']) : '';
    $cuitEmisor = isset($campos['cuit']) ? preg_replace('/\D/', '', $campos['cuit']) : '';
    if (rfpCuitEsCompradorComedica($cuitEmisor)) {
        $cuitEmisor = '';
    }

    // 0. Diccionario aprendido (nombre_ocr / CUIT → código)
    try {
        $alias = PrestadoresAlias::buscar(rfpDb(), $razonCab, $cuitEmisor);
        if (isset($alias['codigo']) && $alias['codigo'] !== '') {
            $codPrest  = $alias['codigo'];
            $prestador = $razonCab !== '' ? $razonCab : (isset($alias['nombre_ocr']) ? $alias['nombre_ocr'] : '');
        }
    } catch (Exception $eAlias) {
        error_log('OCR alias: ' . $eAlias->getMessage());
    }

    // 1. CUIT del emisor → padrón (no usar CUIT de COMEDICA)
    if ($codPrest === '' && strlen($cuitEmisor) === 11) {
        try {
            $matchCuit = rfpResolverPrestadorPorCuit(rfpDb(), $cuitEmisor);
            if (isset($matchCuit['codigo']) && $matchCuit['codigo'] !== '') {
                $nomOficial = isset($matchCuit['nombre']) ? strtoupper(trim($matchCuit['nombre'])) : '';
                if ($razonCab === '' || rfpNombresPrestadorCompatibles($razonCab, $nomOficial)) {
                    $codPrest  = $matchCuit['codigo'];
                    $prestador = $nomOficial !== '' ? $nomOficial : $razonCab;
                }
            }
        } catch (Exception $eMatch) {
            error_log('OCR match CUIT: ' . $eMatch->getMessage());
        }
    }

    // 2. Si el CUIT no calza con la razón social del encabezado, buscar por nombre
    if ($codPrest === '' && $prestador === '' && $razonCab !== '') {
        try {
            $matchNom = rfpResolverPrestadorPorNombre(rfpDb(), $razonCab);
            if (isset($matchNom['codigo']) && $matchNom['codigo'] !== '') {
                $codPrest  = $matchNom['codigo'];
                $prestador = isset($matchNom['nombre']) ? strtoupper(trim($matchNom['nombre'])) : $razonCab;
            }
        } catch (Exception $eNom) {
            error_log('OCR match nombre: ' . $eNom->getMessage());
        }
    }

    // 3. Razón social del encabezado (2.ª línea / SAS), nunca el detalle
    if ($prestador === '') {
        $prestador = $razonCab;
    }

    // 4. Fallback: solo líneas de cabecera, sin ítems de detalle
    if ($prestador === '') {
        $textoCab = isset($campos['encabezado']) && $campos['encabezado'] !== ''
            ? $campos['encabezado']
            : $texto;
        $lineas = preg_split('/\r\n|\r|\n/', $textoCab);
        if (!is_array($lineas)) {
            $lineas = array();
        }
        foreach ($lineas as $linea) {
            $linea = trim($linea);
            if (strlen($linea) <= 5 || str_word_count($linea) <= 1) {
                continue;
            }
            if (preg_match('/^[#_@|]/', $linea) || preg_match('/^\(\d{4}\)/', $linea) || stripos($linea, 'C.P.') !== false) {
                continue;
            }
            if (preg_match('/\b(DESCRIPCION|CONCEPTO|DETALLE|PRESTACIONES|AMBULATORIAS)\b/i', $linea)) {
                continue;
            }
            if (preg_match('/^(ORIGINAL|DUPLICADO|TRIPLICADO|PAG|PÁG|PAGINA|FACTURA|COMPROBANTE|DOCUMENTO|CÓDIGO|CODIGO|TIPO|PUNTO|FECHA|CUIT|C\.U\.I\.T|COND|CONDICI[OÓ]N|DOMICILIO|DIRECCI[OÓ]N)/i', $linea)) {
                continue;
            }
            if (stripos($linea, 'COMEDICA') !== false || stripos($linea, 'SR(ES)') !== false) {
                continue;
            }
            $linea_limpia = trim(preg_replace('/^Razón Social:\s*/i', '', $linea));
            if (preg_match('/(SRL|S\.R\.L|S\.A\.S|S\.A\.|S\.A\b|CLINICA|CLÍNICA|SANATORIO|HOSPITAL|CENTRO|LABORATORIO|INSTITUTO|OMINT|OBRA SOCIAL|MUTUAL|FUNDACION|FUNDACIÓN)/i', $linea_limpia)) {
                $prestador = strtoupper(trim(preg_replace('/^\d+\s+/', '', $linea_limpia)));
                break;
            }
        }
    }

    $prestador = trim($prestador, " \t\n\r\0\x0B-.,_:/\\|");

    // ── Total: optimizado sin riesgo de Backtracking ─────────────────────
    $importe = 0.00;

    // 1. Regla Estricta: Tolera hasta 80 caracteres intermedios pero se detiene al ver números (Súper rápida)
    if (preg_match_all('/(?:Total|Total General|Total en \$|Son pesos|Son|Importe)[^\d]{0,80}?(\d{1,12}(?:[.,\h]\d{3})*[.,]\d{2})(?!\d)/is', $texto, $matches)) {
        $num_str = end($matches[1]);
        $num_str = preg_replace('/\h+/', '', $num_str); // Limpia espacios horizontales
        
        if (preg_match('/[.,](\d{2})$/', $num_str, $dec_match)) {
            $decimales = $dec_match[1];
            $parte_entera = preg_replace('/[^\d]/', '', substr($num_str, 0, -3));
            if (!empty($parte_entera)) {
                $importe = (float)($parte_entera . '.' . $decimales);
            }
        }
    }

    // 2. Fallback Seguro: máximo del documento (tope 100 millones para evitar colapsos)
    if (empty($importe) || $importe == 0.00) {
        if (preg_match_all('/(\d{1,12}(?:[.,\h]\d{3})*[.,]\d{2})(?!\d)/', $texto, $matches)) {
            $max_importe = 0.00;
            foreach ($matches[1] as $num_str) {
                $num_str = preg_replace('/\h+/', '', $num_str); // Limpia espacios horizontales
                if (preg_match('/[.,](\d{2})$/', $num_str, $dec_match)) {
                    $decimales = $dec_match[1];
                    $parte_entera = preg_replace('/[^\d]/', '', substr($num_str, 0, -3));
                    if (!empty($parte_entera)) {
                        $val = (float)($parte_entera . '.' . $decimales);
                        if ($val > $max_importe && $val < 99999999.99) {
                            $max_importe = $val;
                        }
                    }
                }
            }
            $importe = $max_importe;
        }
    }

    // ── Nro factura: Punto de Venta + Comp. N°/Nro (evitar CAE) ───────────
    $nro_factura = '';
    $sucursal    = '';
    if (preg_match('/Punto de Venta.*?(\d{4,5}).*?(?:Comp|Comprobante).*?(\d{8})/is', $texto, $matches)) {
        $nro_factura = str_pad($matches[1], 4, '0', STR_PAD_LEFT) . '-' . $matches[2];
    } elseif (preg_match('/(?<!\d)(\d{4,5})[\.\-\s]+(\d{8})(?!\d)/', $texto, $matches)) {
        $posCtx = strpos($texto, $matches[1]);
        $linea_contexto = ($posCtx !== false) ? substr($texto, max(0, $posCtx - 20), 50) : '';
        if (stripos($linea_contexto, 'CAE') === false) {
            $nro_factura = str_pad($matches[1], 4, '0', STR_PAD_LEFT) . '-' . $matches[2];
        }
    }
    if ($nro_factura !== '' && preg_match('/^(\d+)-(\d+)$/', $nro_factura, $mSuc)) {
        $sucursal = $mSuc[1];
    }

    // Período: siempre mes anterior (se ignora lo que diga el OCR).
    $periodo = DateHelper::getPeriodoAnterior();

    // ── Validación de seguridad (Freno a filas fantasmas) ─────────────────
    if (empty($prestador) && empty($nro_factura) && $importe == 0.00) {
        throw new Exception('El motor OCR no detectó datos legibles. Verificá la calidad del PDF o cargalo manualmente.');
    }

    $model = new FacturasTempModel();
    $resultado = $model->upsertDesdePdf(array(
        'prestador'   => $prestador,
        'cod_prest'   => $codPrest,
        'f_factura'   => isset($campos['fecha']) ? $campos['fecha'] : '',
        'sucursal'    => $sucursal,
        'nro_factura' => $nro_factura,
        'periodo'     => $periodo,
        'total'       => $importe,
        'archivo_pdf' => $safe,
        'texto_ocr'   => $texto,
        'usuario'     => rfpUsuario(),
        'user'        => rfpUsuarioLargo(),
    ));

    $status = isset($resultado['status']) ? $resultado['status'] : 'success';
    if (ob_get_level()) {
        ob_end_clean();
    }
    echo json_encode(array(
        'status'  => $status,
        'id'      => (int) $resultado['id'],
        'message' => ($status === 'updated') ? 'Factura actualizada' : 'Procesado correctamente',
    ));
    exit;
} catch (Exception $e) {
    if (ob_get_level()) {
        ob_end_clean();
    }
    echo json_encode(array(
        'status'  => 'error',
        'message' => $e->getMessage(),
    ));
    exit;
}