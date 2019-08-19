<?php

namespace Kansas\Request;

use Psr\Http\Message\RequestInterface;

function getRequestType(RequestInterface $request) {
    if($request->hasHeader('X_REQUESTED_WITH')) { 
        // Devuelve XMLHttpRequest en las peticiones mediante Javascript XMLHttpRequest
        // Should work with Prototype/Script.aculo.us, possibly others.
        $requestedWith = $request->getHeader('X_REQUESTED_WITH');
        if(array_search('XMLHttpRequest', $requestedWith))
            return 'XMLHttpRequest';
    } 
    if($request->hasHeader('user-agent')) {
        // Devuelve flash en las peticiones mediante Adobe Flash
        $userAgent = $request->getHeader('user-agent');
        if(stristr($userAgent[0], ' flash'))
            return 'flash';
    }
    // Devuelve HttpRequest en el resto de los casos
    return 'HttpRequest';
}