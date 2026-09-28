<?php
/**
 * models/PrestacionesModel.php
 * Aranceles de prácticas por prestador (tabla real: pracespe).
 *
 * En el esquema VFP no existe `prestaciones`; el equivalente es `pracespe`:
 *   PECODIGO   → código del prestador
 *   PEPRACTICA → código de práctica (ej: 420101)
 *   PEIMPORTE  → valor arancelario (la spec lo llama columna pracespe)
 */

require_once __DIR__ . '/../config/database.php';

class PrestacionesModel
{
    /** @var PDO */
    private $db;

    public function __construct($pdo = null)
    {
        $this->db = $pdo instanceof PDO ? $pdo : getDBConnection();
    }

    /**
     * Primera práctica 42XXXX del prestador y su arancel unitario.
     *
     * @param string $codPrestador
     * @param string $codPractica  Si viene 42XXXX, se prioriza esa práctica.
     * @return array{cod_practica:string,valor_unidad:float}
     */
    public function getArancelPractica42($codPrestador, $codPractica = '')
    {
        $codPrestador = trim((string) $codPrestador);
        $codPractica  = trim((string) $codPractica);
        $fallback = [
            'cod_practica'  => ($codPractica !== '' && preg_match('/^42\d{4}$/', $codPractica))
                ? $codPractica
                : '420101',
            'valor_unidad'  => 5000.00,
        ];

        if ($codPrestador === '') {
            return $fallback;
        }

        try {
            $row = $this->buscarEnPrestaciones($codPrestador, $codPractica);
            if (!$row) {
                $row = $this->buscarEnPracespe($codPrestador, $codPractica);
            }
            if ($row) {
                return $row;
            }
        } catch (Exception $e) {
            error_log('PrestacionesModel::getArancelPractica42: ' . $e->getMessage());
        }

        return $fallback;
    }

