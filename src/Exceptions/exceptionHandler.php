<?php declare(strict_types = 1);
/**
 * Proporciona un manejador de errores compatible con set_exception_handler
 *
 * @package Kansas
 * @author Marcos Porto
 * @copyright Marcos Porto
 * @since v0.4
 * PHP 7
 */

namespace Kansas\Exceptions;

use Throwable;
use function Kansas\Exceptions\getErrorData as GetErrorData;

function exceptionHandler(Throwable $ex) : void {
    global $application;
    require_once 'Kansas/Exceptions/getErrorData.php';
    $message = GetErrorData($ex);
    $application->raiseMessage($message);
    exit($ex->getCode());
}
