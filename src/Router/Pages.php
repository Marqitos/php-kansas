<?php declare(strict_types = 1);
/**
  * Proporciona enrutamiento estatico mediante la coincidencia con la ruta
  *
  * @package    Kansas
  * @author     Marcos Porto Mariño
  * @copyright  2025, Marcos Porto <lib-kansas@marcospor.to>
  * @since      v0.4
  * @version    v0.6
  */

namespace Kansas\Router;

use Kansas\Router;
use System\EnvStatus;

require_once 'Kansas/Router.php';

class Pages extends Router {

  // Miembros de System\Configurable\ConfigurableInterface
  public function getDefaultOptions(EnvStatus $environment): array {
    return [
      'base_path' => '',
      'routes'    => [],
      'params'    => [],
      'keys'      => [
        'path',
        'pattern']
    ];
  }

    /**
      * Devuelve los datos de enrutamiento de la ruta actual
      *
      * @return array | false
      */
    #[SuppressWarnings("php:S1142")]
    #[SuppressWarnings("php:S3776")]
    public function match(): array|false {
        $path   = self::getPath($this);
        if ($path === false) {
            return false;
        }

        $match = parent::match();
        if (is_array($match)) {
            return $this->getParams($match);
        }

        $routes = $this->options['routes'];
        foreach ($routes as $route) {
            // Comparamos con rutas estáticas
            if (isset($route['path']) &&
                is_array($route['path'])) {
                $match = false;
                foreach ($route['path'] as $page) {
                    if ($page == $path) {
                        $match = true;
                        break;
                    }
                }
                if ($match ||
                    $route['path'] == $path) {
                    return $this->getParams($route);
                }
            }

            // Comparamos con patrónes
            if (isset($route['pattern'])) {
                preg_match($route['pattern'], $path, $matches);
                if (count($matches) > 0) {
                    return [...$matches, ...$this->getParams($route)];
                }
            }
        }
        return false;
    }

## Miebros de Kansas\Router
    public function getParams($route) : array {
        foreach ($this->options['keys'] as $key) {
            unset($route[$key]);
        }
        return parent::getParams($route);
    }
## -- Router
}
