<?php
/**
 * controllers/ObrasSocialesController.php
 * Módulo Archivos → Obras Sociales (cartilla.obrasoc).
 *
 * Rutas:
 *   index.php?route=obras_sociales
 *   index.php?route=obras-sociales          (menú MNU_ARC_OBRAS_SOCIALES)
 *   index.php?route=MNU_ARC_OBRAS_SOCIALES
 *
 * Acciones:
 *   listar   GET  JSON  incluir_inactivas=0|1 (default 0), busqueda opcional
 *   obtener  GET  JSON  codigo
 *   guardar   POST JSON  alta o edición de datos generales
 *   cierre    POST JSON  cierre de período (TAFECHAFIN)
 *   eliminar       POST JSON  borra la obra social, sus asignaciones y deja auditoría
 *   subir_archivo  POST JSON  guarda un archivo de coseguro o norma en public/uploads/obrassoc
 */

declare(strict_types=1);

require_once __DIR__ . '/../models/ObraSocialModel.php';
require_once __DIR__ . '/../models/PadronImportModel.php';

class ObrasSocialesController
{
    /**
     * Clave real en vfpmedicos.permisos: MNU_ARC_OBRASSOCIALES.
     * Se acepta también el alias con guion bajo usado en el mapa de menú.
     */
    private const CLAVES_MODULO = ['MNU_ARC_OBRASSOCIALES', 'MNU_ARC_OBRAS_SOCIALES'];

    /**
     * Pantalla principal.
     */
    public function index()
    {
        $this->autorizar(false);

        $pageTitle  = 'COMEDICA — Obras Sociales';
        $breadcrumb = [
            ['label' => 'Archivos', 'url' => 'index.php?route=dashboard'],
            ['label' => 'Obras Sociales'],
        ];
        $csrfToken = (string) ($_SESSION['csrf_token'] ?? '');

        require_once __DIR__ . '/../views/obras_sociales/index.php';
    }

    /**
     * GET ?route=obras_sociales&action=listar&incluir_inactivas=0|1
     */
    public function listar()
    {
        $this->autorizar(true);
        $this->jsonHeader();

        $mostrarInactivas = $this->flagIncluirInactivas();
        $busqueda         = trim((string) ($_GET['busqueda'] ?? ''));

        try {
            $model = new ObraSocialModel();
            $data  = $model->getObrasSociales($mostrarInactivas, $busqueda);
            $this->jsonOut([
                'ok'                 => true,
                'incluir_inactivas'  => $mostrarInactivas,
                'data'               => $data,
            ]);
        } catch (PDOException $e) {
            error_log('ObrasSocialesController::listar — ' . $e->getMessage());
            $this->jsonOut(['ok' => false, 'msg' => 'No se pudo consultar el catálogo de obras sociales.'], 500);
        }
    }

    /**
     * GET ?route=obras_sociales&action=obtener&codigo=
     */
    public function obtener()
    {
        $this->autorizar(true);
        $this->jsonHeader();

        $codigo = trim((string) ($_GET['codigo'] ?? ''));
        if ($codigo === '') {
            $this->jsonOut(['ok' => false, 'msg' => 'Falta el código de la obra social.'], 422);
        }

        try {
            $fila = (new ObraSocialModel())->getObraSocialByCodigo($codigo);
            if ($fila === null) {
                $this->jsonOut(['ok' => false, 'msg' => 'Obra social no encontrada.'], 404);
            }
            $this->jsonOut(['ok' => true, 'data' => $fila]);
        } catch (PDOException $e) {
            error_log('ObrasSocialesController::obtener — ' . $e->getMessage());
            $this->jsonOut(['ok' => false, 'msg' => 'No se pudo leer la obra social.'], 500);
        }
    }

    /**
     * POST ?route=obras_sociales&action=guardar
     * modo=alta|edicion
     */
    public function guardar()
    {
        $this->autorizar(true);
        $this->jsonHeader();
        $this->exigirPost();
        $this->validarCsrf();

        $modo = strtolower(trim((string) ($_POST['modo'] ?? 'alta')));

        try {
            $model   = new ObraSocialModel();
            $usuario = $this->usuarioSesion();
            $guardado = $model->guardarObraSocial($_POST, $usuario);

            $this->jsonOut([
                'ok'     => true,
                'msg'    => $modo === 'edicion'
                    ? 'Datos de la obra social actualizados.'
                    : 'Obra social agregada.',
                'codigo' => $guardado,
            ]);
        } catch (InvalidArgumentException $e) {
            $this->jsonOut(['ok' => false, 'msg' => $e->getMessage()], 422);
        } catch (PDOException $e) {
            error_log('ObrasSocialesController::guardar — ' . $e->getMessage());
            $this->jsonOut(['ok' => false, 'msg' => 'No se pudo guardar la obra social.'], 500);
        }
    }

