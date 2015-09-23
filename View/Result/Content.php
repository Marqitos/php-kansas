<?php

class Kansas_View_Result_Content
	extends Kansas_View_Result_Abstract {

	private $_content;
	
	public function __construct($content, $mimeType) {
    parent::__construct($mimeType);
		$this->_content = $content;
	}
	
  public function executeResult() {
  	parent::sendHeaders(true);
		if(extension_loaded('zlib'))
			ob_start('ob_gzhandler');

		echo($this->_content);
		
		if(extension_loaded('zlib'))
			ob_end_flush();
			
		return true;
	}
		
}