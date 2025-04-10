<?php declare(strict_types = 1);
/**
  * Representa un error de autenticación durante una solicitud a la API
  *
  * @package    Kansas
  * @author     Marcos Porto Mariño
  * @copyright  2025, Marcos Porto <lib-kansas@marcospor.to>
  * @since      v0.5
  */

namespace Kansas\API;

use Kansas\API\APIExceptionInterface;
use Kansas\API\APIException;

require_once 'Kansas/API/APIExceptionInterface.php';
require_once 'Kansas/API/APIException.php';

class APIUnauthorizedException extends APIException implements APIExceptionInterface {

    protected function getHTTPStatusCode() : int {
        return 401;
    }

    public function getAPIResult() : array {
        return [
            'status'    => $this->getHTTPStatusCode(),
            'success'   => false,
            'data'      => [],
            'error'     => $this->getCode(),
            'message'   => $this->getMessage()
        ];
    }

}
