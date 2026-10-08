-- MySQL dump 10.13  Distrib 8.0.46, for Win64 (x86_64)
--
-- Host: localhost    Database: sistema web
-- ------------------------------------------------------
-- Server version	8.0.46

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `asignaciones`
--

DROP TABLE IF EXISTS `asignaciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `asignaciones` (
  `id_asignacion` int NOT NULL AUTO_INCREMENT,
  `id_reporte` int NOT NULL,
  `id_tecnico` int NOT NULL,
  `fecha_asignacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `observacion` text,
  `activa` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id_asignacion`),
  KEY `fk_asignacion_reporte` (`id_reporte`),
  KEY `fk_asignacion_tecnico` (`id_tecnico`),
  CONSTRAINT `fk_asignacion_reporte` FOREIGN KEY (`id_reporte`) REFERENCES `reportes` (`id_reporte`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_asignacion_tecnico` FOREIGN KEY (`id_tecnico`) REFERENCES `usuarios` (`id_usuario`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `asignaciones`
--

LOCK TABLES `asignaciones` WRITE;
/*!40000 ALTER TABLE `asignaciones` DISABLE KEYS */;
INSERT INTO `asignaciones` VALUES (1,1,3,'2026-09-23 13:32:36','Revisar el ventilador del aula 203.',1),(2,4,3,'2026-09-23 17:41:06','Revisar el bombillo y verificar la instalación eléctrica del aula.',1),(3,5,3,'2026-10-07 11:36:52','Revisar la evidencia adjunta y verificar el problema reportado.',1),(4,8,3,'2026-10-07 16:24:33','',1);
/*!40000 ALTER TABLE `asignaciones` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categorias`
--

DROP TABLE IF EXISTS `categorias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `categorias` (
  `id_categoria` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id_categoria`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categorias`
--

LOCK TABLES `categorias` WRITE;
/*!40000 ALTER TABLE `categorias` DISABLE KEYS */;
INSERT INTO `categorias` VALUES (1,'Electrico','Problemas relacionados con instalaciones eléctricas',1),(2,'Plomeria','Problemas relacionados con agua y tuberías',1),(3,'Infraestructura','Problemas relacionados con paredes, techos, pisos y estructuras',1),(4,'Mobiliario','Problemas relacionados con escritorios, sillas y otros muebles',1),(5,'Tecnologia','Problemas relacionados con computadores y equipos tecnológicos',1),(6,'Aseo','Problemas relacionados con limpieza y condiciones sanitarias',1);
/*!40000 ALTER TABLE `categorias` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `estados`
--

DROP TABLE IF EXISTS `estados`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `estados` (
  `id_estado` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id_estado`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `estados`
--

LOCK TABLES `estados` WRITE;
/*!40000 ALTER TABLE `estados` DISABLE KEYS */;
INSERT INTO `estados` VALUES (1,'Pendiente','Reporte registrado y esperando revisión'),(2,'En revision','Reporte en proceso de revisión'),(3,'Asignado','Reporte asignado a un técnico'),(4,'En proceso','Mantenimiento actualmente en ejecución'),(5,'Resuelto','El problema ha sido solucionado'),(6,'Cerrado','El reporte ha finalizado completamente'),(7,'Cancelado','El reporte fue cancelado');
/*!40000 ALTER TABLE `estados` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `evidencias`
--

DROP TABLE IF EXISTS `evidencias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `evidencias` (
  `id_evidencia` int NOT NULL AUTO_INCREMENT,
  `id_reporte` int NOT NULL,
  `id_usuario` int NOT NULL,
  `nombre_archivo` varchar(255) NOT NULL,
  `ruta_archivo` varchar(500) NOT NULL,
  `tipo` varchar(50) DEFAULT NULL,
  `fecha_subida` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_evidencia`),
  KEY `fk_evidencia_reporte` (`id_reporte`),
  KEY `fk_evidencia_usuario` (`id_usuario`),
  CONSTRAINT `fk_evidencia_reporte` FOREIGN KEY (`id_reporte`) REFERENCES `reportes` (`id_reporte`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_evidencia_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `evidencias`
--

LOCK TABLES `evidencias` WRITE;
/*!40000 ALTER TABLE `evidencias` DISABLE KEYS */;
INSERT INTO `evidencias` VALUES (1,5,2,'Mapas mentales con líneas.png','uploads/evidencia_6ac66ec0bb03c4.99576530.png','image/png','2026-10-07 11:09:36'),(2,9,2,'Mapas mentales con líneas.png','uploads/evidencia_6ac6bec2c11f28.94849686.png','image/png','2026-10-07 16:50:58');
/*!40000 ALTER TABLE `evidencias` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `prioridades`
--

DROP TABLE IF EXISTS `prioridades`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `prioridades` (
  `id_prioridad` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id_prioridad`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `prioridades`
--

LOCK TABLES `prioridades` WRITE;
/*!40000 ALTER TABLE `prioridades` DISABLE KEYS */;
INSERT INTO `prioridades` VALUES (1,'Baja','El problema no afecta significativamente las actividades'),(2,'Media','El problema afecta parcialmente las actividades'),(3,'Alta','El problema afecta considerablemente las actividades'),(4,'Critica','El problema requiere atención prioritaria');
/*!40000 ALTER TABLE `prioridades` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reportes`
--

DROP TABLE IF EXISTS `reportes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reportes` (
  `id_reporte` int NOT NULL AUTO_INCREMENT,
  `titulo` varchar(150) NOT NULL,
  `descripcion` text NOT NULL,
  `fecha_reporte` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `id_usuario` int NOT NULL,
  `id_categoria` int NOT NULL,
  `id_ubicacion` int NOT NULL,
  `id_prioridad` int NOT NULL,
  `id_estado` int NOT NULL,
  PRIMARY KEY (`id_reporte`),
  KEY `fk_reporte_usuario` (`id_usuario`),
  KEY `fk_reporte_categoria` (`id_categoria`),
  KEY `fk_reporte_ubicacion` (`id_ubicacion`),
  KEY `fk_reporte_prioridad` (`id_prioridad`),
  KEY `fk_reporte_estado` (`id_estado`),
  CONSTRAINT `fk_reporte_categoria` FOREIGN KEY (`id_categoria`) REFERENCES `categorias` (`id_categoria`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_reporte_estado` FOREIGN KEY (`id_estado`) REFERENCES `estados` (`id_estado`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_reporte_prioridad` FOREIGN KEY (`id_prioridad`) REFERENCES `prioridades` (`id_prioridad`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_reporte_ubicacion` FOREIGN KEY (`id_ubicacion`) REFERENCES `ubicaciones` (`id_ubicacion`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_reporte_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reportes`
--

LOCK TABLES `reportes` WRITE;
/*!40000 ALTER TABLE `reportes` DISABLE KEYS */;
INSERT INTO `reportes` VALUES (1,'Ventilador dañado','El ventilador del aula no enciende.','2026-09-23 13:31:57',2,1,3,2,6),(2,'Bombillo dañado','El bombillo del aula no enciende.','2026-09-23 15:42:59',2,1,1,2,1),(3,'Bombillo dañado','El bombillo del aula no enciende.','2026-09-23 15:47:23',2,1,1,2,1),(4,'Bombillo dañado','El bombillo del aula no enciende.','2026-09-23 16:07:43',2,1,1,2,6),(5,'Bombillo dañado','bombillo quemado','2026-10-07 11:09:36',2,1,3,2,6),(6,'Bombillo dañado','asasada','2026-10-07 15:36:42',2,1,6,2,1),(7,'Bombillo dañado','asasada','2026-10-07 15:42:53',2,1,6,2,1),(8,'prueba final del proyecto','Reporte creado para verificar el flujo completo del sistema de mantenimiento.','2026-10-07 16:21:29',2,1,1,2,6),(9,'Prueba de evidencia','Reporte creado para comprobar el funcionamiento de las evidencias.','2026-10-07 16:50:58',2,5,4,3,1);
/*!40000 ALTER TABLE `reportes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id_rol` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id_rol`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'Administrador','Gestiona usuarios, reportes y configuraciones del sistema'),(2,'Reportante','Registra y consulta reportes de mantenimiento'),(3,'Tecnico','Atiende los reportes de mantenimiento asignados');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `seguimientos`
--

DROP TABLE IF EXISTS `seguimientos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `seguimientos` (
  `id_seguimiento` int NOT NULL AUTO_INCREMENT,
  `id_reporte` int NOT NULL,
  `id_usuario` int NOT NULL,
  `id_estado` int NOT NULL,
  `comentario` text,
  `fecha` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_seguimiento`),
  KEY `fk_seguimiento_reporte` (`id_reporte`),
  KEY `fk_seguimiento_usuario` (`id_usuario`),
  KEY `fk_seguimiento_estado` (`id_estado`),
  CONSTRAINT `fk_seguimiento_estado` FOREIGN KEY (`id_estado`) REFERENCES `estados` (`id_estado`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_seguimiento_reporte` FOREIGN KEY (`id_reporte`) REFERENCES `reportes` (`id_reporte`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_seguimiento_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `seguimientos`
--

LOCK TABLES `seguimientos` WRITE;
/*!40000 ALTER TABLE `seguimientos` DISABLE KEYS */;
INSERT INTO `seguimientos` VALUES (1,1,2,1,'Se registró el reporte de mantenimiento.','2026-09-23 13:33:07'),(2,2,2,1,'Se registró el reporte de mantenimiento.','2026-09-23 15:42:59'),(3,3,2,1,'Se registró el reporte de mantenimiento.','2026-09-23 15:47:23'),(4,4,2,1,'Se registró el reporte de mantenimiento.','2026-09-23 16:07:43'),(5,1,3,4,'El técnico actualizó el estado del reporte a: En proceso','2026-09-23 17:04:49'),(6,1,3,4,'Se revisó el ventilador del aula y se verificó el cableado eléctrico. Se realizó ajuste de la conexión.','2026-09-23 17:13:55'),(7,1,3,5,'Se realizó la reparación del ventilador y se verificó su funcionamiento. El equipo quedó funcionando correctamente.','2026-09-23 17:25:44'),(8,4,1,3,'El administrador asignó el reporte al técnico: Carlos Gomez. Observación: Revisar el bombillo y verificar la instalación eléctrica del aula.','2026-09-23 17:41:06'),(9,4,3,4,'El técnico actualizó el estado del reporte a: En proceso','2026-09-23 19:14:43'),(10,4,3,4,'Se revisó el bombillo y se verificó la conexión eléctrica. Se inició el proceso de reparación.','2026-09-23 19:16:09'),(11,4,3,5,'Se realizó el cambio del bombillo y se verificó su correcto funcionamiento. El mantenimiento fue completado satisfactoriamente.','2026-09-23 19:16:52'),(12,4,1,6,'El administrador cerró el reporte después de verificar que el mantenimiento fue completado.','2026-09-23 19:30:21'),(13,5,2,1,'Se registró el reporte de mantenimiento.','2026-10-07 11:09:36'),(14,5,1,3,'El administrador asignó el reporte al técnico: Carlos Gomez. Observación: Revisar la evidencia adjunta y verificar el problema reportado.','2026-10-07 11:36:52'),(15,6,2,1,'Se registró el reporte de mantenimiento.','2026-10-07 15:36:42'),(16,7,2,1,'Se registró el reporte de mantenimiento.','2026-10-07 15:42:53'),(17,5,3,4,'El técnico actualizó el estado del reporte a: En proceso','2026-10-07 16:02:05'),(18,5,3,5,'ya quedo solucionado','2026-10-07 16:02:24'),(19,8,2,1,'Se registró el reporte de mantenimiento.','2026-10-07 16:21:29'),(20,8,1,3,'El administrador asignó el reporte al técnico: Carlos Gomez','2026-10-07 16:24:33'),(21,8,3,4,'El técnico actualizó el estado del reporte a: En proceso','2026-10-07 16:27:38'),(22,8,3,4,'Se realizó revisión del ventilador y se verificó el estado de las conexiones eléctricas.','2026-10-07 16:30:46'),(23,8,3,5,'Se realizó el mantenimiento y se verificó el correcto funcionamiento del equipo. El problema quedó solucionado.','2026-10-07 16:32:07'),(24,5,1,6,'El administrador cerró el reporte después de verificar que el mantenimiento fue completado.','2026-10-07 16:34:00'),(25,8,1,6,'El administrador cerró el reporte después de verificar que el mantenimiento fue completado.','2026-10-07 16:35:21'),(26,1,1,6,'El administrador cerró el reporte después de verificar que el mantenimiento fue completado.','2026-10-07 16:48:21'),(27,9,2,1,'Se registró el reporte de mantenimiento.','2026-10-07 16:50:58');
/*!40000 ALTER TABLE `seguimientos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ubicaciones`
--

DROP TABLE IF EXISTS `ubicaciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ubicaciones` (
  `id_ubicacion` int NOT NULL AUTO_INCREMENT,
  `bloque` varchar(100) NOT NULL,
  `espacio` varchar(100) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id_ubicacion`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ubicaciones`
--

LOCK TABLES `ubicaciones` WRITE;
/*!40000 ALTER TABLE `ubicaciones` DISABLE KEYS */;
INSERT INTO `ubicaciones` VALUES (1,'Bloque A','Aula 101','Aula académica',1),(2,'Bloque A','Aula 102','Aula académica',1),(3,'Bloque A','Aula 203','Aula académica',1),(4,'Bloque B','Sala de informática','Sala con equipos de cómputo',1),(5,'Bloque B','Baños segundo piso','Baños de estudiantes',1),(6,'Bloque C','Biblioteca','Biblioteca institucional',1);
/*!40000 ALTER TABLE `ubicaciones` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `usuarios` (
  `id_usuario` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) NOT NULL,
  `correo` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `id_rol` int NOT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT '1',
  `fecha_registro` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_usuario`),
  UNIQUE KEY `correo` (`correo`),
  KEY `fk_usuario_rol` (`id_rol`),
  CONSTRAINT `fk_usuario_rol` FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id_rol`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuarios`
--

LOCK TABLES `usuarios` WRITE;
/*!40000 ALTER TABLE `usuarios` DISABLE KEYS */;
INSERT INTO `usuarios` VALUES (1,'Administrador','Sistema','admin@institucion.edu.co','password_demo','3000000000',1,1,'2026-09-23 13:30:58'),(2,'Juan','Perez','juan.perez@institucion.edu.co','password_demo','3000000001',2,1,'2026-09-23 13:30:58'),(3,'Carlos','Gomez','carlos.gomez@institucion.edu.co','password_demo','3000000002',3,1,'2026-09-23 13:30:58'),(4,'María','Rodríguez','maria.rodriguez@institucion.edu.co','123456','3119876543',2,1,'2026-09-23 20:25:06');
/*!40000 ALTER TABLE `usuarios` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-10-07 17:10:41
