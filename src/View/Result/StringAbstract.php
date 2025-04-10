<?php declare(strict_types = 1);
/**
  * Proporciona la funcionalidad básica para la devolución de texto, como resultado de una solicitud
  *
  * @package    Kansas
  * @author     Marcos Porto Mariño
  * @copyright  2025, Marcos Porto <lib-kansas@marcospor.to>
  * @since      v0.4
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
    $result = $this->getResult($cache);
    if(parent::sendHeaders($cache)) {
      echo $result;
    }
    return true;
  }

  /** (non-PHPdoc)
    * @see Kansas_View_Result_String_Interface::getResult()
    */
  abstract public function getResult(&$cache);

}
