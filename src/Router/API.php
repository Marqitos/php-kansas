<?php declare(strict_types = 1);
/**
  * Router que controla las llamadas a la API
  *
  * @package    Kansas
  * @author     Marcos Porto Mariño
  * @copyright  2025, Marcos Porto <lib-kansas@marcospor.to>
  * @since      v0.4
  */

namespace Kansas\Router;

use LogicException;
use Throwable;
use Kansas\API\APIExceptionInterface;
use Kansas\Router;
use Kansas\Localization\Resources;
use Kansas\Plugin\API as APIPlugin;
use Psr\Http\Message\RequestMethodInterface;
use System\ArgumentException;
use System\EnvStatus;

use function call_user_func;
use function is_array;
use function trim;

require_once 'Kansas/Plugin/API.php';
require_once 'Kansas/Router.php';

class API extends Router implements RouterInterface {

    private $callbacks  = [];
    private $paths      = [];

## Miembros de System\Configurable\ConfigurableInterface
    public function getDefaultOptions(EnvStatus $environment): array {
        return [
            'base_path' => '',
            'params'    => [
                'cors'          => [
                    'origin'        => '*',
                    'headers'       => '*',
                    'credentials'   => true],
                'controller'    => 'index',
                'action'        => 'API']];
    }
## -- ConfigurableInterface

## Miembros de Kansas\Router\RouterInterface
    public function match(): array|false {
        global $application, $environment;
        $path = static::getPath($this);
        if ($path === false) {
            return false;
        }
        $path       = trim($path, '/');
        $method     = $environment->getRequest()->getMethod();
        // Gestionamos el metodo OPTIONS
        if ($method == RequestMethodInterface::METHOD_OPTIONS) {
            $methods = $this->getMethods($path);
            if (!empty($methods)) {
                $methods = implode(', ', $methods);
                header('Cache-Control: no-cache');
                header('Access-Control-Allow-Origin: *');
                header('Access-Control-Allow-Methods: ' . $methods);
                header('Access-Control-Allow-Headers: *');
                header('Access-Control-Allow-Credentials: true');
                exit(0);
            }
        }
        // Gestionamos el resto de metodos
        require_once 'Kansas/API/APIExceptionInterface.php';
        $dispatch   = false;
        $result     = false;
        if (isset($this->paths[$method], $this->paths[$method][$path])) {
            $dispatch = $this->paths[$method][$path];
        } elseif (isset($this->paths[APIPlugin::METHOD_ALL], $this->paths[APIPlugin::METHOD_ALL][$path])) {
            $dispatch = $this->paths[APIPlugin::METHOD_ALL][$path];
        }
        if ($dispatch) {
            ignore_user_abort(true);
            set_time_limit(0);
            try {
                if (is_array($dispatch) &&
                isset($dispatch[APIPlugin::PARAM_FUNCTION])) {
                    $function = $dispatch[APIPlugin::PARAM_FUNCTION];
                    if (isset($dispatch[APIPlugin::PARAM_REQUIRE])) {
                        require_once $dispatch[APIPlugin::PARAM_REQUIRE];
                    }
                } else {
                    $function = $dispatch;
                }
                if (!function_exists($function)) {
                    require_once str_replace('\\', DIRECTORY_SEPARATOR, $function) . '.php';
                }
                $result = call_user_func($function, $path, $method);
            } catch (APIExceptionInterface $ex) {
                $result = $ex->getAPIResult();
            } catch (Throwable $ex) {
                if ($logger = $application->hasPlugin('Logger')) {
                    $logger->error($ex->getMessage(), ['exception' => $ex]);
                }
                $result = APIPlugin::ERROR_INTERNAL_SERVER;
            }
        }

        if(!$result) { // Gestionamos callbacks
            foreach($this->callbacks as $callback) {
                try {
                    $result = call_user_func($callback, $path, $method);
                    // Establecemos un cache predeterminado según el metodo de llamada
                    switch($method) {
                        case RequestMethodInterface::METHOD_GET:
                        case RequestMethodInterface::METHOD_HEAD:
                            header('Cache-Control: must-revalidate');
                            break;
                        case RequestMethodInterface::METHOD_POST:
                        case RequestMethodInterface::METHOD_PUT:
                        case RequestMethodInterface::METHOD_DELETE:
                        case RequestMethodInterface::METHOD_PATCH:
                            header('Cache-Control: no-cache');
                            break;
                        default:
                        if($logger = $application->hasPlugin('Logger')) {
                            $logger->debug('metodo desconocido: {method}', ['method' => $method]);
                        }
                    }
                } catch (APIExceptionInterface $ex) {
                    $result = $ex->getAPIResult();
                } catch (Throwable $ex) {
                    if ($logger = $application->hasPlugin('Logger')) {
                        $logger->error($ex->getMessage(), ['exception' => $ex]);
                    }
                    $result = APIPlugin::ERROR_INTERNAL_SERVER;
                }
            }
        }

        if (!$result) { // No se ha encontrado el documento
            $result = APIPlugin::ERROR_NOT_FOUND;
        } elseif (is_array($this->options['params']['cors'])) { // Gestionamos CORS
            $methods = implode(', ', $this->getMethods($path));
            $this->options['params']['cors']['methods'] = $methods;
        }

        return parent::getParams($result);
    }

    public function registerCallback(callable $callback) : void {
        $this->callbacks[] = $callback;
    }

    public function registerPath($path, $dispatch, string $method = APIPlugin::METHOD_ALL) {
        if (is_array($path)) {
            foreach ($path as $item) {
                $this->registerPath($item, $dispatch, $method);
            }
            return;
        } elseif (! is_string($path)) {
            throw new ArgumentException('path', 'Formato de ruta no válido');
        }
        if ($method == RequestMethodInterface::METHOD_OPTIONS) {
            require_once 'Kansas/Localization/Resources.php';
            throw new LogicException(Resources::API_OPTIONS_METHOD_RESERVED);
        }
        if (!isset($this->paths[$method])) {
            $this->paths[$method] = [];
        }
        $this->paths[$method][$path] = $dispatch;
    }

    protected function getMethods(string $path) : array {
        $methods = [];
        foreach ($this->paths as $routeMethod => $routePath) {
            if (isset($routePath[$path])) {
                if ($routeMethod == APIPlugin::METHOD_ALL) {
                    $methods = ['*'];
                } else {
                    $methods[] = $routeMethod;
                }
            }
        }
        return $methods;
    }

}
