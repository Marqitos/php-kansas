<?php

namespace Kansas\API;

use Psr\Http\Message\RequestMethodInterface;
use function strlen;
use function substr;
use function trim;
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
            return $application->dispatch([
                'controller'	=> 'API',
                'action'		=> 'config'
            ]);
            break;
    }
    require_once 'System/String/startWith.php';
    if(startWith($path, 'files')) {
        $params = [
            'controller'	=> 'API',
            'action'		=> 'files'
        ];
        if(strlen($path) > 5)
            $params['path'] = trim(substr($path, 6), './ ');
        $application->dispatch($params);
    }
    return false;
}