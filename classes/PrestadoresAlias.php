<?php
/**
 * classes/PrestadoresAlias.php
 * Diccionario OCR → código de prestador (t_prestadores_alias).
 * PHP 5.6.
 */
class PrestadoresAlias
{
    /**
     * @param PDO $db
     */
    public static function asegurarTabla($db)
    {
        try {
            $db->exec(
                "CREATE TABLE IF NOT EXISTS t_prestadores_alias (
                    id INT NOT NULL AUTO_INCREMENT,
                    nombre_ocr VARCHAR(160) NOT NULL DEFAULT '',
                    codigo_prestador VARCHAR(20) NOT NULL DEFAULT '',
                    cuit VARCHAR(15) DEFAULT NULL,
                    fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (id),
                    KEY idx_nombre_ocr (nombre_ocr),
                    KEY idx_cuit (cuit)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8"
            );
        } catch (PDOException $e) {
            error_log('PrestadoresAlias::asegurarTabla: ' . $e->getMessage());
        }
    }

    /**
     * @param string $s
     * @return string
     */
    public static function normalizarNombre($s)
    {
        $s = strtoupper(trim(isset($s) ? $s : ''));
        $s = preg_replace('/\s+/', ' ', $s);
        return $s;
    }

    /**
     * @param string $cuit
     * @return string
     */
    public static function normalizarCuit($cuit)
    {
        return preg_replace('/\D/', '', isset($cuit) ? $cuit : '');
    }

    /**
     * @param PDO    $db
     * @param string $nombreOcr
     * @param string $cuit
     * @return array codigo, nombre_ocr, cuit
     */
    public static function buscar($db, $nombreOcr, $cuit)
    {
        $out = array('codigo' => '', 'nombre_ocr' => '', 'cuit' => '');
        self::asegurarTabla($db);
        $nombreOcr = self::normalizarNombre($nombreOcr);
        $cuitDig   = self::normalizarCuit($cuit);

        try {
            if (strlen($cuitDig) === 11) {
                $stmt = $db->prepare(
                    "SELECT TRIM(codigo_prestador) AS codigo,
                            TRIM(nombre_ocr) AS nombre_ocr,
                            TRIM(cuit) AS cuit
                     FROM t_prestadores_alias
                     WHERE REPLACE(REPLACE(REPLACE(TRIM(IFNULL(cuit,'')), '-', ''), '.', ''), ' ', '') = :c
                       AND TRIM(codigo_prestador) <> ''
                     ORDER BY fecha_registro DESC
                     LIMIT 1"
                );
                $stmt->execute(array(':c' => $cuitDig));
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row && isset($row['codigo']) && $row['codigo'] !== '') {
                    return $row;
                }
            }

            if ($nombreOcr !== '' && strlen($nombreOcr) >= 4) {
                $stmt = $db->prepare(
                    "SELECT TRIM(codigo_prestador) AS codigo,
                            TRIM(nombre_ocr) AS nombre_ocr,
                            TRIM(cuit) AS cuit
                     FROM t_prestadores_alias
                     WHERE UPPER(TRIM(nombre_ocr)) = :n
                       AND TRIM(codigo_prestador) <> ''
                     ORDER BY fecha_registro DESC
                     LIMIT 1"
                );
                $stmt->execute(array(':n' => $nombreOcr));
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row && isset($row['codigo']) && $row['codigo'] !== '') {
                    return $row;
                }
            }
        } catch (PDOException $e) {
            error_log('PrestadoresAlias::buscar: ' . $e->getMessage());
        }
        return $out;
    }

    /**
     * @param PDO    $db
     * @param string $nombreOcr
     * @param string $codigo
     * @param string $cuit
     * @return bool
     */
    public static function guardar($db, $nombreOcr, $codigo, $cuit)
    {
        $nombreOcr = self::normalizarNombre($nombreOcr);
        $codigo    = strtoupper(trim(isset($codigo) ? $codigo : ''));
        $cuitDig   = self::normalizarCuit($cuit);
        if ($nombreOcr === '' || $codigo === '') {
            return false;
        }
        self::asegurarTabla($db);

        try {
            $id = 0;
            $stmt = $db->prepare(
                "SELECT id FROM t_prestadores_alias
                 WHERE UPPER(TRIM(nombre_ocr)) = :n
                 LIMIT 1"
            );
            $stmt->execute(array(':n' => $nombreOcr));
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row && isset($row['id'])) {
                $id = (int) $row['id'];
            }

            if ($id <= 0 && strlen($cuitDig) === 11) {
                $stmt = $db->prepare(
                    "SELECT id FROM t_prestadores_alias
                     WHERE REPLACE(REPLACE(REPLACE(TRIM(IFNULL(cuit,'')), '-', ''), '.', ''), ' ', '') = :c
                     LIMIT 1"
                );
                $stmt->execute(array(':c' => $cuitDig));
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row && isset($row['id'])) {
                    $id = (int) $row['id'];
                }
            }

            if ($id > 0) {
                $up = $db->prepare(
                    "UPDATE t_prestadores_alias
                     SET codigo_prestador = :cod,
                         nombre_ocr = :n,
                         cuit = :cuit,
                         fecha_registro = NOW()
                     WHERE id = :id"
                );
                $up->execute(array(
                    ':cod'  => $codigo,
                    ':n'    => $nombreOcr,
                    ':cuit' => strlen($cuitDig) === 11 ? $cuitDig : null,
                    ':id'   => $id,
                ));
                return true;
            }

            $ins = $db->prepare(
                "INSERT INTO t_prestadores_alias (nombre_ocr, codigo_prestador, cuit, fecha_registro)
                 VALUES (:n, :cod, :cuit, NOW())"
            );
            $ins->execute(array(
                ':n'    => $nombreOcr,
                ':cod'  => $codigo,
                ':cuit' => strlen($cuitDig) === 11 ? $cuitDig : null,
            ));
            return true;
        } catch (PDOException $e) {
            error_log('PrestadoresAlias::guardar: ' . $e->getMessage());
            return false;
        }
    }
}
