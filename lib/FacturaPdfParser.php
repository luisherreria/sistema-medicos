<?php
/**
 * FacturaPdfParser — Extrae campos de una factura argentina (AFIP / PDF)
 * y normaliza el período al formato AA/MM. Compatible con PHP 5.6.
 */
class FacturaPdfParser
{
    /**
     * Meses en español (nombre completo y abreviatura) → MM.
     * @var array
     */
    private static $MESES = array(
        'ENERO'      => '01',
        'ENE'        => '01',
        'FEBRERO'    => '02',
        'FEB'        => '02',
        'MARZO'      => '03',
        'MAR'        => '03',
        'ABRIL'      => '04',
        'ABR'        => '04',
        'MAYO'       => '05',
        'MAY'        => '05',
        'JUNIO'      => '06',
        'JUN'        => '06',
        'JULIO'      => '07',
        'JUL'        => '07',
        'AGOSTO'     => '08',
        'AGO'        => '08',
        'SEPTIEMBRE' => '09',
        'SETIEMBRE'  => '09',
        'SEP'        => '09',
        'SET'        => '09',
        'OCTUBRE'    => '10',
        'OCT'        => '10',
        'NOVIEMBRE'  => '11',
        'NOV'        => '11',
        'DICIEMBRE'  => '12',
        'DIC'        => '12',
    );

    /**
     * @param string $texto Texto plano extraído del PDF
     * @return array
     */
    public function parsear($texto)
    {
        $texto = isset($texto) ? $texto : '';
        $norm  = $this->normalizarTexto($texto);

        $fecha   = $this->extraerFecha($norm);
        $sucNro  = $this->extraerSucursalYNumero($norm);
        $importe = $this->extraerImporte($norm);
        $cuit    = $this->extraerCuit($norm);

        return array(
            'razon_social'    => $this->extraerRazonSocial($norm),
            'fecha'           => $fecha,
            'sucursal'        => $sucNro['sucursal'],
            'nro_comprobante' => $sucNro['numero'],
            'importe'         => $importe,
            'periodo'         => $this->normalizarPeriodo($norm),
            'cuit'            => $cuit,
            'obra_social'     => $this->extraerObraSocial($norm),
            'encabezado'      => $this->textoCabecera($norm),
        );
    }

    /**
     * Regla estricta: traduce variaciones al formato AA/MM.
     * Ej: "AGOSTO/2026" → "26/08", "AGO/26" → "26/08".
     * Si no se detecta un período explícito, devuelve cadena vacía.
     *
     * @param string $texto
     * @return string
     */
    public function normalizarPeriodo($texto)
    {
        $t = $this->normalizarTexto(isset($texto) ? $texto : '');
        if ($t === '') {
            return '';
        }

        $nombres = array_keys(self::$MESES);
        usort($nombres, array($this, 'cmpLenDesc'));
        $alt = implode('|', $nombres);

        // 1) "AGOSTO/2026"  "AGO/26"  "AGOSTO 2026"  "PERIODO AGOSTO 26"
        if (preg_match('/\b(' . $alt . ')\b\s*[\/\-\.\s]+\s*(20)?(\d{2})\b/u', $t, $m)) {
            return $m[3] . '/' . self::$MESES[$m[1]];
        }

        // 2) "2026/AGOSTO"  "26-AGO"
        if (preg_match('/\b(20)?(\d{2})\s*[\/\-\.]\s*(' . $alt . ')\b/u', $t, $m)) {
            return $m[2] . '/' . self::$MESES[$m[3]];
        }

        // 3) "PERIODO: 08/2026" / "PERIODO FACTURADO ... 01/08/2026"
        if (preg_match('/PERIODO(?:\s+FACTURADO)?[^0-9]{0,48}(\d{1,2})[\/\-\.](\d{1,2})[\/\-\.](\d{2,4})/u', $t, $m)) {
            $aa = strlen($m[3]) === 4 ? substr($m[3], 2, 2) : str_pad($m[3], 2, '0', STR_PAD_LEFT);
            $mm = str_pad($m[2], 2, '0', STR_PAD_LEFT);
            if ($this->mesValido($mm)) {
                return $aa . '/' . $mm;
            }
        }

        // 4) "PERIODO 08/2026" o "PERIODO 08-26"
        if (preg_match('/PERIODO[^0-9]{0,24}(0?[1-9]|1[0-2])[\/\-](20)?(\d{2})\b/u', $t, $m)) {
            return $m[3] . '/' . str_pad($m[1], 2, '0', STR_PAD_LEFT);
        }

        // 5) "08/2026" o "08-2026" cerca de la palabra PERIODO (ventana)
        if (preg_match('/PERIODO.{0,40}?\b(0?[1-9]|1[0-2])[\/\-](20)(\d{2})\b/u', $t, $m)) {
            return $m[3] . '/' . str_pad($m[1], 2, '0', STR_PAD_LEFT);
        }

        // 6) Ya viene como AA/MM explícito junto a PERIODO
        if (preg_match('/PERIODO[^0-9]{0,16}(\d{2})\/(0[1-9]|1[0-2])\b/u', $t, $m)) {
            return $m[1] . '/' . $m[2];
        }

        return '';
    }

