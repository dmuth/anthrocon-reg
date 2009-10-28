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
-- Table structure for table `reg_message`
--

DROP TABLE IF EXISTS `reg_message`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `reg_message` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) default NULL,
  `value` text,
  `notes` text,
  `subject` varchar(255) default NULL,
  `type` varchar(255) default 'message',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `reg_message_name` (`name`)
) ENGINE=MyISAM AUTO_INCREMENT=13 DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `reg_message`
--

LOCK TABLES `reg_message` WRITE;
/*!40000 ALTER TABLE `reg_message` DISABLE KEYS */;
INSERT INTO `reg_message` VALUES (1,'no-levels-available','Sorry, but registration does not seem to be open at this time.\r\n\r\nIf you believe you are receiving this message in error, please contact us at <b>!munged_email</b>.\r\n','Test note.','','message'),(2,'header','Welcome to Anthrocon registration!','',NULL,'message'),(3,'verify','Please fill out all of the fields below to verify your memberships. If you experience problems, feel free to contact us at <b>!munged_email</b>.\r\n','',NULL,'message'),(4,'success','Congratulations!  Your registration was successful, and your badge number is <b>!badge_num</b>.\r\n\r\nYour credit card (!cc_name) was successfully charged for $<b>!total_cost</b>.\r\n\r\nYou will receive a confirmation email sent to !member_email shortly.\r\n\r\nTo confirm your registration in the future, please point your web browser to !verify_url.\r\n\r\nIf you have any questions, please contact us at <b>!munged_email</b>.\r\n','This mesasge is displayed to the user after a successful registration.',NULL,'message'),(5,'email-receipt','Congratulations!  Your registration was successful, and your badge number is <b>!badge_num</b>.\r\n\r\nYour credit card (!cc_name) was successfully charged for $<b>!total_cost</b>.\r\n\r\nTo confirm your registration in the future, please point your web browser to !verify_url.\r\n\r\nIf you have any questions, please contact us at <b>!munged_email</b>.\r\n\r\ntest2','This goes out when a user purchases a membership on the website.','test subject2','email'),(6,'email-receipt-no-cc','Congratulations!  Your registration was successful, and your badge number is <b>!badge_num</b>.\r\n\r\nTo confirm your registration in the future, please point your web browser to !verify_url.\r\n\r\nIf you have any questions, please contact us at <b>!munged_email</b>.\r\n','This would go out if a user has their receipt re-sent to them, or if we manually re-send the receipt.','test subject 3','email'),(7,'cc-declined','\r\nWe\'re sorry, but your credit card seems to have been declined.\r\n\r\nPlease check your credit card data and try again.  If you still encounter problems, please email us at !email so that we may investigate.','Display when a member\'s credit card is declined.',NULL,'message'),(8,'cc-error','\r\nWe\'re sorry, but an error seems to have occurred on our end.\r\n\r\nPlease wait a few minutes and try again.  If you are still unable to purchase a registration, kindly email us at !email for further assistance.  Thanks!','Display when authorize.net freaks out or similar.',NULL,'message'),(9,'cc-no-amex','We\'re sorry, but at this time we do not accept American Express cards.\r\n','','','message'),(10,'cc-declined-cvv','The good news is that the credit card number and expiration date look okay.\r\nThe bad news is that the \"card security code\" does not.  Please check and make sure that you have entered the right number, and try again.\r\n','Display when a CVV code is invalid.','','message'),(11,'footer','Anthrocon is a Pennsylvania-incorporated 501(c)7 nonprofit organization.  As such, donations to Anthrocon are <b>not</b> deductible from individual US income taxes.\r\n\r\n','','','message'),(12,'cc-declined-avs','The good news is that the credit card number and expiration date look okay.\r\nThe bad news is that our merchant gateway said there was a problem with your billing address.  Please check to make sure that the billing address you entered matches what your credit card company has on file, and try again.\r\n','Printed when there is an AVS mismatch','','message');
/*!40000 ALTER TABLE `reg_message` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2008-10-08  2:54:43
