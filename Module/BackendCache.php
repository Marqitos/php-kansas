<?php
require_once 'System/Configurable/Abstract.php';
require_once 'Kansas/Cache/Interface.php';

class Kansas_Module_BackendCache
	extends System_Configurable_Abstract
  implements Kansas_Module_Interface, Kansas_Cache_Interface {
  
  /// Campos
  private $_router;
  private $_cache;

  /// Constructor
  public function __construct(array $options) {
    global $application;
    parent::__construct($options);
    $this->_cache = Kansas_Cache::Factory( // Creamos el almacenamiento de cache
      $this->options['cache_type'],
      $this->options['cache_options']
    );

    $application->registerCallback('preinit', [$this, 'appPreInit']);
    if($this->options['cacheRouting']) // Cache de rutas
  		$application->registerCallback('route', [$this, "appRoute"]);
    if($this->options['log'] && $this->_cache instanceof Kansas_Cache_ExtendedInterface) // Registro de errores
      $application->set('log', [$this, 'log']);
  }
  
  /// Miembros de System_Configurable_Interface
  public function getDefaultOptions($environment) {
    switch ($environment) {
      case 'production':
        return [
          'cache_type' => 'File',
          'cache_options' => [],
          'cacheRouting' => true,
          'log' => true
        ];
      case 'development':
      case 'test':
        return [
          'cache_type' => 'File',
          'cache_options' => [],
          'cacheRouting' => false,
          'log' => true
        ];
      default:
        require_once 'System/NotSupportedException.php';
        throw new System_NotSupportedException("Entorno no soportado [$environment]");
    }
  }
  
  /// Miembros de Kansas_Module_Interface
  public function getVersion() {
		global $environment;
		return $environment->getVersion();
	}
  
  /// Miembros de Kansas_Cache_Interface
  public function setDirectives(array $directives) {
    return $this->_cache->setDirectives($directives);
  }
  
  public function save($data, $id, array $tags = [], $specificLifetime = false) {
    return $this->_cache->save($data, $id, $tags, $specificLifetime);
  }
  
  public function load($id, $doNotTestCacheValidity = false) {
    return $this->_cache->load($id, $doNotTestCacheValidity);
  }
  
  public function test($cacheId) {
    return $this->_cache->test($cacheId);
  }
  
  public function clean($mode = Kansas_Cache::CLEANING_MODE_ALL, array $tags = []) {
    return $this->_cache->clean($mode, $tags);
  }
  
  public function remove($id) {
    return $this->_cache->remove($id);
  }
  
  /// Miembros de Kansas_Cache_ExtendedInterface
  public function getIdsMatchingTags($tags = []) {
    return $this->_cache->getIdsMatchingTags($tags);
  }
    
  /// Eventos de la aplicación
  public function appPreInit() {
    global $application;
    if($this->options['cacheRouting']) // añadir router
      $application->addRouter($this->getRouter(), 10);
    $zones = $application->hasModule('zones');
    if($zones && $zones->getZone() instanceof Kansas_Module_Admin) {
      $admin = $zones->getZone();
		  $admin->registerMenuCallbacks([$this, "adminMenu"]);         
      if($this->options['log'])
		    $admin->registerAlertsCallbacks([$this, "adminAlerts"]);
    }
  }

  public function appRoute(Kansas_Request $request, $params) { // Guardar ruta en cache
    if(!isset($params['cache']) && !isset($params['error']))
      $this->save(serialize($params), self::getCacheId($request), ['route']);
    return [];
  }
      
  /// Eventos de Kansas_Module_Admin
  public function adminAlerts() {
    $errors = $this->_cache->getIdsMatchingTags(['error']);
    return [
      'errores'         => [
        'title'         => 'Errores',
        'text'          => count($errors) . ' registros',
        'icon'          => 'fa-exclamation-triangle',
        'isDisabled'    => count($errors) == 0,
        'dispatch'      => [
          'controller'  => 'error',
          'action'      => 'adminError'],
        'match'         => [$this, 'errorMatch']]];    
  }
  
  public function adminMenu() {
    // TODO: Comprobar permisos
    return [
      'cache'           => [
        'title'           => 'Cache',
        'icon'            => 'fa-line-chart',
        'dispatch'        => [
          'controller'    => 'cache',
          'action'        => 'admin']]];    
  }  
  
  public function errorMatch($path) {
    $path = substr($path, 8);
    // 32
    if($this->_cache->test('error-' . $path)) {
      return [
        'controller'  => 'error',
        'action'      => 'adminErrorDetail',
        'error'       => unserialize($this->_cache->load('error-' . $path))
      ];
    }
    if($path == 'clear') {
      return [
        'controller'  => 'error',
        'action'      => 'adminErrorClear'
      ];
    }
    return FALSE;
  }
 
  
  public static function getCacheId(Kansas_Request $request) {
    global $application;
    $roles = $application->getModule('auth')->getCurrentRoles();
        return urlencode(
            'router|'.
            implode('/', $roles).
            '|'.
            $request->getUriString()
        );
    }
    
  public function getRouter() {
    if($this->_router == null) {
      require_once 'Kansas/Router/Cache.php';
      $this->_router = new Kansas_Router_Cache($this);
    }
    return $this->_router;
  }
  
	public function log($level, $message) {
		global $environment;
		$time = microtime();
		$executionTime = $environment->getExecutionTime();
		$data = (is_string($message))
		? [
			'httpCode'  => 500,
			'file'      => $environment->getRequest()->getUriString(),
			'line'      => null,
			'message'   => $message,
			'level'     => $level] 
		: ($message['code'] == 404
			? [
			'httpCode'  => 404,
			'file'      => $environment->getRequest()->getUriString(),
			'line'      => null,
			'message'   => $message['message'],
			'level'     => $level]
			: [
			'httpCode'  => $message['code'],
			'file'      => $message['file'],
			'line'      => $message['line'],
			'message'   => $message['message'],
			'level'     => $level]);
		$id = md5(serialize($data));
		$data['id'] = $id;
		
		if($this->test('error-' . $id))
		$data = unserialize($this->load('error-' . $id));
		else $data['log'] = [];
		foreach ($message['trace'] as $traceLine) {
		if(isset($traceLine['args'])) {
			$args = [];
			foreach ($traceLine['args'] as $arg) {
			try {
				$argData = serialize($arg);
				$args[] = $argId = md5($argData);
				if(!$this->test($argId))
				$this->save($argData, 'error-arg-' . $argId, ['error-arg'], null);
			} catch (Exception $e) {
				$args[] = 'no-serializable';
			}
			}
			$traceLine['args'] = $args;
		}
		}
		$log = serialize([
		'time'          => $time,
		'executionTime' => $executionTime,
		'uri'           => $environment->getRequest()->getUriString(),
		'exception'     => $message['exception'],
		'trace'         => $message['trace']
		]);

		$logId = md5($log);
		$data['log'][] = $logId;
		
		$this->save($log, 'error-log-' . $id . '-' . $logId, ['error-log'], null);
		$this->save(serialize($data), 'error-' . $id, ['error'], null);
	}

}
		
	