    /**
     * Convierte "26/08" → "2608" (almacenamiento VFP / registrf).
     *
     * @param string $periodoAaMm
     * @return string
     */
    public function periodoAamm($periodoAaMm)
    {
        $p = strtoupper(str_replace(array(' ', '-'), '', isset($periodoAaMm) ? $periodoAaMm : ''));
        $p = str_replace('/', '', $p);
        if (strlen($p) === 4 && ctype_digit($p)) {
            return $p;
        }
        return '';
    }

    /**
     * Razón social del emisor: solo encabezado AFIP, nunca el detalle/concepto.
     *
     * @param string $t
     * @return string
     */
    private function extraerRazonSocial($t)
    {
        $cab = $this->textoCabecera($t);
        $patrones = array(
            '/RAZON\s+SOCIAL\s*:?\s*([A-Z0-9ÑÁÉÍÓÚÜ .,&\'\-]{3,80})/u',
            '/EMISOR\s*:?\s*([A-Z0-9ÑÁÉÍÓÚÜ .,&\'\-]{3,80})/u',
            '/(LABORATORIO\s+[A-Z0-9ÑÁÉÍÓÚÜ .,&\'\-]{3,70})/u',
            '/\b([A-Z0-9ÑÁÉÍÓÚÜ .,&\'\-]{3,60}\s+S\.?A\.?\s*S\.?)\b/u',
            '/\b([A-Z0-9ÑÁÉÍÓÚÜ .,&\'\-]{3,60}\s+S\.?R\.?L\.?)\b/u',
            '/\b([A-Z0-9ÑÁÉÍÓÚÜ .,&\'\-]{3,60}\s+S\.?A\.?)\b/u',
        );
        foreach ($patrones as $re) {
            if (preg_match_all($re, $cab, $todos)) {
                foreach ($todos[1] as $cand) {
                    $nom = $this->limpiarNombre($cand);
                    if ($nom !== '' && !$this->esBasuraRazonSocial($nom)) {
                        return $nom;
                    }
                }
            }
        }

        $lineas = preg_split('/\r\n|\r|\n/', $cab);
        $n = count($lineas);
        for ($i = 0; $i < $n; $i++) {
            $ln = trim($lineas[$i]);
            if ($ln === '' || strlen($ln) < 4) {
                continue;
            }
            if ($this->esBasuraRazonSocial($ln)) {
                continue;
            }
            if (preg_match('/^(FACTURA|ORIGINAL|DUPLICADO|CODIGO|COD|CUIT|C\.U\.I\.T|N[°ºO]|FECHA|IVA|PUNTO|PAGINA|HOJA)\b/u', $ln)) {
                continue;
            }
            return $this->limpiarNombre($ln);
        }

        return '';
    }

