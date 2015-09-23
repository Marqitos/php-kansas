<?php

class Kansas_Application_Module_BackendCache
	extends Zend_Cache_Backend_File {
  
  private $_router;
  
  public function __construct(array $options) {
    global $environment;
    parent::__construct($options);
    
    if(!isset($this->_options['cacheRouting']))
      $this->_options['cacheRouting'] = $environment->getStatus() == Kansas_Environment::PRODUCTION;
      
    if($this->_options['cacheRouting']) {
      global $application;
  		$application->registerPreInitCallbacks([$this, "appPreInit"]);
  		$application->registerRouteCallbacks([$this, "appRoute"]);
    }
     
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
  
	public function getCacheId(Kansas_Request $request) {
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
}
		
	