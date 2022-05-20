<?php declare(strict_types = 1 );
/**
 * Router que controla las llamadas a la API
 *
 * @package Kansas
 * @author Marcos Porto
 * @copyright Marcos Porto
 * @since v0.4
 */

namespace Kansas\Router;

use Throwable;
use System\NotSupportedException;
use Kansas\Router;
use Kansas\Plugin\API as APIPlugin;
use Psr\Http\Message\RequestMethodInterface;
use function call_user_func;
use function is_array;
use function trim;

require_once 'Kansas/Plugin/API.php';
require_once 'Kansas/Router.php';

class API extends Router implements RouterInterface {

	private $callbacks 	= [];
    private $paths 		= [];

	// Miembros de System\Configurable\ConfigurableInterface
	public function getDefaultOptions(string $environment) : array {
		switch ($environment) {
			case 'production':
			case 'development':
			case 'test':
				return [
					'base_path'	=> '',
					'params'	=> [
						'cors'			=> [
                            'Origin'        => '*',
                            'Headers'       => '*',
                            'Credentials'   => true],
						'controller'	=> 'index',
						'action'		=> 'API']];
			default:
				require_once 'System/NotSupportedException.php';
				NotSupportedException::NotValidEnvironment($environment);
		}
	}

    /// Miembros de Kansas_Router_Interface
	public function match() : array {
		global $application, $environment;
		$path = static::getPath($this);
        if($path === false) {
			return false;
		}
		$path 	= trim($path, '/');
		$method = $environment->getRequest()->getMethod();
		$dispatch	= false;
		if($method == RequestMethodInterface::METHOD_OPTIONS) {
			$methods = [];
			foreach($this->paths as $routeMethod => $routePath) {
				if(isset($routePath[$path])) {
					if($routeMethod == APIPlugin::METHOD_ALL) {
                        $methods = ['*'];
					} else {
						$methods[] = $routeMethod;
					}
				}
			}
            if(!empty($methods)) {
                $methods = implode(', ', $methods);
                header('Access-Control-Allow-Origin: *');
                header('Access-Control-Allow-Methods: ' . $methods);
                header('Access-Control-Allow-Headers: *');
                header('Access-Control-Allow-Credentials: true');
                die;
            }
		}
		if(isset($this->paths[$method], $this->paths[$method][$path])) {
			$dispatch = $this->paths[$method][$path];
		} elseif(isset($this->paths[APIPlugin::METHOD_ALL], $this->paths[APIPlugin::METHOD_ALL][$path])) {
			$dispatch = $this->paths[APIPlugin::METHOD_ALL][$path];
		}
		if($dispatch) {
			ignore_user_abort(true);
			set_time_limit(0);
			try {
                if(is_array($dispatch) &&
                isset($dispatch[APIPlugin::PARAM_FUNCTION])) {
                    $function = $dispatch[APIPlugin::PARAM_FUNCTION];
                    if(isset($dispatch[APIPlugin::PARAM_REQUIRE])) {
                        require_once $dispatch[APIPlugin::PARAM_REQUIRE];
                    }
                } else {
                    $function = $dispatch;
                }
                if(!function_exists($function)) {
                    require_once str_replace('\\', DIRECTORY_SEPARATOR, $function) . '.php';
                }
                $result = call_user_func($function, $path, $method);
            } catch(Throwable $ex) {
				if($debugger = $application->hasPlugin('Debug')) {
					$debugger->error($ex);
				}
                $result = APIPlugin::ERROR_INTERNAL_SERVER;
            }
            if(is_array($result)) {
                return parent::getParams($result);
            }
		}
	
		foreach($this->callbacks as $callback) {
            try {
                $result = call_user_func($callback, $path, $method);
            } catch(Throwable $ex) {
				if($debugger = $application->hasPlugin('Debug')) {
					$debugger->error($ex);
				}
                $result = APIPlugin::ERROR_INTERNAL_SERVER;
            }
			if(is_array($result)) {
				return parent::getParams($result);
			}
		}

		return parent::getParams(APIPlugin::ERROR_NOT_FOUND);
	}

	public function registerCallback(callable $callback) : void {
	    $this->callbacks[] = $callback;
	}

	public function registerPath(string $path, $dispatch, string $method = APIPlugin::METHOD_ALL) {
        if(!isset($this->paths[$method])) {
            $this->paths[$method] = [];
        }
        $this->paths[$method][$path] = $dispatch;
    }

}
