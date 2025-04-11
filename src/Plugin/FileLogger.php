<?php declare(strict_types = 1);
/**
  * Plugin para el guardado de logs en archivo
  *
  * @package    Kansas
  * @author     Marcos Porto Mariño
  * @copyright  2025, Marcos Porto <lib-kansas@marcospor.to>
  * @since      v0.5
  * @version    v0.6
  */

namespace Kansas\Plugin;

use DateTime;
use Throwable;
use Kansas\Application;
use Kansas\Environment;
use Kansas\Plugin\Logger;
use Kansas\Plugin\PluginInterface;
use Psr\Log\LoggerTrait;
use Psr\Log\LogLevel;
use Psr\Log\LoggerInterface;
use System\Configurable;
use System\EnvStatus;
use System\Version;
use function System\String\interpolate as StringInterpolate;
use function error_get_last;
use function join;
use function get_class;
use function register_shutdown_function;
use function set_error_handler;
use function set_exception_handler;
use function str_replace;
use const LOCK_EX;

require_once 'Kansas/Plugin/PluginInterface.php';
require_once 'Psr/Log/LoggerInterface.php';
require_once 'Psr/Log/LoggerTrait.php';
require_once 'System/Configurable.php';

class FileLogger extends Configurable implements PluginInterface, LoggerInterface {
    use LoggerTrait;

    /// Constructor
    public function __construct(array $options) {
        global $application;
        parent::__construct($options);

        if ($this->options['log_errors']) { // Activo el registro de eventos de errores
            error_reporting($this->options['error_level']);
            set_error_handler([$this, 'errorHandler']);
            set_exception_handler([$this, 'exceptionHandler']);
            $application->registerCallback(Application::EVENT_ERROR, [$this, 'exceptionHandler']);
            $application->registerCallback(Application::EVENT_DISPOSE, [$this, "appShutdown"]);
        }

        $logger = $application->getPlugin('Logger');
        $logger->addLogger($this);
    }

    # Miembros de ConfigurableInterface
    public function getDefaultOptions(EnvStatus $environment) : array {
        require_once 'Kansas/Plugin/Logger.php';
        return [
            'format'        => Logger::FORMAT_HTML,
            'log_errors'    => true,
            'log_sessions'  => false,
            'log_hints'     => false,
            'error_level'   => error_reporting()];
    }
    # --

    # Miembros de PluginInterface
    public function getVersion() : Version {
        return Environment::getVersion();
    }
    # --

    // Eventos de errores
    public function errorHandler(int $errno, string $errstr, string $errfile, int $errline): bool {
        $types = Logger::getErrorTypes($errno);
        $message = Logger::formatError($this->options['format'], $types, $errstr, $errfile, $errline);
        $this->writeError($message, true);
        return true;
    }

    public function exceptionHandler(Throwable $ex) {
        $message = Logger::formatError($this->options['format'], get_class($ex), $ex->getMessage(), $ex->getFile(), $ex->getLine(), $ex->getTraceAsString());
        $this->writeError($message, true);
    }

    public function appShutdown() {
        if ($error = error_get_last()) {
            $this->errorHandler(
                $error['type'],
                $error['message'],
                $error['file'],
                $error['line']
            );
        }
    }


    public function writeError(string $message, $showTime = false) {
        if ($showTime) {
            $time = '[' . date('H:i:s') . '] ';
            $message = $time . $message;
        }

        ignore_user_abort();
        $logFile = Environment::getSpecialFolder(Environment::SF_ERRORS) . 'errors.html';

        $stderr = fopen($logFile, 'a');
        flock($stderr, LOCK_EX);
        fwrite($stderr, $message);
        fclose($stderr);
    }

    public function log(string $level, $message, array $context = []) {
        if ($level == LogLevel::ERROR &&
            is_string($message) &&
            isset($context['exception']) &&
            is_a($context['exception'], 'Throwable')) { // Código especifico para registrar errores
            $this->exceptionHandler($context['exception']);
        }

        if (gettype($message) == 'array' || gettype($message) == 'object') {
            $message = $this->options['format'] == Logger::FORMAT_HTML
                ? $this->formatDump(print_r($message, true))
                : print_r($message, true);
        }
        if (gettype($message) == 'boolean') {
            $message = ($message) ? 'true' : 'false';
        }
        if (gettype($message) == 'NULL') {
            $message = 'NULL';
        }
        if (!empty($context)) {
            require_once 'System/String/interpolate.php';
            $message = StringInterpolate($message, $context);
        }

        $now = new DateTime('now');
        switch ($this->options['format']) {
            case Logger::FORMAT_MARKDOWN:
                $logFile = Environment::getSpecialFolder(Environment::SF_ERRORS) . $now->format('Ymd') . '.md';
                break;
            case Logger::FORMAT_HTML:
            default:
                $logFile = Environment::getSpecialFolder(Environment::SF_ERRORS) . $now->format('Ymd') . '.html';
                break;
        }

        ignore_user_abort();
        $stderr = fopen($logFile, 'a');
        flock($stderr, LOCK_EX);
        switch ($this->options['format']) {
            case Logger::FORMAT_MARKDOWN:
                fwrite($stderr, strtoupper($level) . ": $message\r\n");
                break;
            case Logger::FORMAT_HTML:
            default:
                fwrite($stderr, strtoupper($level) . ": $message<br>\r\n");
                break;
        }
        fclose($stderr);
    }

    protected function formatDump($data) {
        $data = htmlentities($data);
        $data = str_replace("  ", "<span style='color:#fff'>__</span>", $data);

        return $data;
    }

}
