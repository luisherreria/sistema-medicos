<?php
/**
 * class.pdf2text.php
 * Extractor de texto de PDF — PHP 5.6 puro.
 *
 * Sin Composer, sin shell_exec, sin pdftotext.
 * Requiere zlib (gzuncompress) para streams FlateDecode; viene en XAMPP.
 *
 * Uso:
 *   require_once 'class.pdf2text.php';
 *   $pdf = new PDF2Text();
 *   $pdf->setFilename('/ruta/archivo.pdf');
 *   $pdf->decodePDF();
 *   $texto = $pdf->output();
 */
if (class_exists('PDF2Text')) {
    return;
}

class PDF2Text
{
    public $filename    = '';
    public $decodedtext = '';

    public function setFilename($filename)
    {
        $this->filename    = $filename;
        $this->decodedtext = '';
    }

    /**
     * Devuelve el texto extraído. Si $echo es true, lo imprime.
     */
    public function output($echo = false)
    {
        if ($echo) {
            echo $this->decodedtext;
            return '';
        }
        return $this->decodedtext;
    }

    /**
     * Lee el PDF, descomprime streams y junta el texto de Tj / TJ / ' / ".
     */
    public function decodePDF()
    {
        $this->decodedtext = '';

        if ($this->filename === '' || !is_file($this->filename) || !is_readable($this->filename)) {
            return '';
        }

        $infile = @file_get_contents($this->filename);
        if ($infile === false || $infile === '') {
            return '';
        }

        $chunks = array();

        // 1) Cada bloque stream ... endstream
        if (preg_match_all('#stream[\r\n]+(.*)endstream#ismU', $infile, $matches)) {
            $n = count($matches[1]);
            for ($i = 0; $i < $n; $i++) {
                $plain = $this->decompress($matches[1][$i]);
                if ($plain === '' || $this->esBinarioImagen($plain)) {
                    continue;
                }
                $text = $this->extractFromContent($plain);
                if ($text !== '' && $this->textoUtil($text)) {
                    $chunks[] = $text;
                }
            }
        }

        // 2) Fallback solo si hay texto real (no JPEG / escaneo)
        if (!$chunks) {
            $direct = $this->extractFromContent($infile);
            if ($direct !== '' && $this->textoUtil($direct)) {
                $chunks[] = $direct;
            }
        }

        $texto = implode("\n", $chunks);
        $texto = $this->toUtf8($texto);
        $texto = preg_replace("/[ \t]+/", ' ', $texto);
        $texto = preg_replace("/\n{3,}/", "\n\n", $texto);

        $this->decodedtext = trim($texto);
        return $this->decodedtext;
    }

    /**
     * Descomprime FlateDecode / zlib. Si no está comprimido, devuelve el crudo.
     */
    public function decompress($stream)
    {
        $stream = ltrim($stream, "\r\n");
        if ($stream === '') {
            return '';
        }

        $plain = @gzuncompress($stream);
        if ($plain !== false && $plain !== '') {
            return $plain;
        }

        $plain = @gzinflate($stream);
        if ($plain !== false && $plain !== '') {
            return $plain;
        }

        if (function_exists('gzdecode')) {
            $plain = @gzdecode($stream);
            if ($plain !== false && $plain !== '') {
                return $plain;
            }
        }

        // zlib con 2 bytes de cabecera extra
        if (strlen($stream) > 2) {
            $plain = @gzuncompress(substr($stream, 2));
            if ($plain !== false && $plain !== '') {
                return $plain;
            }
            $plain = @gzinflate(substr($stream, 2));
            if ($plain !== false && $plain !== '') {
                return $plain;
            }
        }

        return $stream;
    }

    /**
     * Interpreta operadores de texto PDF: TJ, Tj, ' y ".
     */
    public function extractFromContent($content)
    {
        $out = array();

        // [(H) -10 (ola)] TJ
        if (preg_match_all('/\[([^\[\]]*)\]\s*TJ/s', $content, $mTj)) {
            foreach ($mTj[1] as $body) {
                $pieza = $this->parseTjArray($body);
                if ($pieza !== '') {
                    $out[] = $pieza;
                }
            }
        }

        // (texto) Tj   |   (texto) '   |   (texto) "
        if (preg_match_all('/(?<!\\\\)\\(((?:\\\\.|[^\\\\)])*)\\)\\s*(Tj|\'|")/s', $content, $mLit)) {
            foreach ($mLit[1] as $str) {
                $out[] = $this->unescapeLiteral($str);
            }
        }

        // <0048006F> Tj  (hex / UTF-16)
        if (preg_match_all('/<([0-9A-Fa-f \t\r\n]+)>\s*Tj/s', $content, $mHex)) {
            foreach ($mHex[1] as $hex) {
                $out[] = $this->hexToStr($hex);
            }
        }

        return trim(implode(' ', $out));
    }

