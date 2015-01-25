<?php

class Kansas_Auth_Facebook
	implements Zend_Auth_Adapter_Interface {
	
	private $_signIn;
	private $_core;
	private $_provider;
	
	public function __construct(Kansas_Db_SignIn $signIn, Kansas_Db_Auth_Facebook $provider, Facebook_Core $core) {
		$this->_signIn		= $signIn;
		$this->_provider	= $provider;
		$this->_core			= $core;
	}
	
	/**
	 * Performs an authentication attempt
	 *
	 * @throws Zend_Auth_Adapter_Exception If authentication cannot be performed
	 * @return Zend_Auth_Result
	 */
	public function authenticate() {
		if($this->_core->getUser() == 0)
			$result = new Zend_Auth_Result(Zend_Auth_Result::FAILURE_CREDENTIAL_INVALID, null);
		elseif($user = $this->isRegistered())
			$result = new Zend_Auth_Result(Zend_Auth_Result::SUCCESS, $user);
		else
			$result = new Zend_Auth_Result(Zend_Auth_Result::FAILURE, null);
		// Log intento de inicio de sesión
		$this->_signIn->addResult($result);
		return $result;
	}
	
	
	public function isRegistered() {
		$userId = $this->_core->getUser();
		return $userId != 0 && $user = $this->_provider->getUser($userId) ? $user:
																																				false;
	}
	
	public function register($regData = null) {
		if($regData == null)
			$regData = $this->_core->getSignedRequest();
		$this->_provider->createUser($regData['user_id'], $regData['registration']['name'], $regData['registration']['email']);
	}
	
}