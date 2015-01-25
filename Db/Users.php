<?php

class Kansas_Db_Users
	extends Kansas_Db {
		
	public function __construct(Zend_Db_Adapter_Abstract $db) {
		parent::__construct($db);
	}
	
	// Devuelve un usuario por su email,
	// si el usuario no existe y se especifica un nombre, lo crea
	public function getByEmail($email, $name = null) {
		$email = trim(strtolower($email));
		$sql = 'SELECT HEX(Id) as id, name, email, subscriptions, isApproved, isLockedOut, lastLockOutDate, comment FROM `Users` WHERE `Email` = ?;';
		$row = $this->db->fetchRow($sql, strtolower($email));
		if($row != null)
			return new Kansas_User_Db($row);
			
		if(empty($name))
			return null;

		$user = new Kansas_User_Static([
			'name'						=> $name,
			'email'						=> $email,
			'subscriptions'		=> 0,
			'isApproved'			=> 0,
			'isLockedOut'			=> 0,
			'lastLockOutDate'	=> null,
			'roles'						=> Kansas_User::ROLE_SUSCRIBER,
			'comment'					=> null
		]);
		$this->saveUser($user);
		return $user;			
	}
	
	// Devuelve un usuario por su Id
	public function getById(System_Guid $id) {
		if(System_Guid::isEmpty($id))
			return null;
			
		$sql = 'SELECT HEX(Id) as id, name, email, subscriptions, isApproved, isLockedOut, lastLockOutDate, comment FROM `Users` WHERE `Id` = UNHEX(?);';
		$row = $this->db->fetchRow($sql, $id->getHex());
		return $row == null? null:
												 new Kansas_User($row);
	}
	
	public function saveUser(Kansas_User_Interface $user) {
		if(!$user->hasId() || $this->updateUser($user) == 0)
			$this->insertUser($user);
	}
	
	protected function insertUser(Kansas_User_Interface $user) {
		$id = $user->createId();
			
		$sql = "INSERT INTO `Users` (`id`, `name`, `email`, `subscriptions`, `isApproved`, `isLockedOut`, `lastLockOutDate`, `comment`) VALUES (UNHEX(?), ?, ?, ?, ?, ?, ?, ?)";
		$this->db->query($sql, array(
			$id->getHex(),
			$user->getName(),
			strtolower($user->getEmail()),
			$user->getSubscriptions(),
			$user->isApproved() ? 1: 0,
			$user->isLockedOut()? 1: 0,
			$user->getLastLockOutDate(),
			$user->getComment()
		));
		return $id;
	}
	
	protected function updateUser(Kansas_User_Interface $user) {
		$sql = "UPDATE `Users` SET `name` = ?, `email` = ?, `subscriptions` = ?, `isApproved`= ?, `isLockedOut` = ?, `lastLockOutDate` = ?, `comment` = ? WHERE `id` = UNHEX(?)";
		return $this->db->query($sql, [
			$user->getName(),
			strtolower($user->getEmail()),
			$user->getSubscriptions(),
			$user->isApproved() ? 1: 0,
			$user->isLockedOut()? 1: 0,
			$user->getLastLockOutDate(),
			$user->getComment(),
			$id->getHex()
		]);
	}
	
	public function approveUser(System_Guid $id) {
		
		
	}
	
	public function unlockOutUser(System_Guid $id, $date) {
		
	}
	
	public function lockOutUser(System_Guid $id) {
		
	}
	
	
	public function saveAddress(array $data) {
		
		$sql = "REPLACE INTO `Address` (`Id`, `User`, `Name`, `Address`, `PostalCode`, `City`, `State`, `Country`) VALUES (UNHEX(?), UNHEX(?), ?, ?, ?, ?, ?, ?)";
		
		$sql = "REPLACE INTO `DefaultAddress` (`Id`, `User`, `Type`, `Address`) VALUES (UNHEX(?), UNHEX(?), ?, UNHEX(?))";
		
	}
	
}

/*	
CREATE TABLE IF NOT EXISTS `Users` (
  `Id` binary(16) NOT NULL,
  `Name` varchar(250) DEFAULT NULL,
	`Email` varchar(250) NOT NULL,
	`Subscriptions` int(11) DEFAULT 0,
	`IsApproved` int(11) DEFAULT 0,
	`IsLockedOut` int(11) DEFAULT 0,
	`LastLockOutDate` datetime DEFAULT NULL,
	`Role` varchar(50) DEFAULT NULL,
	`Comment` text DEFAULT NULL, 
	PRIMARY KEY (`Id`),
	UNIQUE KEY `Email` (`Email`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Usuarios';	

CREATE TABLE IF NOT EXISTS `Address` (
  `Id` binary(16) NOT NULL,
  `User` binary(16) NOT NULL,
  `Name` varchar(100) NOT NULL,
	`PostOfficeBox` varchar(200) DEFAULT NULL,
	`ExtendedAddress` varchar(200) DEFAULT NULL,
	`StreetAddress` varchar(200) NOT NULL,
  `PostalCode` varchar(100) DEFAULT NULL,
  `Locality` varchar(100) NOT NULL,
  `Region` varchar(100) NOT NULL,
  `Country` binary(16) NOT NULL,
	`Label` varchar(500) DEFAULT NULL,
	PRIMARY KEY (`Id`),
	UNIQUE KEY `Name` (`User`, `Name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Direcciones';	

CREATE TABLE IF NOT EXISTS `DefaultAddress` (
  `Id` binary(16) NOT NULL,
  `User` binary(16) NOT NULL,
  `Type` varchar(50) NOT NULL,
  `Address` binary(16) NOT NULL,
	PRIMARY KEY (`Id`),
	UNIQUE KEY `Type` (`User`, `Type`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Direcciones predeterminadas';	

*/
