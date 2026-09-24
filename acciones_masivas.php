<?php
/**
 * acciones_masivas.php
 * Confirmar (transacción: UPDATE temp → INSERT SELECT registrf → DELETE)
 * o eliminar filas de t_facturas_temp.
 * PHP 5.6 — array() / isset() ternarios / PDO try-catch.
 */

require_once dirname(__FILE__) . '/includes/facturas_pdf_common.php';
require_once dirname(__FILE__) . '/models/FacturasTempModel.php';

rfpRequireAuth();
rfpRequirePermiso();

header('Content-Type: application/json; charset=utf-8');

if (!rfpCsrfOk()) {
    rfpJsonError('Token de seguridad inválido.');
}

$accion = isset($_POST['accion']) ? trim($_POST['accion']) : 'confirmar';

try {
    $model = new FacturasTempModel();
    $db    = $model->pdo();

    if ($accion === 'eliminar') {
        $ids = isset($_POST['ids']) ? $_POST['ids'] : array();
        if (!is_array($ids) || !$ids) {
            $uno = isset($_POST['id']) ? (int) $_POST['id'] : 0;
            if ($uno > 0) {
                $ids = array($uno);
            }
        }
        if (!$ids) {
            rfpJsonError('No hay filas para eliminar.');
        }
        $n = $model->eliminarIds($ids);
        rfpJsonOk(array('eliminadas' => $n));
    }

    // ── Confirmar seleccionadas ──────────────────────────────────────────
    $filas = isset($_POST['filas']) ? $_POST['filas'] : array();
    if (!is_array($filas) || !$filas) {
        rfpJsonError('No hay filas completas para confirmar.');
    }

    $db->beginTransaction();

    try {
        foreach ($filas as $fila) {
            if (!is_array($fila)) {
                continue;
            }
            $id = isset($fila['id']) ? (int) $fila['id'] : 0;
            if ($id <= 0) {
                continue;
            }

            $cod = isset($fila['COD_PREST']) ? trim($fila['COD_PREST']) : '';
            $nro = isset($fila['NRO_FACTURA']) ? trim($fila['NRO_FACTURA']) : '';
            $os  = isset($fila['O_SOCIAL']) ? trim($fila['O_SOCIAL']) : '';
            $per = isset($fila['PERIODO']) ? trim($fila['PERIODO']) : '';

            $suc = '';
            if (preg_match('/^(\d{1,5})\s*[\-–]\s*(\d+)$/', $nro, $m)) {
                $suc = str_pad($m[1], 4, '0', STR_PAD_LEFT);
                $nro = str_pad(preg_replace('/\D/', '', $m[2]), 8, '0', STR_PAD_LEFT);
            } else {
                $nro = preg_replace('/\D/', '', $nro);
            }

            $periodoAamm = rfpPeriodoAamm($per);
            $osRes = rfpResolverOs($db, $os);
            $prRes = rfpResolverPrestadorCodigo($db, $cod);

            $model->actualizarFila($id, array(
                'COD_PREST'   => $prRes['codigo'] !== '' ? $prRes['codigo'] : $cod,
                'NRO_FACTURA' => $nro,
                'SUCURSAL'    => $suc,
                'O_SOCIAL'    => $osRes['codigo'] !== '' ? $osRes['codigo'] : $os,
                'CONOMOBRA'   => $osRes['nombre'],
                'PERIODO'     => $periodoAamm,
            ));

            if ($prRes['nombre'] !== '') {
                $up = $db->prepare('UPDATE t_facturas_temp SET CONOMPREST = :n, COCATEG = :c WHERE id = :id');
                $up->execute(array(
                    ':n'  => $prRes['nombre'],
                    ':c'  => $prRes['categ'],
                    ':id' => $id,
                ));
            }
        }

        rfpRenombrarPdfsMarcados($db);
        $model->traspasarMarcadas();
        $borradas = $model->borrarMarcadas();

        $db->commit();
        rfpJsonOk(array(
            'confirmadas' => (int) $borradas,
            'msg'         => 'Facturas confirmadas y pasadas al registro oficial.',
        ));
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        throw $e;
    }
} catch (PDOException $e) {
    rfpJsonError('Error de base de datos: ' . $e->getMessage());
} catch (Exception $e) {
    rfpJsonError($e->getMessage());
}

