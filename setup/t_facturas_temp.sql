-- setup/t_facturas_temp.sql
-- Clon exacto de REGISTRF + columnas de control para la carga PDF.
-- El traspaso oficial es INSERT INTO registrf (...) SELECT ... FROM t_facturas_temp.

SET NAMES utf8mb4;

DROP TABLE IF EXISTS t_facturas_temp;

CREATE TABLE t_facturas_temp LIKE registrf;

ALTER TABLE t_facturas_temp ADD COLUMN archivo_pdf VARCHAR(255) DEFAULT NULL;
ALTER TABLE t_facturas_temp ADD COLUMN marcado TINYINT(1) DEFAULT 0;
ALTER TABLE t_facturas_temp ADD COLUMN texto_ocr TEXT DEFAULT NULL;

-- registrf.archivo_pdf se agrega en runtime (rfpAsegurarArchivoPdfRegistrf)
-- si todavía no existe, para conservar el PDF confirmado.
