<?php declare(strict_types = 1);
/**
 * Implementación de Psr\Log\LoggerInterface
 *
 * @package Kansas
 * @author Marcos Porto
 * @copyright 2022, Marcos Porto
 * @since v0.5
 */

namespace Kansas\Plugin;

use Throwable;
use Kansas\Plugin\PluginInterface;
use Psr\Log\LoggerTrait;
use Psr\Log\LoggerInterface;
use System\Configurable;
use System\Version;

require_once 'Kansas/Plugin/PluginInterface.php';
require_once 'Psr/Log/LoggerInterface.php';
require_once 'Psr/Log/LoggerTrait.php';
require_once 'System/Configurable.php';

class Logger extends Configurable implements PluginInterface, LoggerInterface {
    use LoggerTrait;

    private $loggers = [];

    // Miembros de System\Configurable\ConfigurableInterface
    public function getDefaultOptions(string $environment) : array {
        return [];
    }

    // Miembros de PluginInterface
    public function getVersion() : Version {
        global $environment;
        return $environment->getVersion();
    }


    // Miembros de Psr\Log\LoggerInterface
    public function log(string $level, $message, array $context = []) {
        foreach($this->loggers as $logger) {
            try {
                $logger->log($level, $message, $context);
            } catch(Throwable $ex) {
                ;
            }
        }
    }

    public function addLogger(LoggerInterface $logger) {
        $this->loggers[] = $logger;
    }

}
