CREATE TABLE `users` (
  `id` binary(16) NOT NULL,
  `name` varchar(250) NOT NULL,
  `email` varchar(250) NOT NULL,
  `isApproved` int(11) DEFAULT '0',
  `isLockedOut` int(11) DEFAULT '0',
  `lastLockOutDate` datetime DEFAULT NULL,
  `comment` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `u_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Usuarios';