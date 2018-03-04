CREATE TABLE `users` (
  `id` binary(16) NOT NULL,
  `name` varchar(250) NOT NULL,
  `email` varchar(250) NOT NULL,
  `isApproved` int(1) DEFAULT 0,
  `isEnabled` int(1) DEFAULT 1,
  `comment` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `u_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Usuarios';