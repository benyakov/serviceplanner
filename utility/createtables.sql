CREATE TABLE `days` (
  `pkey` int(10) unsigned NOT NULL auto_increment,
  `caldate` date default NULL,
  `name` varchar(50) default NULL,
  `rite` varchar(50) default NULL,
  `servicenotes` text default NULL,
  KEY `pkey` (`pkey`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
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
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
CREATE TABLE `names` (
  `book` varchar(5) default NULL,
  `number` int(11) default NULL,
  `title` varchar(50) default NULL,
  UNIQUE KEY `book` (`book`,`number`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
CREATE TABLE `sermons` (
  `bibletext` varchar(80) default NULL,
  `outline` text,
  `notes` text,
  `service` int(10) unsigned default NULL,
  UNIQUE KEY `service` (`service`),
  CONSTRAINT `sermons_ibfk_1` FOREIGN KEY (`service`) REFERENCES `days` (`pkey`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
CREATE TABLE `users` (
  `uid` smallint NOT NULL auto_increment,
  `username` char(15) NOT NULL,
  `password` varchar(1024) NOT NULL,
  `fname` char(20) NOT NULL,
  `lname` char(30) NOT NULL,
  `userlevel` tinyint NOT NULL default '0',
  `email` char(40) default NULL,
  `resetkey` text default NULL,
  `resetexpiry` datetime default NULL,
  PRIMARY KEY (`uid`)
) TYPE=InnoDB DEFAULT CHARSET=utf8;
