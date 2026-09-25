<?php
/**
 * models/FacturasTempModel.php
 * t_facturas_temp = clon de registrf + archivo_pdf + marcado.
 * Sintaxis clásica PHP 5.6.
 */

require_once dirname(__FILE__) . '/../includes/facturas_pdf_common.php';

class FacturasTempModel
{
    private $db;

    public function __construct()
    {
        $this->db = rfpDb();
        rfpAsegurarTabla();
    }

    /**
     * Inserta una factura extraída del PDF.
     * COPRESTADO se completa si el OCR matcheó CUIT en ebamp.
     * O_SOCIAL (COOBRASOC) va vacío.
     * PERIODO (COPERIODO) llega en AA/MM si el OCR lo detectó.
     *
     * @param array $datos
     * @return int
     */
    public function insertarDesdePdf($datos)
    {
        $usr     = isset($datos['usuario']) ? $datos['usuario'] : rfpUsuario();
        $hoy     = date('Y-m-d');
        $archivo = isset($datos['archivo_pdf']) ? $datos['archivo_pdf'] : '';

        $sql = "INSERT INTO t_facturas_temp
                    (COFECHA, COPERIODO, COOBRASOC, COPRESTADO, CONOMPREST,
                     COSUCFAC, CONROFAC, COFECFAC, COIMPFAC, COTOTALFAC,
                     COFECCARGA, CONOMOBRA, COUSUARIO, COCANTIDAD, COCANTCALC,
                     CODISKETTE, COFACTURA, TPFACT, `user`,
                     archivo_pdf, marcado, texto_ocr)
                VALUES
                    (:cofecha, :coperiodo, '', :coprestado, :conomprest,
                     :cosucfac, :conrofac, :cofecfac, :coimpfac, :cototalfac,
                     :cofeccarga, '', :cousuario, 1, 1,
                     0, 'SI', 'FISICA', :userlargo,
                     :archivo_pdf, 0, :texto_ocr)";

        $prest   = isset($datos['prestador']) ? $datos['prestador'] : '';
        $codPr   = isset($datos['cod_prest']) ? $datos['cod_prest'] : '';
        $suc     = isset($datos['sucursal']) ? $datos['sucursal'] : '';
        $nro     = isset($datos['nro_factura']) ? $datos['nro_factura'] : '';
        $periodo = isset($datos['periodo']) ? $datos['periodo'] : '';

        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(
            ':cofecha'     => $hoy,
            ':coperiodo'   => substr($periodo, 0, 5),
            ':coprestado'  => substr($codPr, 0, 18),
            ':conomprest'  => substr($prest, 0, 80),
            ':cosucfac'    => substr($suc, 0, 4),
            ':conrofac'    => substr($nro, 0, 18),
            ':cofecfac'    => isset($datos['f_factura']) && $datos['f_factura'] !== '' ? $datos['f_factura'] : $hoy,
            ':coimpfac'    => isset($datos['total']) ? $datos['total'] : 0,
            ':cototalfac'  => isset($datos['total']) ? $datos['total'] : 0,
            ':cofeccarga'  => $hoy,
            ':cousuario'   => $usr,
            ':userlargo'   => isset($datos['user']) ? $datos['user'] : rfpUsuarioLargo(),
            ':archivo_pdf' => $archivo,
            ':texto_ocr'   => isset($datos['texto_ocr']) ? $datos['texto_ocr'] : null,
        ));

