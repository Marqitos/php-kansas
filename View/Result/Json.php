<?php

class Kansas_View_Result_Json
	implements Kansas_View_Result_Interface {
		
	private $_data;
	
	public function __construct($data) {
		$this->_data			= $data;
	}
		
	/* (non-PHPdoc)
   * @see Kansas_View_Result_Interface::executeResult()
	 */
	public function executeResult() {
		echo(json_encode($this->_data));
		return true;
	}
	
		
}