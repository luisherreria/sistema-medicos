<?php
/**
 * controllers/PlantillasEmailsController.php
 * Módulo: Tablas generales → Cabecera Mails
 * ABM de `t_plantillas_emails`. Dialecto PHP 5.6.
 *
 * Acceso exclusivo: USER_ID = 16 (rol administrador del sistema)
 * + permiso MNU_ARC_TAB_CABECERA_MAILS vía Permission::tiene().
 */

require_once __DIR__ . '/../models/Permission.php';
require_once __DIR__ . '/../models/PlantillaEmail.php';

class PlantillasEmailsController
{
    const CLAVE_PERMISO     = 'MNU_ARC_TAB_CABECERA_MAILS';
    const USER_ID_EXCLUSIVO = 16;

    public function index()
    {
        $this->requireModulo();

        $user       = $_SESSION['user'];
        $permisos   = isset($_SESSION['permisos']) ? $_SESSION['permisos'] : array();
        $pageTitle  = 'COMEDICA — Cabecera Mails';
        $breadcrumb = array(
            array('label' => 'Tablas generales'),
            array('label' => 'Cabecera Mails'),
        );
        $csrfToken = isset($_SESSION['csrf_token']) ? (string) $_SESSION['csrf_token'] : '';

        require_once __DIR__ . '/../views/plantillas_emails/index.php';
    }

    /**
     * GET ?route=cabecera-mails&action=listado
     */
    public function listado()
    {
        $this->requireModulo();
        header('Content-Type: application/json; charset=utf-8');

        $model = new PlantillaEmail();

        try {
            $busqueda = isset($_GET['busqueda']) ? trim((string) $_GET['busqueda']) : '';
            $activo   = isset($_GET['activo']) ? trim((string) $_GET['activo']) : '';
            $pagina   = isset($_GET['pagina']) ? (int) $_GET['pagina'] : 1;
            $porPag   = isset($_GET['por_pagina']) ? (int) $_GET['por_pagina'] : 200;

            $resultado = $model->listar(array(
                'busqueda'   => $busqueda,
                'activo'     => $activo,
                'pagina'     => $pagina,
                'por_pagina' => $porPag,
            ));

            echo json_encode(array_merge(array('ok' => true), $resultado), JSON_UNESCAPED_UNICODE);
        } catch (PDOException $e) {
            $this->jsonError('Error al consultar plantillas: ' . $e->getMessage());
        }
        exit;
    }

    /**
     * GET ?route=cabecera-mails&action=obtener&id=
     */
    public function obtener()
    {
        $this->requireModulo();
        header('Content-Type: application/json; charset=utf-8');

        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($id <= 0) {
            $this->jsonError('Identificador inválido.');
        }

        try {
            $row = (new PlantillaEmail())->obtener($id);
            if ($row === null) {
                $this->jsonError('Plantilla no encontrada.');
            }
            echo json_encode(array('ok' => true, 'registro' => $row), JSON_UNESCAPED_UNICODE);
        } catch (PDOException $e) {
            $this->jsonError('Error al obtener la plantilla: ' . $e->getMessage());
        }
        exit;
    }

    /**
     * POST ?route=cabecera-mails&action=guardar
     */
    public function guardar()
    {
        $this->requireModulo();
        header('Content-Type: application/json; charset=utf-8');
        $this->requireCsrf();

        $id            = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $codigo        = isset($_POST['codigo']) ? trim((string) $_POST['codigo']) : '';
        $nombreUso     = isset($_POST['nombre_uso']) ? trim((string) $_POST['nombre_uso']) : '';
        $asunto        = isset($_POST['asunto']) ? trim((string) $_POST['asunto']) : '';
        $cuerpo        = isset($_POST['cuerpo']) ? (string) $_POST['cuerpo'] : '';
        $destinatarios = isset($_POST['destinatarios']) ? trim((string) $_POST['destinatarios']) : '';
        $activo        = isset($_POST['activo']) && (string) $_POST['activo'] !== '0' && (string) $_POST['activo'] !== '';

        if ($codigo === '') {
            $this->jsonError('El código es obligatorio.');
        }
        if ($nombreUso === '') {
            $this->jsonError('El nombre de uso es obligatorio.');
        }
        if ($asunto === '') {
            $this->jsonError('El asunto es obligatorio.');
        }

        $datos = array(
            'codigo'        => $codigo,
            'nombre_uso'    => $nombreUso,
            'asunto'        => $asunto,
            'cuerpo'        => $cuerpo,
            'destinatarios' => $destinatarios,
            'activo'        => $activo,
        );

        $model = new PlantillaEmail();

        try {
            if ($model->existeCodigo($codigo, $id)) {
                $this->jsonError('Ya existe una plantilla con ese código.');
            }

            if ($id > 0) {
                $existente = $model->obtener($id);
                if ($existente === null) {
                    $this->jsonError('Plantilla no encontrada.');
                }
                $model->actualizar($id, $datos);
                echo json_encode(array('ok' => true, 'msg' => 'Plantilla actualizada correctamente.', 'id' => $id), JSON_UNESCAPED_UNICODE);
                exit;
            }

            $nuevoId = $model->insertar($datos);
            echo json_encode(array('ok' => true, 'msg' => 'Plantilla creada correctamente.', 'id' => $nuevoId), JSON_UNESCAPED_UNICODE);
        } catch (PDOException $e) {
            $this->jsonError('Error al guardar la plantilla: ' . $e->getMessage());
        }
        exit;
    }

