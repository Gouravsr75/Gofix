-- MySQL dump 10.13  Distrib 8.4.3, for Win64 (x86_64)
--
-- Host: localhost    Database: fixlify_db
-- ------------------------------------------------------
-- Server version	8.4.3

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
-- Table structure for table `admins`
--

DROP TABLE IF EXISTS `admins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `admins` (
  `id` int NOT NULL AUTO_INCREMENT,
  `admin_name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `status` enum('active','blocked') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admins`
--

LOCK TABLES `admins` WRITE;
/*!40000 ALTER TABLE `admins` DISABLE KEYS */;
INSERT INTO `admins` VALUES (1,'Gourav Admin','admin@gmail.com','$2y$10$DWgfYqIL5ndT/NgEoYIpReNCGbcsSGhq2ZB9NpGqq1j9u57XetjdK','active','2026-09-22 15:02:30');
/*!40000 ALTER TABLE `admins` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `booking_status_history`
--

DROP TABLE IF EXISTS `booking_status_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `booking_status_history` (
  `id` int NOT NULL AUTO_INCREMENT,
  `booking_id` int NOT NULL,
  `old_status` varchar(50) DEFAULT NULL,
  `new_status` varchar(50) NOT NULL,
  `changed_by` enum('customer','provider','admin','system') DEFAULT 'system',
  `changed_by_id` int DEFAULT NULL,
  `note` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=65 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `booking_status_history`
--

LOCK TABLES `booking_status_history` WRITE;
/*!40000 ALTER TABLE `booking_status_history` DISABLE KEYS */;
INSERT INTO `booking_status_history` VALUES (1,1,NULL,'pending','customer',1,'Booking created by customer','2026-09-20 17:48:57'),(2,2,NULL,'pending','customer',1,'Booking created by customer','2026-09-21 15:33:45'),(3,2,'pending','accepted','provider',4,'Booking accepted by service provider.','2026-09-21 15:43:51'),(4,2,'accepted','on_the_way','provider',4,'Provider is on the way to customer.','2026-09-21 15:43:56'),(5,2,'on_the_way','work_started','provider',4,'Provider has started the service.','2026-09-21 15:44:18'),(6,2,'work_started','completed','provider',4,'Service completed successfully.','2026-09-21 15:44:30'),(7,5,NULL,'pending','customer',1,'Booking created by customer','2026-09-21 15:49:01'),(8,5,'pending','rescheduled','provider',4,'Provider requested reschedule to 2026-09-22 at 20:00.','2026-09-21 15:49:47'),(9,5,'rescheduled','accepted','provider',4,'Rescheduled booking accepted. New schedule: 2026-09-22 at 20:00','2026-09-21 15:51:37'),(10,5,'accepted','on_the_way','provider',4,'Provider is on the way to customer.','2026-09-21 15:51:49'),(11,5,'on_the_way','work_started','provider',4,'Provider has started the service.','2026-09-21 15:52:11'),(12,6,NULL,'pending','customer',1,'Booking created by customer','2026-09-21 15:59:26'),(13,7,NULL,'pending','customer',1,'Booking created by customer','2026-09-21 15:59:50'),(14,6,'pending','cancelled','customer',1,'Booking cancelled by customer.','2026-09-21 16:28:37'),(15,7,'pending','cancelled','customer',1,'Booking cancelled by customer.','2026-09-21 16:29:44'),(16,4,'pending','cancelled','customer',1,'Booking cancelled by customer.','2026-09-21 16:30:14'),(17,8,NULL,'pending','customer',1,'Booking created by customer','2026-09-22 17:24:25'),(18,2,'completed','provider_notified','admin',1,'Provider assigned by admin.','2026-09-26 15:20:10'),(19,2,'provider_notified','cancelled','admin',1,'Booking cancelled by admin.','2026-09-26 15:20:17'),(20,5,'work_started','cancelled','admin',1,'Booking cancelled by admin.','2026-09-26 15:20:24'),(21,8,'pending','completed','admin',1,'Status changed by admin.','2026-09-26 15:20:36'),(22,9,NULL,'pending','customer',2,'Booking created by customer','2026-09-26 17:20:00'),(23,10,NULL,'pending','customer',3,'Booking created by customer','2026-09-26 17:46:19'),(24,11,NULL,'pending','customer',2,'Booking created by customer','2026-09-27 15:08:24'),(25,11,'pending','provider_notified','admin',1,'Provider assigned by admin.','2026-09-27 15:18:32'),(26,11,'provider_notified','provider_notified','admin',1,'Provider assigned by admin.','2026-09-27 15:18:48'),(27,11,'provider_notified','provider_notified','admin',1,'Provider assigned by admin.','2026-09-27 15:34:30'),(28,11,'provider_notified','rejected','provider',6,'Provider rejected this booking request.','2026-09-27 15:37:19'),(29,11,'provider_notified','rejected','provider',6,'Provider rejected this booking request.','2026-09-27 15:37:23'),(30,11,'provider_notified','provider_notified','admin',1,'Provider assigned by admin.','2026-09-27 15:38:11'),(31,11,'provider_notified','accepted','provider',6,'Booking accepted by service provider.','2026-09-27 15:38:22'),(32,11,'accepted','on_the_way','provider',6,'Provider is on the way to customer.','2026-09-27 15:38:30'),(33,11,'on_the_way','work_started','provider',6,'Provider has started the service.','2026-09-27 15:38:32'),(34,11,'work_started','completed','provider',6,'Service completed successfully.','2026-09-27 15:38:35'),(35,11,'completed','provider_notified','admin',1,'Provider assigned by admin.','2026-09-27 15:38:49'),(36,11,'provider_notified','accepted','provider',6,'Booking accepted by service provider.','2026-09-27 15:40:20'),(37,11,'accepted','on_the_way','provider',6,'Provider is on the way to customer.','2026-09-27 15:40:23'),(38,11,'on_the_way','work_started','provider',6,'Provider has started the service.','2026-09-27 15:40:26'),(39,11,'work_started','completed','provider',6,'Service completed successfully.','2026-09-27 15:40:28'),(40,10,'pending','provider_notified','admin',1,'Status changed by admin.','2026-09-27 15:44:06'),(41,10,'provider_notified','provider_notified','admin',1,'Provider assigned by admin.','2026-09-27 15:44:41'),(42,9,'pending','provider_notified','admin',1,'Provider assigned by admin.','2026-09-27 15:45:13'),(43,9,'provider_notified','accepted','provider',6,'Booking accepted by service provider.','2026-09-27 15:45:28'),(44,10,'provider_notified','accepted','provider',6,'Booking accepted by service provider.','2026-09-27 15:45:33'),(45,10,'accepted','on_the_way','provider',6,'Provider is on the way to customer.','2026-09-27 15:45:37'),(46,10,'on_the_way','work_started','provider',6,'Provider has started the service.','2026-09-27 15:45:39'),(47,9,'accepted','on_the_way','provider',6,'Provider is on the way to customer.','2026-09-27 15:45:41'),(48,9,'on_the_way','work_started','provider',6,'Provider has started the service.','2026-09-27 15:45:44'),(49,10,'work_started','completed','provider',6,'Service completed successfully.','2026-09-27 15:45:53'),(50,9,'work_started','completed','provider',6,'Service completed successfully.','2026-09-27 15:45:56'),(51,9,'completed','provider_notified','admin',1,'Provider assigned by admin.','2026-09-27 15:47:01'),(52,9,'provider_notified','accepted','provider',6,'Booking accepted by service provider.','2026-09-27 15:47:35'),(53,9,'accepted','on_the_way','provider',6,'Provider is on the way to customer.','2026-09-27 15:47:39'),(54,9,'on_the_way','work_started','provider',6,'Provider has started the service.','2026-09-27 15:47:40'),(55,9,'work_started','completed','provider',6,'Service completed successfully.','2026-09-27 15:47:43'),(56,12,NULL,'pending','customer',2,'Booking created by customer','2026-09-27 16:27:30'),(57,13,NULL,'pending','customer',4,'Booking created by customer','2026-09-27 16:30:03'),(58,13,'pending','accepted','admin',1,'Status changed by admin.','2026-09-27 16:32:56'),(59,13,'accepted','provider_notified','admin',1,'Status changed by admin.','2026-09-27 16:33:17'),(60,13,'provider_notified','provider_notified','admin',1,'Provider assigned by admin.','2026-09-27 16:33:24'),(61,13,'provider_notified','accepted','provider',9,'Booking accepted by service provider.','2026-09-27 16:33:30'),(62,13,'accepted','on_the_way','provider',9,'Provider is on the way to customer.','2026-09-27 16:33:33'),(63,13,'on_the_way','work_started','provider',9,'Provider has started the service.','2026-09-27 16:33:36'),(64,13,'work_started','completed','provider',9,'Service completed successfully.','2026-09-27 16:33:38');
/*!40000 ALTER TABLE `booking_status_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bookings`
--

DROP TABLE IF EXISTS `bookings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bookings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `booking_id` varchar(30) NOT NULL,
  `user_id` int NOT NULL,
  `provider_id` int DEFAULT NULL,
  `service_id` int NOT NULL,
  `customer_name` varchar(150) NOT NULL,
  `mobile` varchar(15) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `alternate_mobile` varchar(15) DEFAULT NULL,
  `address` text NOT NULL,
  `village` varchar(100) DEFAULT NULL,
  `area` varchar(100) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `district` varchar(100) DEFAULT NULL,
  `pincode` varchar(10) DEFAULT NULL,
  `landmark` varchar(255) DEFAULT NULL,
  `booking_date` date NOT NULL,
  `booking_time` varchar(30) NOT NULL,
  `problem_description` text,
  `special_instruction` text,
  `price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `payment_mode` enum('COD') DEFAULT 'COD',
  `payment_status` enum('pending','paid','failed','refunded') DEFAULT 'pending',
  `booking_status` enum('pending','provider_notified','accepted','rejected','rescheduled','on_the_way','work_started','completed','cancelled') DEFAULT 'pending',
  `provider_note` text,
  `rescheduled_date` date DEFAULT NULL,
  `rescheduled_time` varchar(30) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `booking_id` (`booking_id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bookings`
--

LOCK TABLES `bookings` WRITE;
/*!40000 ALTER TABLE `bookings` DISABLE KEYS */;
INSERT INTO `bookings` VALUES (2,'BOOK-20260921-9826AB',1,4,6,'Gourav kumar swarnkar','9546328811','GOURAV.WIX@GMAIL.COM','8210113048','udhwa somwari haat para','udhwa','udhwa','udhwa','sahibganj','816108','mango tree','2026-09-21','07:00 PM','switch board installation','no',299.00,'COD','paid','cancelled',NULL,NULL,NULL,'2026-09-21 15:33:45','2026-09-26 15:20:17'),(4,'BOOK-20260920-9EDDD9',1,NULL,5,'Gourav kumar swarnkar','8210113048','GOURAV.WIX@GMAIL.COM','','huisuifdg','ashfue','bczhdc','hsagh','hghd','786321','','2026-09-21','10:00 AM','','',249.00,'COD','pending','cancelled',NULL,NULL,NULL,'2026-09-21 17:48:57','2026-09-21 16:30:14'),(5,'BOOK-20260921-D5760C',1,4,2,'gufran ali','8210113048','GOURAV.WIX@GMAIL.COM','','udhwa','udhwa','udhwa','udhwa','sahibaganj','816108','','2026-09-22','20:00','','',550.00,'COD','paid','cancelled',NULL,NULL,NULL,'2026-09-21 15:49:01','2026-09-26 15:20:24'),(6,'BOOK-20260921-E42305',1,NULL,2,'gufran ali','8210113048','GOURAV.WIX@GMAIL.COM','','udhwa','udhwa','udhwa','udhwa','sahibaganj','816108','','2026-09-21','08:00 PM','','',550.00,'COD','paid','cancelled',NULL,NULL,NULL,'2026-09-21 15:59:26','2026-09-22 15:04:58'),(7,'BOOK-20260921-6076E0',1,NULL,2,'gufran ali','8210113048','GOURAV.WIX@GMAIL.COM','','udhwa','udhwa','udhwa','udhwa','sahibaganj','816108','','2026-09-21','08:00 PM','','',550.00,'COD','paid','cancelled',NULL,NULL,NULL,'2026-09-21 15:59:50','2026-09-22 15:04:53'),(8,'BOOK-20260922-9EA12E',1,NULL,83,'DEVA MANDAL','8210113048','GOURAV.WIX@GMAIL.COM','','udhwa','udhwa','udhwa','rajmahal','sahibganj','816108','','2026-09-22','04:00 PM','','',249.00,'COD','paid','completed',NULL,NULL,NULL,'2026-09-22 17:24:25','2026-09-27 15:39:51'),(9,'BOOK-20260926-0140C6',2,6,6,'test bot 1','0000000001','codex.test.20260925@example.com','','g','t','t','t','t','816108','','2026-09-27','06:00 PM','','',299.00,'COD','paid','completed',NULL,NULL,NULL,'2026-09-26 17:20:00','2026-09-27 15:47:43'),(10,'BOOK-20260926-B8E771',3,6,17,'test boat','9546328821','testboat@gmail.com','','test','test','test','test','test','816108','','2026-09-27','09:00 AM','','',299.00,'COD','paid','completed',NULL,NULL,NULL,'2026-09-26 17:46:19','2026-09-27 15:45:53'),(11,'BOOK-20260927-835C5C',2,6,5,'aman','0000000001','codex.test.20260925@example.com','','udhwa','udhwa','udhwa','rajmahal','sahibganj','816108','','2026-09-28','01:00 PM','','',249.00,'COD','paid','completed',NULL,NULL,NULL,'2026-09-27 15:08:24','2026-09-27 15:41:13'),(12,'BOOK-20260927-224AC9',2,NULL,58,'BOOKIG TEST RAHUL','0000000001','codex.test.20260925@example.com','','UDHWA','UDHWA','UDHWA','UDHWA','SAHIBGANJ','816108','','2026-09-28','10:00 AM','','',299.00,'COD','paid','pending',NULL,NULL,NULL,'2026-09-27 16:27:30','2026-09-27 17:21:20'),(13,'BOOK-20260927-B4F295',4,9,70,'BOOKIG TEST AMIT','7070707077','TESTAC1@GMAIL.COM','','SAHIBGANJ','SAHIBGANJ','SAHIBGANJ','SAHIBGANJ','SAHIBGANJ','816109','','2026-09-28','11:00 AM','','',699.00,'COD','paid','completed',NULL,NULL,NULL,'2026-09-27 16:30:03','2026-09-27 16:34:25');
/*!40000 ALTER TABLE `bookings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `city`
--

DROP TABLE IF EXISTS `city`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `city` (
  `id` int NOT NULL AUTO_INCREMENT,
  `city` varchar(20) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `city`
--

LOCK TABLES `city` WRITE;
/*!40000 ALTER TABLE `city` DISABLE KEYS */;
/*!40000 ALTER TABLE `city` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contact_messages`
--

DROP TABLE IF EXISTS `contact_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contact_messages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `mobile` varchar(15) DEFAULT NULL,
  `subject` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `status` enum('New','Read','Replied') DEFAULT 'New',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contact_messages`
--

LOCK TABLES `contact_messages` WRITE;
/*!40000 ALTER TABLE `contact_messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `contact_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_resets`
--

DROP TABLE IF EXISTS `password_resets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_resets` (
  `id` int NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `otp` varchar(6) NOT NULL,
  `user_type` varchar(20) NOT NULL DEFAULT 'customer',
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_email` (`email`),
  KEY `idx_token` (`token`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_resets`
--

LOCK TABLES `password_resets` WRITE;
/*!40000 ALTER TABLE `password_resets` DISABLE KEYS */;
INSERT INTO `password_resets` VALUES (1,'GOURAV.WIX@GMAIL.COM','378e2a230427ca5508172f26736ab81b398dcd4a733c804e7ed4096f034b0174','433056','customer','2026-10-08 16:18:25',1,'2026-10-08 16:03:25');
/*!40000 ALTER TABLE `password_resets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `booking_id` int NOT NULL,
  `payment_method` enum('COD') DEFAULT 'COD',
  `amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `payment_status` enum('pending','paid','failed','refunded') DEFAULT 'pending',
  `transaction_id` varchar(150) DEFAULT NULL,
  `paid_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payments`
--

LOCK TABLES `payments` WRITE;
/*!40000 ALTER TABLE `payments` DISABLE KEYS */;
INSERT INTO `payments` VALUES (1,1,'COD',249.00,'pending',NULL,NULL,'2026-09-20 17:48:57'),(2,2,'COD',299.00,'paid',NULL,'2026-09-22 15:05:17','2026-09-21 15:33:45'),(3,5,'COD',550.00,'paid',NULL,'2026-09-22 15:05:03','2026-09-21 15:49:01'),(4,6,'COD',550.00,'paid',NULL,'2026-09-22 15:04:58','2026-09-21 15:59:26'),(5,7,'COD',550.00,'paid',NULL,'2026-09-22 15:04:53','2026-09-21 15:59:50'),(6,8,'COD',249.00,'paid',NULL,'2026-09-27 15:39:51','2026-09-22 17:24:25'),(7,9,'COD',299.00,'paid',NULL,'2026-09-27 15:39:48','2026-09-26 17:20:00'),(8,10,'COD',299.00,'paid',NULL,'2026-09-27 15:39:45','2026-09-26 17:46:19'),(9,11,'COD',249.00,'paid',NULL,'2026-09-27 15:42:53','2026-09-27 15:08:24'),(10,12,'COD',299.00,'paid',NULL,'2026-09-27 17:21:20','2026-09-27 16:27:30'),(11,13,'COD',699.00,'paid',NULL,'2026-09-27 16:34:25','2026-09-27 16:30:03');
/*!40000 ALTER TABLE `payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `provider_notifications`
--

DROP TABLE IF EXISTS `provider_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `provider_notifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `provider_id` int NOT NULL,
  `booking_id` int NOT NULL,
  `notification_type` enum('new_booking','booking_accepted','booking_rejected','booking_rescheduled','booking_cancelled','booking_updated') DEFAULT 'new_booking',
  `message` text,
  `is_read` tinyint(1) DEFAULT '0',
  `response_status` enum('pending','accepted','rejected','rescheduled') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=44 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `provider_notifications`
--

LOCK TABLES `provider_notifications` WRITE;
/*!40000 ALTER TABLE `provider_notifications` DISABLE KEYS */;
INSERT INTO `provider_notifications` VALUES (1,4,2,'new_booking','New Book a consultant booking available in udhwa. Booking ID: BOOK-20260921-9826AB',1,'accepted','2026-09-21 15:33:45'),(2,4,2,'booking_updated','Booking BOOK-20260921-9826AB status updated to On The Way.',0,'pending','2026-09-21 15:43:56'),(3,4,2,'booking_updated','Booking BOOK-20260921-9826AB status updated to Work Started.',0,'pending','2026-09-21 15:44:18'),(4,4,2,'booking_updated','Booking BOOK-20260921-9826AB status updated to Completed.',0,'pending','2026-09-21 15:44:30'),(5,4,5,'new_booking','New MCB & submeter Installation booking available in udhwa. Booking ID: BOOK-20260921-D5760C',1,'rescheduled','2026-09-21 15:49:01'),(6,4,5,'booking_updated','Booking BOOK-20260921-D5760C reschedule has been accepted.',0,'pending','2026-09-21 15:51:37'),(7,4,5,'booking_updated','Booking BOOK-20260921-D5760C status updated to On The Way.',0,'pending','2026-09-21 15:51:49'),(8,4,5,'booking_updated','Booking BOOK-20260921-D5760C status updated to Work Started.',0,'pending','2026-09-21 15:52:11'),(9,4,6,'new_booking','New MCB & submeter Installation booking available in udhwa. Booking ID: BOOK-20260921-E42305',0,'pending','2026-09-21 15:59:26'),(10,4,7,'new_booking','New MCB & submeter Installation booking available in udhwa. Booking ID: BOOK-20260921-6076E0',0,'pending','2026-09-21 15:59:50'),(11,4,2,'new_booking','New booking BOOK-20260921-9826AB has been assigned to you.',0,'pending','2026-09-26 15:20:10'),(12,4,2,'booking_cancelled','Booking BOOK-20260921-9826AB was cancelled by admin.',0,'pending','2026-09-26 15:20:17'),(13,4,5,'booking_cancelled','Booking BOOK-20260921-D5760C was cancelled by admin.',0,'pending','2026-09-26 15:20:24'),(14,4,9,'new_booking','New Book a consultant booking available in t. Booking ID: BOOK-20260926-0140C6',1,'rejected','2026-09-26 17:33:35'),(15,4,11,'new_booking','New Ceiling Fan Installation booking available in udhwa. Booking ID: BOOK-20260927-835C5C',1,'rejected','2026-09-27 15:08:24'),(16,5,11,'new_booking','New booking BOOK-20260927-835C5C has been assigned to you.',1,'rejected','2026-09-27 15:18:32'),(17,6,11,'new_booking','New booking BOOK-20260927-835C5C has been assigned to you.',1,'rejected','2026-09-27 15:18:48'),(18,6,11,'new_booking','New booking BOOK-20260927-835C5C has been assigned to you.',1,'rejected','2026-09-27 15:34:30'),(19,6,11,'new_booking','New booking BOOK-20260927-835C5C has been assigned to you.',1,'accepted','2026-09-27 15:38:11'),(20,6,11,'booking_updated','Booking BOOK-20260927-835C5C status updated to On The Way.',0,'pending','2026-09-27 15:38:30'),(21,6,11,'booking_updated','Booking BOOK-20260927-835C5C status updated to Work Started.',0,'pending','2026-09-27 15:38:32'),(22,6,11,'booking_updated','Booking BOOK-20260927-835C5C status updated to Completed.',0,'pending','2026-09-27 15:38:35'),(23,6,11,'new_booking','New booking BOOK-20260927-835C5C has been assigned to you.',1,'accepted','2026-09-27 15:38:49'),(24,6,11,'booking_updated','Booking BOOK-20260927-835C5C status updated to On The Way.',0,'pending','2026-09-27 15:40:23'),(25,6,11,'booking_updated','Booking BOOK-20260927-835C5C status updated to Work Started.',0,'pending','2026-09-27 15:40:26'),(26,6,11,'booking_updated','Booking BOOK-20260927-835C5C status updated to Completed.',0,'pending','2026-09-27 15:40:28'),(27,6,10,'new_booking','New booking BOOK-20260926-B8E771 has been assigned to you.',1,'accepted','2026-09-27 15:44:41'),(28,6,9,'new_booking','New booking BOOK-20260926-0140C6 has been assigned to you.',1,'accepted','2026-09-27 15:45:13'),(29,6,10,'booking_updated','Booking BOOK-20260926-B8E771 status updated to On The Way.',0,'pending','2026-09-27 15:45:37'),(30,6,10,'booking_updated','Booking BOOK-20260926-B8E771 status updated to Work Started.',0,'pending','2026-09-27 15:45:39'),(31,6,9,'booking_updated','Booking BOOK-20260926-0140C6 status updated to On The Way.',0,'pending','2026-09-27 15:45:41'),(32,6,9,'booking_updated','Booking BOOK-20260926-0140C6 status updated to Work Started.',0,'pending','2026-09-27 15:45:44'),(33,6,10,'booking_updated','Booking BOOK-20260926-B8E771 status updated to Completed.',0,'pending','2026-09-27 15:45:53'),(34,6,9,'booking_updated','Booking BOOK-20260926-0140C6 status updated to Completed.',0,'pending','2026-09-27 15:45:56'),(35,6,9,'new_booking','New booking BOOK-20260926-0140C6 has been assigned to you.',1,'accepted','2026-09-27 15:47:01'),(36,6,9,'booking_updated','Booking BOOK-20260926-0140C6 status updated to On The Way.',0,'pending','2026-09-27 15:47:39'),(37,6,9,'booking_updated','Booking BOOK-20260926-0140C6 status updated to Work Started.',0,'pending','2026-09-27 15:47:40'),(38,6,9,'booking_updated','Booking BOOK-20260926-0140C6 status updated to Completed.',0,'pending','2026-09-27 15:47:43'),(39,8,12,'new_booking','New RO Water Purifier Service booking available in UDHWA. Booking ID: BOOK-20260927-224AC9',0,'pending','2026-09-27 16:27:30'),(40,9,13,'new_booking','New booking BOOK-20260927-B4F295 has been assigned to you.',1,'accepted','2026-09-27 16:32:08'),(41,9,13,'booking_updated','Booking BOOK-20260927-B4F295 status updated to On The Way.',0,'pending','2026-09-27 16:33:33'),(42,9,13,'booking_updated','Booking BOOK-20260927-B4F295 status updated to Work Started.',0,'pending','2026-09-27 16:33:36'),(43,9,13,'booking_updated','Booking BOOK-20260927-B4F295 status updated to Completed.',0,'pending','2026-09-27 16:33:38');
/*!40000 ALTER TABLE `provider_notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `service_providers`
--

DROP TABLE IF EXISTS `service_providers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `service_providers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `provider_name` varchar(150) NOT NULL,
  `mobile` varchar(15) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `address` text,
  `village` varchar(100) DEFAULT NULL,
  `area` varchar(100) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `district` varchar(100) DEFAULT NULL,
  `pincode` varchar(10) DEFAULT NULL,
  `service_categories` text,
  `experience` int DEFAULT '0',
  `government_document_file` varchar(100) DEFAULT NULL,
  `provider_photo_file` varchar(100) DEFAULT NULL,
  `status` enum('pending','active','inactive','blocked') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mobile` (`mobile`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `service_providers`
--

LOCK TABLES `service_providers` WRITE;
/*!40000 ALTER TABLE `service_providers` DISABLE KEYS */;
INSERT INTO `service_providers` VALUES (1,'admin','8210113048','admin@gmail.com','admin123',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,'active','2026-09-20 17:56:53','2026-09-20 17:59:20'),(3,'admin','8210113049','admin@gmail.com','admin123',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,'active','2026-09-20 17:57:15','2026-09-20 18:00:36'),(4,'AMAN MANDAL','8210113047','aman123@gmail.com','$2y$10$Sp8X63A8DA4ZkiSVS06kae50jdPIckwgm/KByAFjXiHdnrLTs5Zza','udhwa','udhwa','udhwa','udhwa','sahibganj','816108','Electrician',4,NULL,NULL,'active','2026-09-21 15:12:30','2026-09-21 15:13:17'),(5,'TANMAY TUSH SINGH','9546328811','TANMAY@GAMIL.COM','$2y$10$mXYLATaCzVCc68E8TFwARuAMiJ/L41/mbh4tjA0BUMdst.48gSD9i','Barharwa bangali bara','Barharwa','Barharwa','Barharwa','Sahibganj','816101','AC Repair',6,NULL,NULL,'active','2026-09-25 16:47:56','2026-09-25 16:49:12'),(6,'BHARAT SAHA','9546327711','admintest1@gmail.com','$2y$10$TN9pUtqoCpTUL559ME3ar.aJpUqxSqF4/5P9NzcKbbHBqMG5fgfgC','udhwa 123','udhwa','udhwa','udhwa','sahibganj','816108','Home Cleaning, Salon at Home, Pest Control, Car Wash, Sofa Cleaning',10,'1a747a51c5be610cd16ce461f62d47ff3f9240b67990fdfd.jpg',NULL,'active','2026-09-26 15:36:36','2026-09-26 15:37:26'),(7,'test 2.0','9546327712','admintest12@gmail.com','$2y$10$DOoHywvNC8thmi7Tz7ba/uV1ZP7nFkBObh5mZTqErTODbwK9yMfaS','udhwa 123','udhwa','udhwa','udhwa','sahibganj','816108','AC Repair, Home Cleaning, Salon at Home, Pest Control, Car Wash, Sofa Cleaning',10,'9bd52005b2ab2010f97c9ba6da8480a144fbd4bac7d2c482.jpg','03f07e1872dfb6b1790577613e5ed5273bc6531f44f02b23.jpg','active','2026-09-26 15:51:39','2026-09-26 17:48:02'),(8,'TEST SERVICE - 1','7070707070','TEST1@GMAIL.COM','$2y$10$7MwnUR1kzmN1DWzWBOIrjeeDfuz1dMCm1Dl9ykHqFGTSsxNNkMEUW','UDHWA','UDHWA','UDHWA','UDHWA','SAHIBGANJ','816108','Electrician, Plumber, AC Repair, Home Cleaning, Salon at Home, Carpenter, Painter, Pest Control, Car Wash, Sofa Cleaning, Laundry, Appliance Repair',10,'61566948835c99424c67640a1f79afdd2f5cc59132e6b208.jpg','e7625cadd06b71fed1c5ef28ed15550b71268926b15794c0.jpg','active','2026-09-27 16:25:16','2026-09-27 16:25:30'),(9,'TEST SERVICE - 2','7070833287','TEST2@GMAIL.COM','$2y$10$jkkwGeOeNXZ4aGrLGohhuuevDFgrfg2PD.N4NfjQ/kCz3NU/kdXcS','SAHIBGANJ','SAHIBGANJ','SAHIBGANJ','SAHIBGANJ','SAHIBGANJ','816109','Electrician, Plumber, AC Repair, Home Cleaning, Salon at Home, Carpenter, Painter, Pest Control, Car Wash, Sofa Cleaning, Laundry, Appliance Repair',11,'18baa78757388928d236698063f700337d0f5c8ce338785b.png','ecda2d58e88a62593f2f78ae01058e5eec4d932f086e4579.jpg','active','2026-09-27 16:31:54','2026-09-27 16:32:00');
/*!40000 ALTER TABLE `service_providers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `services`
--

DROP TABLE IF EXISTS `services`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `services` (
  `id` int NOT NULL AUTO_INCREMENT,
  `category` varchar(100) NOT NULL,
  `service_name` varchar(150) NOT NULL,
  `description` text,
  `price` decimal(10,2) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `status` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=84 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `services`
--

LOCK TABLES `services` WRITE;
/*!40000 ALTER TABLE `services` DISABLE KEYS */;
INSERT INTO `services` VALUES (1,'Electrician','Switch & Socket Repair','Repair damaged switches, sockets and wiring.',149.00,'service_6aaecf2d04a354.79048352.png',1,'2026-09-19 17:42:11'),(2,'Electrician','MCB & submeter Installation','MCB & Submeter Installation – Safe installation and testing.',550.00,'service_6aaf71c63092b4.15784531.png',1,'2026-09-20 05:40:22'),(3,'Electrician','Inverter & Stabilizer Installation','Inverter & Stabilizer Installation/Reinstallation – Safe setup and testing.',749.00,'service_6aaf722c2615e8.61833726.png',1,'2026-09-20 05:42:04'),(4,'Electrician','Wiring','Wiring – Safe and reliable electrical wiring. \r\n\r\nWiring starting price 250rs extra charge according to place',250.00,'service_6aaf72a73241b8.35981711.png',1,'2026-09-20 05:44:07'),(5,'Electrician','Ceiling Fan Installation','Ceiling Fan Installation/Reinstallation/Uninstallation – Safe fitting and removal.',249.00,'service_6aaf7301855914.87797324.png',1,'2026-09-20 05:45:37'),(6,'Electrician','Book a consultant','Book a Consultant Electrician – Expert electrical consultation and guidance.',299.00,'service_6aaf73dd35aff4.55002115.png',1,'2026-09-20 05:49:17'),(7,'Plumber','Tap Repair','Repair leaking or damaged taps and faucets.',149.00,'service_6aaf8013078322.94172474.png',1,'2026-09-20 06:02:14'),(8,'Plumber','Pipe Leakage Repair','Find and repair water pipe leakage.',199.00,'service_6aaf800040cc36.63983006.png',1,'2026-09-20 06:02:14'),(9,'Plumber','Wash Basin Repair','Repair and service wash basin fittings.',199.00,'service_6aaf7ff30f02f9.71866307.png',1,'2026-09-20 06:02:14'),(10,'Plumber','Toilet Repair','Repair common toilet and flush problems.',249.00,'service_6aaf7fe2c09081.05871178.png',1,'2026-09-20 06:02:14'),(11,'Plumber','Flush Tank Repair','Repair or replace flush tank parts.',199.00,'service_6aaf7fd4a4d3e9.51869260.png',1,'2026-09-20 06:02:14'),(12,'Plumber','Shower Installation','Install or replace shower fittings.',199.00,'service_6aaf7fc85f61c0.41606146.png',1,'2026-09-20 06:02:14'),(13,'Plumber','Bathroom Fitting Installation','Install bathroom taps, fittings and accessories.',299.00,'service_6aaf7fb7bb5e12.09459233.png',1,'2026-09-20 06:02:14'),(14,'Plumber','Kitchen Sink Repair','Repair leakage and fitting problems in kitchen sinks.',199.00,'service_6aaf7fa8e26357.08199403.png',1,'2026-09-20 06:02:14'),(15,'Plumber','Water Tank Pipe Repair','Repair water tank inlet and outlet pipe connections.',299.00,'service_6aaf7b86eb41f3.48612751.png',1,'2026-09-20 06:02:14'),(16,'Plumber','Drain Blockage Removal','Clear common drain and pipe blockages.',299.00,'service_6aaf7b34182383.15099213.png',1,'2026-09-20 06:02:14'),(17,'Plumber','Geyser Plumbing Service','Repair plumbing connections for geyser installation.',299.00,'service_6aaf7aef14fca3.60404770.png',1,'2026-09-20 06:02:14'),(18,'Plumber','Water Motor Connection','Install or repair water motor plumbing connections.',349.00,'service_6aaf7a56583c31.88303500.png',1,'2026-09-20 06:02:14'),(19,'AC Repair','AC Service','Complete AC servicing to improve cooling and overall performance.',399.00,'service_6aaf892db90989.29240554.png',1,'2026-09-20 06:49:19'),(20,'AC Repair','AC Repair','Professional repair service for common air conditioner problems.',299.00,'service_6aaf8913f2baa4.30306115.png',1,'2026-09-20 06:49:19'),(21,'AC Repair','AC Installation','Professional installation of split and window air conditioners.',699.00,'service_6aaf891e64d4e0.37661274.png',1,'2026-09-20 06:49:19'),(22,'AC Repair','AC Uninstallation','Safe removal and uninstallation of your air conditioner.',499.00,'service_6aaf8907e70021.76918592.png',1,'2026-09-20 06:49:19'),(23,'AC Repair','AC Gas Filling','AC gas charging service to restore proper cooling performance.',1499.00,'service_6aaf88fa4ee1b4.74586245.png',1,'2026-09-20 06:49:19'),(24,'AC Repair','AC Gas Leak Repair','Find and repair refrigerant gas leakage in your AC system.',799.00,'service_6aaf88f0e5d588.21507976.png',1,'2026-09-20 06:49:19'),(25,'AC Repair','AC Cleaning','Basic indoor and outdoor AC cleaning for better performance.',349.00,'service_6aaf88e845d4a3.25603187.png',1,'2026-09-20 06:49:19'),(26,'AC Repair','AC Deep Cleaning','Deep cleaning of AC filters, coils and other important components.',599.00,'service_6aaf88dd2853b1.08445476.png',1,'2026-09-20 06:49:19'),(27,'AC Repair','AC PCB Repair','Diagnosis and repair of AC PCB and electronic control problems.',999.00,'service_6aaf88d4aaf767.17169444.png',1,'2026-09-20 06:49:19'),(28,'AC Repair','AC Compressor Repair','Professional diagnosis and repair service for AC compressor problems.',1499.00,'service_6aaf88ca78eb47.24319025.png',1,'2026-09-20 06:49:19'),(29,'AC Repair','AC Water Leakage Repair','Repair AC water leakage and drainage-related problems.',399.00,'service_6aaf88c0bf07d9.49731614.png',1,'2026-09-20 06:49:19'),(30,'AC Repair','AC Cooling Problem Repair','Diagnosis and repair of AC cooling problems.',499.00,'service_6aaf88a99c7e87.52610565.png',1,'2026-09-20 06:49:19'),(31,'Home Cleaning','Full Home Cleaning','Complete cleaning of rooms, floors, surfaces and common areas.',999.00,'service_6aaf8f1e072656.77104710.png',1,'2026-09-20 07:31:32'),(32,'Home Cleaning','Bathroom Cleaning','Deep cleaning of bathroom floors, tiles, wash basin and fittings.',299.00,'service_6aaf8ee87d83a5.85949112.png',1,'2026-09-20 07:31:32'),(33,'Home Cleaning','Kitchen Cleaning','Cleaning of kitchen surfaces, cabinets, tiles and other areas.',399.00,'service_6aaf8eda1e3a93.67681692.png',1,'2026-09-20 07:31:32'),(34,'Home Cleaning','Sofa Cleaning','Professional cleaning of sofas to remove dust and dirt.',499.00,'service_6aaf8d9a132662.86679522.png',1,'2026-09-20 07:31:32'),(35,'Home Cleaning','Bedroom Cleaning','Complete cleaning of bedroom floors, surfaces and furniture areas.',299.00,'service_6aaf8d4d6f7d60.09328806.png',1,'2026-09-20 07:31:32'),(36,'Home Cleaning','Move-In / Move-Out Cleaning','Detailed cleaning service for homes before moving in or after moving out.',1299.00,'service_6aaf8cd2c75cc7.42643895.png',1,'2026-09-20 07:31:32'),(37,'Salon at Home','Haircut for Women','Professional haircut and basic hair styling at home.',299.00,'service_6aafa3d0daf2d4.73999014.png',1,'2026-09-20 08:13:11'),(38,'Salon at Home','Hair Spa','Relaxing hair spa service for healthy and smooth hair.',499.00,'service_6aafa3b52ac771.85041412.png',1,'2026-09-20 08:13:11'),(39,'Salon at Home','Facial','Professional facial service for fresh and glowing skin.',399.00,'service_6aafa3a9ced311.13345163.png',1,'2026-09-20 08:13:11'),(40,'Salon at Home','Manicure','Complete manicure service for clean and healthy hands.',299.00,'service_6aafa3a099d905.83601147.png',1,'2026-09-20 08:13:11'),(41,'Salon at Home','Pedicure','Complete pedicure service for clean and well-groomed feet.',349.00,'service_6aafa387d81c90.37805541.png',1,'2026-09-20 08:13:11'),(42,'Painter','Full Home Painting','Complete interior and exterior painting service for your home.',2999.00,'full-home-painting.png',1,'2026-09-20 13:33:59'),(43,'Painter','Room Painting','Professional painting service for bedrooms, living rooms and other rooms.',999.00,'room-painting.png',1,'2026-09-20 13:33:59'),(44,'Painter','Wall Painting','Give your walls a fresh and attractive new look.',799.00,'wall-painting.png',1,'2026-09-20 13:33:59'),(45,'Painter','Ceiling Painting','Professional ceiling painting and finishing service.',699.00,'ceiling-painting.png',1,'2026-09-20 13:33:59'),(46,'Painter','Door & Window Painting','Painting and finishing service for doors and windows.',599.00,'door-window-painting.png',1,'2026-09-20 13:33:59'),(47,'Painter','Texture Painting','Decorative texture painting for attractive interior walls.',1499.00,'texture-painting.png',1,'2026-09-20 13:33:59'),(48,'Carpenter','Furniture Repair','Repair damaged or broken furniture such as tables, chairs and beds.',299.00,'furniture-repair.png',1,'2026-09-20 13:47:20'),(49,'Carpenter','Door Repair','Repair loose, damaged or difficult-to-close doors.',249.00,'door-repair.png',1,'2026-09-20 13:47:20'),(50,'Carpenter','Door Installation','Professional installation of wooden and other doors.',499.00,'door-installation.png',1,'2026-09-20 13:47:20'),(51,'Carpenter','Furniture Assembly','Assembly service for beds, tables, chairs and other furniture.',299.00,'furniture-assembly.png',1,'2026-09-20 13:47:20'),(52,'Carpenter','Wardrobe Repair','Repair wardrobe doors, hinges, handles and other fittings.',349.00,'wardrobe-repair.png',1,'2026-09-20 13:47:20'),(53,'Carpenter','Curtain Rod Installation','Professional installation of curtain rods and brackets.',199.00,'curtain-rod-installation.png',1,'2026-09-20 13:47:20'),(54,'Appliance','Washing Machine Repair','Repair common washing machine problems and faults.',299.00,'washing-machine-repair.png',1,'2026-09-20 13:49:36'),(55,'Appliance','Refrigerator Repair','Professional repair service for common refrigerator problems.',299.00,'refrigerator-repair.png',1,'2026-09-20 13:49:36'),(56,'Appliance','Microwave Repair','Repair heating and other common microwave problems.',299.00,'microwave-repair.png',1,'2026-09-20 13:49:36'),(57,'Appliance','Chimney Repair','Repair and service kitchen chimney problems.',349.00,'chimney-repair.png',1,'2026-09-20 13:49:36'),(58,'Appliance','RO Water Purifier Service','Service and repair for common RO water purifier problems.',299.00,'ro-service.png',1,'2026-09-20 13:49:36'),(59,'Appliance','Geyser Repair','Repair and service common electric geyser problems.',299.00,'geyser-repair.png',1,'2026-09-20 13:49:36'),(60,'Pest Control','Cockroach Control','Professional treatment to help control cockroach infestation in your home.',499.00,'cockroach-control.png',1,'2026-09-20 13:54:46'),(61,'Pest Control','Termite Control','Treatment service to help protect your home and furniture from termites.',999.00,'termite-control.png',1,'2026-09-20 13:54:46'),(62,'Pest Control','Mosquito Control','Pest control treatment to help reduce mosquitoes around your home.',599.00,'mosquito-control.png',1,'2026-09-20 13:54:46'),(63,'Pest Control','Bed Bug Control','Professional treatment for common bed bug problems.',799.00,'bed-bug-control.png',1,'2026-09-20 13:54:46'),(64,'Pest Control','Ant Control','Treatment service for common ant infestation problems.',399.00,'ant-control.png',1,'2026-09-20 13:54:46'),(65,'Pest Control','General Pest Control','General pest control treatment for common household pests.',699.00,'general-pest-control.png',1,'2026-09-20 13:54:46'),(66,'Car Wash','Basic Car Wash','Exterior car wash to remove dust, dirt and mud.',299.00,'basic-car-wash.png',1,'2026-09-20 13:59:39'),(67,'Car Wash','Premium Car Wash','Complete exterior and interior cleaning for your car.',499.00,'premium-car-wash.png',1,'2026-09-20 13:59:39'),(68,'Car Wash','Interior Cleaning','Deep cleaning of car seats, dashboard, mats and interior areas.',399.00,'car-interior-cleaning.png',1,'2026-09-20 13:59:39'),(69,'Car Wash','Car Foam Wash','Foam wash service for better cleaning of your car exterior.',349.00,'car-foam-wash.png',1,'2026-09-20 13:59:39'),(70,'Car Wash','Car Polishing','Professional polishing service to improve the appearance of your car.',699.00,'car-polishing.png',1,'2026-09-20 13:59:39'),(71,'Car Wash','Full Car Detailing','Detailed interior and exterior cleaning for your car.',1299.00,'car-detailing.png',1,'2026-09-20 13:59:39'),(72,'Sofa Cleaning','1 Seater Sofa Cleaning','Professional cleaning of a single sofa seat to remove dust and dirt.',199.00,'1-seater-sofa-cleaning.png',1,'2026-09-20 14:36:10'),(73,'Sofa Cleaning','2 Seater Sofa Cleaning','Deep cleaning service for a two-seater sofa.',299.00,'2-seater-sofa-cleaning.png',1,'2026-09-20 14:36:10'),(74,'Sofa Cleaning','3 Seater Sofa Cleaning','Professional deep cleaning for three-seater sofas.',399.00,'3-seater-sofa-cleaning.png',1,'2026-09-20 14:36:10'),(75,'Sofa Cleaning','5 Seater Sofa Cleaning','Complete cleaning service for five-seater sofas.',599.00,'5-seater-sofa-cleaning.png',1,'2026-09-20 14:36:10'),(76,'Sofa Cleaning','L Shape Sofa Cleaning','Deep cleaning service for L-shaped sofas and sectional sofas.',699.00,'l-shape-sofa-cleaning.png',1,'2026-09-20 14:36:10'),(77,'Sofa Cleaning','Premium Sofa Cleaning','Detailed sofa cleaning to remove dust, stains and surface dirt.',899.00,'premium-sofa-cleaning.png',1,'2026-09-20 14:36:10'),(78,'Laundry','Wash & Fold','Professional washing, drying and folding of clothes.',99.00,'wash-fold.png',1,'2026-09-20 14:38:01'),(79,'Laundry','Wash & Iron','Complete washing and ironing service for everyday clothes.',149.00,'wash-iron.png',1,'2026-09-20 14:38:01'),(80,'Laundry','Dry Cleaning','Professional dry cleaning service for delicate and special garments.',199.00,'dry-cleaning.png',1,'2026-09-20 14:38:01'),(81,'Laundry','Steam Ironing','Professional steam ironing service for wrinkle-free clothes.',49.00,'steam-ironing.png',1,'2026-09-20 14:38:01'),(82,'Laundry','Blanket Cleaning','Deep cleaning service for blankets to remove dust and dirt.',199.00,'blanket-cleaning.png',1,'2026-09-20 14:38:01'),(83,'Laundry','Curtain Cleaning','Professional cleaning service for curtains and fabric materials.',249.00,'curtain-cleaning.png',1,'2026-09-20 14:38:01');
/*!40000 ALTER TABLE `services` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `full_name` varchar(100) NOT NULL,
  `mobile` varchar(15) NOT NULL,
  `email` varchar(150) NOT NULL,
  `city` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `profile_photo` varchar(255) DEFAULT NULL,
  `status` enum('active','blocked') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mobile` (`mobile`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'GOURAV KUMAR','8210113048','GOURAV.WIX@GMAIL.COM','Udhwa rajmahal','$2y$10$SIxQXsr3W8IioKSd/GT83.oVtzGro9vSKWQdk5pxNX6UcNH1VxRxG',NULL,'active','2026-09-15 17:22:51'),(2,'Codex Local Test','0000000001','codex.test.20260925@example.com','Udhwa','$2y$10$3m.tOC.4tC9lmnKEnAA8L.DhWlO8CVRvBk0EKVS6LJsg4MBSXF3eS',NULL,'active','2026-09-25 05:25:37'),(3,'Test me','9546328821','testboat@gmail.com','Udhwa','$2y$10$TtJP6OHU5YJtuN9bfo/bw.qDF5VtF9uqZISHUD29/dKiFg5MebR9O',NULL,'active','2026-09-26 17:45:12'),(4,'TEST ACCOUNT - 1','7070707077','TESTAC1@GMAIL.COM','Sahibganj','$2y$10$qiMlMpmhkLOL983N4fmzZ.B9uOpDySSvqzFpcNWik71FbUEL8qACS',NULL,'active','2026-09-27 16:28:56');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-10-21 21:44:20
