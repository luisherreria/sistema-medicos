-- =============================================================================
-- setup/ebamp_migration.sql
-- Reconstrucción de tablas InnoDB con engine perdido + tabla novedade
--
-- EJECUTAR UNA SOLA VEZ en phpMyAdmin o MySQL CLI:
--   SOURCE /path/to/ebamp_migration.sql
--
-- Contexto: Las tablas existen en el catálogo de MySQL (information_schema)
-- pero los archivos .ibd de InnoDB están ausentes → "doesn't exist in engine".
-- Este script dropea las definiciones huérfanas y las recrea correctamente.
-- =============================================================================

SET FOREIGN_KEY_CHECKS = 0;
SET sql_mode = '';

-- ─────────────────────────────────────────────────────────────────────────────
-- 1. EBAMP — Padrón de Prestadores (tabla principal)
-- ─────────────────────────────────────────────────────────────────────────────
DROP TABLE IF EXISTS `ebamp`;
CREATE TABLE `ebamp` (
  `ESPEC`        char(30)       DEFAULT NULL,
  `CODIGO`       char(11)       DEFAULT NULL COMMENT 'Código interno del prestador',
  `CATEG`        char(11)       DEFAULT NULL COMMENT 'Categoría: CP, SAN, FARM, LAB…',
  `MATRICULA`    char(8)        DEFAULT NULL,
  `NOMBRE`       char(100)      DEFAULT NULL,
  `DIRECC`       char(60)       DEFAULT NULL,
  `TELCONS`      char(70)       DEFAULT NULL,
  `LOCALIDAD`    char(30)       DEFAULT NULL,
  `ZONA`         char(19)       DEFAULT NULL,
  `TELPART`      char(40)       DEFAULT NULL,
  `CELULAR`      char(15)       DEFAULT NULL,
  `FAX`          char(12)       DEFAULT NULL,
  `LUN`          char(11)       DEFAULT NULL,
  `MAR`          char(11)       DEFAULT NULL,
  `MIE`          char(11)       DEFAULT NULL,
  `JUE`          char(11)       DEFAULT NULL,
  `VIE`          char(11)       DEFAULT NULL,
  `SAB`          char(11)       DEFAULT NULL,
  `MAIL`         char(250)      DEFAULT NULL,
  `MAIL_PAGO`    char(250)      DEFAULT NULL,
  `MAIL_VENC`    char(250)      DEFAULT NULL,
  `MAIL_DEB`     char(250)      DEFAULT NULL,
  `MAIL_AUTO`    char(250)      DEFAULT NULL,
  `NUEVOS`       decimal(11,0)  DEFAULT NULL,
  `ESPE`         decimal(11,0)  DEFAULT NULL,
  `ZONAN`        decimal(11,0)  DEFAULT NULL,
  `OSBA`         decimal(11,0)  DEFAULT NULL,
  `OSPTV`        decimal(11,0)  DEFAULT NULL,
  `GPM`          decimal(11,0)  DEFAULT NULL,
  `SELECC`       char(1)        DEFAULT NULL,
  `TIPO`         char(60)       DEFAULT NULL,
  `CODESP`       char(8)        DEFAULT NULL,
  `TIPOMED`      char(10)       DEFAULT NULL,
  `ACTIVO`       char(1)        DEFAULT NULL,
  `RECOMENDA`    char(30)       DEFAULT NULL,
  `LOCALIDA2`    char(18)       DEFAULT NULL,
  `LOCALIDA3`    char(18)       DEFAULT NULL,
  `ZONA2`        char(19)       DEFAULT NULL,
  `ZONA3`        char(19)       DEFAULT NULL,
  `DIRECC2`      char(29)       DEFAULT NULL,
  `DIRECC3`      char(29)       DEFAULT NULL,
  `TELCONS2`     char(40)       DEFAULT NULL,
  `TELCONS3`     char(40)       DEFAULT NULL,
  `EMPRESA`      char(8)        DEFAULT NULL,
  `BANCO`        char(8)        DEFAULT NULL,
  `SUCURSAL`     char(3)        DEFAULT NULL,
  `NOMBREEMP`    char(30)       DEFAULT NULL,
  `NOMBREBAN`    char(30)       DEFAULT NULL,
  `NOMBRESUC`    char(30)       DEFAULT NULL,
  `HORARIO1`     char(40)       DEFAULT NULL,
  `HORARIO2`     char(40)       DEFAULT NULL,
  `HORARIO3`     char(40)       DEFAULT NULL,
  `CBU`          char(40)       DEFAULT NULL,
  `LABO`         char(1)        DEFAULT NULL,
  `IMAG`         char(1)        DEFAULT NULL,
  `CONTACTO`     char(100)      DEFAULT NULL,
  `NOMALTERNA`   char(40)       DEFAULT NULL,
  `TIPODOC`      char(3)        DEFAULT NULL,
  `NRODOC`       char(10)       DEFAULT NULL,
  `SSS`          char(1)        DEFAULT NULL,
  `SSSVENC`      datetime       DEFAULT NULL,
  `HABILITAC`    char(1)        DEFAULT NULL,
  `SEGURO`       char(1)        DEFAULT NULL,
  `SEGUROVENC`   datetime       DEFAULT NULL,
  `MATRICULAD`   char(1)        DEFAULT NULL,
  `TITULO`       char(1)        DEFAULT NULL,
  `CONTRATO`     char(1)        DEFAULT NULL,
  `CONTRAFEC`    datetime       DEFAULT NULL,
  `CLAUSULA`     char(1)        DEFAULT NULL,
  `TRABAJAOS`    char(1)        DEFAULT NULL COMMENT '1=tiene OS activas, 0=sin OS',
  `TIENEPREST`   char(1)        DEFAULT NULL COMMENT '1=tiene prácticas',
  `BAJA`         char(1)        DEFAULT NULL COMMENT 'T=dado de baja (VFP .T.)',
  `FBAJA`        datetime       DEFAULT NULL,
  `CUIT`         char(13)       DEFAULT NULL,
  `CADENA`       char(8)        DEFAULT NULL,
  `CHQORD`       char(50)       DEFAULT NULL,
  `SELECCION`    char(1)        DEFAULT NULL,
  `CODTANGO`     char(10)       DEFAULT NULL,
  `NROPROV`      char(10)       DEFAULT NULL,
  `CONFLICTO`    char(1)        DEFAULT NULL,
  `MONOTRIB`     char(1)        DEFAULT NULL,
  `FECHAALTA`    datetime       DEFAULT NULL,
  `FECHABAJA`    datetime       DEFAULT NULL COMMENT 'Si <= CURDATE() = prestador dado de baja',
  `CONDIVA`      char(8)        DEFAULT NULL,
  `AGRUPACION`   char(8)        DEFAULT NULL,
  `DELEGACION`   char(8)        DEFAULT NULL,
  `COPIA`        char(1)        DEFAULT NULL,
  `RETIRADO`     char(1)        DEFAULT NULL,
  `BUENPERFIL`   char(1)        DEFAULT NULL,
  `ESPECIALIZ`   char(30)       DEFAULT NULL,
  `EXCLUCART`    char(1)        DEFAULT NULL,
  `EXCLUCALL`    char(1)        DEFAULT NULL,
  `IMPORTE`      decimal(10,2)  DEFAULT NULL,
  `CATEGORIZA`   char(20)       DEFAULT NULL,
  `PRESTADOR`    char(8)        DEFAULT NULL,
  `FALTACBU`     char(1)        DEFAULT NULL,
  `FALTAREC`     char(1)        DEFAULT NULL,
  `FALTAOTRO`    char(50)       DEFAULT NULL,
  `FALTAOTRO2`   char(1)        DEFAULT NULL,
  `ANOMDESDE`    char(5)        DEFAULT NULL,
  `XPREST`       char(1)        DEFAULT NULL,
  `INFOLIQ`      text           DEFAULT NULL,
  `OBSERVA`      text           DEFAULT NULL,
  `NOMFANTAS`    char(150)      DEFAULT NULL,
  `CERTIF`       text           DEFAULT NULL,
  `AVISO`        char(100)      DEFAULT NULL,
  `FECHAAVI`     datetime       DEFAULT NULL,
  `LEGAJO`       int(11)        DEFAULT NULL,
  `ETITULO`      char(6)        DEFAULT NULL,
  `EMAXAFIL`     int(11)        DEFAULT NULL,
  `ELATITUD`     char(20)       DEFAULT NULL,
  `ELONGITU`     char(20)       DEFAULT NULL,
  `ECOUNTAF`     int(11)        DEFAULT NULL,
  `DCONTRATO`    char(250)      DEFAULT NULL,
  `CONFACT`      char(1)        DEFAULT NULL,
  `IDBD`         int(11)        DEFAULT NULL,
  `CODREEM`      char(11)       DEFAULT NULL,
  `FMODIF`       datetime       DEFAULT NULL,
  `FUSER`        char(5)        DEFAULT NULL,
  `FHORA`        char(5)        DEFAULT NULL,
  `ID_PREST`     decimal(10,0)  DEFAULT NULL,
  `PRACDEFA`     char(6)        DEFAULT NULL,
  `ISAUTO`       char(1)        DEFAULT NULL,
  `CARGAUSR`     char(20)       DEFAULT NULL,
  `SUBCATEG`     char(30)       DEFAULT NULL,
  `IDWEB`        int(11)        DEFAULT NULL,
  `GRUPOWEB`     char(20)       DEFAULT NULL,
  `CONTVTO`      char(150)      DEFAULT NULL,
  `CONTLIQ`      char(150)      DEFAULT NULL,
  `CONTCON`      char(150)      DEFAULT NULL,
  `CONTAUT`      char(70)       DEFAULT NULL,
  `TELVTO`       char(70)       DEFAULT NULL,
  `TELLIQ`       char(70)       DEFAULT NULL,
  `TELCON`       char(70)       DEFAULT NULL,
  `TELAUT`       char(70)       DEFAULT NULL,
  `MAILCONTRA`   char(250)      DEFAULT NULL,
  `IDRED`        int(11)        DEFAULT NULL,
  `FECHAAUM`     datetime       DEFAULT NULL,
  `AUMENTO`      char(1)        DEFAULT NULL,
  `ISONCOLOGO`   char(1)        DEFAULT NULL,
  `EXCLSTVTA`    char(1)        DEFAULT NULL,
  `VALERECI`     char(1)        DEFAULT NULL,
  `MATRIMED`     char(11)       DEFAULT NULL,
  `CONVENIO`     char(6)        DEFAULT NULL,
  `TIENESUCPR`   char(1)        DEFAULT NULL,
  `TIENESUC`     char(1)        DEFAULT NULL COMMENT '1=tiene sucursal, 0=sin sucursal',
  `SUCEXCLU`     char(1)        DEFAULT NULL,
  `PRACEXCLU`    char(1)        DEFAULT NULL,
  `HACEINTER`    char(1)        DEFAULT NULL,
  `MAIL_FACT`    char(250)      DEFAULT NULL,
  `MATRIPROV`    char(11)       DEFAULT NULL,
  `TELCTRA`      char(150)      DEFAULT NULL,
  `TELFACT`      char(150)      DEFAULT NULL,
  `CONTCTRA`     char(70)       DEFAULT NULL,
  `CONTFACT`     char(70)       DEFAULT NULL,
  `MOTIVOBJ`     char(250)      DEFAULT NULL,
  `ISSUSPEND`    char(1)        DEFAULT NULL,
  `id_pk`        int(11)        NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id_pk`),
  KEY `idx_codigo`   (`CODIGO`),
  KEY `idx_categ`    (`CATEG`),
  KEY `idx_fechabaja`(`FECHABAJA`),
  KEY `idx_nombre`   (`NOMBRE`(20))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Padrón de Prestadores - migrado de VFP (ebamp.dbf)';

-- ─────────────────────────────────────────────────────────────────────────────
-- 2. OBRAMED — Obras Sociales habilitadas por prestador
-- ─────────────────────────────────────────────────────────────────────────────
DROP TABLE IF EXISTS `obramed`;
CREATE TABLE `obramed` (
  `OBRASOC`     char(8)       DEFAULT NULL COMMENT 'Código de la O.S. (FK → obrasoc.TACODIGO)',
  `MEDICO`      char(8)       DEFAULT NULL COMMENT 'Código del prestador (FK → ebamp.CODIGO)',
  `ESPEC`       char(30)      DEFAULT NULL,
  `PRESTACION`  char(6)       DEFAULT NULL,
  `IMPORTE`     decimal(10,2) DEFAULT NULL,
  `NNMAS`       decimal(10,2) DEFAULT NULL,
  `NOMMED`      char(40)      DEFAULT NULL,
  `NOMPREST`    char(40)      DEFAULT NULL,
  `NOMOBRA`     char(40)      DEFAULT NULL,
  `EMPRESA`     char(8)       DEFAULT NULL,
  `NOMEMPRE`    char(40)      DEFAULT NULL,
  `BANCO`       char(8)       DEFAULT NULL,
  `NOMBAN`      char(40)      DEFAULT NULL,
  `CONTRATO`    char(1)       DEFAULT NULL,
  `CLAUSULA`    char(1)       DEFAULT NULL,
  `GRUPO`       char(10)      DEFAULT NULL,
  `FECHAALTA`   datetime      DEFAULT NULL,
  `FECHABAJA`   datetime      DEFAULT NULL,
  `COPIA`       char(1)       DEFAULT NULL,
  `RETIRADO`    char(1)       DEFAULT NULL,
  `CONSU1`      char(1)       DEFAULT NULL,
  `CONSU2`      char(1)       DEFAULT NULL,
  `CONSU3`      char(1)       DEFAULT NULL,
  `EMPRESAANT`  char(8)       DEFAULT NULL,
  `EXCLUCART`   char(1)       DEFAULT NULL,
  `EXCLUCALL`   char(1)       DEFAULT NULL,
  `EXCEP`       char(1)       DEFAULT NULL COMMENT 'S=tiene excepción',
  `USER`        char(10)      DEFAULT NULL,
  `FECHAUP`     datetime      DEFAULT NULL,
  `TIMEUP`      char(10)      DEFAULT NULL,
  `id_pk`       int(11)       NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id_pk`),
  KEY `idx_medico`  (`MEDICO`),
  KEY `idx_obrasoc` (`OBRASOC`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Obras Sociales habilitadas por prestador - migrado de VFP (obramed.dbf)';

-- ─────────────────────────────────────────────────────────────────────────────
-- 3. OBRASOC — Catálogo de Obras Sociales
-- ─────────────────────────────────────────────────────────────────────────────
DROP TABLE IF EXISTS `obrasoc`;
CREATE TABLE `obrasoc` (
  `TATIPO`      char(3)       DEFAULT NULL,
  `TACODIGO`    char(8)       DEFAULT NULL COMMENT 'Código de la O.S.',
  `TADESCRIP`   char(30)      DEFAULT NULL COMMENT 'Nombre / descripción',
  `TACANTVENC`  int(11)       DEFAULT NULL,
  `TAIMPORTE`   decimal(12,2) DEFAULT NULL,
  `TAVALCAPIT`  decimal(10,2) DEFAULT NULL,
  `TACANTAFIL`  decimal(12,0) DEFAULT NULL,
  `TAFACOSEG`   char(1)       DEFAULT NULL,
  `TACOSEGPRA`  decimal(10,2) DEFAULT NULL,
  `TACOSEGCON`  decimal(10,2) DEFAULT NULL,
  `TACUIT`      char(13)      DEFAULT NULL,
  `TADIR`       char(40)      DEFAULT NULL,
  `TATELEF`     char(30)      DEFAULT NULL,
  `TACONTACTO`  char(30)      DEFAULT NULL,
  `TACP`        char(10)      DEFAULT NULL,
  `TACONDIVA`   char(15)      DEFAULT NULL,
  `TAPADRON`    char(80)      DEFAULT NULL,
  `TACOSEGDET`  decimal(10,2) DEFAULT NULL,
  `TAINTERCON`  char(10)      DEFAULT NULL,
  `TAREFACT`    decimal(10,2) DEFAULT NULL,
  `TAFUERAMED`  char(1)       DEFAULT NULL,
  `TAFUERACAP`  char(1)       DEFAULT NULL,
  `TAEMPRESA`   char(8)       DEFAULT NULL,
  `TANOMEMP`    char(40)      DEFAULT NULL,
  `TAFECHAINI`  datetime      DEFAULT NULL,
  `TAFECHAFIN`  datetime      DEFAULT NULL COMMENT 'Fecha fin vigencia. NULL o futuro = vigente',
  `TACOPIA`     char(1)       DEFAULT NULL,
  `TARETIRADO`  char(1)       DEFAULT NULL,
  `TADERIVAC`   char(1)       DEFAULT NULL,
  `TAAGRUPA`    char(8)       DEFAULT NULL,
  `XPREST`      char(1)       DEFAULT NULL,
  `TAEMAIL`     char(150)     DEFAULT NULL,
  `TAMESAOP`    char(2)       DEFAULT NULL,
  `GRUPO_OS`    char(2)       DEFAULT NULL,
  `TAEXCCAR`    char(1)       DEFAULT NULL,
  `TAMAILCAR`   char(150)     DEFAULT NULL,
  `ISVERPLAN`   char(1)       DEFAULT NULL,
  `CONVADM`     char(1)       DEFAULT NULL,
  `id_pk`       int(11)       NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id_pk`),
  KEY `idx_tacodigo` (`TACODIGO`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Catálogo de Obras Sociales - migrado de VFP (obrasoc.dbf)';

-- ─────────────────────────────────────────────────────────────────────────────
-- 4. NOVEDADE — Log de operaciones sobre prestadores/OS
--    (tabla nueva, no existía en VFP migración)
-- ─────────────────────────────────────────────────────────────────────────────
DROP TABLE IF EXISTS `novedade`;
CREATE TABLE `novedade` (
  `id`         int(11)       NOT NULL AUTO_INCREMENT,
  `operacion`  varchar(20)   NOT NULL COMMENT 'ALTA / BAJA / MODIF',
  `fecha`      datetime      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `usuario`    varchar(50)   DEFAULT NULL,
  `codigo`     char(11)      DEFAULT NULL COMMENT 'CODIGO del prestador (ebamp.CODIGO)',
  `cosoc`      char(8)       DEFAULT NULL COMMENT 'Código de la O.S. si aplica',
  `detalle`    varchar(250)  DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_codigo`  (`codigo`),
  KEY `idx_cosoc`   (`cosoc`),
  KEY `idx_fecha`   (`fecha`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Historial de novedades/operaciones del módulo Prestadores';

-- ─────────────────────────────────────────────────────────────────────────────
-- 5. DATOS DE MUESTRA (borrar antes de cargar datos reales)
--    Suficientes para probar la grilla y el modal OS
-- ─────────────────────────────────────────────────────────────────────────────

-- Obras Sociales (catálogo)
INSERT INTO `obrasoc` (`TACODIGO`,`TADESCRIP`,`TAFECHAFIN`,`TAEMPRESA`) VALUES
('OSDE',   'O.S. DE EMPLEADOS',                '2099-12-31','COME'),
('IOMA',   'I.O.M.A.',                          '2099-12-31','COME'),
('OSPAT',  'O.S. DEL PERSONAL DE ACCION SOCIAL','2099-12-31','COME'),
('OSECAC', 'O.S. EMPLEADOS DE COMERCIO',        '2099-12-31','COME'),
('SWISS',  'SWISS MEDICAL',                     '2099-12-31','COME'),
('PAMI',   'P.A.M.I.',                          '2099-12-31','COME'),
('MEDIFE', 'MEDIFE',                            '2099-12-31','COME'),
('OSSEG',  'O.S. DE SEGUROS',                   '2032-12-31','COME'),
('VENCIDA','O.S. VENCIDA (PRUEBA)',             '2020-01-01','COME');

-- Prestadores (ebamp)
INSERT INTO `ebamp`
  (`CODIGO`,`MATRICULA`,`NOMBRE`,`CUIT`,`DIRECC`,`LOCALIDAD`,`ZONA`,`CATEG`,
   `TELCONS`,`TRABAJAOS`,`TIENEPREST`,`TIENESUC`,`FECHAALTA`,`FECHABAJA`,`BAJA`,
   `EMPRESA`,`CONDIVA`,`LEGAJO`)
VALUES
('A0000001','45710',  'AROCENA LUCIA CRISTINA',           '27-06365216-9','LISANDRO DE LA TORRE 4122','CASEROS',    'GBA - NOROESTE','CP',  '4750-2819','1','1','1','2004-08-01',NULL,          NULL,'COME','MONOTRIB',10025),
('A0000002','48200',  'ABRAMZON INGRID DEBORA',           '27-28765000-2','PARAGUAY 4564 3P C',        'CAPITAL FEDERAL','PALERMO',       'CP',  '4774-0938','1','1','0','2009-11-01',NULL,          NULL,'COME','MONOTRIB',10026),
('A0000003','57311',  'ANTONUCCI PASEO M. VERONICA',      '27-12978500-3','CUENCA 3446 PISO 1 DTO','VILLA DEL PARQUE','CAPITAL FEDERAL','CP',  NULL,       '1','0','1','2009-11-17',NULL,          NULL,'COME','MONOTRIB',10027),
('A0000050','00001',  'AMBULANCIAS UCICOM',               '30-50000000-1','',                          'BUENOS AIRES',   'TANDIL',        'SAN', '0249-445107','0','1','1','2019-06-07',NULL,        NULL,'COME','RI',     20001),
('A0000051','00002',  'AMBULANCIAS AMBAR',                '30-50000001-2','',                          'LA PLATA',       'LA PLATA',      'SAN', '0221-4524823','0','1','0','2019-06-07',NULL,       NULL,'COME','RI',     20002),
('A0000064','00064',  'ASARFARMA S.A.',                   '30-61385995-8','14 DE JULIO 178',           'CAPITAL FEDERAL','CHACARITA',     'FARM','1132859556','1','1','1','2024-03-25',NULL,         NULL,'COME','RI',     20064),
('A0000070','CLI069', 'AMBULANCIAS EMERMED',              '30-70000000-3','RECONQUISTA 1016 9P',       'CAPITAL FEDERAL','ALMAGRO',       'CLI', '4138-6000', '1','1','1','2015-03-13',NULL,         NULL,'COME','RI',     20070),
('A0000080','00080',  'BIOROSI LABORATORIOS ACREDITADOS', '30-80000000-4','ESPORA 598',                'ADROGUE',        'GBA - SUR',     'LAB', '4224-3679', '1','1','1','2023-07-22',NULL,         NULL,'COME','RI',     20080),
('A9998001','00801',  'PRESTADOR DADO DE BAJA (PRUEBA)',  '20-99980010-5','AV EJEMPLO 999',            'CAPITAL FEDERAL','FLORES',        'CP',  '4999-9999', '0','0','0','2010-01-01','2023-06-30','T','COME','MONOTRIB',99801);

-- Obras Sociales del prestador A0000001
INSERT INTO `obramed` (`OBRASOC`,`MEDICO`,`NOMOBRA`,`FECHAALTA`,`FECHABAJA`,`EMPRESA`,`EXCEP`,`EXCLUCART`,`EXCLUCALL`) VALUES
('OSDE',   'A0000001','O.S. DE EMPLEADOS',        '2017-07-26','2099-12-31','COME','N','N','N'),
('IOMA',   'A0000001','I.O.M.A.',                 '2013-01-01','2099-12-31','COME','N','N','N'),
('SWISS',  'A0000001','SWISS MEDICAL',            '2024-03-01','2099-12-31','COME','N','N','N'),
('OSPAT',  'A0000001','O.S. ACCION SOCIAL',       '2019-09-14','2023-05-28','COME','N','N','N'), -- dada de baja
('OSSEG',  'A0000001','O.S. DE SEGUROS',          '2013-01-01','2099-12-31','COME','S','N','N'); -- con excepción

-- Obras Sociales del prestador A0000002
INSERT INTO `obramed` (`OBRASOC`,`MEDICO`,`NOMOBRA`,`FECHAALTA`,`FECHABAJA`,`EMPRESA`,`EXCEP`,`EXCLUCART`,`EXCLUCALL`) VALUES
('PAMI',   'A0000002','P.A.M.I.',           '2009-11-01','2099-12-31','COME','N','N','N'),
('MEDIFE', 'A0000002','MEDIFE',             '2015-06-01','2099-12-31','COME','N','N','N');

SET FOREIGN_KEY_CHECKS = 1;

-- ─────────────────────────────────────────────────────────────────────────────
-- FIN DEL SCRIPT
-- ─────────────────────────────────────────────────────────────────────────────
-- Verificar resultado:
--   SELECT COUNT(*) FROM ebamp;
--   SELECT COUNT(*) FROM obramed;
--   SELECT COUNT(*) FROM obrasoc;
--   SELECT COUNT(*) FROM novedade;
-- ─────────────────────────────────────────────────────────────────────────────