    /**
     * POST ?route=cabecera-mails&action=eliminar
     */
    public function eliminar()
    {
        $this->requireModulo();
        header('Content-Type: application/json; charset=utf-8');
        $this->requireCsrf();

        $id = 0;
        if (isset($_POST['id'])) {
            $id = (int) $_POST['id'];
        } elseif (isset($_GET['id'])) {
            $id = (int) $_GET['id'];
        }
        if ($id <= 0) {
            $this->jsonError('Identificador inválido.');
        }

        try {
            $ok = (new PlantillaEmail())->eliminar($id);
            if (!$ok) {
                $this->jsonError('No se encontró la plantilla o ya fue eliminada.');
            }
            echo json_encode(array('ok' => true, 'msg' => 'Plantilla eliminada correctamente.'), JSON_UNESCAPED_UNICODE);
        } catch (PDOException $e) {
            $this->jsonError('Error al eliminar la plantilla: ' . $e->getMessage());
        }
        exit;
    }

    private function requireModulo()
    {
        $this->requireAuth();
        $this->requireUsuarioExclusivo();
        $this->requirePermiso(self::CLAVE_PERMISO);
    }

    private function requireAuth()
    {
        if (empty($_SESSION['user'])) {
            if ($this->isAjax()) {
                $this->jsonError('Sesión expirada. Recargue la página.', 401);
            }
            header('Location: ' . $this->baseUrl() . '?route=login');
            exit;
        }
    }

    private function requireUsuarioExclusivo()
    {
        $userId = isset($_SESSION['user']['id']) ? (int) $_SESSION['user']['id'] : 0;
        $idRol  = isset($_SESSION['user']['id_rol']) ? (int) $_SESSION['user']['id_rol'] : 0;

        if ($userId === self::USER_ID_EXCLUSIVO || $idRol === self::USER_ID_EXCLUSIVO) {
            return;
        }

        if ($this->isAjax()) {
            $this->jsonError('Este módulo está reservado al rol 16.', 403);
        }
        $_SESSION['flash_warning'] = 'No tiene permiso para acceder a Cabecera Mails.';
        header('Location: ' . $this->baseUrl() . '?route=dashboard');
        exit;
    }

    /**
     * @param string $clave
     */
    private function requirePermiso($clave)
    {
        $permisos = isset($_SESSION['permisos']) ? $_SESSION['permisos'] : array();
        if (Permission::tiene($permisos, $clave)) {
            return;
        }

        if ($this->isAjax()) {
            $this->jsonError('Sin permiso para esta operación.', 403);
        }
        $_SESSION['flash_warning'] = 'No tiene permiso para acceder al módulo solicitado.';
        header('Location: ' . $this->baseUrl() . '?route=dashboard');
        exit;
    }

    private function requireCsrf()
    {
        $token = '';
        if (isset($_POST['csrf_token'])) {
            $token = (string) $_POST['csrf_token'];
        } elseif (isset($_SERVER['HTTP_X_CSRF_TOKEN'])) {
            $token = (string) $_SERVER['HTTP_X_CSRF_TOKEN'];
        }
        $esper = isset($_SESSION['csrf_token']) ? (string) $_SESSION['csrf_token'] : '';
        if ($esper === '' || $token === '' || !hash_equals($esper, $token)) {
            $this->jsonError('Token de seguridad inválido. Recargue la página.', 403);
        }
    }

    /**
     * @return bool
     */
    private function isAjax()
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower((string) $_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * @param string $msg
     * @param int    $code
     */
    private function jsonError($msg, $code = 200)
    {
        if ((int) $code !== 200) {
            http_response_code((int) $code);
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(array('ok' => false, 'error' => $msg), JSON_UNESCAPED_UNICODE);
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
        $https  = isset($_SERVER['HTTPS']) ? $_SERVER['HTTPS'] : '';
        $scheme = (!empty($https) && $https !== 'off') ? 'https' : 'http';
        $host   = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
        $scriptName = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '';
        $script = rtrim((string) dirname($scriptName), '/\\');
        return $scheme . '://' . $host . $script . '/index.php';
    }
}
