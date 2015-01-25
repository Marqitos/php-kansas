<?php

class Kansas_Places_Region
	extends Kansas_Places_Region_Abstract {
		
	private $_key;
	
	public function __construct($key, $name, $description = null) {
		parent::__construct($name, $description);
		$this->_key = $key;
	}
		
	/* Miembros de Kansas_Places_Region_Abstract */
	public function getKey() {
		return $this->_key;
	}
	public function getRegionType() {
		return 'other';
	}
	public function matchAddress(Kansas_Places_Address_Interface $address) {
		
		return false;
	}
}