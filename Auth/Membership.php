<?php

class Kansas_Auth_Membership
	implements Zend_Auth_Adapter_Interface {
	
	private $_signIn;
	
	private $_email;
	private $_password;
	private $_membership;
	
	public function __construct(Kansas_Db_SignIn $signIn, Kansas_Db_Auth_Membership $membership, $email, $password) {
		$this->_signIn			= $signIn;
		$this->_membership	= $membership;
		$this->_email				= $email;
		$this->_password		= $password;
	}
	
	/**
	 * Performs an authentication attempt
	 *
	 * @throws Zend_Auth_Adapter_Exception If authentication cannot be performed
	 * @return Zend_Auth_Result
	 */
	public function authenticate() {
		$result = $this->_membership->validate($this->_email, $this->_password);
		// Log intento de inicio de sesión
		$this->_signIn->addResult($result);
		return $result;
	}
	
}