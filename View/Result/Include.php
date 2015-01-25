<?php 

class Kansas_View_Result_Include
  implements Kansas_View_Result_Interface {
    
  private $_filename;
  
  public function getFileName() {
    return $this->_filename;
  }
  

	public function __construct($filename) {
		$this->_filename	= $filename;
	}
    
  /* (non-PHPdoc)
   * @see Kansas_View_Result_Interface::executeResult()
   */
  public function executeResult () {
		include($this->_filename);
  }
    
}