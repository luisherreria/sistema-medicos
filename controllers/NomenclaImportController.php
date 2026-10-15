<?php
/**
 * controllers/NomenclaImportController.php
 * Importación de plantillas al nomenclador y al prestador.
 */

declare(strict_types=1);

require_once __DIR__ . '/../models/NomenclaImportModel.php';

class NomenclaImportController
{
    /** @var array<string, string> encabezado normalizado => campo */
    private $sinonimos = array(
        'TACODIGO' => 'codigo',
        'CODIGO' => 'codigo',
        'COD' => 'codigo',
        'CODPRACTICA' => 'codigo',
        'CODIGOPRACTICA' => 'codigo',
        'TADESCRIP' => 'nombre',
        'DESCRIP' => 'nombre',
        'DESCRIPCION' => 'nombre',
        'PRACTICA' => 'nombre',
        'NOMBRE' => 'nombre',
        'TAIMPORTE' => 'importe',
        'IMPORTE' => 'importe',
        'PRECIO' => 'importe',
        'VALOR' => 'importe',
        'TAGRUPO' => 'grupo',
        'GRUPO' => 'grupo',
        'VALNBU' => 'valnbu',
        'NBU' => 'valnbu',
        'SUBGRUPO' => 'subgrupo',
        'CAPITULO' => 'capitulo',
        'NOMENCLADA' => 'nomenclada',
        'COMPLEJIDAD' => 'complejida',
        'COMPLEJIDA' => 'complejida',
        'GRUPOCAR' => 'grupocar',
        'GRUPOSUP' => 'gruposup',
        'CODPRESTADOR' => 'prestador',
        'PRESTADOR' => 'prestador',
        'CODPRESTA' => 'prestador',
        'PECODIGO' => 'prestador',
        'MATRICULA' => 'prestador',
    );

    public function validarPrestador(): void
    {
        $this->jsonAuth();
        $cod = trim((string) ($_GET['cod'] ?? $_POST['cod'] ?? ''));
        $model = new NomenclaImportModel();
        $row = $model->validarPrestador($cod);
        if ($row === null) {
            $this->json(array('ok' => false, 'msg' => 'El prestador no existe en el padrón.'), 404);
        }
        $this->json(array('ok' => true, 'prestador' => $row));
    }

    public function procesar(): void
    {
        $this->jsonAuth();
        if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->json(array('ok' => false, 'msg' => 'Método no permitido.'), 405);
        }
        $this->csrf();

        if (empty($_FILES['archivo']) || (int) $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
            $this->json(array('ok' => false, 'msg' => 'Seleccione un archivo para importar.'), 422);
        }

        $tmp = (string) $_FILES['archivo']['tmp_name'];
        $nombre = (string) ($_FILES['archivo']['name'] ?? '');
        $ext = strtolower(pathinfo($nombre, PATHINFO_EXTENSION));
        if (!in_array($ext, array('xlsx', 'csv', 'pdf', 'jpg', 'jpeg', 'png'), true)) {
            $this->json(array('ok' => false, 'msg' => 'Formato no admitido. Use Excel, CSV, PDF o imagen.'), 422);
        }
        if (in_array($ext, array('pdf', 'jpg', 'jpeg', 'png'), true)) {
            $this->json(array(
                'ok' => false,
                'msg' => 'El archivo se recibió, pero una imagen o un PDF no trae columnas identificables. Use una plantilla Excel o CSV.',
            ), 422);
        }

        try {
            $filasCrudas = $ext === 'csv' ? $this->leerCsv($tmp) : $this->leerXlsx($tmp);
            $filas = $this->mapearFilas($filasCrudas);
        } catch (Throwable $e) {
            error_log('NomenclaImportController::procesar lectura — ' . $e->getMessage());
            $this->json(array('ok' => false, 'msg' => 'No se pudo leer la plantilla. Verifique que la primera fila tenga los nombres de columna.'), 422);
        }

        if (!$filas) {
            $this->json(array('ok' => false, 'msg' => 'La plantilla no tiene prácticas para importar.'), 422);
        }

