-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: rms
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
-- Current Database: `rms`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `rms` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci */;

USE `rms`;

--
-- Table structure for table `category`
--

DROP TABLE IF EXISTS `category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `category` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `category_name` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `category`
--

LOCK TABLES `category` WRITE;
/*!40000 ALTER TABLE `category` DISABLE KEYS */;
INSERT INTO `category` VALUES (11,'barbique'),(14,'bakery'),(15,'biryani'),(17,'Traditional'),(18,'Burgers'),(19,'Pizza'),(20,'Beverages'),(21,'Sides'),(22,'Desserts'),(23,'Biryani'),(24,'BBQ'),(25,'gtg'),(27,'gcdrdsderts');
/*!40000 ALTER TABLE `category` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customers`
--

DROP TABLE IF EXISTS `customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `customers` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `email` varchar(100) DEFAULT '',
  `dob` date DEFAULT '2000-01-01',
  `address` varchar(255) DEFAULT '',
  `discount` varchar(20) DEFAULT '0',
  `added_by` varchar(50) DEFAULT 'Admin',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customers`
--

LOCK TABLES `customers` WRITE;
/*!40000 ALTER TABLE `customers` DISABLE KEYS */;
INSERT INTO `customers` VALUES (18,'dgdf','123345','skpattan850911@gmail.com','0002-02-02','1qgjhg','15','Admin','2026-10-02 12:04:41'),(20,'salaman','03323306366','skpattan850911@gmail.com','2026-10-05','chashma road dera ismail khan','012','Admin','2026-10-02 12:26:39'),(23,'jaA','6781686','ajaz@gmail.com','2026-10-12','chashma road dera ismail khan','05','Admin','2026-10-06 12:41:41'),(24,'salaman','56464','swiper@gmail.com','2026-10-15','chashma road dera ismail khan','03','Admin','2026-10-06 12:42:37');
/*!40000 ALTER TABLE `customers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `expenses`
--

DROP TABLE IF EXISTS `expenses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `expenses` (
  `id` int(5) NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `rp` varchar(30) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `catagory` varchar(50) NOT NULL,
  `note` varchar(70) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `expenses`
--

LOCK TABLES `expenses` WRITE;
/*!40000 ALTER TABLE `expenses` DISABLE KEYS */;
INSERT INTO `expenses` VALUES (2,'0014-12-02','sohail',1500.00,'electricity','to be paid','2026-10-02 12:04:41'),(3,'0013-02-03','sohail',1400.00,'gas','painding','2026-10-02 12:04:41'),(4,'0012-02-01','adad',2114.00,'barbi','','2026-10-02 12:04:41'),(5,'2026-10-02','dkdlk',34.00,'Staff Salary','23435rtdf','2026-10-02 12:24:08'),(7,'2026-10-03','dkdlk',3445.00,'Gas','dcee4','2026-10-03 10:30:07');
/*!40000 ALTER TABLE `expenses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `floor`
--

DROP TABLE IF EXISTS `floor`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `floor` (
  `id` int(5) NOT NULL AUTO_INCREMENT,
  `floor` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `floor`
--

LOCK TABLES `floor` WRITE;
/*!40000 ALTER TABLE `floor` DISABLE KEYS */;
INSERT INTO `floor` VALUES (3,'2'),(4,'3rd'),(5,'3rd'),(8,'4th');
/*!40000 ALTER TABLE `floor` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `login`
--

DROP TABLE IF EXISTS `login`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `login` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `email` varchar(120) NOT NULL,
  `pass` varchar(255) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `reset_token` varchar(150) DEFAULT NULL,
  `token_expire` datetime(6) DEFAULT NULL,
  `facebook_id` varchar(150) DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `login`
--

LOCK TABLES `login` WRITE;
/*!40000 ALTER TABLE `login` DISABLE KEYS */;
INSERT INTO `login` VALUES (27,'SOHAIL@gmail.com','123',NULL,'5db64fa85e38dd5adf4651f2198a44bc227c98309e0a32b9e8f99f3d81b9537e','2025-12-05 04:16:36.000000',''),(28,'skpattan850911@gmail.com','123',NULL,NULL,NULL,''),(29,'ajaz@gmail.com','12345',NULL,NULL,NULL,''),(30,'salman@gmail.com','123456','salaman',NULL,NULL,'');
/*!40000 ALTER TABLE `login` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `menue`
--

DROP TABLE IF EXISTS `menue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `menue` (
  `sn` int(10) NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `item_name` varchar(150) NOT NULL,
  `catagory` varchar(100) NOT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `gst` decimal(5,2) NOT NULL DEFAULT 0.00,
  `total` decimal(10,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`sn`)
) ENGINE=InnoDB AUTO_INCREMENT=61 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `menue`
--

LOCK TABLES `menue` WRITE;
/*!40000 ALTER TABLE `menue` DISABLE KEYS */;
INSERT INTO `menue` VALUES (41,'12344','pos_bbq_burger.jpg','biryani','biryani',1890.00,12.00,2117.00),(47,'1234','pos_lava_cake.jpg','cake','bakery',1280.00,13.00,1446.00),(49,'101','/RMS/public/assets/images/food/pos_cheeseburger.jpg','Classic Cheeseburger','Burgers',9.00,5.00,9.00),(50,'102','/RMS/public/assets/images/food/pos_bbq_burger.jpg','Double Bacon BBQ Bur','Burgers',12.00,5.00,12.00),(51,'103','/RMS/public/assets/images/food/pos_pizza.jpg','Margherita Pizza 12\"','Pizza',14.00,5.00,15.00),(52,'104','/RMS/public/assets/images/food/pos_pepperoni.jpg','Pepperoni Passion Pi','Pizza',16.00,5.00,16.00),(53,'105','/RMS/public/assets/images/food/pos_iced_tea.jpg','Fresh Iced Lemon Tea','Beverages',4.00,0.00,4.00),(54,'106','/RMS/public/assets/images/food/pos_cappuccino.jpg','Cappuccino / Latte','Beverages',4.00,0.00,4.00),(55,'107','/RMS/public/assets/images/food/pos_fries.jpg','Crispy French Fries ','Sides',5.00,5.00,5.00),(56,'108','/RMS/public/assets/images/food/pos_buffalo_wings.jpg','Buffalo Chicken Wing','Sides',9.00,5.00,10.00),(57,'109','/RMS/public/assets/images/food/pos_lava_cake.jpg','Chocolate Lava Cake','Desserts',7.00,5.00,7.00);
/*!40000 ALTER TABLE `menue` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_items`
--

DROP TABLE IF EXISTS `order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `qty` int(11) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `product_name` varchar(100) DEFAULT NULL,
  `subtotal` decimal(10,2) DEFAULT 0.00,
  `item_code` varchar(50) DEFAULT NULL,
  `item_name` varchar(150) DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `gst` decimal(5,2) DEFAULT 0.00,
  `total` decimal(10,2) DEFAULT 0.00,
  `notes` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=113 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_items`
--

LOCK TABLES `order_items` WRITE;
/*!40000 ALTER TABLE `order_items` DISABLE KEYS */;
INSERT INTO `order_items` VALUES (1,1,4,1,199.00,NULL,0.00,NULL,NULL,1,0.00,0.00,NULL),(2,3,1,1,149.00,NULL,0.00,NULL,NULL,1,0.00,0.00,NULL),(3,3,3,1,299.00,NULL,0.00,NULL,NULL,1,0.00,0.00,NULL),(4,4,21,3,1676.00,NULL,0.00,NULL,NULL,1,0.00,0.00,NULL),(5,4,33,2,140.00,NULL,0.00,NULL,NULL,1,0.00,0.00,NULL),(7,6,41,1,1890.00,NULL,0.00,NULL,NULL,1,0.00,0.00,NULL),(8,6,47,1,1280.00,NULL,0.00,NULL,NULL,1,0.00,0.00,NULL),(9,6,48,1,1890.00,NULL,0.00,NULL,NULL,1,0.00,0.00,NULL),(10,7,41,1,1890.00,NULL,0.00,NULL,NULL,1,0.00,0.00,NULL),(11,8,41,1,1890.00,NULL,0.00,NULL,NULL,1,0.00,0.00,NULL),(12,9,41,1,1890.00,NULL,0.00,NULL,NULL,1,0.00,0.00,NULL),(13,10,41,1,1890.00,NULL,0.00,NULL,NULL,1,0.00,0.00,NULL),(14,11,41,1,1890.00,NULL,0.00,NULL,NULL,1,0.00,0.00,NULL),(15,12,41,1,1890.00,NULL,0.00,NULL,NULL,1,0.00,0.00,NULL),(16,13,41,1,1890.00,NULL,0.00,NULL,NULL,1,0.00,0.00,NULL),(17,14,41,1,1890.00,NULL,0.00,NULL,NULL,1,0.00,0.00,NULL),(18,15,41,1,1890.00,NULL,0.00,NULL,NULL,1,0.00,0.00,NULL),(19,16,41,1,1890.00,NULL,0.00,NULL,NULL,1,0.00,0.00,NULL),(20,17,41,1,1890.00,NULL,0.00,NULL,NULL,1,0.00,0.00,NULL),(21,18,41,1,1890.00,NULL,0.00,NULL,NULL,1,0.00,0.00,NULL),(22,19,41,1,1890.00,NULL,0.00,NULL,NULL,1,0.00,0.00,NULL),(23,20,41,1,1890.00,NULL,0.00,NULL,NULL,1,0.00,0.00,NULL),(24,21,41,1,1890.00,NULL,0.00,NULL,NULL,1,0.00,0.00,NULL),(25,22,41,1,1890.00,NULL,0.00,NULL,NULL,1,0.00,0.00,NULL),(26,23,41,1,1890.00,NULL,0.00,NULL,NULL,1,0.00,0.00,NULL),(27,24,41,1,1890.00,NULL,0.00,NULL,NULL,1,0.00,0.00,NULL),(28,25,47,1,1280.00,NULL,0.00,NULL,NULL,1,0.00,0.00,NULL),(29,26,41,1,1890.00,NULL,0.00,NULL,NULL,1,0.00,0.00,NULL),(30,27,41,1,1890.00,NULL,0.00,NULL,NULL,1,0.00,0.00,NULL),(31,28,41,1,1890.00,NULL,0.00,NULL,NULL,1,0.00,0.00,NULL),(32,29,41,1,1890.00,NULL,0.00,NULL,NULL,1,0.00,0.00,NULL),(33,30,41,1,1890.00,NULL,0.00,NULL,NULL,1,0.00,0.00,NULL),(34,31,47,1,1280.00,NULL,0.00,NULL,NULL,1,0.00,0.00,NULL),(35,32,41,1,1890.00,NULL,0.00,NULL,NULL,1,0.00,0.00,NULL),(36,33,48,1,1890.00,NULL,0.00,NULL,NULL,1,0.00,0.00,NULL),(37,34,41,1,1890.00,NULL,0.00,NULL,NULL,1,0.00,0.00,NULL),(38,35,41,1,1890.00,NULL,0.00,NULL,NULL,1,0.00,0.00,NULL),(39,35,47,1,1280.00,NULL,0.00,NULL,NULL,1,0.00,0.00,NULL),(40,36,41,1,1890.00,NULL,0.00,NULL,NULL,1,0.00,0.00,NULL),(41,37,47,1,1280.00,NULL,0.00,NULL,NULL,1,0.00,0.00,NULL),(42,38,47,1,1280.00,NULL,0.00,NULL,NULL,1,0.00,0.00,NULL),(43,39,41,1,1890.00,NULL,0.00,NULL,NULL,1,0.00,0.00,NULL),(44,40,41,1,1890.00,NULL,0.00,NULL,NULL,1,0.00,0.00,NULL),(45,41,41,1,1890.00,NULL,0.00,NULL,NULL,1,0.00,0.00,NULL),(46,42,47,1,1280.00,NULL,0.00,NULL,NULL,1,0.00,0.00,NULL),(47,43,48,1,1890.00,NULL,0.00,NULL,NULL,1,0.00,0.00,NULL),(48,44,41,1,1890.00,NULL,0.00,NULL,NULL,1,0.00,0.00,NULL),(49,45,47,1,1280.00,NULL,0.00,NULL,NULL,1,0.00,0.00,NULL),(50,46,41,1,1890.00,NULL,0.00,NULL,NULL,1,0.00,0.00,NULL),(51,47,41,1,1890.00,NULL,0.00,NULL,NULL,1,0.00,0.00,NULL),(52,48,41,1,1890.00,NULL,0.00,NULL,NULL,1,0.00,0.00,NULL),(53,49,47,1,1280.00,NULL,0.00,NULL,NULL,1,0.00,0.00,NULL),(54,50,47,1,1280.00,NULL,0.00,NULL,NULL,1,0.00,0.00,NULL),(55,51,47,1,1280.00,NULL,0.00,NULL,NULL,1,0.00,0.00,NULL),(56,52,48,1,1890.00,NULL,0.00,NULL,NULL,1,0.00,0.00,NULL),(57,53,47,1,1280.00,NULL,0.00,NULL,NULL,1,0.00,0.00,NULL),(58,54,41,1,1890.00,NULL,0.00,NULL,NULL,1,0.00,0.00,NULL),(59,55,41,1,1890.00,NULL,0.00,NULL,NULL,1,0.00,0.00,NULL),(60,56,47,1,1280.00,NULL,0.00,NULL,NULL,1,0.00,0.00,NULL),(61,57,47,1,1280.00,NULL,0.00,NULL,NULL,1,0.00,0.00,NULL),(62,58,47,1,1280.00,NULL,0.00,NULL,NULL,1,0.00,0.00,NULL),(63,59,41,1,1890.00,NULL,0.00,NULL,NULL,1,0.00,0.00,NULL),(64,60,41,1,1890.00,NULL,0.00,NULL,NULL,1,0.00,0.00,NULL),(65,61,41,1,1890.00,NULL,0.00,NULL,NULL,1,0.00,0.00,NULL),(66,62,47,1,1280.00,NULL,0.00,NULL,NULL,1,0.00,0.00,NULL),(67,63,48,1,1890.00,NULL,0.00,NULL,NULL,1,0.00,0.00,NULL),(68,64,47,1,1280.00,NULL,0.00,NULL,NULL,1,0.00,0.00,NULL),(69,65,41,1,1890.00,NULL,0.00,NULL,NULL,1,0.00,0.00,NULL),(70,66,47,1,1280.00,NULL,0.00,NULL,NULL,1,0.00,0.00,NULL),(71,67,48,1,1890.00,NULL,0.00,NULL,NULL,1,0.00,0.00,NULL),(72,68,41,1,1890.00,NULL,0.00,NULL,NULL,1,0.00,0.00,NULL),(73,69,41,1,1890.00,NULL,0.00,NULL,NULL,1,0.00,0.00,NULL),(74,69,47,1,1280.00,NULL,0.00,NULL,NULL,1,0.00,0.00,NULL),(75,69,48,1,1890.00,NULL,0.00,NULL,NULL,1,0.00,0.00,NULL),(76,124,41,1,1890.00,NULL,0.00,NULL,NULL,1,0.00,0.00,NULL),(77,125,41,1,1890.00,NULL,0.00,NULL,NULL,1,0.00,0.00,NULL),(78,126,41,1,1890.00,NULL,0.00,NULL,NULL,1,0.00,0.00,NULL),(79,127,41,1,1890.00,NULL,0.00,NULL,NULL,1,0.00,0.00,NULL),(80,127,47,1,1280.00,NULL,0.00,NULL,NULL,1,0.00,0.00,NULL),(81,127,48,1,1890.00,NULL,0.00,NULL,NULL,1,0.00,0.00,NULL),(82,128,41,1,1890.00,NULL,0.00,NULL,NULL,1,0.00,0.00,NULL),(83,128,47,1,1280.00,NULL,0.00,NULL,NULL,1,0.00,0.00,NULL),(84,128,48,1,1890.00,NULL,0.00,NULL,NULL,1,0.00,0.00,NULL),(85,129,47,1,1280.00,NULL,0.00,NULL,NULL,1,0.00,0.00,NULL),(86,129,48,1,1890.00,NULL,0.00,NULL,NULL,1,0.00,0.00,NULL),(87,130,41,1,1890.00,NULL,0.00,NULL,NULL,1,0.00,0.00,NULL),(88,130,47,1,1280.00,NULL,0.00,NULL,NULL,1,0.00,0.00,NULL),(89,130,48,1,1890.00,NULL,0.00,NULL,NULL,1,0.00,0.00,NULL),(90,131,48,1,1890.00,NULL,0.00,NULL,NULL,1,0.00,0.00,NULL),(91,132,NULL,NULL,1890.00,NULL,0.00,'123','tikka',1,12.00,1890.00,''),(92,133,NULL,NULL,5.00,NULL,0.00,'110','Vanilla Bean Sundae',1,5.00,5.00,''),(93,134,NULL,NULL,9.00,NULL,0.00,'101','Classic Cheeseburger',1,5.00,9.45,''),(94,134,NULL,NULL,14.00,NULL,0.00,'103','Margherita Pizza 12\"',1,5.00,14.70,''),(95,135,NULL,NULL,1890.00,NULL,0.00,'12344','biryani',1,12.00,1890.00,'4'),(96,135,NULL,NULL,9.00,NULL,0.00,'108','Buffalo Chicken Wing',1,5.00,9.00,''),(97,136,NULL,NULL,12.00,NULL,0.00,'102','Double Bacon BBQ Bur',1,5.00,12.00,''),(98,137,NULL,NULL,1890.00,NULL,0.00,'12344','biryani',1,12.00,1890.00,''),(99,138,NULL,NULL,7.00,NULL,0.00,'109','Chocolate Lava Cake',1,5.00,7.00,''),(100,138,NULL,NULL,1890.00,NULL,0.00,'12344','biryani',1,12.00,1890.00,''),(101,139,NULL,NULL,5.00,NULL,0.00,'107','Crispy French Fries ',1,5.00,5.00,''),(102,139,NULL,NULL,9.00,NULL,0.00,'108','Buffalo Chicken Wing',1,5.00,9.00,''),(103,140,NULL,NULL,4.00,NULL,0.00,'106','Cappuccino / Latte',1,0.00,4.00,''),(104,141,NULL,NULL,9.00,NULL,0.00,'108','Buffalo Chicken Wing',1,5.00,9.00,''),(105,141,NULL,NULL,12.00,NULL,0.00,'102','Double Bacon BBQ Bur',1,5.00,12.00,''),(106,142,NULL,NULL,4.00,NULL,0.00,'106','Cappuccino / Latte',1,0.00,4.00,''),(107,143,NULL,NULL,4.00,NULL,0.00,'105','Fresh Iced Lemon Tea',2,0.00,8.00,''),(108,144,NULL,NULL,4.00,NULL,0.00,'106','Cappuccino / Latte',2,0.00,8.00,''),(109,145,NULL,NULL,9.00,NULL,0.00,'108','Buffalo Chicken Wing',1,5.00,9.00,''),(110,146,NULL,NULL,9.00,NULL,0.00,'108','Buffalo Chicken Wing',1,5.00,9.00,''),(111,147,NULL,NULL,12.00,NULL,0.00,'102','Double Bacon BBQ Bur',1,5.00,12.00,''),(112,148,NULL,NULL,5.00,NULL,0.00,'107','Crispy French Fries ',1,5.00,5.00,'');
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
  `total` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(20) NOT NULL,
  `table_id` int(11) NOT NULL,
  `waiter_id` int(11) NOT NULL,
  `order_number` varchar(50) DEFAULT NULL,
  `customer_name` varchar(100) DEFAULT 'Walk-in Customer',
  `order_type` varchar(50) DEFAULT 'Dine-In',
  `floor_name` varchar(50) DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT 'Cash',
  `subtotal` decimal(10,2) DEFAULT 0.00,
  `tax` decimal(10,2) DEFAULT 0.00,
  `discount` decimal(10,2) DEFAULT 0.00,
  `grand_total` decimal(10,2) DEFAULT 0.00,
  `table_no` varchar(30) DEFAULT NULL,
  `customer_phone` varchar(30) DEFAULT NULL,
  `paid_amount` decimal(10,2) DEFAULT 0.00,
  `change_amount` decimal(10,2) DEFAULT 0.00,
  `order_status` varchar(50) DEFAULT 'Completed',
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=149 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orders`
--

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
INSERT INTO `orders` VALUES (1,199.00,'2025-12-10 13:50:58','CANCELLED',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(2,230.00,'2025-12-10 13:51:02','CANCELLED',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(3,448.00,'2025-12-10 13:54:30','CANCELLED',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(4,5308.00,'2025-12-13 11:08:03','CANCELLED',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(6,5060.00,'2025-12-16 05:48:17','CANCELLED',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(7,1890.00,'2025-12-16 16:26:26','CANCELLED',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(8,1890.00,'2025-12-16 16:26:53','CANCELLED',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(9,1890.00,'2025-12-16 16:37:19','CANCELLED',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(10,1890.00,'2025-12-16 16:44:02','CANCELLED',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(11,1890.00,'2025-12-16 16:44:10','CANCELLED',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(12,1890.00,'2025-12-16 16:49:37','CANCELLED',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(13,1890.00,'2025-12-16 16:49:41','CANCELLED',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(14,1890.00,'2025-12-16 16:50:47','CANCELLED',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(15,1890.00,'2025-12-16 16:50:48','CANCELLED',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(16,1890.00,'2025-12-16 16:50:49','CANCELLED',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(17,1890.00,'2025-12-16 16:50:49','CANCELLED',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(18,1890.00,'2025-12-16 16:50:49','CANCELLED',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(19,1890.00,'2025-12-16 16:50:50','CANCELLED',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(20,1890.00,'2025-12-16 16:50:50','CANCELLED',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(21,1890.00,'2025-12-16 16:51:19','CANCELLED',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(22,1890.00,'2025-12-16 16:56:08','CANCELLED',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(23,1890.00,'2025-12-16 16:56:59','CANCELLED',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(24,1890.00,'2025-12-16 17:06:00','CANCELLED',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(25,1280.00,'2025-12-16 17:11:45','CANCELLED',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(26,1890.00,'2025-12-16 17:14:20','CANCELLED',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(27,1890.00,'2025-12-16 17:18:30','CANCELLED',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(28,1890.00,'2025-12-16 17:19:08','CANCELLED',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(29,1890.00,'2025-12-16 17:22:11','CANCELLED',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(30,1890.00,'2025-12-17 04:04:43','CANCELLED',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(31,100.00,'2025-12-17 04:05:06','CANCELLED',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(32,1890.00,'2025-12-17 04:08:16','CANCELLED',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(33,1890.00,'2025-12-17 04:08:38','CANCELLED',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(34,1890.00,'2025-12-17 04:09:21','CANCELLED',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(35,3170.00,'2025-12-17 04:12:52','CANCELLED',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(36,1890.00,'2025-12-17 04:12:59','CANCELLED',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(37,1280.00,'2025-12-17 04:13:07','CANCELLED',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(38,1280.00,'2025-12-17 04:13:18','CANCELLED',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(39,1890.00,'2025-12-17 04:13:40','CANCELLED',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(40,1890.00,'2025-12-17 04:19:38','CANCELLED',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(41,1890.00,'2025-12-17 04:20:29','CANCELLED',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(42,1280.00,'2025-12-17 04:24:35','CANCELLED',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(43,1890.00,'2025-12-17 04:25:41','CANCELLED',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(44,1890.00,'2025-12-17 04:29:41','CANCELLED',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(45,1280.00,'2025-12-17 04:31:40','CANCELLED',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(46,1890.00,'2025-12-17 04:33:02','CANCELLED',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(47,1890.00,'2025-12-17 04:46:32','CANCELLED',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(48,1890.00,'2025-12-17 04:46:39','CANCELLED',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(49,1280.00,'2025-12-17 04:46:58','CANCELLED',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(50,1280.00,'2025-12-17 04:53:34','CANCELLED',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(51,1280.00,'2025-12-17 04:54:02','CANCELLED',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(52,1890.00,'2025-12-17 04:56:41','CANCELLED',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(53,1280.00,'2025-12-17 05:00:42','CANCELLED',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(54,1890.00,'2025-12-17 05:02:26','CANCELLED',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(55,1890.00,'2025-12-17 05:08:53','CANCELLED',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(56,1280.00,'2025-12-17 05:09:02','CANCELLED',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(57,1280.00,'2025-12-17 05:12:29','CANCELLED',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(58,1280.00,'2025-12-17 05:12:58','CANCELLED',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(59,1890.00,'2025-12-17 05:16:09','CANCELLED',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(60,1890.00,'2025-12-17 05:19:18','CANCELLED',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(61,1890.00,'2025-12-17 05:20:40','CANCELLED',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(62,1280.00,'2025-12-17 05:20:44','CANCELLED',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(63,1890.00,'2025-12-17 05:20:50','CANCELLED',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(64,1280.00,'2025-12-17 05:22:20','CANCELLED',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(65,1890.00,'2025-12-17 05:25:58','CANCELLED',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(66,1280.00,'2025-12-17 05:26:03','CANCELLED',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(67,1890.00,'2025-12-17 05:26:08','CANCELLED',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(68,1890.00,'2025-12-17 09:25:16','CANCELLED',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(69,5060.00,'2025-12-17 09:26:19','CANCELLED',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(70,1890.00,'2025-12-17 10:05:27','CANCELLED',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(71,1890.00,'2025-12-17 10:05:28','CANCELLED',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(72,1890.00,'2025-12-17 10:05:28','CANCELLED',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(73,1890.00,'2025-12-17 10:05:41','CANCELLED',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(74,1890.00,'2025-12-19 11:04:56','CANCELLED',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(75,1890.00,'2025-12-19 11:04:58','CANCELLED',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(76,1890.00,'2025-12-19 11:04:59','CANCELLED',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(77,1890.00,'2025-12-19 11:04:59','CANCELLED',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(78,1890.00,'2025-12-19 11:04:59','CANCELLED',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(79,1890.00,'2025-12-19 11:05:00','CANCELLED',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(80,1890.00,'2025-12-19 11:05:00','CANCELLED',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(81,1890.00,'2025-12-19 11:05:00','CANCELLED',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(82,1890.00,'2025-12-19 11:05:01','CANCELLED',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(83,1890.00,'2025-12-19 11:05:01','CANCELLED',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(84,1890.00,'2025-12-19 11:05:01','CANCELLED',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(85,1890.00,'2025-12-19 11:05:02','CANCELLED',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(86,1890.00,'2025-12-19 11:05:02','CANCELLED',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(87,1890.00,'2025-12-19 11:05:03','CANCELLED',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(88,1280.00,'2025-12-19 11:10:36','CANCELLED',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(89,2560.00,'2025-12-19 11:10:46','CANCELLED',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(90,3840.00,'2025-12-19 11:10:53','CANCELLED',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(91,5060.00,'2025-12-20 06:34:39','CANCELLED',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(92,1890.00,'2025-12-21 07:26:20','CANCELLED',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(93,1890.00,'2025-12-21 07:26:21','CANCELLED',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(94,1890.00,'2025-12-21 07:26:22','',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(95,1890.00,'2025-12-21 07:26:22','',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(96,3170.00,'2025-12-21 07:45:30','',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(97,1890.00,'2025-12-22 06:39:12','',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(98,1890.00,'2025-12-22 06:46:01','',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(99,1890.00,'2025-12-22 07:29:13','',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(100,1890.00,'2025-12-22 11:36:29','',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(101,1280.00,'2025-12-22 11:43:06','',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(102,1890.00,'2025-12-22 11:46:59','',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(103,1890.00,'2025-12-22 11:54:42','',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(104,1890.00,'2025-12-22 12:10:48','',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(105,1890.00,'2025-12-22 12:10:55','',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(106,1890.00,'2025-12-22 12:10:55','',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(107,1890.00,'2025-12-22 12:10:55','',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(108,1890.00,'2025-12-22 12:10:56','',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(109,1890.00,'2025-12-22 12:10:56','',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(110,1890.00,'2025-12-22 12:10:56','',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(111,1890.00,'2025-12-22 12:10:56','',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(112,1890.00,'2025-12-22 12:10:56','',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(113,1890.00,'2025-12-22 12:10:57','',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(114,1890.00,'2025-12-22 12:16:11','',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(115,1890.00,'2025-12-22 12:16:24','',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(116,1890.00,'2025-12-22 12:16:25','',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(117,1890.00,'2025-12-22 12:16:25','',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(118,1890.00,'2025-12-22 12:16:26','',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(119,1890.00,'2025-12-22 12:28:53','',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(120,1890.00,'2025-12-22 12:28:55','',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(121,1890.00,'2025-12-22 12:28:55','',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(122,1890.00,'2025-12-22 12:28:56','',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(123,1890.00,'2025-12-22 12:28:56','',0,0,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(124,1890.00,'2025-12-22 13:07:45','RUNNING',4,1,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(125,1890.00,'2025-12-23 10:57:33','RUNNING',4,3,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(126,1890.00,'2025-12-23 11:41:01','RUNNING',3,3,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(127,5060.00,'2025-12-24 07:57:25','CANCELLED',4,6,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(128,5060.00,'2025-12-27 04:38:41','RUNNING',4,3,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(129,3170.00,'2025-12-27 04:39:17','RUNNING',4,3,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(130,5060.00,'2026-02-05 11:19:52','RUNNING',3,6,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(131,1890.00,'2026-02-27 11:33:58','RUNNING',3,3,NULL,'Walk-in Customer','Dine-In',NULL,'Cash',0.00,0.00,0.00,0.00,NULL,NULL,0.00,0.00,'Completed',NULL),(132,NULL,'2026-10-02 12:22:06','',0,0,'ORD-20261002-1CCC','Walk-in Customer','Dine-In',NULL,'Card',1890.00,226.80,189.00,1927.80,'T-01','',1927.80,0.00,'Completed',''),(133,NULL,'2026-10-02 12:28:18','',0,0,'ORD-20261002-6FB4','Walk-in Customer','Dine-In',NULL,'Online',5.00,0.25,0.00,5.25,'T-01','',5.25,0.00,'Completed',''),(134,NULL,'2026-10-03 09:42:14','',0,0,'ORD-20261003-D009','Test VIP Diner','Dine-In','Ground Floor','Cash',25.00,1.25,0.00,26.25,'Table 01','034702320579',30.00,3.75,'Completed',''),(135,NULL,'2026-10-03 09:45:03','',0,0,'ORD-20261003-F84B','Walk-in Customer','Dine-In','Ground Floor','Cash',1899.00,227.25,0.00,2126.25,'Table 02','',2126.25,0.00,'Completed',''),(136,NULL,'2026-10-03 09:50:31','',0,0,'ORD-20261003-38A8','Walk-in Customer','Dine-In','Ground Floor','Cash',12.00,0.60,0.00,12.60,'Table 01','',12.60,0.00,'Completed',''),(137,NULL,'2026-10-03 09:51:18','',0,0,'ORD-20261003-5723','Walk-in Customer','Dine-In','Ground Floor','Cash',1890.00,226.80,0.00,2116.80,'Table 01','',2116.80,0.00,'Completed',''),(138,NULL,'2026-10-03 10:15:31','',0,0,'ORD-20261003-BCB0','Walk-in Customer','Dine-In','Ground Floor','Cash',1897.00,227.15,0.00,2124.15,'Table 01','',2124.15,0.00,'Completed',''),(139,NULL,'2026-10-03 10:48:28','',0,0,'ORD-20261003-5888','Walk-in Customer','Dine-In','Ground Floor','Cash',14.00,0.70,0.00,14.70,'Table 01','',14.70,0.00,'Completed',''),(140,NULL,'2026-10-04 04:36:32','',0,0,'ORD-20261004-471B','Walk-in Customer','Dine-In','Ground Floor','Cash',4.00,0.00,0.00,4.00,'Table 01','',4.00,0.00,'Completed',''),(141,NULL,'2026-10-04 04:46:36','',0,0,'ORD-20261004-8DB2','Walk-in Customer','Dine-In','Ground Floor','Cash',21.00,1.05,0.00,22.05,'Table 01','',22.05,0.00,'Completed',''),(142,NULL,'2026-10-04 05:06:22','',0,0,'ORD-20261004-DFDC','Walk-in Customer','Dine-In','Ground Floor','Cash',4.00,0.00,0.00,4.00,'Table 01','',4.00,0.00,'Completed',''),(143,NULL,'2026-10-04 05:27:14','',0,0,'ORD-20261004-13E5','Walk-in Customer','Dine-In','Ground Floor','Cash',8.00,0.00,0.00,8.00,'Table 01','',8.00,0.00,'Completed',''),(144,NULL,'2026-10-06 09:32:43','',0,0,'ORD-20261006-61D3','Walk-in Customer','Dine-In','Ground Floor','Cash',8.00,0.00,0.00,8.00,'Table 01','',8.00,0.00,'Completed',''),(145,NULL,'2026-10-06 10:16:08','',0,0,'ORD-20261006-110A','Walk-in Customer','Dine-In','Ground Floor','Cash',9.00,0.45,0.00,9.45,'Table 01','',9.45,0.00,'Completed',''),(146,NULL,'2026-10-06 10:32:12','',0,0,'ORD-20261006-8351','Walk-in Customer','Dine-In','Ground Floor','Cash',9.00,0.45,0.00,9.45,'Table 01','',9.45,0.00,'Completed',''),(147,NULL,'2026-10-06 10:56:16','',0,0,'ORD-20261006-701C','Walk-in Customer','Dine-In','Ground Floor','Cash',12.00,0.60,0.00,12.60,'Table 01','',12.60,0.00,'Completed',''),(148,NULL,'2026-10-06 12:40:25','',0,0,'ORD-20261006-8A38','Walk-in Customer','Dine-In','Ground Floor','Cash',5.00,0.25,0.00,5.25,'Table 01','',5.25,0.00,'Completed','');
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES (1,'Arancini',149.00,'arancini.jpg'),(2,'French Fries',99.00,'fries.jpg'),(3,'Pizza Margherita',299.00,'pizza.jpg'),(4,'Burger',199.00,'burger.jpg');
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `restaurant_floors`
--

DROP TABLE IF EXISTS `restaurant_floors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `restaurant_floors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `floor_name` varchar(100) NOT NULL,
  `floor_code` varchar(50) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'Active',
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `floor_name` (`floor_name`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `restaurant_floors`
--

LOCK TABLES `restaurant_floors` WRITE;
/*!40000 ALTER TABLE `restaurant_floors` DISABLE KEYS */;
INSERT INTO `restaurant_floors` VALUES (1,'Ground Floor','GF','Main dining hall with open kitchen views','Active','2026-10-03 14:28:51'),(2,'First Floor (Family)','1F','Family section and quiet dining cabins','Active','2026-10-03 14:28:51'),(3,'Rooftop Terrace','RT','Open-air panoramic rooftop dining','Active','2026-10-03 14:28:51'),(4,'Outdoor Lawn','OD','Lush green garden seating with umbrellas','Active','2026-10-03 14:28:51'),(5,'5th','sp','for special people that','Active','2026-10-03 15:27:22');
/*!40000 ALTER TABLE `restaurant_floors` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `restaurant_tables`
--

DROP TABLE IF EXISTS `restaurant_tables`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `restaurant_tables` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `table_number` varchar(50) NOT NULL,
  `floor_id` int(11) NOT NULL,
  `floor_name` varchar(100) NOT NULL,
  `capacity` int(11) NOT NULL DEFAULT 4,
  `status` varchar(30) DEFAULT 'Available',
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `table_number` (`table_number`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `restaurant_tables`
--

LOCK TABLES `restaurant_tables` WRITE;
/*!40000 ALTER TABLE `restaurant_tables` DISABLE KEYS */;
INSERT INTO `restaurant_tables` VALUES (1,'Table 01',1,'Ground Floor',2,'Occupied','2026-10-03 14:28:51'),(2,'Table 02',1,'Ground Floor',4,'Occupied','2026-10-03 14:28:51'),(3,'Table 03',1,'Ground Floor',4,'Occupied','2026-10-03 14:28:51'),(4,'Table 04',1,'Ground Floor',6,'Available','2026-10-03 14:28:51'),(5,'Table 05',1,'Ground Floor',8,'Available','2026-10-03 14:28:51'),(6,'Family Cabin 1',2,'First Floor (Family)',6,'Available','2026-10-03 14:28:51'),(7,'Family Cabin 2',2,'First Floor (Family)',8,'Reserved','2026-10-03 14:28:51'),(8,'Table 11',2,'First Floor (Family)',4,'Available','2026-10-03 14:28:51'),(9,'Table 12',2,'First Floor (Family)',4,'Available','2026-10-03 14:28:51'),(10,'Roof Table 01',3,'Rooftop Terrace',2,'Available','2026-10-03 14:28:51'),(11,'Roof Table 02',3,'Rooftop Terrace',4,'Available','2026-10-03 14:28:51'),(12,'Roof VIP 01',3,'Rooftop Terrace',6,'Available','2026-10-03 14:28:51'),(13,'Lawn 01',4,'Outdoor Lawn',4,'Available','2026-10-03 14:28:51'),(14,'Lawn 02',4,'Outdoor Lawn',4,'Available','2026-10-03 14:28:51'),(15,'32tyte',5,'5th',4,'Reserved','2026-10-03 15:27:45');
/*!40000 ALTER TABLE `restaurant_tables` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `supliers`
--

DROP TABLE IF EXISTS `supliers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `supliers` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `name` varchar(30) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `item` varchar(150) DEFAULT NULL,
  `date` date NOT NULL,
  `dues` decimal(10,2) DEFAULT 0.00,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `supliers`
