<?php

class Kansas_Application_Module_BackendCache
	extends Zend_Cache_Backend_File
  implements Kansas_Application_Module_Interface {
  
  private $_router;
  private $options;
  private $_config;

  public function __construct(array $options) {
    $this->_config = $options;
    parent::__construct($this->getOptions());
    
    if($this->getOptions('cacheRouting')) {
      global $application;
  		$application->registerPreInitCallbacks([$this, "appPreInit"]);
  		$application->registerRouteCallbacks([$this, "appRoute"]);
    }
     
  }

  public function getOptions($key = NULL){
    if($this->options == null) {
      $this->options = array_replace_recursive(
        $this->getDefaultOptions(),
        $this->_config
      );
    }
    if($key == null)
      return $this->options;
    elseif(is_string($key))
      return $this->options[$key];
    elseif(is_array($key)) {
      $value = $this->options;
      foreach($key as $search)
        $value = $value[$search];
      return $value;
    } else 
      throw new System_ArgumentOutOfRangeException();
  }

  public function setOptions($options) {
    $this->_config = $options;
    $this->options = null;
  }
  
  public function getDefaultOptions() {
    global $environment;
    return [
      'cacheRouting' => ($environment->getStatus() == Kansas_Environment::PRODUCTION)
    ];
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
  
  public function getVersion() {
		global $environment;
		return $environment->getVersion();
	}	
}
		
	