<?php
namespace Kansas\Db\Auth;

use Exception;
use System\Guid;
use Kanasa\Auth\AuthException;
use Kansas\Db\AbstractDb;
use function password_hash;
use function strlen;

require_once 'Kansas/AbstractDb.php';
require_once 'System/Guid.php';

/*
CREATE TABLE IF NOT EXISTS `membership` (
  `id` binary(16) NOT NULL,
  `password` binary(20) NOT NULL,
  `isLockedOut` int(1) DEFAULT '0',
  `lastLockOutDate` datetime DEFAULT NULL,
	PRIMARY KEY (`Id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Cuentas de inicio de sesión';

INSERT INTO `Users` (`Id`, `Name`, `Email`, `Subscriptions`, `IsApproved`, `IsLockedOut`, `LastLockOutDate`, `Role`, `Comment`) VALUES
(UNHEX('68545fb28bdb4994b40d17203081fc9e'), 'Marcos Porto Mariño', 'marcosarnoso@msn.com', 0, 1, 0, NULL, 'admin',  'Administrador del sitio web.');
INSERT INTO `Membership` (`Id`, `Password`) VALUES
(UNHEX('68545fb28bdb4994b40d17203081fc9e'), UNHEX('ccaffecb8baeaee6cb52ff8bbaaf36f88c472763'));

*/

class Membership extends AbstractDb {
		
	public function createMembership($id, $password) {
		$hash = password_hash($password, PASSWORD_BCRYPT);
		var_dump(strlen($password), strlen($hash));
		
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
		$hash = password_hash($password, PASSWORD_BCRYPT);
		var_dump(strlen($password), strlen($hash));

		require_once 'Kansas/Auth/AuthException.php';
		$sql = 'SELECT HEX(USR.Id) as id, MBS.password, USR.name, USR.email, USR.isApproved, USR.isEnabled, USR.comment, MBS.lastLockOutDate, MBS.isLockedOut FROM `users` AS USR INNER JOIN `membership` AS MBS ON USR.Id = MBS.Id WHERE USR.Email = ?;';
		try {
			$row = $this->db->fetchRow($sql, array(strtolower($email), $hash));
			if($row == null) {
				throw new AuthException(Kansas_Auth_Exception::FAILURE_CREDENTIAL_INVALID);
			} else {

				$error = 0;
				if($row['isApproved'] == 0)
					$error += AuthException::FAILURE_IDENTITY_NOT_APPROVED;
				if($row['isEnabled'] == 0)
					$error += AuthException::FAILURE_IDENTITY_NOT_ENABLED;
				if($row['isLockedOut'] == 1)
					$error += AuthException::FAILURE_IDENTITY_LOCKEDOUT;
				if($error != 0)
					throw new AuthException($error);
				return $row;
			}
		} catch(Exception $ex) {
			if($ex instanceof AuthException)
				throw $ex;
			throw new AuthException(Kansas_Auth_Exception::FAILURE_UNCATEGORIZED);
		}
	}

}