<?php
require_once('Kansas/View/Page/Abstract.php');
require_once('Kansas/View/Page/Interface.php');
require_once('Kansas/Router/Interface.php');

class Kansas_View_Page_Static
	extends Kansas_View_Page_Abstract {
		
	private $_description;
	private $_keywords;
	private $_url;
	
	public function __construct(
		$description = null,
		$keywords = [],
		$url = null,
		Kansas_View_Page_Interface $parent = null,
		Kansas_Router_Interface $router = null) {
		parent::__construct($parent, $router);
		$this->_description = $description;
		$this->_keywords		= (array) $keywords;
		$this->_url					= $url;
	}
		
	public function getTitle() {
		global $application;
		return (string)$application->getTitle();
	}
	public function hasDescription() {
		return !empty($this->_description);
	}
	public function getDescription() {
		return $this->_description;
	}
	public function setDescription($description) {
		$this->_description = $description;
	}
	
	public function getKeywords() {
		return implode(', ', $this->_keywords);
	}

	public function getUrl() {
		return $this->_url;
	}
		
}