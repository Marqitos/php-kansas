<?php

class Kansas_View_Result_Javascript
	extends Kansas_View_Result_String_Abstract {
		
	private $_components;
	
	public function __construct($components) {
    parent::__construct('application/javascript; charset: UTF-8');
		$this->_components	= $components;
	}
	
	public function getResult(&$noCache) {
		global $application;
    $noCache = true;
		return $application->getModule('Javascript')->build($this->_components);
	}

}