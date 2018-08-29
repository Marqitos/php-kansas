<?php
namespace Kansas\Controller;

interface ControllerInterface {
	public function init(array $params);
	public function callAction($actionName, array $vars);
}