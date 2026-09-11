<?php
/**
 * api/telegram/webhook.php
 * ─────────────────────────────────────────────────────────────────────────
 * Receptor de actualizaciones de Telegram (Webhook).
 *
 * Configurar en Telegram:
 *   curl -X POST "https://api.telegram.org/bot<TOKEN>/setWebhook" \
 *        -d "url=https://tu-dominio.com/sistema-medicos/api/telegram/webhook.php&secret_token=<SECRET>"
 *
 * El webhook espera:
 *   - Header X-Telegram-Bot-Api-Secret-Token = WEBHOOK_SECRET (si está configurado)
 *   - Body JSON con la actualización de Telegram
 *
 * Maneja: mensajes de texto, fotos, documentos, voz, video.
 * ─────────────────────────────────────────────────────────────────────────
 */

declare(strict_types=1);

// ── Configuración ─────────────────────────────────────────────────────────
const BOT_TOKEN     = '8973344719:AAGaLHqO7ADz2WqaMDBZhP_kAIFClYoVa0M';
const WEBHOOK_SECRET = '';   // Dejar vacío para deshabilitar verificación (desarrollo local)
const TELEGRAM_API   = 'https://api.telegram.org/bot' . BOT_TOKEN . '/';

// ── Logs ──────────────────────────────────────────────────────────────────
$logFile = __DIR__ . '/../../storage/logs/webhook.log';

// ── Autoload mínimo ────────────────────────────────────────────────────────
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/TelegramMessage.php';

// ════════════════════════════════════════════════════════════════════════════
//  1. VERIFICACIÓN DE SEGURIDAD
// ════════════════════════════════════════════════════════════════════════════

// Verificar método HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

// Verificar secret token (si está configurado)
if (WEBHOOK_SECRET !== '') {
    $receivedSecret = $_SERVER['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN'] ?? '';
    if (!hash_equals(WEBHOOK_SECRET, $receivedSecret)) {
        http_response_code(403);
        exit('Forbidden');
    }
}

// ════════════════════════════════════════════════════════════════════════════
//  2. PARSEO DEL PAYLOAD
// ════════════════════════════════════════════════════════════════════════════

$rawBody = file_get_contents('php://input');
if (!$rawBody) {
    http_response_code(200); // Responder 200 siempre a Telegram
    exit;
}

$update = json_decode($rawBody, true);
if (!is_array($update)) {
    writeLog($logFile, 'WARN', 'Payload inválido: ' . $rawBody);
    http_response_code(200);
    exit;
}

// ── Solo procesar mensajes entrantes (ignorar ediciones, callbacks, etc.)
$message = $update['message'] ?? $update['channel_post'] ?? null;
if (!$message) {
    http_response_code(200);
    exit;
}

// ════════════════════════════════════════════════════════════════════════════
//  3. EXTRACCIÓN DE DATOS DEL MENSAJE
// ════════════════════════════════════════════════════════════════════════════

$chatId    = (int) ($message['chat']['id']              ?? 0);
$messageId = (int) ($message['message_id']              ?? 0);
$from      = $message['from']                           ?? $message['chat'] ?? [];
$nombre    = trim(($from['first_name'] ?? '') . ' ' . ($from['last_name'] ?? ''));
$nombre    = $nombre ?: ($from['username'] ?? 'Desconocido');
$username  = $from['username'] ?? null;

if ($chatId === 0 || $messageId === 0) {
    writeLog($logFile, 'WARN', "chat_id o message_id inválido en: " . json_encode($message));
    http_response_code(200);
    exit;
}

// ── Determinar tipo y contenido ───────────────────────────────────────────
$tipo        = 'text';
$texto       = null;
$archivoPath = null;

