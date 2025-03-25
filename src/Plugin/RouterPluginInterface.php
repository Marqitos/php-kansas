<?php declare(strict_types = 1);
/**
 * Representa un plugin que tiene un router asociado
 *
 * @package Kansas
 * @author Marcos Porto
 * @copyright 2021, Marcos Porto
 * @since v0.4
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
