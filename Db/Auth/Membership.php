<?php
/*
CREATE TABLE IF NOT EXISTS `Membership` (
  `Id` binary(16) NOT NULL,
  `Password` binary(20) NOT NULL,
	PRIMARY KEY (`Id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Cuentas de inicio de sesión';





INSERT INTO `Users` (`Id`, `Name`, `Email`, `Subscriptions`, `IsApproved`, `IsLockedOut`, `LastLockOutDate`, `Role`, `Comment`) VALUES
(UNHEX('68545fb28bdb4994b40d17203081fc9e'), 'Marcos Porto Mariño', 'marcosarnoso@msn.com', 0, 1, 0, NULL, 'admin',  'Administrador del sitio web.');
INSERT INTO `Membership` (`Id`, `Password`) VALUES
(UNHEX('68545fb28bdb4994b40d17203081fc9e'), UNHEX('ccaffecb8baeaee6cb52ff8bbaaf36f88c472763'));

*/

class Kansas_Db_Auth_Membership
	extends Kansas_Db {
		
	public function __construct(Zend_Db_Adapter_Abstract $db) {
		parent::__construct($db);
	}
	
	public function createMembership($id, $password) {
		
	}
	public function changePassword($id, $oldPassword, $newPassword) {
		
	}
	
	/**
	 * Performs an authentication attempt
	 *
	 * @throws Zend_Auth_Adapter_Exception If authentication cannot be performed
	 * @return Zend_Auth_Result
	 */
	public function validate($email, $password) {
		$sql = 'SELECT HEX(USR.Id) as id, USR.name, USR.email, USR.subscriptions, USR.isApproved, USR.isLockedOut, USR.lastLockOutDate, USR.comment FROM `Users` AS USR INNER JOIN `Membership` AS MBS ON USR.Id = MBS.Id WHERE USR.Email = ? AND MBS.Password = UNHEX(SHA1(?));';
		$row = $this->db->fetchRow($sql, array(strtolower($email), $password));
		if($row == null) {
			return new Zend_Auth_Result(Zend_Auth_Result::FAILURE_CREDENTIAL_INVALID, null);
		} elseif($row['isApproved'] == 1 && $row['isLockedOut'] == 0) {
			return new Zend_Auth_Result(Zend_Auth_Result::SUCCESS, new Kansas_User_Db($row));
		} else {
			return new Zend_Auth_Result(Zend_Auth_Result::FAILURE, null);
		}
	}

	
}