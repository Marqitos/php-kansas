<?php

class Kansas_Application_Module_Membership
	implements Kansas_Auth_Service_Interface {

  protected $options;
  
	public function __construct(array $options) {
		global $application;
    $this->options = array_replace_recursive([
  			'path'		  => [
  				'signin'    => 'signin'], // iniciar sesión
  			'action'  => [
  				'signin'	=> [
  					'controller'	=> 'Membership',
  					'action'			=> 'signIn']]
  		], $options);
		$application->registerPreInitCallbacks([$this, "appPreInit"]);
	}

	public function appPreInit() { // añadir proveedor de inicio de sesión
		global $application;
		$usersModule = $application->getModule('Auth');
		$usersModule->setAuthService('membership', $this);
		$usersModule->getRouter()->setOptions($this->options);
	}
	
	public static function factory($email, $password) {
		global $application;
		return new Kansas_Auth_Membership(
			$application->getProvider('Auth_Membership'),
			$email,
			$password
		);
	}
	
	public function getAuthType() {
		return 'form';
	}
	
  public function getVersion() {
		global $environment;
		return $environment->getVersion();
	}
}