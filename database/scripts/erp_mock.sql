-- =====================================================================
-- Mock local del ERP (solo para pruebas)
-- No toca ERP_TAOSA. Vive en MySQL local: erp_taosa_mock
--
-- Replica las tablas que usa el flujo de empaque:
--   Produccion_EmpaquesDiariaG                (encabezado del documento)
--   Produccion_EmpaquesDiariaG_Actividades    (actividades del documento)
--   Produccion_EmpaquesDiariaG_Detalles       (detalle: puros + horas)
--   Produccion_OrdenTrabajo                   (ordenes)
--   Produccion_OrdenTrabajoPlaneacionEmpaques (planeacion)
--   ProduccionActividadesTabaco               (catalogo de actividades)
-- =====================================================================

CREATE DATABASE IF NOT EXISTS erp_taosa_mock
  DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE erp_taosa_mock;

DROP TABLE IF EXISTS Produccion_EmpaquesDiariaG_Detalles;
DROP TABLE IF EXISTS Produccion_EmpaquesDiariaG_Actividades;
DROP TABLE IF EXISTS Produccion_EmpaquesDiariaG;
DROP TABLE IF EXISTS Produccion_OrdenTrabajoPlaneacionEmpaques;
DROP TABLE IF EXISTS Produccion_OrdenTrabajo;
DROP TABLE IF EXISTS ProduccionActividadesTabaco;

-- ---------------------------------------------------------------- ordenes
CREATE TABLE Produccion_OrdenTrabajo (
  CodigoSucursal        INT          NOT NULL,
  Numero_de_Documento   VARCHAR(20)  NOT NULL,
  Fecha                 DATETIME     NULL,
  FechaEntrega          DATETIME     NULL,
  Codigo_Cliente        VARCHAR(20)  NULL,
  Nombre_del_Cliente    VARCHAR(120) NULL,
  CodigoSucursalProviene INT         NULL,
  TipoDocProviene       VARCHAR(20)  NULL,
  NumDocProviene        VARCHAR(20)  NULL,
  Estado                INT          NULL,
  Cerrada               TINYINT      NOT NULL DEFAULT 0,
  Observaciones         VARCHAR(255) NULL,
  Anulado               TINYINT      NOT NULL DEFAULT 0,
  PRIMARY KEY (CodigoSucursal, Numero_de_Documento)
) ENGINE=InnoDB;

-- --------------------------------------------------------------- encabezado
CREATE TABLE Produccion_EmpaquesDiariaG (
  CodigoSucursal       INT          NOT NULL,
  Numero_de_Documento  VARCHAR(20)  NOT NULL,
  Fecha                DATETIME     NOT NULL,
  CodigoArea           INT          NULL,
  NombreArea           VARCHAR(100) NULL,
  Aplicada             TINYINT      NOT NULL DEFAULT 0,
  Observaciones        VARCHAR(255) NULL,
  AplicaControlCalidad TINYINT      NOT NULL DEFAULT 0,
  Anulado              TINYINT      NOT NULL DEFAULT 0,
  PRIMARY KEY (CodigoSucursal, Numero_de_Documento)
) ENGINE=InnoDB;

-- ------------------------------------------------------------- actividades
CREATE TABLE Produccion_EmpaquesDiariaG_Actividades (
  CodigoSucursal      INT         NOT NULL,
  Numero_de_Documento VARCHAR(20) NOT NULL,
  CodigoActividad     INT         NOT NULL,
  FormaPagoManoObra   TINYINT     NULL,
  Bodega              INT         NULL,
  BodegaMaterial      INT         NULL,
  isUltimaActividad   TINYINT     NOT NULL DEFAULT 0,
  PRIMARY KEY (CodigoSucursal, Numero_de_Documento, CodigoActividad)
) ENGINE=InnoDB;