    /**
     * Recorta el comprobante al encabezado (antes de Descripción / Concepto / ítems).
     *
     * @param string $t
     * @return string
     */
    private function textoCabecera($t)
    {
        $t = isset($t) ? $t : '';
        if ($t === '') {
            return '';
        }

        $corteRe = '/\b(DESCRIPCION|CONCEPTO|DETALLE(?:\s+DE)?|CODIGO\s+(?:DE\s+)?(?:ARTICULO|PRODUCTO|ITEM)|PRECIO\s+UNIT|IMPORTE\s+UNIT|PRODUCTO\s*\/\s*SERVICIO|UNIDAD\s+DE\s+MEDIDA)\b/u';
        if (preg_match($corteRe, $t, $m, PREG_OFFSET_CAPTURE)) {
            $pos = (int) $m[0][1];
            if ($pos > 40) {
                $t = substr($t, 0, $pos);
            }
        }

        $lineas = preg_split('/\r\n|\r|\n/', $t);
        if (!is_array($lineas) || count($lineas) < 3) {
            return trim(substr($t, 0, 900));
        }

        $max = min(22, count($lineas));
        $out = array();
        for ($i = 0; $i < $max; $i++) {
            $ln = trim($lineas[$i]);
            if ($i >= 6 && $this->esLineaDetalleOConcepto($ln)) {
                break;
            }
            $out[] = $ln;
        }
        return trim(implode("\n", $out));
    }

    /**
     * @param string $ln
     * @return bool
     */
    private function esLineaDetalleOConcepto($ln)
    {
        $ln = trim(isset($ln) ? $ln : '');
        if ($ln === '') {
            return false;
        }
        if (preg_match('/\b(DESCRIPCION|CONCEPTO|DETALLE|CANTIDAD|PRECIO UNIT|IMPORTE UNIT)\b/u', $ln)) {
            return true;
        }
        if (preg_match('/\bPRESTACIONES\s+AMBULATORIAS\b/u', $ln)) {
            return true;
        }
        if (preg_match('/^\d+([.,]\d+)?\s+[A-Z].{8,}/u', $ln)) {
            return true;
        }
        return false;
    }

    /**
     * @param string $ln
     * @return bool
     */
    private function esBasuraRazonSocial($ln)
    {
        $ln = trim(isset($ln) ? $ln : '');
        if ($ln === '') {
            return true;
        }
        if (stripos($ln, 'COMEDICA') !== false) {
            return true;
        }
        if (preg_match('/\b(APELLIDO\s+Y\s+NOMBRE|SENOR(?:ES)?|SR\(ES\)|DESTINATARIO|COMPRADOR|CLIENTE)\b/u', $ln)) {
            return true;
        }
        if ($this->esLineaDetalleOConcepto($ln)) {
            return true;
        }
        if (preg_match('/\bPRESTACIONES\s+AMBULATORIAS\b/u', $ln)) {
            return true;
        }
        if (preg_match('/\b(HONORARIOS MEDICOS|CONSULTA MEDICA|INTERNACION)\b/u', $ln)
            && !preg_match('/\b(S\.?A\.?S?|S\.?R\.?L|CLINICA|SANATORIO|HOSPITAL|LABORATORIO)\b/u', $ln)
        ) {
            return true;
        }
        if (preg_match('/\([A-Z0-9]{1,5}\)\s*$/u', $ln) && preg_match('/\b(PRESTACION|PRESTACIONES|CONSULTA|PRACTICA)\b/u', $ln)) {
            return true;
        }
        return false;
    }

    private function extraerFecha($t)
    {
        $patrones = array(
            '/FECHA\s+DE\s+EMISION\s*:?\s*(\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4})/u',
            '/FECHA\s+EMISION\s*:?\s*(\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4})/u',
            '/FECHA\s*:?\s*(\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4})/u',
        );
        foreach ($patrones as $re) {
            if (preg_match($re, $t, $m)) {
                return $this->fechaIso($m[1]);
            }
        }
        if (preg_match('/\b(\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4})\b/', $t, $m)) {
            return $this->fechaIso($m[1]);
        }
        return null;
    }

