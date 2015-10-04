<?php

class Kansas_Application_Module_Auth
  extends Kansas_Application_Module_Abstract {

  protected $options;
	private $_router;
	private $_authServices = [];
	private $_rolePermisions;
	
	private $_events;

	public function __construct(array $options) {
    parent::__construct($options);
		global $application;
		@session_start();
		$_events = new Kansas_Auth_Events();
		$application->registerPreInitCallbacks([$this, "appPreInit"]);
		$application->registerRouteCallbacks([$this, "appRoute"]);
    $application->registerRenderCallbacks([$this, "appRender"]);
	}
  
  public function getDefaultOptions() {
    return [
      'router' => [
        'basepath' => 'account'
      ]
    ];
  }
  
  public function getVersion() {
		global $environment;
		return $environment->getVersion();
	}	
	
	public function appRoute(Kansas_Request $request, $params) { // Añadir datos de usuario
		return $this->hasIdentity() ? ['identity' => $this->getIdentity()]
																: [];
	}
		
	public function appPreInit() { // añadir router
		global $application;
		$application->addRouter($this->getRouter());
	}
	
  public function appRender() { // desbloquear sesión
    session_write_close();
	}
  
	public function getRouter() {
		if($this->_router == null)
			$this->_router = new Kansas_Router_Account($this->getOptions('router'));
		return $this->_router;
	}
	
	public function createAuthMembership($service = 'membership', array $params = []) {
		return call_user_func_array(
			$this->getAuthServiceFactory($service),
			$params);
	}
	
	protected function getAuthServiceFactory($serviceName) {
		$factory = $this->getAuthService($serviceName);
		return $factory ?	[$factory, 'factory']:
											function() { return false; };
	}
	
	public function getAuthService($serviceName = 'membership') { // Devuelve un servicio de autenticación por el nombre
		return isset($this->_authServices[$serviceName]) ? 	$this->_authServices[$serviceName]:
																												false;
	}
	
	public function getAuthServices($serviceAuthType = 'form') { // Devuelve los servicios de autenticación por el tipo
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
		return $this->getOptions(['router', 'basePath']);
	}
	
	// Obtiene los rols del usuario actual, invitado si no esta autenticado
	public function getCurrentRoles() {
		return $this->hasIdentity() ?	$auth->getIdentity()->getRoles()
																:	[Kansas_User_Abstract::ROLE_GUEST];
	}
	
	// Obtiene el usuario actual, o false si no está autenticado
    public function getIdentity() {
			return (isset($_SESSION['auth'])) ? $_SESSION['auth']
																				: false;
    }
	
	// Obtiene si el usuario actual tiene permisos para realizar una acción
	public function hasPermision($permisionName) {
		if(!isset($_SESSION['auth']))
			return false;
		else if($this->getIdentity()->isInRole(Kansas_User_Abstract::ROLE_ADMIN))
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
	
  public function authenticate(Kansas_Auth_Adapter_Interface $adapter) {
    $result = $adapter->authenticate();
		$this->_events->autenthicationAttempt($adapter, $result);

    if (isset($_SESSION['auth']))
      unset($_SESSION['auth']);

    if ($result->isValid())
      $_SESSION['auth'] = $result->getIdentity();

    return $result;
  }	
	
  public function hasIdentity() {
    return isset($_SESSION['auth']);
  }

  public function clearIdentity() {
    unset($_SESSION['auth']);
  }
    
}