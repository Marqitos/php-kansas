<?php
/**
 * Proporciona enrutamiento estatico mediante la coincidencia con la ruta
 *
 * @package Kansas
 * @author Marcos Porto
 * @copyright Marcos Porto
 * @since v0.4
 */

namespace Kansas\Router;

use Kansas\Router;

require_once 'Kansas/Router.php';

class Pages extends Router {

	// Miembros de System\Configurable\ConfigurableInterface
    public function getDefaultOptions($environment) : array {
        return [
            'pages' => [],
            'params' => [],
            'base_path' => ''
        ];
    }

    public function match() {
		global $environment;
		$params = false;
		$path = trim($environment->getRequest()->getUri()->getPath(), '/');
    
        $pages = $this->options['pages'];
        if($path == '' && isset($pages['.'])) {
            $params = $this->getParams($pages['.']);
        } elseif(isset($pages[$path])) {
            $params = $this->getParams($pages[$path]);
        }
            
        return $params;
    }

	public function setRoute($page, $params) {
		 $this->options['pages'][$page] = $params;
	}
}