<?php

class Kansas_Router_API
	extends Kansas_Router_Abstract {
	use Router_PartialPath;

	private $_routers;
	
	public function __construct(Zend_Config $options) {
		parent::__construct($options);
		$this->_routers = [];
	}
	
	public function match() {
		global $application;
    global $environment;
		$params = false;
		$path = trim($environment->getRequest()->getUri()->getPath(), '/');

  	if($path === false)
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
		
		foreach($application->getModules() as $name => $module) {
			if($name != 'api' && Kansas_String::startWith($path, strtolower($name))) {
				$module = $application->getModule($name);
				if($params = $module->ApiMatch($request))
					break;
			}
		}
			
		
		if($params)
			$params['router']	= $this;
		return $params;
	}
	
}
