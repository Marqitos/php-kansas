<?php declare(strict_types = 1 );
/**
 * Router que controla las llamadas a la API
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
use function call_user_func;
use function is_array;
use function trim;

require_once 'Kansas/Router.php';

class API extends Router implements RouterInterface {

	private $callbacks = [];

	// Miembros de System\Configurable\ConfigurableInterface
	public function getDefaultOptions(string $environment) : array {
		switch ($environment) {
			case 'production':
			case 'development':
			case 'test':
				return [
					'base_path'	=> 'api',
					'params'	=> [
						'cors'			=> '*',
						'controller'	=> 'index',
						'action'		=> 'API']];
			default:
				require_once 'System/NotSupportedException.php';
				NotSupportedException::NotValidEnvironment($environment);
		}
	}

    /// Miembros de Kansas_Router_Interface
	public function match() : array {
		global $environment;
		$path = static::getPath($this);
        if($path === false) {
			return false;
		}
		$path 	= trim($path, '/');
		$method = $environment->getRequest()->getMethod();
		foreach($this->callbacks as $callback) {
			$result = call_user_func($callback, $path, $method);
			if(is_array($result)) {
				return parent::getParams($result);
			}
		}
		require_once 'Kansas/Plugin/API.php';
		return parent::getParams(APIPlugin::ERROR_NOT_FOUND);
	}

	public function registerCallback(callable $callback) : void {
	    $this->callbacks[] = $callback;
	}
	
}