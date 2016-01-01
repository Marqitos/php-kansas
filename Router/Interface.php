<?php

interface Kansas_Router_Interface {
	public function match();
  public function assemble($data = array(), $reset = false, $encode = false);

	public function getBasePath();
	public function setOptions(array $options);	
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
	
	protected function matchRoute($path) {
		if($path == '')
			$params = array_merge($this->getDefaultParams(), $this->_pages['.']);
		elseif(isset($this->_pages[$path]))
			$params = array_merge($this->getDefaultParams(), $this->_pages[$path]);
			
		if($params)
			$params['router']	= $this;
		
		return $params;
	}
	
	public function setRoute($page, $params) {
		$this->_pages[$page] = $params;
	}
}