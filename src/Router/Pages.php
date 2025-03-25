<?php declare(strict_types = 1);
/**
 * Proporciona enrutamiento estatico mediante la coincidencia con la ruta
 *
 * @package Kansas
 * @author Marcos Porto
 * @copyright 2024, Marcos Porto
 * @since v0.4
 * PHP 7 >= 7.2
 */

namespace Kansas\Router;

use Kansas\Router;

require_once 'Kansas/Router.php';

class Pages extends Router {

  // Miembros de System\Configurable\ConfigurableInterface
  public function getDefaultOptions(string $environment) : array {
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
  public function match() : mixed {
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
      if (isset($route['path'])) {
        $match = false;
        if (is_array($route['path'])) {
          foreach ($route['path'] as $page) {
            if ($page == $path) {
              $match = true;
              break;
            }
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
          return $this->getParams(array_merge($matches, $route));
        }
      }
    }
    return false;
  }

  public function setPageRoute(string $page, array $params) : void {
        $this->options['routes'][] = array_merge($params, ['page' => $page]);
  }

  // Miebros de Kansas\Router
  public function getParams($route) : array {
      foreach ($this->options['keys'] as $key) {
          unset($route[$key]);
      }
      return parent::getParams($route);
  }
}
