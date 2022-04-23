SET FOREIGN_KEY_CHECKS = 0;

-- --------------------------------------------------------

DROP TABLE IF EXISTS `table::user`;
CREATE TABLE `table::user` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `alias` int unsigned DEFAULT NULL,
  `uname` varchar(32) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `admin` int DEFAULT '0',
  `upass` text,
  `pw_type` enum('irreversible','reversible','temporary'),
  `pw_expire` datetime DEFAULT NULL,
  `company` varchar(255) DEFAULT NULL,
  `division` varchar(255) DEFAULT NULL,
  `fullname` varchar(255) DEFAULT NULL,
  `fullname_rubi` varchar(255) DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL,
  `zip` varchar(8) DEFAULT NULL,
  `state` varchar(50) DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `town` varchar(255) DEFAULT NULL,
  `address1` varchar(255) DEFAULT NULL,
  `address2` varchar(255) DEFAULT NULL,
  `tel` varchar(13) DEFAULT NULL,
  `fax` varchar(13) DEFAULT NULL,
  `priv` int unsigned NOT NULL DEFAULT 0,
  `contract` date DEFAULT NULL,
  `expire` int DEFAULT NULL,
  `closing` int DEFAULT NULL,
  `pay` int DEFAULT NULL,
  `forward` int DEFAULT NULL,
  `type` char(1) DEFAULT NULL,
  `restriction` varchar(32) DEFAULT NULL,
  `free1` text,
  `free2` text,
  `free3` text,
  `create_date` datetime NOT NULL,
  `modify_date` datetime DEFAULT NULL,
  `lft` int unsigned DEFAULT NULL,
  `rgt` int unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uname` (`uname`),
  UNIQUE KEY `email` (`email`,`admin`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

DROP TABLE IF EXISTS `table::user_preference`;
CREATE TABLE `table::user_preference` (
  `userkey` int unsigned NOT NULL DEFAULT '0',
  `section` varchar(32) NOT NULL DEFAULT '',
  `config` varchar(32) NOT NULL DEFAULT '',
  `value` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`userkey`,`section`,`config`),
  KEY `table::user_preference_ibfk_1` (`userkey`),
  CONSTRAINT `table::user_preference_ibfk_1` FOREIGN KEY (`userkey`) REFERENCES `table::user` (`id`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

DROP TABLE IF EXISTS `table::permission`;
CREATE TABLE `table::permission` (
  `userkey` int unsigned NOT NULL,
  `filter1` int unsigned NOT NULL DEFAULT '0',
  `filter2` int unsigned NOT NULL DEFAULT '0',
  `application` varchar(32) NOT NULL DEFAULT '',
  `class` varchar(255) NOT NULL DEFAULT '',
  `type` varchar(255) NOT NULL DEFAULT '',
  `priv` enum('0','1') NOT NULL DEFAULT '0',
  PRIMARY KEY (`userkey`,`filter1`,`filter2`,`application`,`class`,`type`),
  KEY `table::permission_ibfk_1` (`userkey`),
  CONSTRAINT `table::permission_ibfk_1` FOREIGN KEY (`userkey`) REFERENCES `table::user` (`id`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

SET FOREIGN_KEY_CHECKS = 1;
