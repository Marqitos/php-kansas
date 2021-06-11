<?php
/**
 * Plugin para controlar la autenticación
 *
 * @package Kansas
 * @author Marcos Porto
 * @copyright Marcos Porto
 * @since v0.4
 */

namespace Kansas\Plugin;

use Kansas\Auth\ServiceInterface as AuthService;
use Kansas\Loader;
use Kansas\Plugin\Admin as AdminZone;
use Kansas\Plugin\RouterPluginInterface;
use Kansas\Router\Auth as AuthRouter;
use Kansas\Router\RouterInterface;
use Psr\Http\Message\RequestInterface;
use System\Configurable;
use System\Guid;
use System\NotSupportedException;
use System\Version;

require_once 'System/Configurable.php';
require_once 'Kansas/Plugin/RouterPluginInterface.php';
require_once 'Kansas/Router/RouterInterface.php';
require_once 'Psr/Http/Message/RequestInterface.php';

class Auth extends Configurable implements RouterPluginInterface {

	/// Constantes
	// Roles predeterminadas
	const ROLE_ADMIN        = 'admin'; // Usuario con todos los permisos
	const ROLE_GUEST        = 'guest'; // Usuario no autenticado

	const TYPE_FORM         = 'form';
	const TYPE_FEDERATED    = 'federated';
	
	/// Campos
	private $_router;
	private $_authServices = [];
	private $_session;
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
  
	// Miembros de System\Configurable\ConfigurableInterface
	public function getDefaultOptions(string $environment) : array {
		switch ($environment) {
			case 'production':
			case 'development':
			case 'test':
				return [
					'router' =>  [
						'base_path' => 'cuenta',
						'pages'		=> []],
					'actions' => [
						'account' => [
							'path'          => '',
							'controller'	=> 'Auth',
							'action'	    => 'index'],
						'sessionInfo' => [
							'path'          => 'sesiones',
							'controller'    => 'Auth',
							'action'        => 'sessionInfo'],
						'signOut' => [
							'path'          => '/cerrar-session',
							'controller'    => 'Auth',
							'action'        => 'signOut']],
					'session'	=> 'Kansas\Auth\Session\SessionDefault',
					'lifetime'  => 60*60*24*15, // 15 días
					'domain'    => '',
					'device'    => true
				];
			default:
				require_once 'System/NotSupportedException.php';
				throw new NotSupportedException("Entorno no soportado [$environment]");
		}
	}

	public function getVersion() : Version {
		global $environment;
		return $environment->getVersion();
	}

	// Miembros de Kansas\Plugin\RouterPluginInterface
	public function getRouter() : RouterInterface {
		if($this->_router == null) {
			require_once 'Kansas/Router/Auth.php';
			$this->_router = new AuthRouter($this->options['router']);
			$this->_router->addActions($this->options['actions']);
			foreach ($this->_authServices as $authService) {
				$this->_router->addActions($authService->getActions());
			}
		}
		return $this->_router;
	}
  
	/// Eventos de la aplicación
	public function appPreInit() { // añadir router
		global $application;
		require_once 'Kansas/Plugin/Admin.php';
		$zones = $application->hasPlugin('zones');
		if($zones && $zones->getZone() instanceof AdminZone) {
			$admin = $zones->getZone();
			$admin->registerMenuCallbacks([$this, "adminMenu"]);
		} else {
			$application->addRouter($this->getRouter());
		}
	}

	public function appRoute(RequestInterface $request, $params) { // Añadir datos de usuario
		$result = [];
		$session = $this->getSession();
		$user = $session->getIdentity();
		if($user) {
			$result['identity'] = $user;
		}
		if(array_search(self::TYPE_FORM, $this->_authTypes) !== FALSE) {
			$result['authForm'] = TRUE;
		}
		return $result;
	}

	public function getSession() {
		if(!isset($this->_session)) {
			require_once 'Kansas/Loader.php';
			Loader::loadClass($this->options['session']);
			$sessionOptions = [
				'lifetime'  => $this->options['lifetime'],
				'domain'    => '' // TODO: Ver por que no existe: $this->options['domain']
			];
			$this->_session = new $this->options['session']($sessionOptions);
		}
		return $this->_session;
	}

	public function setIdentity($user, $remember = false, $domain = null, $deviceId = null) {
		// Almacenar usuario en sesión
		$lifetime =	$remember
			? $this->options['lifetime']
			: 0;
		if($this->options['device']) {
			if($deviceId == null) {

			}
			$device = $deviceId;
		} else {
			$device = false;
		}
		if($domain == null) {
			$domain = ''; // TODO: Ver por que no existe: $this->options['domain']
		}
		$this->getSession()->setIdentity($user, $lifetime, $domain, $device);
	}

	public function getIdentity() {
		return $this->getSession()->getIdentity();
	}

	public function registerChangedPassword($callback) {
		if(is_callable($callback)) {
			$this->_callbacks['onChangedPassword'][] = $callback;
		}
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
		if(strpos($path, 'delete-user') == 0) {
			return [
				'controller'    => 'account',
				'action'        => 'adminDeleteUser',
				'user'          => substr($path, 12)
			];
		}
	}
  
  

	public function getAuthService($serviceName = 'membership') { // Devuelve un servicio de autenticación por el nombre
		return isset($this->_authServices[$serviceName])
			? $this->_authServices[$serviceName]
			: false;
	}
  
	public function getAuthServices($serviceAuthType = 'form') { // Devuelve los servicios de autenticación por el tipo
		$result = [];
		foreach($this->_authServices as $name => $service) {
			if($service->getAuthType() == $serviceAuthType) {
				$result[$name] = $service;
			}
		}
		return $result;
	}
  
	public function addAuthService(AuthService $authService) {
		$this->_authServices[$authService->getName()] = $authService;
		if(!array_search($authService->getAuthType(), $this->_authTypes)) {
			$this->_authTypes[] = $authService->getAuthType();
		}
	}


  
	// Obtiene los rols del usuario actual, invitado si no esta autenticado
	public function getCurrentRoles(Guid $scope = null) {
		global $application;
		if($scope == null) {
			$scope = self::getDefaultScope();
		}
		$user = $this->getSession()->getIdentity();
		if($user === FALSE) {
			return [
				'scope' => $scope['id'],
				'name'  => self::ROLE_GUEST];
		}
		return $application->getProvider('users')->getUserRoles(new Guid($user['id']), $scope);
	}
  
	public static function getRolesByScope(array $scope = NULL) {
		global $application;
		if($scope == null) {
			$scope = self::getDefaultScope();
		}
		$default = FALSE;
		if(isset($scope['default']) && is_callable($scope['default'])) {
			$default = call_user_func($scope['default']);
		}
		if($default) {
			$default = array_combine(array_column($default, 'rol'), $default);
		}
		$result = $application->getProvider('users')->getRolesByScope(new Guid($scope['id']), $default);
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
		if(!isset($_SESSION['auth'])) {
			return false;
		} elseif($this->getIdentity()->isInRole(self::ROLE_ADMIN)) {
			return true;
		} else {
			return $this->getRolePermisions()->hasPermision($this->getIdentity(), $permisionName);
		}
	}
  
	protected function getRolePermisions() {
		if($this->_rolePermisions == null) {
			; // TODO: Obtener permisos
		}
		return $this->_rolePermisions;
	}
  
}