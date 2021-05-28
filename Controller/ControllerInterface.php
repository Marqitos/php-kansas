<?php declare(strict_types = 1 );
/**
 * Representa un controlador MVC
 */

namespace Kansas\Controller;

use Kansas\View\Result\ViewResultInterface;

require_once 'Kansas/View/Result/ViewResultInterface.php';

interface ControllerInterface {
	public function init(array $params) : void;
	public function callAction(string $actionName, array $vars) : ViewResultInterface;
}
