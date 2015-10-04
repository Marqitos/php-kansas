<?php

class Kansas_View_Result_String
	extends Kansas_View_Result_String_Abstract {
		
	private $_text;
	
	public function __construct($text, $mimeType) {
    parent::__construct($mimeType);
		$this->_text = $text;
	}
	
	public function getResult(&$noCache) {
    $noCache = true;
		return $this->_text;
	}
}