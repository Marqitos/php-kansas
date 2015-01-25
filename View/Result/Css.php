<?php

class Kansas_View_Result_Css
	extends Kansas_View_Result_File_Abstract {
		
	private $_files;
	
	public function __construct($files) {
		$this->_files = (array)$files;
	}
	
	public function compress($buffer) {
	   // remove comments 
		$buffer = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $buffer);
		return $buffer;
	}
	
	public function getCacheId() {
		$hash = System_Guid::getEmpty()->getRaw();
		foreach($this->_files as $file) {
			$value = md5($file);
			$raw 		= '';
			for($c = 0; $c < 32; $c += 2)
				$raw .= chr(hexdec(substr($value, $c, 2))); 
			$hash ^= $raw;
		}
		
		return urlencode(
			'css|'.
			bin2hex($hash)
		);
	}
	
	public function getMimeType() {
		return 'text/css';
	}
	
  public function executeResult() {
		if(extension_loaded('zlib'))
			ob_start('ob_gzhandler');
		parent::executeResult();
//		header ("content-type: text/css; charset: UTF-8");
		header ("cache-control: must-revalidate");
		$offset = 60 * 60;
		$expire = "expires: " . gmdate ("D, d M Y H:i:s", time() + $offset) . " GMT";
		header ($expire);
		
		ob_start(array($this, "compress"));
		foreach($this->_files as $file)
			include($file);
		
		if(extension_loaded('zlib'))
			ob_end_flush();

	}
	
	public function getResult() {
		$result = '';
		foreach($this->_files as $file)
			$result .= $this->compress(file_get_contents($file));
		return $result;
	}

}