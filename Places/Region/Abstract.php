<?php

abstract class Kansas_Places_Region_Abstract
	implements Kansas_Places_Region_Interface {
		
	private $_name;
	private $_description;
	
	protected function __construct($name, $description = null) {
		$this->_name = $name;
		$this->_description = $description;
	}
	
	/* Miembros de Kansas_PKansas_Places_Region_Interface */
	public function getName() {
		return $this->_name;
	}
	public function getDescription() {
		return $this->_description;
	}
		
	/* Miembros de Kansas_Core_GuidItem_Interface */
	public function getId() {
		return new System_Guid(md5($this->getKey()));
	}
}