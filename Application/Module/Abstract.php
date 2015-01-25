<?php

class Kansas_Application_Module_Abstract
	implements Kansas_Application_Module_Interface {
		
	protected $options;
	
	protected function __construct(Zend_Config $options) {
		$this->options			= $options;
	}
		
	public function setOptions(Zend_Config $options) {
		$this->options->merge($options);
	}
	
	public function getOptions() {
		return $this->options->toArray();
	}
	
	public function getVersion() {
		return Kansas_Version::getCurrent()->getVersion();
	}
		
}