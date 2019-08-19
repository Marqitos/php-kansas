<?php

namespace Kansas\Router;

use Kansas\Router;
use System\NotSuportedException;
use Kansas\Environment;

require_once 'Kansas/Router.php';

class TrailResources extends Router {

	public function __construct(array $options) {
        parent::__construct($options);
    }
    
    /// Miembros de Kansas\Configurable
    public function getDefaultOptions($environmentStatus) {
        global $environment;
        switch ($environmentStatus) {
            case 'production':
            case 'development':
            case 'test':
                require_once 'Kansas/Environment.php';
                $libsPath = $environment->getSpecialFolder(Environment::SF_LIBS);
                return [
                    'paths'	=> [
                        'img/browser-' => realpath($libsPath . 'bbClone/images/browser') . '/',
                        'img/ext-'     => realpath($libsPath . 'bbClone/images/ext')     . '/',
                        'img/os-'      => realpath($libsPath . 'bbClone/images/os')      . '/',
                        'img/robot-'   => realpath($libsPath . 'bbClone/images/robot')   . '/'
                    ]
                ];
            default:
                require_once 'System/NotSuportedException.php';
                throw new NotSuportedException("Entorno no soportado [$environmentStatus]");
        }
    }

		
	public function match() {
        global $environment;
        $path = trim($environment->getRequest()->getUri()->getPath(), '/');
        foreach($this->options['paths'] as $requestPath => $realPath) {
            $length = strlen($requestPath);
            if($requestPath == substr($path, 0, $length)) {
                $partial = substr($path, $length);
                if($file = realpath($realPath . $partial . ".png")) {
                    return [
                        'controller'    => 'index',
                        'action'        => 'file',
                        'file'          => $file
                    ];
                }
            }
        }
		return false;
	}
	
}