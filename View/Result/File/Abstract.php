<?php 

abstract class Kansas_View_Result_File_Abstract
  implements Kansas_View_Result_Interface {
		
	private $_mimeType;
	
	protected function hasMimeType() {
		return !empty($this->_mimeType);
	}
	
	public function getMimeType() {
		return $this->_mimeType;
	}
	
	public function setMimeType($value) {
		$this->_mimeType = $value;
	}
	
	public function executeResult() {
 		header('Content-Type: ' . $this->getMimeType());
	}

}