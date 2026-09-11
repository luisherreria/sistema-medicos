-- =============================================================================
-- setup/telegram_contactos.sql
-- Tabla de contactos externos vinculados al Chat de Telegram.
-- DB: vfpmedicos
-- =============================================================================

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

CREATE TABLE IF NOT EXISTS `telegram_contactos` (
    `ID`         INT           NOT NULL AUTO_INCREMENT,
    `CHAT_ID`    BIGINT        NOT NULL COMMENT 'Chat ID de Telegram',
    `NOMBRE`     VARCHAR(200)  NOT NULL,
    `EMAIL`      VARCHAR(200)  NULL DEFAULT NULL,
    `CELULAR`    VARCHAR(50)   NULL DEFAULT NULL,
    `CREATED_AT` DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `UPDATED_AT` DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`ID`),
    UNIQUE KEY `uk_chat_id` (`CHAT_ID`),
    INDEX `idx_nombre` (`NOMBRE`),
    INDEX `idx_email`  (`EMAIL`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Contactos externos del módulo Chat de Telegram';

-- =============================================================================
-- VERIFICACIÓN
-- =============================================================================
-- SELECT * FROM telegram_contactos LIMIT 10;