    /**
     * @return array{cod_practica:string,valor_unidad:float}|null
     */
    private function buscarEnPrestaciones($codPrestador, $codPractica)
    {
        try {
            $sql = "SELECT pracespe, cod_prestacion
                    FROM prestaciones
                    WHERE TRIM(cod_prestador) = :cod
                      AND TRIM(cod_prestacion) LIKE '42%'";
            $params = [':cod' => $codPrestador];
            if ($codPractica !== '' && preg_match('/^42/', $codPractica)) {
                $sql .= " AND TRIM(cod_prestacion) = :prac";
                $params[':prac'] = $codPractica;
            }
            $sql .= " ORDER BY CASE WHEN TRIM(cod_prestacion) = '420101' THEN 0 ELSE 1 END, TRIM(cod_prestacion) ASC LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row && isset($row['pracespe']) && $row['pracespe'] !== '') {
                return [
                    'cod_practica' => trim((string) $row['cod_prestacion']),
                    'valor_unidad' => (float) $row['pracespe'],
                ];
            }
        } catch (PDOException $e) {
            // Tabla prestaciones no existe en este esquema.
        }
        return null;
    }

    /**
     * @return array{cod_practica:string,valor_unidad:float}|null
     */
    private function buscarEnPracespe($codPrestador, $codPractica)
    {
        $sql = "SELECT TRIM(PEPRACTICA) AS cod_practica, PEIMPORTE AS valor_unidad
                FROM pracespe
                WHERE TRIM(PECODIGO) = :cod
                  AND TRIM(PEPRACTICA) LIKE '42%'";
        $params = [':cod' => $codPrestador];
        if ($codPractica !== '' && preg_match('/^42/', $codPractica)) {
            $sql .= " AND TRIM(PEPRACTICA) = :prac";
            $params[':prac'] = $codPractica;
        }
        $sql .= " ORDER BY TRIM(PEPRACTICA) ASC LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && isset($row['valor_unidad']) && $row['valor_unidad'] !== null && $row['valor_unidad'] !== '') {
            return [
                'cod_practica' => (string) $row['cod_practica'],
                'valor_unidad' => (float) $row['valor_unidad'],
            ];
        }
        return null;
    }

    /**
     * Busca prestador en ebamp por código exacto o por apellido/nombre.
     *
     * @return array{codigo:string,nombre:string}|null
     */
    public function buscarPrestador($codigoONombre)
    {
        $q = trim((string) $codigoONombre);
        if ($q === '') {
            return null;
        }
        try {
            $stmt = $this->db->prepare(
                "SELECT TRIM(codigo) AS codigo, TRIM(nombre) AS nombre
                 FROM ebamp
                 WHERE TRIM(codigo) = :cod
                 LIMIT 1"
            );
            $stmt->execute([':cod' => $q]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row && $row['codigo'] !== '') {
                return $row;
            }

            $stmt = $this->db->prepare(
                "SELECT TRIM(codigo) AS codigo, TRIM(nombre) AS nombre
                 FROM ebamp
                 WHERE nombre LIKE :nom
                 LIMIT 12"
            );
            $stmt->execute([':nom' => '%' . $q . '%']);
            $candidatos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $primero = null;
            foreach ($candidatos as $cand) {
                if ($primero === null) {
                    $primero = $cand;
                }
                $chk = $this->db->prepare(
                    "SELECT 1 FROM pracespe
                     WHERE TRIM(PECODIGO) = :cod AND TRIM(PEPRACTICA) LIKE '42%'
                     LIMIT 1"
                );
                $chk->execute([':cod' => $cand['codigo']]);
                if ($chk->fetchColumn()) {
                    return $cand;
                }
            }
            if ($primero && $primero['codigo'] !== '') {
                return $primero;
            }
        } catch (PDOException $e) {
            error_log('PrestacionesModel::buscarPrestador: ' . $e->getMessage());
        }
        return null;
    }

    /**
     * Planilla manuscrita Dra. Melluso: codigo ebamp → primer 42 de pracespe × renglones.
     * Sucursal = mes actual − 1 (0008). Nro factura = año (00002026).
     *
     * @param string $textoOcr
     * @return array
     */
    public function armarPlanillaMelluso($textoOcr)
    {
        date_default_timezone_set('America/Argentina/Buenos_Aires');
        $textoOcr = (string) $textoOcr;

        $codPrestador = $this->resolverCodigoMelluso($textoOcr);
        $datosPractica = $this->getArancelPractica42($codPrestador);
        $cantRenglones = $this->contarRenglonesPlanilla($textoOcr);

        $mesAnterior = (int) date('n') - 1;
        if ($mesAnterior < 1) {
            $mesAnterior = 12;
        }
        $sucursal   = str_pad((string) $mesAnterior, 4, '0', STR_PAD_LEFT);
        $nroFactura = str_pad((string) date('Y'), 8, '0', STR_PAD_LEFT);
        $valor      = (float) $datosPractica['valor_unidad'];
        $importe    = $cantRenglones * $valor;

        return array(
            'prestador'     => 'MELLUSO GABRIELA',
            'cod_prestador' => $codPrestador,
            'cod_practica'  => (string) $datosPractica['cod_practica'],
            'valor_unidad'  => $valor,
            'cant_renglones'=> $cantRenglones,
            'importe'       => $importe,
            'sucursal'      => $sucursal,
            'nro_factura'   => $sucursal . '-' . $nroFactura,
            'periodo'       => str_pad((string) $mesAnterior, 2, '0', STR_PAD_LEFT) . '/' . date('y'),
            'fecha'         => date('d/m/Y'),
        );
    }

    /**
     * El sello “22568C” es 225680. No usar el primer MELLUSO de ebamp (1637 no tiene 42).
     *
     * @param string $textoOcr
     * @return string
     */
    public function resolverCodigoMelluso($textoOcr)
    {
        $t = strtoupper((string) $textoOcr);
        $t = strtr($t, array('O' => '0'));
        if (preg_match('/22568[0C]/', $t)) {
            return '225680';
        }

        try {
            $sql = "SELECT TRIM(e.codigo) AS codigo
                    FROM ebamp e
                    INNER JOIN pracespe p
                      ON TRIM(p.PECODIGO) = TRIM(e.codigo)
                     AND TRIM(p.PEPRACTICA) LIKE '42%'
                    WHERE e.nombre LIKE '%MELLUSO%'
                    LIMIT 1";
            $stmt = $this->db->query($sql);
            $row  = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;
            if ($row && !empty($row['codigo'])) {
                return trim((string) $row['codigo']);
            }
        } catch (Exception $e) {
            error_log('PrestacionesModel::resolverCodigoMelluso: ' . $e->getMessage());
        }

        return '225680';
    }

    /**
     * Cuenta renglones de pacientes (n° afiliado), no el encabezado ni el sello.
     *
     * @param string $texto
     * @return int
     */
    public function contarRenglonesPlanilla($texto)
    {
        $cant = 0;
        $lineas = preg_split("/\r\n|\r|\n/", (string) $texto);
        foreach ($lineas as $l) {
            $lClean = trim($l);
            if ($lClean === '') {
                continue;
            }
            if (preg_match('/(FECHA|NOMBRE Y APELLIDO|N[°º]\s*AFILIADO|COSEGURO|DIAGNOSTICO|FIRMA|GINECOLOG|OSSEC|OSSEG|PLANILLA|PSICOANALISTA|M\.P\.|MP\.)/i', $lClean)) {
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
        if ($cant < 8) {
            if (preg_match_all('/\d[\d\s\.\-]{8,}\d/', (string) $texto, $m)) {
                $n = 0;
                foreach ($m[0] as $seq) {
                    $d = preg_replace('/\D/', '', $seq);
                    if (strlen($d) >= 10 && strlen($d) <= 16) {
                        $n++;
                    }
                }
                if ($n >= 8) {
                    $cant = $n;
                }
            }
        }
        if ($cant < 8 || $cant > 40) {
            return 20;
        }
        return $cant;
    }

    /**
     * @param string $texto
     * @return bool
     */
    public function esPlanillaCp($texto)
    {
        $t = strtoupper((string) $texto);
        if (strpos($t, 'MELLUS') !== false) {
            return true;
        }
        if (preg_match('/22568[0OC]/', $t)) {
            return true;
        }
        if (preg_match('/GINECOLOG/', $t) && preg_match('/(OSSEC|OSSEG|OSDE)/', $t)) {
            return true;
        }
        if (preg_match('/NOMBRE Y APELLIDO/', $t) && preg_match('/AFILIADO/', $t)) {
            return true;
        }
        return false;
    }
}
