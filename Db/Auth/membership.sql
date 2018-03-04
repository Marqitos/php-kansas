CREATE TABLE IF NOT EXISTS `membership` (
  `Id` binary(16) NOT NULL,
  `Password` binary(20) NOT NULL,
  `isLockedOut` int(1) DEFAULT '0',
  `lastLockOutDate` datetime DEFAULT NULL,
	PRIMARY KEY (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Cuentas de inicio de sesión';
