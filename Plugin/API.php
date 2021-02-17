<?php

namespace Kansas\Plugin;

use Kansas\Plugin\AbstractZone;
use System\NotSupportedException;
use Kansas\Router\API as RouterAPI;

use function Kansas\API\APICore;

require_once 'Kansas/Plugin/AbstractZone.php';

class API extends AbstractZone {
	
    private $router;
    private $callbacks = [];

    /// Constructor
	public function __construct(array $options) {
        parent::__construct($options);
		global $application;
	}
 
	// Miembros de System\Configurable\ConfigurableInterface
    public function getDefaultOptions($environment) : array {
        switch ($environment) {
            case 'production':
            case 'development':
            case 'test':
                return [
                'base_path' => 'api',
                'params' => []
                ];
            default:
                require_once 'System/NotSupportedException.php';
                throw new NotSupportedException("Entorno no soportado [$environment]");
        }
    }

    public function getVersion() {
		global $environment;
		return $environment->getVersion();
	}

	public function onAppPreInit($zone) { // añadir router
        global $application;
        if($zone instanceof API) {
            require_once 'Kansas/API/Core.php';
            $router = $this->getRouter();
            $router->registerCallback('Kansas\API\APICore');
            $application->addRouter($router);
        }
    }
  
    public function registerAPICallback(callable $callback) {
        $this->getRouter()->registerCallback($callback);
    }

	public function getRouter() {
		if($this->router == null) {
			require_once 'Kansas/Router/API.php';
			$this->router = new RouterAPI($this->options);
		}
		return $this->router;
	}

    const ERROR_AUTH = [
        'error' => 'Se requiere autenticación',
        'code'  => 401,
        'scheme'=> 'Bearer'
    ];

    const ERROR_REQUEST = [
        'error' => 'Solicitud no válida',
        'code'  => 412
    ];
}