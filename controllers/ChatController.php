<?php
/**
 * controllers/ChatController.php
 * Módulo: Chat de Telegram — Interfaz de mensajería para operadores.
 *
 * Acciones disponibles via ?route=chat&action=X :
 *   (vacío)   GET  → index()         Carga la vista principal del chat.
 *   messages  GET  → getMessages()   Devuelve mensajes de un chat (AJAX).
 *   unread    GET  → getUnreadCount() Devuelve contador de no leídos (AJAX).
 *   send      POST → sendMessage()   Envía un mensaje de texto o archivo.
 */

require_once __DIR__ . '/../models/TelegramMessage.php';

class ChatController
{
    // ── Credenciales del Bot de Telegram ────────────────────────────────────
    private const BOT_TOKEN    = '8973344719:AAGaLHqO7ADz2WqaMDBZhP_kAIFClYoVa0M';
    private const TELEGRAM_API = 'https://api.telegram.org/bot';

    // ── Directorio local para guardar archivos adjuntos ──────────────────────
    private const UPLOADS_DIR = __DIR__ . '/../public/uploads/telegram/';
    private const UPLOADS_URL = '/sistema-medicos/public/uploads/telegram/';

    // ════════════════════════════════════════════════════════════════════════
    //  ACCIONES PRINCIPALES
    // ════════════════════════════════════════════════════════════════════════

    /**
     * Carga la vista principal del chat.
     * GET ?route=chat
     */
    public function index(): void
    {
        $this->requireAuth();
        $this->requirePermiso('MNU_CHAT_TELEGRAM');

        $user       = $_SESSION['user'];
        $permisos   = $_SESSION['permisos'] ?? [];
        $pageTitle  = 'COMEDICA — Chat Telegram';
        $breadcrumb = [['label' => 'Chat Telegram']];

        $model = new TelegramMessage();
        $chats = $model->getChats();

        require_once __DIR__ . '/../views/layouts/header.php';
        require_once __DIR__ . '/../views/chat/index.php';
        require_once __DIR__ . '/../views/layouts/footer.php';
    }

    // ────────────────────────────────────────────────────────────────────────

    /**
     * AJAX: Devuelve los mensajes de una conversación.
     * Opcionalmente solo los mensajes desde un ID (polling incremental).
     * Marca los mensajes IN como leídos al abrir.
     *
     * GET ?route=chat&action=messages&chat_id=X[&after_id=Y]
     */
    public function getMessages(): void
    {
        $this->requireAuth();
        $this->requirePermiso('MNU_CHAT_TELEGRAM');

        $chatId  = (int) ($_GET['chat_id']  ?? 0);
        $afterId = (int) ($_GET['after_id'] ?? 0);

        if ($chatId <= 0) {
            $this->jsonError('chat_id inválido', 400);
        }

        $model = new TelegramMessage();

        // Polling incremental o carga completa
        if ($afterId > 0) {
            $messages = $model->getMessagesSince($chatId, $afterId);
        } else {
            $messages  = $model->getMessages($chatId);
        }

        // Marcar como leídos al abrir el chat
        $model->markAsRead($chatId);
        $chat = $model->getChatById($chatId);

        $this->jsonSuccess([
            'messages' => $messages,
            'chat'     => $chat,
            'unread'   => $model->getUnreadCount(),
        ]);
    }

    // ────────────────────────────────────────────────────────────────────────

    /**
     * AJAX liviano: Devuelve el total de mensajes no leídos.
     * No requiere permiso de chat, solo autenticación.
     *
     * GET ?route=chat&action=unread
     */
    public function getUnreadCount(): void
    {
        $this->requireAuth();
        header('Content-Type: application/json; charset=utf-8');
        try {
            $model = new TelegramMessage();
            echo json_encode(['unread' => $model->getUnreadCount()]);
        } catch (Exception $e) {
            echo json_encode(['unread' => 0]);
        }
        exit;
    }

    // ────────────────────────────────────────────────────────────────────────

    /**
     * AJAX: Envía un mensaje de texto y/o archivo.
     *
     * POST ?route=chat&action=send
     * Body: chat_id, message, [file]
     */
    public function sendMessage(): void
    {
        $this->requireAuth();
        $this->requirePermiso('MNU_CHAT_TELEGRAM');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonError('Método no permitido', 405);
        }

