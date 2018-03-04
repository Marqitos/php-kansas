<?php
require_once 'System/Configurable/Abstract.php';
require_once 'Kansas/Router/Interface.php';

abstract class Kansas_Router_Abstract
	extends System_Configurable_Abstract
	implements Kansas_Router_Interface {
	
	protected function getParams(array $params) {
		return array_merge($this->options['params'], $params);
	}
  
	public function getDefaultOptions($environment) {
		return [
			'base_path'	=> '',
  			'params'	=> []
		];
	}
	
	/* Miembros de Kansas_Router_Interface */
	public function getBasePath() {
		return $this->options['base_path'];
	}
	public function setBasePath($basePath) {
		$this->options['base_path'] = trim((string) $basePath, '/');
	}
	
	public function assemble($data = [], $reset = false, $encode = false) {
		return isset($data['basepath']) ?
			$data['basepath']:
			'/' . $this->getBasePath();
	}

	public static function getPath(Kansas_Router_Interface $router) {
		global $environment;
		$path = trim($environment->getRequest()->getUri()->getPath(), '/');
		return (System_String::startWith($path, $router->getBasePath()))
			? substr($path, strlen($router->getBasePath()))
			: false;
	}
	

}