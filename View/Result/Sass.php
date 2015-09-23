<?php

require_once('Phpsass/SassParser.php');

class Kansas_View_Result_Sass
	extends Kansas_View_Result_String_Abstract {
		
	private $_file;
	
	public function __construct($file) {
    parent::__construct('text/css; charset: UTF-8');
		$this->_file = $file;
	}
	
	public function getResult(&$noCache) {
		global $application;
    $noCache = true;
		return $application->getModule('Sass')->toCss($this->_file);
	}

}