        $chatId  = (int)   ($_POST['chat_id'] ?? 0);
        $texto   = trim(   $_POST['message']  ?? '');
        $userId  = (int)   ($_SESSION['user']['id'] ?? 0);
        $hasFile = !empty($_FILES['file']['tmp_name']);

        if ($chatId <= 0) {
            $this->jsonError('chat_id inválido', 400);
        }
        if (empty($texto) && !$hasFile) {
            $this->jsonError('Mensaje vacío', 400);
        }

        $model = new TelegramMessage();

        // ── Envío con archivo adjunto ──────────────────────────────────────
        if ($hasFile) {
            $uploadResult = $this->uploadFileTelegram($chatId, $_FILES['file'], $texto);

            if (!$uploadResult['ok']) {
                // Si Telegram falla, guardar igual localmente
                $archivoPath = $uploadResult['archivo_path'] ?? null;
                $tipo        = $uploadResult['tipo'] ?? 'document';
                $model->insertOutgoing($chatId, $texto ?: '[' . $tipo . ']', $userId, $tipo, $archivoPath);
                $this->jsonSuccess([
                    'sent'     => true,
                    'telegram' => false,
                    'note'     => 'Guardado localmente. Error Telegram: ' . ($uploadResult['error'] ?? ''),
                    'tipo'     => $tipo,
                ]);
            }

            $tipo        = $uploadResult['tipo'];
            $archivoPath = $uploadResult['archivo_path'] ?? null;
            $model->insertOutgoing($chatId, $texto ?: '[' . $tipo . ']', $userId, $tipo, $archivoPath);
            $this->jsonSuccess(['sent' => true, 'telegram' => true, 'tipo' => $tipo]);
        }

        // ── Envío de texto puro ────────────────────────────────────────────
        $apiResult = $this->callTelegramApi('sendMessage', [
            'chat_id'    => $chatId,
            'text'       => $texto,
            'parse_mode' => 'HTML',
        ]);

        // Guardar siempre, aunque Telegram falle (modo offline/desarrollo)
        $model->insertOutgoing($chatId, $texto, $userId, 'text');

        if (!($apiResult['ok'] ?? false)) {
            $this->jsonSuccess([
                'sent'     => true,
                'telegram' => false,
                'note'     => 'Guardado sin enviar a Telegram: ' . ($apiResult['description'] ?? $apiResult['error'] ?? 'sin red'),
            ]);
        }

