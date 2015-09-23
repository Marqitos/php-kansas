<?php

class Kansas_View_Result_Css
	extends Kansas_View_Result_String_Abstract {
		
	private $_files;
	
	public function __construct($files) {
		$this->_files = (array)$files;
    parent::__construct('text/css; charset: UTF-8');    
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
	
  public function getResult(&$noCache) {
    $noCache = true;
    $result = '';
		foreach($this->_files as $file)
			$result .= file_get_contents($file);
    
    global $environment;
    if($environment->getStatus() == Kansas_Environment::PRODUCTION)
      return preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $result);
    
    return $result;
  }
  
}