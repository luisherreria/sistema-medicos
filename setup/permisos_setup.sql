-- =============================================================================
-- setup/permisos_setup.sql
-- Carga completa de permisos del sistema para la base de datos vfpmedicos.
-- Ejecutar UNA SOLA VEZ o usar INSERT IGNORE para ejecuciones repetidas.
--
-- Incluye:
--   1. INSERT de todos los permisos en la tabla `permisos`
--   2. Asignación de todos los permisos al usuario USER_ID = 16
--
-- Uso en phpMyAdmin: Importar este archivo SQL directamente.
-- Uso en CLI:  mysql -u root vfpmedicos < setup/permisos_setup.sql
-- =============================================================================

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- =============================================================================
-- 1. TABLA `permisos` — INSERT de todos los ítems del menú jerárquico
-- =============================================================================

INSERT IGNORE INTO permisos (CLAVE, NOMBRE_PERMISO, CLAVE_CATEGORIA, DESCRIPCION) VALUES

-- ── ARCHIVOS ─────────────────────────────────────────────────────────────────
('MNU_ARC_PRESTADORES',   'Prestadores',                   'ARCHIVOS',      'Gestión del padrón de prestadores médicos'),
('MNU_ARC_ESPECIALIDADES','Especialidades',                 'ARCHIVOS',      'Alta y edición de especialidades médicas'),
('MNU_ARC_OBRAS_SOCIALES','Obras Sociales',                'ARCHIVOS',      'Administración de obras sociales convenidas'),
('MNU_ARC_NOMENCLADOR',   'Nomenclador Nacional',          'ARCHIVOS',      'Gestión del nomenclador nacional de prestaciones'),
('MNU_ARC_NBU',           'NBU',                           'ARCHIVOS',      'Nomenclador de Bioquímica Unificado'),
('MNU_ARC_KAIROS',        'Kairos',                        'ARCHIVOS',      'Integración con sistema Kairos'),