    private function extraerSucursalYNumero($t)
    {
        $suc = '';
        $nro = '';

        if (preg_match('/PUNTO\s+DE\s+VENTA\s*:?\s*(\d{1,5})/u', $t, $m)) {
            $suc = str_pad($m[1], 4, '0', STR_PAD_LEFT);
        } elseif (preg_match('/SUCURSAL\s*:?\s*(\d{1,5})/u', $t, $m)) {
            $suc = str_pad($m[1], 4, '0', STR_PAD_LEFT);
        } elseif (preg_match('/\bP\.?\s*V\.?\s*:?\s*(\d{1,5})\b/u', $t, $m)) {
            $suc = str_pad($m[1], 4, '0', STR_PAD_LEFT);
        }

        if (preg_match('/COMP\.?\s*NRO\.?\s*:?\s*(\d{1,12})/u', $t, $m)) {
            $nro = str_pad($m[1], 8, '0', STR_PAD_LEFT);
        } elseif (preg_match('/N(UMERO|RO)\s+(DE\s+)?(COMPROBANTE|FACTURA)\s*:?\s*(\d{1,12})/u', $t, $m)) {
            $nro = str_pad($m[4], 8, '0', STR_PAD_LEFT);
        } elseif (preg_match('/N[°ºO]\s*(\d{4,6})\s*[\-–]\s*(\d{6,9})/u', $t, $m)) {
            $suc = str_pad($m[1], 4, '0', STR_PAD_LEFT);
            $nro = str_pad($m[2], 8, '0', STR_PAD_LEFT);
        }

        // 00006-00009423  /  0001-00001234
        if (($suc === '' || $nro === '') && preg_match('/\b(\d{4,6})\s*[\-–]\s*(\d{6,9})\b/', $t, $m)) {
            if ($suc === '') {
                $suc = str_pad($m[1], 4, '0', STR_PAD_LEFT);
            }
            if ($nro === '') {
                $nro = str_pad($m[2], 8, '0', STR_PAD_LEFT);
            }
        }

        return array('sucursal' => $suc, 'numero' => $nro);
    }

    private function extraerImporte($t)
    {
        $patrones = array(
            '/IMPORTE\s+TOTAL\s*:?\s*\$?\s*([\d\.\,]+)/u',
            '/TOTAL\s+FACTURA\s*:?\s*\$?\s*([\d\.\,]+)/u',
            '/TOTAL\s*:?\s*\$\s*([\d\.\,]+)/u',
            '/TOTALTAL\s*:?\s*\$?\s*([\d\.\,]+)/u',
            '/MONTO\s+TOTAL\s*:?\s*\$?\s*([\d\.\,]+)/u',
            '/IMPORTE\s*:?\s*\$?\s*([\d]{1,3}(?:\.\d{3})+,\d{2})/u',
            '/\$\s*([\d]{1,3}(?:\.\d{3})+,\d{2})/u',
        );
        foreach ($patrones as $re) {
            if (preg_match($re, $t, $m)) {
                $val = $this->parseImporte($m[1]);
                if ($val > 0) {
                    return $val;
                }
            }
        }
        return 0.0;
    }

    private function extraerCuit($t)
    {
        $encontrados = $this->listarCuits($this->textoCabecera($t));
        if (!$encontrados) {
            $encontrados = $this->listarCuits($t);
        }
        foreach ($encontrados as $c) {
            if (empty($c['receptor'])) {
                return $c['cuit'];
            }
        }
        return isset($encontrados[0]['cuit']) ? $encontrados[0]['cuit'] : '';
    }