    /**
     * POST ?route=obras_sociales&action=cierre
     * fecha (Y-m-d), codigo opcional, aplicar_todas=1 para el universo vigente.
     */
    public function cierre()
    {
        $this->autorizar(true);
        $this->jsonHeader();
        $this->exigirPost();
        $this->validarCsrf();

        $fecha        = trim((string) ($_POST['fecha'] ?? ''));
        $codigo       = trim((string) ($_POST['codigo'] ?? ''));
        $aplicarTodas = $this->flagRequest('aplicar_todas');

        try {
            $n = (new ObraSocialModel())->cerrarPeriodo(
                $fecha,
                $codigo,
                $aplicarTodas,
                $this->usuarioSesion()
            );
            $this->jsonOut([
                'ok'        => true,
                'msg'       => $n > 0
                    ? 'Cierre de período registrado (' . $n . ' obra' . ($n === 1 ? '' : 's') . ' social' . ($n === 1 ? '' : 'es') . ').'
                    : 'No hubo obras sociales para actualizar.',
                'afectadas' => $n,
            ]);
        } catch (InvalidArgumentException $e) {
            $this->jsonOut(['ok' => false, 'msg' => $e->getMessage()], 422);
        } catch (PDOException $e) {
            error_log('ObrasSocialesController::cierre — ' . $e->getMessage());
            $this->jsonOut(['ok' => false, 'msg' => 'No se pudo registrar el cierre de período.'], 500);
        }
    }

    /**
     * POST ?route=obras_sociales&action=eliminar
     * codigo. Borra cartilla.obrasoc y sus asignaciones en obramed y prestac.
     */
    public function eliminar()
    {
        $this->autorizar(true);
        $this->jsonHeader();
        $this->exigirPost();
        $this->validarCsrf();

        $codigo = trim((string) ($_POST['codigo'] ?? ''));
        $res = (new ObraSocialModel())->eliminarObraSocial($codigo, $this->usuarioSesion());

        if (empty($res['ok'])) {
            $msg = (string) ($res['error'] ?? 'No se pudo eliminar la obra social.');
            $this->jsonOut(['ok' => false, 'error' => $msg, 'msg' => $msg], 422);
        }

        $this->jsonOut([
            'ok'                 => true,
            'msg'                => (string) ($res['msg'] ?? 'Obra social eliminada.'),
            'obramed_afectados'  => (int) ($res['obramed_afectados'] ?? 0),
            'prestac_afectados'  => (int) ($res['prestac_afectados'] ?? 0),
        ]);
    }

    /**
     * POST ?route=obras_sociales&action=subir_archivo
     * tipo=coseg|norma, archivo, codigo opcional.
     * Deja el archivo en public/uploads/obrassoc y devuelve el nombre guardado.
     */
    public function subirArchivo()
    {
        $this->autorizar(true);
        $this->jsonHeader();
        $this->exigirPost();
        $this->validarCsrf();

        $tipo = strtolower(trim((string) ($_POST['tipo'] ?? '')));
        if ($tipo !== 'coseg' && $tipo !== 'norma') {
            $this->jsonOut(['ok' => false, 'msg' => 'Tipo de archivo no reconocido.'], 422);
        }

        if (empty($_FILES['archivo']) || !is_array($_FILES['archivo'])) {
            $this->jsonOut(['ok' => false, 'msg' => 'No se recibió ningún archivo.'], 422);
        }

        $file = $_FILES['archivo'];
        if ((int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $this->jsonOut(['ok' => false, 'msg' => 'No se pudo recibir el archivo.'], 422);
        }

        $tmp = (string) ($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            $this->jsonOut(['ok' => false, 'msg' => 'El archivo recibido no es válido.'], 422);
        }

        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0 || $size > 20 * 1024 * 1024) {
            $this->jsonOut(['ok' => false, 'msg' => 'El archivo debe pesar hasta 20 MB.'], 422);
        }

        $original = str_replace(["\0", '/', '\\'], '', (string) ($file['name'] ?? ''));
        $original = basename($original);
        $ext = strtolower((string) pathinfo($original, PATHINFO_EXTENSION));
        $permitidas = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'tif', 'tiff', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'txt', 'zip'];
        if ($ext === '' || !in_array($ext, $permitidas, true)) {
            $this->jsonOut(['ok' => false, 'msg' => 'Tipo de archivo no permitido.'], 422);
        }

