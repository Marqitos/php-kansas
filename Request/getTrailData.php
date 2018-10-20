<?php

namespace Kansas\Request;

use Psr\Http\Message\ServerRequestInterface;

require_once 'Psr/Http/Message/ServerRequestInterface.php';

// Obtiene los datos basicos de navegación de la solicitud actual
function getTrailData(ServerRequestInterface $request) {
    global $environment;
    $uri = $request->getUri();
    $data = [
        'time' => $environment->getRequestTime(),
        'hostname' => $uri->getHost(),
        'uri' => $request->getRequestTarget(),
        'page' => trim($uri->getPath()),
        'environment' => $environment->getStatus()
    ];

    $serverParams = $request->getServerParams();
    if((stristr(PHP_OS, "darwin") !== false) &&
        isset($serverParams['HTTP_PC_REMOTE_ADDR']) &&
        !empty($serverParams['HTTP_PC_REMOTE_ADDR'])) {
        $data['remoteAddress'] = $serverParams['HTTP_PC_REMOTE_ADDR'];
    } else if(isset($serverParams['REMOTE_ADDR'])) {
        $data['remoteAddress'] = $serverParams['REMOTE_ADDR'];
    }

    if($request->hasHeader('user-agent')) {
        $data['userAgent'] = $request->getHeader('user-agent')[0];
    } else if(isset($serverParams['HTTP_USER_AGENT'])) {
        $data['userAgent'] = $serverParams['HTTP_USER_AGENT'];
    }

    if(isset($serverParams['HTTP_REFERER'])) {
        $data['referer'] = $serverParams['HTTP_REFERER'];
    } else {
        //$data['referer'] = bbc_filter_ref($HTTP_HOST, $HTTP_REFERER, $SERVER_NAME, $SERVER_ADDR);
    }
    
    //$prx = bbc_parse_headers();

    //$data['prx'] = bbc_get_remote_addr($REMOTE_ADDR, $HTTP_X_REMOTECLIENT_IP);

    return $data;
}