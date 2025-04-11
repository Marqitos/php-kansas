<?php
/**
  * Plugin para causar una redirección en caso de error 404
  *
  * @package    Kansas
  * @author     Marcos Porto Mariño
  * @copyright  2025, Marcos Porto <lib-kansas@marcospor.to>
  * @since      v0.4
  */

namespace Kansas\Plugin;

use Exception;
use System\Configurable;
use System\EnvStatus;
use System\NotSupportedException;
use System\Version;
use Kansas\Environment;
use Kansas\Plugin\PluginInterface;
use Kansas\View\Result\Redirect;

require_once 'System/Configurable.php';
require_once 'Kansas/Plugin/PluginInterface.php';

class RedirectionError extends Configurable implements PluginInterface {

    private $next;

    public function __construct(array $options) {
        parent::__construct($options);
        global $application;
        try {
            $this->next = $application->getOptions()['error'];
        } catch(Exception $ex) {
            $this->next = [$application, 'errorManager'];
        }
        $application->setOption('error', [$this, 'errorManager']);
    }

    /// Miembros de Kansas_Module_Interface
    public function getDefaultOptions(EnvStatus $environment) : array {
        return [
            'basePath' => '',
            'append'   => true];
    }

    public function getVersion() : Version {
        return Environment::getVersion();
    }

    public function errorManager($params) {
        if ($params['code'] == 404 &&
            $path = $this->options['basePath']) {
            if($this->options['append']) {
                $path = rtrim($path, '/') . Environment::getRequest()->getRequestUri();
            }
            require_once 'Kansas/View/Result/Redirect.php';
            $result = Redirect::gotoUrl($path);
            $result->executeResult();
        } else {
            call_user_func($this->next, $params);
        }
    }

}