if (!empty($message['text'])) {
    $tipo  = 'text';
    $texto = $message['text'];

} elseif (!empty($message['photo'])) {
    $tipo  = 'photo';
    // Tomar la foto de mayor resolución (última del array)
    $photo     = end($message['photo']);
    $fileId    = $photo['file_id'];
    $texto     = $message['caption'] ?? null;
    $archivoPath = downloadTelegramFile($fileId, 'jpg');

} elseif (!empty($message['document'])) {
    $tipo        = 'document';
    $doc         = $message['document'];
    $fileId      = $doc['file_id'];
    $mimeType    = $doc['mime_type'] ?? 'application/octet-stream';
    $fileName    = $doc['file_name'] ?? ('doc_' . $fileId);
    $ext         = pathinfo($fileName, PATHINFO_EXTENSION) ?: 'bin';
    $texto       = $message['caption'] ?? $fileName;
    $archivoPath = downloadTelegramFile($fileId, $ext);

} elseif (!empty($message['voice'])) {
    $tipo        = 'voice';
    $fileId      = $message['voice']['file_id'];
    $archivoPath = downloadTelegramFile($fileId, 'ogg');
    $texto       = '[Mensaje de voz]';

} elseif (!empty($message['video'])) {
    $tipo        = 'video';
    $fileId      = $message['video']['file_id'];
    $archivoPath = downloadTelegramFile($fileId, 'mp4');
    $texto       = $message['caption'] ?? '[Video]';

} elseif (!empty($message['sticker'])) {
    $tipo  = 'sticker';
    $texto = '[Sticker: ' . ($message['sticker']['emoji'] ?? '🎭') . ']';

} else {
    $tipo  = 'other';
    $texto = '[Tipo de mensaje no soportado]';
}

// ════════════════════════════════════════════════════════════════════════════
//  4. PERSISTENCIA EN BD
// ════════════════════════════════════════════════════════════════════════════

try {
    $model = new TelegramMessage();

    // Crear/actualizar el chat
    $model->upsertChat($chatId, $nombre, $username);

    // Insertar mensaje
    $insertedId = $model->insertIncoming($chatId, $messageId, $texto ?? '', $tipo, $archivoPath);

    writeLog($logFile, 'INFO', "Mensaje #{$insertedId} guardado | chat_id={$chatId} tipo={$tipo} de='{$nombre}'");

} catch (Throwable $e) {
    writeLog($logFile, 'ERROR', 'BD Exception: ' . $e->getMessage());
}

// ── Responder 200 a Telegram (siempre, para que no reintente) ─────────────
http_response_code(200);
header('Content-Type: application/json');
echo json_encode(['ok' => true]);
exit;


// ════════════════════════════════════════════════════════════════════════════
//  HELPERS
// ════════════════════════════════════════════════════════════════════════════

/**
 * Descarga un archivo de Telegram y lo guarda localmente.
 *
 * @param  string $fileId   file_id de Telegram.
 * @param  string $ext      Extensión para el archivo local.
 * @return string|null      URL relativa al archivo guardado, o null si falla.
 */
function downloadTelegramFile(string $fileId, string $ext): ?string
{
    // 1. Obtener el path del archivo en Telegram
    $ch = curl_init(TELEGRAM_API . 'getFile?file_id=' . urlencode($fileId));
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode((string) $response, true);
    if (!($data['ok'] ?? false) || empty($data['result']['file_path'])) {
        return null;
    }

    $remotePath = $data['result']['file_path'];
    $downloadUrl = 'https://api.telegram.org/file/bot' . BOT_TOKEN . '/' . $remotePath;

    // 2. Descargar el archivo
    $uploadsDir = __DIR__ . '/../../public/uploads/telegram/';
    if (!is_dir($uploadsDir)) {
        mkdir($uploadsDir, 0755, true);
    }

    $filename  = date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $localPath = $uploadsDir . $filename;

    $ch2 = curl_init($downloadUrl);
    curl_setopt_array($ch2, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_FOLLOWLOCATION => true,
    ]);
    $fileContent = curl_exec($ch2);
    $httpCode    = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
    curl_close($ch2);

    if ($fileContent === false || $httpCode !== 200) {
        return null;
    }

    file_put_contents($localPath, $fileContent);
    return '/sistema-medicos/public/uploads/telegram/' . $filename;
}

/**
 * Escribe en el log del webhook.
 */
function writeLog(string $logFile, string $level, string $message): void
{
    $dir = dirname($logFile);
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    $line = '[' . date('Y-m-d H:i:s') . '] [' . $level . '] ' . $message . PHP_EOL;
    @file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
}
