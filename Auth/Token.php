<?php

class Kansas_Auth_Token
	implements Kansas_Auth_Adapter_Interface {
	
	private $_token;
	private $_provider;
	
	public function __construct(Kansas_Db_Token $provider, $token) {
		$this->_provider		= $provider;
		$this->_token				= $token;
	}
	
	/* Performs an authentication attempt
	 * @return Zend_Auth_Result
	 */
	public function authenticate() {
		return $this->_provider->validate($this->_token);
	}
	
}