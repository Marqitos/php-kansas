<?php

class Kansas_Db_Model
	extends Kansas_Db {
		
	public function __construct(Zend_Db_Adapter_Abstract $db) {
		parent::__construct($db);
	}
	
	public function getModel(System_Guid $modelId) {
		if(Zend_Auth::getInstance()->hasIdentity()) {
			$sql = 'SELECT `Data` FROM `Models` WHERE `Id` = UNHEX(?) AND (`User` = UNHEX(?) OR `User` = UNHEX(?));';
			$userId = Zend_Auth::getInstance()->getIdentity()->getId();
			$modelData = $this->db->fetchOne($sql, array(
				$modelId->getHex(),
				$userId->getHex(),
				System_Guid::getEmpty()->getHex()
			));
		} else {
			$sql = 'SELECT `Data` FROM `Models` WHERE `Id` = UNHEX(?) AND `User` = UNHEX(?);';
			$modelData = $this->db->fetchOne($sql, array($modelId->getHex(), System_Guid::getEmpty()->getHex()));
		}
		return unserialize($modelData);
	}

	// Guarda los datos del modelo;
	public function save(System_Guid $modelId = null, $modelData) {
		if($modelId == null)
			return self::createModel($modelData);
		self::updateModel($modelId, $modelData);
		return $modelId;
	}
	
	public function updateModel(System_Guid $modelId, $modelData) {
		$userId = Zend_Auth::getInstance()->hasIdentity()?
			Zend_Auth::getInstance()->getIdentity()->getId():
			System_Guid::getEmpty();
		$sql = 'REPLACE INTO `Models` (`Id`, `User`, `Data`) VALUES (UNHEX(?), UNHEX(?), ?);';
		$this->db->query($sql, array($modelId->getHex(), $userId->getHex(), serialize($modelData)));
	}
	
	public function deleteModel(System_Guid $modelId) {
		$sql = 'DELETE FROM `Models` WHERE `Id` = UNHEX(?);';
		$this->db->query($sql, array($modelId->getHex()));
	}
	
	public function createModel($modelData) {
		$id = System_Guid::NewGuid();
		$auth = Zend_Auth::getInstance();
		$userHex = $auth->hasIdentity()	?	$auth->getIdentity()->getHex()
																		:	System_Guid::getEmpty()->getHex();
		$sql = 'INSERT INTO `Models` (`Id`, `User`, `Data`) VALUES (UNHEX(?), UNHEX(?), ?);';
		$this->db->query($sql, array($id->getHex(), $userHex, serialize($modelData)));
		return $id;
	}
	
}