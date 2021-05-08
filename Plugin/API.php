<?php

namespace Kansas\Plugin;

use System\Localization\Resources as SystemResources;
use System\NotSupportedException;
use System\Version;
use Kansas\Plugin\AbstractZone;
use Kansas\Router\API as RouterAPI;

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
                    'params'    => [
                        'cors'      => true],
                    'plugins'   => []
                ];
            default:
                require_once 'System/NotSupportedException.php';
                throw new NotSupportedException("Entorno no soportado [$environment]");
        }
    }

    public function getVersion() : Version {
		global $environment;
		return $environment->getVersion();
	}

	public function setUp() : void { // añadir router
        require_once 'Kansas/API/Core.php';
        global $application;
        $this->getRouter()->registerCallback('Kansas\API\core');
        $application->addRouter($this->router);
        foreach($this->options['plugins'] as $pluginName => $options) {
            $application->setPlugin($pluginName, $options);
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
        'code'      => 401,
        'status'    => 'error',
        'message'   => SystemResources::WebException401Message,
        'scheme'    => 'Bearer'
    ];

    public const ERROR_REQUEST = [
        'code'      => 412,
        'status'    => 'error',
        'message'   => SystemResources::WebException412Message,
    ];

    public const ERROR_NOT_FOUND = [
        'code'      => 404,
        'status'    => 'error',
        'message'   => SystemResources::WebException404Message,
    ];

}