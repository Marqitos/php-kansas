<?php
/**
 * Plugin para el guardado de logs en disco
 *
 * @package Kansas
 * @author Marcos Porto
 * @copyright 2022, Marcos Porto
 * @since v0.5
 */

 // TODO: Cambiar modo de escritura a markdown

namespace Kansas\Plugin;

use Throwable;
use Kansas\Environment;
use Kansas\Plugin\PluginInterface;
use Psr\Log\LoggerTrait;
use Psr\Log\LogLevel;
use Psr\Log\LoggerInterface;
use System\Configurable;
use System\Version;
use function System\String\interpolate as StringInterpolate;
use function error_get_last;
use function get_class;
use function register_shutdown_function;
use function set_error_handler;
use function set_exception_handler;
use function str_replace;
use const LOCK_EX;

require_once 'System/Configurable.php';
require_once 'Kansas/Plugin/PluginInterface.php';

class FileLogger extends Configurable implements PluginInterface, LoggerInterface {
    use LoggerTrait;
  
    /// Constructor
    public function __construct(array $options) {
        global $application;
		parent::__construct($options);

        if($this->options['log_errors']) { // Activo el registro de eventos de errores
            ini_set('display_errors', '1');
            ini_set('display_startup_errors', '1');
            error_reporting($this->options['error_level']);
            set_error_handler([$this, "errorHandler"]);
            set_exception_handler([$this, "exceptionHandler"]);
            register_shutdown_function([$this, "shutdown"]);
        }

        $logger = $application->getPlugin('Logger');
        $logger->addLogger($this);
    }

    /// Miembros de ConfigurableInterface
    public function getDefaultOptions(string $environment) : array {
        return [
            'log_errors'    => true,
            'log_sessions'  => false,
            'log_hints'     => false,
            'error_level'   => error_reporting()];
    }

    /// Miembros de PluginInterface
    public function getVersion() : Version {
        global $environment;
        return $environment->getVersion();
    }

    // Eventos de errores
    public function errorHandler(int $errorNumber, $errorMsg, $errorFile, $errorLine) {
        $type = null;
        if((E_USER_DEPRECATED & $errorNumber) == E_USER_DEPRECATED) {
            $type = 'E_USER_DEPRECATED';
        }
        if((E_DEPRECATED & $errorNumber) == E_DEPRECATED) {
            $type = 'E_DEPRECATED';
        }
        if(E_RECOVERABLE_ERROR & $errorNumber == E_RECOVERABLE_ERROR) {
            $type = 'E_RECOVERABLE_ERROR';
        }
        if(E_STRICT & $errorNumber == E_STRICT) {
            $type = 'E_STRICT';
        }
        if(E_USER_NOTICE & $errorNumber == E_USER_NOTICE) {
            $type = 'E_USER_NOTICE';
        }
        if(E_USER_WARNING & $errorNumber == E_USER_WARNING) {
            $type = 'E_USER_WARNING';
        }
        if(E_USER_ERROR & $errorNumber == E_USER_ERROR) {
            $type = 'E_USER_ERROR';
        }
        if(E_USER_WARNING & $errorNumber == E_USER_WARNING) {
            $type = 'E_USER_WARNING';
        }
        if(E_COMPILE_WARNING & $errorNumber == E_COMPILE_WARNING) {
            $type = 'E_COMPILE_WARNING';
        }
        if(E_COMPILE_ERROR & $errorNumber == E_COMPILE_ERROR) {
            $type = 'E_COMPILE_ERROR';
        }
        if(E_CORE_WARNING & $errorNumber == E_CORE_WARNING) {
            $type = 'E_CORE_WARNING';
        }
        if(E_CORE_ERROR & $errorNumber == E_CORE_ERROR) {
            $type = 'E_CORE_ERROR';
        }
        if(E_NOTICE & $errorNumber == E_NOTICE) {
            $type = 'E_NOTICE';
        }
        if(E_PARSE & $errorNumber == E_PARSE) {
            $type = 'E_PARSE';
        }
        if(E_WARNING & $errorNumber == E_WARNING) {
            $type = 'E_WARNING';
        }
        if(E_ERROR & $errorNumber == E_ERROR) {
            $type = 'E_ERROR';
        }
        if(E_ALL & $errorNumber == E_ALL) {
            $type = 'E_ALL';
        }
        if($type == null) {
            return;
        }

        $message  = "<span style='color:red'>[$type] Line $errorLine in $errorFile</span><br />";
        $message .= "<span style='color:#DE2500'><em>";
        $message .= $this->getLineFromFile($errorFile, $errorLine);
        $message .= "</em></span><br />$errorMsg";

        $this->writeError($message, true, LogLevel::ERROR);
        return true;
    }

    public function exceptionHandler(Throwable $ex) {
        $className = get_class($ex);
        $message  = "<span style='color:red'>[$className] ";
        $message .= "Line " . $ex->getLine() . " in " . $ex->getFile();
        $message .= "</span><br /><span style='color:#DE2500'><em>";
        $message .= $this->getLineFromFile($ex->getFile(), $ex->getLine());
        $message .= "</em></span><br />" . $ex->getMessage();
        $message .= str_replace('#', '<br />#' , $ex->getTraceAsString());

        $this->writeError($message, true, LogLevel::ERROR);
    }

    public function shutdown() {
        if ($error = error_get_last()) {
            $this->errorHandler($error['type'], $error['message'],
                                $error['file'], $error['line']);
        }
    }

    public function getLineFromFile($file, $lineNumber)	{
        $line = null;
        if(file_exists($file)) {
            $f = fopen($file, 'r');
            if($f) {
                $count = 1;
                while (($line = fgets($f)) !== false) {
                    if ($count == $lineNumber) {
                        break;
                    }
                    ++$count;
                }
            }
        }
        return $line;
    }

    public function writeError(string $message, $showTime = false, string $level = LogLevel::INFO)	{
        global $environment;
        if ($showTime) {
            $time = "[".date('H:i:s') . ']';
            $message = $time . $message;
        }

        ignore_user_abort();
        $logFile = $environment->getSpecialFolder(Environment::SF_ERRORS) . 'errors.html';

        $stderr = fopen($logFile, 'a');
        flock($stderr, LOCK_EX);
        fwrite($stderr, $message . '<br/>');
        fclose($stderr);
    }

    public function log(string $level, $message, array $context = []) {
        global $environment;
        if($level == LogLevel::ERROR &&
           is_string($message) &&
           isset($context['exception']) &&
           is_a($context['exception'], 'Throwable')) { // Codigo especifico para registrar errores
            $this->exceptionHandler($context['exception']);
        }

        if (gettype($message) == 'array' || gettype($message) == 'object') {
            $message = '<br />' . $this->formatDump(print_r($message, true));
        }
        if (gettype($message) == 'boolean') {
            $message = ($message) ? 'true' : 'false';
        }
        if (gettype($message) == 'NULL') {
            $message = 'NULL';
        }
        if(!empty($context)) {
            require_once 'System/String/interpolate.php';
            $message = StringInterpolate($message, $context);
        }
        $message = nl2br($message);

        ignore_user_abort();
        $logFile = $environment->getSpecialFolder(Environment::SF_ERRORS) . 'log.html';

        $stderr = fopen($logFile, 'a');
        flock($stderr, LOCK_EX);
        fwrite($stderr, strtoupper($level) . ':<br/>');
        fwrite($stderr, $message . '<br/>');
        fclose($stderr);
    }

	protected function formatDump($data) {
		$data = htmlentities($data);
		$data = str_replace("  ", "<span style='color:#fff'>__</span>", $data);

		return $data;
	}


}
