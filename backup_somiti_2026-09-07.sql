-- MariaDB dump 10.19  Distrib 10.4.27-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: somiti
-- ------------------------------------------------------
-- Server version	10.4.27-MariaDB

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
-- Current Database: `somiti`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `somiti` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci */;

USE `somiti`;

--
-- Table structure for table `investment_schedules`
--

DROP TABLE IF EXISTS `investment_schedules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `investment_schedules` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `investment_id` int(10) unsigned NOT NULL,
  `installment_no` smallint(5) unsigned NOT NULL,
  `due_date` date NOT NULL,
  `principal_amount` decimal(14,2) NOT NULL,
  `profit_amount` decimal(14,2) NOT NULL,
  `installment_amount` decimal(14,2) NOT NULL,
  `paid_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `status` enum('upcoming','partial','paid') NOT NULL DEFAULT 'upcoming',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_investment_installment` (`investment_id`,`installment_no`),
  CONSTRAINT `fk_schedule_investment` FOREIGN KEY (`investment_id`) REFERENCES `investments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `investment_schedules`
--

LOCK TABLES `investment_schedules` WRITE;
/*!40000 ALTER TABLE `investment_schedules` DISABLE KEYS */;
/*!40000 ALTER TABLE `investment_schedules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `investments`
--

DROP TABLE IF EXISTS `investments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `investments` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `invoice_no` varchar(50) NOT NULL,
  `issue_date` date NOT NULL,
  `member_id` int(10) unsigned NOT NULL,
  `item_description` varchar(255) DEFAULT NULL,
  `quantity` decimal(12,2) NOT NULL DEFAULT 1.00,
  `unit` varchar(30) DEFAULT NULL,
  `selling_price` decimal(14,2) NOT NULL DEFAULT 0.00,
  `cost_price` decimal(14,2) NOT NULL DEFAULT 0.00,
  `gross_profit` decimal(14,2) NOT NULL DEFAULT 0.00,
  `investment_amount` decimal(14,2) NOT NULL,
  `amount_paid` decimal(14,2) NOT NULL DEFAULT 0.00,
  `amount_due` decimal(14,2) NOT NULL DEFAULT 0.00,
  `profit_rate` decimal(8,2) NOT NULL DEFAULT 0.00,
  `profit_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `total_payable` decimal(14,2) NOT NULL DEFAULT 0.00,
  `term_months` smallint(5) unsigned NOT NULL,
  `start_date` date NOT NULL,
  `status` enum('active','completed','cancelled') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_investments_member` (`member_id`),
  KEY `idx_investments_status` (`status`),
  CONSTRAINT `fk_investments_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `investments`
--

LOCK TABLES `investments` WRITE;
/*!40000 ALTER TABLE `investments` DISABLE KEYS */;
/*!40000 ALTER TABLE `investments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `investor_ledger`
--

DROP TABLE IF EXISTS `investor_ledger`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `investor_ledger` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `member_id` int(10) unsigned NOT NULL,
  `entry_type` enum('collection','investment','invest_withdraw','payment','expense') NOT NULL,
  `debit` decimal(14,2) NOT NULL DEFAULT 0.00,
  `credit` decimal(14,2) NOT NULL DEFAULT 0.00,
  `purpose` varchar(200) NOT NULL DEFAULT '',
  `method` varchar(30) NOT NULL DEFAULT 'Cash',
  `reference_no` varchar(100) DEFAULT NULL,
  `entry_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ledger_member` (`member_id`),
  KEY `idx_ledger_type` (`entry_type`),
  KEY `idx_ledger_date` (`entry_date`),
  CONSTRAINT `fk_ledger_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `investor_ledger`
--

LOCK TABLES `investor_ledger` WRITE;
/*!40000 ALTER TABLE `investor_ledger` DISABLE KEYS */;
/*!40000 ALTER TABLE `investor_ledger` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `member_collections`
--

DROP TABLE IF EXISTS `member_collections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `member_collections` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `member_id` int(10) unsigned NOT NULL,
  `purpose` varchar(150) NOT NULL DEFAULT 'Monthly fee',
  `method` varchar(30) NOT NULL DEFAULT 'Cash',
  `reference_no` varchar(100) DEFAULT NULL,
  `amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `collected_at` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_collections_member` (`member_id`),
  KEY `idx_collections_date` (`collected_at`),
  CONSTRAINT `fk_collections_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `member_collections`
--

LOCK TABLES `member_collections` WRITE;
/*!40000 ALTER TABLE `member_collections` DISABLE KEYS */;
/*!40000 ALTER TABLE `member_collections` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `members`
--

DROP TABLE IF EXISTS `members`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `members` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `members`
--

LOCK TABLES `members` WRITE;
/*!40000 ALTER TABLE `members` DISABLE KEYS */;
INSERT INTO `members` VALUES (1,'Mohammad Jonaid',NULL,NULL,1,'2026-09-07 09:41:46'),(2,'Abdullah Ibne Kabir Fahim',NULL,NULL,1,'2026-09-07 09:41:46'),(3,'Abdur Rahim',NULL,NULL,1,'2026-09-07 09:41:46'),(4,'MD Ariful Islam Riad',NULL,NULL,1,'2026-09-07 09:41:46'),(5,'Mohammad Jonaid',NULL,NULL,1,'2026-09-07 10:09:03'),(6,'Abdullah Ibne Kabir Fahim',NULL,NULL,1,'2026-09-07 10:09:03'),(7,'Abdur Rahim',NULL,NULL,1,'2026-09-07 10:09:03'),(8,'MD Ariful Islam Riad',NULL,NULL,1,'2026-09-07 10:09:03');
/*!40000 ALTER TABLE `members` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-09-07 16:25:41