        return (int) $this->db->lastInsertId();
    }

    /**
     * Inserta o actualiza por CONROFAC (nro de factura).
     * Si ya existe, prioriza el importe mayor (corrige lecturas de $0.00).
     * No pisa COPRESTADO / COOBRASOC ni el marcado que ya cargó el usuario.
     *
     * @param array $datos
     * @return array status (success|updated), id
     */
    public function upsertDesdePdf($datos)
    {
        $nro = isset($datos['nro_factura']) ? trim($datos['nro_factura']) : '';
        if ($nro !== '') {
            $stmtCheck = $this->db->prepare(
                "SELECT id, CONOMPREST, COPRESTADO, COSUCFAC, COFECFAC, COIMPFAC, COTOTALFAC, COPERIODO, texto_ocr
                 FROM t_facturas_temp
                 WHERE TRIM(CONROFAC) = :nro
                 LIMIT 1"
            );
            $stmtCheck->execute(array(':nro' => $nro));
            $existe = $stmtCheck->fetch(PDO::FETCH_ASSOC);

            if ($existe) {
                $importeNuevo = isset($datos['total']) ? (float) $datos['total'] : 0.00;
                $importeViejo = 0.00;
                if (isset($existe['COTOTALFAC']) && $existe['COTOTALFAC'] !== null && $existe['COTOTALFAC'] !== '') {
                    $importeViejo = (float) $existe['COTOTALFAC'];
                } elseif (isset($existe['COIMPFAC']) && $existe['COIMPFAC'] !== null && $existe['COIMPFAC'] !== '') {
                    $importeViejo = (float) $existe['COIMPFAC'];
                }
                $nuevoImporte = ($importeNuevo > $importeViejo) ? $importeNuevo : $importeViejo;

                $prest = isset($datos['prestador']) ? trim($datos['prestador']) : '';
                if ($prest === '' && isset($existe['CONOMPREST'])) {
                    $prest = $existe['CONOMPREST'];
                }

                $codPr = isset($datos['cod_prest']) ? trim($datos['cod_prest']) : '';
                if ($codPr === '' && isset($existe['COPRESTADO'])) {
                    $codPr = trim($existe['COPRESTADO']);
                }

                $suc = isset($datos['sucursal']) ? trim($datos['sucursal']) : '';
                if ($suc === '' && isset($existe['COSUCFAC'])) {
                    $suc = $existe['COSUCFAC'];
                }

                $fecha = isset($datos['f_factura']) ? $datos['f_factura'] : '';
                if ($fecha === '' && isset($existe['COFECFAC'])) {
                    $fecha = $existe['COFECFAC'];
                }

                $periodo = isset($datos['periodo']) ? trim($datos['periodo']) : '';
                if ($periodo === '' && isset($existe['COPERIODO'])) {
                    $periodo = $existe['COPERIODO'];
                }

                $stmtUpd = $this->db->prepare(
                    "UPDATE t_facturas_temp
                     SET CONOMPREST  = :conomprest,
                         COPRESTADO  = :coprestado,
                         COSUCFAC    = :cosucfac,
                         COFECFAC    = :cofecfac,
                         COIMPFAC    = :coimpfac,
                         COTOTALFAC  = :cototalfac,
                         COPERIODO   = :coperiodo,
                         archivo_pdf = :archivo_pdf,
                         texto_ocr   = :texto_ocr
                     WHERE id = :id"
                );
                $stmtUpd->execute(array(
                    ':conomprest'  => substr($prest, 0, 80),
                    ':coprestado'  => substr($codPr, 0, 18),
                    ':cosucfac'    => substr($suc, 0, 4),
                    ':cofecfac'    => $fecha,
                    ':coimpfac'    => $nuevoImporte,
                    ':cototalfac'  => $nuevoImporte,
                    ':coperiodo'   => substr($periodo, 0, 5),
                    ':archivo_pdf' => isset($datos['archivo_pdf']) ? $datos['archivo_pdf'] : '',
                    ':texto_ocr'   => (isset($datos['texto_ocr']) && trim($datos['texto_ocr']) !== '')
                        ? $datos['texto_ocr']
                        : (isset($existe['texto_ocr']) ? $existe['texto_ocr'] : null),
                    ':id'          => (int) $existe['id'],
                ));

                return array(
                    'status' => 'updated',
                    'id'     => (int) $existe['id'],
                );
            }
        }

        $id = $this->insertarDesdePdf($datos);
        return array(
            'status' => 'success',
            'id'     => $id,
        );
    }

    /**
     * @return array
     */
    public function listarPendientes()
    {
        $sql = "SELECT id,
                       TRIM(COPRESTADO)  AS COD_PREST,
                       TRIM(CONOMPREST)  AS PRESTADOR,
                       COFECFAC          AS F_FACTURA,
                       TRIM(COSUCFAC)    AS SUCURSAL,
                       TRIM(CONROFAC)    AS NRO_FACTURA,
                       GREATEST(COALESCE(COTOTALFAC, 0), COALESCE(COIMPFAC, 0)) AS TOTAL,
                       COIMPFAC          AS IMPORTE,
                       TRIM(COOBRASOC)   AS O_SOCIAL,
                       TRIM(COPERIODO)   AS PERIODO,
                       TRIM(COUSUARIO)   AS USUARIO,
                       COFECCARGA        AS F_CARGA,
                       archivo_pdf,
                       marcado,
                       texto_ocr
                FROM t_facturas_temp
                ORDER BY id DESC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @param int $id
     * @return array|false
     */
    public function obtenerPorId($id)
    {
        $sql = "SELECT id,
                       TRIM(COPRESTADO)  AS COD_PREST,
                       TRIM(CONOMPREST)  AS PRESTADOR,
                       COFECFAC          AS F_FACTURA,
                       TRIM(COSUCFAC)    AS SUCURSAL,
                       TRIM(CONROFAC)    AS NRO_FACTURA,
                       GREATEST(COALESCE(COTOTALFAC, 0), COALESCE(COIMPFAC, 0)) AS TOTAL,
                       COIMPFAC          AS IMPORTE,
                       TRIM(COOBRASOC)   AS O_SOCIAL,
                       TRIM(COPERIODO)   AS PERIODO,
                       TRIM(COUSUARIO)   AS USUARIO,
                       COFECCARGA        AS F_CARGA,
                       archivo_pdf,
                       marcado,
                       texto_ocr
                FROM t_facturas_temp
                WHERE id = :id
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(':id' => (int) $id));
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * @param int   $id
     * @param array $campos  COD_PREST, NRO_FACTURA, O_SOCIAL, PERIODO, SUCURSAL, IMPORTE
     */
    public function actualizarFila($id, $campos)
    {
        $sql = "UPDATE t_facturas_temp
                SET COPRESTADO = :cod,
                    CONROFAC   = :nro,
                    COSUCFAC   = :suc,
                    COOBRASOC  = :os,
                    CONOMOBRA  = :nomos,
                    COPERIODO  = :per,
                    COIMPFAC   = :imp,
                    COTOTALFAC = :tot,
                    marcado    = 1
                WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $imp = isset($campos['IMPORTE']) ? (float) $campos['IMPORTE'] : 0;
        $stmt->execute(array(
            ':cod'   => isset($campos['COD_PREST']) ? $campos['COD_PREST'] : '',
            ':nro'   => isset($campos['NRO_FACTURA']) ? $campos['NRO_FACTURA'] : '',
            ':suc'   => isset($campos['SUCURSAL']) ? $campos['SUCURSAL'] : '',
            ':os'    => isset($campos['O_SOCIAL']) ? $campos['O_SOCIAL'] : '',
            ':nomos' => isset($campos['CONOMOBRA']) ? $campos['CONOMOBRA'] : '',
            ':per'   => isset($campos['PERIODO']) ? $campos['PERIODO'] : '',
            ':imp'   => $imp,
            ':tot'   => $imp,
            ':id'    => (int) $id,
        ));
    }

    /**
     * INSERT INTO registrf SELECT ... FROM t_facturas_temp WHERE marcado = 1
     * excluyendo id, marcado, fechaupdate y texto_ocr.
     * Incluye archivo_pdf (nombre ya renombrado).
     */
    public function traspasarMarcadas()
    {
        rfpAsegurarArchivoPdfRegistrf($this->db);
        $oficial = rfpColumnasOficial($this->db);
        $temp    = rfpColumnasTemp($this->db);
        $excluir = array(
            'ID' => true, 'MARCADO' => true,
            'FECHAUPDATE' => true, 'TEXTO_OCR' => true,
        );

        $cols = array();
        foreach ($oficial as $up => $real) {
            if (isset($excluir[$up])) {
                continue;
            }
            if (!isset($temp[$up])) {
                continue;
            }
            $cols[] = ($up === 'USER') ? '`user`' : $real;
        }
        if (!$cols) {
            throw new PDOException('No hay columnas coincidentes para el traspaso a registrf.');
        }

        $lista = implode(', ', $cols);
        $sql = 'INSERT INTO registrf (' . $lista . ') SELECT ' . $lista
             . ' FROM t_facturas_temp WHERE marcado = 1';
        $this->db->exec($sql);
        return $this->db->query('SELECT ROW_COUNT()')->fetchColumn();
    }

    public function borrarMarcadas()
    {
        $stmt = $this->db->exec('DELETE FROM t_facturas_temp WHERE marcado = 1');
        return $stmt;
    }

    public function eliminarIds($ids)
    {
        $limpios = array();
        if (!is_array($ids)) {
            return 0;
        }
        foreach ($ids as $id) {
            $n = (int) $id;
            if ($n > 0) {
                $limpios[] = $n;
            }
        }
        $limpios = array_values(array_unique($limpios));
        if (!$limpios) {
            return 0;
        }
        $in = implode(',', array_fill(0, count($limpios), '?'));
        $stmt = $this->db->prepare('DELETE FROM t_facturas_temp WHERE id IN (' . $in . ')');
        $stmt->execute($limpios);
        return $stmt->rowCount();
    }

    /**
     * @return PDO
     */
    public function pdo()
    {
        return $this->db;
    }
}
