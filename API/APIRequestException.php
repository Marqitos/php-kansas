<?php declare(strict_types = 1);
/**
 * Representa los errores producidos durante una solicitud a la api
 *
 * @package Kansas
 * @author Marcos Porto
 * @copyright 2022, Marcos Porto
 * @since v0.5
 */

namespace Kansas\API;

use Throwable;
use Kansas\API\APIAggregateException;
use Kansas\API\APIException;

require_once 'Kansas/API/APIAggregateException.php';

/**
 * Representa uno o varios errores producidos durante una solicitud a la API
 */
class APIRequestException extends APIAggregateException implements APIExceptionInterface {

    /**
     * Crea una instancia de APIRequestException, con un mensaje de error.
     *
     * @param string $message Mensaje de error
     * @param integer $code Código de error (FLAG)
     * @param Throwable|null $previous Excepción previa
     */
    public function __construct(string $message, int $code, Throwable $previous = null) {
        if($previous == null) {
            require_once 'Kansas/API/APIException.php';
            $previous = new APIException($message, $code);
        }
        parent::__construct($previous);
    }

    // Miembros de APIAggregateException
    /**
     * Obtiene el código HTTP
     *
     * @return integer Código HTTP
     */
    protected function getHTTPStatusCode() : int {
        return 412;
    }

    // Miembros de APIExceptionInterface
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