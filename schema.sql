-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: lapify
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
-- Current Database: `lapify`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `lapify` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */;

USE `lapify`;

--
-- Table structure for table `admins`
--

DROP TABLE IF EXISTS `admins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(100) DEFAULT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `secret_key` varchar(255) NOT NULL DEFAULT '',
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_expires` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admins`
--

LOCK TABLES `admins` WRITE;
/*!40000 ALTER TABLE `admins` DISABLE KEYS */;
INSERT INTO `admins` VALUES (1,'admin','Lapify Admin','admin@lapify.com','9876542123','$2y$10$wgyzTy8WBUvNoA0xRgE1OuCYjiAsT8/1Rgy9pwjVkkaiGKTteIGM.','1787164283_700762_b42e41c6.jpg','','active',NULL,NULL,'2026-08-18 14:19:42');
/*!40000 ALTER TABLE `admins` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `brand_models`
--

DROP TABLE IF EXISTS `brand_models`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `brand_models` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `brand_id` int(11) NOT NULL,
  `model_name` varchar(150) NOT NULL,
  `year` year(4) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_brand_model` (`brand_id`,`model_name`),
  CONSTRAINT `brand_models_ibfk_1` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=82 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `brand_models`
--

LOCK TABLES `brand_models` WRITE;
/*!40000 ALTER TABLE `brand_models` DISABLE KEYS */;
INSERT INTO `brand_models` VALUES (1,1,'MacBook Air 13\" M3',2024,'2026-08-18 14:19:42'),(2,1,'MacBook Air 15\" M3',2024,'2026-08-18 14:19:42'),(3,1,'MacBook Pro 14\" M3 Pro',2024,'2026-08-18 14:19:42'),(4,1,'MacBook Pro 16\" M3 Max',2024,'2026-08-18 14:19:42'),(5,1,'MacBook Air 13\" M4',2025,'2026-08-18 14:19:42'),(6,1,'MacBook Air 15\" M4',2025,'2026-08-18 14:19:42'),(7,1,'MacBook Pro 14\" M4 Pro',2025,'2026-08-18 14:19:42'),(8,1,'MacBook Pro 16\" M4 Max',2025,'2026-08-18 14:19:42'),(9,1,'MacBook Air 13\" M5',2026,'2026-08-18 14:19:42'),(10,1,'MacBook Pro 14\" M5 Pro',2026,'2026-08-18 14:19:42'),(11,2,'Dell XPS 13 9340',2024,'2026-08-18 14:19:42'),(12,2,'Dell XPS 14 9440',2024,'2026-08-18 14:19:42'),(13,2,'Dell XPS 16 9640',2024,'2026-08-18 14:19:42'),(14,2,'Dell Inspiron 14 5440',2024,'2026-08-18 14:19:42'),(15,2,'Dell Latitude 7450',2024,'2026-08-18 14:19:42'),(16,2,'Dell XPS 13 9350',2025,'2026-08-18 14:19:42'),(17,2,'Dell XPS 16 9650',2025,'2026-08-18 14:19:42'),(18,2,'Dell Inspiron 15 5545',2025,'2026-08-18 14:19:42'),(19,2,'Dell Precision 5680',2025,'2026-08-18 14:19:42'),(20,2,'Dell XPS 13 9360',2026,'2026-08-18 14:19:42'),(21,2,'Dell Inspiron 16 7640',2026,'2026-08-18 14:19:42'),(22,3,'HP Spectre x360 14',2024,'2026-08-18 14:19:42'),(23,3,'HP Spectre x360 16',2024,'2026-08-18 14:19:42'),(24,3,'HP Envy x360 15',2024,'2026-08-18 14:19:42'),(25,3,'HP Pavilion 15',2024,'2026-08-18 14:19:42'),(26,3,'HP Omen 16',2024,'2026-08-18 14:19:42'),(27,3,'HP Spectre x360 14 2025',2025,'2026-08-18 14:19:42'),(28,3,'HP Envy 16',2025,'2026-08-18 14:19:42'),(29,3,'HP Pavilion Plus 14',2025,'2026-08-18 14:19:42'),(30,3,'HP Omen Transcend 16',2025,'2026-08-18 14:19:42'),(31,3,'HP Spectre Fold 17',2026,'2026-08-18 14:19:42'),(32,3,'HP Envy x360 14',2026,'2026-08-18 14:19:42'),(33,4,'Lenovo ThinkPad X1 Carbon Gen 12',2024,'2026-08-18 14:19:42'),(34,4,'Lenovo ThinkPad T14s Gen 5',2024,'2026-08-18 14:19:42'),(35,4,'Lenovo Yoga 9i 14',2024,'2026-08-18 14:19:42'),(36,4,'Lenovo Legion 5 Pro 16',2024,'2026-08-18 14:19:42'),(37,4,'Lenovo IdeaPad Slim 5',2024,'2026-08-18 14:19:42'),(38,4,'Lenovo ThinkPad X1 Carbon Gen 13',2025,'2026-08-18 14:19:42'),(39,4,'Lenovo Yoga Slim 7x',2025,'2026-08-18 14:19:42'),(40,4,'Lenovo Legion 7i 16',2025,'2026-08-18 14:19:42'),(41,4,'Lenovo ThinkBook 14 Gen 7',2025,'2026-08-18 14:19:42'),(42,4,'Lenovo ThinkPad X1 Nano Gen 4',2026,'2026-08-18 14:19:42'),(43,4,'Lenovo Yoga Pro 9i',2026,'2026-08-18 14:19:42'),(44,5,'Asus ROG Zephyrus G14',2024,'2026-08-18 14:19:42'),(45,5,'Asus ROG Zephyrus G16',2024,'2026-08-18 14:19:42'),(46,5,'Asus Zenbook 14 OLED',2024,'2026-08-18 14:19:42'),(47,5,'Asus Vivobook 16',2024,'2026-08-18 14:19:42'),(48,5,'Asus TUF Gaming A16',2024,'2026-08-18 14:19:42'),(49,5,'Asus ROG Strix Scar 18',2025,'2026-08-18 14:19:42'),(50,5,'Asus Zenbook S 16',2025,'2026-08-18 14:19:42'),(51,5,'Asus Vivobook S 15',2025,'2026-08-18 14:19:42'),(52,5,'Asus ROG Flow Z13',2025,'2026-08-18 14:19:42'),(53,5,'Asus ROG Zephyrus G14 2026',2026,'2026-08-18 14:19:42'),(54,5,'Asus Zenbook Duo 14',2026,'2026-08-18 14:19:42'),(55,6,'Acer Swift Go 14',2024,'2026-08-18 14:19:42'),(56,6,'Acer Swift X 14',2024,'2026-08-18 14:19:42'),(57,6,'Acer Aspire 5',2024,'2026-08-18 14:19:42'),(58,6,'Acer Predator Helios 16',2024,'2026-08-18 14:19:42'),(59,6,'Acer Nitro V 15',2024,'2026-08-18 14:19:42'),(60,6,'Acer Swift Go 14 2025',2025,'2026-08-18 14:19:42'),(61,6,'Acer Aspire Vero 16',2025,'2026-08-18 14:19:42'),(62,6,'Acer Predator Helios Neo 16',2025,'2026-08-18 14:19:42'),(63,6,'Acer Nitro 16',2025,'2026-08-18 14:19:42'),(64,6,'Acer Swift Edge 16',2026,'2026-08-18 14:19:42'),(65,6,'Acer Predator Triton 14',2026,'2026-08-18 14:19:42'),(66,7,'MSI Stealth 14 Studio',2024,'2026-08-18 14:19:42'),(67,7,'MSI Stealth 16 Studio',2024,'2026-08-18 14:19:42'),(68,7,'MSI Raider GE68 HX',2024,'2026-08-18 14:19:42'),(69,7,'MSI Katana 15',2024,'2026-08-18 14:19:42'),(70,7,'MSI Prestige 16 AI',2024,'2026-08-18 14:19:42'),(71,7,'MSI Stealth 18 AI Studio',2025,'2026-08-18 14:19:42'),(72,7,'MSI Raider 18 HX',2025,'2026-08-18 14:19:42'),(73,7,'MSI Titan 18 HX',2025,'2026-08-18 14:19:42'),(74,7,'MSI Cyborg 15',2025,'2026-08-18 14:19:42'),(75,7,'MSI Stealth 16 AI Studio',2026,'2026-08-18 14:19:42'),(76,7,'MSI Creator Z16 HX',2026,'2026-08-18 14:19:42'),(77,1,'Test Pro 486',2026,'2026-08-19 19:13:37'),(78,1,'Test Pro 727',2026,'2026-08-19 19:14:44'),(79,1,'Test Pro 979',2026,'2026-08-19 19:15:02'),(80,1,'MacBook Air M3 Test',2026,'2026-08-19 19:29:06'),(81,1,'MacBook Air M3 Test 1787148561',2026,'2026-08-19 19:39:21');
/*!40000 ALTER TABLE `brand_models` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `brands`
--

DROP TABLE IF EXISTS `brands`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `brands` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `brand_name` varchar(100) NOT NULL,
  `logo_path` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `brand_name` (`brand_name`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `brands`
--

LOCK TABLES `brands` WRITE;
/*!40000 ALTER TABLE `brands` DISABLE KEYS */;
INSERT INTO `brands` VALUES (1,'Apple',NULL,'active','2026-08-18 14:19:42'),(2,'Dell',NULL,'active','2026-08-18 14:19:42'),(3,'HP',NULL,'active','2026-08-18 14:19:42'),(4,'Lenovo',NULL,'active','2026-08-18 14:19:42'),(5,'Asus',NULL,'active','2026-08-18 14:19:42'),(6,'Acer',NULL,'active','2026-08-18 14:19:42'),(7,'MSI',NULL,'active','2026-08-18 14:19:42'),(8,'LG','uploads/brands/1787088221_593229_94796d7b.png','active','2026-08-19 02:53:41');
/*!40000 ALTER TABLE `brands` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cart`
--

DROP TABLE IF EXISTS `cart`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cart` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `laptop_id` int(11) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_cart_item` (`user_id`,`laptop_id`),
  KEY `laptop_id` (`laptop_id`),
  CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`laptop_id`) REFERENCES `laptops` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cart`
--

LOCK TABLES `cart` WRITE;
/*!40000 ALTER TABLE `cart` DISABLE KEYS */;
/*!40000 ALTER TABLE `cart` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contact_queries`
--

DROP TABLE IF EXISTS `contact_queries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `contact_queries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `subject` varchar(200) DEFAULT NULL,
  `message` text NOT NULL,
  `admin_reply` text DEFAULT NULL,
  `replied_at` datetime DEFAULT NULL,
  `replied_by` int(11) DEFAULT NULL,
  `status` enum('new','read','resolved') NOT NULL DEFAULT 'new',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `contact_queries_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contact_queries`
--

LOCK TABLES `contact_queries` WRITE;
/*!40000 ALTER TABLE `contact_queries` DISABLE KEYS */;
INSERT INTO `contact_queries` VALUES (1,4,'Yash Kotak','kotakyash192@gmail.com','demo test','demoemowd','now it is done ???','2026-08-18 14:54:38',1,'resolved','2026-08-18 14:23:56','2026-08-18 14:54:38'),(2,2,'Alex Johnson','alex@example.com','demo test','denmo test','i ndsifssffs','2026-08-19 13:55:55',1,'resolved','2026-08-19 13:55:16','2026-08-19 13:55:55');
/*!40000 ALTER TABLE `contact_queries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `laptops`
--

DROP TABLE IF EXISTS `laptops`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `laptops` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `brand_id` int(11) NOT NULL,
  `type` enum('New','Old') NOT NULL,
  `condition_type` enum('new','old') NOT NULL DEFAULT 'old',
  `model` varchar(100) NOT NULL,
  `processor` varchar(100) DEFAULT NULL,
  `ram` varchar(20) DEFAULT NULL,
  `storage` varchar(20) DEFAULT NULL,
  `condition` varchar(50) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 5,
  `stock_quantity` int(11) NOT NULL DEFAULT 1,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `approval_status` varchar(20) NOT NULL DEFAULT 'pending',
  `rejection_reason` text DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `brand_id` (`brand_id`),
  CONSTRAINT `laptops_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `laptops_ibfk_2` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=84 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `laptops`
--

LOCK TABLES `laptops` WRITE;
/*!40000 ALTER TABLE `laptops` DISABLE KEYS */;
INSERT INTO `laptops` VALUES (1,2,1,'New','new','MacBook Air 13\" M3','Apple M3 8-Core','8GB','256GB SSD','Brand New',109900.00,'Brand new Apple MacBook Air 13 inch with M3 chip, 8GB unified memory, 256GB SSD. Midnight color with full Apple warranty.',NULL,10,10,'approved','approved',NULL,NULL,NULL,NULL,NULL,'2026-08-18 14:19:42'),(2,3,1,'Old','old','MacBook Air 15\" M3','Apple M3 8-Core','8GB','512GB SSD','Like New',95000.00,'Gently used MacBook Air 15 M3, battery health 96%, comes with original charger. Starlight color.',NULL,10,10,'approved','approved',NULL,NULL,NULL,NULL,NULL,'2026-08-18 14:19:42'),(3,2,1,'New','new','MacBook Pro 14\" M3 Pro','Apple M3 Pro 11-Core','18GB','512GB SSD','Brand New',199900.00,'Brand new MacBook Pro 14 inch with M3 Pro chip, 18GB unified memory, 512GB SSD. Space Black.',NULL,10,10,'approved','approved',NULL,NULL,NULL,NULL,NULL,'2026-08-18 14:19:42'),(4,3,1,'Old','old','MacBook Pro 16\" M3 Max','Apple M3 Max 16-Core','36GB','1TB SSD','Good',245000.00,'Used MacBook Pro 16 M3 Max, minor wear on bottom case, battery health 88%.',NULL,10,10,'approved','approved',NULL,NULL,NULL,NULL,NULL,'2026-08-18 14:19:42'),(5,2,1,'New','new','MacBook Air 13\" M4','Apple M4 10-Core','16GB','512GB SSD','Brand New',124900.00,'Latest MacBook Air 13 with M4 chip, 16GB RAM, 512GB SSD. Sky Blue color, sealed box.',NULL,10,10,'approved','approved',NULL,NULL,NULL,NULL,NULL,'2026-08-18 14:19:42'),(6,3,1,'Old','old','MacBook Air 15\" M4','Apple M4 10-Core','16GB','512GB SSD','Like New',105000.00,'MacBook Air 15 M4 in excellent condition, only 3 months old, battery cycle count 45.',NULL,10,10,'approved','approved',NULL,NULL,NULL,NULL,NULL,'2026-08-18 14:19:42'),(7,2,1,'New','new','MacBook Pro 14\" M4 Pro','Apple M4 Pro 14-Core','24GB','1TB SSD','Brand New',249900.00,'Brand new MacBook Pro 14 with M4 Pro chip, 24GB unified memory, 1TB SSD. Space Black, sealed.',NULL,10,10,'approved','approved',NULL,NULL,NULL,NULL,NULL,'2026-08-18 14:19:42'),(8,3,1,'Old','old','MacBook Pro 16\" M4 Max','Apple M4 Max 16-Core','48GB','1TB SSD','Excellent',280000.00,'MacBook Pro 16 M4 Max, barely used, comes with box and all accessories.',NULL,10,10,'approved','approved',NULL,NULL,NULL,NULL,NULL,'2026-08-18 14:19:42'),(9,2,1,'New','new','MacBook Air 13\" M5','Apple M5 10-Core','16GB','512GB SSD','Brand New',134900.00,'Newest MacBook Air 13 with M5 chip, 16GB RAM, 512GB SSD. Midnight color, sealed.',NULL,10,10,'approved','approved',NULL,NULL,NULL,NULL,NULL,'2026-08-18 14:19:42'),(10,3,1,'Old','old','MacBook Pro 14\" M5 Pro','Apple M5 Pro 14-Core','24GB','1TB SSD','Good',220000.00,'Used MacBook Pro 14 M5 Pro, light wear, battery health 90%.',NULL,10,10,'approved','approved',NULL,NULL,NULL,NULL,NULL,'2026-08-18 14:19:42'),(11,2,2,'New','new','Dell XPS 13 9340','Intel Core Ultra 7 155H','16GB','512GB SSD','Brand New',129900.00,'Brand new Dell XPS 13 with Intel Core Ultra 7, 16GB RAM, 512GB SSD. Platinum Silver.',NULL,10,10,'approved','approved',NULL,NULL,NULL,NULL,NULL,'2026-08-18 14:19:42'),(12,3,2,'Old','old','Dell XPS 14 9440','Intel Core Ultra 7 155H','32GB','1TB SSD','Like New',125000.00,'Dell XPS 14 in excellent condition, 6 months old, comes with charger.',NULL,10,10,'approved','approved',NULL,NULL,NULL,NULL,NULL,'2026-08-18 14:19:42'),(13,2,2,'New','new','Dell XPS 16 9640','Intel Core Ultra 9 185H','32GB','1TB SSD','Brand New',219900.00,'Dell XPS 16 with RTX 4060, Intel Core Ultra 9, 32GB RAM, 1TB SSD. Platinum.',NULL,10,10,'approved','approved',NULL,NULL,NULL,NULL,NULL,'2026-08-18 14:19:42'),(14,3,2,'Old','old','Dell Inspiron 14 5440','Intel Core i5 13th Gen','16GB','512GB SSD','Good',42000.00,'Used Dell Inspiron 14, visible signs of use, works perfectly.',NULL,10,10,'approved','approved',NULL,NULL,NULL,NULL,NULL,'2026-08-18 14:19:42'),(15,2,2,'New','new','Dell Latitude 7450','Intel Core Ultra 5 125U','16GB','512GB SSD','Brand New',89900.00,'Business-grade Dell Latitude 7450, Intel Core Ultra 5, 16GB RAM, 512GB SSD.',NULL,10,10,'approved','approved',NULL,NULL,NULL,NULL,NULL,'2026-08-18 14:19:42'),(16,3,2,'Old','old','Dell XPS 13 9350','Intel Core Ultra 7 155H','16GB','512GB SSD','Like New',98000.00,'Dell XPS 13 9350 barely used, comes with original box and accessories.',NULL,10,10,'approved','approved',NULL,NULL,NULL,NULL,NULL,'2026-08-18 14:19:42'),(17,2,2,'New','new','Dell XPS 16 9650','Intel Core Ultra 9 285H','32GB','1TB SSD','Brand New',229900.00,'Dell XPS 16 9650 with RTX 4070, Intel Core Ultra 9, 32GB RAM, 1TB SSD. Sealed.',NULL,10,10,'approved','approved',NULL,NULL,NULL,NULL,NULL,'2026-08-18 14:19:42'),(18,3,2,'Old','old','Dell Inspiron 15 5545','AMD Ryzen 5 7530U','16GB','512GB SSD','Fair',35000.00,'Used Dell Inspiron 15, visible wear, works fine for daily tasks.',NULL,10,10,'approved','approved',NULL,NULL,NULL,NULL,NULL,'2026-08-18 14:19:42'),(19,2,2,'New','new','Dell Precision 5680','Intel Core i7 13th Gen','32GB','1TB SSD','Brand New',189900.00,'Dell Precision 5680 workstation, Intel i7, 32GB RAM, 1TB SSD. Professional grade.',NULL,10,10,'approved','approved',NULL,NULL,NULL,NULL,NULL,'2026-08-18 14:19:42'),(20,3,2,'Old','old','Dell XPS 13 9360','Intel Core Ultra 7 155H','16GB','512GB SSD','Good',90000.00,'Dell XPS 13 9360, clean condition, battery health 92%.',NULL,10,10,'approved','approved',NULL,NULL,NULL,NULL,NULL,'2026-08-18 14:19:42'),(21,2,3,'New','new','HP Spectre x360 14','Intel Core Ultra 7 155H','16GB','1TB SSD','Brand New',149900.00,'HP Spectre x360 14 convertible, Intel Core Ultra 7, 16GB RAM, 1TB SSD. Nightfall Black.',NULL,10,10,'approved','approved',NULL,NULL,NULL,NULL,NULL,'2026-08-18 14:19:42'),(22,3,3,'Old','old','HP Spectre x360 16','Intel Core Ultra 7 155H','32GB','1TB SSD','Like New',135000.00,'HP Spectre x360 16 in excellent condition, 4 months old, includes stylus.',NULL,10,10,'approved','approved',NULL,NULL,NULL,NULL,NULL,'2026-08-18 14:19:42'),(23,2,3,'New','new','HP Envy x360 15','AMD Ryzen 7 8840HS','16GB','1TB SSD','Brand New',99900.00,'HP Envy x360 15 with AMD Ryzen 7, 16GB RAM, 1TB SSD. Silver, sealed.',NULL,10,10,'approved','approved',NULL,NULL,NULL,NULL,NULL,'2026-08-18 14:19:42'),(24,3,3,'Old','old','HP Pavilion 15','Intel Core i5 13th Gen','16GB','512GB SSD','Fair',38000.00,'Used HP Pavilion 15, visible wear, works fine for daily tasks.',NULL,10,10,'approved','approved',NULL,NULL,NULL,NULL,NULL,'2026-08-18 14:19:42'),(25,2,3,'New','new','HP Omen 16','Intel Core i7 14th Gen','32GB','1TB SSD','Brand New',149900.00,'HP Omen 16 gaming laptop, RTX 4070, Intel i7, 32GB RAM, 1TB SSD. Shadow Black.',NULL,10,10,'approved','approved',NULL,NULL,NULL,NULL,NULL,'2026-08-18 14:19:42'),(26,3,3,'Old','old','HP Spectre x360 14 2025','Intel Core Ultra 7 155H','16GB','1TB SSD','Excellent',115000.00,'HP Spectre x360 14 2025, barely used, comes with original box.',NULL,10,10,'approved','approved',NULL,NULL,NULL,NULL,NULL,'2026-08-18 14:19:42'),(27,2,3,'New','new','HP Envy 16','Intel Core Ultra 7 155H','32GB','1TB SSD','Brand New',119900.00,'HP Envy 16 with RTX 4050, Intel Core Ultra 7, 32GB RAM, 1TB SSD. Sealed.',NULL,10,10,'approved','approved',NULL,NULL,NULL,NULL,NULL,'2026-08-18 14:19:42'),(28,3,3,'Old','old','HP Pavilion Plus 14','Intel Core Ultra 5 125U','16GB','512GB SSD','Good',55000.00,'HP Pavilion Plus 14, light wear, battery health 89%.',NULL,10,10,'approved','approved',NULL,NULL,NULL,NULL,NULL,'2026-08-18 14:19:42'),(29,2,3,'New','new','HP Omen Transcend 16','Intel Core i9 14th Gen','32GB','2TB SSD','Brand New',199900.00,'HP Omen Transcend 16, RTX 4080, Intel i9, 32GB RAM, 2TB SSD. Premium gaming.',NULL,10,10,'approved','approved',NULL,NULL,NULL,NULL,NULL,'2026-08-18 14:19:42'),(30,3,3,'Old','old','HP Spectre Fold 17','Intel Core Ultra 7 155H','16GB','1TB SSD','Excellent',210000.00,'HP Spectre Fold 17, barely used, comes with original box and accessories.',NULL,10,10,'approved','approved',NULL,NULL,NULL,NULL,NULL,'2026-08-18 14:19:42'),(31,2,4,'New','new','Lenovo ThinkPad X1 Carbon Gen 12','Intel Core Ultra 7 155U','32GB','1TB SSD','Brand New',169900.00,'Lenovo ThinkPad X1 Carbon Gen 12, Intel Core Ultra 7, 32GB RAM, 1TB SSD. Black.',NULL,10,10,'approved','approved',NULL,NULL,NULL,NULL,NULL,'2026-08-18 14:19:42'),(32,3,4,'Old','old','Lenovo ThinkPad T14s Gen 5','Intel Core Ultra 7 155U','32GB','512GB SSD','Like New',88000.00,'ThinkPad T14s Gen 5, corporate use, clean condition, battery health 91%.',NULL,10,10,'approved','approved',NULL,NULL,NULL,NULL,NULL,'2026-08-18 14:19:42'),(33,2,4,'New','new','Lenovo Yoga 9i 14','Intel Core Ultra 7 155H','16GB','1TB SSD','Brand New',129900.00,'Lenovo Yoga 9i 14 convertible, Intel Core Ultra 7, 16GB RAM, 1TB SSD. Storm Grey.',NULL,10,10,'approved','approved',NULL,NULL,NULL,NULL,NULL,'2026-08-18 14:19:42'),(34,3,4,'Old','old','Lenovo Legion 5 Pro 16','AMD Ryzen 7 8845HS','32GB','1TB SSD','Good',105000.00,'Legion 5 Pro gaming laptop, minor wear, battery health 87%.',NULL,10,10,'approved','approved',NULL,NULL,NULL,NULL,NULL,'2026-08-18 14:19:42'),(35,2,4,'New','new','Lenovo IdeaPad Slim 5','AMD Ryzen 5 7530U','16GB','512GB SSD','Brand New',54900.00,'Lenovo IdeaPad Slim 5 with AMD Ryzen 5, 16GB RAM, 512GB SSD. Cloud Grey.',NULL,10,10,'approved','approved',NULL,NULL,NULL,NULL,NULL,'2026-08-18 14:19:42'),(36,3,4,'Old','old','Lenovo ThinkPad X1 Carbon Gen 13','Intel Core Ultra 7 155U','32GB','1TB SSD','Excellent',130000.00,'ThinkPad X1 Carbon Gen 13, barely used, comes with original box.',NULL,10,10,'approved','approved',NULL,NULL,NULL,NULL,NULL,'2026-08-18 14:19:42'),(37,2,4,'New','new','Lenovo Yoga Slim 7x','Snapdragon X Elite','32GB','1TB SSD','Brand New',99900.00,'Lenovo Yoga Slim 7x with Snapdragon X Elite, 32GB RAM, 1TB SSD. Sealed.',NULL,10,10,'approved','approved',NULL,NULL,NULL,NULL,NULL,'2026-08-18 14:19:42'),(38,3,4,'Old','old','Lenovo Legion 7i 16','Intel Core i9 14th Gen','32GB','2TB SSD','Good',145000.00,'Legion 7i 16 gaming laptop, minor wear, battery health 90%.',NULL,10,10,'approved','approved',NULL,NULL,NULL,NULL,NULL,'2026-08-18 14:19:42'),(39,2,4,'New','new','Lenovo ThinkBook 14 Gen 7','Intel Core Ultra 5 125U','16GB','512GB SSD','Brand New',69900.00,'Lenovo ThinkBook 14 Gen 7, Intel Core Ultra 5, 16GB RAM, 512GB SSD. Business ready.',NULL,10,10,'approved','approved',NULL,NULL,NULL,NULL,NULL,'2026-08-18 14:19:42'),(40,3,4,'Old','old','Lenovo ThinkPad X1 Nano Gen 4','Intel Core Ultra 7 155U','16GB','512GB SSD','Like New',85000.00,'ThinkPad X1 Nano Gen 4, ultra-light, excellent condition.',NULL,10,10,'approved','approved',NULL,NULL,NULL,NULL,NULL,'2026-08-18 14:19:42'),(41,2,5,'New','new','Asus ROG Zephyrus G14','AMD Ryzen 9 8945HS','32GB','1TB SSD','Brand New',159900.00,'Asus ROG Zephyrus G14, RTX 4060, AMD Ryzen 9, 32GB RAM, 1TB SSD. Eclipse Grey.',NULL,10,10,'approved','approved',NULL,NULL,NULL,NULL,NULL,'2026-08-18 14:19:42'),(42,3,5,'Old','old','Asus ROG Zephyrus G16','Intel Core Ultra 9 185H','32GB','1TB SSD','Like New',140000.00,'ROG Zephyrus G16 in excellent condition, 4 months old, comes with box.',NULL,10,10,'approved','approved',NULL,NULL,NULL,NULL,NULL,'2026-08-18 14:19:42'),(43,2,5,'New','new','Asus Zenbook 14 OLED','Intel Core Ultra 7 155H','16GB','1TB SSD','Brand New',99900.00,'Asus Zenbook 14 OLED, Intel Core Ultra 7, 16GB RAM, 1TB SSD. Ponder Blue.',NULL,10,10,'approved','approved',NULL,NULL,NULL,NULL,NULL,'2026-08-18 14:19:42'),(44,3,5,'Old','old','Asus Vivobook 16','Intel Core i5 13th Gen','16GB','512GB SSD','Fair',36000.00,'Used Vivobook 16, visible wear, works fine for daily use.',NULL,10,10,'approved','approved',NULL,NULL,NULL,NULL,NULL,'2026-08-18 14:19:42'),(45,2,5,'New','new','Asus TUF Gaming A16','AMD Ryzen 7 8845HS','16GB','1TB SSD','Brand New',89900.00,'Asus TUF Gaming A16, RTX 4060, AMD Ryzen 7, 16GB RAM, 1TB SSD. Mecha Grey.',NULL,10,10,'approved','approved',NULL,NULL,NULL,NULL,NULL,'2026-08-18 14:19:42'),(46,3,5,'Old','old','Asus ROG Strix Scar 18','Intel Core i9 14th Gen','32GB','2TB SSD','Excellent',230000.00,'ROG Strix Scar 18, barely used, comes with original accessories.',NULL,10,10,'approved','approved',NULL,NULL,NULL,NULL,NULL,'2026-08-18 14:19:42'),(47,2,5,'New','new','Asus Zenbook S 16','AMD Ryzen AI 9 HX 370','32GB','1TB SSD','Brand New',119900.00,'Asus Zenbook S 16 with AMD Ryzen AI 9, 32GB RAM, 1TB SSD. Sealed.',NULL,10,10,'approved','approved',NULL,NULL,NULL,NULL,NULL,'2026-08-18 14:19:42'),(48,3,5,'Old','old','Asus Vivobook S 15','Snapdragon X Elite','16GB','1TB SSD','Good',65000.00,'Vivobook S 15, light wear, battery health 88%.',NULL,10,10,'approved','approved',NULL,NULL,NULL,NULL,NULL,'2026-08-18 14:19:42'),(49,2,5,'New','new','Asus ROG Flow Z13','Intel Core Ultra 9 185H','32GB','1TB SSD','Brand New',179900.00,'Asus ROG Flow Z13 gaming tablet, RTX 4060, Intel Core Ultra 9, 32GB RAM. Sealed.',NULL,10,10,'approved','approved',NULL,NULL,NULL,NULL,NULL,'2026-08-18 14:19:42'),(50,3,5,'Old','old','Asus ROG Zephyrus G14 2026','AMD Ryzen AI 9 HX 370','32GB','1TB SSD','Excellent',135000.00,'ROG Zephyrus G14 2026, barely used, comes with original box.',NULL,10,10,'approved','approved',NULL,NULL,NULL,NULL,NULL,'2026-08-18 14:19:42'),(51,2,6,'New','new','Acer Swift Go 14','AMD Ryzen 7 8845HS','16GB','512GB SSD','Brand New',69900.00,'Acer Swift Go 14 with AMD Ryzen 7, 16GB RAM, 512GB SSD. Pure Silver.',NULL,10,10,'approved','approved',NULL,NULL,NULL,NULL,NULL,'2026-08-18 14:19:42'),(52,3,6,'Old','old','Acer Swift X 14','Intel Core Ultra 7 155H','16GB','1TB SSD','Like New',85000.00,'Swift X 14 barely used, comes with original box and charger.',NULL,10,10,'approved','approved',NULL,NULL,NULL,NULL,NULL,'2026-08-18 14:19:42'),(53,2,6,'New','new','Acer Aspire 5','Intel Core i5 13th Gen','16GB','512GB SSD','Brand New',49900.00,'Acer Aspire 5 with Intel i5, 16GB RAM, 512GB SSD. Steel Grey.',NULL,10,10,'approved','approved',NULL,NULL,NULL,NULL,NULL,'2026-08-18 14:19:42'),(54,3,6,'Old','old','Acer Predator Helios 16','Intel Core i9 14th Gen','32GB','1TB SSD','Good',140000.00,'Predator Helios 16 gaming laptop, minor wear, battery health 85%.',NULL,10,10,'approved','approved',NULL,NULL,NULL,NULL,NULL,'2026-08-18 14:19:42'),(55,2,6,'New','new','Acer Nitro V 15','AMD Ryzen 7 8845HS','16GB','1TB SSD','Brand New',79900.00,'Acer Nitro V 15 gaming laptop, RTX 4050, AMD Ryzen 7, 16GB RAM. Obsidian Black.',NULL,10,10,'approved','approved',NULL,NULL,NULL,NULL,NULL,'2026-08-18 14:19:42'),(56,3,6,'Old','old','Acer Swift Go 14 2025','AMD Ryzen 7 8845HS','16GB','512GB SSD','Excellent',52000.00,'Acer Swift Go 14 2025, barely used, comes with original box.',NULL,10,10,'approved','approved',NULL,NULL,NULL,NULL,NULL,'2026-08-18 14:19:42'),(57,2,6,'New','new','Acer Aspire Vero 16','Intel Core Ultra 5 125U','16GB','512GB SSD','Brand New',59900.00,'Acer Aspire Vero 16 eco-friendly laptop, Intel Core Ultra 5, 16GB RAM. Sealed.',NULL,10,10,'approved','approved',NULL,NULL,NULL,NULL,NULL,'2026-08-18 14:19:42'),(58,3,6,'Old','old','Acer Predator Helios Neo 16','Intel Core i7 14th Gen','16GB','1TB SSD','Good',110000.00,'Predator Helios Neo 16, light wear, battery health 89%.',NULL,10,10,'approved','approved',NULL,NULL,NULL,NULL,NULL,'2026-08-18 14:19:42'),(59,2,6,'New','new','Acer Nitro 16','AMD Ryzen 7 8845HS','16GB','1TB SSD','Brand New',84900.00,'Acer Nitro 16 gaming laptop, RTX 4060, AMD Ryzen 7, 16GB RAM. Sealed.',NULL,10,10,'approved','approved',NULL,NULL,NULL,NULL,NULL,'2026-08-18 14:19:42'),(60,3,6,'Old','old','Acer Swift Edge 16','AMD Ryzen 7 7840U','16GB','1TB SSD','Excellent',72000.00,'Acer Swift Edge 16, ultra-light, barely used, comes with original box.',NULL,10,10,'approved','approved',NULL,NULL,NULL,NULL,NULL,'2026-08-18 14:19:42'),(61,2,7,'New','new','MSI Stealth 14 Studio','Intel Core Ultra 7 155H','32GB','1TB SSD','Brand New',169900.00,'MSI Stealth 14 Studio, RTX 4060, Intel Core Ultra 7, 32GB RAM, 1TB SSD. Star Blue.',NULL,10,10,'approved','approved',NULL,NULL,NULL,NULL,NULL,'2026-08-18 14:19:42'),(62,3,7,'Old','old','MSI Stealth 16 Studio','Intel Core Ultra 7 155H','32GB','1TB SSD','Like New',145000.00,'MSI Stealth 16 Studio in excellent condition, 4 months old.',NULL,10,10,'approved','approved',NULL,NULL,NULL,NULL,NULL,'2026-08-18 14:19:42'),(63,2,7,'New','new','MSI Raider GE68 HX','Intel Core i9 14th Gen','32GB','2TB SSD','Brand New',249900.00,'MSI Raider GE68 HX, RTX 4080, Intel i9, 32GB RAM, 2TB SSD. Black.',NULL,10,10,'approved','approved',NULL,NULL,NULL,NULL,NULL,'2026-08-18 14:19:42'),(64,3,7,'Old','old','MSI Katana 15','Intel Core i7 14th Gen','16GB','1TB SSD','Fair',72000.00,'Used Katana 15, visible wear on keyboard, works perfectly.',NULL,10,10,'approved','approved',NULL,NULL,NULL,NULL,NULL,'2026-08-18 14:19:42'),(65,2,7,'New','new','MSI Prestige 16 AI','Intel Core Ultra 7 155H','32GB','1TB SSD','Brand New',129900.00,'MSI Prestige 16 AI, Intel Core Ultra 7, 32GB RAM, 1TB SSD. Urban Silver.',NULL,10,10,'approved','approved',NULL,NULL,NULL,NULL,NULL,'2026-08-18 14:19:42'),(66,3,7,'Old','old','MSI Stealth 18 AI Studio','Intel Core Ultra 9 185H','32GB','2TB SSD','Excellent',190000.00,'MSI Stealth 18 AI Studio, barely used, comes with original box.',NULL,10,10,'approved','approved',NULL,NULL,NULL,NULL,NULL,'2026-08-18 14:19:42'),(67,2,7,'New','new','MSI Raider 18 HX','Intel Core i9 14th Gen','64GB','2TB SSD','Brand New',299900.00,'MSI Raider 18 HX, RTX 4090, Intel i9, 64GB RAM, 2TB SSD. Sealed.',NULL,10,10,'approved','approved',NULL,NULL,NULL,NULL,NULL,'2026-08-18 14:19:42'),(68,3,7,'Old','old','MSI Titan 18 HX','Intel Core i9 14th Gen','64GB','4TB SSD','Good',320000.00,'Titan 18 HX, minor wear, battery health 87%.',NULL,10,10,'approved','approved',NULL,NULL,NULL,NULL,NULL,'2026-08-18 14:19:42'),(69,2,7,'New','new','MSI Cyborg 15','Intel Core i7 13th Gen','16GB','512GB SSD','Brand New',79900.00,'MSI Cyborg 15 gaming laptop, RTX 4050, Intel i7, 16GB RAM, 512GB SSD. Sealed.',NULL,10,10,'approved','approved',NULL,NULL,NULL,NULL,NULL,'2026-08-18 14:19:42'),(70,3,7,'Old','old','MSI Stealth 16 AI Studio','Intel Core Ultra 7 155H','32GB','1TB SSD','Excellent',150000.00,'MSI Stealth 16 AI Studio, barely used, comes with original box and accessories.',NULL,8,8,'approved','approved',NULL,NULL,NULL,NULL,NULL,'2026-08-18 14:19:42'),(71,5,1,'Old','old','MacBook Air 13\" M5','Apple M3 Max 16-Core','16GB','512GB SSD','Old',52000.00,'🌟 Apple MacBook Air 13\" M5\r\n✨ Condition: Verified Pre-Owned (Good Working Condition)\r\n💰 Asking Price: ₹52,000\r\n\r\n⚙️ Technical Specifications:\r\n• Processor: Apple M3 Max 16-Core\r\n• RAM: 16GB\r\n• Storage: 512GB SSD\r\n\r\n📋 Product Highlights & Included Items:\r\n• Thoroughly tested and verified to be in 100% full working order.\r\n• Clean cosmetic condition, screen is clear, and battery holds reliable charge.\r\n• Includes compatible power adapter.\r\n• Safe and verified transaction on Lapify Marketplace with Buyer Protection.','1787054736_691862_fb769441.jpg',10,10,'approved','approved',NULL,1,'2026-08-18 19:13:31',NULL,NULL,'2026-08-18 17:35:36'),(75,2,1,'New','new','Test Pro 979',NULL,NULL,NULL,'Brand New',65000.00,'🌟 Apple Test Pro 979\n✨ Condition: Brand New (100% Unused / Sealed)\n💰 Asking Price: ₹65,000.00\n\n⚙️ Technical Specifications:\n• Processor: Standard Multi-Core CPU\n• RAM: Standard High-Speed RAM\n• Storage: High-Speed SSD\n\n📋 Product Highlights:\n• 100% genuine brand new unit with original packaging and power adapter.\n• Verified listing on Lapify Marketplace with Buyer Protection.',NULL,1,1,'approved','approved',NULL,NULL,NULL,NULL,'2026-08-19 19:21:26','2026-08-19 19:15:02'),(80,4,2,'New','new','Dell Precision 5680','Intel Core i7 12th Gen','32GB','512GB SSD','Brand New',52000.00,'🌟 Dell Precision 5680\r\n✨ Condition: Brand New (100% Unused / Sealed)\r\n💰 Asking Price: ₹52,000\r\n\r\n⚙️ Technical Specifications:\r\n• Processor: Intel Core i7 12th Gen\r\n• RAM: 32GB\r\n• Storage: 512GB SSD\r\n\r\n📋 Product Highlights & Included Items:\r\n• 100% genuine brand new unit in original factory packaging.\r\n• Includes original power adapter, charging cable, and user documentation.\r\n• Safe and verified transaction on Lapify Marketplace with Buyer Protection.',NULL,10,10,'approved','approved',NULL,1,'2026-08-20 00:01:03',NULL,NULL,'2026-08-19 19:48:28'),(81,4,3,'Old','old','HP Pavilion Plus 14','Intel Core i7 12th Gen','8GB','512GB SSD','Old',65000.00,'🌟 HP Pavilion Plus 14\r\n✨ Condition: Verified Pre-Owned (Good Working Condition)\r\n💰 Asking Price: ₹65,000\r\n\r\n⚙️ Technical Specifications:\r\n• Processor: Intel Core i7 12th Gen\r\n• RAM: 8GB\r\n• Storage: 512GB SSD\r\n\r\n📋 Product Highlights & Included Items:\r\n• Thoroughly tested and verified to be in 100% full working order.\r\n• Clean cosmetic condition, screen is clear, and battery holds reliable charge.\r\n• Includes compatible power adapter.\r\n• Safe and verified transaction on Lapify Marketplace with Buyer Protection.','1787152446_131875_41cae13d.jpg',10,10,'approved','approved',NULL,1,'2026-08-20 00:01:01',NULL,NULL,'2026-08-19 20:44:06'),(83,4,1,'Old','old','MacBook Air M3 Test','Apple M4','8GB','512GB SSD','Old',58000.00,'🌟 Apple MacBook Air M3 Test\r\n✨ Condition: Verified Pre-Owned (Good Working Condition)\r\n💰 Asking Price: ₹58,000\r\n\r\n⚙️ Technical Specifications:\r\n• Processor: Apple M4\r\n• RAM: 8GB\r\n• Storage: 512GB SSD\r\n\r\n📋 Product Highlights & Included Items:\r\n• Thoroughly tested and verified to be in 100% full working order.\r\n• Clean cosmetic condition, screen is clear, and battery holds reliable charge.\r\n• Includes compatible power adapter.\r\n• Safe and verified transaction on Lapify Marketplace with Buyer Protection.','1787222432_691425_459fde5a.jpg',0,0,'approved','approved',NULL,1,'2026-08-20 16:11:14',NULL,NULL,'2026-08-20 16:10:32');
/*!40000 ALTER TABLE `laptops` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_items`
--

DROP TABLE IF EXISTS `order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `laptop_id` int(11) NOT NULL,
  `brand_name` varchar(100) NOT NULL,
  `model` varchar(100) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_items`
--

LOCK TABLES `order_items` WRITE;
/*!40000 ALTER TABLE `order_items` DISABLE KEYS */;
INSERT INTO `order_items` VALUES (1,1,70,'MSI','MSI Stealth 16 AI Studio',150000.00,2),(2,2,83,'Apple','MacBook Air M3 Test',58000.00,1);
/*!40000 ALTER TABLE `order_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_number` varchar(30) NOT NULL,
  `user_id` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('placed','confirmed','shipped','delivered','cancelled') NOT NULL DEFAULT 'placed',
  `placed_at` datetime DEFAULT NULL,
  `status_updated_at` datetime DEFAULT NULL,
  `shipping_method` enum('standard','express') NOT NULL DEFAULT 'standard',
  `shipping_address` varchar(255) DEFAULT NULL,
  `promo_code` varchar(50) DEFAULT NULL,
  `discount_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_number` (`order_number`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orders`
--

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
INSERT INTO `orders` VALUES (1,'LPF-2026-F6F5FE',4,295199.00,'shipped','2026-08-19 03:20:53','2026-08-24 12:00:07','express','oscar sky park ayodhya chowk , Rajkot, gujrat 360006','LAPIFY10',5000.00,'2026-08-19 03:20:53'),(2,'LPF-2026-F79657',2,58000.00,'confirmed','2026-08-20 23:30:09','2026-08-24 12:00:07','standard','oscar sky park ayodhya chowk , Rajkot, gujrat 360006','',0.00,'2026-08-20 23:30:09');
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` enum('cod') NOT NULL DEFAULT 'cod',
  `payment_status` enum('pending','collected','failed') NOT NULL DEFAULT 'pending',
  `transaction_id` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payments`
--

LOCK TABLES `payments` WRITE;
/*!40000 ALTER TABLE `payments` DISABLE KEYS */;
INSERT INTO `payments` VALUES (1,1,4,295199.00,'cod','pending','COD-1468788F4B8A','2026-08-19 03:20:53'),(2,2,2,58000.00,'cod','pending','COD-2B3A424A41D9','2026-08-20 23:30:09');
/*!40000 ALTER TABLE `payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `schema_meta`
--

DROP TABLE IF EXISTS `schema_meta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `schema_meta` (
  `meta_key` varchar(50) NOT NULL,
  `meta_value` varchar(255) NOT NULL,
  PRIMARY KEY (`meta_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `schema_meta`
--

LOCK TABLES `schema_meta` WRITE;
/*!40000 ALTER TABLE `schema_meta` DISABLE KEYS */;
INSERT INTO `schema_meta` VALUES ('schema_version','11');
/*!40000 ALTER TABLE `schema_meta` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `role` varchar(25) NOT NULL DEFAULT 'user',
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `remember_token` varchar(255) DEFAULT NULL,
  `remember_expiry` datetime DEFAULT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_expires` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `gender` varchar(20) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (2,'Alex Johnson','alex@example.com','+1 (555) 234-5678','$2y$10$BuUC29UK7qd2Mi/I4MYw3O8y343E3Mi3M8lpYXGXoigsqb2JmoOTG',NULL,'user','active',NULL,NULL,NULL,NULL,'2026-08-18 14:19:42',NULL,NULL),(3,'Sarah Connor','sarah@example.com','+1 (555) 876-5432','$2y$10$BuUC29UK7qd2Mi/I4MYw3O8y343E3Mi3M8lpYXGXoigsqb2JmoOTG',NULL,'user','active',NULL,NULL,NULL,NULL,'2026-08-18 14:19:42',NULL,NULL),(4,'Yash Kotak','kotakyash192@gmail.com',NULL,'$2y$10$rAeV6.Xh8z93gAq11omzrejDOKLXxYr79uILG/nG5R6Yeb4XD.uMi',NULL,'user','active',NULL,NULL,'3d56b0b52dfd7263fd7e00a639aef3785756a4f5cbd802f9a90fcf5088aa8ca1','2026-08-20 17:08:39','2026-08-18 14:22:11',NULL,NULL),(5,'pratik','premmehta7607@gmail.com',NULL,'$2y$10$FY/yEYUcTt1OxIBKypHeKOPYaIkpDcFRkOlwBlnYCiH1sXZykO.fC',NULL,'user','active',NULL,NULL,NULL,NULL,'2026-08-18 15:02:54',NULL,NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wishlist`
--

DROP TABLE IF EXISTS `wishlist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wishlist` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `laptop_id` int(11) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_wish` (`user_id`,`laptop_id`),
  KEY `laptop_id` (`laptop_id`),
  CONSTRAINT `wishlist_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `wishlist_ibfk_2` FOREIGN KEY (`laptop_id`) REFERENCES `laptops` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wishlist`
--

LOCK TABLES `wishlist` WRITE;
/*!40000 ALTER TABLE `wishlist` DISABLE KEYS */;
INSERT INTO `wishlist` VALUES (1,2,3,'2026-08-18 14:19:42'),(2,2,8,'2026-08-18 14:19:42'),(3,3,1,'2026-08-18 14:19:42'),(6,2,83,'2026-08-20 16:12:37');
/*!40000 ALTER TABLE `wishlist` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-08-24 12:07:44
