<?php

class Kansas_View_Result_Json
	extends Kansas_View_Result_String_Abstract {
		
	private $_data;
	
	public function __construct($data) {
    parent::__construct('application/json; charset: UTF-8');    
		$this->_data = $data;
	}
		
	public function getResult(&$noCache) {
    $noCache = true;
		return json_encode($this->_data);
	}
      
}