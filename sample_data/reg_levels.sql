-- MySQL dump 10.9
--
-- Host: localhost    Database: drupal_ac
-- ------------------------------------------------------
-- Server version	4.1.10

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;

--
-- Table structure for table `reg_level`
--

DROP TABLE IF EXISTS `reg_level`;
CREATE TABLE `reg_level` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) default NULL,
  `year` int(11) default NULL,
  `reg_type_id` int(11) NOT NULL default '0',
  `price` decimal(10,2) default NULL,
  `start` date default NULL,
  `end` date default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `reg_level`
--


/*!40000 ALTER TABLE `reg_level` DISABLE KEYS */;
LOCK TABLES `reg_level` WRITE;
INSERT INTO `reg_level` VALUES (1,'Attending - $40',2008,1,'40.00','2007-07-01','2008-01-31'),(2,'Attending - $45',2008,1,'45.00','2008-02-01','2008-06-05'),(3,'Sponsor',2008,2,'80.00','2007-07-01','2008-06-05'),(4,'Super Sponsor',2008,3,'175.00','2007-07-01','2008-06-05'),(5,'Attending - $40',2009,1,'40.00','2008-07-01','2009-01-01'),(6,'Sponsor',2009,2,'80.00','2008-07-01','2009-06-14'),(7,'Super Sponsor',2009,3,'175.00','2008-07-01','2009-06-14');
UNLOCK TABLES;
/*!40000 ALTER TABLE `reg_level` ENABLE KEYS */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

