<?php
namespace Kansas\Plugin;

use System\Configurable;
use Kansas\Cache;
use Kansas\Cache\CacheInterface;
use Kansas\Plugin\PluginInterface;
use System\NotSupportedException;
use Exception;

class BackendCache extends Configurable implements PluginInterface {
  
  /// Campos
  private $caches = [];

  /// Constructor
  public function __construct(array $options) {
    global $application;
    parent::__construct($options);
    $this->caches['.'] = Cache::Factory( // Creamos el almacenamiento de cache
      $this->options['cache_type'],
      $this->options['cache_options']
    );

    $application->registerCallback('preinit', [$this, 'appPreInit']);
    if($this->options['log'] && $this->caches['.'] instanceof Kansas_Cache_ExtendedInterface) // Registro de errores
      $application->set('log', [$this, 'log']);
  }
  
  /// Miembros de ConfigurableInterface
  public function getDefaultOptions($environment) {
    switch ($environment) {
      case 'production':
        return [
          'cache_type' => 'File',
          'cache_options' => [],
          'log' => true
        ];
      case 'development':
      case 'test':
        return [
          'cache_type' => 'File',
          'cache_options' => [],
          'log' => true
        ];
      default:
        throw new NotSupportedException("Entorno no soportado [$environment]");
    }
  }
  
    /// Miembros de PluginInterface
    public function getVersion() {
        global $environment;
        return $environment->getVersion();
    }
  
    public function getCache($category = '.', $cacheType = null, array $cacheOptions = []) {
        if(!isset($this->caches[$category])) {
            if(empty($cacheType))
                $cacheType = $this->options['cache_type'];
            $cacheOptions = array_merge($this->options['cache_options'], $cacheOptions);
            $cacheOptions['file_name_prefix'] = $category;
            $this->cache[$category] = Cache::Factory( // Creamos el almacenamiento de cache
                $this->options['cache_type'],
                $this->options['cache_options']
            );
        }
        return $this->caches[$category];
    }
  
  /// Miembros de Kansas_Cache_Interface
  public function setDirectives(array $directives) {
    return $this->caches['.']->setDirectives($directives);
  }
  
  public function save($data, $id, array $tags = [], $specificLifetime = false) {
    return $this->caches['.']->save($data, $id, $tags, $specificLifetime);
  }
  
  public function load($id, $doNotTestCacheValidity = false) {
    return $this->caches['.']->load($id, $doNotTestCacheValidity);
  }
  
  public function test($cacheId) {
    return $this->caches['.']->test($cacheId);
  }
  
  public function clean($mode = Kansas_Cache::CLEANING_MODE_ALL, array $tags = []) {
    return $this->caches['.']->clean($mode, $tags);
  }
  
  public function remove($id) {
    return $this->caches['.']->remove($id);
  }
  
  /// Miembros de Kansas_Cache_ExtendedInterface
  public function getIdsMatchingTags($tags = []) {
    return $this->caches['.']->getIdsMatchingTags($tags);
  }
    
  /// Eventos de la aplicación
  public function appPreInit() {
    global $application;
    $zones = $application->hasPlugin('zones');
    if($zones && $zones->getZone() instanceof Kansas\Plugin\Admin) {
      $admin = $zones->getZone();
		  $admin->registerMenuCallbacks([$this, "adminMenu"]);         
      if($this->options['log'])
		    $admin->registerAlertsCallbacks([$this, "adminAlerts"]);
    }
  }

  /// Eventos de Kansas_Module_Admin
  public function adminAlerts() {
    $errors = $this->caches['.']->getIdsMatchingTags(['error']);
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
    if($this->caches['.']->test('error-' . $path)) {
      return [
        'controller'  => 'error',
        'action'      => 'adminErrorDetail',
        'error'       => unserialize($this->caches['.']->load('error-' . $path))
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
		
	