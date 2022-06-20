<?php declare(strict_types = 1);
/**
 * Plugin para depuración mediante Maurina.
 *
 * @package Kansas
 * @author Marcos Porto
 * @copyright 2022, Marcos Porto
 * @since v0.4
 */

namespace Kansas\Plugin;

use Throwable;
use Kansas\Plugin\PluginInterface;
use Maurina\Debug as MaurinaDebug;
use Psr\Log\LoggerTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use System\Configurable;
use System\Version;
use function System\String\interpolate as StringInterpolate;

require_once 'Kansas/Plugin/PluginInterface.php';
require_once 'Psr/Log/LoggerInterface.php';
require_once 'Psr/Log/LoggerTrait.php';
require_once 'System/Configurable.php';

class SocketLogger extends Configurable implements PluginInterface, LoggerInterface {
    use LoggerTrait;

    private $maurina;

	public function __construct(array $options) {
        global $application;
		parent::__construct($options);
        ini_set('display_errors', '1');
        ini_set('display_startup_errors', '1');
        error_reporting(E_ALL);
        require_once 'Maurina/Debug.php';
        $this->maurina = new MaurinaDebug($this->options['server_ip'], $this->options['server_port'], $this->options['tab_captions']);
        $logger = $application->getPlugin('Logger');
        $logger->addLogger($this);
	}

	// Miembros de System\Configurable\ConfigurableInterface
	public function getDefaultOptions(string $environment) : array {
        return [
            'server_ip'     => '127.0.0.1', 
            'server_port'   => null, 
            'tab_captions'  => ['&Usuario', '&Errores', '&Solicitudes', '&Sesión']];
	}

    // Miembros de PluginInterface
	public function getVersion() : Version {
		global $environment;
		return $environment->getVersion();
	}

    public function trackError(Throwable $ex) {
        $this->maurina->exceptionHandler($ex);
    }

    // Miembros de Psr\Log\LoggerInterface
    public function log(string $level, $message, array $context = []) {
        if($level == LogLevel::ERROR &&
           isset($context['exception']) &&
           is_a($context['exception'], 'Throwable')) {
            $this->maurina->exceptionHandler($context['exception']);
            if(is_string($message) &&
               $message == $context['exception']->getMessage()) {
                return;
            }
        }
        if(is_string($message) &&
           is_array($context)) {
            require_once 'System/String/interpolate.php';
            $message = StringInterpolate($message, $context);
        }
        $this->maurina->log($level, $message, $context);
    }

}