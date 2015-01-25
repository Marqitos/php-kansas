<?php

class Kansas_Db_Auth_Facebook
	extends Kansas_Db {
		
	public function __construct(Zend_Db_Adapter_Abstract $db) {
		parent::__construct($db);
	}
	
	public function createUser($fbUser, $name, $email) {
		$application = Kansas_Application::getInstance();
		$usersProvider = $application->getProvider('Users');
		$user = $usersProvider->getByEmail($email);
		$this->db->beginTransaction();
		try {
			if($user == null) {
				$user = new Kansas_User(array(
					'Id'							=> System_Guid::NewGuid(),
					'Name'						=> $name, 
					'Email'						=> $email,
					'Subscriptions' 	=> 0,
					'IsApproved'			=> 1,
					'IsLockedOut'			=> 0,
					'LastLockOutDate'	=> null,
					'Role'						=> Kansas_User::ROLE_GUEST,
					'Comment'					=> 'Facebook user'
				));
				$id = $usersProvider->createUser($user);
			} else {
				if(!$user->IsApproved()) {
					$user->setApproved(true);	
					$usersProvider->updateUser($user);
				}
				$id = $user->getId();
			}
			$sql = "REPLACE INTO `FbUsers` (`Id`, `User`, `Email`) VALUES (?, UNHEX(?), ?)";
			$this->db->query($sql, array(
				$fbUser,
				$id->getHex(),
				strtolower($email)
			));
			$this->db->commit();
		} catch(Exception $e) {
			$this->db->rollBack();
			echo($e->getMessage());
		}
		return $id;
	}
	
	public function getUser($userId) {
		$sql = 'SELECT USR.* FROM `Users` AS USR INNER JOIN `FbUsers` AS FBU ON USR.Id = FBU.User WHERE FBU.Id = ?;';
		$row = $this->db->fetchRow($sql, array($userId));
		return ($row == null) ?
			null:
			new Kansas_User($row);
	}
	
	public function validate($userId) {
		$sql = 'SELECT USR.* FROM `Users` AS USR INNER JOIN `FbUsers` AS FBU ON USR.Id = FBU.User WHERE FBU.Id = ?;';
		$row = $this->db->fetchRow($sql, array($userId));
		if($row == null) {
			return new Zend_Auth_Result(Zend_Auth_Result::FAILURE_CREDENTIAL_INVALID, null);
		} elseif($row['IsApproved'] == 1 && $row['IsLockedOut'] == 0) {
			return new Zend_Auth_Result(Zend_Auth_Result::SUCCESS, new Kansas_User($row));
		} else {
			return new Zend_Auth_Result(Zend_Auth_Result::FAILURE, null);
		}
	}


}