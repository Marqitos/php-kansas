<?php
/**
 * Router que analiza el destino, mediante acciones o rutas estáticas, utilizado por el plugin de autenticación.
 *
 * @package Kansas
 * @author Marcos Porto
 * @copyright Marcos Porto
 * @since v0.4
 */

namespace Kansas\Router;

use Kansas\Router\Pages;

require_once 'Kansas/Router/Pages.php';

class Auth extends Pages {

	private $_actions = [];

	public function match() {
		global $environment;
		$params = false;
		$path = trim($environment->getRequest()->getUri()->getPath(), '/');

		foreach($this->_actions as $action) {
			if(isset($action['path'])) {
				$actionPath = $this->getActionPath($action['path']);
				if(substr($actionPath, 1) == $path) {
					unset($action['path']);
					$params = $this->getParams($action);
					break;
				}
			}
		}
		if(!$params) { // Si no ha coincidido con ninguna action, buscamos en páginas estáticas
			$params = parent::match();
		}
		return $params;
	}
	
	public function assemble($data = [], $reset = false, $encode = false) {
		 if(isset($data['action'])) {
			 if(isset($this->_actions[$data['action']]['path'])) {
				$path = $this->getActionPath($this->_actions[$data['action']]['path']);
				unset($data['action']);
				return empty($data)
					? $path
					: $path . '?' . http_build_query($data);
			}
			return false;
		}
		return parent::assemble($data, $reset, $encode);
	}

	public function getActionPath($path) {
		return (substr($path, 0, 1) == '/')
			? $path
			: rtrim(parent::assemble() . '/' . $path, '/');
	}
	
	public function addActions(array $actions = []) {
		foreach ($actions as $key => $value) {
			$this->_actions[$key] = $value;
		}
	}
}