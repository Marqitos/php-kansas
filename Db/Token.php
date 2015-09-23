<?php

//CREATE TABLE `bioter`.`tokens` (
//  `Id` BINARY(16) NOT NULL,
//  `user` BINARY(16) NOT NULL,
//  `device` BINARY(16) NULL,
//  PRIMARY KEY (`Id`),
//  UNIQUE INDEX `U_USER_DEVICE` (`user` ASC, `device` ASC));

class Kansas_Db_Token
	extends Kansas_Db {

	public function __construct(Zend_Db_Adapter_Abstract $db) {
		parent::__construct($db);
	}

	public function getToken(System_Guid $userId, System_Guid $deviceId = null) {
		if($deviceId == null) {
			$sql = 'SELECT Id FROM `Tokens` WHERE User = UNHEX(?) AND Device = NULL;';
			$params = [$token->getHex()];
		} else {
			$sql = 'SELECT Id FROM `Tokens` WHERE User = UNHEX(?) AND Device = UNHEX(?);';
			$params = [$token->getHex(), $deviceId->getHex()];
		}
		$row = $this->db->fetchOne($sql, $params);
		return $row == null ?
			false:
			new System_Guid($row);
	}
	
	public function createToken(System_Guid $userId, System_Guid $deviceId = null) {
		$device = $deviceId == null?
			null:
			$deviceId->getHex();
		$token = System_Guid::getNew();
		$sql = 'REPLACE INTO `Tokens` SET Id = UNHEX(?) WHERE User = UNHEX(?) AND Device = UNHEX(?);';
		$this->db->query($sql, [
			$token->getHex(),
			$userId->getHex(),
			$device
		]);
		return $token;
	}
	
	public function validate(System_Guid $token, System_Guid $deviceId = null) {
		if($deviceId == null) {
			$sql = 'SELECT USR.* FROM `Users` AS USR INNER JOIN `Tokens` AS TKS ON USR.Id = TKS.Id WHERE TKS.Id = UNHEX(?) AND TKS.Device = NULL;';
			$params = [$token->getHex()];
		} else {
			$sql = 'SELECT USR.* FROM `Users` AS USR INNER JOIN `Tokens` AS TKS ON USR.Id = TKS.Id WHERE TKS.Id = UNHEX(?) AND (TKS.Device = UNHEX(?) OR TKS.Device = NULL);';
			$params = [$token->getHex(), $deviceId->getHex()];
		}
		$row = $this->db->fetchRow($sql, $params);
		if($row == null) {
			return Kansas_Auth_Result::Failure(Kansas_Auth_Result::FAILURE_CREDENTIAL_INVALID);
		} elseif($row['IsLockedOut'] == 0) {
			return Kansas_Auth_Result::Success(new Kansas_User($row));
		} else {
			return Kansas_Auth_Result(Kansas_Auth_Result::FAILURE);
		}
	}


}