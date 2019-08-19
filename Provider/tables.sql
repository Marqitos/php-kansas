CREATE TABLE `Lists` (
  `id` binary(16) NOT NULL,
  `list` binary(16) NOT NULL,
  `value` varchar(128) NOT NULL,
  PRIMARY KEY (`id`),
  INDEX (`id`, `list`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Listas';

CREATE TABLE `Users` (
  `id` binary(16) NOT NULL,
  `name` varchar(256) NOT NULL,
  `email` varchar(256) NOT NULL,
  `isApproved` int(1) DEFAULT 0 NOT NULL,
  `isEnabled` int(1) DEFAULT 1 NOT NULL,
  `comment` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `u_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Usuarios';

CREATE TABLE `Roles` (
  `user` binary(16) NOT NULL,
  `scope` binary(16) NOT NULL,
  `rol` binary(16) NOT NULL,
  UNIQUE KEY `u_rol` (`user`,`scope`,`rol`),
  INDEX (`user`),
  INDEX (`rol`, `scope`),
  CONSTRAINT FOREIGN KEY (`user`)
    REFERENCES `Users` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT FOREIGN KEY (`rol`, `scope`)
    REFERENCES `Lists` (`id`, `list`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Roles';

CREATE TABLE `Membership` (
  `id` binary(16) NOT NULL,
  `password` char(60) NOT NULL,
  `isLockedOut` int(1) DEFAULT '0',
  PRIMARY KEY (`Id`),
  CONSTRAINT FOREIGN KEY (`id`)
    REFERENCES `Users` (`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Cuentas de inicio de sesión';

CREATE TABLE `Tokens` (
  `id` BINARY(16) NOT NULL,
  `user` BINARY(16) NULL,
  `dev` BINARY(16) NULL,
  `header` blob NOT NULL,
  `payload` blob NOT NULL,
  `signature` blob NULL,
  `exp` INT(11),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Javascript web tokens';

CREATE TABLE `Contacts` (
  `id` BINARY(16) NOT NULL,
  `kind` varchar(64) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Contactos';

CREATE TABLE `ContactProperties` (
  `id` BINARY(16) NOT NULL,
  `contact` BINARY(16) NOT NULL,
  `key` varchar(64) NOT NULL,
  `value` blob NOT NULL,
  PRIMARY KEY (`id`),
  INDEX (`id`, `contact`),
  CONSTRAINT FOREIGN KEY (`contact`)
        REFERENCES `Contacts` (`id`)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Propiedades de los contactos';

CREATE TABLE `SignInAttempts` (
  `remoteAddress` VARCHAR(256) NOT NULL,
  `userAgent` VARCHAR(256) NOT NULL,
  `time` INT(11) NOT NULL,
  `status` INT(1) NOT NULL,
  `user` BINARY(16) NULL,
  `session` VARCHAR(128),
  INDEX (`remoteAddress`),
  INDEX (`userAgent`),
  INDEX (`session`),
  CONSTRAINT FOREIGN KEY (`user`)
    REFERENCES `Users` (`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Intentos de inicio de sesión';