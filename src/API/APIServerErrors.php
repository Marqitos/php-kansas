<?php declare(strict_types = 1);
/**
  * Define los posibles errores internos del servidor
  *
  * @package    Kansas
  * @author     Marcos Porto Mariño
  * @copyright  2025, Marcos Porto <lib-kansas@marcospor.to>
  * @since      v0.6
  */

namespace Kansas\API;

use Kansas\API\APITokenErrors;

require_once 'Kansas/API/APITokenErrors.php';

/**
  * Lista de errores de la API a causa del servidor
  */
class APIServerErrors extends APITokenErrors {
    const E_SERVER_UNHANDLER= 0x0014; // Error no controlado en el servidor.
    const E_SERVER_DDBB     = 0x0018; // Error relacionado con la base de datos.
    const E_SERVER_MODULE   = 0x001C; // Error producido por un componente.
}
