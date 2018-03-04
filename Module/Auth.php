<?php
require_once 'System/Configurable/Abstract.php';
require_once 'Kansas/Module/Interface.php';

class Kansas_Module_Auth
  extends System_Configurable_Abstract
  implements Kansas_Module_Interface {

  /// Constantes
  // Roles predeterminadas
  const ROLE_ADMIN			= 'admin'; // Usuario con todos los permisos
  const ROLE_GUEST			= 'guest'; // Usuario no autenticado

  const TYPE_FORM       = 'form';
  const TYPE_FEDERATED  = 'federated';
  
  /// Campos
  private $_router;
  private $_authServices = [];
  private $_session;
  private $_user;
  private $_callbacks = [
    'onChangedPassword' => []
  ];
  private $_authTypes = [];

  private $_rolePermisions;
  private static $_defaultScope;
  
  /// Constructor
  public function __construct(array $options) {
    parent::__construct($options);
    global $application;
    $application->registerCallback('preinit', [$this, "appPreInit"]);
    $application->registerCallback('route',   [$this, "appRoute"]);
  }
  
  /// Miembros de Kansas_Module_Interface
  public function getDefaultOptions($environment) {
    switch ($environment) {
    case 'production':
    case 'development':
    case 'test':
      return [
      'router' =>  [
        'base_path' => 'cuenta'
      ],
      'actions' => [
        'account' => [
          'path'        => '',
          'controller'	=> 'Auth',
          'action'	    => 'index'],
        'signout' => [
          'path' 			  => '/cerrar-session',
          'controller'	=> 'Auth',
          'action'		  => 'signOut']
      ],
      'session'	  => 'Kansas_Auth_Session_Default',
      'lifetime'  => 60*60*24*15, // 15 días
      'roles'     => []
      ];
    default:
      require_once 'System/NotSuportedException.php';
      throw new System_NotSuportedException("Entorno no soportado [$environment]");
    }
  }

  public function getVersion() {
    global $environment;
    return $environment->getVersion();
  }	
  
  /// Eventos de la aplicación
  public function appPreInit() { // añadir router
    global $application;
    $this->getSession()->initialize();
    $this->_user = $this->_session->getIdentity();
    $zones = $application->hasModule('zones');
    if($zones && $zones->getZone() instanceof Kansas_Module_Admin) {
      $admin = $zones->getZone();
      $admin->registerMenuCallbacks([$this, "adminMenu"]);
    } else    
      $application->addRouter($this->getRouter());
  }

  public function appRoute(Kansas_Request $request, $params) { // Añadir datos de usuario
    $result = [];
    if($this->_user)
      $result['identity'] = $this->_user;
    if(array_search(self::TYPE_FORM, $this->_authTypes) !== FALSE)
      $result['authForm'] = TRUE;
    return $result;
  }


  public function getSession() {
    if(!isset($this->_session)) {
      try {
        require_once 'Kansas/Loader.php';
        Kansas_Loader::loadClass($this->options['session']);
        $this->_session = new $this->options['session']();
      } catch(Exception $ex) {
        var_dump($ex);
        throw $ex;
      }
    }
    return $this->_session;
  }

  public function setIdentity($user, $remember = false, $domain = NULL) {
    $this->_user = $user;
    // Registrar eventos de inicio de sesión
    $lifetime = $remember ? $this->options['lifetime'] : 0;
    $this->getSession()->setIdentity($user, $lifetime, $domain);
  }

  public function getIdentity() {
    return ($this->_user)
      ? $this->_user
      : FALSE;
  }

  public function registerChangedPassword($callback) {
		if(is_callable($callback))
			$this->_callbacks['onChangedPassword'][] = $callback;
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
      require_once 'Kansas/Router/Auth.php';
      $this->_router = new Kansas_Router_Auth($this->options['router']);
      $this->_router->addActions($this->options['actions']);
      foreach ($this->_authServices as $authService)
        $this->_router->addActions($authService->getActions());
    }
    return $this->_router;
  }
  
  public function getAuthService($serviceName = 'membership') { // Devuelve un servicio de autenticación por el nombre
    return isset($this->_authServices[$serviceName])
      ? $this->_authServices[$serviceName]
      : false;
  }
  
  public function getAuthServices($serviceAuthType = 'form') { // Devuelve los servicios de autenticación por el tipo
    $result = [];
    foreach($this->_authServices as $name => $service)
      if($service->getAuthType() == $serviceAuthType)
        $result[$name] = $service;
    return $result;
  }
  
  public function addAuthService(Kansas_Auth_Service_Interface $authService) {
    $this->_authServices[$authService->getName()] = $authService;
    if(!array_search($this->_authTypes, $authService->getAuthType()))
    $this->_authTypes[] = $authService->getAuthType();
  }


  
  // Obtiene los rols del usuario actual, invitado si no esta autenticado
  public function getCurrentRoles(System_Guid $scope = null) {
    global $application;
    if($scope == null)
      $scope = self::getDefaultScope();
    $user = $this->getSession()->getIdentity();
    if($user === FALSE)
      return [
        'scope' => $scope['id'],
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
  
}