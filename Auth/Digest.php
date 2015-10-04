<?php

//CREATE TABLE  `Digest` (
// `Id` BINARY( 16 ) NOT NULL ,
// `Realm` BINARY( 16 ) NOT NULL ,
// `A1` BINARY( 16 ) NOT NULL ,
//PRIMARY KEY (  `Id` ),
//  UNIQUE INDEX `U_REALM` (`Id` ASC, `Realm` ASC)
//) COMMENT =  'Autenticación HttpDigest';

class Kansas_Auth_Digest
	implements Kansas_Auth_Adapter_Interface {
	
	private $_digest;
	private $_users;
	private $_realm;
	private $_nonce;
	
	private $_adminUsername;
	private $_adminA1;
	
	public function __construct(Kansas_Db_Auth_Digest $digest, Kansas_Db_Users $users, $realm) {
		$this->_digest			= $digest;
		$this->_users				= $users;
		$this->_realm				= $realm;
		$this->_nonce				= uniqid();
	}
	
	/**
	 * Performs an authentication attempt
	 *
	 * @throws Zend_Auth_Adapter_Exception If authentication cannot be performed
	 * @return Zend_Auth_Result
	 */
	public function authenticate() {
		$digest = self::getDigest();
		// If there was no digest, show login
		if (!$digest)
			$result = Kansas_Auth_Result::Failure(Kansas_Auth_Result::FAILURE);
		else {
			$digestParts = self::digestParse($digest);
			
			// Based on all the info we gathered we can figure out what the response should be
			// $A1 = md5("{$validUser}:{$realm}:{$validPass}");
			$A1 = $digestParts['username'] == $this->_adminUsername ? $this->_adminA1 :
																																$this->_digest->getA1($this->_realm, $digestParts['username']); 
			if(!$A1)
				$result = Kansas_Auth_Result::Failure(Kansas_Auth_Result::FAILURE);
			else {
				
				$A2 = md5("{$_SERVER['REQUEST_METHOD']}:{$digestParts['uri']}");
				
				$validResponse = md5("{$A1}:{$digestParts['nonce']}:{$digestParts['nc']}:{$digestParts['cnonce']}:{$digestParts['qop']}:{$A2}");
				
				if ($digestParts['response']!=$validResponse)
					$result = Kansas_Auth_Result::Failure(Kansas_Auth_Result::FAILURE_CREDENTIAL_INVALID);
				else {
					try {
						$user = $this->_users->getByEmail($digestParts['username']);
					} catch(Exception $exception) {
						// Todo: Save exception
					}
					if(!isset($user) && $digestParts['username'] == $this->_adminUsername)
						$user = $this->getAdmin();
					$result = Kansas_Auth_Result::Success($user);
				}
			}
		}
		return $result;
	}
	
	public function setAdmin($username, $A1) {
		$this->_adminUsername = $username;
		$this->_adminA1 = $A1;
	}
	
	public function getAdmin() {
		return new Kansas_User([
			'id'							=> System_Guid::getEmpty(),
			'name'						=> 'Administrador',
			'email'						=> $this->_adminUsername,
			'isApproved'			=> 1,
			'isLockedOut'			=> 0,
			'role'						=> Kansas_User::ROLE_ADMIN,
			'subscriptions'		=> 0,
			'lastLockOutDate'	=> null,
			'comment'					=> 'Administrador integrado'
		]);
	}
	
	// This function returns the digest string
	public static function getDigest() {
		if (isset($_SERVER['PHP_AUTH_DIGEST'])) // mod_php
			return $_SERVER['PHP_AUTH_DIGEST'];
		elseif (isset($_SERVER['HTTP_AUTHENTICATION']) &&
						strpos(strtolower($_SERVER['HTTP_AUTHENTICATION']),'digest')===0) // most other servers
			return substr($_SERVER['HTTP_AUTHORIZATION'], 7);
		else
			return false;
	}

	// This function forces a login prompt
	public function requireLogin(Kansas_View_Result_String_Interface $cancelResult) {
		header('WWW-Authenticate: Digest realm="' . $this->_realm . '",qop="auth",nonce="' . $this->_nonce . '",opaque="' . md5($this->_realm) . '"');
		header('HTTP/1.0 401 Unauthorized');
		echo $cancelResult->getResult();
		die();
	}
	
	// This function extracts the separate values from the digest string
	public static function digestParse($digest) {
		// protect against missing data
		$needed_parts = ['nonce'=>1, 'nc'=>1, 'cnonce'=>1, 'qop'=>1, 'username'=>1, 'uri'=>1, 'response'=>1];
		$data = [];

		preg_match_all('@(\w+)=(?:(?:")([^"]+)"|([^\s,$]+))@', $digest, $matches, PREG_SET_ORDER);

		foreach ($matches as $m) {
			$data[$m[1]] = $m[2] ? $m[2] : $m[3];
			unset($needed_parts[$m[1]]);
		}

		return $needed_parts ? false : $data;
	}
	
}