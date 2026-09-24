<?php
/**
 * controllers/PlantillasEmailsController.php
 * Módulo: Tablas generales → Cabecera Mails
 * ABM de `t_plantillas_emails`.
 *
 * Acceso exclusivo: USER_ID = 16 (rol administrador del sistema)
 * + permiso MNU_ARC_TAB_CABECERA_MAILS vía Permission::tiene().
 *
 * Acciones:
 *   index()    GET  ?route=cabecera-mails
 *   listado()  GET  ?action=listado
 *   obtener()  GET  ?action=obtener&id=
 *   guardar()  POST ?action=guardar
 *   eliminar() POST ?action=eliminar
 */

declare(strict_types=1);

require_once __DIR__ . '/../models/Permission.php';
require_once __DIR__ . '/../models/PlantillaEmail.php';

class PlantillasEmailsController
{
    private const CLAVE_PERMISO     = 'MNU_ARC_TAB_CABECERA_MAILS';
    private const USER_ID_EXCLUSIVO = 16;

    public function index(): void
    {
        $this->requireModulo();

        $user       = $_SESSION['user'];
        $permisos   = $_SESSION['permisos'] ?? [];
        $pageTitle  = 'COMEDICA — Cabecera Mails';
        $breadcrumb = [
            ['label' => 'Tablas generales'],
            ['label' => 'Cabecera Mails'],
        ];
        $csrfToken = (string) ($_SESSION['csrf_token'] ?? '');

        require_once __DIR__ . '/../views/plantillas_emails/index.php';
    }

    /**
     * GET ?route=cabecera-mails&action=listado
     */
    public function listado(): void
    {
        $this->requireModulo();
        header('Content-Type: application/json; charset=utf-8');

        $model = new PlantillaEmail();

        try {
            $resultado = $model->listar([
                'busqueda'   => trim((string) ($_GET['busqueda'] ?? '')),
                'activo'     => trim((string) ($_GET['activo'] ?? '')),
                'pagina'     => (int) ($_GET['pagina'] ?? 1),
                'por_pagina' => (int) ($_GET['por_pagina'] ?? 200),
            ]);

            echo json_encode(['ok' => true] + $resultado, JSON_UNESCAPED_UNICODE);
        } catch (PDOException $e) {
            $this->jsonError('Error al consultar plantillas: ' . $e->getMessage());
        }
        exit;
    }

    /**
     * GET ?route=cabecera-mails&action=obtener&id=
     */
    public function obtener(): void
    {
        $this->requireModulo();
        header('Content-Type: application/json; charset=utf-8');

        $id = (int) ($_GET['id'] ?? 0);
        if ($id <= 0) {
            $this->jsonError('Identificador inválido.');
        }

        try {
            $row = (new PlantillaEmail())->obtener($id);
            if ($row === null) {
                $this->jsonError('Plantilla no encontrada.');
            }
            echo json_encode(['ok' => true, 'registro' => $row], JSON_UNESCAPED_UNICODE);
        } catch (PDOException $e) {
            $this->jsonError('Error al obtener la plantilla: ' . $e->getMessage());
        }
        exit;
    }

    /**
     * POST ?route=cabecera-mails&action=guardar
     */
    public function guardar(): void
    {
        $this->requireModulo();
        header('Content-Type: application/json; charset=utf-8');
        $this->requireCsrf();

        $id           = (int) ($_POST['id'] ?? 0);
        $codigo       = trim((string) ($_POST['codigo'] ?? ''));
        $nombreUso    = trim((string) ($_POST['nombre_uso'] ?? ''));
        $asunto       = trim((string) ($_POST['asunto'] ?? ''));
        $cuerpo       = (string) ($_POST['cuerpo'] ?? '');
        $destinatarios = trim((string) ($_POST['destinatarios'] ?? ''));
        $activo       = isset($_POST['activo']) && (string) $_POST['activo'] !== '0' && (string) $_POST['activo'] !== '';

        if ($codigo === '') {
            $this->jsonError('El código es obligatorio.');
        }
        if ($nombreUso === '') {
            $this->jsonError('El nombre de uso es obligatorio.');
        }
        if ($asunto === '') {
            $this->jsonError('El asunto es obligatorio.');
        }

        $datos = [
            'codigo'        => $codigo,
            'nombre_uso'    => $nombreUso,
            'asunto'        => $asunto,
            'cuerpo'        => $cuerpo,
            'destinatarios' => $destinatarios,
            'activo'        => $activo,
        ];

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
                echo json_encode(['ok' => true, 'msg' => 'Plantilla actualizada correctamente.', 'id' => $id], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $nuevoId = $model->insertar($datos);
            echo json_encode(['ok' => true, 'msg' => 'Plantilla creada correctamente.', 'id' => $nuevoId], JSON_UNESCAPED_UNICODE);
        } catch (PDOException $e) {
            $this->jsonError('Error al guardar la plantilla: ' . $e->getMessage());
        }
        exit;
    }

    /**
     * POST ?route=cabecera-mails&action=eliminar
     */
    public function eliminar(): void
    {
        $this->requireModulo();
        header('Content-Type: application/json; charset=utf-8');
        $this->requireCsrf();

        $id = (int) ($_POST['id'] ?? $_GET['id'] ?? 0);
        if ($id <= 0) {
            $this->jsonError('Identificador inválido.');
        }

        try {
            $ok = (new PlantillaEmail())->eliminar($id);
            if (!$ok) {
                $this->jsonError('No se encontró la plantilla o ya fue eliminada.');
            }
            echo json_encode(['ok' => true, 'msg' => 'Plantilla eliminada correctamente.'], JSON_UNESCAPED_UNICODE);
        } catch (PDOException $e) {
            $this->jsonError('Error al eliminar la plantilla: ' . $e->getMessage());
        }
        exit;
    }

    // ── Guards ────────────────────────────────────────────────────────────

    /**
     * Autenticación + permiso del módulo + candado exclusivo a USER_ID = 16.
     * La validación de permiso usa Permission::tiene() (función existente).
     */
    private function requireModulo(): void
    {
        $this->requireAuth();
        $this->requireUsuarioExclusivo();
        $this->requirePermiso(self::CLAVE_PERMISO);
    }

    private function requireAuth(): void
    {
        if (empty($_SESSION['user'])) {
            if ($this->isAjax()) {
                $this->jsonError('Sesión expirada. Recargue la página.', 401);
            }
            header('Location: ' . $this->baseUrl() . '?route=login');
            exit;
        }
    }

    private function requireUsuarioExclusivo(): void
    {
        $userId = (int) ($_SESSION['user']['id'] ?? 0);
        $idRol  = (int) ($_SESSION['user']['id_rol'] ?? 0);

        // Candado exclusivo: usuario 16 y/o rol 16 (admin del sistema).
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

    private function requirePermiso(string $clave): void
    {
        $permisos = $_SESSION['permisos'] ?? [];
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

    private function requireCsrf(): void
    {
        $token = (string) ($_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        $esper = (string) ($_SESSION['csrf_token'] ?? '');
        if ($esper === '' || $token === '' || !hash_equals($esper, $token)) {
            $this->jsonError('Token de seguridad inválido. Recargue la página.', 403);
        }
    }

    private function isAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower((string) $_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    private function jsonError(string $msg, int $code = 200): void
    {
        if ($code !== 200) {
            http_response_code($code);
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'error' => $msg], JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function baseUrl(): string
    {
        if (function_exists('getBaseUrl')) {
            return getBaseUrl();
        }
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $script = rtrim((string) dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
        return "{$scheme}://{$host}{$script}/index.php";
    }
}
