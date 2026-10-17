<?php
/**
 * models/PadronImportModel.php
 * Prelectura e importación de padrones a vfpmedicos.padron_activos.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

class PadronImportModel
{
    /** @var PDO */
    private $db;

    /** @var array<string, int> */
    private $largos = array(
        'CODADH' => 4, 'NOMBRE' => 50, 'SEXO' => 1, 'TELEFONO' => 40, 'WHATSAPP' => 30,
        'DOMICILIO' => 40, 'D_PUERTA' => 5, 'D_PISO' => 2, 'D_DEPTO' => 3, 'LOCALIDAD' => 30,
        'TIPODOC' => 3, 'NROAFILIAD' => 14, 'PLAN' => 10, 'POSNET' => 30, 'ESTADO' => 10,
        'CODPOS' => 10, 'INCAP' => 5, 'CERTINCA' => 8, 'DIAGINCA' => 8, 'PMI' => 3,
        'EMAIL' => 100, 'PROVIN' => 6, 'NACION' => 6, 'ENROL1' => 30, 'ENROL2' => 30, 'ENROL3' => 30,
        'OBRASOC' => 10, 'PERIODO' => 7, 'NPADRON' => 80, 'userupdate' => 20,
    );

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * @param string $path
     * @param string $nombreOriginal
     * @param string $obrasoc
     * @param string $npadron
     * @param string $periodo
     * @return array<string, mixed>
     */
    public function previsualizar($path, $nombreOriginal, $obrasoc, $npadron, $periodo)
    {
        $resumen = $this->recorrer($path, $nombreOriginal, 10);
        $resumen['obrasoc'] = $obrasoc;
        $resumen['npadron'] = $npadron;
        $resumen['periodo'] = $periodo;
        $resumen['msg'] = 'Se detectaron ' . $resumen['activos'] . ' registros activos para el período ' . $periodo . '.';
        return $resumen;
    }

    /**
     * @param string $path
     * @param string $nombreOriginal
     * @param string $obrasoc
     * @param string $npadron
     * @param string $periodo
     * @param string $fbaj  Y-m-d o vacío. Si viene, es la vigencia hasta del padrón.
     * @param string $usuario
     * @return array<string, mixed>
     */
    public function confirmar($path, $nombreOriginal, $obrasoc, $npadron, $periodo, $fbaj, $usuario)
    {
        $columnas = array(
            'AFILIADO', 'CODADH', 'NOMBRE', 'FECNACIM', 'SEXO', 'TELEFONO', 'WHATSAPP',
            'DOMICILIO', 'D_PUERTA', 'D_PISO', 'D_DEPTO', 'LOCALIDAD', 'TIPODOC', 'NRODOC',
            'NROAFILIAD', 'PLAN', 'POSNET', 'ESTADO', 'CODPOS', 'FING', 'FBAJ', 'INCAP',
            'CERTINCA', 'DIAGINCA', 'VTOCERINCA', 'PMI', 'PMIHASTA', 'EMAIL', 'PROVIN',
            'NACION', 'ENROL1', 'ENROL2', 'ENROL3', 'OBRASOC', 'PERIODO', 'NPADRON', 'userupdate',
        );
        $numericas = array('AFILIADO' => true, 'NRODOC' => true);
        $fechas = array('FECNACIM' => true, 'FING' => true, 'FBAJ' => true, 'VTOCERINCA' => true, 'PMIHASTA' => true);
        $usuario = $this->texto($usuario, $this->largos['userupdate']);
        $insertados = 0;
        $lote = array();

        $this->db->beginTransaction();
        try {
            $del = $this->db->prepare(
                'DELETE FROM vfpmedicos.padron_activos
                 WHERE OBRASOC = :obrasoc AND PERIODO = :periodo AND NPADRON = :npadron'
            );
            $del->execute(array(
                ':obrasoc' => $obrasoc,
                ':periodo' => $periodo,
                ':npadron' => $npadron,
            ));
            $reemplazados = $del->rowCount();

            $volcar = function (array $fila) use (&$lote, &$insertados, $columnas, $numericas, $fechas, $obrasoc, $periodo, $npadron, $usuario) {
                if ($this->esBaja($fila)) {
                    return;
                }
                $fila['OBRASOC'] = $obrasoc;
                $fila['PERIODO'] = $periodo;
                $fila['NPADRON'] = $npadron;
                $fila['userupdate'] = $usuario;
                $valores = array();
                foreach ($columnas as $col) {
                    $valor = isset($fila[$col]) ? $fila[$col] : '';
                    if (isset($fechas[$col])) {
                        $valores[] = $this->fechaSql($valor);
                    } elseif (isset($numericas[$col])) {
                        $valores[] = $this->numero($valor, $col === 'NRODOC' ? 9 : 15);
                    } else {
                        $valores[] = $this->texto($valor, isset($this->largos[$col]) ? $this->largos[$col] : 20);
                    }
                }
                $lote[] = $valores;
                if (count($lote) >= 200) {
                    $insertados += $this->insertarLote($columnas, $lote);
                    $lote = array();
                }
            };

            $this->recorrer($path, $nombreOriginal, 0, $volcar);
            if ($lote) {
                $insertados += $this->insertarLote($columnas, $lote);
            }

            $this->registrarPadrosoc($obrasoc, $npadron, $periodo, $fbaj, $insertados);
            $this->actualizarObra($obrasoc, $npadron, $insertados);
            $this->db->commit();
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }

        return array(
            'insertados'    => $insertados,
            'reemplazados'  => $reemplazados,
            'obrasoc'       => $obrasoc,
            'npadron'       => $npadron,
            'periodo'       => $periodo,
        );
    }

    /**
     * @param string        $path
     * @param string        $nombreOriginal
     * @param int           $limitePreview  0 = no guardar muestra
     * @param callable|null $cadaActivo
     * @return array<string, mixed>
     */
    private function recorrer($path, $nombreOriginal, $limitePreview, $cadaActivo = null)
    {
        $ext = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));
        $leidos = 0;
        $bajas = 0;
        $activos = 0;
        $preview = array();
        $vioColumna = false;

        $consumir = function (array $fila) use (&$leidos, &$bajas, &$activos, &$preview, &$vioColumna, $limitePreview, $cadaActivo) {
            if ($this->filaVacia($fila)) {
                return;
            }
            $vioColumna = true;
            $leidos++;
            if ($this->esBaja($fila)) {
                $bajas++;
                return;
            }
            $activos++;
            if ($cadaActivo) {
                $cadaActivo($fila);
            }
            if ($limitePreview > 0 && count($preview) < $limitePreview) {
                $preview[] = array(
                    'NROAFILIAD' => $this->texto(isset($fila['NROAFILIAD']) ? $fila['NROAFILIAD'] : '', 14),
                    'CODADH'     => $this->texto(isset($fila['CODADH']) ? $fila['CODADH'] : '', 4),
                    'NOMBRE'     => $this->texto(isset($fila['NOMBRE']) ? $fila['NOMBRE'] : '', 50),
                    'NRODOC'     => $this->texto(isset($fila['NRODOC']) ? $fila['NRODOC'] : '', 15),
                    'PLAN'       => $this->texto(isset($fila['PLAN']) ? $fila['PLAN'] : '', 10),
                    'ESTADO'     => $this->texto(isset($fila['ESTADO']) ? $fila['ESTADO'] : '', 10),
                    'FBAJ'       => (string) $this->fechaSql(isset($fila['FBAJ']) ? $fila['FBAJ'] : ''),
                );
            }
        };

        if ($ext === 'dbf') {
            $this->leerDbf($path, $consumir);
        } elseif ($ext === 'csv' || $ext === 'txt') {
            $this->leerTexto($path, $consumir);
        } elseif ($ext === 'xlsx' || $ext === 'xls') {
            $this->leerExcel($path, $ext, $consumir);
        } else {
            throw new InvalidArgumentException('Formato no admitido. Use DBF, XLSX, XLS, CSV o TXT.');
        }

        if (!$vioColumna) {
            throw new InvalidArgumentException('El archivo no tiene registros reconocibles de padrón.');
        }

        return array(
            'leidos'  => $leidos,
            'bajas'   => $bajas,
            'activos' => $activos,
            'preview' => $preview,
        );
    }

    /**
     * @param callable $consumir
     */
    private function leerDbf($path, $consumir)
    {
        $fh = fopen($path, 'rb');
        if ($fh === false) {
            throw new RuntimeException('No se pudo abrir el DBF.');
        }
        $cab = fread($fh, 32);
        if ($cab === false || strlen($cab) < 32) {
            fclose($fh);
            throw new RuntimeException('El DBF está incompleto.');
        }
        $nrec = unpack('V', substr($cab, 4, 4))[1];
        $hlen = unpack('v', substr($cab, 8, 2))[1];
        $rlen = unpack('v', substr($cab, 10, 2))[1];
        $campos = array();
        while (ftell($fh) < $hlen - 1) {
            $buf = fread($fh, 32);
            if ($buf === false || $buf === '' || ord($buf[0]) === 0x0D) {
                break;
            }
            $campos[] = array(
                'nombre' => rtrim(substr($buf, 0, 11), "\0 "),
                'tipo'   => $buf[11],
                'largo'  => ord($buf[16]),
            );
        }
        if (!$campos) {
            fclose($fh);
            throw new InvalidArgumentException('El DBF no tiene columnas.');
        }
        fseek($fh, $hlen);
        for ($i = 0; $i < $nrec; $i++) {
            $rec = fread($fh, $rlen);
            if ($rec === false || strlen($rec) < $rlen) {
                break;
            }
            if ($rec[0] === '*') {
                continue;
            }
            $offset = 1;
            $cruda = array();
            foreach ($campos as $campo) {
                $raw = rtrim(substr($rec, $offset, $campo['largo']));
                $offset += $campo['largo'];
                if ($campo['tipo'] === 'D') {
                    $raw = $this->fechaDbf($raw);
                }
                $cruda[$campo['nombre']] = $this->aUtf8($raw);
            }
            $consumir($this->mapear($cruda));
        }
        fclose($fh);
    }

    /**
     * @param callable $consumir
     */
    private function leerTexto($path, $consumir)
    {
        $fh = fopen($path, 'rb');
        if ($fh === false) {
            throw new RuntimeException('No se pudo abrir el archivo.');
        }
        $primera = fgets($fh);
        if ($primera === false) {
            fclose($fh);
            throw new InvalidArgumentException('El archivo está vacío.');
        }
        $delim = $this->delimitador($primera);
        rewind($fh);
        $encabezado = null;
        while (($row = fgetcsv($fh, 0, $delim)) !== false) {
            if ($row === array(null)) {
                continue;
            }
            $row = array_map(function ($v) {
                $v = $this->aUtf8(trim((string) $v));
                if (strncmp($v, "\xEF\xBB\xBF", 3) === 0) {
                    $v = substr($v, 3);
                }
                return $v;
            }, $row);
            if ($encabezado === null) {
                $encabezado = $row;
                if ($this->canon(isset($row[0]) ? $row[0] : '') === null && !$this->pareceEncabezado($row)) {
                    throw new InvalidArgumentException('No se reconocieron las columnas del padrón.');
                }
                continue;
            }
            $assoc = array();
            foreach ($encabezado as $i => $nombre) {
                $assoc[$nombre] = isset($row[$i]) ? $row[$i] : '';
            }
            $consumir($this->mapear($assoc));
        }
        fclose($fh);
    }

    /**
     * @param callable $consumir
     */
    private function leerExcel($path, $ext, $consumir)
    {
        $magic = file_get_contents($path, false, null, 0, 4);
        $esZip = $magic !== false && strncmp($magic, "PK\x03\x04", 4) === 0;
        if ($ext === 'xls' && !$esZip) {
            throw new InvalidArgumentException('Ese XLS no se puede leer. Guárdelo como XLSX o CSV.');
        }
        $filas = $this->leerXlsx($path);
        if (!$filas) {
            throw new InvalidArgumentException('El Excel está vacío.');
        }
        $encabezado = array_shift($filas);
        if (!$this->pareceEncabezado($encabezado)) {
            throw new InvalidArgumentException('No se reconocieron las columnas del padrón.');
        }
        foreach ($filas as $row) {
            $assoc = array();
            foreach ($encabezado as $i => $nombre) {
                $assoc[$nombre] = isset($row[$i]) ? $row[$i] : '';
            }
            $consumir($this->mapear($assoc));
        }
    }

    /**
     * @return array<int, array<int, string>>
     */
    private function leerXlsx($path)
    {
        if (!class_exists('ZipArchive')) {
            throw new RuntimeException('No se puede leer Excel en este servidor. Use CSV.');
        }
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new RuntimeException('No se pudo abrir el Excel.');
        }
        $shared = array();
        $ss = $zip->getFromName('xl/sharedStrings.xml');
        if ($ss !== false) {
            $xml = @simplexml_load_string($ss);
            if ($xml) {
                $ns = $xml->getNamespaces(true);
                $uri = isset($ns['']) ? $ns[''] : 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
                foreach ($xml->children($uri)->si as $si) {
                    $nodo = $si->children($uri);
                    if (isset($nodo->t)) {
                        $shared[] = (string) $nodo->t;
                    } else {
                        $texto = '';
                        foreach ($nodo->r as $run) {
                            $texto .= (string) $run->children($uri)->t;
                        }
                        $shared[] = $texto;
                    }
                }
            }
        }
        $sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();
        if ($sheet === false) {
            throw new RuntimeException('El Excel no tiene una hoja legible.');
        }
        $xml = @simplexml_load_string($sheet);
        if (!$xml) {
            throw new RuntimeException('No se pudo leer la hoja del Excel.');
        }
        $ns = $xml->getNamespaces(true);
        $uri = isset($ns['']) ? $ns[''] : 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
        $filas = array();
        $sheetData = $xml->children($uri)->sheetData;
        if (!$sheetData) {
            return $filas;
        }
        foreach ($sheetData->children($uri)->row as $row) {
            $linea = array();
            $col = 0;
            foreach ($row->children($uri)->c as $c) {
                $idx = $this->indiceColumna((string) $c['r']);
                $tipo = (string) $c['t'];
                $nodo = $c->children($uri);
                $valor = isset($nodo->v) ? (string) $nodo->v : '';
                if ($tipo === 's') {
                    $valor = isset($shared[(int) $valor]) ? $shared[(int) $valor] : '';
                } elseif ($tipo === 'inlineStr' && isset($nodo->is)) {
                    $valor = (string) $nodo->is->children($uri)->t;
                }
                while ($col < $idx) {
                    $linea[] = '';
                    $col++;
                }
                $linea[] = $this->aUtf8(trim($valor));
                $col++;
            }
            $filas[] = $linea;
        }
        return $filas;
    }

    /**
     * @param array<string, string> $cruda
     * @return array<string, string>
     */
    private function mapear(array $cruda)
    {
        $out = array();
        foreach ($cruda as $nombre => $valor) {
            $canon = $this->canon((string) $nombre);
            if ($canon === null) {
                continue;
            }
            if (!isset($out[$canon]) || $out[$canon] === '') {
                $out[$canon] = trim((string) $valor);
            }
        }
        return $out;
    }

    /**
     * @param string $nombre
     * @return string|null
     */
    private function canon($nombre)
    {
        $h = strtoupper(trim($nombre));
        $h = strtr($h, array('Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ñ' => 'N'));
        $h = str_replace(array(' ', '_', '-', '.', '/'), '', $h);
        $map = array(
            'AFILIADO' => 'AFILIADO', 'CODADH' => 'CODADH', 'ADHERENTE' => 'CODADH',
            'NOMBRE' => 'NOMBRE', 'APEYNOM' => 'NOMBRE', 'APELLIDOYNOMBRE' => 'NOMBRE',
            'FECNACIM' => 'FECNACIM', 'FECNAC' => 'FECNACIM', 'FECHANAC' => 'FECNACIM', 'FECHANACIMIENTO' => 'FECNACIM',
            'SEXO' => 'SEXO', 'TELEFONO' => 'TELEFONO', 'TEL' => 'TELEFONO', 'WHATSAPP' => 'WHATSAPP',
            'DOMICILIO' => 'DOMICILIO', 'DIRECCION' => 'DOMICILIO', 'CALLE' => 'DOMICILIO',
            'DPUERTA' => 'D_PUERTA', 'PUERTA' => 'D_PUERTA', 'DPISO' => 'D_PISO', 'PISO' => 'D_PISO',
            'DDEPTO' => 'D_DEPTO', 'DEPTO' => 'D_DEPTO', 'LOCALIDAD' => 'LOCALIDAD',
            'TIPODOC' => 'TIPODOC', 'NRODOC' => 'NRODOC', 'DOCUMENTO' => 'NRODOC', 'DNI' => 'NRODOC',
            'NROAFILIAD' => 'NROAFILIAD', 'NROAFILIADO' => 'NROAFILIAD', 'AFILIADONRO' => 'NROAFILIAD',
            'PLAN' => 'PLAN', 'POSNET' => 'POSNET', 'ESTADO' => 'ESTADO', 'CODPOS' => 'CODPOS', 'CP' => 'CODPOS',
            'FING' => 'FING', 'FECHAINGRESO' => 'FING', 'FBAJ' => 'FBAJ', 'FBAJA' => 'FBAJ', 'FECHABAJA' => 'FBAJ',
            'INCAP' => 'INCAP', 'CERTINCA' => 'CERTINCA', 'DIAGINCA' => 'DIAGINCA',
            'VTOCERINCA' => 'VTOCERINCA', 'PMI' => 'PMI', 'PMIHASTA' => 'PMIHASTA',
            'EMAIL' => 'EMAIL', 'MAIL' => 'EMAIL', 'PROVIN' => 'PROVIN', 'PROVINCIA' => 'PROVIN',
            'NACION' => 'NACION', 'ENROL1' => 'ENROL1', 'ENROL2' => 'ENROL2', 'ENROL3' => 'ENROL3',
        );
        return isset($map[$h]) ? $map[$h] : null;
    }

    /**
     * @param array<int, string> $encabezado
     */
    private function pareceEncabezado(array $encabezado)
    {
        foreach ($encabezado as $nombre) {
            if ($this->canon((string) $nombre) !== null) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param array<string, string> $fila
     */
    private function esBaja(array $fila)
    {
        $fbaj = isset($fila['FBAJ']) ? trim((string) $fila['FBAJ']) : '';
        if ($this->fechaSql($fbaj) !== null) {
            return true;
        }
        $estado = strtoupper(isset($fila['ESTADO']) ? trim((string) $fila['ESTADO']) : '');
        return $estado === 'B' || $estado === 'BAJA';
    }

    /**
     * @param array<string, string> $fila
     */
    private function filaVacia(array $fila)
    {
        foreach ($fila as $valor) {
            if (trim((string) $valor) !== '') {
                return false;
            }
        }
        return true;
    }

    /**
     * @param array<int, string>           $columnas
     * @param array<int, array<int, mixed>> $lote
     */
    private function insertarLote(array $columnas, array $lote)
    {
        if (!$lote) {
            return 0;
        }
        $marca = '(' . implode(',', array_fill(0, count($columnas), '?')) . ', NOW())';
        $sql = 'INSERT INTO vfpmedicos.padron_activos (`' . implode('`,`', $columnas) . '`, fechaupdate) VALUES ';
        $grupos = array();
        $params = array();
        foreach ($lote as $fila) {
            $grupos[] = $marca;
            foreach ($fila as $valor) {
                $params[] = $valor;
            }
        }
        $sql .= implode(',', $grupos);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return count($lote);
    }

    private function registrarPadrosoc($obrasoc, $npadron, $periodo, $fbaj, $cantidad)
    {
        $desde = $this->inicioPeriodo($periodo);
        $hasta = $fbaj !== '' ? $fbaj : $this->finPeriodo($periodo);
        $sel = $this->db->prepare(
            'SELECT id FROM vfpmedicos.padrosoc
             WHERE TRIM(OBRASOC) = :obrasoc AND TRIM(NPADRON) = :npadron
             LIMIT 1'
        );
        $sel->execute(array(':obrasoc' => $obrasoc, ':npadron' => $npadron));
        $id = $sel->fetchColumn();
        if ($id) {
            $up = $this->db->prepare(
                'UPDATE vfpmedicos.padrosoc
                 SET DESDE = :desde, HASTA = :hasta, ACTIVO = :activo, CANTREG = :cant
                 WHERE id = :id'
            );
            $up->execute(array(
                ':desde' => $desde,
                ':hasta' => $hasta,
                ':activo' => 'T',
                ':cant' => $cantidad,
                ':id' => $id,
            ));
            return;
        }
        $ins = $this->db->prepare(
            'INSERT INTO vfpmedicos.padrosoc (OBRASOC, DESDE, HASTA, NPADRON, ACTIVO, CANTREG)
             VALUES (:obrasoc, :desde, :hasta, :npadron, :activo, :cant)'
        );
        $ins->execute(array(
            ':obrasoc' => substr($obrasoc, 0, 8),
            ':desde' => $desde,
            ':hasta' => $hasta,
            ':npadron' => substr($npadron, 0, 80),
            ':activo' => 'T',
            ':cant' => $cantidad,
        ));
    }

    private function actualizarObra($obrasoc, $npadron, $cantidad)
    {
        $up = $this->db->prepare(
            'UPDATE cartilla.obrasoc
             SET TAPADRON = :npadron, TACANTAFIL = :cant
             WHERE TRIM(TACODIGO) = :codigo'
        );
        $up->execute(array(
            ':npadron' => substr($npadron, 0, 80),
            ':cant' => $cantidad,
            ':codigo' => $obrasoc,
        ));
    }

    /**
     * @param string $periodo AA/MM
     */
    private function inicioPeriodo($periodo)
    {
        $anio = 2000 + (int) substr($periodo, 0, 2);
        $mes = (int) substr($periodo, 3, 2);
        return sprintf('%04d-%02d-01', $anio, $mes);
    }

    /**
     * @param string $periodo AA/MM
     */
    private function finPeriodo($periodo)
    {
        $inicio = $this->inicioPeriodo($periodo);
        $dt = DateTime::createFromFormat('Y-m-d', $inicio);
        if (!$dt) {
            return $inicio;
        }
        $dt->modify('last day of this month');
        return $dt->format('Y-m-d');
    }

    private function fechaDbf($raw)
    {
        $raw = trim($raw);
        if (preg_match('/^\d{8}$/', $raw) && $raw !== '00000000') {
            return substr($raw, 0, 4) . '-' . substr($raw, 4, 2) . '-' . substr($raw, 6, 2);
        }
        return $raw;
    }

    /**
     * @param mixed $valor
     * @return string|null
     */
    private function fechaSql($valor)
    {
        $valor = trim((string) $valor);
        if ($valor === '' || $valor === '0000-00-00' || $valor === '00000000' || $valor === '00/00/0000') {
            return null;
        }
        if (preg_match('/^\d{8}$/', $valor)) {
            $valor = substr($valor, 0, 4) . '-' . substr($valor, 4, 2) . '-' . substr($valor, 6, 2);
        }
        if (preg_match('/^(\d{2})[\/\-](\d{2})[\/\-](\d{4})$/', $valor, $m)) {
            $valor = $m[3] . '-' . $m[2] . '-' . $m[1];
        }
        $dt = DateTime::createFromFormat('Y-m-d', substr($valor, 0, 10));
        if (!$dt || $dt->format('Y-m-d') !== substr($valor, 0, 10)) {
            return null;
        }
        $anio = (int) $dt->format('Y');
        if ($anio < 1900 || $anio > 2100) {
            return null;
        }
        return $dt->format('Y-m-d');
    }

    /**
     * @param mixed $valor
     * @return string|null
     */
    private function numero($valor, $digitos)
    {
        $valor = trim((string) $valor);
        if ($valor === '') {
            return null;
        }
        $valor = str_replace(array(' ', ','), array('', '.'), $valor);
        if (!is_numeric($valor)) {
            return null;
        }
        $entero = (string) (int) round((float) $valor);
        if (strlen(ltrim($entero, '-')) > $digitos) {
            $entero = substr($entero, 0, $digitos);
        }
        return $entero;
    }

    private function texto($valor, $largo)
    {
        $valor = trim($this->aUtf8((string) $valor));
        if (function_exists('mb_substr')) {
            return mb_substr($valor, 0, $largo, 'UTF-8');
        }
        return substr($valor, 0, $largo);
    }

    private function aUtf8($valor)
    {
        if ($valor === '' || mb_check_encoding($valor, 'UTF-8')) {
            return $valor;
        }
        $conv = @mb_convert_encoding($valor, 'UTF-8', 'Windows-1252');
        return $conv === false ? $valor : $conv;
    }

    private function delimitador($linea)
    {
        $tab = substr_count($linea, "\t");
        $punto = substr_count($linea, ';');
        $coma = substr_count($linea, ',');
        if ($tab >= $punto && $tab >= $coma && $tab > 0) {
            return "\t";
        }
        return $punto > $coma ? ';' : ',';
    }

    private function indiceColumna($ref)
    {
        if (!preg_match('/^([A-Z]+)/i', $ref, $m)) {
            return 0;
        }
        $letras = strtoupper($m[1]);
        $n = 0;
        $len = strlen($letras);
        for ($i = 0; $i < $len; $i++) {
            $n = $n * 26 + (ord($letras[$i]) - 64);
        }
        return $n - 1;
    }
}
