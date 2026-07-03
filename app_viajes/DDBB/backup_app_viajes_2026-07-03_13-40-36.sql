-- MariaDB dump 10.17  Distrib 10.4.14-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: app_viajes
-- ------------------------------------------------------
-- Server version	10.4.14-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `autorizantes`
--

DROP TABLE IF EXISTS `autorizantes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `autorizantes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_empresa` int(11) NOT NULL,
  `id_centro_costo` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `celular` varchar(30) DEFAULT NULL,
  `email` varchar(50) NOT NULL,
  `horario` varchar(100) DEFAULT NULL,
  `estado` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=96 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `autorizantes`
--

LOCK TABLES `autorizantes` WRITE;
/*!40000 ALTER TABLE `autorizantes` DISABLE KEYS */;
INSERT INTO `autorizantes` VALUES (1,999,999,'Fabian Alejandro','1169356236','fabian_12345@hotmail.com','06 A 18',1),(9,3,5,'Alegre Maria Florencia','','aleflor@gmail.com','',1),(10,3,5,'Giles Juan Manuel','','alegilesj@gmail.com','',1),(11,3,5,'Giles Pablo andres','','alegi@gmail.com','',1),(12,3,5,'Perussetti Zully Elisa','','aleperu@gmail.com','',1),(13,3,5,'Ruggiero Enzo','','aleenz@gmail.com','',1),(14,4,6,'CARNAU MAZZEI SABRINA','','HHH@PORTENIO.COM','',1),(15,4,6,'ESTEVEZ EMILIANO','','C@PORTENIO.COM','',1),(16,4,6,'MASTRANGELO PABLO','','B@PORTENIO.COM','',1),(17,4,6,'MAZZINI	ALEJANDRA','','mazini@PORTENIO.COM','',1),(18,4,6,'PEREDO	ROMAN','','RO@PORTENIO.COM','',1),(19,4,6,'VAZQUEZ PABLO','','FF@PORTENIO.COM','',1),(20,4,6,'YAÑEZ SANTIAGO','','	GG@PORTENIO.COM','',1),(21,5,22,'AIJENBOM VALERIA-RABINATO','','AIJEM@GMAIL.COM','',1),(22,5,9,'AJZENMESSER FERNANDO - ADMINISTRACION-DOC','','FERNANDOAJZENMESSER@GMAIL.COM','',1),(23,5,26,'ALBAJARI VERONICA-SERVICIOS DE EMPLEO','','alaba@PORTENIO.COM','',1),(24,5,8,'ALFIE VIVIANA - ADMINISTRACION-CONTADURIA','','VIVIANAALFIE@GMAIL.COM','',1),(25,5,10,'ALGACE NADIA - ADMINISTRACION-INMUEBLES','','NADIAALGACE@GMAIL.COM','',1),(26,5,27,'AUDAY SALVADOR - SERVICIOS COMUNITARIOS','','SALVADORAUDAY@GMAIL.COM','',1),(27,5,13,'BARANKIEWICZ IGNACIO-COCHERIA','','GTERWE|@PORTENIO.COM','',1),(28,5,28,'CAHANSKY SERGIO - SOCIOS Y DESARROLLO','','caja@PORTENIO.COM','',1),(29,5,13,'CAPLAN PABLO - COCHERIA','','pcaplan@amia.org.ar','',1),(30,5,28,'CHIPRUT ANDREA - SOCIOS Y DESARROLLO','','CHIPRUT@PORTENIO.COM','',1),(31,5,29,'COHEN IMACH ARIEL - VAAD HAJINUJ','','COHEN@PORTENIO.COM','',1),(33,5,18,'COSTANTINI DAMIAN-INFRAESTRUCTUTA','','CONS@GMAIL.COM','',1),(34,5,21,'CROUDO KARINA - PROGRAMAS SOCIALES','','CROUDO@PORTENIO.COM','',1),(35,5,24,'Emanuel Cynthia Florencia-SECRETARIA INSTITUCIONAL','','EMA@GMAIL.COM','',1),(36,5,21,'EPELBAUM ELIANA - PROGRAMAS SOCIALES','','EPELBAUM@PORTENIO.COM','',1),(37,5,18,'FARIAS RAQUEL -INFRAESTRUCTURA','','FAR@GMAIL.COM','',1),(38,5,18,'FELCMAN ADRIAN-INFRAESTRUCTURA','','FEL@GMAIL.COM','',1),(39,5,8,'FINGERET NURIA - ADMINISTRACION-CONTADURIA','','NURIAFINGERET@GMAIL.COM','',1),(40,5,8,'FLORES CLAUDIA - SERVIVIO DE EMPLEO','','FLORES@PORTENIO.COM','',1),(41,5,26,'FRAUMAN ILEANA - SERVIVIO DE EMPLEO','','FRAUMAN@PORTENIO.COM','',1),(42,5,12,'FREUE MIRIAN - ADMINISTRACION- COMPRAS','','mfreue@amia.org.ar','',1),(43,5,21,'FRIDMAN SEBASTIAN - PROGRAMAS SOCIALES','','FRIDMANS@PORTENIO.COM','',1),(44,5,11,'GALAGOVSKY TAMARA - ADMINISTRACION-TESORERIA','','GALAGOVSKY@PORTENIO.COM','',1),(45,5,25,'GLIKIN CLAUDIO - SEGURIDAD','','GLIKIN@PORTENIO.COM','',1),(46,5,11,'GOLDFINGER VERONICA - ADMINISTRACION-TESORERIA','','r@PORTENIO.COM','',1),(47,5,29,'GORENSTEN KARINA-VAAD HAJINUJ','','GOREN@GMAIL.COM','',1),(48,5,10,'GROSMAN YANINA - ADMINISTRACION-Inmuebles','','VVV@PORTENIO.COM','',1),(49,5,27,'GRUBER JACKELINE - SERVICIOS COMUNITARIOS','','SSSSS@PORTENIO.COM','',1),(50,5,24,'Hamra Elías Eliahu Isaac-Secretaria institucional','','hamra@gmail.com','',1),(51,5,27,'HEISEL MARIANA - SERVICIOS COMUNITARIOS','','DN@PORTENIO.COM','',1),(52,5,13,'JAGODA MARCELO - COCHERIA','','mjagoda@amia.org.ar','',1),(53,5,21,'JAIT PAULA - PROGRAMAS SOCIALES','','JAIT@PORTENIO.COM','',1),(55,5,28,'JUNOVICH SANDRA - SOCIOS Y DESARROLLO','','JUNOVICH@PORTENIO.COM','',1),(56,5,17,'KAPSZUK ELIO - ESPACIO DE ARTE','','ELIOKAPSZUK@GMAIL.COM','',1),(57,5,21,'KOHON FANNY - PROGRAMAS SOCIALES','','FANNYKOHON@GMAIL.COM','',1),(58,5,29,'KOROB KARINA - VAAD HAJINUJ','','KOROB@PORTENIO.COM','',1),(59,5,13,'KRAWIEC SILVINA','','skrawiec@amia.org.ar','',1),(60,5,7,'KUKIOLKA TAMARA - ADMINISTRACION-SISTEMA','','TAMARAKUKIOLKA@GMAIL.COM','',1),(61,5,18,'KUPFERMAN MARCELO-INFRAESTRUCTURA','','KUPFERMAN@GMAIL.COM','',1),(62,5,24,'LANIADO ANDREA-SECRETARIA INSTITUCIONAL','','LAN@GMAIL.COM','',1),(63,5,29,'LEWIN SANDRA - VAAD HAJINUJ','','LEWIN@PORTENIO.COM','',1),(64,5,9,'LIBERMAN MARIO - ADMINISTRACION-DOCUMENTOS','','ertoamarcogliese@PORTENIO.COM','',1),(65,5,23,'LLERNOVOY KARINA - RECURSOS HUMANOS','','LLERNOVOYK@PORTENIO.COM','',1),(66,5,21,'LOKAJ DAMIAN - PROGRAMAS SOCIALES','','DAMIANLOKAJ@GMAIL.COM','',1),(67,5,13,'LUKACHER MARTIN - COCHERIA','','LUKACHER@PORTENIO.COM','',1),(68,5,13,'MILINKIEWICZ HERNAN - COCHERIA','','DQA@PORTENIO.COM','',1),(69,5,21,'MIZRAHI XIMENA - PROGRAMAS SOCIALES','','MIZRAHI@PORTENIO.COM','',1),(70,5,7,'MOHADEB SALOMON - ADMINISTRACION-SISTEMA','','SALOMONMOHADEB@GMAIL.COM','',1),(71,5,8,'MOSER MARINA - ADMINISTRACION-CONTADURIA','','MARINAMOSER@GMAIL.COM','',1),(72,5,18,'MOYA FLAVIO-INFRAESTRUCTURA','','MOY@GMAIL.COM','',1),(73,5,21,'NAHUM AYLEN - PROGRAMAS SOCIALES','','NAHUM@PORTENIO.COM','',1),(74,5,29,'PALEY KARINA - VAAD HAJINUJ','','PALEY@PORTENIO.COM','',1),(75,5,29,'PALUCH LILI-VAAD HAJINUJ','','PALUCH@GMAIL.COM','',1),(76,5,18,'PARED RAMON -INFRAESTRUCTURA','','PAR@GMAIL.COM','',1),(77,5,26,'PASARELLI FERNANDO - SERVIVIO DE EMPLEO','','PASARELLI@PORTENIO.COM','',1),(78,5,14,'PIESKE MARCELA - COMUNICACIÓN y PRENSA','','MARCELAPIESKE@GMAIL.COM','',1),(79,5,16,'POMERANTZ DANIEL - DIRECCION EJECUTIVA','','POMERANTZ@PORTENIO.COM','',1),(80,5,8,'POMPAS KAREN - ADMINISTRACION-CONTADURIA','','KARENPOMPAS@GMAIL.COM','',1),(81,5,21,'RACHILEVICH JULIETA - PROGRAMAS SOCIALES','','RACHILEVICH@PORTENIO.COM','',1),(82,5,16,'RONIT BOREN - DIRECCION EJECUTIVA','','RONIT@PORTENIO.COM','',1),(83,5,13,'SABAJ SERGIO','','SSABAJ@AMIA.ORG.AR','',1),(84,5,28,'SALEM MARIANA - SOCIOS Y DESARROLLO','','SALEM@PORTENIO.COM','',1),(85,5,14,'SAMSOLO VANESA - COMUNICACIÓN','','BHGG@PORTENIO.COM','',1),(86,5,14,'SCHERMAN GABRIEL - COMUNICACIÓN','','DDDDDDD@PORTENIO.COM','',1),(87,5,17,'SCHRAIER NADIA - ESPACIO DE ARTE','','SCHRAIER@PORTENIO.COM','',1),(88,5,21,'SCHUSTER BARBARA - PROGRAMAS SOCIALES','','shuster@PORTENIO.COM','',1),(89,5,14,'STARK MATIAS - COMUNICACIÓN','','STARKAMATIAS@GMAIL.COM','',1),(90,5,21,'TABOADA CONSTANZA - PROGRAMAS SOCIALES','','CONSTANZATABOADA@GMAIL.COM','',1),(91,5,21,'VENTURA JULI - PROGRAMAS SOCIALES','','VENTURA@PORTENIO.COM','',1),(92,5,20,'ZALCMAN FLAVIA - JUVENTUD','','ZALCMAN@PORTENIO.COM','',1),(93,5,30,'COHEN MARCOS -VAAD HAKEHILOT','','COHENNAR@GMAIL.COM','',1),(94,5,30,'JATEMLIANSKY TAMARA -VAAD HAKEHILOT','','cogliese@PORTENIO.COM','',1),(95,1,2,'Fabian','1169356236','fabian_12345@hotmail.com','8 a 12',1);
/*!40000 ALTER TABLE `autorizantes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `centros_costo`
--