        $cargar = filter_var($_POST['cargar_prestador'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $prestador = trim((string) ($_POST['prestador'] ?? ''));
        $usuario = $this->usuario();
        $userId = (int) ($_SESSION['user_id'] ?? $_SESSION['user']['id'] ?? 0);

        try {
            $model = new NomenclaImportModel();
            $res = $model->procesarImportacion($filas, $prestador, $cargar, $usuario, $userId);
        } catch (Throwable $e) {
            error_log('NomenclaImportController::procesar — ' . $e->getMessage());
            $this->json(array('ok' => false, 'msg' => 'No se pudo completar la importación. No se grabaron cambios.'), 500);
        }

        if (!empty($res['pendiente'])) {
            $_SESSION['nomencla_import_pendiente'] = $res['pendiente'];
        } else {
            unset($_SESSION['nomencla_import_pendiente']);
        }
        unset($res['pendiente']);
        $this->json($res);
    }

    public function grabarSucursales(): void
    {
        $this->jsonAuth();
        if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->json(array('ok' => false, 'msg' => 'Método no permitido.'), 405);
        }
        $this->csrf();

        $pendiente = $_SESSION['nomencla_import_pendiente'] ?? null;
        if (!is_array($pendiente) || empty($pendiente['practicas'])) {
            $this->json(array('ok' => false, 'msg' => 'No hay una importación pendiente de sucursales.'), 422);
        }

        $elegidas = $_POST['sucursales'] ?? array();
        if (!is_array($elegidas)) {
            $elegidas = array();
        }
        $elegidas = array_values(array_filter(array_map('trim', $elegidas), function ($v) {
            return $v !== '';
        }));
        if (!$elegidas) {
            $this->json(array(
                'ok' => false,
                'msg' => 'No se grabarán prácticas en las sucursales. El evento ha sido registrado.',
            ), 422);
        }

        try {
            $model = new NomenclaImportModel();
            $n = $model->grabarSucursalesElegidas(
                (string) $pendiente['prestador'],
                $elegidas,
                (array) $pendiente['practicas'],
                (string) $pendiente['usuario'],
                (int) $pendiente['user_id']
            );
            unset($_SESSION['nomencla_import_pendiente']);
            $this->json(array(
                'ok' => true,
                'msg' => 'Se grabaron ' . $n . ' prácticas en las sucursales seleccionadas.',
            ));
        } catch (Throwable $e) {
            error_log('NomenclaImportController::grabarSucursales — ' . $e->getMessage());
            $this->json(array('ok' => false, 'msg' => 'No se pudieron grabar las sucursales.'), 500);
        }
    }

    public function omitirSucursales(): void
    {
        $this->jsonAuth();
        $this->csrf();
        $pendiente = $_SESSION['nomencla_import_pendiente'] ?? null;
        $model = new NomenclaImportModel();
        $prestador = is_array($pendiente) ? (string) ($pendiente['prestador'] ?? '') : '';
        $usuario = is_array($pendiente) ? (string) ($pendiente['usuario'] ?? $this->usuario()) : $this->usuario();
        $userId = is_array($pendiente) ? (int) ($pendiente['user_id'] ?? 0) : (int) ($_SESSION['user_id'] ?? 0);
        $model->registrarOmisionSucursales($prestador, $usuario, $userId);
        unset($_SESSION['nomencla_import_pendiente']);
        $this->json(array(
            'ok' => true,
            'msg' => 'No se grabarán prácticas en las sucursales. El evento ha sido registrado.',
        ));
    }

    /**
     * @param array<int, array<int, string>> $tabla
     * @return array<int, array<string, string>>
     */
    private function mapearFilas(array $tabla): array
    {
        if (!$tabla) {
            return array();
        }
        $headers = array_shift($tabla);
        $mapa = array();
        foreach ($headers as $i => $titulo) {
            $campo = $this->campoDeEncabezado((string) $titulo);
            if ($campo !== null && !isset($mapa[$campo])) {
                $mapa[$campo] = $i;
            }
        }
        if (!isset($mapa['codigo']) || !isset($mapa['nombre'])) {
            throw new InvalidArgumentException('Faltan columnas de código o nombre.');
        }

        $filas = array();
        foreach ($tabla as $linea) {
            $codigo = trim((string) ($linea[$mapa['codigo']] ?? ''));
            $nombre = trim((string) ($linea[$mapa['nombre']] ?? ''));
            if ($codigo === '' && $nombre === '') {
                continue;
            }
            $fila = array(
                'codigo' => $codigo,
                'nombre' => $nombre,
                'importe' => '',
                'grupo' => '',
                'prestador' => '',
                'valnbu' => '',
                'subgrupo' => '',
                'capitulo' => '',
                'nomenclada' => '',
                'complejida' => '',
                'grupocar' => '',
                'gruposup' => '',
            );
            foreach (array('importe', 'grupo', 'prestador', 'valnbu', 'subgrupo', 'capitulo', 'nomenclada', 'complejida', 'grupocar', 'gruposup') as $campo) {
                if (isset($mapa[$campo])) {
                    $fila[$campo] = trim((string) ($linea[$mapa[$campo]] ?? ''));
                }
            }
            $filas[] = $fila;
            if (count($filas) >= 5000) {
                break;
            }
        }
        return $filas;
    }

    private function campoDeEncabezado($titulo)
    {
        $key = strtoupper(trim($titulo));
        $key = strtr($key, array(
            'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ñ' => 'N',
        ));
        $key = preg_replace('/[^A-Z0-9]/', '', $key);
        if (!is_string($key) || $key === '') {
            return null;
        }
        return $this->sinonimos[$key] ?? null;
    }

