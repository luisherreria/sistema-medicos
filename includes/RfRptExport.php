<?php
/**
 * Exportación servidor de listados REGISTRF (PDF inline / XLSX attachment).
 */

class RfRptExport
{
    public static function enviarXlsx(string $prefijo, array $filas): void
    {
        $nombre = 'reporte_' . date('Ymd_His') . '.xlsx';
        $bin = self::xlsxDesdeFilas($filas);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $nombre . '"');
        header('Cache-Control: max-age=0');
        header('Pragma: public');
        header('Content-Length: ' . strlen($bin));
        echo $bin;
        exit;
    }

    public static function enviarPdf(string $titulo, array $filas, string $orient = 'landscape'): void
    {
        $nombre = 'reporte_' . date('Ymd_His') . '.pdf';
        $bin = self::pdfDesdeFilas($titulo, $filas, $orient);
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . $nombre . '"');
        header('Cache-Control: max-age=0');
        header('Content-Length: ' . strlen($bin));
        echo $bin;
        exit;
    }

    /**
     * @param array<string,mixed> $filtros
     * @param array<string,array<string,mixed>> $grupos
     */
    public static function detallado(string $titulo, array $filtros, array $grupos, string $format): void
    {
        $filas = [];
        $filas[] = ['SERVICIOS MEDICOS'];
        $filas[] = [$titulo];
        $filas[] = [self::metaLinea($filtros)];
        $filas[] = [];
        $cols = ['Fecha', 'Factura', 'Período', 'Obra Social', 'Prestador', 'Rec. Fact.',
            'Cantidad', 'Importe', 'I.V.A.', 'Coseguro', 'Total', 'Consultas', 'Importe', 'Tipo'];
        foreach ($grupos as $g) {
            $filas[] = ['SUBGRUPO: ' . (string) ($g['nombre'] ?? '')];
            $filas[] = $cols;
            foreach ($g['filas'] as $r) {
                $suc = trim((string) ($r['cosucfac'] ?? ''));
                $nro = trim((string) ($r['conrofac'] ?? ''));
                $fac = ($suc !== '' && $nro !== '') ? ($suc . '-' . $nro) : ($nro ?: $suc);
                $os  = trim((string) ($r['coobrasoc'] ?? ''));
                if (!empty($r['os_nombre'])) {
                    $os .= ' ' . $r['os_nombre'];
                }
                $filas[] = [
                    self::fecha($r['cofecfac'] ?? ''),
                    $fac,
                    self::periodo($r['coperiodo'] ?? ''),
                    $os,
                    (string) ($r['conomprest'] ?? ''),
                    (string) ($r['cofactura'] ?? ''),
                    self::entero($r['cocantcalc'] ?? 0),
                    self::num($r['coimpfac'] ?? 0),
                    self::num($r['coivafac'] ?? 0),
                    self::num($r['cocsgfac'] ?? 0),
                    self::num($r['cototalfac'] ?? 0),
                    self::entero($r['cocantcalc'] ?? 0),
                    self::num($r['copesos'] ?? 0),
                    (string) ($r['cotipopre'] ?? ''),
                ];
            }
            $filas[] = [
                'TOTALES ' . (string) ($g['nombre'] ?? ''), '', '', '', '', '',
                self::entero($g['cantidad'] ?? 0),
                self::num($g['importe'] ?? 0),
                self::num($g['iva'] ?? 0),
                self::num($g['coseguro'] ?? 0),
                self::num($g['total'] ?? 0),
                self::entero($g['consultas'] ?? 0),
                self::num($g['pesos'] ?? 0),
                '',
            ];
            $filas[] = [];
        }
        if ($format === 'pdf') {
            self::enviarPdf($titulo, $filas, 'landscape');
        }
        self::enviarXlsx('detallado', $filas);
    }

    /**
     * @param array<string,mixed> $filtros
     * @param array<int,array<string,mixed>> $obras
     * @param array<string,mixed> $total
     */
    public static function totales(string $titulo, array $filtros, array $obras, array $total, string $format): void
    {
        $filas = [];
        $filas[] = ['SERVICIOS MEDICOS'];
        $filas[] = [$titulo];
        $filas[] = [self::metaLinea($filtros)];
        $filas[] = [];
        $cols = ['Subcategoría', 'Cantidad', 'Total', 'Consultas', 'Importe'];
        foreach ($obras as $os) {
            $titOs = 'OBRA SOCIAL : ' . (string) ($os['codigo'] ?? '');
            if (!empty($os['nombre'])) {
                $titOs .= '  ' . $os['nombre'];
            }
            $filas[] = [$titOs];
            $filas[] = $cols;
            foreach ($os['subs'] as $sub) {
                $filas[] = [
                    (string) ($sub['subcateg'] ?? ''),
                    self::entero($sub['cantidad'] ?? 0),
                    self::num($sub['total'] ?? 0),
                    self::entero($sub['consultas'] ?? 0),
                    self::num($sub['importe'] ?? 0),
                ];
            }
            $filas[] = [
                'TOTALES ' . (string) ($os['codigo'] ?? '') . '  Cons. Vestida: ' . self::num($os['cons_vestida'] ?? 0),
                self::entero($os['cantidad'] ?? 0),
                self::num($os['total'] ?? 0),
                self::entero($os['consultas'] ?? 0),
                self::num($os['importe'] ?? 0),
            ];
            $filas[] = [];
        }
        $filas[] = [
            'TOTAL GENERAL  Cons. Vestida: ' . self::num($total['cons_vestida'] ?? 0),
            self::entero($total['cantidad'] ?? 0),
            self::num($total['total'] ?? 0),
            self::entero($total['consultas'] ?? 0),
            self::num($total['importe'] ?? 0),
        ];
        if ($format === 'pdf') {
            self::enviarPdf($titulo, $filas, 'portrait');
        }
        self::enviarXlsx('totales', $filas);
    }

    /**
     * @param array<string,mixed> $filtros
     */
    private static function metaLinea(array $filtros): string
    {
        return 'Fecha: ' . date('d/m/Y H:i')
            . ' | Período: ' . (string) ($filtros['desde_fmt'] ?? '') . ' a ' . (string) ($filtros['hasta_fmt'] ?? '')
            . ' | Obra Social: ' . (string) ($filtros['os_lbl'] ?? 'Todas')
            . ' | Prestador: ' . (string) ($filtros['prest_lbl'] ?? 'Todos')
            . ' | Tipo: ' . (string) ($filtros['tipo_lbl'] ?? 'Todas');
    }

    private static function fecha($s): string
    {
        $s = (string) $s;
        if ($s === '' || strpos($s, '0000-00-00') === 0) {
            return '';
        }
        $p = explode('-', substr($s, 0, 10));
        return count($p) === 3 ? ($p[2] . '/' . $p[1] . '/' . $p[0]) : $s;
    }

    private static function periodo($p): string
    {
        $p = trim((string) $p);
        if (preg_match('/^(\d{2})\/(\d{2})$/', $p)) {
            return $p;
        }
        if (strlen($p) === 4 && ctype_digit($p)) {
            return substr($p, 0, 2) . '/' . substr($p, 2, 2);
        }
        return $p;
    }

    private static function num($n): string
    {
        return number_format((float) $n, 2, ',', '.');
    }

    private static function entero($n): string
    {
        return number_format((float) $n, 0, ',', '.');
    }

    /**
     * @param array<int,array<int,string>> $filas
     */
    private static function xlsxDesdeFilas(array $filas): string
    {
        $sheet = self::sheetXml($filas);
        $files = [
            '[Content_Types].xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
                . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
                . '<Default Extension="xml" ContentType="application/xml"/>'
                . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
                . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
                . '</Types>',
            '_rels/.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
                . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
                . '</Relationships>',
            'xl/workbook.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"'
                . ' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
                . '<sheets><sheet name="Listado" sheetId="1" r:id="rId1"/></sheets></workbook>',
            'xl/_rels/workbook.xml.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
                . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
                . '</Relationships>',
            'xl/worksheets/sheet1.xml' => $sheet,
        ];
        if (!class_exists('ZipArchive')) {
            throw new RuntimeException('ZipArchive no está disponible para generar Excel.');
        }
        $tmp = tempnam(sys_get_temp_dir(), 'rfx');
        $zip = new ZipArchive();
        if ($zip->open($tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('No se pudo crear el archivo Excel.');
        }
        foreach ($files as $path => $xml) {
            $zip->addFromString($path, $xml);
        }
        $zip->close();
        $bin = (string) file_get_contents($tmp);
        @unlink($tmp);
        return $bin;
    }

    /**
     * @param array<int,array<int,string>> $filas
     */
    private static function sheetXml(array $filas): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>';
        $r = 1;
        foreach ($filas as $fila) {
            $xml .= '<row r="' . $r . '">';
            $c = 0;
            foreach ($fila as $val) {
                $ref = self::colLetter($c) . $r;
                $xml .= '<c r="' . $ref . '" t="inlineStr"><is><t xml:space="preserve">'
                    . self::xml($val) . '</t></is></c>';
                $c++;
            }
            $xml .= '</row>';
            $r++;
        }
        $xml .= '</sheetData></worksheet>';
        return $xml;
    }

    private static function colLetter(int $i): string
    {
        $s = '';
        $n = $i + 1;
        while ($n > 0) {
            $m = ($n - 1) % 26;
            $s = chr(65 + $m) . $s;
            $n = intdiv($n - 1, 26);
        }
        return $s;
    }

    private static function xml($s): string
    {
        $s = (string) $s;
        $s = str_replace(["\r", "\n", "\t"], ' ', $s);
        return htmlspecialchars($s, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    /**
     * @param array<int,array<int,string>> $filas
     */
    private static function pdfDesdeFilas(string $titulo, array $filas, string $orient): string
    {
        $land = ($orient !== 'portrait');
        $pw = $land ? 842 : 595;
        $ph = $land ? 595 : 842;
        $ml = 28;
        $mr = 28;
        $mt = 36;
        $fs = $land ? 7 : 8;
        $lh = $fs + 4;
        $usable = $pw - $ml - $mr;

        $pageStreams = [];
        $y = $ph - $mt;
        $stream = '';

        $flush = static function () use (&$pageStreams, &$stream) {
            $pageStreams[] = $stream;
            $stream = '';
        };

        $esc = static function (string $s): string {
            $s = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $s);
            if ($s === false) {
                $s = utf8_decode($s);
            }
            return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $s);
        };

        $newPage = static function () use (&$y, &$stream, $flush, $ph, $mt) {
            if ($stream !== '') {
                $flush();
            }
            $y = $ph - $mt;
        };

        $write = static function (float $x, float $yy, string $txt, int $size) use (&$stream, $esc) {
            $stream .= "BT /F1 {$size} Tf " . sprintf('%.1f', $x) . ' ' . sprintf('%.1f', $yy)
                . ' Td (' . $esc($txt) . ") Tj ET\n";
        };

        $write($ml, $y, 'SERVICIOS MEDICOS — ' . $titulo, 11);
        $y -= 16;
        $write($ml, $y, date('d/m/Y H:i'), 8);
        $y -= ($lh + 4);

        foreach ($filas as $fila) {
            if ($y < 36) {
                $newPage();
            }
            $n = count($fila);
            if ($n <= 1) {
                $txt = isset($fila[0]) ? (string) $fila[0] : '';
                if ($txt !== '') {
                    $write($ml, $y, $txt, $fs + 1);
                }
                $y -= $lh;
                continue;
            }
            $cw = $usable / max($n, 1);
            $x = $ml;
            foreach ($fila as $val) {
                $t = (string) $val;
                $maxc = max(4, (int) floor($cw / ($fs * 0.5)));
                if (strlen($t) > $maxc) {
                    $t = substr($t, 0, $maxc - 1) . '.';
                }
                $write($x, $y, $t, $fs);
                $x += $cw;
            }
            $y -= $lh;
        }
        if ($stream !== '' || !$pageStreams) {
            $flush();
        }

        return self::pdfEmpaquetar($pageStreams, $pw, $ph);
    }

    /**
     * @param array<int,string> $pageStreams
     */
    private static function pdfEmpaquetar(array $pageStreams, int $pw, int $ph): string
    {
        $objs = [];
        $objs[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $pageIds = [];
        $oid = 3;
        $fontId = $oid++;
        $objs[$fontId] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';

        $contentIds = [];
        foreach ($pageStreams as $st) {
            $cid = $oid++;
            $contentIds[] = $cid;
            $objs[$cid] = '<< /Length ' . strlen($st) . " >>\nstream\n" . $st . "endstream";
            $pid = $oid++;
            $pageIds[] = $pid;
            $objs[$pid] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 ' . $pw . ' ' . $ph . '] '
                . '/Resources << /Font << /F1 ' . $fontId . ' 0 R >> >> /Contents ' . $cid . ' 0 R >>';
        }
        $kids = '';
        foreach ($pageIds as $pid) {
            $kids .= $pid . ' 0 R ';
        }
        $objs[2] = '<< /Type /Pages /Kids [' . trim($kids) . '] /Count ' . count($pageIds) . ' >>';

        ksort($objs);
        $pdf = "%PDF-1.4\n";
        $offs = [0];
        foreach ($objs as $id => $body) {
            $offs[$id] = strlen($pdf);
            $pdf .= $id . " 0 obj\n" . $body . "\nendobj\n";
        }
        $xref = strlen($pdf);
        $maxId = max(array_keys($objs));
        $pdf .= "xref\n0 " . ($maxId + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i <= $maxId; $i++) {
            $pos = isset($offs[$i]) ? $offs[$i] : 0;
            $pdf .= sprintf("%010d 00000 n \n", $pos);
        }
        $pdf .= "trailer << /Size " . ($maxId + 1) . " /Root 1 0 R >>\nstartxref\n" . $xref . "\n%%EOF";
        return $pdf;
    }
}
