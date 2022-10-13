CREATE TABLE `known_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `hostname` varchar(255) DEFAULT NULL,
  `nick_aliases` varchar(1024) DEFAULT NULL,
  `last_datatype` varchar(12) DEFAULT NULL,
  `last_message` varchar(512) DEFAULT NULL,
  `last_location` varchar(64) DEFAULT NULL,
  `total_words` bigint(20) DEFAULT NULL,
  `total_lines` bigint(20) DEFAULT NULL,
  `bot_flags` text,
  `join_modes` varchar(8) DEFAULT NULL,
  `timestamp` varchar(128) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=latin1;