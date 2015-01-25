CREATE TABLE `users` (
  `id` binary(16) NOT NULL,
  `name` varchar(250) DEFAULT NULL,
  `email` varchar(250) NOT NULL,
  `subscriptions` int(11) DEFAULT '0',
  `isApproved` int(11) DEFAULT '0',
  `isLockedOut` int(11) DEFAULT '0',
  `lastLockOutDate` datetime DEFAULT NULL,
  `comment` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `Email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Usuarios';

CREATE TABLE `roles` (
  `user` binary(16) NOT NULL,
  `rol` varchar(45) NOT NULL,
  UNIQUE KEY `user` (`user`,`rol`),
  CONSTRAINT `roles_users` FOREIGN KEY (`user`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Rols';

