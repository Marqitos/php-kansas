<?php declare(strict_types = 1);
/**
  * Representa un controlador MVC
  *
  * @package    Kansas
  * @author     Marcos Porto Mariño
  * @copyright  2025, Marcos Porto <lib-kansas@marcospor.to>
  */

namespace Kansas\Controller;

use Kansas\View\Result\ViewResultInterface;

require_once 'Kansas/View/Result/ViewResultInterface.php';

interface ControllerInterface {
    public function init(array $params) : void;
    public function callAction(string $actionName, array $vars) : ViewResultInterface;
}
