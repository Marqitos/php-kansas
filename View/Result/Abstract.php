<?php

abstract class Kansas_View_Result_Abstract
  implements Kansas_View_Result_Interface {
		
	protected $_mimeType;
	
  protected function __construct($mimeType) {
    $this->_mimeType = $mimeType;
  }
  
  // Obtiene o establece el tipo de contenido de archivo	
	public function getMimeType() {
		return $this->_mimeType;
	}
	public function setMimeType($value) {
		$this->_mimeType = $value;
	}
  
	protected function sendHeaders($cache = false) {
 		header('Content-Type: ' . $this->getMimeType());
    if($cache) {
      header ("cache-control: must-revalidate");
      if(is_int($cache))
        header ("expires: " . gmdate ("D, d M Y H:i:s", time() + $cache) . " GMT");        
      if(is_string($cache)) {
        if(isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim($_SERVER['HTTP_IF_NONE_MATCH']) == $cache) {
          header("HTTP/1.1 304 Not Modified");
          return false;
        } else
          header('Etag: ' . $cache);
      }
    } else
      header ("cache-control: no-store");
    return true;
	}

}
