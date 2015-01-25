<?php

class Kansas_Router_Default
	extends Kansas_Router_Abstract {
	use Router_PartialPath;
		
	private $_routers;
	private $_pages				= [];
	private $_defaultPage = false;
	
	public function __construct(Zend_Config $options = null) {
		if($options == null)
			$options = new Zend_Config([], true);
		parent::__construct($options);
		$this->_routers = new SplPriorityQueue();
	}

	public function match(Zend_Controller_Request_Abstract $request) {
		$path 	= $this->getPartialPath($this, $request);
		$params = false;
		
		if($path == '')
			$params = array_merge($this->getDefaultParams(), $this->_defaultPage);
		elseif(isset($this->_pages[$path]))
			$params = array_merge($this->getDefaultParams(), $this->_pages[$path]);
			
		if($params)
			$params['router']	= $this;
		else
			foreach($this->getRouters() as $router)
				if($params = $router->match($request)) break;
		
		return $params;
	}
	
	public function getRouters() {
		return $this->_routers;
	}
	
	public function addRouter($router, $priority = 0) {
		$this->_routers->insert($router, $priority);
	}
	
	public function setRoute($page, $params) {
		if(empty($page))
			$this->_defaultPage = $params;
		else
			$this->_pages[$page] = $params;
	}
	
}