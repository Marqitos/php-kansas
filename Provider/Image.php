<?php

class Kansas_Db_Image
	extends Kansas_Db {
		
	public $tagProviders;
		
	public function __construct(Zend_Db_Adapter_Abstract $db) {
		global $application;
		parent::__construct($db);
		$this->tagProviders = [$application->getProvider('Image_TagProvider')];
	}

	// Gallery
	public function getByTagType($type) {
		$sql = "SELECT * FROM `ImageTagTypes` WHERE `Type` = ? ORDER BY `Name`;";
		$row = $this->db->fetchRow($sql, $type);
		$result = new Kansas_Media_Group_Image_TagType($row);
		return $result;
	}
	
	// GuidItemCollection
	public function getAll() {
		$sql = "SELECT * FROM `Images`;";
		$rows = $this->db->fetchAll($sql);
		$result = new Kansas_Core_GuidItem_Collection();
		foreach($rows as $row)
			$result->add(new Kansas_Media_Image($row));
		return $result;
	}

	// Image
	public function getById(System_Guid $imageId) {
		$sql = "SELECT * FROM `Images` WHERE `Id` = UNHEX(?);";
		$row = $this->db->fetchRow($sql, $imageId->getHex());
		return new Kansas_Media_Image($row);
	}
	
	// SlugCollection
	public function getTagPhotos(System_Guid $tagId) {
		$sql = "SELECT IMG.* FROM `Images` AS IMG INNER JOIN `ImageTags` AS IMT ON IMT.`Image` = IMG.`Id` WHERE IMT.Tag = UNHEX(?);";
		$rows = $this->db->fetchAll($sql, $tagId->getHex());
		$result = new Kansas_Core_GuidItem_Collection();
		foreach($rows as $row)
			$result->add(new Kansas_Media_Image($row));
		return $result;
	}

	// TagType_Collection
	public function getTagTypes() {
		$result = new Kansas_Media_Group_Image_TagType_Collection();
		foreach($this->tagProviders as $tagProvider)
			$result->addRange($tagProvider->getTagTypes());
		return $result;
	}

	// SlugCollection
	public function getTagGroupsByType($type) {
		$result = new Kansas_Core_Slug_Collection();
		foreach($this->tagProviders as $tagProvider)
			$result->addRange($tagProvider->getTagGroupsByType($type));
		return $result;
	}

	// GuidItem_Collection
	public function getTagGroupsByImageId(System_Guid $id) {
		$result = new Kansas_Core_Slug_Collection();
		foreach($this->tagProviders as $tagProvider)
			$result->addRange($tagProvider->getTagGroupsByImageId($id));
		return $result;
	}

	// ImageTag
	public function getTagGroup(System_Guid $id) {
		$result = null;
		foreach($this->tagProviders as $tagProvider) {
			$result = $tagProvider->getTagGroup($id);
			if($result != null)
				break;
		}
		return $result;
	}
	
	//SlugCollection
	public function getAlbums() {
		$sql = "SELECT * FROM `ImageAlbums` ORDER BY `Name`;";
		$rows = $this->db->fetchAll($sql);
		$result = new Kansas_Core_Slug_Collection();
		foreach($rows as $row)
			$result->add(new Kansas_Media_Group_Image_Album($row));
		return $result;
	}
	
	//Image
	public function createImage() {
		return new Kansas_Media_Image(array(
			'Id'						=> System_Guid::getEmpty()->getHex(),
			'Name'					=> '',
			'Description'		=> '',
			'Thumbnail'			=> '',
			'slug'					=> '',
			'Album'					=> null,
			'DefaultSource'	=> null
		));
	}
	
	// GuidCollection
	public function getSources(System_Guid $image) {
		$sql = "SELECT * FROM `ImageSources` WHERE `Image` = UNHEX(?) ORDER BY `Format`;";
		$rows = $this->db->fetchAll($sql, $image->getHex());
		$result = new Kansas_Core_GuidItem_Collection();
		foreach($rows as $row)
			$result->add(new Kansas_Media_Image_Source($row));
		return $result;
	}
	
	// Source
	public function getSourceByImageFormat(System_Guid $id, $format) {
		$sql = "SELECT * FROM `ImageSources` WHERE `Image` = UNHEX(?) AND `Format` = ?;";
		$row = $this->db->fetchRow($sql, array($id->getHex(), $format));
		return $row == null?
			null:
			new Kansas_Media_Image_Source($row);
	}
	
	public function saveImage($data) {
		$this->db->beginTransaction();
		try {
			$id = new System_Guid($data['Id']);
			if($id == System_Guid::getEmpty())
				$id = System_Guid::NewGuid();
			$albumId = empty($data['Album'])?
				null:
				new System_Guid($data['Album']);
			$defaultSourceId = empty($data['DefaultSource'])?
				null:
				new System_Guid($data['DefaultSource']);
				
			$sql = "REPLACE INTO `Images` (`Id`, `Name`, `Description`, `slug`, `Album`, `DefaultSource`) VALUES (UNHEX(?), ?, ?, ?, UNHEX(?), UNHEX(?));";
			$this->db->query($sql, array(
				$id->getHex(),
				$data['Name'],
				$data['Description'],
				$data['slug'],
				$albumId == null?
					null:
					$albumId->getHex(),
				$defaultSourceId == null?
					null:
					$defaultSourceId->getHex()
			));
			
			if(isset($data['sourcesRemoved'])) {
				$sql = "DELETE FROM `ImageSources` WHERE `Id` = UNHEX(?);";
				foreach($data['sourcesRemoved'] as $removeSource)
					$this->db->query($sql, $removeSource->getHex());
			}
			if(isset($data['sourcesEdit'])) {
				$sql = "REPLACE INTO `ImageSources` (`Id`, `Image`, `Format`, `Path`) VALUES (UNHEX(?), UNHEX(?), ?, ?);";
				foreach($data['sourcesEdit'] as $editSource)
					$this->db->query($sql, array(
						$editSource->getId()->getHex(),
						$id->getHex(),
						$editSource->getFormat(),
						$editSource->getPath()
					));
			}
			
			if(isset($data['tagsRemoved'])) {
				$sql = "DELETE FROM `ImageTags` WHERE `Tag` = UNHEX(?) AND `Image` = UNHEX(?);";
				foreach($data['tagsRemoved'] as $removeTag)
					$this->db->query($sql, array(
						$removeTag->getHex(),
						$id->getHex()
					));
			}
			if(isset($data['tagsEdit'])) {
				$sql = "REPLACE INTO `ImageTags` (`Id`, `Image`, `Tag`) VALUES (UNHEX(?), UNHEX(?), UNHEX(?));";
				foreach($data['tagsEdit'] as $editTag)
					$this->db->query($sql, array(
						System_Guid::NewGuid()->getHex(),
						$id->getHex(),
						$editTag->getHex()
					));
			}
			$this->db->commit();
		} catch(Exception $e) {
			$this->db->rollBack();
			echo($e->getMessage());
		}
		
		
	}

}

