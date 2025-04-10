<?php declare(strict_types = 1);
/**
  * Proporciona un manejador de errores compatible con set_error_handler
  *
  * @package    Kansas
  * @author     Marcos Porto Mariño
  * @copyright  2025, Marcos Porto <lib-kansas@marcospor.to>
  * @since      v0.4
  */

namespace Kansas\Exceptions;

use function array_shift;
use function debug_backtrace;

function errorHandler(int $errno, string $errstr, string $errfile, int $errline, array $errcontext) : bool {
    global $application;
    $trace = debug_backtrace();
    array_shift($trace);

    $errData = [
        'exception'     => null,
        'errorLevel'    => $errno,
        'code'          => 500,
        'message'       => $errstr,
        'trace'         => $trace,
        'line'          => $errline,
        'file'          => $errfile,
        'context'       => $errcontext
    ];
    $application->raiseMessage($errData);
    return true; // No ejecutar el gestor de errores interno de PHP
}
