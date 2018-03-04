<?php
require_once 'Kansas/View/Result/String/Abstract.php';

class Kansas_View_Result_Javascript
	extends Kansas_View_Result_String_Abstract {
		
	private $_components;
	
	public function __construct($components) {
    parent::__construct('application/javascript; charset: UTF-8');
		$this->_components	= $components;
	}
	
	public function getResult(&$cache) {
		global $application;
		return $application->getModule('Javascript')->build($this->_components, $cache);
	}

}