-- =============================================================================
-- setup/tablas_generales_setup.sql
-- PK `id` en tablas satélite + permisos extra para USER_ID=16
-- Compatible con MySQL 5.6 (sin ADD COLUMN IF NOT EXISTS).
--
-- Si `id` ya existe, el ALTER falla: ignorar ese error o usar
-- setup/tablas_generales_setup.php (chequea information_schema).
-- =============================================================================

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- Satélites (si la columna id no existe, descomentar y ejecutar uno a uno):
-- ALTER TABLE grupocarti ADD COLUMN id INT NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST;
-- ALTER TABLE gruponn    ADD COLUMN id INT NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST;
-- ALTER TABLE gruposup   ADD COLUMN id INT NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST;
-- ALTER TABLE nomentp    ADD COLUMN id INT NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST;
-- ALTER TABLE tablas     ADD COLUMN id INT NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST;

CREATE TABLE IF NOT EXISTS diagno (
  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  tacodigo VARCHAR(8) DEFAULT NULL,
  tadescrip VARCHAR(80) DEFAULT NULL,
  tagrupo VARCHAR(20) DEFAULT NULL,
  isonco TINYINT(1) DEFAULT 0,
  isvih TINYINT(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO permisos (CLAVE, NOMBRE_PERMISO, CLAVE_CATEGORIA, DESCRIPCION) VALUES
('MNU_ARC_TAB_NOMENTP',    'Tipo Normativa 650',       'ARCHIVOS', 'ABM tipos de normativa 650'),
('MNU_ARC_TAB_GRUPOCARTI', 'Grupos Cartilla N.N',      'ARCHIVOS', 'ABM grupos cartilla nomenclador'),
('MNU_ARC_TAB_GRUPONN',    'Grupos N.N',               'ARCHIVOS', 'ABM grupos nomenclador nacional'),
('MNU_ARC_TAB_GRUPOSUP',   'Titulo Cartilla N.N',      'ARCHIVOS', 'ABM titulo cartilla nomenclador'),
('MNU_ARC_TAB_ESP',        'Tipos de Especializacion', 'ARCHIVOS', 'ABM tipos de especialización'),
('MNU_ARC_TABLAS',         'Tablas Generales',         'ARCHIVOS', 'Hub de tablas generales');

INSERT IGNORE INTO users_permissions (USER_ID, PERMISSION_ID, ADDED, REMOVED)
SELECT 16, CLAVE, 1, 0 FROM permisos WHERE CLAVE LIKE 'MNU_ARC_TAB%';