--

LOCK TABLES `supliers` WRITE;
/*!40000 ALTER TABLE `supliers` DISABLE KEYS */;
INSERT INTO `supliers` VALUES (11,'Global Food Corp 770','+1 555-7766','Frozen French Fries & Cheese','2026-10-02',250.00);
/*!40000 ALTER TABLE `supliers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `suppliers`
--

DROP TABLE IF EXISTS `suppliers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `suppliers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `supplier_name` varchar(100) NOT NULL,
  `phone` varchar(30) NOT NULL,
  `item_of_supply` varchar(150) NOT NULL,
  `date` date NOT NULL,
  `dues` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `suppliers`
--

LOCK TABLES `suppliers` WRITE;
/*!40000 ALTER TABLE `suppliers` DISABLE KEYS */;
/*!40000 ALTER TABLE `suppliers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tables`
--

DROP TABLE IF EXISTS `tables`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tables` (
  `table_id` int(5) NOT NULL AUTO_INCREMENT,
  `floor` varchar(2) NOT NULL,
  `table_no` int(8) NOT NULL,
  `seats` int(3) NOT NULL,
  `discription` varchar(150) NOT NULL,
  `added-by` int(11) NOT NULL,
  PRIMARY KEY (`table_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tables`
--

LOCK TABLES `tables` WRITE;
/*!40000 ALTER TABLE `tables` DISABLE KEYS */;
INSERT INTO `tables` VALUES (3,'5',5,8,'',0),(4,'8',5,5,'',0);
/*!40000 ALTER TABLE `tables` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `waiter`
--

DROP TABLE IF EXISTS `waiter`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `waiter` (
  `waiter_id` int(5) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `phone` int(11) NOT NULL,
  `email` varchar(50) NOT NULL,
  PRIMARY KEY (`waiter_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `waiter`
--

LOCK TABLES `waiter` WRITE;
/*!40000 ALTER TABLE `waiter` DISABLE KEYS */;
INSERT INTO `waiter` VALUES (1,'dgdf',1122,'skpattan850911@gmail.com'),(3,'Sohail  Khan',1122,'SOHAIL@gmail.com'),(6,'sultan',123,'Sk@gmail.com');
/*!40000 ALTER TABLE `waiter` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-10-06 18:09:50