DROP TABLE IF EXISTS `centros_costo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `centros_costo` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_empresa` int(4) NOT NULL,
  `id_centro_costo` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `direccion` varchar(50) DEFAULT NULL,
  `contacto_centro` varchar(50) NOT NULL,
  `cel` int(15) NOT NULL,
  `observaciones` varchar(250) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `centros_costo`
--

LOCK TABLES `centros_costo` WRITE;
/*!40000 ALTER TABLE `centros_costo` DISABLE KEYS */;
INSERT INTO `centros_costo` VALUES (1,999,999,'Paganini','Carlos Gardel 3296','',0,''),(2,1,1,'Frente','Venezuela 4488','',0,'Casa'),(5,3,3,'ALEGRE EN FLOR','Av. Cordoba 1776','',0,''),(6,4,1,'ALGEIBA S.A.','PARANA 771 1Âº B','',0,''),(7,5,1,'ADMINISTARCION SISTEMAS','Pasteur 633','',0,''),(8,5,2,'ADMINISTRACION CONTADURIA','Pasteur 633','',0,''),(9,5,3,'ADMINISTRACION DOCUMENTOS','Pasteur 633','',0,''),(10,5,4,'ADMINISTRACION INMUEBLES','Pasteur 633','',0,''),(11,5,5,'ADMINISTRACION TESORERIA','Pasteur 633','',0,''),(12,5,6,'ADMINNISTRACION COMPRAS','Pasteur 633','',0,''),(13,5,7,'COCHERIA','Pasteur 735','',0,''),(14,5,8,'COMUNICACIONES','PASTEUR 735','',0,''),(15,5,9,'CULTURA','PASTEUR 735','',0,''),(16,5,10,'DIRECCION EJECUTIVA','PASTEUR 735','',0,''),(17,5,11,'ESPACIO DE ARTE','PASTEUR 735','',0,''),(18,5,12,'INFRAESTRUCTURA','Pasteur 633','',0,''),(19,5,13,'JUBILADOS','URIBURU 650','',0,''),(20,5,14,'JUVENTUD','PASTEUR 735','',0,''),(21,5,15,'PROGRAMA SOCIALES','PASTEUR 735','',0,''),(22,5,16,'RABINATO','PASTEUR 735','',0,''),(23,5,17,'RECURSOS HUMANOS','PASTEUR 675','',0,''),(24,5,18,'SECRETARIA INSTITUCIONAL','PASTEUR 735','',0,''),(25,5,19,'SEGURIDAD','PASTEUR 735','',0,''),(26,5,20,'SERVICIO DE EMPLEO','PASTEUR 735','',0,''),(27,5,21,'SERVICIOS COMUNITARIOS','PASTEUR 735','',0,''),(28,5,22,'	SOCIOS Y DESARROLLO','PASTEUR 735','',0,''),(29,5,23,'VAAD HAJINUJ','PASTEUR 735','',0,''),(30,5,24,'	VAAD HAKEHILOT','Pasteur 633','',0,'');
/*!40000 ALTER TABLE `centros_costo` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `choferes`
--

DROP TABLE IF EXISTS `choferes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `choferes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) NOT NULL,
  `cel` int(10) NOT NULL,
  `dir` varchar(30) NOT NULL,
  `barrio` varchar(20) NOT NULL,
  `cp` int(4) NOT NULL,
  `movil` int(11) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `user` int(8) NOT NULL,
  `clave` int(4) NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_vehiculo_asignado` FOREIGN KEY (`movil`) REFERENCES `vehiculos` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `choferes`
--

LOCK TABLES `choferes` WRITE;
/*!40000 ALTER TABLE `choferes` DISABLE KEYS */;
INSERT INTO `choferes` VALUES (2,'Fabian','Nogueroles',1169356236,'Carlos Gardel 3296','V. Libertad',1650,2,0,14952694,145152),(3,'JORGE','Rodriguez',1154873265,'Caminito 2020','La Boca',1245,NULL,0,0,0),(4,'Cachorro','Lopez',1145895689,'Av Ricardo Balbin 2589','Saavedra',1250,3025,0,123456,1234),(5,'Carlos','Piragine',1125896589,'Corrientes 458','Flores',1444,327,0,0,0);
/*!40000 ALTER TABLE `choferes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cuenta_empresa`
--

DROP TABLE IF EXISTS `cuenta_empresa`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cuenta_empresa` (
  `id` int(4) NOT NULL AUTO_INCREMENT,
  `id_empresa` int(4) NOT NULL,
  `razon_social` varchar(50) NOT NULL,
  `dir` varchar(50) NOT NULL,
  `cuit` varchar(20) DEFAULT NULL,
  `inc_brutos` varchar(20) DEFAULT NULL,
  `contacto_1` varchar(50) DEFAULT NULL,
  `cel_1` int(10) DEFAULT NULL,
  `observaciones` int(4) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cuenta_empresa`
--

LOCK TABLES `cuenta_empresa` WRITE;
/*!40000 ALTER TABLE `cuenta_empresa` DISABLE KEYS */;
INSERT INTO `cuenta_empresa` VALUES (1,999,'MyF','Carlos Gardel 3296','30-14952694-7','30-14952694-7','Fabian',1169356236,0),(3,14,'ALEGRE EN FLOR','FELIPE VALLESE 501-CABA','27920804867','27920804867',NULL,NULL,0),(4,5032,'ALGEIBA S.A.','PARANA 771 1Âº B','20129904187',NULL,NULL,NULL,0),(5,17,'AMIA','Pasteur 633','33333334532',NULL,NULL,NULL,0);
/*!40000 ALTER TABLE `cuenta_empresa` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pasajeros_cc`
--

DROP TABLE IF EXISTS `pasajeros_cc`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pasajeros_cc` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_empresa` int(11) NOT NULL,
  `id_cc` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `celular` varchar(30) DEFAULT NULL,
  `email` varchar(50) NOT NULL,
  `horario` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pasajeros_cc`
--

LOCK TABLES `pasajeros_cc` WRITE;
/*!40000 ALTER TABLE `pasajeros_cc` DISABLE KEYS */;
/*!40000 ALTER TABLE `pasajeros_cc` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ubicaciones`
--

DROP TABLE IF EXISTS `ubicaciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ubicaciones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lat` decimal(10,7) DEFAULT NULL,
  `lng` decimal(10,7) DEFAULT NULL,
  `user_id` varchar(50) DEFAULT NULL,
  `device_id` varchar(30) DEFAULT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp(),
  `viaje_num` int(10) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=103 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ubicaciones`
--

LOCK TABLES `ubicaciones` WRITE;
/*!40000 ALTER TABLE `ubicaciones` DISABLE KEYS */;
INSERT INTO `ubicaciones` VALUES (92,-34.6472173,-58.4581041,'2','activo','2026-07-01 14:04:47',0),(93,-34.6472173,-58.4581041,'2','activo','2026-07-01 14:04:51',0),(94,-34.6472211,-58.4580927,'2','activo','2026-07-01 14:04:58',0),(95,-34.6472211,-58.4580927,'2','activo','2026-07-01 14:05:02',0),(96,-34.6472079,-58.4580979,'2','activo','2026-07-01 14:05:08',0),(97,-34.6472079,-58.4580979,'2','activo','2026-07-01 14:05:11',0),(98,-34.6472098,-58.4580934,'2','inactivo','2026-07-01 14:05:17',0),(99,-34.6472283,-58.4580923,'3025','activo','2026-07-01 14:06:05',0),(100,-34.6472283,-58.4580923,'3025','inactivo','2026-07-01 14:06:08',0),(101,-34.6472100,-58.4580940,'3025','activo','2026-07-01 14:06:23',0),(102,-34.6472100,-58.4580940,'3025','inactivo','2026-07-01 14:06:25',0);
/*!40000 ALTER TABLE `ubicaciones` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `usuarios` (
  `id` int(4) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL,
  `telefono` int(10) NOT NULL,
  `email` varchar(50) NOT NULL,
  `password` varchar(100) NOT NULL,
  `permisos` varchar(15) NOT NULL,
  `estado` varchar(15) NOT NULL,
  `nom_apellido` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuarios`
--

LOCK TABLES `usuarios` WRITE;
/*!40000 ALTER TABLE `usuarios` DISABLE KEYS */;
INSERT INTO `usuarios` VALUES (1,'fabian',1169356236,'laboratorio.fabian@gmail.com','$2y$10$9Y7tFQpxkwpYwzSulzEaju0/jbKU4lS2yx3Rv5.PWkY075nv2rikC','0','activo','Fabian Nogueroles'),(2,'lucas',1144444444,'lucas.nogueroles@gmail.com','$2y$10$nNHgf9izB3i1z1vUbvhBKe6C94mJVFFx5HUXNGdnIDNuUkCdXOQRG','3','activo','Lucas Nogueroles'),(3,'jorge',1156892356,'Jorge@gmail.com','$2y$10$AgC9SwZ2ikTJI8T6NtIQpetjz3oWLh8kvVIKvtv3N4T2hxFRdk6tO','2','suspendido','JORGE MACELLO');
/*!40000 ALTER TABLE `usuarios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vehiculos`
--

DROP TABLE IF EXISTS `vehiculos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `vehiculos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `categoria` varchar(15) NOT NULL,
  `marca` varchar(50) NOT NULL,
  `modelo` varchar(50) NOT NULL,
  `patente` varchar(20) DEFAULT NULL,
  `estado` varchar(20) NOT NULL,
  `color` varchar(20) NOT NULL,
  `id_chofer` int(4) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vehiculos`
--

LOCK TABLES `vehiculos` WRITE;
/*!40000 ALTER TABLE `vehiculos` DISABLE KEYS */;
INSERT INTO `vehiculos` VALUES (1,'TAXI','CHEVROLET','SPIN','AA456GG','disponible','NEGRA',NULL),(2,'REMIS','FIAT','CRONOS','AA456GG','disponible','BLANCO',2),(3,'REMIS','TOYOTA','COROLLA','AA802GX','disponible','BLANCO',NULL),(4,'REMIS','TOYOTA','COROLLA','AA456GG','ocupado','NEGRO',NULL),(5,'REMIS','FIAT','CRONOS','AA802GX','disponible','NEGRO',NULL),(6,'REMIS','TOYOTA','COROLLA','AG444JH','disponible','AMARILLO',4),(7,'REMIS','FIAT','CRONOS','AA589GT','ocupado','VERDE',3),(8,'TAXI','CHEVROLET','SPIN','AA456FF','disponible','NEGRO',5);
/*!40000 ALTER TABLE `vehiculos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `viajes_despacho`
--

DROP TABLE IF EXISTS `viajes_despacho`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `viajes_despacho` (
  `id` int(20) NOT NULL AUTO_INCREMENT,
  `cel_pasaj` int(10) NOT NULL,
  `nombre_pasaj` varchar(50) NOT NULL,
  `direccion_origen` varchar(70) NOT NULL,
  `direccion_destino` varchar(70) NOT NULL,
  `obs_operador` varchar(500) NOT NULL,
  `obs_pasaj` varchar(500) NOT NULL,
  `estado` varchar(15) NOT NULL,
  `diferido` varchar(20) NOT NULL,
  `fecha` date DEFAULT NULL,
  `hora` varchar(11) DEFAULT NULL,
  `categoria_movil` varchar(15) NOT NULL,
  `origen_lat` decimal(10,8) DEFAULT NULL,
  `origen_lng` decimal(11,8) DEFAULT NULL,
  `destino_lat` decimal(10,8) DEFAULT NULL,
  `destino_lng` decimal(11,8) DEFAULT NULL,
  `cc` int(4) DEFAULT NULL,
  `centro_de_costo` int(4) DEFAULT NULL,
  `id_autorizante` int(11) DEFAULT NULL,
  `obs_viaje` varchar(255) NOT NULL,
  `id_chofer` int(4) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=54 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `viajes_despacho`
--

LOCK TABLES `viajes_despacho` WRITE;
/*!40000 ALTER TABLE `viajes_despacho` DISABLE KEYS */;
INSERT INTO `viajes_despacho` VALUES (36,2147483647,'Alegre Maria Florencia','Aranguren 450, caba','French 2500, Vicente lopez','','','Cancelado','','2026-06-25','16:56','REMIS',-34.61241300,-58.43735780,-34.55473440,-58.51283720,3,0,NULL,'a',0),(37,2147483647,'ESTEVEZ EMILIANO','Camacua 2000, caba','Sarmiento 250, San Martin','','','Cancelado','','2026-06-25','16:58','REMIS',-34.63418120,-58.45559180,-34.57788150,-58.53131770,4,0,NULL,'aa',0),(38,2147483647,'Alegre Maria Florencia','Av Lafuente 1499,caba','French 2500, Vicente lopez','','','Cancelado','','2026-06-26','10:30','REMIS',-34.64737290,-58.45786790,-34.55473440,-58.51283720,3,0,NULL,'frff',0),(39,2147483647,'Alegre Maria Florencia','Camacua 2000, caba','French 2500, Vicente lopez','aaa','bbb','Cancelado','','2026-06-26','10:20','REMIS',-34.63418120,-58.45559180,-34.55473440,-58.51283720,3,0,NULL,'ghkjghj',0),(40,2147483647,'CARNAU MAZZEI SABRINA','Av Lafuente 1499,caba','','aaa','ddd','Cancelado','','2026-06-26','10:52','',-34.64737290,-58.45786790,NULL,NULL,4,0,NULL,'zxcv',0),(41,2147483647,'Alegre Maria Florencia','Aranguren 450, caba','','','','Cancelado','','2026-06-26','11:13','REMIS',-34.61241300,-58.43735780,NULL,NULL,3,0,NULL,'zxcv',0),(42,1125895698,'CARNAU MAZZEI SABRINA','Av Lafuente 1499,caba','','bbb','fff','Cancelado','','2026-06-26','11:14','TAXI',-34.64737290,-58.45786790,NULL,NULL,4,0,NULL,'xzcv',0),(43,2147483647,'Amendola','Av Lafuente 1499,caba','','ff','gg','Cancelado','','2026-06-26','11:14','VAN',-34.64737290,-58.45786790,NULL,NULL,2,0,NULL,'cxzvvc',0),(44,2147483647,'Giles Juan Manuel','Camacua 2000, caba','','kkk','hhh','Cancelado','','2026-06-26','11:15','UTILITARIO',-34.63418120,-58.45559180,NULL,NULL,3,0,NULL,'asdf',0),(45,1125895698,'Paco Jerte','Camacua 2000, caba','','ggg','ddd','Inmediato','','2026-06-26','11:41','REMIS',-34.62931130,-58.45795720,NULL,NULL,0,0,NULL,'',0),(46,1125895698,'werf','French 2500, caba','','ggg','gggg','Inmediato','','2026-06-26','11:44','TAXI',-34.59035210,-58.40024590,NULL,NULL,0,0,NULL,'',0),(47,1125895698,'Pascualito Peres','','','mañana','despues','Cancelado','','2026-06-26','12:20','VAN',NULL,NULL,NULL,NULL,0,0,NULL,'adsfdaf',0),(48,1125895698,'ESTEVEZ EMILIANO','Laprida 4490, Villa Martelli','','ddd','sss','Inmediato','','2026-06-26','12:22','UTILITARIO',-34.55154940,-58.51190860,NULL,NULL,4,0,NULL,'',0),(49,1125895698,'Carlos Garcia','Laprida 4490, Villa Martelli','','asdasd','zxczxc','Asignado','','2026-06-26','12:20','REMIS',-34.55154940,-58.51190860,NULL,NULL,0,0,NULL,'',2),(50,2147483647,'LIBERMAN MARIO - ADMINISTRACION-DOCUMENTOS','Aranguren 450, caba','','asd','zxc','Inmediato','','2026-06-26','12:24','VAN',-34.61241300,-58.43735780,NULL,NULL,5,0,NULL,'',0),(51,1125895698,'Pascualito Peres','Camacua 2000, caba','','','','Inmediato','','2026-06-26','12:26','REMIS',-34.62931130,-58.45795720,NULL,NULL,0,0,NULL,'',0),(52,1125895698,'CARNAU MAZZEI SABRINA','Sanabria 1979, caba','Garcia del rio 1250, caba','','','Inmediato','','2026-06-26','12:28','REMIS',-34.61847620,-58.50530700,-34.55175860,-58.47860080,4,0,NULL,'',0),(53,1125895698,'CARNAU MAZZEI SABRINA','Sanabria 1979, caba','Garcia del rio 1250, caba','','','Inmediato','','2026-06-26','12:28','REMIS',-34.61847620,-58.50530700,-34.55175860,-58.47860080,4,0,NULL,'',0);
/*!40000 ALTER TABLE `viajes_despacho` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-07-03 13:40:41
