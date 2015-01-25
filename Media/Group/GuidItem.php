<?php

abstract class Kansas_Media_Group_GuidItem
	extends Kansas_Media_Group_Abstract
	implements Kansas_Core_GuidItem_Interface {

	private $_id;
	
	protected function init() {
		$this->_id	= new System_Guid($this->row['Id']);
	}
		
	public function getId() {
		return $this->_id;
	}
	
}