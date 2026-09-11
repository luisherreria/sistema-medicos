-- =============================================================================
-- setup/telegram_setup.sql
-- Crea las tablas necesarias para el módulo de Chat de Telegram.
-- DB: vfpmedicos
--
-- Uso en phpMyAdmin: Importar este archivo SQL.
-- Uso en CLI:  mysql -u root vfpmedicos < setup/telegram_setup.sql
-- =============================================================================

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- ─────────────────────────────────────────────────────────────────────────────
-- 1. TABLA telegram_chats — una fila por chat/contacto de Telegram
-- ─────────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `telegram_chats` (
    `ID`         INT           NOT NULL AUTO_INCREMENT,
    `CHAT_ID`    BIGINT        NOT NULL,
    `NOMBRE`     VARCHAR(200)  NOT NULL DEFAULT '',
    `USERNAME`   VARCHAR(100)  NULL     DEFAULT NULL,
    `CREATED_AT` DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `UPDATED_AT` DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`ID`),
    UNIQUE KEY `uk_chat_id` (`CHAT_ID`),
    INDEX `idx_updated` (`UPDATED_AT`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Conversaciones de Telegram vinculadas al sistema';


-- ─────────────────────────────────────────────────────────────────────────────
-- 2. TABLA telegram_messages — todos los mensajes IN (recibidos) y OUT (enviados)
-- ─────────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `telegram_messages` (
    `ID`           INT                                                        NOT NULL AUTO_INCREMENT,
    `CHAT_ID`      BIGINT                                                     NOT NULL COMMENT 'Referencia a telegram_chats.CHAT_ID',
    `MESSAGE_ID`   BIGINT                                                     NULL DEFAULT NULL COMMENT 'ID del mensaje en Telegram (NULL para OUT antes de confirmación)',
    `DIRECCION`    ENUM('IN','OUT')                                           NOT NULL DEFAULT 'IN',
    `TIPO`         ENUM('text','photo','document','voice','video','sticker','other') NOT NULL DEFAULT 'text',
    `TEXTO`        TEXT                                                       NULL DEFAULT NULL,
    `ARCHIVO_PATH` VARCHAR(500)                                               NULL DEFAULT NULL COMMENT 'Ruta local relativa o file_id de Telegram',
    `LEIDO`        TINYINT(1)                                                 NOT NULL DEFAULT 0,
    `USER_ID`      INT                                                        NULL DEFAULT NULL COMMENT 'Operador que envió el mensaje (solo DIRECCION=OUT)',
    `CREATED_AT`   DATETIME                                                   NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`ID`),
    INDEX `idx_chat_id`  (`CHAT_ID`),
    INDEX `idx_unread`   (`DIRECCION`, `LEIDO`),
    INDEX `idx_created`  (`CREATED_AT`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Mensajes de chat de Telegram (IN=recibidos, OUT=enviados)';


-- ─────────────────────────────────────────────────────────────────────────────
-- 3. Permiso del módulo Chat Telegram
-- ─────────────────────────────────────────────────────────────────────────────
INSERT IGNORE INTO `permisos` (`CLAVE`, `NOMBRE_PERMISO`, `CLAVE_CATEGORIA`, `DESCRIPCION`)
VALUES ('MNU_CHAT_TELEGRAM', 'Chat Telegram', 'UTILES', 'Acceso al módulo de mensajería de Telegram (Mesa Operativa)');

-- Asignar el permiso al usuario administrador (USER_ID = 16)
INSERT IGNORE INTO `users_permissions` (`USER_ID`, `PERMISSION_ID`, `ADDED`, `REMOVED`)
VALUES (16, 'MNU_CHAT_TELEGRAM', 1, 0);


-- =============================================================================
-- VERIFICACIÓN (descomentar para revisar)
-- =============================================================================

-- Chats:
-- SELECT * FROM telegram_chats LIMIT 10;

-- Mensajes no leídos:
-- SELECT COUNT(*) AS no_leidos FROM telegram_messages WHERE DIRECCION = 'IN' AND LEIDO = 0;

-- Permiso asignado:
-- SELECT up.USER_ID, p.CLAVE, p.NOMBRE_PERMISO
-- FROM users_permissions up
-- INNER JOIN permisos p ON up.PERMISSION_ID = p.CLAVE
-- WHERE up.USER_ID = 16 AND p.CLAVE = 'MNU_CHAT_TELEGRAM';
