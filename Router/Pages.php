<?php declare(strict_types = 1);
/**
 * Proporciona enrutamiento estatico mediante la coincidencia con la ruta
 *
 * @package Kansas
 * @author Marcos Porto
 * @copyright Marcos Porto
 * @since v0.4
 * PHP 7 >= 7.2
 */

namespace Kansas\Router;

use Kansas\Router;

require_once 'Kansas/Router.php';

class Pages extends Router {

	// Miembros de System\Configurable\ConfigurableInterface
    public function getDefaultOptions(string $environment) : array {
        return [
            'base_path' => '',
            'pages'     => [],
            'params'    => []
        ];
    }

    public function match() {
		$params = false;
        $path   = self::getPath($this);
    
        $pages = $this->options['pages'];
        if($path == '' && isset($pages['.'])) {
            $params = $this->getParams($pages['.']);
        } elseif(isset($pages[$path])) {
            $params = $this->getParams($pages[$path]);
        }
            
        return $params;
    }

	public function setRoute(string $page, array $params) : void {
		 $this->options['pages'][$page] = $params;
	}
}