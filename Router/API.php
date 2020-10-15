<?php

namespace Kansas\Router;

use Kansas\Router;
use System\NotSuportedException;

require_once 'Kansas/Router.php';

class API extends Router {

	private $callbacks = [];

	// Miembros de System\Configurable\ConfigurableInterface
	public function getDefaultOptions($environment) : array {
		switch ($environment) {
			case 'production':
			case 'development':
			case 'test':
				return [
					'base_path'	=> 'api',
					'params'	=> []
				];
			default:
				require_once 'System/NotSuportedException.php';
				throw new NotSuportedException("Entorno no soportado [$environment]");
		}
	}

    /// Miembros de Kansas_Router_Interface
	public function match() {
		global $environment;
		$path = static::getPath($this);
        if($path === false)
			return false;
		$path = trim($path, '/');
		$method = $environment->getRequest()->getMethod();
		foreach($this->callbacks as $callback) {
			$result = call_user_func($callback, $path, $method);
			if(is_array($result))
				return array_merge($result, [
					'controller'	=> 'index',
					'action'		=> 'API'
				]);
		}
		return [
			'controller'	=> 'index',
			'action'		=> 'API',
			'error'			=> 'No encontrado',
			'code'			=> 404
		];
	}

	public function registerCallback(callable $callback) {
	    $this->callbacks[] = $callback;
	}
	
}