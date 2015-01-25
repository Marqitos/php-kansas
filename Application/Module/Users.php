<?php

class Kansas_Application_Module_Users
	extends Kansas_Application_Module_Abstract {

	private $_router;
	private $_authServices = [];
	private $_rolePermisions;

	public function __construct(Zend_Config $options) {
		parent::__construct($options);
		Zend_Session::start();
		global $application;
		$application->registerPreInitCallbacks([$this, "appPreInit"]);
		$application->registerRouteCallbacks([$this, "appRoute"]);
	}
	
	public function appRoute(Zend_Controller_Request_Abstract $request, $params) {
		$result = [];
		if(Zend_Auth::getInstance()->hasIdentity())
			$result['identity'] = Zend_Auth::getInstance()->getIdentity();
		return $result;
	}
		
	public function appPreInit() { // añadir router
		global $application;
		$application->getRouter()->addRouter($this->getRouter());
	}
	
	public function getRouter() {
		if($this->_router == null)
			$this->_router = new Kansas_Router_Account($this->options->router);
		return $this->_router;
	}
	
	public function createAuthMembership($service = 'membership', array $params = array()) {
		return call_user_func_array(
			$this->getAuthServiceFactory($service),
			$params);
	}
	
	protected function getAuthServiceFactory($serviceName) {
		$factory = $this->getAuthService($serviceName);
		return $factory ?	[$factory, 'factory']:
											function() { return false; };
	}
	
	public function getAuthService($serviceName = 'membership') {
		return isset($this->_authServices[$serviceName]) ? 	$this->_authServices[$serviceName]:
																												false;
	}
	
	public function getAuthServices($serviceAuthType = 'form') {
		$result = [];
		foreach($this->_authServices as $name => $service)
			if($service->getAuthType() == $serviceAuthType)
				$result[$name] = $service;
		return $result;
	}
	
	public function setAuthService($serviceName, Kansas_Auth_Service_Interface $factory) {
		$this->_authServices[$serviceName] = $factory;
	}
	
	public function getBasePath() {
		return $this->options->router->basePath;
	}
	
	// Obtiene los rols del usuario actual, invitado si no esta autenticado
	public function getCurrentRoles() {
		$auth = Zend_Auth::getInstance();
		return $auth->hasIdentity() ?	$auth->getIdentity()->getRoles():
																	[Kansas_User::ROLE_GUEST];
	}
	
	// Obtiene el usuario actual, o false si no está autenticado
	public function getIdentity() {
		$auth = Zend_Auth::getInstance();
		return $auth->hasIdentity() ?	$auth->getIdentity():
																	false;
	}
	
	// Obtiene si el usuario actual tiene permisos para realizar una acción
	public function hasPermision($permisionName) {
		if($this->getIdentity() == false)
			return false;
		else if($this->getIdentity()->isInRole(Kansas_User::ROLE_ADMIN))
			return true;
		else
			return $this->getRolePermisions()->hasPermision($this->getIdentity(), $permisionName);
	}
	
	// Obt
	protected function getRolePermisions() {
		if($this->_rolePermisions == null) {
			
		}
		return $this->_rolePermisions;
	}
	
}