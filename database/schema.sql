-- ============================================================
-- INETAM - Sistema de Certificados y Diplomas
-- Base de Datos MySQL
-- ============================================================

CREATE DATABASE IF NOT EXISTS inetam_certificados
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE inetam_certificados;

-- Tabla principal de estudiantes y diplomas
CREATE TABLE IF NOT EXISTS estudiantes (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  primer_nombre   VARCHAR(60)  NOT NULL,
  segundo_nombre  VARCHAR(60)  NULL,
  primer_apellido VARCHAR(60)  NOT NULL,
  segundo_apellido VARCHAR(60) NULL,
  numero_documento VARCHAR(20) NOT NULL UNIQUE,
  titulo          ENUM(
    'Bachiller Académico',
    'Bachiller Técnico',
    'Bachiller Técnico en TIC'
  ) NOT NULL,
  especialidad    ENUM(
    'Sin Especialidad',
    'Agropecuario',
    'Énfasis Programación'
  ) NOT NULL DEFAULT 'Sin Especialidad',
  -- Datos del Acta
  dia_acta        TINYINT UNSIGNED NOT NULL COMMENT '1-31',
  mes_acta        TINYINT UNSIGNED NOT NULL COMMENT '1-12',
  anio_acta       YEAR             NOT NULL,
  -- Datos del Diploma
  libro           VARCHAR(10)  NOT NULL,
  folio           VARCHAR(10)  NOT NULL,
  numero_diploma  VARCHAR(20)  NOT NULL,
  -- Metadatos
  created_at      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  updated_at      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Índices útiles para búsquedas
CREATE INDEX idx_documento   ON estudiantes (numero_documento);
CREATE INDEX idx_titulo       ON estudiantes (titulo);
CREATE INDEX idx_anio_acta    ON estudiantes (anio_acta);

-- Tabla de configuración institucional (para logo, rector, secretaria, etc.)
CREATE TABLE IF NOT EXISTS configuracion (
  clave  VARCHAR(60)  PRIMARY KEY,
  valor  TEXT         NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO configuracion (clave, valor) VALUES
  ('nombre_institucion',  'Institución Educativa Técnica Agropecuaria y Minera'),
  ('municipio',           'San Martín de Loba, Bolívar'),
  ('resolucion_aprobacion','Resolución Nº. 341 del 4/12/2003'),
  ('nit',                 '806.012.943-6'),
  ('dane',                '113667000016'),
  ('rector',              'Esp. JULIO CESAR ESCALANTE TEJADA'),
  ('secretaria',          'Lic. TERCILIA CENTENO LONDOÑO'),
  ('cc_rector',           '73.089.211 de Cartagena'),
  ('cc_secretaria',       '23.106.000 de Sn Martín de Loba'),
  ('num_acta_general',    '44')
ON DUPLICATE KEY UPDATE valor = VALUES(valor);

-- Datos de ejemplo
INSERT INTO estudiantes
  (primer_nombre, segundo_nombre, primer_apellido, segundo_apellido, numero_documento,
   titulo, especialidad, dia_acta, mes_acta, anio_acta, libro, folio, numero_diploma)
VALUES
  ('Katherin','Alexa','Ardila','Limas','1085050020',
   'Bachiller Técnico','Agropecuario',12,12,2025,'18','228','2028'),
  ('María','Fernanda','Alzate','Gaviria','1047008229',
   'Bachiller Académico','Sin Especialidad',12,12,2025,'18','229','2029'),
  ('Gabriela',NULL,'Angulo','Amaris','1050066098',
   'Bachiller Técnico en TIC','Énfasis Programación',12,12,2025,'18','230','2030');
