<?php
/**
 * busqueda_dinamica.php
 * AJAX: validar / buscar prestador (ebamp) y obra social (obrasoc).
 * PHP 5.6 — PDO, array(), isset(), try/catch.
 */

require_once dirname(__FILE__) . '/includes/facturas_pdf_common.php';

rfpRequireAuth();
rfpRequirePermiso();

header('Content-Type: application/json; charset=utf-8');

if (!rfpCsrfOk()) {
    rfpJsonError('Token de seguridad inválido.');
}

$accion = isset($_POST['accion']) ? trim($_POST['accion']) : '';
$tipo   = isset($_POST['tipo_busqueda']) ? trim($_POST['tipo_busqueda']) : '';
if ($tipo === '' && isset($_POST['tipo'])) {
    $tipo = trim($_POST['tipo']);
}
$valor = isset($_POST['valor']) ? trim($_POST['valor']) : '';

$tipo   = strtolower($tipo);
$accion = strtolower($accion);

if ($accion !== 'validar' && $accion !== 'buscar') {
    rfpJsonError('Acción no válida.');
}
if ($tipo !== 'prestador' && $tipo !== 'obrasoc') {
    rfpJsonError('Tipo de búsqueda no válido.');
}

try {
    $db = rfpDb();

    if ($accion === 'validar') {
        if ($valor === '') {
            rfpJsonOk(array('existe' => false, 'error' => 'Valor vacío.'));
        }

        if ($tipo === 'prestador') {
            $stmt = $db->prepare(
                'SELECT TRIM(codigo) AS codigo
                 FROM ebamp
                 WHERE TRIM(codigo) = :valor
                 LIMIT 1'
            );
            $stmt->execute(array(':valor' => $valor));
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row && isset($row['codigo']) && $row['codigo'] !== '') {
                rfpJsonOk(array(
                    'existe' => true,
                    'codigo' => $row['codigo'],
                ));
            }
            rfpJsonOk(array('existe' => false, 'error' => 'Prestador no encontrado.'));
        }

        $stmt = $db->prepare(
            'SELECT TRIM(TACODIGO) AS tacodigo
             FROM obrasoc
             WHERE TRIM(TACODIGO) = :valor
             LIMIT 1'
        );
        $stmt->execute(array(':valor' => $valor));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && isset($row['tacodigo']) && $row['tacodigo'] !== '') {
            rfpJsonOk(array(
                'existe'   => true,
                'tacodigo' => $row['tacodigo'],
            ));
        }
        rfpJsonOk(array('existe' => false, 'error' => 'Obra social no encontrada.'));
    }

    // accion == buscar
    $like = '%' . $valor . '%';

    if ($accion == 'buscar' && $tipo == 'prestador') {
        $termino_original = trim(isset($_POST['valor']) ? $_POST['valor'] : $valor);

        $unwanted_array = array(
            'Š'=>'S', 'š'=>'s', 'Ž'=>'Z', 'ž'=>'z', 'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A', 'Å'=>'A', 'Æ'=>'A', 'Ç'=>'C',
            'È'=>'E', 'É'=>'E', 'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I', 'Ï'=>'I', 'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O',
            'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ù'=>'U', 'Ú'=>'U', 'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'Ss',
            'à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a', 'å'=>'a', 'æ'=>'a', 'ç'=>'c', 'è'=>'e', 'é'=>'e', 'ê'=>'e', 'ë'=>'e',
            'ì'=>'i', 'í'=>'i', 'î'=>'i', 'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o', 'ô'=>'o', 'õ'=>'o', 'ö'=>'o', 'ø'=>'o',
            'ù'=>'u', 'ú'=>'u', 'û'=>'u', 'ý'=>'y', 'þ'=>'b', 'ÿ'=>'y',
        );
        $termino_original = strtr($termino_original, $unwanted_array);

        // 1. Reemplazar puntuación con espacios para despegar palabras (ej: "S.A.FACTURA" -> "S A FACTURA")
        $q_limpio = @preg_replace('/[^a-zA-Z0-9áéíóúÁÉÍÓÚñÑ]/u', ' ', $termino_original);
        if ($q_limpio === null) {
            $q_limpio = preg_replace('/[^a-zA-Z0-9]/', ' ', $termino_original);
        }

        // 2. Separar el string en un array de palabras
        $palabras = preg_split('/\s+/', $q_limpio, -1, PREG_SPLIT_NO_EMPTY);
        if (!is_array($palabras)) {
            $palabras = array();
        }

        // 3. Palabras basura de OCR que no deben buscarse
        $basura_ocr = array(
            'S', 'A', 'SA', 'SRL', 'FACTURA', 'ORIGINAL', 'DUPLICADO', 'COMPROBANTE',
            'C', 'DE', 'LA', 'EL', 'LOS', 'LAS', 'EN', 'Y', 'PARA',
        );

        $palabras_utiles = array();
        foreach ($palabras as $p) {
            $p_upper = strtoupper($p);
            if (!in_array($p_upper, $basura_ocr) && strlen($p_upper) > 2) {
                $palabras_utiles[] = $p_upper;
            }
        }

        if (empty($palabras_utiles)) {
            $palabras_utiles = array();
            foreach ($palabras as $p) {
                $p_upper = strtoupper(trim($p));
                if ($p_upper !== '') {
                    $palabras_utiles[] = $p_upper;
                }
            }
        }

        if (empty($palabras_utiles)) {
            rfpJsonOk(array(
                'status'     => 'success',
                'data'       => array(),
                'resultados' => array(),
            ));
        }

        // 4. WHERE dinámico: todas las palabras útiles deben coincidir (AND)
        $where_condiciones = array();
        $params = array();
        foreach ($palabras_utiles as $i => $palabra) {
            $where_condiciones[] = '(nombre LIKE :pn' . $i . ' OR nomfantas LIKE :pf' . $i . ')';
            $params[':pn' . $i] = '%' . $palabra . '%';
            $params[':pf' . $i] = '%' . $palabra . '%';
        }

        $where_sql = implode(' AND ', $where_condiciones);

        $stmt = $db->prepare(
            'SELECT TRIM(codigo) AS codigo,
                    TRIM(nombre) AS nombre,
                    TRIM(nomfantas) AS nomfantas,
                    TRIM(categ) AS categ
             FROM ebamp
             WHERE ' . $where_sql . '
             LIMIT 20'
        );
        $stmt->execute($params);
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!is_array($resultados)) {
            $resultados = array();
        }

        rfpJsonOk(array(
            'status'     => 'success',
            'data'       => $resultados,
            'resultados' => $resultados,
        ));
    }

    $rows = array();
    if ($valor !== '') {
        $stmt = $db->prepare(
            'SELECT TRIM(TACODIGO) AS tacodigo,
                    TRIM(TADESCRIP) AS tadescrip
             FROM obrasoc
             WHERE TRIM(TACODIGO) = :exact
                OR TACODIGO LIKE :like1
                OR TADESCRIP LIKE :like2
             ORDER BY TADESCRIP ASC
             LIMIT 80'
        );
        $stmt->execute(array(
            ':exact' => $valor,
            ':like1' => $like,
            ':like2' => $like,
        ));
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    if (!is_array($rows) || !$rows) {
        $stmt = $db->prepare(
            'SELECT TRIM(TACODIGO) AS tacodigo,
                    TRIM(TADESCRIP) AS tadescrip
             FROM obrasoc
             ORDER BY TADESCRIP ASC
             LIMIT 300'
        );
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!is_array($rows)) {
            $rows = array();
        }
    }
    rfpJsonOk(array('resultados' => $rows));
} catch (PDOException $e) {
    rfpJsonError('Error de base de datos: ' . $e->getMessage());
} catch (Exception $e) {
    rfpJsonError($e->getMessage());
}
