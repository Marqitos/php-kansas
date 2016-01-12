<?php

class Kansas_Module_API
  extends Kansas_Module_Zone_Abstract
  implements Kansas_Router_Interface {
	
  /// Constructor
	public function __construct(array $options) {
    parent::__construct($options, __FILE__);
    $application->registerPreInitCallbacks([$this, "appPreInit"]);
	}
 
  /// Miembros de Kansas_Module_Interface
  public function getVersion() {
		global $environment;
		return $environment->getVersion();
	}
  
  /// Miembros de Kansas_Router_Interface
	public function match() {
		global $application;
    global $environment;
		$params = false;
		$path = trim($environment->getRequest()->getUri()->getPath(), '/');
    if(Kansas_String::startWith($path, $this->getBasePath()))
      $path = substr($path, strlen($this->getBasePath()));
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

  
	public function appPreInit() { // añadir router
		global $application;
    if($this->zones->getZone() instanceof Kansas_Module_API) {
      $application->addRouter($this);
    }
	}

}