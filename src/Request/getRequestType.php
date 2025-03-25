<?php declare(strict_types = 1);
/**
 * Devuelve el tipo de petición de datos, indicando quien inicia la solicitud
 * 
 * @package Kansas
 * @author Marcos Porto
 * @copyright Marcos Porto
 * @since v0.4
 * PHP 7 >= 7.2
 */

namespace Kansas\Request;

use Psr\Http\Message\RequestInterface;

require_once 'Psr/Http/Message/RequestInterface.php';

function getRequestType(RequestInterface $request) : string {
    if($request->hasHeader('X_REQUESTED_WITH')) { 
        // Devuelve XMLHttpRequest en las peticiones mediante Javascript XMLHttpRequest
        // Funciona con Prototype/Script.aculo.us, y posiblemente otros.
        $requestedWith = $request->getHeader('X_REQUESTED_WITH');
        if(isset($requestedWith['XMLHttpRequest'])) {
            return 'XMLHttpRequest';
        }
    } 
    if($request->hasHeader('user-agent')) {
        // Devuelve flash en las peticiones mediante Adobe Flash
        $userAgent = $request->getHeader('user-agent');
        if(stristr($userAgent[0], ' flash')) {
            return 'FlashRequest';
        }
    }
    // Devuelve HttpRequest en el resto de los casos
    return 'HttpRequest';
}