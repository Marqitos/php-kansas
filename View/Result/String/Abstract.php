<?php

abstract class Kansas_View_Result_String_Abstract
  extends Kansas_View_Result_Abstract
	implements Kansas_View_Result_String_Interface {
		
	/* (non-PHPdoc)
   * @see Kansas_View_Result_Interface::executeResult()
	 */
	public function executeResult() {
    $noCache = false;
    $result = $this->getResult($noCache);
    parent::sendHeaders($noCache);
    echo $result;
		return true;
	}
	
	/* (non-PHPdoc)
   * @see Kansas_View_Result_String_Interface::getResult()
	 */
	public abstract function getResult(&$noCache);
		
}