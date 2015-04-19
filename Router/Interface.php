<?php

interface Kansas_Router_Interface {
	public function match(Kansas_Request $request);
  public function assemble($data = array(), $reset = false, $encode = false);

	public function getBasePath();
	public function setBasePath($basePath);
	public function setOptions(Zend_Config $options);	
}

trait Router_PartialPath {
	protected function getPartialPath(Kansas_Router_Interface $route, Kansas_Request $request) {
		$path = trim($request->getUri()->getPath(), '/');
		$basePath = $route->getBasePath();
		if(Kansas_String::startWith($path, $basePath))
			return trim(substr($path, strlen($basePath)), '/');
		return false;
	}
}

trait Router_Routers {
	private $_routers;
	protected function getRouters() {
		if($this->_routers == null)
			$this->_routers = new SplPriorityQueue();
		return $this->_routers;
	}
}

trait Router_Route {
	private $_pages = [];
	private $_defaultPage = false;
	
	protected function matchRoute($path) {
		if($path == '')
			$params = array_merge($this->getDefaultParams(), $this->_defaultPage);
		elseif(isset($this->_pages[$path]))
			$params = array_merge($this->getDefaultParams(), $this->_pages[$path]);
			
		if($params)
			$params['router']	= $this;
		
		return $params;
	}
	
	public function setRoute($page, $params) {
		if(empty($page))
			$this->_defaultPage = $params;
		else
			$this->_pages[$page] = $params;
	}
}