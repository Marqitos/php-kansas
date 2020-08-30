<?php

namespace Kansas;

use System\Configurable;
use Kansas\Router\RouterInterface;
use function array_merge;
use function trim;
use function substr;
use function System\String\startWith;

require_once 'System/Configurable.php';
require_once 'Kansas/Router/RouterInterface.php';

abstract class Router extends Configurable implements RouterInterface {
	
	protected function getParams(array $params) : array {
		return array_merge($this->options['params'], $params);
	}
	
	// Miembros de System\Configurable\ConfigurableInterface
	public function getDefaultOptions($environment) : array {
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

	public static function getPath(RouterInterface $router) {
		global $environment;
		require_once 'System/String/startWith.php';
		$path = trim($environment->getRequest()->getUri()->getPath(), '/');
		$basePath = $router->getBasePath();
		if(!startWith($path, $basePath))
			return false;
		if(substr($path, strlen($basePath)) == false)
			return '';
		return substr($path, strlen($basePath));
	}
	

}