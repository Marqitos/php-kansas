<?php
/**
  * Representa la funcionalidad basica de un router (MVC)
  *
  * @package    Kansas
  * @author     Marcos Porto Mariño
  * @copyright  2025, Marcos Porto <lib-kansas@marcospor.to>
  * @since      v0.4
  */

namespace Kansas\Router;

use System\Configurable\ConfigurableInterface;

require_once 'System/Configurable/ConfigurableInterface.php';

interface RouterInterface extends ConfigurableInterface {
  public function getBasePath() : string;
  public function match() : array|false;
  public function assemble($data = [], $reset = false, $encode = false) : string;
}
