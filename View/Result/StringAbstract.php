<?php
/**
 * Proporciona la funcionalidad básica para la devolución de texto, como resultado de una solicitud
 *
 * @package Kansas
 * @author Marcos Porto
 * @copyright Marcos Porto
 * @since v0.4
 */

namespace Kansas\View\Result;

use Kansas\View\Result\ViewResultAbstract;
use Kansas\View\Result\StringInterface;

abstract class StringAbstract extends ViewResultAbstract implements StringInterface {
		
	/* (non-PHPdoc)
     * @see Kansas_View_Result_Interface::executeResult()
	 */
	public function executeResult() {
		$cache = null;
		$result = $this->getResult($cache); // TODO: Optimizar para no generar el contenido si no es necesario
		if(parent::sendHeaders($cache)) {
			echo $result;
		}
		return true;
	}
	
	/* (non-PHPdoc)
     * @see Kansas_View_Result_String_Interface::getResult()
	 */
	public abstract function getResult(&$cache);
		
}