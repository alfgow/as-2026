/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

CREATE TABLE `arrendadores` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre_arrendador` varchar(100) NOT NULL,
  `nombre_representante` varchar(100) DEFAULT NULL,
  `email` varchar(50) NOT NULL,
  `device_id` varchar(255) DEFAULT NULL,
  `celular` varchar(20) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `direccion_arrendador` text,
  `estadocivil` varchar(30) DEFAULT NULL,
  `rfc` varchar(15) DEFAULT NULL,
  `id_asesor` int DEFAULT NULL,
  `nacionalidad` varchar(60) DEFAULT NULL,
  `tipo_id` varchar(20) DEFAULT NULL,
  `num_id` varchar(20) DEFAULT NULL,
  `banco` varchar(30) DEFAULT NULL,
  `cuenta` varchar(30) DEFAULT NULL,
  `clabe` varchar(18) DEFAULT NULL,
  `terminos_condiciones` varchar(50) DEFAULT 'He leído y acepto los Términos y Condiciones',
  `ip` varchar(45) DEFAULT NULL,
  `fecha_registro` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `comentarios` text,
  `estatus` varchar(10) DEFAULT NULL,
  `slug` varchar(120) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ux_arrendadores_slug` (`slug`),
  KEY `id_asesor` (`id_asesor`)
) ENGINE=InnoDB AUTO_INCREMENT=564 DEFAULT CHARSET=utf8mb3;

