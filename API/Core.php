<?php

namespace Kansas\API;

use Psr\Http\Message\RequestMethodInterface;
use function System\String\startWith;

function APICore($path, $method) {
    require_once 'Psr/Http/Message/RequestMethodInterface.php';
    global $application, $environment;
    $authError = [
        'error' => 'Se requiere autenticación',
        'code'  => 403
    ];
    switch($path) {
        case '': // Devuelve los datos basicos de la aplicación y usuario.
            if($method == RequestMethodInterface::METHOD_GET) {
                $auth = $application->getPlugin('Auth');
                if($auth->getIdentity() === false)
                    return $authError;
                return [
                    'host'			=> $environment->getRequest()->getServerParams()['HTTP_HOST'], // Use trail data
                    'name'			=> $application->createTitle()->__toString(),
                    'username'		=> $auth->getIdentity()->getName(),
                    'environment'	=> $application->getEnvironment()
                ];
            }
            break;
        case 'modules':
            return $application->getPlugins();
        case 'ping':
            return [ 'ping' => 'pong'];
        case 'config':
            $params = $this->getParams([
                'controller'	=> 'API',
                'action'		=> 'config'
            ]);
            break;
    }
    require_once 'System/String/startWith.php';
    if(startWith($path, 'files')) {
        $params = $this->getParams([
            'controller'	=> 'API',
            'action'		=> 'files'
        ]);
        if(strlen($path) > 5)
            $params['path'] = trim(substr($path, 6), './ ');
    }
    return false;
}