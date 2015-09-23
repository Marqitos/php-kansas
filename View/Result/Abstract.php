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
  
	protected function sendHeaders($noCache = false) {
 		header('Content-Type: ' . $this->getMimeType());
    if($noCache) {
      header ("cache-control: must-revalidate");
      header ("expires: " . gmdate ("D, d M Y H:i:s", time() + 60 * 60) . " GMT");
    }
	}

}
