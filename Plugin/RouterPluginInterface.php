<?php
/**
 * Representa un plugin que tiene un router asociado
 *
 * @package Kansas
 * @author Marcos Porto
 * @copyright Marcos Porto
 * @since v0.4
 */

namespace Kansas\Plugin;

use Kansas\Plugin\PluginInterface;
use Kansas\Router\RouterInterface;

require_once 'Kansas/Plugin/PluginInterface.php';
require_once 'Kansas/Router/RouterInterface.php';

interface RouterPluginInterface extends PluginInterface {
	// Obtiene el router asociado al plugin
	public function getRouter() : RouterInterface;
}