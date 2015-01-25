<?php

class Kansas_Auth_Token
	implements Zend_Auth_Adapter_Interface {
	
	private $_signIn;
	
	private $_token;
	private $_provider;
	
	public function __construct(Kansas_Db_SignIn $signIn, Kansas_Db_Token $provider, $token) {
		$this->_signIn			= $signIn;
		$this->_provider		= $provider;
		$this->_token				= $token;
	}
	
	/**
	 * Performs an authentication attempt
	 *
	 * @throws Zend_Auth_Adapter_Exception If authentication cannot be performed
	 * @return Zend_Auth_Result
	 */
	public function authenticate() {
		$result = $this->_provider->validate($this->_token);
		// Log intento de inicio de sesión
		$this->_signIn->addResult($result);
		return $result;
	}
	
}