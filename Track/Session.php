<?php

class Kansas_Track_Session
	extends Kansas_Model_GuidItem {
		
	private $_device;
	private $_user;
	
	public function __construct() {
		$this->_device = new Kansas_Track_Device();
		$this->row['sessionId'] = Zend_Session::getId();
		$auth = Zend_Auth::getInstance();
		if($auth->hasIdentity())
			$this->_user = $auth->getIdentity();
	}
	
	public function getDevice() {
		return $this->_device;
	}
	
	public function save() {
		$this->createId();
		$this->_device->createId();
		$this->__sleep();
		Kansas_Application::getInstance()->getProvider('session')->saveSession($this->row);
		$this->_device->save();
	}
	
	function __sleep() {
		$this->row['device'] = $this->_device->getId()->getHex();
		$this->row['user'] = $this->_user == null?
			null:
			$this->user->getId()->getHex();
	}
		
		
		
		
}