        $stem = trim((string) preg_replace('/[^A-Za-z0-9._-]+/', '_', (string) pathinfo($original, PATHINFO_FILENAME)), '._-');
        if ($stem === '') {
            $stem = 'archivo';
        }

        $codigo = strtoupper(trim((string) ($_POST['codigo'] ?? '')));
        $codigo = trim((string) preg_replace('/[^A-Z0-9]+/', '_', $codigo), '_');
        $limite = $tipo === 'norma' ? 150 : 100;
        $prefijo = $codigo !== '' ? $codigo . '_' : date('YmdHis') . '_';
        $nombre = $this->nombreArchivoRuta($prefijo, $stem, $ext, $limite);

        $dir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'obrassoc';
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            $this->jsonOut(['ok' => false, 'msg' => 'No se pudo crear la carpeta de archivos.'], 500);
        }

        $destino = $dir . DIRECTORY_SEPARATOR . $nombre;
        if (is_file($destino)) {
            $nombre = $this->nombreArchivoRuta($prefijo, $stem . '_' . date('His'), $ext, $limite);
            $destino = $dir . DIRECTORY_SEPARATOR . $nombre;
        }

        $realDir = realpath($dir);
        if ($realDir === false || !@move_uploaded_file($tmp, $destino)) {
            $this->jsonOut(['ok' => false, 'msg' => 'No se pudo guardar el archivo.'], 500);
        }

        $this->jsonOut([
            'ok'     => true,
            'msg'    => 'Archivo guardado.',
            'nombre' => $nombre,
        ]);
    }

    /**
     * Arma un nombre que entra en DIRCOSEG (100) o DIRNORMA (150).
     *
     * @param string $prefijo
     * @param string $stem
     * @param string $ext
     * @param int    $limite
     * @return string
     */
    private function nombreArchivoRuta($prefijo, $stem, $ext, $limite)
    {
        $suffix = '.' . $ext;
        $maxStem = (int) $limite - strlen($prefijo) - strlen($suffix);
        if ($maxStem < 1) {
            $prefijo = '';
            $maxStem = (int) $limite - strlen($suffix);
        }
        if (strlen($stem) > $maxStem) {
            $stem = substr($stem, 0, max(1, $maxStem));
        }
        $stem = rtrim($stem, '._-');
        if ($stem === '') {
            $stem = 'archivo';
        }
        return $prefijo . $stem . $suffix;
    }

    /**
     * POST ?route=obras_sociales&action=previsualizar_padron
     * Lee el archivo y devuelve activos, bajas y las primeras 10 filas.
     */
    public function previsualizarPadron()
    {
        $this->autorizar(true);
        $this->jsonHeader();
        $this->exigirPost();
        $this->validarCsrf();

        $archivo = null;
        try {
            $datos = $this->datosPadron();
            $archivo = $this->recibirArchivoPadron();
            $resumen = (new PadronImportModel())->previsualizar(
                $archivo['path'],
                $archivo['nombre'],
                $datos['obrasoc'],
                $datos['npadron'],
                $datos['periodo']
            );
            $this->olvidarImportacionPrevia();
            $_SESSION['padron_import'] = array(
                'path'    => $archivo['path'],
                'nombre'  => $archivo['nombre'],
                'obrasoc' => $datos['obrasoc'],
                'npadron' => $datos['npadron'],
                'periodo' => $datos['periodo'],
                'fbaj'    => $datos['fbaj'],
            );
            $this->jsonOut(array(
                'ok'      => true,
                'msg'     => $resumen['msg'],
                'activos' => $resumen['activos'],
                'bajas'   => $resumen['bajas'],
                'leidos'  => $resumen['leidos'],
                'periodo' => $resumen['periodo'],
                'preview' => $resumen['preview'],
            ));
        } catch (InvalidArgumentException $e) {
            $this->descartarArchivoSuelt($archivo);
            $this->jsonOut(array('ok' => false, 'msg' => $e->getMessage()), 422);
        } catch (Exception $e) {
            $this->descartarArchivoSuelt($archivo);
            error_log('ObrasSocialesController::previsualizarPadron — ' . $e->getMessage());
            $this->jsonOut(array('ok' => false, 'msg' => 'No se pudo leer el padrón.'), 500);
        }
    }

    /**
     * POST ?route=obras_sociales&action=confirmar_padron
     * Vuelca los activos de la prelectura a vfpmedicos.padron_activos.
     */
    public function confirmarPadron()
    {
        $this->autorizar(true);
        $this->jsonHeader();
        $this->exigirPost();
        $this->validarCsrf();

        $previo = isset($_SESSION['padron_import']) && is_array($_SESSION['padron_import'])
            ? $_SESSION['padron_import']
            : null;
        if (!$previo || empty($previo['path']) || !is_file($previo['path'])) {
            $this->jsonOut(array('ok' => false, 'msg' => 'No hay una prelectura vigente. Vuelva a soltar el archivo.'), 422);
        }

        try {
            $datos = $this->datosPadron();
            if ($datos['obrasoc'] !== $previo['obrasoc'] || $datos['npadron'] !== $previo['npadron'] || $datos['periodo'] !== $previo['periodo']) {
                $this->jsonOut(array('ok' => false, 'msg' => 'Cambiaron los datos del formulario. Vuelva a leer el archivo.'), 422);
            }
            $res = (new PadronImportModel())->confirmar(
                $previo['path'],
                $previo['nombre'],
                $datos['obrasoc'],
                $datos['npadron'],
                $datos['periodo'],
                $datos['fbaj'],
                $this->usuarioSesion()
            );
            $this->olvidarImportacionPrevia();
            $this->jsonOut(array(
                'ok'  => true,
                'msg' => 'Se importaron ' . $res['insertados'] . ' afiliados activos de ' . $res['obrasoc'] . ' para el período ' . $res['periodo'] . '.',
                'insertados' => $res['insertados'],
            ));
        } catch (InvalidArgumentException $e) {
            $this->jsonOut(array('ok' => false, 'msg' => $e->getMessage()), 422);
        } catch (Exception $e) {
            error_log('ObrasSocialesController::confirmarPadron — ' . $e->getMessage());
            $this->jsonOut(array('ok' => false, 'msg' => 'No se pudo importar el padrón.'), 500);
        }
    }

    /**
     * @return array{obrasoc:string,npadron:string,periodo:string,fbaj:string}
     */
    private function datosPadron()
    {
        $obrasoc = strtoupper(trim((string) ($_POST['obrasoc'] ?? '')));
        $npadron = trim((string) ($_POST['npadron'] ?? ''));
        $periodo = trim((string) ($_POST['periodo'] ?? ''));
        $fbaj = trim((string) ($_POST['fbaj'] ?? ''));

        if ($obrasoc === '' || !(new ObraSocialModel())->existe($obrasoc)) {
            throw new InvalidArgumentException('Seleccione una obra social válida.');
        }
        if ($npadron === '') {
            throw new InvalidArgumentException('Indique el nombre del padrón.');
        }
        if (strlen($npadron) > 80) {
            throw new InvalidArgumentException('El nombre del padrón admite hasta 80 caracteres.');
        }
        if (!preg_match('/^\d{2}\/\d{2}$/', $periodo)) {
            throw new InvalidArgumentException('El período debe tener el formato AA/MM.');
        }
        $aa = (int) substr($periodo, 0, 2);
        $mm = (int) substr($periodo, 3, 2);
        $maxAa = ((int) date('Y') + 1) % 100;
        if ($mm < 1 || $mm > 12) {
            throw new InvalidArgumentException('El mes del período debe estar entre 01 y 12.');
        }
        if ($aa > $maxAa) {
            throw new InvalidArgumentException('El año del período no puede ser posterior a ' . sprintf('%02d', $maxAa) . '.');
        }
        if ($fbaj !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fbaj)) {
            throw new InvalidArgumentException('La fecha de baja no es válida.');
        }

        return array(
            'obrasoc' => substr($obrasoc, 0, 10),
            'npadron' => $npadron,
            'periodo' => $periodo,
            'fbaj'    => $fbaj,
        );
    }

    /**
     * @return array{path:string,nombre:string}
     */
    private function recibirArchivoPadron()
    {
        if (empty($_FILES['archivo']) || !is_array($_FILES['archivo'])) {
            throw new InvalidArgumentException('Seleccione o arrastre un archivo de padrón.');
        }
        $file = $_FILES['archivo'];
        if ((int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new InvalidArgumentException('No se pudo recibir el archivo.');
        }
        $tmp = (string) ($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            throw new InvalidArgumentException('El archivo recibido no es válido.');
        }
        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0 || $size > 64 * 1024 * 1024) {
            throw new InvalidArgumentException('El archivo debe pesar hasta 64 MB.');
        }
        $original = basename(str_replace(array("\0", '/', '\\'), '', (string) ($file['name'] ?? '')));
        $ext = strtolower((string) pathinfo($original, PATHINFO_EXTENSION));
        if (!in_array($ext, array('dbf', 'xlsx', 'xls', 'csv', 'txt'), true)) {
            throw new InvalidArgumentException('Formato no admitido. Use DBF, XLSX, XLS, CSV o TXT.');
        }
        $dir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'padron_import';
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException('No se pudo crear la carpeta temporal.');
        }
        $dest = $dir . DIRECTORY_SEPARATOR . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        if (!@move_uploaded_file($tmp, $dest)) {
            throw new RuntimeException('No se pudo guardar el archivo temporal.');
        }
        return array('path' => $dest, 'nombre' => $original);
    }

    /**
     * @param array<string, string>|null $archivo
     */
    private function descartarArchivoSuelt($archivo)
    {
        if (!is_array($archivo) || empty($archivo['path']) || !is_file($archivo['path'])) {
            return;
        }
        $guardado = isset($_SESSION['padron_import']['path']) ? $_SESSION['padron_import']['path'] : '';
        if ($guardado !== $archivo['path']) {
            @unlink($archivo['path']);
        }
    }

    private function olvidarImportacionPrevia()
    {
        if (!empty($_SESSION['padron_import']['path']) && is_file($_SESSION['padron_import']['path'])) {
            @unlink($_SESSION['padron_import']['path']);
        }
        unset($_SESSION['padron_import']);
    }

    /**
     * incluir_inactivas ausente o falso → solo vigentes.
     */
    private function flagIncluirInactivas()
    {
        return $this->flagRequest('incluir_inactivas');
    }

    /**
     * @param string $nombre
     * @return bool
     */
    private function flagRequest($nombre)
    {
        if (!isset($_REQUEST[$nombre])) {
            return false;
        }
        $valor = $_REQUEST[$nombre];
        if (is_bool($valor)) {
            return $valor;
        }
        $s = strtolower(trim((string) $valor));
        return in_array($s, ['1', 'true', 'on', 'si', 'sí', 'yes'], true);
    }

    /**
     * @param bool $json
     */
    private function autorizar($json)
    {
        if (empty($_SESSION['user'])) {
            if ($json) {
                $this->jsonHeader();
                $this->jsonOut(['ok' => false, 'msg' => 'Sesión expirada. Recargue la página.'], 401);
            }
            header('Location: ' . $this->baseUrl() . '?route=login');
            exit;
        }

        $userId = (int) ($_SESSION['user_id'] ?? $_SESSION['user']['id'] ?? 0);
        $model  = new ObraSocialModel();
        $ok     = false;
        foreach (self::CLAVES_MODULO as $clave) {
            if ($model->tienePermiso($userId, $clave) || $this->tienePermisoSesion($clave)) {
                $ok = true;
                break;
            }
        }

        if ($ok) {
            return;
        }

        if ($json) {
            $this->jsonHeader();
            $this->jsonOut(['ok' => false, 'msg' => 'Sin permiso para Obras Sociales.'], 403);
        }

        $_SESSION['flash_warning'] = 'No tiene permiso para acceder a Obras Sociales.';
        header('Location: ' . $this->baseUrl() . '?route=dashboard');
        exit;
    }

    /**
     * @param string $clave
     * @return bool
     */
    private function tienePermisoSesion($clave)
    {
        foreach (($_SESSION['permisos'] ?? []) as $p) {
            if (isset($p['CLAVE']) && $p['CLAVE'] === $clave) {
                return true;
            }
        }
        return false;
    }

    private function exigirPost()
    {
        if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->jsonOut(['ok' => false, 'msg' => 'Método no permitido.'], 405);
        }
    }

    private function validarCsrf()
    {
        $token = (string) ($_POST['csrf_token'] ?? '');
        $esper = (string) ($_SESSION['csrf_token'] ?? '');
        if ($esper === '' || !hash_equals($esper, $token)) {
            $this->jsonOut(['ok' => false, 'msg' => 'Token de seguridad inválido. Recargue la página.'], 403);
        }
    }

    /**
     * @return string
     */
    private function usuarioSesion()
    {
        $user = $_SESSION['user'] ?? [];
        $nombre = trim((string) ($user['username'] ?? $user['full_name'] ?? ''));
        if ($nombre === '') {
            $nombre = 'WEB';
        }
        return $nombre;
    }

    private function jsonHeader()
    {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }
    }

    /**
     * @param array<string, mixed> $payload
     * @param int                  $code
     */
    private function jsonOut(array $payload, $code = 200)
    {
        if (!headers_sent()) {
            http_response_code((int) $code);
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * @return string
     */
    private function baseUrl()
    {
        if (function_exists('getBaseUrl')) {
            return getBaseUrl();
        }
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $script = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
        return "{$scheme}://{$host}{$script}/index.php";
    }
}
