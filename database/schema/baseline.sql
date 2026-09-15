/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `empresa_config` (
  `id` int NOT NULL AUTO_INCREMENT,
  `razon_social` varchar(150) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Farmacia y Servicios Médicos S.A.S.',
  `nit` varchar(30) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '900.123.456-7',
  `direccion` varchar(200) COLLATE utf8mb4_general_ci DEFAULT 'Calle 100 # 15-20, Bogotá, Colombia',
  `telefono` varchar(50) COLLATE utf8mb4_general_ci DEFAULT '(601) 745-8000',
  `email` varchar(100) COLLATE utf8mb4_general_ci DEFAULT 'contacto@farmaciasalud.com.co',
  `logo_url` varchar(255) COLLATE utf8mb4_general_ci DEFAULT 'assets/img/logo_default.png',
  `pie_tiquete` text COLLATE utf8mb4_general_ci,
  `video_turnero_url` varchar(255) COLLATE utf8mb4_general_ci DEFAULT 'https://www.w3schools.com/html/mov_bbb.mp4',
  `marquesina_turnero` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `hora_apertura_atencion` time DEFAULT '07:20:00',
  `hora_apertura_festivos` time DEFAULT '08:00:00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `empresas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `razon_social` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `nit` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `direccion` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `telefono` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `logo_url` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `estado` enum('Activo','Inactivo') COLLATE utf8mb4_general_ci DEFAULT 'Activo',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ingreso_documentos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ingreso_id` int NOT NULL,
  `tipo_documento` varchar(100) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'OTRO',
  `ruta_archivo` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `nombre_original` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ingreso_id` (`ingreso_id`),
  CONSTRAINT `ingreso_documentos_ibfk_1` FOREIGN KEY (`ingreso_id`) REFERENCES `ingresos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=107 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ingresos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `empresa_id` int DEFAULT '1',
  `sede_id` int DEFAULT '1',
  `ticket_numero` varchar(30) COLLATE utf8mb4_general_ci NOT NULL,
  `paciente_id` int NOT NULL,
  `orientador_id` int NOT NULL,
  `fecha_ingreso` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `estado_tramite` varchar(50) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'INGRESADO',
  `locked_by_user_id` int DEFAULT NULL,
  `locked_at` datetime DEFAULT NULL,
  `modulo_entrega_asignado` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `observaciones_pendientes` text COLLATE utf8mb4_general_ci,
  `pdf_transcripcion_url` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `pdf_transcripciones_json` text COLLATE utf8mb4_general_ci,
  `acta_entrega_url` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `firma_paciente_url` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `prioridad` enum('NORMAL','TERCERA_EDAD','EMBARAZADA','DISCAPACIDAD','NIÑO_LACTANTE','OTRO_PREFERENCIAL') COLLATE utf8mb4_general_ci DEFAULT 'NORMAL',
  `prioridad_observacion` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `pdf_alistamiento` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `faltantes_alistamiento` text COLLATE utf8mb4_general_ci,
  `alistado_por_user_id` int DEFAULT NULL,
  `fecha_alistado` datetime DEFAULT NULL,
  `foto_paciente_url` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `persona_reclama` enum('PACIENTE_DIRECTO','TERCERO_ACUDIENTE') COLLATE utf8mb4_general_ci DEFAULT 'PACIENTE_DIRECTO',
  `ips_remite` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `estado_verificacion` enum('PENDIENTE','VERIFICADA','CON_ERRORES') COLLATE utf8mb4_general_ci DEFAULT 'PENDIENTE',
  `verificado_por_user_id` int DEFAULT NULL,
  `fecha_verificacion` datetime DEFAULT NULL,
  `observacion_verificacion` text COLLATE utf8mb4_general_ci,
  `pdf_formula_final_url` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `pdf_validacion_derechos_url` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `pdf_mipres_url` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `contiene_mipres` enum('SI','NO') COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ticket_numero` (`ticket_numero`),
  KEY `paciente_id` (`paciente_id`),
  KEY `orientador_id` (`orientador_id`),
  KEY `locked_by_user_id` (`locked_by_user_id`),
  CONSTRAINT `ingresos_ibfk_1` FOREIGN KEY (`paciente_id`) REFERENCES `pacientes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ingresos_ibfk_2` FOREIGN KEY (`orientador_id`) REFERENCES `usuarios` (`id`),
  CONSTRAINT `ingresos_ibfk_3` FOREIGN KEY (`locked_by_user_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=54 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `locks` (
  `id` int NOT NULL AUTO_INCREMENT,
  `record_id` int NOT NULL,
  `user_id` int NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_record` (`record_id`),
  KEY `idx_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `logs_auditoria` (
  `id` int NOT NULL AUTO_INCREMENT,
  `usuario_id` int DEFAULT NULL,
  `usuario_nombre` varchar(255) DEFAULT NULL,
  `rol_nombre` varchar(100) DEFAULT NULL,
  `modulo` varchar(100) NOT NULL,
  `accion` varchar(100) NOT NULL,
  `registro_id` int DEFAULT NULL,
  `detalles` text,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_usuario` (`usuario_id`),
  KEY `idx_modulo` (`modulo`),
  KEY `idx_accion` (`accion`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `modulos_entrega` (
  `id` int NOT NULL AUTO_INCREMENT,
  `sede_id` int NOT NULL DEFAULT '1',
  `nombre` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `descripcion` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `estado` enum('ACTIVO','INACTIVO') COLLATE utf8mb4_general_ci DEFAULT 'ACTIVO',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_sede_modulo_nombre` (`sede_id`,`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notificaciones` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ingreso_id` int NOT NULL,
  `usuario_destino_id` int NOT NULL,
  `mensaje` text COLLATE utf8mb4_general_ci NOT NULL,
  `leido` tinyint(1) DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ingreso_id` (`ingreso_id`),
  KEY `usuario_destino_id` (`usuario_destino_id`),
  CONSTRAINT `notificaciones_ibfk_1` FOREIGN KEY (`ingreso_id`) REFERENCES `ingresos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `notificaciones_ibfk_2` FOREIGN KEY (`usuario_destino_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pacientes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tipo_documento` enum('CC','CE','PA','TI','RC','PEP','PPT','NV') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'CC',
  `numero_documento` varchar(30) COLLATE utf8mb4_general_ci NOT NULL,
  `fecha_nacimiento` date DEFAULT NULL,
  `nombres` varchar(80) COLLATE utf8mb4_general_ci NOT NULL,
  `apellidos` varchar(80) COLLATE utf8mb4_general_ci NOT NULL,
  `eps_nombre` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `telefono` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `ciudad_expedicion` varchar(100) COLLATE utf8mb4_general_ci DEFAULT 'MEDELLIN-ANT-05001',
  `estado` enum('Activo','Inactivo') COLLATE utf8mb4_general_ci DEFAULT 'Activo',
  `primer_apellido` varchar(80) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `segundo_apellido` varchar(80) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `primer_nombre` varchar(80) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `segundo_nombre` varchar(80) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `pais_nacimiento` varchar(100) COLLATE utf8mb4_general_ci DEFAULT 'COLOMBIA',
  `nacionalidad` varchar(100) COLLATE utf8mb4_general_ci DEFAULT 'COLOMBIANA',
  `ciudad_nacimiento` varchar(100) COLLATE utf8mb4_general_ci DEFAULT 'MEDELLIN-ANT-05001',
  `sexo` enum('Masculino','Femenino','Indeterminado o Intersexual') COLLATE utf8mb4_general_ci DEFAULT 'Masculino',
  `identidad_genero` varchar(80) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `estado_civil` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'Soltero(a)',
  `grupo_sanguineo` varchar(10) COLLATE utf8mb4_general_ci DEFAULT 'O+',
  `sede_atencion` varchar(100) COLLATE utf8mb4_general_ci DEFAULT 'Sede Prado',
  `contacto_emergencia_nombre` varchar(120) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `contacto_emergencia_telefono` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `contacto_emergencia_parentesco` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `grupo_poblacional` varchar(100) COLLATE utf8mb4_general_ci DEFAULT 'Otro Grupo Poblacional',
  `grupo_etnico` varchar(100) COLLATE utf8mb4_general_ci DEFAULT 'No Aplica',
  `comunidad_etnica` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tipo_discapacidad` varchar(100) COLLATE utf8mb4_general_ci DEFAULT 'No Aplica',
  `tipo_escolaridad` varchar(100) COLLATE utf8mb4_general_ci DEFAULT 'NA',
  `direccion_residencia` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `indicativo_1` varchar(10) COLLATE utf8mb4_general_ci DEFAULT '+57',
  `numero_celular` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `indicativo_2` varchar(10) COLLATE utf8mb4_general_ci DEFAULT '+57',
  `otro_telefono` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ciudad_residencia` varchar(100) COLLATE utf8mb4_general_ci DEFAULT 'MEDELLIN-ANT-05001',
  `zona` enum('Urbana','Rural') COLLATE utf8mb4_general_ci DEFAULT 'Urbana',
  `barrio` varchar(100) COLLATE utf8mb4_general_ci DEFAULT 'El Poblado',
  `direccion_laboral` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `telefono_laboral` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ocupacion` varchar(100) COLLATE utf8mb4_general_ci DEFAULT 'Empleado',
  `tipo_afiliado` varchar(100) COLLATE utf8mb4_general_ci DEFAULT 'Contributivo Cotizante',
  `plan_salud` varchar(100) COLLATE utf8mb4_general_ci DEFAULT 'Plan Básico',
  `actualiza_citas_plan` tinyint(1) DEFAULT '0',
  `nivel_socioeconomico` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'CATEGORIA A',
  `estrato_socioeconomico` tinyint DEFAULT '3',
  `fecha_sgsss` date DEFAULT NULL,
  `fecha_afiliacion` date DEFAULT NULL,
  `municipio_afiliacion` varchar(100) COLLATE utf8mb4_general_ci DEFAULT 'MEDELLIN',
  `ips_primaria` varchar(200) COLLATE utf8mb4_general_ci DEFAULT '900294794 - COMITE DE ESTUDIOS MEDICOS SAS',
  `empleador` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ips_remite` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `numero_documento` (`numero_documento`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `descripcion` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `permisos` text COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sedes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `empresa_id` int NOT NULL,
  `nombre_sede` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `codigo_sede` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ciudad` varchar(100) COLLATE utf8mb4_general_ci DEFAULT 'MEDELLIN',
  `direccion` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `telefono` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `estado` enum('Activo','Inactivo') COLLATE utf8mb4_general_ci DEFAULT 'Activo',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `hora_apertura_atencion` time DEFAULT '07:20:00',
  `hora_apertura_festivos` time DEFAULT '08:00:00',
  PRIMARY KEY (`id`),
  KEY `empresa_id` (`empresa_id`),
  CONSTRAINT `sedes_ibfk_1` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `usuarios` (
  `id` int NOT NULL AUTO_INCREMENT,
  `rol_id` int NOT NULL,
  `empresa_id` int DEFAULT '1',
  `sede_id` int DEFAULT '1',
  `nombre_completo` varchar(120) COLLATE utf8mb4_general_ci NOT NULL,
  `usuario` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `estado` enum('ACTIVO','INACTIVO') COLLATE utf8mb4_general_ci DEFAULT 'ACTIVO',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `usuario` (`usuario`),
  KEY `rol_id` (`rol_id`),
  CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
