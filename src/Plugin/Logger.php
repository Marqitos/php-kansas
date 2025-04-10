<?php declare(strict_types = 1);
/**
  * Implementación de Psr\Log\LoggerInterface
  *
  * @package    Kansas
  * @author     Marcos Porto Mariño
  * @copyright  2025, Marcos Porto <lib-kansas@marcospor.to>
  * @since      v0.5
  * @version    v0.6
  */

namespace Kansas\Plugin;

// TODO: Implementar formateado de MarkDown

use Throwable;
use Kansas\Localization\Resources;
use Kansas\Plugin\PluginInterface;
use Psr\Log\LoggerTrait;
use Psr\Log\LoggerInterface;
use System\Configurable;
use System\EnvStatus;
use System\Version;

require_once 'Kansas/Plugin/PluginInterface.php';
require_once 'Psr/Log/LoggerInterface.php';
require_once 'Psr/Log/LoggerTrait.php';
require_once 'System/Configurable.php';

class Logger extends Configurable implements PluginInterface, LoggerInterface {
    use LoggerTrait;

    public const FORMAT_HTML        = 'html';
    public const FORMAT_MARKDOWN    = 'md';
    public const FORMAT_JSON        = 'json';

    private $loggers = [];

## Miembros de System\Configurable\ConfigurableInterface
    public function getDefaultOptions(EnvStatus $environment) : array {
        return [];
    }
## --

## Miembros de Kansas\Plugin\PluginInterface
    public function getVersion() : Version {
        global $environment;
        return $environment->getVersion();
    }
## --

## Miembros de Psr\Log\LoggerInterface
    #[SuppressWarnings("php:S108")]
    public function log(string $level, $message, array $context = []) {
        foreach($this->loggers as $logger) {
            try {
                $logger->log($level, $message, $context);
            } catch(Throwable) { }
        }
    }
## --

    public function addLogger(LoggerInterface $logger) {
        $this->loggers[] = $logger;
    }

    public static function formatError(string $format, string $type, string $errorMsg, string $errorFile = '', int $errorLine = 0, string $trace = '') : string {
        require_once 'Kansas/Localization/Resources.php';
        $fileLine = '';
        switch ($format) {
            case self::FORMAT_JSON:

            case self::FORMAT_MARKDOWN:

            case self::FORMAT_HTML:
            default:
                $message  = '<span style="color:red">';
                $message .= "[$type] ";
                if ($errorLine != 0 &&
                    !empty($errorFile)) {
                    $message .= sprintf(Resources::E_LINE_ERROR_FORMAT, $errorLine, $errorFile);
                    $fileLine = self::getLineFromFile($errorFile, $errorLine);
                } elseif (!empty($errorFile)) {
                    $message .= sprintf(Resources::E_FILE_ERROR_FORMAT, $errorFile);
                }
                $message .= "</span><br>\r\n";
                if (!empty($fileLine)) {
                    $message .= '<span style="color:#DE2500"><em>';
                    $message .= $fileLine;
                    $message .= "</em></span><br>\r\n";
                }
                $message .= $errorMsg;
                if (!empty($trace)) {
                    $message   .= str_replace('#', "<br>\r\n#" , $trace);
                }
                $message .= "<br>\r\n";
                break;
        }
        return $message;
    }

    public static function getLineFromFile($file, $lineNumber) : string {
        $line = '';
        if (file_exists($file)) {
            $fHandler = fopen($file, 'r');
            if ($fHandler) {
                $count = 1;
                while (($line = fgets($fHandler)) !== false) {
                    if ($count == $lineNumber) {
                        break;
                    }
                    $count++;
                }
            }
            fclose($fHandler);
        }
        return $line;
    }

    public static function getErrorTypes(int $errorNumber): string|false {
        $coreConstants = get_defined_constants(true)['Core'];
        $types = [];
        foreach ($coreConstants as $errorName => $value) {
            if (str_starts_with($errorName, 'E_') &&
                ($errorNumber & $value) == $value) {
                    $types[] = $errorName;
                }
        }

        return empty($types)
            ? false
            : join(', ', $types);
    }

}
