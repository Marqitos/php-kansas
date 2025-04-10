<?php declare(strict_types = 1);
/**
  * Obtiene los datos a partir de una Exception o Error (Throwable)
  *
  * @package    Kansas
  * @author     Marcos Porto Mariño
  * @copyright  2025, Marcos Porto <lib-kansas@marcospor.to>
  * @since      v0.4
  */

namespace Kansas\Exceptions;

use Throwable;
use System\Net\WebException;
use function get_class;
use const E_USER_ERROR;

function getErrorData(Throwable $ex) : array {
    require_once 'System/Net/WebException.php';
    return [
        'exception'     => get_class($ex),
        'errorLevel'    => E_USER_ERROR,
        'errorCode'     => $ex->getCode(),
        'code'          => $ex instanceof WebException
                        ? $ex->getStatus()
                        : 500,
        'message'       => $ex->getMessage(),
        'trace'         => $ex->getTrace(),
        'line'          => $ex->getLine(),
        'file'          => $ex->getFile()
    ];
}
