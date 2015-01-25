<?php

class Kansas_Version {
	
	private static $_instance;
	private $_version;
	
	private function __construct() {
		$this->_version = new System_Version('0.2');
	}
	
	public static function getCurrent() {
		if(self::$_instance == null)
			self::$_instance = new self();
		return self::$_instance;
	}
	
	public function getVersion() {
		return $this->_version;
	}
	
}