-- ── ARCHIVOS > TABLAS ────────────────────────────────────────────────────────
('MNU_ARC_TAB_LOCALIDADES', 'Localidades',                 'ARCHIVOS',      'Tabla de localidades y municipios'),
('MNU_ARC_TAB_ZONAS',       'Zonas',                       'ARCHIVOS',      'Zonas geográficas de cobertura'),
('MNU_ARC_TAB_GRUPO_WEB',   'Grupo Web',                   'ARCHIVOS',      'Grupos de publicación en cartilla web'),
('MNU_ARC_TAB_EMPRESAS',    'Empresas',                    'ARCHIVOS',      'Tabla de empresas prestadoras'),
('MNU_ARC_TAB_BANCOS',      'Bancos',                      'ARCHIVOS',      'Entidades bancarias para liquidaciones'),
('MNU_ARC_TAB_CIERRE_AUM',  'Cierre de Aumentos',          'ARCHIVOS',      'Gestión de cierres de períodos de aumentos'),
('MNU_ARC_TAB_PCT_AUM',     '% Aumento Presupuesto',       'ARCHIVOS',      'Porcentajes de aumento presupuestario'),
('MNU_ARC_TAB_GRP_CARTILLA','Grupos Cartilla',             'ARCHIVOS',      'Grupos de la cartilla de prestadores'),
('MNU_ARC_TAB_TIT_CARTILLA','Título Cartilla',             'ARCHIVOS',      'Títulos y encabezados de cartilla'),
('MNU_ARC_TAB_CONC_INT',    'Conceptos de Internación',    'ARCHIVOS',      'Conceptos aplicables a internaciones'),
('MNU_ARC_TAB_PROC_INT',    'Procesos de Internación',     'ARCHIVOS',      'Procesos y flujos de internación'),
('MNU_ARC_TAB_TIPO_CERT',   'Tipos Certificados',          'ARCHIVOS',      'Tipos de certificados médicos'),
('MNU_ARC_TAB_VAL_VENTA',   'Valores Lista Venta',         'ARCHIVOS',      'Valores de la lista de ventas'),
('MNU_ARC_TAB_CTRL_WEB',    'Control Actualización Web',   'ARCHIVOS',      'Control de publicaciones en sitio web'),
('MNU_ARC_TAB_URL_REF',     'URL e Referencias',           'ARCHIVOS',      'URLs y referencias externas del sistema'),
('MNU_ARC_TAB_TIPOS_CX',    'Tipos CX para Estadística',   'ARCHIVOS',      'Tipos de cirugía para estadísticas'),
('MNU_ARC_TAB_SUBCAT',      'Subcategoría Prestadores',    'ARCHIVOS',      'Subcategorías de clasificación de prestadores'),
('MNU_ARC_TAB_REP_LEY',     'Reportes Leyendas',           'ARCHIVOS',      'Leyendas para reportes e impresión'),
('MNU_ARC_TAB_HOMOL',       'Homologación Nombres Nomenclador','ARCHIVOS',  'Equivalencias entre nomencladores'),
('MNU_ARC_TAB_GRP_AUT',     'Grupos Autorizaciones',       'ARCHIVOS',      'Grupos para autorizaciones de prestaciones'),
('MNU_ARC_TAB_MOT_DEB',     'Motivos de Débitos',          'ARCHIVOS',      'Causas de débitos en liquidaciones'),
('MNU_ARC_TAB_GRP_NN',      'Grupos N.Nacional',           'ARCHIVOS',      'Grupos del Nomenclador Nacional'),
('MNU_ARC_TAB_SUBGRP_NN',   'Subgrupos N.Nacional',        'ARCHIVOS',      'Subgrupos del Nomenclador Nacional'),
('MNU_ARC_TAB_AJ_IMP',      'Ajustes Aud. Impuestos',      'ARCHIVOS',      'Ajustes de auditoría sobre impuestos'),
('MNU_ARC_TAB_AJ_AFI',      'Ajustes Aud. Afiliado',       'ARCHIVOS',      'Ajustes de auditoría sobre afiliados'),
('MNU_ARC_TAB_TXT_REC',     'Textos de Rechazo',           'ARCHIVOS',      'Textos estándar para rechazo de prestaciones'),
('MNU_ARC_TAB_DIAG',        'Diagnósticos',                'ARCHIVOS',      'Tabla de diagnósticos CIE-10'),

-- ── CARGA DATOS ──────────────────────────────────────────────────────────────
('MNU_CD_PREST_INGRESO',    'Ingreso de Prestaciones',     'CARGA_DATOS',   'Carga de nuevas prestaciones al sistema'),
('MNU_CD_PREST_MOD',        'Modificación de Prestaciones','CARGA_DATOS',   'Modificación de prestaciones existentes'),
('MNU_CD_FAC_INGRESO',      'Ingreso de Facturas',         'CARGA_DATOS',   'Registro de facturas de prestadores'),
('MNU_CD_FAC_LST_DET',      'Listado Detallado x OS',      'CARGA_DATOS',   'Listado detallado de facturas por obra social'),
('MNU_CD_FAC_LST_TOT_AC',   'Totales Acumulados x OS',     'CARGA_DATOS',   'Totales acumulados por obra social'),
('MNU_CD_FAC_LST_TOT_CMP',  'Totales Comparados x OS',     'CARGA_DATOS',   'Comparativa de totales por obra social'),
('MNU_CD_DIAG',             'Carga de Diagnósticos',       'CARGA_DATOS',   'Ingreso de diagnósticos a prestaciones'),

-- ── AUTORIZACIONES ───────────────────────────────────────────────────────────
('MNU_AUT_AUTORIZACIONES',  'Autorizaciones',              'AUTORIZACIONES', 'Gestión de autorizaciones de prestaciones'),

