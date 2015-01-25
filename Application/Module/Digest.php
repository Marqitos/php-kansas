<?php

class Kansas_Application_Module_Digest
	extends Kansas_Application_Module_Abstract
	implements Kansas_Auth_Service_Interface {

	public function __construct(Zend_Config $options) {
		parent::__construct($options);
		global $application;
		$usersModule = $application->getModule('Users');
		$usersModule->setAuthService('digest', $this);
	}

	public function factory($realm) {
		global $application;
		return new Kansas_Auth_Digest(
			$application->getProvider('SignIn'),
			$application->getProvider('Auth_Digest'),
			$application->getProvider('Users'),
			$realm
		);
	}
	
	public function getAuthType() {
		return 'http';
	}
}