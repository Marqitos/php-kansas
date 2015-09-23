<?php

class Kansas_Auth_Membership
	implements Kansas_Auth_Adapter_Interface {
	
	private $_email;
	private $_password;
	private $_membership;
	
	public function __construct(Kansas_Db_Auth_Membership $membership, $email, $password) {
		$this->_membership	= $membership;
		$this->_email				= $email;
		$this->_password		= $password;
	}
	
	/**
	 * Performs an authentication attempt
	 * @return Zend_Auth_Result
	 */
	public function authenticate() {
		return $this->_membership->validate($this->_email, $this->_password);
	}
	
}