/**
 * Renombra el PDF físico de cada fila marcada y actualiza archivo_pdf.
 * Formato: CODIGO_NUMEROFACTURA_nombreviejo.pdf
 */
function rfpRenombrarPdfsMarcados($db)
{
    $dir = rfpDirUploads();
    $stmt = $db->query(
        "SELECT id,
                TRIM(COPRESTADO) AS codigo,
                TRIM(CONROFAC)   AS nro,
                archivo_pdf
         FROM t_facturas_temp
         WHERE marcado = 1"
    );
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!$rows) {
        return;
    }

    $up = $db->prepare('UPDATE t_facturas_temp SET archivo_pdf = :n WHERE id = :id');

    foreach ($rows as $row) {
        $viejo = isset($row['archivo_pdf']) ? trim($row['archivo_pdf']) : '';
        if ($viejo === '') {
            continue;
        }

        $cod = preg_replace('/[^A-Za-z0-9]/', '', isset($row['codigo']) ? $row['codigo'] : '');
        $nro = preg_replace('/[^A-Za-z0-9]/', '', isset($row['nro']) ? $row['nro'] : '');
        $base = basename($viejo);
        $nuevo = $cod . '_' . $nro . '_' . $base;
        $nuevo = preg_replace('/_+/', '_', $nuevo);
        $nuevo = preg_replace('/^_+/', '', $nuevo);

        if ($nuevo === '' || $nuevo === $base) {
            continue;
        }

        $from = $dir . $base;
        $to   = $dir . $nuevo;

        if (is_file($from)) {
            if (!@rename($from, $to)) {
                throw new Exception('No se pudo renombrar el PDF: ' . $base);
            }
            $up->execute(array(':n' => $nuevo, ':id' => (int) $row['id']));
        } elseif (is_file($to)) {
            $up->execute(array(':n' => $nuevo, ':id' => (int) $row['id']));
        }
    }
}

/**
 * @return array codigo, nombre
 */
function rfpResolverOs($db, $valor)
{
    $out = array('codigo' => '', 'nombre' => '');
    $valor = trim(isset($valor) ? $valor : '');
    if ($valor === '') {
        return $out;
    }
    try {
        $stmt = $db->prepare(
            "SELECT TRIM(TACODIGO) AS cosoc, TRIM(TADESCRIP) AS nombre
             FROM obrasoc WHERE TRIM(TACODIGO) = :c LIMIT 1"
        );
        $stmt->execute(array(':c' => $valor));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            return array('codigo' => $row['cosoc'], 'nombre' => $row['nombre']);
        }
        $stmt = $db->prepare(
            "SELECT TRIM(TACODIGO) AS cosoc, TRIM(TADESCRIP) AS nombre
             FROM obrasoc WHERE TADESCRIP LIKE :n
             ORDER BY LENGTH(TRIM(TADESCRIP)) ASC LIMIT 1"
        );
        $stmt->execute(array(':n' => '%' . $valor . '%'));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            return array('codigo' => $row['cosoc'], 'nombre' => $row['nombre']);
        }
    } catch (PDOException $e) {
        error_log('rfpResolverOs: ' . $e->getMessage());
    }
    $out['codigo'] = $valor;
    $out['nombre'] = $valor;
    return $out;
}

/**
 * @return array codigo, nombre, categ
 */
function rfpResolverPrestadorCodigo($db, $codigo)
{
    $out = array('codigo' => '', 'nombre' => '', 'categ' => '');
    $codigo = trim(isset($codigo) ? $codigo : '');
    if ($codigo === '') {
        return $out;
    }
    try {
        $stmt = $db->prepare(
            "SELECT TRIM(codigo) AS codigo, TRIM(nombre) AS nombre, TRIM(categ) AS categ
             FROM ebamp
             WHERE TRIM(codigo) = :c OR TRIM(matricula) = :m
             LIMIT 1"
        );
        $stmt->execute(array(':c' => $codigo, ':m' => $codigo));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            return $row;
        }
    } catch (PDOException $e) {
        error_log('rfpResolverPrestadorCodigo: ' . $e->getMessage());
    }
    $out['codigo'] = $codigo;
    return $out;
}
