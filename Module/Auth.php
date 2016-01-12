<?php

class Kansas_Module_Auth
  extends Kansas_Module_Abstract {

  /// Constantes
	// Roles predeterminadas
	const ROLE_ADMIN			= 'admin'; // Usuario con todos los permisos
	const ROLE_GUEST			= 'guest'; // Usuario no autenticado
  
  /// Campos
	private $_router;
	private $_authServices = [];
	private $_rolePermisions;
	private $_events;
  private static $_defaultScope;
  
  /// Constructor
	public function __construct(array $options) {
    parent::__construct($options, __FILE__);
		global $application;
		@session_start();
		$_events = new Kansas_Auth_Events();
		$application->registerPreInitCallbacks( [$this, "appPreInit"]);
		$application->registerRouteCallbacks(   [$this, "appRoute"]);
    $application->registerRenderCallbacks(  [$this, "appRender"]);
	}
  
  /// Miembros de Kansas_Module_Interface
  public function getVersion() {
		global $environment;
		return $environment->getVersion();
	}	
	
  /// Eventos de la aplicación
	public function appPreInit() { // añadir router
		global $application;
    $zones = $application->hasModule('zones');
    if($zones && $zones->getZone() instanceof Kansas_Module_Admin) {
      $admin = $zones->getZone();
		  $admin->registerMenuCallbacks([$this, "adminMenu"]);
    } else    
      $application->addRouter($this->getRouter());
	}
  
	public function appRoute(Kansas_Request $request, $params) { // Añadir datos de usuario
		return $this->hasIdentity() ? ['identity' => $this->getIdentity()]
																: [];
	}
	
  public function appRender() { // desbloquear sesión
    session_write_close();
	}
  
  /// Eventos de zona Admin
  public function adminMenu() {
    // TODO: Comprobar permisos
    return [
      'account'          => [
        'title'           => 'Usuarios',
        'icon'            => 'fa-user',
        'dispatch'        => [
          'controller'    => 'account',
          'action'        => 'admin'],
        'match'           => [$this, 'adminMatch'],
        'menuItems'       => [
          'roles'   => [
            'title'       => 'Roles',
            'dispatch'    => [
              'controller'=> 'account',
              'action'    => 'adminRoles']]]]];
  }
  
  public function adminMatch($path) {
    $path = substr($path, 8);
    switch($path) {
      case 'create':
        return [
          'controller'    => 'account',
          'action'        => 'adminCreateUser'
        ];
    }
    if(substr($path, 0, 11) == 'delete-user') {
      return [
        'controller'    => 'account',
        'action'        => 'adminDeleteUser',
        'user'          => substr($path, 12)
      ];
    }
  }
  
  
	public function getRouter() {
		if($this->_router == null) {
			$this->_router = new Kansas_Router_Account($this->getOptions('router'));
      foreach ($this->_authServices as $authService)
        $this->_router->addActions($authService->getActions());
    }
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
	
	public function setAuthService($serviceName, Kansas_Auth_Service_Interface $service) {
		$this->_authServices[$serviceName] = $service;
	}
	
	public function getBasePath() {
		return $this->getOptions(['router', 'basePath']);
	}
	
	// Obtiene los rols del usuario actual, invitado si no esta autenticado
	public function getCurrentRoles(System_Guid $scope = null) {
    global $application;
    if($scope == null)
      $scope = $this->getDefaultScope();
    $user;
    if(!$user = $this->hasIdentity())
      return [
        'scope' => $scope->getHex(),
        'name'  => self::ROLE_GUEST];
    return $application->getProvider('users')->getUserRoles(new System_Guid($user['id']), $scope);
	}
  
  public static function getRolesByScope(array $scope = NULL) {
    global $application;
    if($scope == null)
      $scope = self::getDefaultScope();
    $default = FALSE;
    if(isset($scope['default']) && is_callable($scope['default']))
      $default = call_user_func($scope['default']);
    if($default)
      $default = array_combine(array_column($default, 'rol'), $default);
    $result = $application->getProvider('users')->getRolesByScope(new System_Guid($scope['id']), $default);
    if($default) {
      $default  = array_combine(array_map([self, 'rolKey'], $default), $default);
      $result   = array_combine(array_map([self, 'rolKey'], $result), $result);
      return array_merge($default, $result);    
    }
   return $result;
  }
  
  public static function rolKey(array $rol) {
    return $rol['scope'] . '-' . $rol['rol'] . '-' . $rol['name'];
  }
  
  public static function getDefaultScope() {
    if(self::$_defaultScope == null) {
      global $application;
      $config = $application->getConfig();
      $id = strtoupper(md5($config['defaultDomain']));
      self::$_defaultScope = [
        'id'      => $id,
        'default' => function() use ($id) { return self::getDefaultRoles($id); },
        'name'    => 'Aplicación'];
    }
    return self::$_defaultScope;
  }
  
  public static function getDefaultRoles($scope) {
    global $application;
    $config = $application->getConfig();
    return [
      [ 'scope' => $scope,
        'rol'   => str_repeat('0', 32),
        'name'  => self::ROLE_GUEST],
      [ 'scope' => $scope,
        'rol'   => strtoupper(md5($config['defaultDomain'] . '-' . self::ROLE_ADMIN)),
        'name'  => self::ROLE_ADMIN]];
  }
  
  public function getScopes() {
    global $application;
    $config = $application->getConfig();    
    // Añadir scopes de otros modulos
    return [$config['defaultDomain'] => self::getDefaultScope()];
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