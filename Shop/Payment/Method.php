<?php

class Kansas_Shop_Payment_Method
	extends Kansas_Core_GuidItem_Model
	implements Kansas_Shop_Payment_Method_Interface {
	
	protected function init() {
		if(!isset($this->row['Id']))
			$this->row['Id'] = new System_Guid(
				hex2bin(md5($this->row['Name'])) ^
				hex2bin(md5($this->row['Type']))
			);
		parent::init();
	}
	
	public function getType() {
		return $this->row['Type'];
	}
	
	public function getName() {
		return $this->row['Name'];
	}
	
	public function getExtraCost() {
		return isset($this->row['ExtraCost'])?
			floatval($this->row['ExtraCost']):
			0;
	}
	
	public function getHtmlExtraCost() {
		return number_format($this->getExtraCost(), 2) . ' &euro;';
	}
		
}