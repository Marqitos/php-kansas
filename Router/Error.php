<?php

class Kansas_Router_Error
	implements Kansas_Router_Error_Interface {
		
	private $_params;
	
	public function __construct(array $params = array()) {
		$this->_params = $params;
	}
	
	public function getParams(Exception $e) {
		return array_merge($this->_params, array('error' => $e));
	}
	
}
