<?php

class Kansas_Application_Module_Membership
	extends Kansas_Application_Module_Abstract
	implements Kansas_Auth_Service_Interface {

	public function __construct(Zend_Config $options) {
		parent::__construct($options);
		global $application;
		$usersModule = $application->getModule('Users');
		$usersModule->setAuthService('membership', $this);
	}

	public function factory($email, $password) {
		global $application;
		return new Kansas_Auth_Membership(
			$application->getProvider('SignIn'),
			$application->getProvider('Auth_Membership'),
			$email,
			$password
		);
	}
	
	public function getAuthType() {
		return 'form';
	}
	
}