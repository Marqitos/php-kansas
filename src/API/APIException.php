<?php declare(strict_types = 1);
/**
  * Representa un error producido durante una solicitud a la api
  *
  * @package    Kansas
  * @author     Marcos Porto Mariño
  * @copyright  2025, Marcos Porto <lib-kansas@marcospor.to>
  * @since      v0.5
  */

namespace Kansas\API;

use LogicException;
use Kansas\API\APIExceptionInterface;

require_once 'Kansas/API/APIExceptionInterface.php';

class APIException extends LogicException implements APIExceptionInterface {

    public function __construct(string $message = '', int $code = 0) {
        parent::__construct($message, $code);
    }

    /**
      * Obtiene el código HTTP
      *
      * @return integer Código HTTP
      */
    protected function getHTTPStatusCode() : int {
        return 500;
    }

    # Miembros de APIExceptionInterface
    /**
      * @inheritDoc
      */
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
