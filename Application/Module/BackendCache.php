<?php

class Kansas_Application_Module_BackendCache
	extends Kansas_Application_Module_Abstract {
  
  private $_router;
  private $_cache;

  public function __construct(array $options) {
    global $application;
    parent::__construct($options, __FILE__);
    $this->_cache = Kansas_Cache::Factory(
      $this->getOptions('cacheType'),
      $this->getOptions('cacheOptions')
    );

    if($this->getOptions('cacheRouting')) {
  		$application->registerPreInitCallbacks([$this, "appPreInit"]);
  		$application->registerRouteCallbacks([$this, "appRoute"]);
    }
    if($this->getOptions('log'))
      $application->set('log', [$this, 'log']);
  }

    public function appRoute(Kansas_Request $request, $params) { // Guardar ruta en cache
        if(!isset($params['cache']) && !isset($params['error']))
            $this->save(serialize($params), $this->getCacheId($request));
        return [];
    }
        
    public function appPreInit() { // añadir router
        global $application;
        $application->addRouter($this->getRouter(), 10);
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
    if($this->_router == null)
      $this->_router = new Kansas_Router_Cache($this);
    return $this->_router;
  }
  
  
  public function log($level, $message) {
    global $environment;
		$time = $environment->getExecutionTime();
    $this->save(serialize([
      'time' => $time,
      'level' => $level,
      'message' => $message
    ]), 'error-' . System_Guid::newGuid()->__toString());
	}
  
  public function save($data, $cacheId) {
    return $this->_cache->save($data, $cacheId);
  }
  
  public function load($cacheId) {
    return $this->_cache->load($cacheId);
  }
  
  public function test($cacheId) {
    return $this->_cache->test($cacheId);
  }
  
  public function getVersion() {
		global $environment;
		return $environment->getVersion();
	}  
}
		
	