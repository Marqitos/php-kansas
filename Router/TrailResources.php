<?php
/**
 * Router que devuelve las imagenes sobre navegadores, sistemas operativos, robots y regiones.
 * Correspondientes a los datos de acceso de dispositivos
 *
 * @package Kansas
 * @author Marcos Porto
 * @copyright Marcos Porto
 * @since v0.4
 */

namespace Kansas\Router;

use Kansas\Router;
use System\NotSupportedException;
use Kansas\Environment;

require_once 'Kansas/Router.php';

class TrailResources extends Router {

	public function __construct(array $options) {
        parent::__construct($options);
    }
    
    /// Miembros de Kansas\Configurable
    public function getDefaultOptions($environmentStatus) : array {
        global $environment;
        switch ($environmentStatus) {
            case 'production':
            case 'development':
            case 'test':
                require_once 'Kansas/Environment.php';
                $libsPath = $environment->getSpecialFolder(Environment::SF_LIBS);
                return [
                    'paths'	=> [
                        'img/browser-' => realpath($libsPath . 'bbClone/images/browser') . '/',
                        'img/ext-'     => realpath($libsPath . 'bbClone/images/ext')     . '/',
                        'img/os-'      => realpath($libsPath . 'bbClone/images/os')      . '/',
                        'img/robot-'   => realpath($libsPath . 'bbClone/images/robot')   . '/'
                    ]
                ];
            default:
                require_once 'System/NotSupportedException.php';
                throw new NotSupportedException("Entorno no soportado [$environmentStatus]");
        }
    }

		
	public function match() {
        global $environment;
        $path = trim($environment->getRequest()->getUri()->getPath(), '/');
        foreach($this->options['paths'] as $requestPath => $realPath) {
            $length = strlen($requestPath);
            if($requestPath == substr($path, 0, $length)) {
                $partial = substr($path, $length);
                if($file = realpath($realPath . $partial . ".png")) {
                    return [
                        'controller'    => 'index',
                        'action'        => 'file',
                        'file'          => $file
                    ];
                }
            }
        }
		return false;
	}
	
}