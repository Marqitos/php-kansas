<?php

class Kansas_User_Db 
	extends Kansas_User_Abstract {
	
	private $_roles;
		
	public function getRoles() {
		global $application;
		if($this->_roles == null)
			$this->_roles = $application->getProvider('users')->getRoles($this->getId());
		return $this->_roles;
	}
		
}