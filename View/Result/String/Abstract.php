<?php

abstract class Kansas_View_Result_String_Abstract
	implements Kansas_View_Result_String_Interface {
		
	/* (non-PHPdoc)
   * @see Kansas_View_Result_Interface::executeResult()
	 */
	public function executeResult() {
		echo $this->getResult();
		return true;
	}
	
	/* (non-PHPdoc)
   * @see Kansas_View_Result_String_Interface::getResult()
	 */
	public abstract function getResult();
		
}