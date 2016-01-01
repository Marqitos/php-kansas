<?php

class Kansas_Application_Module_API
  extends Kansas_Application_Module_Abstract
  implements Kansas_Router_Interface {
	
	private $_basePath;

	public function __construct(array $options) {
    parent::__construct($options, __FILE__);
		global $application;
    $application->getModule('zones');
    $application->registerPreInitCallbacks([$this, "appPreInit"]);
	}
 
	public function appPreInit() { // añadir router
		global $application;
    if($zones = $application->getModule('zones') &&
       $zones->getZone() == 'api') {
      $application->addRouter($this);
      $this->_basePath = $zones->getBasePath();
    }
	}

  public function getVersion() {
		global $environment;
		return $environment->getVersion();
	}

	public function match() {
		global $application;
    global $environment;
		$params = false;
		$path = trim($environment->getRequest()->getUri()->getPath(), '/');
    if(Kansas_String::startWith($path, $this->_basePath))
      $path = substr($path, strlen($this->_basePath));
    else
			return false;
			
		switch($path) {
			case '':
				$params = array_merge($this->getDefaultParams(), [
					'controller'	=> 'API',
					'action'			=> 'index'
				]);
				break;
			case 'modules':
				$params = array_merge($this->getDefaultParams(), [
					'controller'	=> 'API',
					'action'			=> 'modules'
				]);
				break;
			case 'config':
				$params = array_merge($this->getDefaultParams(), [
					'controller'	=> 'API',
					'action'			=> 'config'
				]);
				break;
		}
		if(Kansas_String::startWith($path, 'files')) {
			$params = array_merge($this->getDefaultParams(), [
				'controller'	=> 'API',
				'action'			=> 'files'
			]);
			if(strlen($path) > 5)
				$params['path'] = trim(substr($path, 6), './ ');
		}
		
		return $params;
	}

  public function getBasePath() {
    return $this->_basePath;
  }  
}