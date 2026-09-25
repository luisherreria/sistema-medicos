-- Log de envíos para classes/MailService.php
-- t_config_smtp ya existe (id, metodo_envio, host, puerto, usuario,
-- password, remitente_email, remitente_nombre, api_key). No recrear.

CREATE TABLE IF NOT EXISTS t_log_emails (
    id                INT          NOT NULL AUTO_INCREMENT,
    fecha             DATETIME     NOT NULL,
    modulo            VARCHAR(80)  NOT NULL DEFAULT '',
    codigo_plantilla  VARCHAR(80)  NOT NULL DEFAULT '',
    destinatarios     TEXT         NULL,
    asunto            VARCHAR(255) NOT NULL DEFAULT '',
    estado            VARCHAR(20)  NOT NULL DEFAULT 'ERROR',
    mensaje_servidor  TEXT         NULL,
    PRIMARY KEY (id),
    KEY idx_log_fecha (fecha),
    KEY idx_log_modulo (modulo),
    KEY idx_log_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
