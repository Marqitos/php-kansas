<?php
/**
 * Representa la funcionalidad basica de un router (MVC)
 *
 * @package Kansas
 * @author Marcos Porto
 * @copyright Marcos Porto
 * @since v0.4
 */

namespace Kansas\Router;

use System\Configurable\ConfigurableInterface;

require_once 'System/Configurable/ConfigurableInterface.php';

interface RouterInterface extends ConfigurableInterface {
	public function match();
	public function assemble($data = [], $reset = false, $encode = false);
	public function getBasePath();
}
