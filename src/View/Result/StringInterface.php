<?php declare(strict_types = 1);
/**
  * Representa una respuesta a una solicitud, que devuelve texto
  *
  * @package    Kansas
  * @author     Marcos Porto Mariño
  * @copyright  2025, Marcos Porto <lib-kansas@marcospor.to>
  * @since      v0.4
  */

namespace Kansas\View\Result;

use Kansas\View\Result\ViewResultInterface;
/**
  * Representa una respuesta a una solicitud, que devuelve texto
  *
  */
interface StringInterface extends ViewResultInterface {
  /**
    * Devuelve el texto a enviar
    */
  public function getResult(&$noCache);
}