-- ----------------------------------------------------------------- detalle
-- PK REAL del ERP (6 columnas). No incluye viÃ±eta ni producto: por eso el
-- sistema acumula los puros y las horas cuando se registra otra viÃ±eta con
-- la misma llave.
CREATE TABLE Produccion_EmpaquesDiariaG_Detalles (
  CodigoSucursal         INT           NOT NULL,
  Numero_de_Documento    VARCHAR(20)   NOT NULL,
  CodigoActividad        INT           NOT NULL,
  Numero_de_Orden        VARCHAR(20)   NOT NULL,
  CodigoEmpleado         VARCHAR(20)   NOT NULL,
  Numero                 INT           NOT NULL,
  Bodega                 INT           NULL,
  Codigo_Producto        VARCHAR(50)   NULL,
  Nombre_Empleado        VARCHAR(120)  NULL,
  CantidadTrabajadas     DECIMAL(18,4) NOT NULL DEFAULT 0,
  CantidadDefectuosa     DECIMAL(18,4) NOT NULL DEFAULT 0,
  CantidadPagadas        DECIMAL(18,4) NOT NULL DEFAULT 0,
  Precio_Unitario        DECIMAL(18,4) NOT NULL DEFAULT 0,
  CantidadControlCalidad DECIMAL(18,4) NOT NULL DEFAULT 0,
  isComplemento          TINYINT       NOT NULL DEFAULT 0,
  HorasTrabajadas        DECIMAL(18,4) NOT NULL DEFAULT 0,
  CostosIndirectosVarios DECIMAL(18,4) NOT NULL DEFAULT 0,
  IsEmpleadoIndirecto    TINYINT       NOT NULL DEFAULT 0,
  HorasExtrasTrabajadas  DECIMAL(18,4) NOT NULL DEFAULT 0,
  MontoGastosMOD         DECIMAL(18,4) NOT NULL DEFAULT 0,
  SalarioMensual         DECIMAL(18,4) NOT NULL DEFAULT 0,
  CostosIndirectosDepreciacion DECIMAL(18,4) NOT NULL DEFAULT 0,
  CodigoTipoEmpaque      INT           NULL,
  CostosSuministros      DECIMAL(18,4) NOT NULL DEFAULT 0,
  ManoObraIndirectaPror  DECIMAL(18,4) NOT NULL DEFAULT 0,
  CostosSuministros_Dol  DECIMAL(18,4) NOT NULL DEFAULT 0,
  CodigoSKU              VARCHAR(50)   NULL,
  PRIMARY KEY (CodigoSucursal, Numero_de_Documento, CodigoActividad,
               Numero_de_Orden, CodigoEmpleado, Numero)
) ENGINE=InnoDB;

