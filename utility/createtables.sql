# Mysql table definitions for main data tables
#   Copyright (C) 2012 Jesse Jacobsen
#
#   This program is free software; you can redistribute it and/or modify
#   it under the terms of the GNU General Public License as published by
#   the Free Software Foundation; either version 2 of the License, or
#   (at your option) any later version.
#
#   This program is distributed in the hope that it will be useful,
#   but WITHOUT ANY WARRANTY; without even the implied warranty of
#   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#   GNU General Public License for more details.
#
#   You should have received a copy of the GNU General Public License
#   along with this program; if not, write to the Free Software
#   Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
#
#   Send feedback or donations to: Jesse Jacobsen <jmatjac@gmail.com>
#
#   Mailed donation may be sent to:
#   Bethany Lutheran Church
#   2323 E. 12th St.
#   The Dalles, OR 97058
#   USA

CREATE TABLE `days` (
  `pkey` int(10) unsigned NOT NULL auto_increment,
  `caldate` date default NULL,
  `name` varchar(50) default NULL,
  `rite` varchar(50) default NULL,
  `servicenotes` text default NULL,
  `block` integer default NULL,
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
  `manuscript` blob,
  `mstype` varchar(50) default NULL,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
CREATE TABLE `churchyear` (
    `dayname` varchar(255),
    `season` varchar(64) default "",
    `base` varchar(255) default NULL,
    `offset` smallint default 0,
    `month` tinyint default 0,
    `day`   tinyint default 0,
    `observed_month` tinyint default 0,
    `observed_sunday` tinyint default 0,
    PRIMARY KEY (`dayname`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
CREATE TABLE `churchyear_order` (
    `name` varchar(32),
    `idx` smallint UNIQUE,
    PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
CREATE TABLE `churchyear_synonyms` (
    `canonical` varchar(255),
    `synonym`   varchar(255),
    INDEX (`canonical`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
CREATE TABLE `churchyear_propers` (
    `dayname`   varchar(255),
    `color`     varchar(32),
    `theme`     varchar(64),
    `introit`   text,
    `note`      text,
    FOREIGN KEY (`dayname`) REFERENCES `churchyear` (`dayname`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
CREATE TABLE `churchyear_lessons` (
    `dayname`   varchar(255),
    `lectionary`    varchar(56),
    `lesson1`   varchar(64),
    `lesson2`   varchar(64),
    `gospel`    varchar(64),
    `psalm`     varchar(64),
    `s2lesson`  varchar(64),
    `s2gospel`  varchar(64),
    `s3lesson`  varchar(64),
    `s3gospel`  varchar(64),
    `hymnabc`   varchar(20),
    `hymn`      varchar(20),
    `id`        integer auto_increment,
    PRIMARY KEY (`id`)
    FOREIGN KEY (`dayname`) REFERENCES `churchyear` (`dayname`),
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
CREATE TABLE `churchyear_collect_index` (
    `dayname`       varchar(255),
    `lectionary`    varchar(56),
    `id`            integer,
    FOREIGN KEY (`id`) REFERENCES `churchyear_collects` (`id`),
    ON DELETE CASCADE
    ON UPDATE CASCADE
    FOREIGN KEY (`dayname`) REFERENCES `churchyear` (`dayname`),
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
CREATE TABLE `blocks` (
  `blockstart` date,
  `blockend` date,
  `label` varchar(128),
  `notes` text,
  `oldtestament` varchar(64),
  `epistle` varchar(64),
  `gospel` varchar(64),
  `psalm` varchar(64),
  `collect` varchar(64),
  `id` integer auto_increment,
  UNIQUE KEY `span` (`blockstart`, `blockend`),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

