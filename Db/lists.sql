CREATE TABLE `lists` (
  `id` binary(16) NOT NULL,
  `list` binary(16) NOT NULL,
  `value` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  INDEX (`id`, `list`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Listas';