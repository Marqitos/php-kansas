CREATE TABLE `roles` (
  `user` binary(16) NOT NULL,
  `scope` binary(16) NOT NULL,
  `rol` binary(16) NOT NULL,
  UNIQUE KEY `u_rol` (`user`,`scope`,`rol`),
  INDEX (`user`),
  INDEX (`rol`, `scope`),
    FOREIGN KEY (`user`)
    REFERENCES `users` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
    
    FOREIGN KEY (`rol`, `scope`)
    REFERENCES `lists` (`id`, `list`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Roles';