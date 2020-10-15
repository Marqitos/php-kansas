<?php

class Kansas_Track_Session
	extends Kansas_Model_GuidItem {
		
	private $_device;
	private $_user;
	
	public function __construct() {
		global $application;
		$this->_device = new Kansas_Track_Device();
		$this->row['sessionId'] = Zend_Session::getId();
		$auth = $application->getPlugin('Auth');
		if($auth->hasIdentity())
			$this->_user = $auth->getIdentity();
	}
	
	public function getDevice() {
		return $this->_device;
	}
	
	public function save() {
		global $application;
		$this->createId();
		$this->_device->createId();
		$this->__sleep();
		$application->getProvider('session')->saveSession($this->row);
		$this->_device->save();
	}
	
	function __sleep() {
		$this->row['device'] = $this->_device->getId()->getHex();
		$this->row['user'] = $this->_user == null?
			null:
			$this->user->getId()->getHex();
	}
		
		
		
		
}