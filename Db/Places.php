<?php

class Kansas_Db_Places
	extends Kansas_Db {
		
	public function __construct(Zend_Db_Adapter_Abstract $db) {
		parent::__construct($db);
	}

	public function getCountries() {
		
	}
	
	public function getCountryByCode($code) {
		$sql = 'SELECT * FROM `country` WHERE `iso` = ?;';
		$row = $this->db->fetchRow($sql, strtoupper($code));
		return $row == null?
			null:
			new Kansas_Places_Country($row['iso'], $row['printable_name']);
	}
	
	public function getAddressById(System_Guid $addressId, System_Guid $userId) {
		$sql = 'SELECT * FROM `Addresses` WHERE `Id` = UNHEX(?) AND `User` = UNHEX(?);';
		$row = $this->db->fetchRow($sql, array(
			$addressId->getHex(),
			$userId->getHex()
		));
		return $row == null?
			null:
			new Kansas_Places_Address($row);
	}
	public function getAddressByUser(System_Guid $userId) {
		$sql	= 'SELECT * FROM `Addresses` WHERE `User` = UNHEX(?);';
		$rows	= $this->db->fetchAll($sql, array(
			$userId->getHex()
		));		
		$result = new Kansas_Core_GuidItem_Collection();
		foreach($rows as $row)
			$result->add(new Kansas_Places_Address($row));
		return $result;
	}
	public function getAddress(System_Guid $userId, $addressName, $addressStreet, $addressStreet2, $addressCity, $addresPostalCode, $addressState, System_Guid $addressCountry) {
		
		
	}
	
	public function saveAddress(array $row) {
		$id			= System_Array::getValueNullGuid($row, 'Id');
		$userId	= System_Array::getValueGuid($row, 'User');
		$alias	= System_Array::getValueNotNull($row, 'Alias');
		if($id == null) {
			$sql = 'SELECT `Id` FROM `Addresses` WHERE `User` = UNHEX(?) AND `Alias` = ?;';
			$value = $this->db->fetchOne($sql, array(
				$userId,
				$alias
			));
			$id = empty($value)?
				System_Guid::NewGuid()->getHex():
				bin2hex($value);
		}
		$sql = 'REPLACE INTO `Addresses` (`Id`, `User`, `Alias`, `Name`, `Street`, `Street2`, `City`, `PostalCode`, `State`, `Country`) VALUES (UNHEX(?), UNHEX(?), ?, ?, ?, ?, ?, ?, ?, ?);';
		$this->db->query($sql, array(
			$id,
			$userId,
			$alias,
			$row['Name'],
			$row['Street'],
			System_Array::getValueNull($row, 'Street2'),
			$row['City'],
			$row['PostalCode'],
			$row['State'],
			System_Array::getValueNotNull($row, 'Country')
		));
		$row['Id'] = $id;
		return $row;
	}



}
/*	

CREATE TABLE IF NOT EXISTS `Addresses` (
  `Id` binary(16) NOT NULL,
  `User` binary(16) NOT NULL,
  `Alias` varchar(100) NOT NULL,
  `Name` varchar(150) NOT NULL,
	`PostOfficeBox` varchar(200) DEFAULT NULL,
	`Street` varchar(200) NOT NULL,
	`Street2` varchar(200) DEFAULT NULL,
  `City` varchar(100) NOT NULL,
  `PostalCode` varchar(50) DEFAULT NULL,
  `State` varchar(100) NOT NULL,
  `Country` varchar(2) NOT NULL,
	`Label` varchar(500) DEFAULT NULL,
	PRIMARY KEY (`Id`),
	UNIQUE KEY `Name` (`User`, `Alias`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Direcciones';	

*/