    /**
     * @param string $t
     * @return array
     */
    private function listarCuits($t)
    {
        $out = array();
        $t = isset($t) ? $t : '';
        if ($t === '') {
            return $out;
        }
        $lineas = preg_split('/\r\n|\r|\n/', $t);
        if (!is_array($lineas) || count($lineas) < 2) {
            $lineas = array($t);
        }
        $n = count($lineas);
        for ($i = 0; $i < $n; $i++) {
            $ln = $lineas[$i];
            $ctx = $ln;
            if ($i > 0) {
                $ctx = $lineas[$i - 1] . ' ' . $ctx;
            }
            if ($i + 1 < $n) {
                $ctx .= ' ' . $lineas[$i + 1];
            }
            $receptor = (stripos($ctx, 'COMEDICA') !== false
                || preg_match('/\b(APELLIDO\s+Y\s+NOMBRE|DESTINATARIO|COMPRADOR|CLIENTE|SR\(ES\))\b/u', $ctx));

            if (preg_match_all('/(?:CUIT|C\.U\.I\.T\.?)\s*:?\s*(\d{2}[\-\.\s]?\d{8}[\-\.\s]?\d{1})/u', $ln, $m)) {
                foreach ($m[1] as $raw) {
                    $dig = preg_replace('/\D/', '', $raw);
                    if (strlen($dig) === 11) {
                        $out[] = array('cuit' => $dig, 'receptor' => $receptor);
                    }
                }
            }
            if (preg_match_all('/\b(\d{2}[\-\.]\d{8}[\-\.]\d{1})\b/', $ln, $m2)) {
                foreach ($m2[1] as $raw) {
                    $dig = preg_replace('/\D/', '', $raw);
                    if (strlen($dig) === 11) {
                        $out[] = array('cuit' => $dig, 'receptor' => $receptor);
                    }
                }
            }
        }
        return $out;
    }

    private function extraerObraSocial($t)
    {
        $patrones = array(
            '/OBRA\s+SOCIAL\s*:?\s*([A-Z0-9ÑÁÉÍÓÚÜ .,&\'\-]{2,60})/u',
            '/O\.?\s*SOCIAL\s*:?\s*([A-Z0-9ÑÁÉÍÓÚÜ .,&\'\-]{2,60})/u',
            '/COBERTURA\s*:?\s*([A-Z0-9ÑÁÉÍÓÚÜ .,&\'\-]{2,60})/u',
        );
        foreach ($patrones as $re) {
            if (preg_match($re, $t, $m)) {
                return $this->limpiarNombre($m[1]);
            }
        }
        return '';
    }

    public function parseImporte($s)
    {
        $s = trim(isset($s) ? $s : '');
        $s = str_replace(array('$', ' ', 'ARS'), '', $s);
        $s = preg_replace('/[^\d,\.]/', '', $s);
        if ($s === '') {
            return 0.0;
        }
        // 16.907,49 → 16907.49
        $s = str_replace('.', '', $s);
        $s = str_replace(',', '.', $s);
        return (float) $s;
    }

    public function fechaIso($s)
    {
        $s = trim(isset($s) ? $s : '');
        if (!preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{2,4})$/', $s, $m)) {
            return null;
        }
        $aa = $m[3];
        if (strlen($aa) === 2) {
            $aa = '20' . $aa;
        }
        $mm = str_pad($m[2], 2, '0', STR_PAD_LEFT);
        $dd = str_pad($m[1], 2, '0', STR_PAD_LEFT);
        if (!$this->mesValido($mm) || (int) $dd < 1 || (int) $dd > 31) {
            return null;
        }
        return $aa . '-' . $mm . '-' . $dd;
    }

    private function normalizarTexto($texto)
    {
        $t = strtoupper($this->sinAcentos(isset($texto) ? $texto : ''));
        $t = preg_replace('/[ \t]+/', ' ', $t);
        return trim($t);
    }

    private function sinAcentos($s)
    {
        $from = array('Á', 'É', 'Í', 'Ó', 'Ú', 'Ü', 'Ñ', 'á', 'é', 'í', 'ó', 'ú', 'ü', 'ñ');
        $to   = array('A', 'E', 'I', 'O', 'U', 'U', 'N', 'A', 'E', 'I', 'O', 'U', 'U', 'N');
        return str_replace($from, $to, $s);
    }

    private function limpiarNombre($s)
    {
        $s = trim($s);
        $s = preg_replace('/\s+/', ' ', $s);
        $cortes = array(' CUIT', ' DOMICILIO', ' CONDICION', ' IVA', ' INGRESOS', ' PUNTO', ' FECHA');
        foreach ($cortes as $c) {
            $p = strpos($s, $c);
            if ($p !== false && $p > 3) {
                $s = trim(substr($s, 0, $p));
            }
        }
        return $s;
    }

    private function mesValido($mm)
    {
        $n = (int) $mm;
        return $n >= 1 && $n <= 12;
    }

    private function cmpLenDesc($a, $b)
    {
        return strlen($b) - strlen($a);
    }
}
