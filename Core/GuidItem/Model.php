<?php

class Kansas_Core_GuidItem_Model
	extends Kansas_Core_Model
	implements Kansas_Core_GuidItem_Interface {
	
	private $_id;
	
	public function __construct($row) {
		parent::__construct($row);
	}
	
	public function getId() {
		if($this->_id == null && isset($this->row['id']))
			$this->_id = new System_Guid($this->row['id']);
		
		return $this->_id == null ?
			System_Guid::getEmpty():
			$this->_id;
	}
	
	public function getHex() {
		return $this->row['id'];
	}
	
	protected function setId(System_Guid $id = null) {
		$this->_id = $id;
		$this->row['id'] = $id == null?
			null:
			$id->getHex();
	}
	
	public function createId() {
		if(empty($this->row['id']))
			$this->setId(System_Guid::NewGuid());
		return new System_Guid($this->row['id']);
	}
	
	public function hasId() {
		return isset($this->row['id']);
	}
		
}