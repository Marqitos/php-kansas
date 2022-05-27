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

use System\Configurable;
use System\Version;
use Kansas\Application;
use Kansas\Router\API as RouterAPI;
use Kansas\Router\RouterInterface;

require_once 'System/Configurable.php';
require_once 'Kansas/Plugin/RouterPluginInterface.php';

class API extends Configurable implements RouterPluginInterface {
    
    protected $router;

    const PARAM_REQUIRE     = 'require';
    const PARAM_FUNCTION    = 'function';
    const METHOD_ALL        = 'ALL';

    /// Constructor
	public function __construct(array $options, $object = null) {
        global $application;
        parent::__construct($options);
        if($object === null) {
            $object = $this;
        }
		$application->registerCallback(Application::EVENT_PREINIT, [$object, "appPreInit"]);
	}
 
	// Miembros de System\Configurable\ConfigurableInterface
    public function getDefaultOptions(string $environment) : array {
        return [
            'base_path' => '',
            'params'    => [
                'cors'			=> [
                    'origin'        => '*',
                    'headers'       => '*',
                    'credentials'   => true]]];
    }

    // Miembros de Kansas\Plugin\PluginInterface
    public function getVersion() : Version {
		global $environment;
		return $environment->getVersion();
	}

    // Miembros de Kansas\Router\RouterInterface
	public function getRouter() : RouterInterface {
		if($this->router == null) {
			require_once 'Kansas/Router/API.php';
			$this->router = new RouterAPI($this->options);
		}
		return $this->router;
	}

	public function appPreInit() : void { // añadir router
        global $application;
        $application->setRouter($this->getRouter());
    }
  
    public function registerAPICallback(callable $callback) : void {
        $this->getRouter()->registerCallback($callback);
    }

    public function registerPath(string $path, $dispatch, string $method = self::METHOD_ALL) {
        $this->getRouter()->registerPath($path, $dispatch, $method);
        if(!isset($this->paths[$method])) {
            $this->paths[$method] = [];
        }
        $this->paths[$method][$path] = $dispatch;
    }


    public const ERROR_NO_AUTH = [
        'status'    => 403,
        'success'   => false,
        'data'      => [],
        'message'   => 'No autorizado'
    ];

    public const ERROR_AUTH_BEARER = [
        'status'    => 401,
        'success'   => false,
        'data'      => [
            'scheme'    => 'Bearer'
        ]
    ];

    public const ERROR_REQUEST = [
        'status'    => 412,
        'success'   => false,
        'data'      => [],
        'message'   => 'No se han enviado los parámetros validos'
    ];

    public const ERROR_NOT_FOUND = [
        'status'    => 404,
        'success'   => false,
        'data'      => [],
        'message'   => 'El documento no ha sido encontrado'
    ];

    public const ERROR_INTERNAL_SERVER = [
        'status'    => 500,
        'success'   => false,
        'data'      => [],
        'message'   => 'Error interno del servidor'
    ];

}