    /**
     * @return array<int, array<int, string>>
     */
    private function leerCsv($path): array
    {
        $fh = fopen($path, 'rb');
        if ($fh === false) {
            throw new RuntimeException('No se pudo abrir el CSV.');
        }
        $primera = fgets($fh);
        if ($primera === false) {
            fclose($fh);
            return array();
        }
        $delim = substr_count($primera, ';') > substr_count($primera, ',') ? ';' : ',';
        rewind($fh);
        $filas = array();
        while (($row = fgetcsv($fh, 0, $delim)) !== false) {
            if ($row === array(null)) {
                continue;
            }
            $filas[] = array_map(function ($v) {
                $v = (string) $v;
                if (strncmp($v, "\xEF\xBB\xBF", 3) === 0) {
                    $v = substr($v, 3);
                }
                return trim($v);
            }, $row);
        }
        fclose($fh);
        return $filas;
    }

    /**
     * @return array<int, array<int, string>>
     */
    private function leerXlsx($path): array
    {
        if (is_file(__DIR__ . '/../vendor/autoload.php')) {
            require_once __DIR__ . '/../vendor/autoload.php';
            if (class_exists('PhpOffice\\PhpSpreadsheet\\IOFactory')) {
                $sheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($path)->getActiveSheet();
                $filas = array();
                foreach ($sheet->toArray(null, true, true, false) as $fila) {
                    $filas[] = array_map(function ($v) {
                        return trim((string) $v);
                    }, $fila);
                }
                return $filas;
            }
        }
        return $this->leerXlsxZip($path);
    }

    /**
     * @return array<int, array<int, string>>
     */
    private function leerXlsxZip($path): array
    {
        if (!class_exists('ZipArchive')) {
            throw new RuntimeException('ZipArchive no está disponible.');
        }
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new RuntimeException('El Excel no se pudo abrir.');
        }
        $shared = array();
        $ss = $zip->getFromName('xl/sharedStrings.xml');
        if (is_string($ss) && $ss !== '') {
            $xml = simplexml_load_string($ss);
            if ($xml) {
                $xml->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
                foreach ($xml->xpath('//m:si') ?: array() as $si) {
                    $textos = $si->xpath('.//m:t') ?: array();
                    $valor = '';
                    foreach ($textos as $t) {
                        $valor .= (string) $t;
                    }
                    $shared[] = $valor;
                }
            }
        }
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();
        if (!is_string($sheetXml) || $sheetXml === '') {
            throw new RuntimeException('La planilla no tiene hoja 1.');
        }
        $xml = simplexml_load_string($sheetXml);
        if (!$xml) {
            throw new RuntimeException('No se pudo leer la hoja.');
        }
        $xml->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $filas = array();
        foreach ($xml->xpath('//m:sheetData/m:row') ?: array() as $row) {
            $linea = array();
            foreach ($row->c as $c) {
                $ref = (string) $c['r'];
                $col = preg_replace('/[0-9]/', '', $ref);
                $idx = $this->colIndex((string) $col);
                $tipo = (string) $c['t'];
                $valor = isset($c->v) ? (string) $c->v : '';
                if ($tipo === 's') {
                    $valor = $shared[(int) $valor] ?? '';
                } elseif ($tipo === 'inlineStr') {
                    $valor = isset($c->is->t) ? (string) $c->is->t : '';
                }
                $linea[$idx] = trim($valor);
            }
            if ($linea) {
                ksort($linea);
                $max = max(array_keys($linea));
                $completa = array();
                for ($i = 0; $i <= $max; $i++) {
                    $completa[] = $linea[$i] ?? '';
                }
                $filas[] = $completa;
            }
        }
        return $filas;
    }

    private function colIndex($letras)
    {
        $n = 0;
        $letras = strtoupper($letras);
        $len = strlen($letras);
        for ($i = 0; $i < $len; $i++) {
            $n = $n * 26 + (ord($letras[$i]) - 64);
        }
        return $n - 1;
    }

    private function jsonAuth(): void
    {
        if (empty($_SESSION['user'])) {
            $this->json(array('ok' => false, 'msg' => 'Sesión expirada. Recargue la página.'), 401);
        }
        $claves = array('importar_nomenclador', 'MNU_ARC_NOMENCLADOR');
        foreach (($_SESSION['permisos'] ?? array()) as $p) {
            if (isset($p['CLAVE']) && in_array($p['CLAVE'], $claves, true)) {
                return;
            }
        }
        $this->json(array('ok' => false, 'msg' => 'Sin permiso para importar prácticas.'), 403);
    }

    private function csrf(): void
    {
        $token = (string) ($_POST['csrf_token'] ?? '');
        $esper = (string) ($_SESSION['csrf_token'] ?? '');
        if ($esper === '' || !hash_equals($esper, $token)) {
            $this->json(array('ok' => false, 'msg' => 'Token de seguridad inválido. Recargue la página.'), 403);
        }
    }

    private function usuario()
    {
        $usuario = $_SESSION['usuario'] ?? $_SESSION['user']['username'] ?? 'SIS';
        return trim((string) $usuario);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function json(array $payload, $status = 200): void
    {
        http_response_code((int) $status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
