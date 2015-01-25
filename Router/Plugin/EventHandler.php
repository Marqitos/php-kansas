<?php

class Kansas_Router_Plugin_EventHandler {
	
	private $_collection;
	
	public function __construct() {
		$this->_collection = array();
	}
	
	public function add(Kansas_Router_Plugin_Interface $plugin) {
		$this->_collection[] = $plugin;
	}
	
	public function invoke(Zend_Controller_Request_Abstract $request, &$params) {
		foreach($this->_collection as $plugin) {
			$params = array_merge(
				$params,
				$plugin->match($request)
			);
			if($plugin->getCancel())
				return true;
		}
		return false;
	}
	
	public function beforeRoute(Zend_Controller_Request_Abstract $request, $params, &$basepath, &$cancel) {
		foreach($this->_collection as $plugin) {
			$params = $plugin->beforeRoute($request, $params, $basepath, $cancel);
			if($cancel)
				break;
		}
		return $params;
	}
	public function afterRoute(Zend_Controller_Request_Abstract $request, $params) {
		foreach($this->_collection as $plugin)
			$params = $plugin->afterRoute($request, $params);
		return $params;
	}
	
		
}