/*

CREATE TABLE IF NOT EXISTS `ImageAlbums` (
  `Id` binary(16) NOT NULL,
  `Name` varchar(100) NOT NULL,
  `Description` text DEFAULT NULL,
  `slug` varchar(100) NOT NULL,
  `Thumbnail` varchar(200) NOT NULL,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `Name` (`Name`),
  UNIQUE KEY `Slug` (`slug`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Albunes de imágenes';

CREATE TABLE IF NOT EXISTS `Images` (
  `Id` binary(16) NOT NULL,
  `Name` varchar(100) NOT NULL,
  `Description` text DEFAULT NULL,
  `slug` varchar(100) NOT NULL,
  `Album` int(11) DEFAULT NULL,
  `DefaultSource` binary(16) DEFAULT NULL,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `Name` (`Name`),
  UNIQUE KEY `Slug` (`slug`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Fotos';

CREATE TABLE IF NOT EXISTS `ImageTags` (
  `Id` binary(16) NOT NULL,
  `Image` binary(16) NOT NULL,
  `Tag` binary(16) NOT NULL,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `Tag` (`Image`, `Tag`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Etiquetas';

CREATE TABLE IF NOT EXISTS `ImageSources` (
  `Id` binary(16) NOT NULL,
  `Image` binary(16) NOT NULL,
  `Format` varchar(100) NOT NULL,
  `Path` varchar(200) NOT NULL,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `Format` (`Image`, `Format`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Archivos de imagen';

*/