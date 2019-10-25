<?php

namespace Kansas\View\Result;

use Kansas\View\Result\ViewResultAbstract;
use Kansas\View\Result\StringInterface;

abstract class StringAbstract extends ViewResultAbstract implements StringInterface {
		
	/* (non-PHPdoc)
   * @see Kansas_View_Result_Interface::executeResult()
	 */
	public function executeResult() {
		$cache = false;
		$result = $this->getResult($cache);
		if(parent::sendHeaders($cache))
			echo $result;
		return true;
	}
	
	/* (non-PHPdoc)
   * @see Kansas_View_Result_String_Interface::getResult()
	 */
	public abstract function getResult(&$cache);
		
}