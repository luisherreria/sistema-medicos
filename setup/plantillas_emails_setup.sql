-- =============================================================================
-- setup/plantillas_emails_setup.sql
-- ABM Cabecera Mails (t_plantillas_emails)
--
-- 1. Crea la tabla si no existe
-- 2. Inserta el permiso MNU_ARC_TAB_CABECERA_MAILS
-- 3. Lo asigna EXCLUSIVAMENTE a USER_ID = 16
-- =============================================================================

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

CREATE TABLE IF NOT EXISTS t_plantillas_emails (
  id            INT          NOT NULL AUTO_INCREMENT,
  codigo        VARCHAR(50)  DEFAULT NULL,
  nombre_uso    VARCHAR(150) DEFAULT NULL,
  asunto        VARCHAR(255) DEFAULT NULL,
  cuerpo        TEXT,
  destinatarios VARCHAR(255) DEFAULT NULL,
  activo        TINYINT(1)   NOT NULL DEFAULT 1,
  PRIMARY KEY (id),
  UNIQUE KEY uk_plantillas_emails_codigo (codigo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO permisos (CLAVE, NOMBRE_PERMISO, CLAVE_CATEGORIA, DESCRIPCION) VALUES
('MNU_ARC_TAB_CABECERA_MAILS', 'Cabecera Mails', 'TABLAS_GENERALES',
 'ABM de plantillas de correo (t_plantillas_emails). Acceso exclusivo rol 16.');

-- Quitar el permiso de cualquier usuario que no sea el 16
UPDATE users_permissions
   SET REMOVED = 1, ADDED = 0
 WHERE PERMISSION_ID = 'MNU_ARC_TAB_CABECERA_MAILS'
   AND USER_ID <> 16;

INSERT IGNORE INTO users_permissions (USER_ID, PERMISSION_ID, ADDED, REMOVED)
VALUES (16, 'MNU_ARC_TAB_CABECERA_MAILS', 1, 0);

UPDATE users_permissions
   SET ADDED = 1, REMOVED = 0
 WHERE USER_ID = 16
   AND PERMISSION_ID = 'MNU_ARC_TAB_CABECERA_MAILS';
