<?php

namespace Kansas\Provider;

use Kansas\Provider\AbstractDb;

require_once 'Kansas/Provider/AbstractDb.php';

class SignIn extends AbstractDb {

	const FAILURE_CREDENTIALS = 0;
	const SUCCESSFUL = 1;

	public function __construct() {
		parent::__construct();
	}
	
	public function registerSignIn(array $user, $remoteAddress, $userAgent, $session) {
		//var_dump($user, $remoteAddress, $userAgent, $session);
		$statement = $this->db->query( // Guarda una copia en la base de datos
			'INSERT INTO `SignInAttempts` '
			. '(`remoteAddress`, `userAgent`, `time`, `status`, `user`, `session`) '
			. 'VALUES (?, ?, ?, ?, UNHEX(?), ?);');
		$result = $statement->execute([
			$remoteAddress,
			$userAgent,
			time(),
			self::SUCCESSFUL,
			$user['id'],
			$session
		]);
		return $result;
	}

}