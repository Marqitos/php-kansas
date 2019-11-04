<?php
namespace Kansas\Provider\Auth;

use Exception;
use System\Guid;
use Kansas\Auth\AuthException;
use Kansas\Provider\AbstractDb;
use function password_hash;
use function strlen;

require_once 'Kansas/Provider/AbstractDb.php';
require_once 'System/Guid.php';

class Membership extends AbstractDb {
		
	public function __construct() {
		parent::__construct();
	}

	public function createMembership(Guid $id, $password) {
		$hash = password_hash($password, PASSWORD_BCRYPT);
		var_dump(strlen($password), strlen($hash));
		
	}
	public function changePassword(Guid $id, $oldPassword, $newPassword) {
		
	}
	
	/**
	 * Inicia sesión y devuelve los datos de usuario
	 *
	 * @throws Kansas_Auth_Exception Si no puede iniciar sesión con los datos facilitados
	 * @return Array Datos del usuario
	 */
	public function validate($email, $password) {
		require_once 'Kansas/Auth/AuthException.php';
		$statement = $this->db->query(
			'SELECT HEX(USR.Id) as id, MBS.password, USR.name, USR.email, USR.isApproved, USR.isEnabled, USR.comment, MBS.isLockedOut FROM `users` AS USR INNER JOIN `membership` AS MBS ON USR.Id = MBS.Id WHERE USR.Email = ?;'
		);
		try {
			$rows = $statement->execute([strtolower($email)]);
			$row = $rows->current();
			if($row == null) { // No existe ningun usuario con ese email
				throw new AuthException(AuthException::FAILURE_CREDENTIAL_INVALID);
			} else {
				$error = 0;
				if(!password_verify($password, $row['password'])) // Credenciales no validos
					$error += AuthException::FAILURE_CREDENTIAL_INVALID;
				if($row['isApproved'] == 0) // Dirección de email no verificada
					$error += AuthException::FAILURE_IDENTITY_NOT_APPROVED;
				if($row['isEnabled'] == 0) // Usuario inhabilitado
					$error += AuthException::FAILURE_IDENTITY_NOT_ENABLED;
				if($row['isLockedOut'] == 1) // Inicio de sesión mediante contraseña bloqueado
					$error += AuthException::FAILURE_IDENTITY_LOCKEDOUT;
				if($error != 0)
					throw new AuthException($error);
				unset($row['password']);
				return $row;
			}
		} catch(AuthException $ex) {
			throw $ex;
		} catch(Exception $ex) {
			throw new AuthException(AuthException::FAILURE_UNCATEGORIZED);
		}
	}

	public function getUserByEmail($email) {
		$statement = $this->db->query(
			'SELECT HEX(USR.id) as id, USR.name, USR.email, USR.isApproved, USR.isEnabled, USR.comment, MBS.isLockedOut FROM `Users` AS USR LEFT JOIN `Membership` AS MBS ON USR.id = MBS.id WHERE USR.email = ?;'
		);
		return $statement->execute([
			strtolower($email)
			])->current();
	}

}