        $this->jsonSuccess(['sent' => true, 'telegram' => true]);
    }

    // ────────────────────────────────────────────────────────────────────────

    /**
     * AJAX: Guarda un contacto externo en `telegram_contactos` y lo registra
     * en `telegram_chats` para que aparezca en la lista de conversaciones.
     *
     * POST ?route=chat&action=saveContact
     * Body: nombre*, chat_id*, email, celular
     */
    public function saveContact(): void
    {
        $this->requireAuth();
        $this->requirePermiso('MNU_CHAT_TELEGRAM');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonError('Método no permitido', 405);
        }

        $nombre  = trim($_POST['nombre']  ?? '');
        $chatId  = trim($_POST['chat_id'] ?? '');
        $email   = trim($_POST['email']   ?? '');
        $celular = trim($_POST['celular'] ?? '');

        // ── Validaciones básicas ──────────────────────────────────────────
        if ($nombre === '') {
            $this->jsonError('El nombre es obligatorio.');
        }
        if ($chatId === '' || !ctype_digit(ltrim($chatId, '-'))) {
            $this->jsonError('El Chat ID de Telegram debe ser un número válido.');
        }
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->jsonError('El correo electrónico no tiene un formato válido.');
        }

        $chatIdInt = (int) $chatId;

        $model = new TelegramMessage();

        // Guardar en telegram_contactos
        $saved = $model->saveContact($chatIdInt, $nombre, $email ?: null, $celular ?: null);
        if (!$saved) {
            $this->jsonError('Error al guardar el contacto. El Chat ID podría ya existir.', 409);
        }

        // Upsert en telegram_chats para que aparezca en la lista
        $model->upsertChat($chatIdInt, $nombre, null);

        // Devolver datos del chat para que el frontend lo muestre en el sidebar
        $chats = $model->getChats();

        $this->jsonSuccess([
            'message'  => 'Contacto guardado correctamente.',
            'chat_id'  => $chatIdInt,
            'nombre'   => $nombre,
            'chats'    => $chats,
        ]);
    }

    // ════════════════════════════════════════════════════════════════════════
    //  HELPERS PRIVADOS — TELEGRAM API
    // ════════════════════════════════════════════════════════════════════════

    /**
     * Llama a un método de la API de Telegram por cURL.
     *
     * @param  string $method  Nombre del método (sendMessage, sendPhoto, etc.)
     * @param  array  $params  Parámetros del método.
     * @return array           Respuesta de la API decodificada.
     */
    private function callTelegramApi(string $method, array $params): array
    {
        $url = self::TELEGRAM_API . self::BOT_TOKEN . '/' . $method;
        $ch  = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $params,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $response = curl_exec($ch);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($curlErr) {
            error_log("ChatController::callTelegramApi($method) cURL error: $curlErr");
            return ['ok' => false, 'error' => $curlErr];
        }

        $data = json_decode((string) $response, true);
        return is_array($data) ? $data : ['ok' => false, 'error' => 'Respuesta inválida de Telegram'];
    }

    /**
     * Guarda el archivo subido localmente y lo envía a Telegram.
     *
     * @param  int    $chatId   CHAT_ID destino.
     * @param  array  $file     $_FILES['file'] entry.
     * @param  string $caption  Texto opcional del mensaje.
     * @return array            ['ok', 'tipo', 'archivo_path', ...]
     */
    private function uploadFileTelegram(int $chatId, array $file, string $caption = ''): array
    {
        // Crear directorio si no existe
        if (!is_dir(self::UPLOADS_DIR)) {
            mkdir(self::UPLOADS_DIR, 0755, true);
        }

        $mime     = $file['type'] ?? 'application/octet-stream';
        $origName = $file['name'] ?? 'archivo';
        $ext      = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

        // Determinar tipo y método de Telegram
        if (strpos($mime, 'image/') === 0) {
            $tipo   = 'photo';
            $method = 'sendPhoto';
            $field  = 'photo';
        } else {
            $tipo   = 'document';
            $method = 'sendDocument';
            $field  = 'document';
        }

        // Nombre único para el archivo local
        $filename  = date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . ($ext ? '.' . $ext : '');
        $localPath = self::UPLOADS_DIR . $filename;

        if (!move_uploaded_file($file['tmp_name'], $localPath)) {
            return ['ok' => false, 'error' => 'No se pudo guardar el archivo', 'tipo' => $tipo];
        }

        $params = [
            'chat_id' => $chatId,
            $field    => new CURLFile($localPath, $mime, $origName),
        ];
        if (!empty($caption)) {
            $params['caption'] = $caption;
        }

        $apiResult = $this->callTelegramApi($method, $params);
        $apiResult['tipo']         = $tipo;
        $apiResult['archivo_path'] = self::UPLOADS_URL . $filename;
        return $apiResult;
    }

    // ════════════════════════════════════════════════════════════════════════
    //  GUARDS Y HELPERS DE RESPUESTA
    // ════════════════════════════════════════════════════════════════════════

    private function requireAuth(): void
    {
        if (empty($_SESSION['user'])) {
            if ($this->isAjax()) {
                $this->jsonError('No autenticado', 401);
            }
            header('Location: ' . $this->baseUrl() . '?route=login');
            exit;
        }
    }

    private function requirePermiso(string $clave): void
    {
        // ── Bypass: el administrador (USER_ID = 16) siempre tiene acceso ──
        $userId = (int) ($_SESSION['user']['id'] ?? 0);
        if ($userId === 16) {
            return;
        }

        // ── Verificar permiso explícito en la sesión ──────────────────────
        $permisos = $_SESSION['permisos'] ?? [];
        foreach ($permisos as $p) {
            if (isset($p['CLAVE']) && $p['CLAVE'] === $clave) {
                return;
            }
        }

        if ($this->isAjax()) {
            $this->jsonError('Sin permiso para acceder al Chat de Telegram', 403);
        }
        $_SESSION['flash_warning'] = 'No tiene permiso para acceder al Chat de Telegram.';
        header('Location: ' . $this->baseUrl() . '?route=dashboard');
        exit;
    }

    private function isAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    private function jsonSuccess(array $data): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(array_merge(['ok' => true], $data));
        exit;
    }

    private function jsonError(string $message, int $code = 400): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'error' => $message]);
        exit;
    }

    private function baseUrl(): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $script = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
        return "{$scheme}://{$host}{$script}/index.php";
    }
}
