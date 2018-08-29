<?php

namespace Kansas\Db;

use System\Guid;
use Kansas\Db\AbstractDb;

//CREATE TABLE `bioter`.`tokens` (
//  `Id` BINARY(16) NOT NULL,
//  `user` BINARY(16) NOT NULL,
//  `device` BINARY(16) NULL,
//  PRIMARY KEY (`Id`),
//  UNIQUE INDEX `U_USER_DEVICE` (`user` ASC, `device` ASC));

class Token	extends AbstractDb {

	public function getToken($token) {
		$row;
		if($this->tryGetDeviceToken($token, $row))
			return $row;
		else if($this->tryGetActionToken($token, $row))
			return $row;
		else
			return false;
	}

	public function tryGetDeviceToken($token, &$row) {
		$sql = 'SELECT HEX(USR.id) as id, USR.name, USR.email, USR.isApproved, USR.isLockedOut, USR.lastLockOutDate, USR.comment, TKS.id, TKS.device as device AS `Token` FROM `Users` AS USR INNER JOIN `DeviceTokens` AS TKS ON USR.Id = TKS.Id WHERE TKS.Id = ?;';
		$params = [$token];
		$result = $this->db->fetchOne($sql, $params);
		var_dump($result);
		exit;
		if($row == null)
			return false;
		return true;
	}
	
	public function saveDeviceToken(Guid $userId, $token, Guid $deviceId, $expire) {
		$sql = 'REPLACE INTO `DeviceTokens` SET Id = ? WHERE User = UNHEX(?) AND Device = UNHEX(?);';
		$this->db->query($sql, [
			$token,
			$userId->getHex(),
			$deviceId->getHex()
		]);
	}

	public function saveActionToken($token, Guid $userId, array $params) {

	}
	
}