<?php declare(strict_types = 1);
/**
  * Plugin para el uso de tokens JWT
  *
  * @package    Kansas
  * @author     Marcos Porto Mariño
  * @copyright  2025, Marcos Porto <lib-kansas@marcospor.to>
  * @since      v0.4
  */

namespace Kansas\Plugin;

use Kansas\Application;
use Kansas\Environment;
use Kansas\Plugin\PluginInterface;
use Kansas\Configurable;
use System\EnvStatus;
use System\Net\WebException;
use System\Version;

require_once 'Kansas/Configurable.php';
require_once 'Kansas/Plugin/PluginInterface.php';
require_once 'System/Net/WebException.php';

class WebErrors extends Configurable implements PluginInterface {

    public function __construct(array $options) {
        global $application;
        parent::__construct($options);

        $application->registerCallback(Application::EVENT_ERROR, [$this, 'appError']);
    }

## Miembros de Kansas\Configurable
    public function getDefaultOptions(EnvStatus $environment) : array {
        return [
            'http_codes'    => []
        ];
    }
## -- Kansas\Configurable

## Miembros de Kansas\Plugin\PluginInterface
    public function getVersion() : Version {
        return Environment::getVersion();
    }
## --

    public function appError($th) {
        if (is_a($th, 'System\Net\WebException')) {
            $code = $th->getStatus();
            if (isset($this->options['http_codes'][$code])) {
                return $this->options['http_codes'][$code];
            }
        }
    }

}
