<?php
interface Kansas_Controller_Interface {
	public function init(array $params);
	public function callAction($actionName, array $vars);
}