-- ============================================================
-- Actividades IGNORADAS en la campanita de fichas de empaque
-- Base de datos: facturuacion_empaque_taopar  (MySQL)
-- Ejecutar en el servidor (phpMyAdmin o consola mysql).
-- ============================================================

CREATE TABLE IF NOT EXISTS `ficha_actividades_ignoradas` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `codigo_actividad` INT UNSIGNED NOT NULL,
  `nombre` VARCHAR(191) DEFAULT NULL,
  `created_by` BIGINT UNSIGNED DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ficha_actividades_ignoradas_codigo_actividad_unique` (`codigo_actividad`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Rezagado (codigo 2) ignorado por defecto.
INSERT IGNORE INTO `ficha_actividades_ignoradas`
  (`codigo_actividad`, `nombre`, `created_by`, `created_at`, `updated_at`)
VALUES
  (2, 'Rezagado', NULL, NOW(), NOW());


-- ============================================================
-- Consultas utiles
-- ============================================================

-- Ver lo que esta ignorado
-- SELECT * FROM `ficha_actividades_ignoradas` ORDER BY `codigo_actividad`;

-- Ignorar otra actividad (ej. Limpieza = 3)
-- INSERT IGNORE INTO `ficha_actividades_ignoradas`
--   (`codigo_actividad`, `nombre`, `created_at`, `updated_at`)
-- VALUES (3, 'Limpieza', NOW(), NOW());

-- Volver a mostrarla
-- DELETE FROM `ficha_actividades_ignoradas` WHERE `codigo_actividad` = 3;

-- Vaciar toda la lista (no se ignora nada)
-- DELETE FROM `ficha_actividades_ignoradas`;