-- ── AUDIT. FACTURAC. ─────────────────────────────────────────────────────────
('MNU_AF_AUDITAR',          'Auditar Facturación',         'AUDIT_FAC',     'Auditoría de facturas de prestadores'),
('MNU_AF_CIERRE_AUTO',      'Cierre Automático',           'AUDIT_FAC',     'Cierre automático de períodos de facturación'),
('MNU_AF_LIQ_AUDITAR',      'Liquidaciones a Auditar',     'AUDIT_FAC',     'Cola de liquidaciones pendientes de auditoría'),
('MNU_AF_LIQ_SIN_CERRAR',   'Liquidaciones sin Cerrar',    'AUDIT_FAC',     'Liquidaciones abiertas sin cierre definitivo'),
('MNU_AF_REIMP_LIQ',        'Reimpresión de Liquidaciones','AUDIT_FAC',     'Reimpresión de liquidaciones cerradas'),
('MNU_AF_ENV_DEBITO',       'Envío avisos de débito',      'AUDIT_FAC',     'Envío de notificaciones de débito a prestadores'),
('MNU_AF_EXP_PREST_GEN',    'Export. Prestaciones General','AUDIT_FAC',     'Exportación general de prestaciones'),
('MNU_AF_EXP_PREST_AYER',   'Export. Hasta Ayer',          'AUDIT_FAC',     'Exportación de prestaciones hasta ayer'),
('MNU_AF_EXP_PREST_VENT',   'Export. Hasta Ayer Ventas',   'AUDIT_FAC',     'Exportación ventas hasta ayer'),
('MNU_AF_CALC_CAPITAS',     'Cálculo Cápitas',             'AUDIT_FAC',     'Cálculo y liquidación de cápitas'),

-- ── CONTADURÍA ───────────────────────────────────────────────────────────────
('MNU_CONT_LC_PERIODO',     'Liq. Cerradas x Período',     'CONTADURIA',    'Listado de liquidaciones cerradas por período'),
('MNU_CONT_LC_OS_PER',      'Liq. Cerradas x O.S. y Período','CONTADURIA',  'Liquidaciones cerradas por obra social y período'),
('MNU_CONT_LC_CERR_PER',    'Cerradas por Período',        'CONTADURIA',    'Resumen de liquidaciones cerradas por período'),
('MNU_CONT_LC_OS_FCH',      'x O.Social y Fecha Cierre',   'CONTADURIA',    'Liquidaciones por obra social y fecha de cierre'),
('MNU_CONT_LC_CUIDAR',      'Pago de CUIDAR',              'CONTADURIA',    'Liquidaciones del programa CUIDAR'),
('MNU_CONT_LC_VARIOS',      'x Varios Prestadores',        'CONTADURIA',    'Liquidaciones de múltiples prestadores'),
('MNU_CONT_LC_FECHAS',      'Reporte entre Fechas',        'CONTADURIA',    'Reporte de liquidaciones en rango de fechas'),
('MNU_CONT_LIQ_EST',        'Liquidaciones Estimadas',     'CONTADURIA',    'Estimación de próximas liquidaciones'),
('MNU_CONT_SIN_CERRAR',     'Sin cerrar hasta Ayer',       'CONTADURIA',    'Liquidaciones abiertas hasta ayer'),
('MNU_CONT_SALDOS',         'Listado de Saldos',           'CONTADURIA',    'Saldos por prestador y obra social'),
('MNU_CONT_RECIBOS',        'Recibos Faltantes',           'CONTADURIA',    'Prestadores con recibos pendientes de entrega'),
('MNU_CONT_FECHAS_PAGO',    'Fechas de Pago',              'CONTADURIA',    'Calendario de fechas de pago a prestadores'),
('MNU_CONT_RES_OPAGO',      'Resumen O.Pago',              'CONTADURIA',    'Resumen de órdenes de pago emitidas'),
('MNU_CONT_HIST_PREST',     'Historial x Prestador',       'CONTADURIA',    'Historial completo de pagos por prestador'),
('MNU_CONT_ANUL_OPAGO',     'Anulación O.Pago',            'CONTADURIA',    'Anulación de órdenes de pago'),

