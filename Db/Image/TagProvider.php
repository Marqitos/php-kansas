<?php

class Kansas_Db_Image_TagProvider
	extends Kansas_Db
	implements Kansas_Media_Group_Image_Tag_Provider_Interface {
		
	public function __construct(Zend_Db_Adapter_Abstract $db) {
		parent::__construct($db);
	}
		
	// ArrayIterator
	public function getTagTypes() {
		$sql = "SELECT * FROM `ImageTagTypes` ORDER BY `Name`;";
		$rows = $this->db->fetchAll($sql);
		$result = new ArrayIterator();
		foreach($rows as $row)
			$result[] = new Kansas_Media_Group_Image_TagType($row);
		return $result;
	}

	// ArrayIterator
	public function getTagGroupsByType($type) {
		$sql = "SELECT * FROM `ImageTagGroups` WHERE `Type` = ? ORDER BY `Name`;";
		$rows = $this->db->fetchAll($sql, $type);
		$result = new ArrayIterator();
		foreach($rows as $row)
			$result[] = new Kansas_Media_Group_Image_Tag($row);
		return $result;
	}

	// ArrayIterator
	public function getTagGroupsByImageId(System_Guid $id) {
		$sql = "SELECT ITG.* FROM `ImageTags` AS IMT INNER JOIN `ImageTagGroups` AS ITG ON IMT.`Tag` = ITG.`Id` WHERE IMT.`Image` = UNHEX(?) ORDER BY ITG.`Name`;";
		$rows = $this->db->fetchAll($sql, $id->getHex());
		$result = new ArrayIterator();
		foreach($rows as $row)
			$result[] = new Kansas_Media_Group_Image_Tag($row);
		return $result;
	}

	// ImageTag
	public function getTagGroup(System_Guid $id) {
		$sql = "SELECT * FROM `ImageTagGroups` WHERE `Id` = UNHEX(?);";
		$row = $this->db->fetchRow($sql, $id->getHex());
		if($row == null)
			return null;
		return new Kansas_Media_Group_Image_Tag($row);
	}
}
/*
CREATE TABLE IF NOT EXISTS `ImageTagTypes` (
  `Type` varchar(50) NOT NULL,
  `Name` varchar(100) NOT NULL,
  PRIMARY KEY (`Type`),
  UNIQUE KEY `Name` (`Name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Tipos de etiquetas';

CREATE TABLE IF NOT EXISTS `ImageTagGroups` (
  `Id` binary(16) NOT NULL,
  `Name` varchar(100) NOT NULL,
  `Description` text DEFAULT NULL,
  `Type` varchar(50) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `Thumbnail` varchar(200) NOT NULL,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `Name` (`Name`, `Type`),
  UNIQUE KEY `Slug` (`slug`, `Type`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Descripción de etiquetas';
*/