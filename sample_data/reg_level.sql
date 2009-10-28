-- MySQL dump 10.11
--
-- Host: localhost    Database: drupal_ac
-- ------------------------------------------------------
-- Server version	5.0.51b

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `reg_level`
--

DROP TABLE IF EXISTS `reg_level`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `reg_level` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) default NULL,
  `year` int(11) default NULL,
  `reg_type_id` int(11) NOT NULL,
  `price` decimal(10,2) default NULL,
  `start` int(11) NOT NULL default '0',
  `end` int(11) NOT NULL default '0',
  `description` text,
  `notes` text,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `reg_level`
--

LOCK TABLES `reg_level` WRITE;
/*!40000 ALTER TABLE `reg_level` DISABLE KEYS */;
INSERT INTO `reg_level` VALUES (1,'Attending',2009,1,'40.00',1217563200,1233460799,'An attending membership allows a member access to the convention for its duration (Thursday through Sunday), and to receive any mailings or newsletters we produce both before and after the convention for one year following the date of registration.',''),(2,'Sponsor',2009,2,'80.00',1217563200,1244260799,'Sponsors are generous patrons who like to help us provide even more services and events for our membership. A sponsor gets the full privileges of an attending membership, as well as a nifty sponsor ribbon, a free Anthrocon T-shirt and our undying gratitude. Additionally, Sponsors will be able to pick up their registration in a private registration area away from the main registration line.',''),(3,'Supersponsor',2009,3,'175.00',1217563200,1244260799,'We consider Supersponsors some of the most generous persons on the planet! We heap lavish praise and gifts unto them. Not only do they receive the privileges of a sponsor and a unique gift available only to Anthrocon Supersponsors, but those who pre-register are invited to attend a luncheon with our Guests of Honor! And don\'t forget, that along with the Sponsors, Super Sponsors will be able to pick up their registration in a private registration area away from the main registration line.',''),(4,'Attending',2008,1,'45.00',1233460800,1244260799,'An attending membership allows a member access to the convention for its duration (Thursday through Sunday), and to receive any mailings or newsletters we produce both before and after the convention for one year following the date of registration.','');
/*!40000 ALTER TABLE `reg_level` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2008-09-25  1:34:16
