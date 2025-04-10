<?php declare(strict_types = 1);
/**
  * Define los posibles errores sobre un token (JWT) de usuario
  *
  * @package    Kansas
  * @author     Marcos Porto Mariño
  * @copyright  2025, Marcos Porto <lib-kansas@marcospor.to>
  * @since      v0.6
  */

namespace Kansas\API;

use Kansas\API\APIJsonErrors;

require_once 'Kansas/API/APIJsonErrors.php';

/**
  * Lista de errores de la API relacionados con los permisos del usuario
  */
class APITokenErrors extends APIJsonErrors {
    const E_NO_TOKEN        = 0x0004; // No se ha recibido el token
    const E_TOKEN_ERROR     = 0x0008; // El token no es válido
    const E_TOKEN_IS_EXPIRED= 0x000C; // El token está expirado
    const E_NO_PERMISSIONS  = 0x0010; // No tiene permisos para realizar la acción
    const E_TOKEN_RENEW     = 0x0020; // Debe renovar el token
    const FILTER_TOKEN      = 0x003C; // Filtro con todos los valores posibles
}
