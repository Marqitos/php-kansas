<?php declare(strict_types = 1);
/**
  * Representa un error producido durante una solicitud a la API
  *
  * @package    Kansas
  * @author     Marcos Porto Mariño
  * @copyright  2025, Marcos Porto <lib-kansas@marcospor.to>
  * @since      v0.5
  */

namespace Kansas\API;

/**
  * Representa un error producido durante una solicitud a la API
  */
interface APIExceptionInterface {

    /**
      * Devuelve el resultado de la solicitud a la API
      *
      * @return array Datos que debe devolver la API
      */
    public function getAPIResult() : array;

}
