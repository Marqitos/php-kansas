<?php

require_once 'Kansas/Db.php';
/*
CREATE TABLE IF NOT EXISTS `membership` (
  `Id` binary(16) NOT NULL,
  `Password` binary(20) NOT NULL,
  `isLockedOut` int(1) DEFAULT '0',
  `lastLockOutDate` datetime DEFAULT NULL,
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
	 * Inicia sesión y devuelve los datos de usuario
	 *
	 * @throws Kansas_Auth_Exception Si no puede iniciar sesión con los datos facilitados
	 * @return Array Datos del usuario
	 */
	public function validate($email, $password) {
		require_once 'Kansas/Auth/Exception.php';
		$sql = 'SELECT HEX(USR.Id) as id, USR.name, USR.email, USR.isApproved, USR.isEnabled, USR.comment, MBS.lastLockOutDate, MBS.isLockedOut FROM `users` AS USR INNER JOIN `membership` AS MBS ON USR.Id = MBS.Id WHERE USR.Email = ? AND MBS.Password = UNHEX(SHA1(?));';
		try {
			$row = $this->db->fetchRow($sql, array(strtolower($email), $password));
			if($row == null) {
				throw new Kansas_Auth_Exception(Kansas_Auth_Exception::FAILURE_CREDENTIAL_INVALID);
			} else {
				$error = 0;
				if($row['isApproved'] == 0)
					$error += Kansas_Auth_Exception::FAILURE_IDENTITY_NOT_APPROVED;
				if($row['isEnabled'] == 0)
					$error += Kansas_Auth_Exception::FAILURE_IDENTITY_NOT_ENABLED;
				if($row['isLockedOut'] == 1)
					$error += Kansas_Auth_Exception::FAILURE_IDENTITY_LOCKEDOUT;
				if($error != 0)
					throw new Kansas_Auth_Exception($error);
				return $row;
			}
		} catch(Exception $ex) {
			if($ex instanceof Kansas_Auth_Exception)
				throw $ex;
			throw new Kansas_Auth_Exception(Kansas_Auth_Exception::FAILURE_UNCATEGORIZED);
		}
	}

}