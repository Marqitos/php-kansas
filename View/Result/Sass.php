<?php

require_once('Phpsass/SassParser.php');

class Kansas_View_Result_Sass
	extends Kansas_View_Result_File_Abstract {
		
	private $_file;
	
	public function __construct($file) {
		$this->_file		= $file;
	}
	
	public function getMimeType() {
		return 'text/css';
	}
	
  public function executeResult() {
		parent::executeResult();
		header ("content-type: text/css; charset: UTF-8");
		header ("cache-control: must-revalidate");
		$offset = 60 * 60;
		$expire = "expires: " . gmdate ("D, d M Y H:i:s", time() + $offset) . " GMT";
		header ($expire);
		
		echo $this->getResult();
	}
	
	public function getResult() {
		global $application;
		return $application->getModule('Sass')->toCss($this->_file);
	}

}