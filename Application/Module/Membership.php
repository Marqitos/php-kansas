<?php

class Kansas_Application_Module_Membership
  extends Kansas_Application_Module_Abstract
  implements Kansas_Auth_Service_Interface {

  /// Constructor
	public function __construct(array $options) {
    parent::__construct($options, __FILE__);
		global $application;
		$usersModule = $application->getModule('Auth');
		$usersModule->setAuthService('membership', $this);
	}
  
  /// Miembros de Kansas_Application_Module_Interface
  public function getVersion() {
		global $environment;
		return $environment->getVersion();
	}
  
  /// Miembros de Kansas_Auth_Service_Interface
  public function getActions() {
    return $this->getOptions('actions');
  }
  
	public function getAuthType() {
		return 'form';
	}
  
  /// Miembros estaticos
	public static function factory($email, $password) {
		global $application;
		return new Kansas_Auth_Membership(
			$application->getProvider('Auth_Membership'),
			$email,
			$password
		);
	}

}