    public function parseTjArray($body)
    {
        $parts = array();

        if (preg_match_all('/\\(((?:\\\\.|[^\\\\)])*)\\)/s', $body, $m)) {
            foreach ($m[1] as $str) {
                $parts[] = $this->unescapeLiteral($str);
            }
        }
        if (preg_match_all('/<([0-9A-Fa-f \t\r\n]+)>/', $body, $m2)) {
            foreach ($m2[1] as $hex) {
                $parts[] = $this->hexToStr($hex);
            }
        }

        return implode('', $parts);
    }

    public function unescapeLiteral($str)
    {
        $str = str_replace(
            array('\\n', '\\r', '\\t', '\\b', '\\f', '\\(', '\\)', '\\\\'),
            array("\n", "\r", "\t", '', '', '(', ')', '\\'),
            $str
        );
        $str = preg_replace_callback('/\\\\([0-7]{1,3})/', array($this, 'octalCb'), $str);
        return $str;
    }

    public function octalCb($m)
    {
        return chr(octdec($m[1]));
    }

    public function hexToStr($hex)
    {
        $hex = preg_replace('/\s+/', '', $hex);
        if ($hex === '') {
            return '';
        }
        if ((strlen($hex) % 2) === 1) {
            $hex .= '0';
        }

        if (function_exists('hex2bin')) {
            $bin = @hex2bin($hex);
        } else {
            $bin = @pack('H*', $hex);
        }
        if ($bin === false || $bin === '') {
            return '';
        }

        // UTF-16BE típico de facturas AFIP: 00 4C 00 75 00 69 00 73
        if (substr($bin, 0, 2) === "\xFE\xFF") {
            $conv = @mb_convert_encoding(substr($bin, 2), 'UTF-8', 'UTF-16BE');
            return ($conv !== false) ? $conv : $bin;
        }
        if (substr($bin, 0, 2) === "\xFF\xFE") {
            $conv = @mb_convert_encoding(substr($bin, 2), 'UTF-8', 'UTF-16LE');
            return ($conv !== false) ? $conv : $bin;
        }
        if (preg_match('/^(\\x00[\\x20-\\x7E])+$/', $bin)) {
            $conv = @mb_convert_encoding($bin, 'UTF-8', 'UTF-16BE');
            return ($conv !== false) ? $conv : $bin;
        }

        return $bin;
    }

    public function toUtf8($text)
    {
        if ($text === '') {
            return '';
        }

        if (substr($text, 0, 2) === "\xFE\xFF") {
            $conv = @mb_convert_encoding(substr($text, 2), 'UTF-8', 'UTF-16BE');
            return ($conv !== false) ? $conv : $text;
        }
        if (substr($text, 0, 2) === "\xFF\xFE") {
            $conv = @mb_convert_encoding(substr($text, 2), 'UTF-8', 'UTF-16LE');
            return ($conv !== false) ? $conv : $text;
        }

        if (function_exists('mb_detect_encoding')) {
            $enc = mb_detect_encoding($text, array('UTF-8', 'ISO-8859-1', 'Windows-1252'), true);
            if ($enc && $enc !== 'UTF-8') {
                $conv = @mb_convert_encoding($text, 'UTF-8', $enc);
                if ($conv !== false) {
                    return $conv;
                }
            }
        }

        if (function_exists('mb_check_encoding') && !mb_check_encoding($text, 'UTF-8')) {
            $conv = @mb_convert_encoding($text, 'UTF-8', 'ISO-8859-1');
            if ($conv !== false) {
                return $conv;
            }
        }

        return $text;
    }

    /**
     * JPEG / XML / xref embebidos no son texto de factura.
     */
    public function esBinarioImagen($plain)
    {
        $head = substr($plain, 0, 16);
        if (substr($head, 0, 2) === "\xFF\xD8") {
            return true;
        }
        if (strpos($head, 'JFIF') !== false || strpos($head, 'Exif') !== false) {
            return true;
        }
        if (substr($plain, 0, 8) === "\x89PNG\r\n\x1a\n") {
            return true;
        }
        if (strpos($plain, '<?xpacket') !== false || strpos($plain, '<x:xmpmeta') !== false) {
            return true;
        }
        return false;
    }

    public function textoUtil($text)
    {
        $text = trim($text);
        if (strlen($text) < 8) {
            return false;
        }
        $ok = preg_match_all('/[A-Za-z0-9]/', $text);
        return ($ok / max(strlen($text), 1)) > 0.40;
    }
}