-- ── RESÚMENES / REPORTES ─────────────────────────────────────────────────────
('MNU_REP_HIST_CLIN',       'Historias Clínicas',          'REPORTES',      'Reporte de historias clínicas por afiliado'),
('MNU_REP_AFIL_PADRON',     'Afil. Fuera Padrón',          'REPORTES',      'Afiliados no registrados en el padrón'),
('MNU_REP_AFIL_PAD_PREST',  'Afil. fuera padrón x prestador','REPORTES',    'Afiliados fuera de padrón por prestador'),
('MNU_REP_VENTAS_GEN',      'Lista de Ventas (General)',   'REPORTES',      'Listado general de ventas'),
('MNU_REP_VENTAS_GRP',      'Lista de Ventas (Grupo)',     'REPORTES',      'Listado de ventas agrupadas'),
('MNU_REP_VENTAS_AYER',     'Lista de Ventas (Ayer)',      'REPORTES',      'Ventas del día anterior'),
('MNU_REP_VENTAS_AYER_GRP', 'Lista de Ventas (Ayer Grupo)','REPORTES',      'Ventas agrupadas del día anterior'),
('MNU_REP_NORM_650',        'Normativa 650',               'REPORTES',      'Reporte de Normativa 650 ANSES'),
('MNU_REP_AUD_CTRL',        'Auditoría Control Prestadores','REPORTES',      'Control cruzado de auditoría de prestadores'),

-- ── CONFIGURACIÓN ────────────────────────────────────────────────────────────
('MNU_CFG_CAMBIO_USR',      'Cambio de Usuario',           'CONFIGURACION', 'Cambio de contraseña de usuario'),
('MNU_CFG_CARGA_USR',       'Carga de Usuarios',           'CONFIGURACION', 'Alta y gestión de usuarios del sistema'),
('MNU_CFG_CONV_PAD',        'Conversión de Padrones',      'CONFIGURACION', 'Conversión e importación de padrones'),
('MNU_CFG_EMAIL_MAS',       'Envío eMail Masivo',          'CONFIGURACION', 'Envío masivo de correos a prestadores'),
('MNU_CFG_CARTILLA',        'Cartilla',                    'CONFIGURACION', 'Gestión de la cartilla digital de prestadores'),
('MNU_CFG_LOG_MAILS',       'Log Mails Enviados',          'CONFIGURACION', 'Registro de correos enviados por el sistema'),
('MNU_CFG_LOG_PAGOS',       'Log Pagos',                   'CONFIGURACION', 'Log de pagos procesados'),
('MNU_CFG_LOG_RES',         'Log Resumen',                 'CONFIGURACION', 'Log resumen de operaciones'),
('MNU_CFG_LOG_PREST',       'Log Prestadores',             'CONFIGURACION', 'Log de cambios en prestadores'),
('MNU_CFG_LOG_DEB',         'Log Débitos',                 'CONFIGURACION', 'Log de débitos aplicados'),
('MNU_CFG_LOG_LIQ_SC',      'Log Liq. sin cerrar',         'CONFIGURACION', 'Log de liquidaciones sin cerrar'),
('MNU_CFG_LOG_COSEG',       'Log Coseguros',               'CONFIGURACION', 'Log de coseguros aplicados'),
('MNU_CFG_LOG_HIST_AUM',    'Log Hist PAumentos',          'CONFIGURACION', 'Historial de procesamiento de aumentos'),
('MNU_CFG_LOG_PDF_AUT',     'Log PDF Autorizaciones',      'CONFIGURACION', 'Log de generación de PDFs de autorización'),
('MNU_CFG_LOG_NOV_OS',      'Log Novedades OS',            'CONFIGURACION', 'Log de novedades por obra social'),
('MNU_CFG_LOG_USO_PREST',   'Log Uso Prestaciones',        'CONFIGURACION', 'Log de uso y acceso a prestaciones'),
('MNU_CFG_LOG_MAS_PREST',   'Log Masivo Prestadores',      'CONFIGURACION', 'Log de procesos masivos de prestadores'),
('MNU_CFG_LOG_MAS_ERR',     'Log Masivo Error',            'CONFIGURACION', 'Log de errores en procesos masivos'),
('MNU_CFG_LOG_BAJADA',      'Log Bajada Órdenes MO',       'CONFIGURACION', 'Log de bajada de órdenes desde Mesa Operativa'),
('MNU_CFG_LOG_TOPE',        'Log Valor Tope Carga',        'CONFIGURACION', 'Log de valores tope en carga de prestaciones'),
('MNU_CFG_LOG_PAGOS_AUT',   'Log Pagos Resumen Automático','CONFIGURACION', 'Log de resumen automático de pagos'),
('MNU_CFG_CIE_PERIODO',     'Cierre de período',           'CONFIGURACION', 'Cierre de período contable'),
('MNU_CFG_CIE_AUM',         'Cierre de Aumentos',          'CONFIGURACION', 'Cierre del proceso de aumentos');

