<?php

namespace Kansas\View\Result;

/**
 * Representa una respuesta a una solicitud
 * @author Marcos
 *
 */
interface ViewResultInterface {
  /**
   * Ejecuta la respuesta de la solicitud
   */	
  public function executeResult();
}