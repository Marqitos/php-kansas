<?php
/**
  * Router que devuelve las imagenes sobre navegadores, sistemas operativos, robots y regiones.
  * Correspondientes a los datos de acceso de dispositivos
  *
  * @package    Kansas
  * @author     Marcos Porto Mariño
  * @copyright  2025, Marcos Porto <lib-kansas@marcospor.to>
  * @since      v0.4
  */

namespace Kansas\Router;

use Kansas\Environment;
use Kansas\Router;
use System\EnvStatus;
use System\NotSupportedException;

require_once 'Kansas/Router.php';

class TrailResources extends Router {

## Miembros de Kansas\Configurable
    public function getDefaultOptions(EnvStatus $environmentStatus) : array {
        require_once 'Kansas/Environment.php';
        $libsPath = $environment->getSpecialFolder(Environment::SF_LIBS);
        return [
            'paths' => [
                'img/browser-' => realpath($libsPath . 'bbClone/images/browser') . '/',
                'img/ext-'     => realpath($libsPath . 'bbClone/images/ext')     . '/',
                'img/os-'      => realpath($libsPath . 'bbClone/images/os')      . '/',
                'img/robot-'   => realpath($libsPath . 'bbClone/images/robot')   . '/'
            ]
        ];
    }
## -- Configurable

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
