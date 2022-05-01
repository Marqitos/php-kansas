<?php declare(strict_types = 1);
/**
 * Proporciona la funcionalidad basica de un router (MVC)
 *
 * @package Kansas
 * @author Marcos Porto
 * @copyright Marcos Porto
 * @since v0.4
 */

namespace Kansas;

use System\Configurable;
use Kansas\Router\RouterInterface;
use function array_merge;
use function mb_strlen;
use function mb_substr;
use function trim;
use function System\String\startWith;

require_once 'System/Configurable.php';
require_once 'Kansas/Router/RouterInterface.php';

abstract class Router extends Configurable implements RouterInterface {
	
	protected function getParams(array $params) : array {
		return array_merge($this->options['params'], $params);
	}
	
	// Miembros de System\Configurable\ConfigurableInterface
	public function getDefaultOptions(string $environment) : array {
		return [
			'base_path'	=> '',
  			'params'	=> []
		];
	}
	
	// Miembros de Kansas\Router\RouterInterface
	public function getBasePath() {
		return $this->options['base_path'];
	}
	public function setBasePath($basePath) {
		$this->options['base_path'] = trim((string) $basePath, '/');
	}
	
	public function assemble($data = [], $reset = false, $encode = false) {
		return isset($data['basepath']) 
			? $data['basepath']
			: '/' . $this->getBasePath();
	}

	public static function getPath(RouterInterface $router) {
		global $environment;
		require_once 'System/String/startWith.php';
		$path = trim($environment->getRequest()->getUri()->getPath(), '/');
		$basePath = $router->getBasePath();
		if(mb_strlen($basePath) == 0) {
			$result = $path;
		} elseif(!startWith($path, $basePath)) {
			return false;
		} else {
			$result = trim(mb_substr($path, mb_strlen($basePath)), '/');
		}
		return $result === false
			? ''
			: $result;
	}

}
