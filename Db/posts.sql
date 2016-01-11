CREATE TABLE `posts` (
  `id` binary(16) NOT NULL,
  `postType` binary(16) NOT NULL,
  `title` varchar(250) NOT NULL,
  `text` text,
  `textType` varchar(10) NOT NULL,
  `sumary` text NOT NULL,
  `author` binary(16) NOT NULL,
  `lang` varchar(4) NOT NULL,
  `publishDate` datetime DEFAULT NULL,
  `draft` tinyint(1) NOT NULL DEFAULT '0',
  `slug` varchar(150) DEFAULT NULL,
  PRIMARY KEY (`Id`),
  INDEX(`postType`, `id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Publicaciones';