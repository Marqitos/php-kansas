<?php
require_once 'System/Configurable/Interface.php';

interface Kansas_Router_Interface
	extends System_Configurable_Interface {
	public function match();
	public function assemble($data = array(), $reset = false, $encode = false);
	public function getBasePath();
}

trait Router_Routers {
	private $_routers;
	protected function getRouters() {
		if($this->_routers == null)
			$this->_routers = new SplPriorityQueue();
		return $this->_routers;
	}
}