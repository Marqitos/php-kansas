<?php declare(strict_types = 1 );
/**
 * Plugin que representa la API de una aplicación web
 *
 * @package Kansas
 * @author Marcos Porto
 * @copyright Marcos Porto
 * @since v0.4
 */

namespace Kansas\Plugin;

use System\Localization\Resources as SystemResources;
use System\NotSupportedException;
use System\Version;
use Kansas\Plugin\AbstractZone;
use Kansas\Router\API as RouterAPI;
use Kansas\Router\RouterInterface;

require_once 'System/Localization/Resources.php';
require_once 'Kansas/Plugin/AbstractZone.php';
require_once 'Kansas/Plugin/RouterPluginInterface.php';

class API extends AbstractZone implements RouterPluginInterface {
    
    private $router;

    /// Constructor
	public function __construct(array $options) {
        parent::__construct($options);
	}
 
	// Miembros de System\Configurable\ConfigurableInterface
    public function getDefaultOptions(string $environment) : array {
        switch ($environment) {
            case 'production':
            case 'development':
            case 'test':
                return [
                    'base_path' => 'api',
                    'params'    => [
                        'cors'      => '*'],
                    'plugins'   => []
                ];
            default:
                require_once 'System/NotSupportedException.php';
                NotSupportedException::NotValidEnvironment($environment);
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
  
    public function registerAPICallback(callable $callback) : void {
        $this->getRouter()->registerCallback($callback);
    }

	public function getRouter() : RouterInterface {
		if($this->router == null) {
			require_once 'Kansas/Router/API.php';
			$this->router = new RouterAPI($this->options);
            $this->registerAPICallback = $this->router->registerAPICallback;
		}
		return $this->router;
	}

    public const ERROR_AUTH_BEARER = [
        'code'      => 401,
        'status'    => 'error',
        'message'   => SystemResources::WEB_EXCEPTION_MESSAGES[401],
        'scheme'    => 'Bearer'
    ];

    public const ERROR_REQUEST = [
        'code'      => 412,
        'status'    => 'error',
        'message'   => SystemResources::WEB_EXCEPTION_MESSAGES[412],
    ];

    public const ERROR_NOT_FOUND = [
        'code'      => 404,
        'status'    => 'error',
        'message'   => SystemResources::WEB_EXCEPTION_MESSAGES[404],
    ];

}