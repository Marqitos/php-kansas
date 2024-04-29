<?php declare(strict_types = 1);
/**
 * Proporciona métodos para importar código en javascript
 *
 * @package Kansas
 * @author Marcos Porto
 * @copyright Marcos Porto
 * @since v0.5
 */

namespace Kansas\Layout;

use System\Collections\KeyNotFoundException;

abstract class Javascript {

  public const SCRIPT = 'script';
  public const DEPENDENCIES = 'dependencies';
  protected $scripts = [];

  protected function resolveDependencies(array $script, array &$list) : void {
    if (isset($script[self::DEPENDENCIES])) {
      $dependencies = $script[self::DEPENDENCIES];
      foreach($dependencies as $callable) {
        // Comprobamos si el script ya está en la lista, lo mueve al principio
        $name = '';
        if (!is_callable($callable, false, $name)) {
          require_once 'System/Collections/KeyNotFoundException.php';
          throw new KeyNotFoundException($name);
        }
        if (isset($list[$name])) {
          $dependencie = $list[$name];
          unset($list[$name]);
          $list = array_reverse($list, true);
          $list[$name] = $dependencie;
          $list = array_reverse($list, true);
        }

        // Cargamos el script si es necesario
        if (!isset($this->scripts[$name])) {
          $this->loadScript($callable);
        }
        $dependencie = $this->scripts[$name];

        // Comprobamos si tiene dependencias
        $this->resolveDependencies($dependencie, $list);

        // Insertamos el nuevo elemento al principio
        $list = array_reverse($list, true);
        $list[$name] = $dependencie;
        $list = array_reverse($list, true);
      }
    }
  }

  public function loadScript(callable $callable) : void {
    $name = '';
    if (is_callable($callable, false, $name)) {
      $this->scripts[$name] = call_user_func($callable);
    } else {
      require_once 'System/Collections/KeyNotFoundException.php';
      throw new KeyNotFoundException($name);
    }
  }
}

global $javascript;

if (!isset($javascript)) {
  $javascript = [];
}
