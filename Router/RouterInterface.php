<?php

namespace Kansas\Router;

use System\Configurable\ConfigurableInterface;

require_once 'System/Configurable/ConfigurableInterface.php';

interface RouterInterface extends ConfigurableInterface {
	public function match();
	public function assemble($data = [], $reset = false, $encode = false);
	public function getBasePath();
}
/*
trait Router_Routers {
	private $_routers;
	protected function getRouters() {
		if($this->_routers == null)
			$this->_routers = new SplPriorityQueue();
		return $this->_routers;
	}
}*/