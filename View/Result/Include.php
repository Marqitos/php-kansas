<?php 

class Kansas_View_Result_Include
  extends Kansas_View_Result_Abstract {
    
  private $_filename;
  
  public function getFileName() {
    return $this->_filename;
  }
  

	public function __construct($filename, $mimeType) {
    parent::__construct($mimeType);
		$this->_filename	= $filename;
	}
    
  /* (non-PHPdoc)
   * @see Kansas_View_Result_Interface::executeResult()
   */
  public function executeResult () {
  	parent::sendHeaders();
		include($this->_filename);
  }
    
}