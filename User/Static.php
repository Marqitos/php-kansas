<?php

class Kansas_User_Static
	extends Kansas_User_Abstract {

	private $_roles;
	
	public function __construct($row) {
		$this->_roles = (array)$row['roles'];
	}
	
	public function getRoles() {
		return $this->_roles;
	}
		
}