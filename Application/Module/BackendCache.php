<?php

class Kansas_Application_Module_BackendCache
	extends Kansas_Application_Module_Abstract {
		
	private $_cache;

	public function __construct(Zend_Config $options) {
		parent::__construct($options);
	}
	
	public function getCache() {
		if($this->_cache == null)
			$this->_cache = new Zend_Cache_Backend_File($this->options->toArray());
		return $this->_cache;
	}
	
	public function clearCache() {
		$this->getCache()->clean();
	}
		
}
		
	