CREATE TABLE `arrendadores_archivos` (
  `id_archivo` int NOT NULL AUTO_INCREMENT,
  `id_arrendador` int NOT NULL,
  `s3_key` varchar(255) NOT NULL,
  `tipo` enum('selfie','identificacion_frontal','identificacion_reverso','pasaporte','forma_migratoria_frontal','forma_migratoria_reverso','poliza') NOT NULL,
  `fecha_subida` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_archivo`),
  KEY `arrendadores_archivos_ibfk_1` (`id_arrendador`),
  CONSTRAINT `arrendadores_archivos_ibfk_1` FOREIGN KEY (`id_arrendador`) REFERENCES `arrendadores` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=65 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `asesores` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre_asesor` varchar(60) NOT NULL,
  `email` varchar(60) NOT NULL,
  `celular` varchar(20) NOT NULL,
  `telefono` varchar(20) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nombre_asesor` (`nombre_asesor`)
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb3;

CREATE TABLE `blog_posts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `titulo` varchar(255) NOT NULL,
  `contenido` longtext NOT NULL,
  `categoria` varchar(100) NOT NULL,
  `etiquetas` varchar(255) DEFAULT NULL,
  `imagen_key` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `slug` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ux_blog_posts_slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `ia_interacciones` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `usuario_id` int DEFAULT NULL,
  `modelo_key` varchar(20) NOT NULL,
  `modelo_id` varchar(200) NOT NULL,
  `prompt` text NOT NULL,
  `respuesta` longtext,
  `duration_ms` int NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ip` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `contexto` json DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=48 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `inmuebles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_arrendador` int NOT NULL,
  `id_asesor` int NOT NULL,
  `direccion_inmueble` text NOT NULL,
  `tipo` varchar(50) NOT NULL,
  `renta` varchar(30) NOT NULL,
  `mantenimiento` varchar(40) NOT NULL,
  `monto_mantenimiento` varchar(30) NOT NULL,
  `deposito` varchar(40) NOT NULL,
  `estacionamiento` tinyint(1) NOT NULL,
  `mascotas` varchar(2) NOT NULL,
  `comentarios` text NOT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `id_arrendador` (`id_arrendador`),
  KEY `id_asesor` (`id_asesor`)
) ENGINE=InnoDB AUTO_INCREMENT=549 DEFAULT CHARSET=utf8mb3;

CREATE TABLE `inquilinos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_asesor` int NOT NULL,
  `tipo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `nombre_inquilino` varchar(70) NOT NULL,
  `apellidop_inquilino` varchar(70) NOT NULL,
  `apellidom_inquilino` varchar(70) DEFAULT NULL,
  `representante` varchar(70) DEFAULT NULL,
  `estadocivil` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT 'Soltero',
  `rfc` varchar(13) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `curp` varchar(50) DEFAULT NULL,
  `email` varchar(50) NOT NULL,
  `celular` varchar(20) DEFAULT NULL,
  `nacionalidad` varchar(20) NOT NULL,
  `tipo_id` varchar(20) NOT NULL,
  `num_id` varchar(255) DEFAULT NULL,
  `fecha` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `conyuge` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `device_id` varchar(100) DEFAULT NULL,
  `ip` varchar(100) DEFAULT NULL,
  `status` int NOT NULL DEFAULT '1',
  `slug` varchar(100) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_inquilino_asesor` (`id_asesor`),
  CONSTRAINT `fk_inquilino_asesor` FOREIGN KEY (`id_asesor`) REFERENCES `asesores` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1249 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `inquilinos_archivos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_inquilino` int NOT NULL,
  `tipo` enum('selfie','escritura','ine_frontal','ine_reverso','pasaporte','forma_frontal','forma_reverso','pdf','otro','comprobante_ingreso','otro_archivo','validacion_ingresos') NOT NULL,
  `s3_key` varchar(255) NOT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `size` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `inquilino_id` (`id_inquilino`),
  CONSTRAINT `inquilinos_archivos_ibfk_1` FOREIGN KEY (`id_inquilino`) REFERENCES `inquilinos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=618 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `inquilinos_direccion` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_inquilino` int NOT NULL,
  `calle` text,
  `num_exterior` text,
  `num_interior` text,
  `colonia` text,
  `alcaldia` text,
  `ciudad` text,
  `codigo_postal` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `inquilino_id` (`id_inquilino`),
  CONSTRAINT `inquilinos_direccion_ibfk_1` FOREIGN KEY (`id_inquilino`) REFERENCES `inquilinos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1239 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `inquilinos_fiador` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_inquilino` int NOT NULL,
  `calle_inmueble` varchar(255) DEFAULT NULL,
  `num_ext_inmueble` varchar(20) DEFAULT NULL,
  `num_int_inmueble` varchar(20) DEFAULT NULL,
  `colonia_inmueble` varchar(100) DEFAULT NULL,
  `alcaldia_inmueble` varchar(100) DEFAULT NULL,
  `estado_inmueble` varchar(100) DEFAULT NULL,
  `numero_escritura` varchar(100) DEFAULT NULL,
  `numero_notario` varchar(100) DEFAULT NULL,
  `estado_notario` varchar(100) DEFAULT NULL,
  `folio_real` varchar(100) DEFAULT NULL,
  `s3_key` varchar(500) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_fiador_inquilino` (`id_inquilino`),
  CONSTRAINT `fk_fiador_inquilino` FOREIGN KEY (`id_inquilino`) REFERENCES `inquilinos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=68 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `inquilinos_historial_vivienda` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_inquilino` int NOT NULL,
  `renta_actualmente` varchar(4) DEFAULT NULL,
  `arrendador_actual` varchar(70) DEFAULT NULL,
  `cel_arrendador_actual` varchar(20) DEFAULT NULL,
  `monto_renta_actual` varchar(20) DEFAULT NULL,
  `tiempo_habitacion_actual` varchar(20) DEFAULT NULL,
  `motivo_arrendamiento` longtext,
  `vive_actualmente` varchar(264) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `inquilino_id` (`id_inquilino`),
  CONSTRAINT `inquilinos_historial_vivienda_ibfk_1` FOREIGN KEY (`id_inquilino`) REFERENCES `inquilinos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1231 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `inquilinos_trabajo` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_inquilino` int NOT NULL,
  `empresa` varchar(50) NOT NULL,
  `direccion_empresa` text NOT NULL,
  `telefono_empresa` varchar(20) DEFAULT NULL,
  `puesto` varchar(50) NOT NULL,
  `antiguedad` varchar(20) NOT NULL,
  `sueldo` varchar(20) NOT NULL,
  `otrosingresos` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT '0',
  `nombre_jefe` varchar(70) NOT NULL,
  `tel_jefe` varchar(20) DEFAULT NULL,
  `web_empresa` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `inquilino_id` (`id_inquilino`),
  CONSTRAINT `inquilinos_trabajo_ibfk_1` FOREIGN KEY (`id_inquilino`) REFERENCES `inquilinos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1234 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `inquilinos_validaciones` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_inquilino` int NOT NULL,
  `proceso_validacion_documentos` int NOT NULL DEFAULT '2',
  `validacion_documentos_resumen` text,
  `validacion_documentos_json` json DEFAULT NULL,
  `proceso_validacion_archivos` int NOT NULL DEFAULT '2',
  `validacion_archivos_resumen` text,
  `validacion_archivos_json` json DEFAULT NULL,
  `proceso_validacion_rostro` int NOT NULL DEFAULT '2',
  `validacion_rostro_resumen` text,
  `validacion_rostro_json` json DEFAULT NULL,
  `proceso_validacion_id` int NOT NULL DEFAULT '2',
  `validacion_id_resumen` text,
  `validacion_id_json` json DEFAULT NULL,
  `proceso_validacion_ingresos` int NOT NULL DEFAULT '2',
  `validacion_ingresos_resumen` text,
  `validacion_ingresos_json` json DEFAULT NULL,
  `proceso_pago_inicial` int NOT NULL DEFAULT '2',
  `pago_inicial_resumen` text,
  `pago_inicial_json` json DEFAULT NULL,
  `proceso_inv_demandas` int NOT NULL DEFAULT '2',
  `proceso_validacion_verificamex` tinyint(1) NOT NULL DEFAULT '2',
  `verificamex_resumen` varchar(255) DEFAULT NULL,
  `verificamex_json` json DEFAULT NULL,
  `inv_demandas_resumen` text,
  `inv_demandas_json` json DEFAULT NULL,
  `comentarios` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_inq` (`id_inquilino`),
  KEY `fk_inq_idx` (`id_inquilino`),
  CONSTRAINT `fk_inq_validaciones` FOREIGN KEY (`id_inquilino`) REFERENCES `inquilinos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1208 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `inquilinos_validaciones_backup` (
  `id` int NOT NULL DEFAULT '0',
  `id_inquilino` int NOT NULL,
  `proceso_validacion_documentos` int NOT NULL DEFAULT '2',
  `validacion_documentos` text,
  `proceso_pago_inicial` int NOT NULL DEFAULT '2',
  `pago_inicial` text,
  `proceso_validacion_id` int NOT NULL DEFAULT '2',
  `validacion_id` text,
  `proceso_inv_demandas` int NOT NULL DEFAULT '2',
  `inv_demandas` text,
  `proceso_validacion_ingresos` int NOT NULL DEFAULT '2',
  `proceso_validacion_rostro` int NOT NULL DEFAULT '2',
  `validacion_rostro` text,
  `validacion_ingresos` text,
  `comentarios` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `polizas` (
  `id_poliza` int NOT NULL AUTO_INCREMENT,
  `tipo_poliza` varchar(20) NOT NULL,
  `id_asesor` int NOT NULL,
  `id_arrendador` int NOT NULL,
  `id_inquilino` int NOT NULL,
  `id_obligado` int NOT NULL,
  `id_fiador` int NOT NULL,
  `id_inmueble` int NOT NULL,
  `tipo_inmueble` varchar(50) NOT NULL,
  `monto_renta` varchar(100) NOT NULL,
  `monto_poliza` decimal(11,2) NOT NULL,
  `estado` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '1',
  `vigencia` varchar(100) NOT NULL,
  `mes_vencimiento` varchar(40) NOT NULL,
  `year_vencimiento` varchar(20) NOT NULL,
  `usuario` varchar(70) NOT NULL,
  `serie_poliza` int NOT NULL,
  `numero_poliza` int NOT NULL,
  `fecha_poliza` date NOT NULL,
  `fecha_fin` date DEFAULT NULL,
  `periodo` varchar(50) NOT NULL,
  `comentarios` text,
  PRIMARY KEY (`id_poliza`),
  UNIQUE KEY `numero_poliza` (`numero_poliza`),
  KEY `id_arrendador` (`id_arrendador`),
  KEY `id_asesor` (`id_asesor`),
  KEY `id_fiador` (`id_fiador`),
  KEY `id_inmueble` (`id_inmueble`),
  KEY `id_inquilino` (`id_inquilino`),
  KEY `id_obligado` (`id_obligado`),
  KEY `usuario` (`usuario`)
) ENGINE=InnoDB AUTO_INCREMENT=830 DEFAULT CHARSET=utf8mb3;

CREATE TABLE `prospect_update_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `actor_type` enum('inquilino','arrendador') COLLATE utf8mb4_unicode_ci NOT NULL,
  `actor_id` int unsigned NOT NULL,
  `email` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `jti` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `otp` char(6) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `otp_hash` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `token_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `scope` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'self:update',
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `used_by_ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `used_user_agent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_jti` (`jti`),
  KEY `idx_actor` (`actor_type`,`actor_id`),
  KEY `idx_email_expires` (`email`,`expires_at`)
) ENGINE=InnoDB AUTO_INCREMENT=82 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `usuarios2` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre_usuario` varchar(60) NOT NULL,
  `apellidos_usuario` varchar(30) NOT NULL,
  `usuario` varchar(10) NOT NULL,
  `corto_usuario` varchar(30) NOT NULL,
  `mail_usuario` varchar(50) NOT NULL,
  `password` varchar(64) NOT NULL,
  `tipo_usuario` int NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mail_usuario` (`mail_usuario`),
  UNIQUE KEY `usuario` (`usuario`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb3;

CREATE TABLE `validaciones_legal` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_inquilino` bigint unsigned NOT NULL,
  `nombre` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `apellido_p` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `apellido_m` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `curp` varchar(18) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rfc` varchar(13) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `query_usada` json NOT NULL,
  `resultado` json DEFAULT NULL,
  `score_max` tinyint unsigned DEFAULT '0',
  `clasificacion` enum('match_alto','posible_match','sin_evidencia') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'sin_evidencia',
  `status` enum('ok','no_data','manual_required','error') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'no_data',
  `error_message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `searched_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_inquilino` (`id_inquilino`),
  KEY `idx_status` (`status`),
  KEY `idx_fecha` (`searched_at`),
  KEY `idx_clasificacion` (`clasificacion`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `ventasvillanuevagarcia` (
  `id_venta` int NOT NULL AUTO_INCREMENT,
  `id_usuario` int NOT NULL,
  `canal_venta` varchar(30) NOT NULL,
  `concepto_venta` varchar(100) NOT NULL,
  `monto_venta` decimal(11,2) NOT NULL,
  `comision_asesor` decimal(11,2) NOT NULL,
  `ganancia_neta` decimal(11,2) NOT NULL,
  `mes_venta` varchar(20) NOT NULL,
  `year_venta` varchar(5) NOT NULL,
  `fecha_venta` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_venta`)
) ENGINE=InnoDB AUTO_INCREMENT=732 DEFAULT CHARSET=utf8mb3;



/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;