-- =============================================================================
-- 2. TABLA `users_permissions` — Asignar TODOS los permisos a USER_ID = 16
-- =============================================================================

INSERT IGNORE INTO users_permissions (USER_ID, PERMISSION_ID, ADDED, REMOVED)
SELECT 16, CLAVE, 1, 0
FROM permisos
WHERE CLAVE IN (
    -- ARCHIVOS
    'MNU_ARC_PRESTADORES','MNU_ARC_ESPECIALIDADES','MNU_ARC_OBRAS_SOCIALES',
    'MNU_ARC_NOMENCLADOR','MNU_ARC_NBU','MNU_ARC_KAIROS',
    -- ARCHIVOS > TABLAS
    'MNU_ARC_TAB_LOCALIDADES','MNU_ARC_TAB_ZONAS','MNU_ARC_TAB_GRUPO_WEB',
    'MNU_ARC_TAB_EMPRESAS','MNU_ARC_TAB_BANCOS','MNU_ARC_TAB_CIERRE_AUM',
    'MNU_ARC_TAB_PCT_AUM','MNU_ARC_TAB_GRP_CARTILLA','MNU_ARC_TAB_TIT_CARTILLA',
    'MNU_ARC_TAB_CONC_INT','MNU_ARC_TAB_PROC_INT','MNU_ARC_TAB_TIPO_CERT',
    'MNU_ARC_TAB_VAL_VENTA','MNU_ARC_TAB_CTRL_WEB','MNU_ARC_TAB_URL_REF',
    'MNU_ARC_TAB_TIPOS_CX','MNU_ARC_TAB_SUBCAT','MNU_ARC_TAB_REP_LEY',
    'MNU_ARC_TAB_HOMOL','MNU_ARC_TAB_GRP_AUT','MNU_ARC_TAB_MOT_DEB',
    'MNU_ARC_TAB_GRP_NN','MNU_ARC_TAB_SUBGRP_NN','MNU_ARC_TAB_AJ_IMP',
    'MNU_ARC_TAB_AJ_AFI','MNU_ARC_TAB_TXT_REC','MNU_ARC_TAB_DIAG',
    -- CARGA DATOS
    'MNU_CD_PREST_INGRESO','MNU_CD_PREST_MOD','MNU_CD_FAC_INGRESO',
    'MNU_CD_FAC_LST_DET','MNU_CD_FAC_LST_TOT_AC','MNU_CD_FAC_LST_TOT_CMP','MNU_CD_DIAG',
    -- AUTORIZACIONES
    'MNU_AUT_AUTORIZACIONES',
    -- AUDIT. FACTURAC.
    'MNU_AF_AUDITAR','MNU_AF_CIERRE_AUTO','MNU_AF_LIQ_AUDITAR','MNU_AF_LIQ_SIN_CERRAR',
    'MNU_AF_REIMP_LIQ','MNU_AF_ENV_DEBITO','MNU_AF_EXP_PREST_GEN',
    'MNU_AF_EXP_PREST_AYER','MNU_AF_EXP_PREST_VENT','MNU_AF_CALC_CAPITAS',
    -- CONTADURÍA
    'MNU_CONT_LC_PERIODO','MNU_CONT_LC_OS_PER','MNU_CONT_LC_CERR_PER',
    'MNU_CONT_LC_OS_FCH','MNU_CONT_LC_CUIDAR','MNU_CONT_LC_VARIOS','MNU_CONT_LC_FECHAS',
    'MNU_CONT_LIQ_EST','MNU_CONT_SIN_CERRAR','MNU_CONT_SALDOS','MNU_CONT_RECIBOS',
    'MNU_CONT_FECHAS_PAGO','MNU_CONT_RES_OPAGO','MNU_CONT_HIST_PREST','MNU_CONT_ANUL_OPAGO',
    -- REPORTES
    'MNU_REP_HIST_CLIN','MNU_REP_AFIL_PADRON','MNU_REP_AFIL_PAD_PREST',
    'MNU_REP_VENTAS_GEN','MNU_REP_VENTAS_GRP','MNU_REP_VENTAS_AYER',
    'MNU_REP_VENTAS_AYER_GRP','MNU_REP_NORM_650','MNU_REP_AUD_CTRL',
    -- CONFIGURACIÓN
    'MNU_CFG_CAMBIO_USR','MNU_CFG_CARGA_USR','MNU_CFG_CONV_PAD',
    'MNU_CFG_EMAIL_MAS','MNU_CFG_CARTILLA',
    'MNU_CFG_LOG_MAILS','MNU_CFG_LOG_PAGOS','MNU_CFG_LOG_RES','MNU_CFG_LOG_PREST',
    'MNU_CFG_LOG_DEB','MNU_CFG_LOG_LIQ_SC','MNU_CFG_LOG_COSEG','MNU_CFG_LOG_HIST_AUM',
    'MNU_CFG_LOG_PDF_AUT','MNU_CFG_LOG_NOV_OS','MNU_CFG_LOG_USO_PREST',
    'MNU_CFG_LOG_MAS_PREST','MNU_CFG_LOG_MAS_ERR','MNU_CFG_LOG_BAJADA',
    'MNU_CFG_LOG_TOPE','MNU_CFG_LOG_PAGOS_AUT',
    'MNU_CFG_CIE_PERIODO','MNU_CFG_CIE_AUM'
);

-- =============================================================================
-- VERIFICACIÓN
-- =============================================================================

-- Ver permisos insertados en `permisos`:
-- SELECT COUNT(*) AS total_permisos FROM permisos WHERE CLAVE LIKE 'MNU_%';

-- Ver permisos asignados a USER_ID=16:
-- SELECT COUNT(*) AS total_asignados FROM users_permissions WHERE USER_ID = 16 AND ADDED = 1;

-- Ver detalle de permisos del usuario 16:
-- SELECT up.USER_ID, p.CLAVE, p.NOMBRE_PERMISO, p.CLAVE_CATEGORIA
-- FROM users_permissions up
-- INNER JOIN permisos p ON up.PERMISSION_ID = p.CLAVE
-- WHERE up.USER_ID = 16 AND up.ADDED = 1 AND (up.REMOVED = 0 OR up.REMOVED IS NULL)
-- ORDER BY p.CLAVE_CATEGORIA, p.NOMBRE_PERMISO;
