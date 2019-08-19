<?php

namespace Kansas\View\Result;

use Kansas\View\Result\ViewResultInterface;
/**
 * Representa una respuesta a una solicitud, que devuelve texto
 * @author Marcos
 *
 */
interface StringInterface extends ViewResultInterface {
  /**
   * Devuelve el texto a enviar
   */	
  public function getResult(&$noCache);
}