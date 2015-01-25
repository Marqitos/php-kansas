<?php

abstract class Kansas_Shop_Payment_Method_Abstract
	implements Kansas_Shop_Payment_Method_Interface {
	
	private $_id;
	
	public function getId() {
		if($this->_id == null)
			$this->_id = new System_Guid(
				hex2bin(md5($this->getName())) ^
				hex2bin(md5($this->getType()))
			);
		return $this->_id;
	}

}