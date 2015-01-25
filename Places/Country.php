<?php

class Kansas_Places_Country
	extends Kansas_Places_Region_Abstract {
		
	private $_code;
		
	public function __construct($code, $name, $description = null) {
		parent::__construct($name, $description);
		$this->_code = $code;
	}
		
	/* Miembros de Kansas_Places_Region_Abstract */
	public function getKey() {
		return $this->_code;
	}
	public function getRegionType() {
		return 'country';
	}
	public function matchAddress(Kansas_Places_Address_Interface $address) {
		
		return false;
	}

	public static function getBuiltIn($code) {
		
	}
	
}