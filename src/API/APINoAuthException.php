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
use System\Security\SecurityException;

require_once 'Kansas/API/APIExceptionInterface.php';
require_once 'System/Security/SecurityException.php';

class APINoAuthException extends SecurityException implements APIExceptionInterface {

    public function __construct(SecurityException $previous) {
        parent::__construct($previous->getMessage(), $previous->getCode(), $previous);
    }

    protected function getHTTPStatusCode() : int {
        return 403;
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
