<?php

class Kansas_View_Result_String
	extends Kansas_View_Result_String_Abstract {
		
	private $_text;
	
	public function __construct($text) {
		$this->_text = $text;
	}
	
	public function getResult() {
		return $this->_text;
	}
}