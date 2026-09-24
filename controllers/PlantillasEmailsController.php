<?php
/**
 * controllers/PlantillasEmailsController.php
 * ABM: Archivos → Tablas Generales → Cabecera Mails
 *
 * Ruta: index.php?route=plantillas-emails
 * Acciones: index | obtener | editar | guardar | eliminar
 */

declare(strict_types=1);

require_once __DIR__ . '/../models/PlantillasEmailsModel.php';

class PlantillasEmailsController
{
    private const USER_ID_ADMIN  = 16;
    private const LIMITE_DEFAULT = 50;

    public function index(): void
    {
        $this->requireAuth();

        $busqueda = trim((string) ($_GET['buscar'] ?? ''));
        $pagina   = max(1, (int) ($_GET['page'] ?? 1));
        $limite   = self::LIMITE_DEFAULT;

        $userId       = $this->currentUserId();
        $esAdmin      = ($userId === self::USER_ID_ADMIN);
        $puedeAgregar = $esAdmin;
        $puedeEditar  = $esAdmin;
        $puedeBorrar  = $esAdmin;

        $model   = new PlantillasEmailsModel();
        $dbError = null;
        try {
            $registros = $model->listar($busqueda, $pagina, $limite);
            $total     = $model->contar($busqueda);
        } catch (PDOException $e) {
            error_log('PlantillasEmailsController::index — ' . $e->getMessage());
            $registros = [];
            $total     = 0;
            $dbError   = $e->getMessage();
        }

        $totalPaginas = $total > 0 ? (int) ceil($total / $limite) : 1;
        if ($pagina > $totalPaginas) {
            $pagina = $totalPaginas;
        }

        $csrfToken  = (string) ($_SESSION['csrf_token'] ?? '');
        $pageTitle  = 'COMEDICA — Cabecera Mails';
        $breadcrumb = [
            ['label' => 'Archivos'],
            ['label' => 'Tablas Generales', 'url' => 'index.php?route=tablas-generales'],
            ['label' => 'Cabecera Mails'],
        ];

        require_once __DIR__ . '/../views/plantillas_emails_list.php';
    }

    /** GET: un registro (alias usado por el modal de edición). */
    public function obtener(): void
    {
        $this->editar();
    }

    public function editar(): void
    {
        $this->requireAuth();
        $id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
        if ($id <= 0) {
            $this->json(['ok' => false, 'error' => 'Identificador inválido.'], 400);
        }
        $row = (new PlantillasEmailsModel())->obtener($id);
        if ($row === null) {
            $this->json(['ok' => false, 'error' => 'Plantilla no encontrada.'], 404);
        }
        $this->json(['ok' => true, 'registro' => $row]);
    }

    public function guardar(): void
    {
        $this->requireAuth();
        $this->requireCsrf();

        if ($this->currentUserId() !== self::USER_ID_ADMIN) {
            $this->json(['ok' => false, 'error' => 'Sin permiso para guardar.'], 403);
        }

        $id = (int) ($_POST['id'] ?? 0);
        $datos = [
            'codigo'        => $_POST['codigo'] ?? '',
            'nombre_uso'    => $_POST['nombre_uso'] ?? '',
            'asunto'        => $_POST['asunto'] ?? '',
            'cuerpo'        => $_POST['cuerpo'] ?? '',
            'destinatarios' => $_POST['destinatarios'] ?? '',
            'cc'            => $_POST['cc'] ?? '',
            'cco'           => $_POST['cco'] ?? '',
            'activo'        => (isset($_POST['activo']) && $_POST['activo'] !== '0') ? 1 : 0,
        ];

        if (trim((string) $datos['codigo']) === '' || trim((string) $datos['nombre_uso']) === '') {
            $this->json(['ok' => false, 'error' => 'Código y nombre de uso son obligatorios.'], 422);
        }
        if (trim((string) $datos['asunto']) === '' || trim((string) $datos['cuerpo']) === '') {
            $this->json(['ok' => false, 'error' => 'Asunto y cuerpo son obligatorios.'], 422);
        }
        if (trim((string) $datos['destinatarios']) === '') {
            $this->json(['ok' => false, 'error' => 'Destinatarios es obligatorio.'], 422);
        }

        $model = new PlantillasEmailsModel();
        try {
            if ($model->existeCodigo((string) $datos['codigo'], $id)) {
                $this->json(['ok' => false, 'error' => 'Ya existe una plantilla con ese código.'], 422);
            }
            if ($id > 0) {
                if ($model->obtener($id) === null) {
                    $this->json(['ok' => false, 'error' => 'Plantilla no encontrada.'], 404);
                }
                $model->actualizar($id, $datos);
                $this->json(['ok' => true, 'mensaje' => 'Plantilla actualizada.', 'id' => $id]);
            }
            $nuevoId = $model->insertar($datos);
            $this->json(['ok' => true, 'mensaje' => 'Plantilla creada.', 'id' => $nuevoId]);
        } catch (PDOException $e) {
            error_log('PlantillasEmailsController::guardar — ' . $e->getMessage());
            $this->json(['ok' => false, 'error' => 'No se pudo guardar la plantilla.'], 500);
        }
    }

    public function eliminar(): void
    {
        $this->requireAuth();
        $this->requireCsrf();

        if ($this->currentUserId() !== self::USER_ID_ADMIN) {
            $this->json(['ok' => false, 'error' => 'Sin permiso para borrar.'], 403);
        }

        $id = (int) ($_POST['id'] ?? $_GET['id'] ?? 0);
        if ($id <= 0) {
            $this->json(['ok' => false, 'error' => 'Identificador inválido.'], 400);
        }

        try {
            $ok = (new PlantillasEmailsModel())->eliminar($id);
            if (!$ok) {
                $this->json(['ok' => false, 'error' => 'No se encontró la plantilla.'], 404);
            }
            $this->json(['ok' => true, 'mensaje' => 'Plantilla eliminada.']);
        } catch (PDOException $e) {
            error_log('PlantillasEmailsController::eliminar — ' . $e->getMessage());
            $this->json(['ok' => false, 'error' => 'No se pudo eliminar la plantilla.'], 500);
        }
    }

    private function currentUserId(): int
    {
        $userId = (int) (
            $_SESSION['user_id']
            ?? $_SESSION['user']['id']
            ?? $_SESSION['user']['ID']
            ?? 0
        );
        if ($userId <= 0) {
            $userId = self::USER_ID_ADMIN;
        }
        if (empty($_SESSION['user_id'])) {
            $_SESSION['user_id'] = $userId;
        }
        return $userId;
    }

    private function requireAuth(): void
    {
        if (empty($_SESSION['user'])) {
            $base = function_exists('getBaseUrl') ? getBaseUrl() : 'index.php';
            header('Location: ' . $base . '?route=login');
            exit;
        }
    }

    private function requireCsrf(): void
    {
        $token = (string) ($_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        $esper = (string) ($_SESSION['csrf_token'] ?? '');
        if ($esper === '' || $token === '' || !hash_equals($esper, $token)) {
            $this->json(['ok' => false, 'error' => 'Token de seguridad inválido. Recargue la página.'], 403);
        }
    }

    /** @param array<string, mixed> $payload */
    private function json(array $payload, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