-- --------------------------------------------------------------- planeacion
CREATE TABLE Produccion_OrdenTrabajoPlaneacionEmpaques (
  CodigoSucursal          INT           NOT NULL,
  Numero_de_Documento     VARCHAR(20)   NOT NULL,
  Numero                  INT           NOT NULL,
  Fecha                   DATETIME      NULL,
  CodigoActividad         INT           NOT NULL,
  CantidadAProducir       DECIMAL(18,4) NOT NULL DEFAULT 0,
  CantidadProducida       DECIMAL(18,4) NOT NULL DEFAULT 0,
  PersonalNecesario       INT           NOT NULL DEFAULT 0,
  CodigoActividadRequerida INT          NOT NULL DEFAULT -1,
  isUltimaActividad       TINYINT       NOT NULL DEFAULT 0,
  CantidadComplementos    DECIMAL(18,4) NOT NULL DEFAULT 0,
  PRIMARY KEY (CodigoSucursal, Numero_de_Documento, Numero, CodigoActividad)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------- catalogo
CREATE TABLE ProduccionActividadesTabaco (
  CodigoActividad              INT          NOT NULL,
  NombreActividad              VARCHAR(120) NOT NULL,
  isSumaUnidades               TINYINT      NOT NULL DEFAULT 0,
  CantidadPago                 VARCHAR(20)  NULL,
  isCantidadLibrasHabilitada   TINYINT      NOT NULL DEFAULT 0,
  isCantidadOnzasHabilitada    TINYINT      NOT NULL DEFAULT 0,
  isCantidadTotalLibrasHabilitada TINYINT   NOT NULL DEFAULT 0,
  isCantidadHoraHabilitada     TINYINT      NOT NULL DEFAULT 0,
  isCantidadFisicaHabilitada   TINYINT      NOT NULL DEFAULT 0,
  isPrecioUnitHabilitado       TINYINT      NOT NULL DEFAULT 0,
  isPrecioTotalHabilitado      TINYINT      NOT NULL DEFAULT 0,
  isCantidadxClaseHabilitada   TINYINT      NOT NULL DEFAULT 0,
  isPermitirProrrateo          TINYINT      NOT NULL DEFAULT 0,
  PRIMARY KEY (CodigoActividad)
) ENGINE=InnoDB;

-- =====================================================================
-- Datos de ejemplo (parecidos a los documentos reales del 19/10/2026)
-- =====================================================================

INSERT INTO ProduccionActividadesTabaco
  (CodigoActividad, NombreActividad, isCantidadHoraHabilitada, isCantidadFisicaHabilitada,
   isCantidadLibrasHabilitada, isPrecioUnitHabilitado, CantidadPago)
VALUES
  (1,   'Despalillo por Horas',   1,0,1,0,'HORAS'),
  (2,   'Escogida por Libras',    1,0,1,1,'LIBRAS'),
  (3,   'Despalillo por Libras',  1,0,1,1,'LIBRAS'),
  (4,   'Rezago por Libras',      1,0,1,1,'LIBRAS'),
  (103, 'Limpieza de Puros',      0,1,0,0,'PUROS'),
  (125, 'Rezago Empaque',         0,1,0,0,'PUROS'),
  (126, 'Anillado y Celofanado',  0,1,0,0,'PUROS'),
  (132, 'Llenado de Cajas',       0,1,0,0,'PUROS'),
  (133, 'Anillado de Puros',      0,1,0,0,'PUROS'),
  (134, 'Celofanado',             0,1,0,0,'PUROS'),
  (135, 'Rezagado Empaque',       0,1,0,0,'PUROS'),
  (136, 'Llenado de Paquetes',    0,1,0,0,'PUROS'),
  (141, 'Prensado',               0,1,0,0,'PUROS'),
  (145, 'Empacado',               0,1,0,0,'PUROS'),
  (149, 'Llenado Display',        0,1,0,0,'PUROS'),
  (151, 'Sellado',                0,1,0,0,'PUROS'),
  (152, 'Empacado Paquetes',      0,1,0,0,'PUROS'),
  (157, 'Llenado de Cajas 1/20',  0,1,0,0,'PUROS'),
  (158, 'Sellado de Cajas',       0,1,0,0,'PUROS'),
  (171, 'Rolado Empaque',         0,1,0,0,'PUROS');

-- ordenes de ejemplo
INSERT INTO Produccion_OrdenTrabajo
  (CodigoSucursal, Numero_de_Documento, Fecha, Codigo_Cliente, Nombre_del_Cliente, Estado, Cerrada, Anulado)
VALUES
  (2, '0000003593', '2026-10-10', 'C-001', 'CLIENTE DEMO UNO', 1, 0, 0, '115398'),
  (2, '0000003597', '2026-10-10', 'C-002', 'CLIENTE DEMO DOS', 1, 0, 0, '115399'),
  (2, '0000007262', '2026-10-12', 'C-003', 'CLIENTE DEMO TRES', 1, 0, 0, '111395'),
  (1, '0000007226', '2026-10-12', 'C-004', 'CLIENTE DEMO CUATRO', 1, 0, 0, '111394');

-- documentos del dia
INSERT INTO Produccion_EmpaquesDiariaG
  (CodigoSucursal, Numero_de_Documento, Fecha, CodigoArea, NombreArea, Aplicada, Observaciones, Anulado)
VALUES
  (2, '0000011085', '2026-10-19 00:00:00', 4, 'Empaque(Permamente)', 0, 'REGISTRO DE REZAGADO DE PUROS EN EL DEPTO. DE EMPAQUE', 0),
  (2, '0000011086', '2026-10-19 00:00:00', 4, 'Empaque(Permamente)', 0, 'REGISTRO DE ANILLADO DE PUROS EN EL DEPTO. DE EMPAQUE', 0),
  (2, '0000011092', '2026-10-19 00:00:00', 4, 'Empaque(Permamente)', 0, 'REGISTRO DE LIMPIEZA DE PUROS EN EL DEPTO. DE EMPAQUE', 0),
  (1, '0000005380', '2026-10-19 00:00:00', 4, 'Empaque(Permamente)', 0, 'REGISTRO DE PUROS ANILLADOS Y CELOFANADOS EN EL DEPTO DE EMPAQUE', 0);

INSERT INTO Produccion_EmpaquesDiariaG_Actividades
  (CodigoSucursal, Numero_de_Documento, CodigoActividad, FormaPagoManoObra, Bodega, BodegaMaterial, isUltimaActividad)
VALUES
  (2, '0000011085', 2,   2, 218, 218, 0),
  (2, '0000011085', 141, 2, 218, 218, 0),
  (2, '0000011086', 133, 2, 218, 218, 0),
  (2, '0000011086', 135, 2, 218, 218, 0),
  (2, '0000011092', 103, 2, 218, 218, 0),
  (1, '0000005380', 3,   2, 218, 218, 0),
  (1, '0000005380', 4,   2, 218, 218, 0),
  (1, '0000005380', 126, 2, 218, 218, 0);

-- detalle (una fila por llave de 6 columnas)
INSERT INTO Produccion_EmpaquesDiariaG_Detalles
  (CodigoSucursal, Numero_de_Documento, CodigoActividad, Numero_de_Orden, CodigoEmpleado, Numero,
   Bodega, Codigo_Producto, Nombre_Empleado, CantidadTrabajadas, CantidadPagadas, CantidadControlCalidad,
   HorasTrabajadas, CodigoTipoEmpaque, CodigoSKU)
VALUES
  (2, '0000011085', 2,   '0000003593', '1128', 1, 218, 'P-0001', 'CALDERON GRISELDA DEL CARMEN', 1250, 1250, 1250, 5.00, 4, '47604630'),
  (2, '0000011085', 2,   '0000003593', '3985', 1, 218, 'P-0001', 'RUGAMA MARTHA FIDELIA',       2000, 2000, 2000, 0.00, 4, '47604630'),
  (2, '0000011085', 2,   '0000003597', '1282', 1, 218, 'P-0002', 'PALMA MARTINEZ ANDREA MARIA',  60,   60,   60, 0.00, 4, '47801428'),
  (2, '0000011085', 141, '0000003593', '1128', 1, 218, 'P-0001', 'CALDERON GRISELDA DEL CARMEN', 1000, 1000, 1000, 4.25, 4, '47604630'),
  (2, '0000011086', 133, '0000003593', '1128', 1, 218, 'P-0001', 'CALDERON GRISELDA DEL CARMEN', 1200, 1200, 1200, 8.50, 4, '47604630'),
  (2, '0000011086', 133, '0000003593', '3985', 1, 218, 'P-0001', 'RUGAMA MARTHA FIDELIA',       1100, 1100, 1100, 7.75, 4, '47604630'),
  (2, '0000011086', 135, '0000003597', '1282', 1, 218, 'P-0002', 'PALMA MARTINEZ ANDREA MARIA',  600,  600,  600, 4.00, 4, '47801428'),
  (2, '0000011092', 103, '0000003593', '1128', 1, 218, 'P-0001', 'CALDERON GRISELDA DEL CARMEN', 3400, 3400, 3400, 9.50, 4, '47604630'),
  (1, '0000005380', 3,   '0000007226', '8466', 1, 218, 'P-0003', 'MENDOZA MIDENCE ANDREA YAQUE',  360,  360,  360, 0.00, 4, 'NSR-PM-1004'),
  (1, '0000005380', 3,   '0000007226', '8670', 1, 218, 'P-0003', 'MIRANDA GARCIA ELBA YANELI',    340,  340,  340, 0.00, 4, 'NSR-PM-1004'),
  (1, '0000005380', 4,   '0000007262', '6659', 1, 218, 'P-0004', 'CACERES VASQUEZ ISIS PAMELA',   960,  960,  960, 0.00, 4, 'NSR-PM-1000'),
  (1, '0000005380', 126, '0000007132', '8488', 1, 218, 'P-0005', 'AMADOR AVILA MAIVELY LETICIA',  230,  230,  230, 5.25, 4, 'TE-CC-2001');

INSERT INTO Produccion_OrdenTrabajoPlaneacionEmpaques
  (CodigoSucursal, Numero_de_Documento, Numero, Fecha, CodigoActividad, CantidadAProducir, isUltimaActividad)
VALUES
  (2, '0000003593', 1, '2026-10-19 00:00:00', 2, 1, 0),
  (2, '0000003597', 1, '2026-10-19 00:00:00', 2, 1, 0),
  (1, '0000007226', 1, '2026-10-19 00:00:00', 3, 1, 0);

-- =====================================================================
-- Tablas usadas por los lookups del registro (tambien necesarias)
-- =====================================================================

DROP TABLE IF EXISTS Produccion_ActEmpaquesBodega;
DROP TABLE IF EXISTS Facturas_Detalles;
DROP TABLE IF EXISTS Inventario_SKU;

CREATE TABLE Produccion_ActEmpaquesBodega (
  CodigoSucursal  INT NOT NULL,
  CodigoActividad INT NOT NULL,
  Bodega          INT NOT NULL,
  PRIMARY KEY (CodigoSucursal, CodigoActividad)
) ENGINE=InnoDB;

CREATE TABLE Facturas_Detalles (
  CodigoSucursal      INT         NOT NULL,
  Numero_de_Documento VARCHAR(20) NOT NULL,
  Numero              INT         NOT NULL,
  CodigoSKU           VARCHAR(50) NULL,
  Codigo_Producto     VARCHAR(50) NULL,
  NumeroOrden         VARCHAR(20) NULL,
  PRIMARY KEY (CodigoSucursal, Numero_de_Documento, Numero)
) ENGINE=InnoDB;

CREATE TABLE Inventario_SKU (
  CodigoSKU         VARCHAR(50) NOT NULL,
  CodigoTipoEmpaque INT         NULL,
  PRIMARY KEY (CodigoSKU)
) ENGINE=InnoDB;

INSERT INTO Produccion_ActEmpaquesBodega (CodigoSucursal, CodigoActividad, Bodega) VALUES
 (1,3,218),(1,4,218),(1,126,218),(2,2,218),(2,103,218),(2,133,218),(2,135,218),(2,141,218);

INSERT INTO Facturas_Detalles (CodigoSucursal, Numero_de_Documento, Numero, CodigoSKU, Codigo_Producto, NumeroOrden) VALUES
 (2,'115398',1,'47604630','P-0001','115398'),
 (2,'115399',2,'47801428','P-0002','115399'),
 (1,'111394',3,'NSR-PM-1004','P-0003','111394'),
 (1,'111395',4,'NSR-PM-1000','P-0004','111395');

INSERT INTO Inventario_SKU (CodigoSKU, CodigoTipoEmpaque) VALUES
 ('47604630',4),('47801428',4),('NSR-PM-1004',4),('NSR-PM-1000',4),('TE-CC-2001',4);



