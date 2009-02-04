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
-- Table structure for table `days`
--

CREATE TABLE `days` (
  `pkey` int(10) unsigned NOT NULL auto_increment,
  `caldate` date default NULL,
  `name` varchar(50) default NULL,
  `rite` varchar(50) default NULL,
  KEY `pkey` (`pkey`)
) ENGINE=InnoDB AUTO_INCREMENT=209 DEFAULT CHARSET=utf8;

--
-- Dumping data for table `days`
--


/*!40000 ALTER TABLE `days` DISABLE KEYS */;
LOCK TABLES `days` WRITE;
UNLOCK TABLES;
/*!40000 ALTER TABLE `days` ENABLE KEYS */;

--
-- Table structure for table `hymns`
--

CREATE TABLE `hymns` (
  `pkey` int(10) unsigned NOT NULL auto_increment,
  `book` varchar(5) default NULL,
  `number` int(11) default NULL,
  `note` varchar(100) default NULL,
  `location` varchar(50) default NULL,
  `service` int(10) unsigned default NULL,
  `sequence` tinyint(3) unsigned default NULL,
  KEY `pkey` (`pkey`),
  KEY `service` (`service`),
  CONSTRAINT `hymns_ibfk_1` FOREIGN KEY (`service`) REFERENCES `days` (`pkey`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1041 DEFAULT CHARSET=utf8;

--
-- Dumping data for table `hymns`
--


/*!40000 ALTER TABLE `hymns` DISABLE KEYS */;
LOCK TABLES `hymns` WRITE;
UNLOCK TABLES;
/*!40000 ALTER TABLE `hymns` ENABLE KEYS */;

--
-- Table structure for table `names`
--

CREATE TABLE `names` (
  `book` varchar(5) default NULL,
  `number` int(11) default NULL,
  `title` varchar(50) default NULL,
  UNIQUE KEY `book` (`book`,`number`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Dumping data for table `names`
--


/*!40000 ALTER TABLE `names` DISABLE KEYS */;
LOCK TABLES `names` WRITE;
UNLOCK TABLES;
/*!40000 ALTER TABLE `names` ENABLE KEYS */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

