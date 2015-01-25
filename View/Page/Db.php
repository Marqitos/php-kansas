<?php

abstract class Kansas_View_Page_Db 
	extends Kansas_View_Page_Abstract {
		
	private $_id;
	private $_description;
	private $_keywords;
	private $_url;
	
	public function __construct(System_Guid $id, Kansas_View_Page_Interface $parent = null, Kansas_Router_Interface $router = null) {
		parent::__construct($parent, $router);
		$this->_id = $id;
	}
	
	public function getKeywords() {
		global $application;
		if(!is_array($this->_keywords))
			$this->_keywords = $application->getProvider('page')->getKeywords($this->_id);
		return implode(', ', $this->_keywords);
	}
	
}