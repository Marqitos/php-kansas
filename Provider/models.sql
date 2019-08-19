CREATE TABLE `models` (
  `Id` binary(16) NOT NULL,
  `User` binary(16) NOT NULL,
  `Data` text NOT NULL,
  `LastAccess` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`Id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Modelos';
