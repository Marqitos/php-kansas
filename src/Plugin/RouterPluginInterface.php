<?php declare(strict_types = 1);
/**
  * Representa un plugin que tiene un router asociado
  *
  * @package    Kansas
  * @author     Marcos Porto Mariño
  * @copyright  2025, Marcos Porto <lib-kansas@marcospor.to>
  * @since      v0.4
  */

namespace Kansas\Plugin;

use Kansas\Plugin\PluginInterface;
use Kansas\Router\RouterInterface;

require_once 'Kansas/Plugin/PluginInterface.php';
require_once 'Kansas/Router/RouterInterface.php';

/**
 * Representa un plugin que tiene un router asociado
 */
interface RouterPluginInterface extends PluginInterface {
    /**
     * Obtiene el router asociado al plugin
     * @return RouterInterface Router asociado
     */
    public function getRouter() : RouterInterface;
}
