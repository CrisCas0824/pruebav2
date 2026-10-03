-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: lacaserita_db
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

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
-- Table structure for table `categoria`
--

DROP TABLE IF EXISTS `categoria`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `categoria` (
  `id_categoria` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `imagen` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_categoria`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categoria`
--

LOCK TABLES `categoria` WRITE;
/*!40000 ALTER TABLE `categoria` DISABLE KEYS */;
/*!40000 ALTER TABLE `categoria` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `configuracion_correo`
--

DROP TABLE IF EXISTS `configuracion_correo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `configuracion_correo` (
  `id_config` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_empresa` varchar(150) NOT NULL DEFAULT 'La Caserita',
  `logo_url` varchar(255) DEFAULT 'Images/logo.png',
  `cabecera_correo_url` varchar(255) DEFAULT NULL,
  `telefono_whatsapp` varchar(30) DEFAULT '+51 987654321',
  `correo_soporte` varchar(150) DEFAULT 'contacto@lacaserita.com',
  `enlace_seguimiento` varchar(255) DEFAULT 'index.php?action=mi_cuenta',
  `direccion_fisica` varchar(255) DEFAULT 'Av. Principal 123, Lima - Per├║',
  `color_primario` varchar(20) NOT NULL DEFAULT '#0d5f6e',
  `color_secundario` varchar(20) NOT NULL DEFAULT '#ff7f00',
  `smtp_host` varchar(150) DEFAULT NULL,
  `smtp_port` int(11) DEFAULT 587,
  `smtp_user` varchar(150) DEFAULT NULL,
  `smtp_pass` varchar(255) DEFAULT NULL,
  `smtp_seguridad` varchar(10) NOT NULL DEFAULT 'tls',
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_actualizacion` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_config`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `configuracion_correo`
--

LOCK TABLES `configuracion_correo` WRITE;
/*!40000 ALTER TABLE `configuracion_correo` DISABLE KEYS */;
/*!40000 ALTER TABLE `configuracion_correo` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `detalle_pedido`
--

DROP TABLE IF EXISTS `detalle_pedido`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `detalle_pedido` (
  `id_detalle` int(11) NOT NULL AUTO_INCREMENT,
  `id_pedido` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `titulo_producto` varchar(150) DEFAULT NULL,
  `unidad_medida` varchar(30) DEFAULT NULL,
  `cantidad` int(11) NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  `costo_unitario` decimal(10,2) NOT NULL DEFAULT 0.00,
  `subtotal` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id_detalle`),
  KEY `fk_det_pedido` (`id_pedido`),
  KEY `fk_det_producto` (`id_producto`),
  CONSTRAINT `fk_det_pedido` FOREIGN KEY (`id_pedido`) REFERENCES `pedido` (`id_pedido`) ON DELETE CASCADE,
  CONSTRAINT `fk_det_producto` FOREIGN KEY (`id_producto`) REFERENCES `producto` (`id_producto`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `detalle_pedido`
--

LOCK TABLES `detalle_pedido` WRITE;
/*!40000 ALTER TABLE `detalle_pedido` DISABLE KEYS */;
/*!40000 ALTER TABLE `detalle_pedido` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `direccion`
--

DROP TABLE IF EXISTS `direccion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `direccion` (
  `id_direccion` int(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11) NOT NULL,
  `departamento` varchar(50) NOT NULL DEFAULT 'Lambayeque',
  `provincia` varchar(50) NOT NULL DEFAULT 'Chiclayo',
  `distrito` varchar(50) NOT NULL,
  `calle` varchar(150) NOT NULL,
  `numero` varchar(20) DEFAULT NULL,
  `lote_manzana` varchar(30) DEFAULT NULL,
  `id_distrito` int(11) DEFAULT NULL,
  `referencia` text DEFAULT NULL,
  `telefono_contacto` varchar(20) DEFAULT NULL,
  `es_principal` tinyint(1) NOT NULL DEFAULT 0,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_registro` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_direccion`),
  KEY `fk_dir_usuario` (`id_usuario`),
  CONSTRAINT `fk_dir_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `direccion`
--

LOCK TABLES `direccion` WRITE;
/*!40000 ALTER TABLE `direccion` DISABLE KEYS */;
/*!40000 ALTER TABLE `direccion` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `distrito_delivery`
--

DROP TABLE IF EXISTS `distrito_delivery`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `distrito_delivery` (
  `id_distrito` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(80) NOT NULL,
  `costo_delivery` decimal(10,2) NOT NULL DEFAULT 5.00,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `orden` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id_distrito`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `distrito_delivery`
--

LOCK TABLES `distrito_delivery` WRITE;
/*!40000 ALTER TABLE `distrito_delivery` DISABLE KEYS */;
/*!40000 ALTER TABLE `distrito_delivery` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `envio_delivery`
--

DROP TABLE IF EXISTS `envio_delivery`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `envio_delivery` (
  `id_envio` int(11) NOT NULL AUTO_INCREMENT,
  `id_pedido` int(11) NOT NULL,
  `id_repartidor` int(11) DEFAULT NULL,
  `nombre_destinatario` varchar(100) NOT NULL,
  `celular_contacto` varchar(20) NOT NULL,
  `direccion_destino` text NOT NULL,
  `referencia` text DEFAULT NULL,
  `costo_delivery` decimal(10,2) NOT NULL DEFAULT 5.00,
  `estado_delivery` enum('Pendiente','Asignado','En Ruta','Entregado','Fallido') NOT NULL DEFAULT 'Pendiente',
  `cobrado_por_repartidor` tinyint(1) NOT NULL DEFAULT 0,
  `monto_cobrado` decimal(10,2) NOT NULL DEFAULT 0.00,
  `liquidado_en_caja` tinyint(1) NOT NULL DEFAULT 0,
  `fecha_asignacion` datetime DEFAULT NULL,
  `fecha_entrega` datetime DEFAULT NULL,
  `notas_entrega` text DEFAULT NULL,
  PRIMARY KEY (`id_envio`),
  UNIQUE KEY `id_pedido` (`id_pedido`),
  KEY `fk_envio_repartidor` (`id_repartidor`),
  CONSTRAINT `fk_envio_pedido` FOREIGN KEY (`id_pedido`) REFERENCES `pedido` (`id_pedido`) ON DELETE CASCADE,
  CONSTRAINT `fk_envio_repartidor` FOREIGN KEY (`id_repartidor`) REFERENCES `usuario` (`id_usuario`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `envio_delivery`
--

LOCK TABLES `envio_delivery` WRITE;
/*!40000 ALTER TABLE `envio_delivery` DISABLE KEYS */;
/*!40000 ALTER TABLE `envio_delivery` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `grupo_momento`
--

DROP TABLE IF EXISTS `grupo_momento`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `grupo_momento` (
  `id_grupo` int(11) NOT NULL AUTO_INCREMENT,
  `id_momento` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `imagen` varchar(255) DEFAULT NULL,
  `color` varchar(30) DEFAULT '#ff7f00',
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_grupo`),
  KEY `fk_gm_momento` (`id_momento`),
  CONSTRAINT `fk_gm_momento` FOREIGN KEY (`id_momento`) REFERENCES `momento_consumo` (`id_momento`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `grupo_momento`
--

LOCK TABLES `grupo_momento` WRITE;
/*!40000 ALTER TABLE `grupo_momento` DISABLE KEYS */;
/*!40000 ALTER TABLE `grupo_momento` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventario`
--

DROP TABLE IF EXISTS `inventario`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `inventario` (
  `id_inventario` int(11) NOT NULL AUTO_INCREMENT,
  `id_producto` int(11) NOT NULL,
  `stock_actual` int(11) NOT NULL DEFAULT 0,
  `stock_minimo` int(11) NOT NULL DEFAULT 5,
  `stock_maximo` int(11) NOT NULL DEFAULT 100,
  `ubicacion` varchar(100) NOT NULL DEFAULT 'Almac├®n principal',
  `ultima_act` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_inventario`),
  UNIQUE KEY `id_producto` (`id_producto`),
  CONSTRAINT `fk_inv_producto` FOREIGN KEY (`id_producto`) REFERENCES `producto` (`id_producto`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventario`
--

LOCK TABLES `inventario` WRITE;
/*!40000 ALTER TABLE `inventario` DISABLE KEYS */;
/*!40000 ALTER TABLE `inventario` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `kardex`
--

DROP TABLE IF EXISTS `kardex`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `kardex` (
  `id_kardex` int(11) NOT NULL AUTO_INCREMENT,
  `id_producto` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `tipo_movimiento` enum('ENTRADA','SALIDA','AJUSTE') NOT NULL,
  `motivo` varchar(100) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `costo_unitario` decimal(10,2) NOT NULL DEFAULT 0.00,
  `precio_unitario` decimal(10,2) NOT NULL DEFAULT 0.00,
  `stock_anterior` int(11) NOT NULL,
  `stock_actual` int(11) NOT NULL,
  `referencia_id` int(11) DEFAULT NULL,
  `referencia_tipo` varchar(50) DEFAULT NULL,
  `fecha` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_kardex`),
  KEY `fk_kardex_usuario` (`id_usuario`),
  KEY `idx_kardex_producto_fecha` (`id_producto`,`fecha`),
  CONSTRAINT `fk_kardex_producto` FOREIGN KEY (`id_producto`) REFERENCES `producto` (`id_producto`),
  CONSTRAINT `fk_kardex_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `kardex`
--

LOCK TABLES `kardex` WRITE;
/*!40000 ALTER TABLE `kardex` DISABLE KEYS */;
/*!40000 ALTER TABLE `kardex` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `metodo_pago`
--

DROP TABLE IF EXISTS `metodo_pago`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `metodo_pago` (
  `id_metodo` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL,
  `descripcion` varchar(150) DEFAULT NULL,
  `codigo_qr` varchar(255) DEFAULT NULL,
  `numero_cuenta` varchar(50) DEFAULT NULL,
  `titular` varchar(100) DEFAULT NULL,
  `requiere_comprobante` tinyint(1) NOT NULL DEFAULT 0,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_metodo`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `metodo_pago`
--

LOCK TABLES `metodo_pago` WRITE;
/*!40000 ALTER TABLE `metodo_pago` DISABLE KEYS */;
/*!40000 ALTER TABLE `metodo_pago` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `momento_consumo`
--

DROP TABLE IF EXISTS `momento_consumo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `momento_consumo` (
  `id_momento` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_momento`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `momento_consumo`
--

LOCK TABLES `momento_consumo` WRITE;
/*!40000 ALTER TABLE `momento_consumo` DISABLE KEYS */;
/*!40000 ALTER TABLE `momento_consumo` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `movimiento_caja`
--

DROP TABLE IF EXISTS `movimiento_caja`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `movimiento_caja` (
  `id_movimiento` int(11) NOT NULL AUTO_INCREMENT,
  `id_sesion` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `tipo` enum('INGRESO','EGRESO') NOT NULL,
  `categoria` enum('insumos','servicios','proveedor','adelanto','fondo_extra','otro') NOT NULL DEFAULT 'otro',
  `monto` decimal(10,2) NOT NULL,
  `motivo` varchar(255) NOT NULL,
  `comprobante_ref` varchar(100) DEFAULT NULL,
  `fecha` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_movimiento`),
  KEY `fk_mov_sesion` (`id_sesion`),
  KEY `fk_mov_usuario` (`id_usuario`),
  CONSTRAINT `fk_mov_sesion` FOREIGN KEY (`id_sesion`) REFERENCES `sesion_caja` (`id_sesion`) ON DELETE CASCADE,
  CONSTRAINT `fk_mov_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `movimiento_caja`
--

LOCK TABLES `movimiento_caja` WRITE;
/*!40000 ALTER TABLE `movimiento_caja` DISABLE KEYS */;
/*!40000 ALTER TABLE `movimiento_caja` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pago_verificacion`
--

DROP TABLE IF EXISTS `pago_verificacion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pago_verificacion` (
  `id_verificacion` int(11) NOT NULL AUTO_INCREMENT,
  `id_pedido` int(11) NOT NULL,
  `id_metodo` int(11) NOT NULL,
  `codigo_operacion` varchar(50) NOT NULL,
  `celular_remitente` varchar(20) NOT NULL,
  `titular_origen` varchar(100) DEFAULT NULL,
  `captura_voucher` varchar(255) NOT NULL,
  `monto_reportado` decimal(10,2) NOT NULL,
  `estado_verificacion` enum('Pendiente','Aprobado','Rechazado') NOT NULL DEFAULT 'Pendiente',
  `id_usuario_verificador` int(11) DEFAULT NULL,
  `motivo_rechazo` varchar(255) DEFAULT NULL,
  `fecha_envio` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_verificacion` datetime DEFAULT NULL,
  PRIMARY KEY (`id_verificacion`),
  KEY `fk_verif_pedido` (`id_pedido`),
  KEY `fk_verif_metodo` (`id_metodo`),
  KEY `fk_verif_usuario` (`id_usuario_verificador`),
  KEY `idx_verif_codigo` (`codigo_operacion`),
  CONSTRAINT `fk_verif_metodo` FOREIGN KEY (`id_metodo`) REFERENCES `metodo_pago` (`id_metodo`),
  CONSTRAINT `fk_verif_pedido` FOREIGN KEY (`id_pedido`) REFERENCES `pedido` (`id_pedido`) ON DELETE CASCADE,
  CONSTRAINT `fk_verif_usuario` FOREIGN KEY (`id_usuario_verificador`) REFERENCES `usuario` (`id_usuario`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pago_verificacion`
--

LOCK TABLES `pago_verificacion` WRITE;
/*!40000 ALTER TABLE `pago_verificacion` DISABLE KEYS */;
/*!40000 ALTER TABLE `pago_verificacion` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pedido`
--

DROP TABLE IF EXISTS `pedido`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pedido` (
  `id_pedido` int(11) NOT NULL AUTO_INCREMENT,
  `codigo_pedido` varchar(30) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `cliente_dni` varchar(15) DEFAULT '00000000',
  `cliente_nombre` varchar(150) DEFAULT 'P├║blico General',
  `id_cajero` int(11) DEFAULT NULL,
  `id_sesion_caja` int(11) DEFAULT NULL,
  `id_metodo` int(11) DEFAULT NULL,
  `id_direccion` int(11) DEFAULT NULL,
  `canal` enum('POS','WEB') NOT NULL DEFAULT 'POS',
  `tipo_entrega` enum('tienda','delivery') NOT NULL DEFAULT 'tienda',
  `horario_entrega` varchar(60) DEFAULT 'Lo antes posible (30-45 min)',
  `fecha_programada` datetime DEFAULT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `costo_envio` decimal(10,2) NOT NULL DEFAULT 0.00,
  `descuento` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total` decimal(10,2) NOT NULL,
  `monto_recibido` decimal(10,2) DEFAULT NULL,
  `vuelto` decimal(10,2) DEFAULT NULL,
  `estado` enum('Pendiente','Por Verificar','En Preparacion','En Camino','Entregado','Cancelado') NOT NULL DEFAULT 'Pendiente',
  `metodo_pago` varchar(50) DEFAULT NULL,
  `direccion_envio` text DEFAULT NULL,
  `observacion` text DEFAULT NULL,
  `fecha` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_pedido` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_pedido`),
  UNIQUE KEY `codigo_pedido` (`codigo_pedido`),
  KEY `fk_ped_usuario` (`id_usuario`),
  KEY `fk_ped_cajero` (`id_cajero`),
  KEY `fk_ped_caja` (`id_sesion_caja`),
  KEY `fk_ped_metodo` (`id_metodo`),
  KEY `fk_ped_direccion` (`id_direccion`),
  KEY `idx_pedido_canal_fecha` (`canal`,`fecha`),
  KEY `idx_pedido_estado` (`estado`),
  CONSTRAINT `fk_ped_caja` FOREIGN KEY (`id_sesion_caja`) REFERENCES `sesion_caja` (`id_sesion`) ON DELETE SET NULL,
  CONSTRAINT `fk_ped_cajero` FOREIGN KEY (`id_cajero`) REFERENCES `usuario` (`id_usuario`) ON DELETE SET NULL,
  CONSTRAINT `fk_ped_direccion` FOREIGN KEY (`id_direccion`) REFERENCES `direccion` (`id_direccion`) ON DELETE SET NULL,
  CONSTRAINT `fk_ped_metodo` FOREIGN KEY (`id_metodo`) REFERENCES `metodo_pago` (`id_metodo`) ON DELETE SET NULL,
  CONSTRAINT `fk_ped_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pedido`
--

LOCK TABLES `pedido` WRITE;
/*!40000 ALTER TABLE `pedido` DISABLE KEYS */;
/*!40000 ALTER TABLE `pedido` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pos_escaner_cola`
--

DROP TABLE IF EXISTS `pos_escaner_cola`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pos_escaner_cola` (
  `id_escaneo` int(11) NOT NULL AUTO_INCREMENT,
  `token_sesion` varchar(64) NOT NULL,
  `codigo_escaneado` varchar(150) NOT NULL,
  `nombre_producto` varchar(150) DEFAULT NULL,
  `id_producto` int(11) DEFAULT NULL,
  `cantidad` int(11) NOT NULL DEFAULT 1,
  `procesado` tinyint(1) NOT NULL DEFAULT 0,
  `dispositivo` varchar(100) DEFAULT 'M├│vil Esc├íner',
  `fecha` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_escaneo`),
  KEY `idx_token_procesado` (`token_sesion`,`procesado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pos_escaner_cola`
--

LOCK TABLES `pos_escaner_cola` WRITE;
/*!40000 ALTER TABLE `pos_escaner_cola` DISABLE KEYS */;
/*!40000 ALTER TABLE `pos_escaner_cola` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `producto`
--

DROP TABLE IF EXISTS `producto`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `producto` (
  `id_producto` int(11) NOT NULL AUTO_INCREMENT,
  `id_categoria` int(11) DEFAULT NULL,
  `id_grupo` int(11) DEFAULT NULL,
  `codigo` varchar(50) DEFAULT NULL,
  `codigo_barras` varchar(100) DEFAULT NULL,
  `nombre` varchar(150) NOT NULL,
  `titulo` varchar(150) NOT NULL,
  `marca` varchar(100) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `precio_costo` decimal(10,2) NOT NULL DEFAULT 0.00,
  `precio` decimal(10,2) NOT NULL,
  `precio_anterior` decimal(10,2) DEFAULT NULL,
  `unidad_medida` varchar(30) NOT NULL DEFAULT 'Unidad',
  `stock` int(11) NOT NULL DEFAULT 0,
  `imagen` varchar(255) DEFAULT NULL,
  `imagen_url` varchar(255) DEFAULT NULL,
  `es_sugerencia` tinyint(1) NOT NULL DEFAULT 0,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_producto`),
  UNIQUE KEY `codigo` (`codigo`),
  UNIQUE KEY `codigo_barras` (`codigo_barras`),
  KEY `fk_prod_categoria` (`id_categoria`),
  KEY `fk_prod_grupo` (`id_grupo`),
  KEY `idx_producto_codigo_barras` (`codigo_barras`),
  KEY `idx_producto_titulo` (`titulo`),
  CONSTRAINT `fk_prod_categoria` FOREIGN KEY (`id_categoria`) REFERENCES `categoria` (`id_categoria`) ON DELETE SET NULL,
  CONSTRAINT `fk_prod_grupo` FOREIGN KEY (`id_grupo`) REFERENCES `grupo_momento` (`id_grupo`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `producto`
--

LOCK TABLES `producto` WRITE;
/*!40000 ALTER TABLE `producto` DISABLE KEYS */;
/*!40000 ALTER TABLE `producto` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `proveedor`
--

DROP TABLE IF EXISTS `proveedor`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `proveedor` (
  `id_proveedor` int(11) NOT NULL AUTO_INCREMENT,
  `ruc` varchar(20) DEFAULT NULL,
  `razon_social` varchar(150) NOT NULL,
  `contacto_nombre` varchar(100) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `correo` varchar(150) DEFAULT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_registro` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_proveedor`),
  UNIQUE KEY `ruc` (`ruc`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `proveedor`
--

LOCK TABLES `proveedor` WRITE;
/*!40000 ALTER TABLE `proveedor` DISABLE KEYS */;
/*!40000 ALTER TABLE `proveedor` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reposicion`
--

DROP TABLE IF EXISTS `reposicion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reposicion` (
  `id_reposicion` int(11) NOT NULL AUTO_INCREMENT,
  `id_producto` int(11) NOT NULL,
  `id_proveedor` int(11) DEFAULT NULL,
  `id_usuario` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `costo_unitario` decimal(10,2) NOT NULL,
  `precio_venta_sugerido` decimal(10,2) DEFAULT NULL,
  `proveedor` varchar(150) DEFAULT NULL,
  `nro_comprobante` varchar(50) DEFAULT NULL,
  `lote` varchar(50) DEFAULT NULL,
  `fecha_vencimiento` date DEFAULT NULL,
  `observacion` text DEFAULT NULL,
  `fecha` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_reposicion`),
  KEY `fk_repo_producto` (`id_producto`),
  KEY `fk_repo_proveedor` (`id_proveedor`),
  KEY `fk_repo_usuario` (`id_usuario`),
  CONSTRAINT `fk_repo_producto` FOREIGN KEY (`id_producto`) REFERENCES `producto` (`id_producto`),
  CONSTRAINT `fk_repo_proveedor` FOREIGN KEY (`id_proveedor`) REFERENCES `proveedor` (`id_proveedor`) ON DELETE SET NULL,
  CONSTRAINT `fk_repo_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reposicion`
--

LOCK TABLES `reposicion` WRITE;
/*!40000 ALTER TABLE `reposicion` DISABLE KEYS */;
/*!40000 ALTER TABLE `reposicion` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sesion_caja`
--

DROP TABLE IF EXISTS `sesion_caja`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sesion_caja` (
  `id_sesion` int(11) NOT NULL AUTO_INCREMENT,
  `caja_id` varchar(50) NOT NULL DEFAULT 'CAJA-01',
  `id_usuario` int(11) NOT NULL,
  `token_emparejamiento` varchar(64) NOT NULL,
  `estado_conexion` enum('esperando','conectado','cerrado') NOT NULL DEFAULT 'esperando',
  `dispositivo_info` varchar(150) DEFAULT 'Esc├íner M├│vil Android',
  `items_json` longtext DEFAULT NULL,
  `total_venta` decimal(10,2) NOT NULL DEFAULT 0.00,
  `monto_apertura` decimal(10,2) NOT NULL DEFAULT 0.00,
  `monto_cierre_esperado` decimal(10,2) DEFAULT NULL,
  `monto_cierre_real` decimal(10,2) DEFAULT NULL,
  `diferencia` decimal(10,2) DEFAULT NULL,
  `total_ventas_efectivo` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_ventas_yape` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_ventas_plin` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_ventas_tarjeta` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_ingresos` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_egresos` decimal(10,2) NOT NULL DEFAULT 0.00,
  `estado` enum('abierta','cerrada') NOT NULL DEFAULT 'abierta',
  `fecha_apertura` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_cierre` datetime DEFAULT NULL,
  PRIMARY KEY (`id_sesion`),
  UNIQUE KEY `token_emparejamiento` (`token_emparejamiento`),
  KEY `fk_caja_usuario` (`id_usuario`),
  CONSTRAINT `fk_caja_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sesion_caja`
--

LOCK TABLES `sesion_caja` WRITE;
/*!40000 ALTER TABLE `sesion_caja` DISABLE KEYS */;
/*!40000 ALTER TABLE `sesion_caja` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sys_sessions`
--

DROP TABLE IF EXISTS `sys_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sys_sessions` (
  `id` varchar(255) NOT NULL,
  `data` longtext NOT NULL,
  `last_accessed` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_session_access` (`last_accessed`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sys_sessions`
--

LOCK TABLES `sys_sessions` WRITE;
/*!40000 ALTER TABLE `sys_sessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `sys_sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuario`
--

DROP TABLE IF EXISTS `usuario`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `usuario` (
  `id_usuario` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) DEFAULT NULL,
  `correo` varchar(150) NOT NULL,
  `password` varchar(255) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `dni` varchar(15) DEFAULT NULL,
  `rol` enum('admin','cajero','cliente','repartidor') NOT NULL DEFAULT 'cliente',
  `auth_provider` varchar(20) NOT NULL DEFAULT 'local',
  `google_id` varchar(255) DEFAULT NULL,
  `avatar_url` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `intentos_fallidos` int(11) NOT NULL DEFAULT 0,
  `bloqueo_until` datetime DEFAULT NULL,
  `fecha_registro` datetime NOT NULL DEFAULT current_timestamp(),
  `ultimo_login` datetime DEFAULT NULL,
  PRIMARY KEY (`id_usuario`),
  UNIQUE KEY `correo` (`correo`),
  UNIQUE KEY `dni` (`dni`),
  UNIQUE KEY `google_id` (`google_id`),
  KEY `idx_usuario_correo` (`correo`),
  KEY `idx_usuario_rol` (`rol`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuario`
--

LOCK TABLES `usuario` WRITE;
/*!40000 ALTER TABLE `usuario` DISABLE KEYS */;
/*!40000 ALTER TABLE `usuario` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `venta`
--

DROP TABLE IF EXISTS `venta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `venta` (
  `id_venta` int(11) NOT NULL AUTO_INCREMENT,
  `id_pedido` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `costo_envio` decimal(10,2) NOT NULL DEFAULT 0.00,
  `igv` decimal(10,2) NOT NULL DEFAULT 0.00,
  `descuento` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total` decimal(10,2) NOT NULL,
  `tipo_comprobante` varchar(30) NOT NULL DEFAULT 'boleta',
  `numero_comprobante` varchar(50) NOT NULL,
  `estado_pago` varchar(50) NOT NULL DEFAULT 'pendiente',
  `fecha` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_venta`),
  KEY `idx_venta_pedido` (`id_pedido`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `venta`
--

LOCK TABLES `venta` WRITE;
/*!40000 ALTER TABLE `venta` DISABLE KEYS */;
/*!40000 ALTER TABLE `venta` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-09-29  3:43:13
