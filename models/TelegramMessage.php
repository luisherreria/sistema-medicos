<?php
/**
 * models/TelegramMessage.php
 * Modelo de datos para el módulo de Chat de Telegram.
 *
 * Tablas:
 *   telegram_chats    : ID, CHAT_ID, NOMBRE, USERNAME, CREATED_AT, UPDATED_AT
 *   telegram_messages : ID, CHAT_ID, MESSAGE_ID, DIRECCION, TIPO, TEXTO,
 *                       ARCHIVO_PATH, LEIDO, USER_ID, CREATED_AT
 */

require_once __DIR__ . '/../config/database.php';

class TelegramMessage
{
    private PDO $db;

    public function __construct()
    {
        $this->db = getDBConnection();
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  CHATS
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * Devuelve todos los chats con el último mensaje y cantidad de no leídos.
     * Ordenados por fecha del último mensaje (más reciente primero).
     */
    public function getChats(): array
    {
        $sql = "
            SELECT
                tc.CHAT_ID,
                tc.NOMBRE,
                tc.USERNAME,
                tc.UPDATED_AT,
                lm.TEXTO           AS ULTIMO_MENSAJE,
                lm.TIPO            AS ULTIMO_TIPO,
                lm.DIRECCION       AS ULTIMO_DIR,
                lm.CREATED_AT      AS ULTIMO_FECHA,
                COALESCE(nr.NO_LEIDOS, 0) AS NO_LEIDOS
            FROM telegram_chats tc
            LEFT JOIN (
                SELECT CHAT_ID,
                       TEXTO,
                       TIPO,
                       DIRECCION,
                       CREATED_AT
                FROM telegram_messages t1
                WHERE ID = (
                    SELECT MAX(ID) FROM telegram_messages t2
                    WHERE t2.CHAT_ID = t1.CHAT_ID
                )
            ) lm ON lm.CHAT_ID = tc.CHAT_ID
            LEFT JOIN (
                SELECT CHAT_ID, COUNT(*) AS NO_LEIDOS
                FROM telegram_messages
                WHERE DIRECCION = 'IN' AND LEIDO = 0
                GROUP BY CHAT_ID
            ) nr ON nr.CHAT_ID = tc.CHAT_ID
            ORDER BY COALESCE(lm.CREATED_AT, tc.UPDATED_AT) DESC
        ";

        try {
            return $this->db->query($sql)->fetchAll();
        } catch (PDOException $e) {
            error_log('TelegramMessage::getChats() — ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Devuelve un chat por su CHAT_ID de Telegram.
     */
    public function getChatById(int $chatId): ?array
    {
        try {
            $stmt = $this->db->prepare(
                'SELECT * FROM telegram_chats WHERE CHAT_ID = :id LIMIT 1'
            );
            $stmt->execute([':id' => $chatId]);
            return $stmt->fetch() ?: null;
        } catch (PDOException $e) {
            error_log('TelegramMessage::getChatById() — ' . $e->getMessage());
            return null;
        }
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  CONTACTOS EXTERNOS
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * Inserta un contacto externo en `telegram_contactos`.
     * Si el CHAT_ID ya existe, actualiza nombre/email/celular.
     *
     * @return bool  true si se insertó/actualizó correctamente.
     */
    public function saveContact(int $chatId, string $nombre, ?string $email, ?string $celular): bool
    {
        $sql = "
            INSERT INTO telegram_contactos (CHAT_ID, NOMBRE, EMAIL, CELULAR)
            VALUES (:chat_id, :nombre, :email, :celular)
            ON DUPLICATE KEY UPDATE
                NOMBRE     = VALUES(NOMBRE),
                EMAIL      = VALUES(EMAIL),
                CELULAR    = VALUES(CELULAR),
                UPDATED_AT = NOW()
        ";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':chat_id' => $chatId,
                ':nombre'  => $nombre,
                ':email'   => $email,
                ':celular' => $celular,
            ]);
            return true;
        } catch (PDOException $e) {
            error_log('TelegramMessage::saveContact() — ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Devuelve todos los contactos de `telegram_contactos` ordenados por nombre.
     */
    public function getContacts(): array
    {
        try {
            return $this->db->query(
                "SELECT * FROM telegram_contactos ORDER BY NOMBRE ASC"
            )->fetchAll();
        } catch (PDOException $e) {
            error_log('TelegramMessage::getContacts() — ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Crea o actualiza el registro de un chat.
     * Si ya existe, actualiza NOMBRE y USERNAME sólo si se proveen valores no vacíos.
     */
    public function upsertChat(int $chatId, string $nombre, ?string $username): void
    {
        $sql = "
            INSERT INTO telegram_chats (CHAT_ID, NOMBRE, USERNAME)
            VALUES (:chat_id, :nombre, :username)
            ON DUPLICATE KEY UPDATE
                NOMBRE     = IF(:nombre2 <> '', :nombre2, NOMBRE),
                USERNAME   = IF(:username2 IS NOT NULL, :username2, USERNAME),
                UPDATED_AT = NOW()
        ";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':chat_id'   => $chatId,
                ':nombre'    => $nombre,
                ':username'  => $username,
                ':nombre2'   => $nombre,
                ':username2' => $username,
            ]);
        } catch (PDOException $e) {
            error_log('TelegramMessage::upsertChat() — ' . $e->getMessage());
        }
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  MENSAJES
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * Devuelve los mensajes de una conversación, ordenados ASC (más antiguos primero).
     *
     * @param int $chatId  CHAT_ID de Telegram.
     * @param int $limit   Máximo de mensajes a retornar (últimos N).
     */
    public function getMessages(int $chatId, int $limit = 150): array
    {
        $sql = "
            SELECT m.*
            FROM (
                SELECT * FROM telegram_messages
                WHERE CHAT_ID = :chat_id
                ORDER BY CREATED_AT DESC
                LIMIT :lim
            ) m
            ORDER BY m.CREATED_AT ASC
        ";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':chat_id', $chatId, PDO::PARAM_INT);
            $stmt->bindValue(':lim',     $limit,  PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('TelegramMessage::getMessages() — ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Devuelve mensajes nuevos después de un ID dado (para polling incremental).
     *
     * @param int $chatId    CHAT_ID de Telegram.
     * @param int $afterId   ID del último mensaje conocido por el cliente.
     */
    public function getMessagesSince(int $chatId, int $afterId): array
    {
        $sql = "SELECT * FROM telegram_messages
                WHERE CHAT_ID = :chat_id AND ID > :after_id
                ORDER BY CREATED_AT ASC
                LIMIT 100";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':chat_id' => $chatId, ':after_id' => $afterId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('TelegramMessage::getMessagesSince() — ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Marca como leídos todos los mensajes IN (entrantes) de un chat.
     */
    public function markAsRead(int $chatId): void
    {
        try {
            $stmt = $this->db->prepare(
                "UPDATE telegram_messages
                 SET LEIDO = 1
                 WHERE CHAT_ID = :chat_id AND DIRECCION = 'IN' AND LEIDO = 0"
            );
            $stmt->execute([':chat_id' => $chatId]);
        } catch (PDOException $e) {
            error_log('TelegramMessage::markAsRead() — ' . $e->getMessage());
        }
    }

    /**
     * Cuenta el total de mensajes no leídos (DIRECCION='IN', LEIDO=0) del sistema.
     */
    public function getUnreadCount(): int
    {
        try {
            $stmt = $this->db->query(
                "SELECT COUNT(*) FROM telegram_messages WHERE DIRECCION = 'IN' AND LEIDO = 0"
            );
            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log('TelegramMessage::getUnreadCount() — ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Inserta un mensaje saliente (enviado por el operador).
     *
     * @return int  ID del mensaje recién insertado.
     */
    public function insertOutgoing(
        int     $chatId,
        string  $texto,
        ?int    $userId,
        string  $tipo        = 'text',
        ?string $archivoPath = null
    ): int {
        $sql = "
            INSERT INTO telegram_messages
                (CHAT_ID, DIRECCION, TIPO, TEXTO, ARCHIVO_PATH, LEIDO, USER_ID, CREATED_AT)
            VALUES
                (:chat_id, 'OUT', :tipo, :texto, :archivo, 1, :user_id, NOW())
        ";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':chat_id'  => $chatId,
                ':tipo'     => $tipo,
                ':texto'    => $texto,
                ':archivo'  => $archivoPath,
                ':user_id'  => $userId,
            ]);
            // Actualizar timestamp del chat
            $this->db->prepare(
                "UPDATE telegram_chats SET UPDATED_AT = NOW() WHERE CHAT_ID = :id"
            )->execute([':id' => $chatId]);

            return (int) $this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log('TelegramMessage::insertOutgoing() — ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Inserta un mensaje entrante recibido por el webhook de Telegram.
     *
     * @return int  ID del mensaje recién insertado.
     */
    public function insertIncoming(
        int     $chatId,
        int     $messageId,
        string  $texto,
        string  $tipo        = 'text',
        ?string $archivoPath = null
    ): int {
        $sql = "
            INSERT INTO telegram_messages
                (CHAT_ID, MESSAGE_ID, DIRECCION, TIPO, TEXTO, ARCHIVO_PATH, LEIDO, CREATED_AT)
            VALUES
                (:chat_id, :message_id, 'IN', :tipo, :texto, :archivo, 0, NOW())
        ";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':chat_id'    => $chatId,
                ':message_id' => $messageId,
                ':tipo'       => $tipo,
                ':texto'      => $texto,
                ':archivo'    => $archivoPath,
            ]);
            // Actualizar timestamp del chat
            $this->db->prepare(
                "UPDATE telegram_chats SET UPDATED_AT = NOW() WHERE CHAT_ID = :id"
            )->execute([':id' => $chatId]);

            return (int) $this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log('TelegramMessage::insertIncoming() — ' . $e->getMessage());
            return 0;
        }
    }
}
