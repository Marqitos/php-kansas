<?php

class Kansas_View_Result_Content
	extends Kansas_View_Result_File_Abstract {

	private $_content;
	private $_offset;
	
	public function __construct($content) {
		$this->_content = $content;
	}
	
  public function executeResult() {
		if(extension_loaded('zlib'))
			ob_start('ob_gzhandler');
		parent::executeResult();
		header ("cache-control: must-revalidate");
		$offset = 60 * 60;
		$expire = "expires: " . gmdate ("D, d M Y H:i:s", time() + $offset) . " GMT";
		header ($expire);
		
		echo($this->_content);
		
		if(extension_loaded('zlib'))
			ob_end_flush();
			
		return true;
	}
		
}