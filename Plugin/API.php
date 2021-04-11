<?php

namespace Kansas\Plugin;

use System\NotSupportedException;
use System\Localization\Resources as SystemResources;
use Kansas\Plugin\AbstractZone;
use Kansas\Router\API as RouterAPI;

use function Kansas\API\APICore;

require_once 'System/Localization/Resources.php';
require_once 'Kansas/Plugin/AbstractZone.php';

class API extends AbstractZone {
    
    private $router;

    /// Constructor
	public function __construct(array $options) {
        parent::__construct($options);
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
            $this->getRouter()->registerCallback('Kansas\API\APICore');
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

    public const ERROR_AUTH = [
        'error' => SystemResources::WebException401Message,
        'code'  => 401,
        'scheme'=> 'Bearer'
    ];

    public const ERROR_REQUEST = [
        'error' => SystemResources::WebException412Message,
        'code'  => 412
    ];

}