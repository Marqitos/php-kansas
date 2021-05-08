<?php
/**
 * Plugin que representa la API de una aplicación web
 *
 * @package Kansas
 * @author Marcos Porto
 * @copyright Marcos Porto
 * @since v0.4
 */

namespace Kansas\Router;

use System\NotSupportedException;
use Kansas\Router;
use Kansas\Plugin\API as APIPlugin;
use function array_merge;
use function call_user_func;
use function is_array;
use function trim;

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
					'params'	=> [
						'cors'	=> true]];
			default:
				require_once 'System/NotSupportedException.php';
				throw new NotSupportedException("Entorno no soportado [$environment]");
		}
	}

    /// Miembros de Kansas_Router_Interface
	public function match() {
		global $environment;
		$path = static::getPath($this);
        if($path === false) {
			return false;
		}
		$params	= false;
		$path 	= trim($path, '/');
		$method = $environment->getRequest()->getMethod();
		foreach($this->callbacks as $callback) {
			$result = call_user_func($callback, $path, $method);
			if(is_array($result)) {
				$params = array_merge($result, [
					'controller'	=> 'index',
					'action'		=> 'API'
				]);
				break;
			}
		}
		if($params === false) {
			require_once 'Kansas/Plugin/API.php';
			$params = array_merge(APIPlugin::ERROR_NOT_FOUND, [
				'controller'	=> 'index',
				'action'		=> 'API']);
		}
		if($this->options['params']['cors']) {
			header('Access-Control-Allow-Origin: *');
		}
		return $params;
	}

	public function registerCallback(callable $callback) {
	    $this->callbacks[] = $callback;
	}
	
}