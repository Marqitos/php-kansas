<?php
/**
  * Representa una respuesta a una solicitud
  *
  * @package    Kansas
  * @author     Marcos Porto Mariño
  * @copyright  2025, Marcos Porto <lib-kansas@marcospor.to>
  * @since      v0.4
  */

namespace Kansas\View\Result;

/**
  * Representa una respuesta a una solicitud
  *
  */
interface ViewResultInterface {
  /**
    * Ejecuta la respuesta de la solicitud
    */
  public function executeResult();
}
