<?php declare(strict_types = 1);
/**
  * Representa los errores producidos durante una solicitud a la api
  *
  * @package    Kansas
  * @author     Marcos Porto Mariño
  * @copyright  2025, Marcos Porto <lib-kansas@marcospor.to>
  * @since      v0.5
  */

namespace Kansas\API;

use Kansas\API\APIExceptionInterface;
use LogicException;
use System\AggregateException;

require_once 'Kansas/API/APIExceptionInterface.php';
require_once 'System/AggregateException.php';

/**
 * Al implementarlo, representa uno o varios errores producidos durante una solicitud a la API
 */
abstract class APIAggregateException extends AggregateException implements APIExceptionInterface {

    /**
      * Al implementarlo debe devolver el codigo HTTP
      *
      * @return integer Código HTTP
      */
    abstract protected function getHTTPStatusCode(): int;

    public static function aggregateError(string $message, int $code, APIAggregateException &$aggregateException = null): APIAggregateException {
        if($aggregateException == null) {
            $className = get_called_class();
            $aggregateException = new $className($message, $code);
        } else {
            $aggregateException->addError($message, $code);
        }
        return $aggregateException;
    }
    /**
     * Obtiene el resultado de la solicitud a la API
     *
     * @return array Datos que debe devolver la api
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
