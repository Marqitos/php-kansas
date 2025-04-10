<?php declare(strict_types = 1);
/**
  * Obtiene los datos de entrada como JSON y los devuelve como un array
  *
  * @package    Kansas
  * @author     Marcos Porto Mariño
  * @copyright  2025, Marcos Porto <lib-kansas@marcospor.to>
  * @since      v0.6
  */

 namespace Kansas\API;

use Kansas\API\APIRequestException;
use Kansas\API\APIJsonErrors;
use Kansas\Localization\Resources;
use System\Text\JsonException;
use function file_get_contents;
use function json_decode;
use function json_last_error_msg;
use const JSON_ERROR_NONE;

/**
 * Obtiene los datos de la peteción como JSON y los devuelve como un array
 *
 * @return array Datos de la peteción
 * @throws Kansas\API\APIRequestException Si no se han enviado datos, o los datos no tienen formato JSON
 */
function getJsonInput() {
    $json       = false;
    $data       = file_get_contents('php://input');

    if ($data == '') {
        $data   = null;
    }

    if ($data !== null) {
        // Decodifica un string de JSON en un arroy asociativo
        $data   = json_decode($data, true);
        $json   = true;

        if (json_last_error() != JSON_ERROR_NONE) { // Ha ocurrido un error durante la decodificación
            require_once 'Kansas/API/APIRequestException.php';
            require_once 'Kansas/APIJsonErrors.php';
            throw new APIRequestException("Error al descodificar datos JSON: " . json_last_error_msg(), APIJsonErrors::E_DATA_NO_JSON);
        }
    }
    if ($data === null) {
        require_once 'Kansas/API/APIRequestException.php';
        require_once 'Kansas/API/APIJsonErrors.php';
        $errorCode  = $json
            ? APIJsonErrors::E_DATA_NO_JSON
            : APIJsonErrors::E_NO_DATA;
        throw new APIRequestException(Resources::E_MESSAGES[$errorCode], $errorCode);
    }

    return $data;
}
