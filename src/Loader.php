<?php
/**
 * Based on Zend Framework 2.0 / Zend_Loader
 */

namespace Kansas;

use System\ArgumentOutOfRangeException;
use System\IO\FileNotFoundException;
use function basename;
use function call_user_func;
use function class_exists;
use function dirname;
use function explode;
use function interface_exists;
use function is_array;
use function is_string;
use function ltrim;
use function preg_match;
use function rtrim;
use function spl_autoload_register;
use function str_replace;
use function strripos;
use function substr;

/**
 * Static methods for loading classes and files.
 */
class Loader {
	
    /**
     * @var Loader Singleton instance
     */
    protected static $instance;

    /**
     * @var bool Whether or not to suppress file not found warnings
     */
    protected $suppressNotFoundWarnings = false;

    /**
     * Retrieve singleton instance
     *
     * @return Kansas\Loader
     */
    public static function autoload() {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }


    /**
     * Get or set the value of the "suppress not found warnings" flag
     *
     * @param  null|bool $flag
     * @return bool|Loader Returns boolean if no argument is passed, object instance otherwise
     */
    public function suppressNotFoundWarnings($flag = null) {
        if (null === $flag) {
            return $this->suppressNotFoundWarnings;
        }
        $this->suppressNotFoundWarnings = (bool) $flag;
        return $this;
    }

    /**
     * Constructor
     *
     * Registers instance with spl_autoload stack
     *
     * @return void
     */
    protected function __construct() {
        spl_autoload_register([__CLASS__, 'loadClass']);
    }

    /**
     * Internal autoloader implementation
     *
     * @param  string $class
     * @return bool
     */
    protected function _autoload($class) {
        require_once 'System/ArgumentOutOfRangeException.php';
        $callback = [__CLASS__, 'loadClass'];
        try {
            if ($this->suppressNotFoundWarnings()) {
                @call_user_func($callback, $class);
            } else {
                call_user_func($callback, $class);
            }
            return $class;
        } catch (ArgumentOutOfRangeException $e) {
            return false;
        }
    }
	
	
    /**
     * Loads a class from a PHP file.  The filename must be formatted
     * as "$class.php".
     *
     * If $dirs is a string or an array, it will search the directories
     * in the order supplied, and attempt to load the first matching file.
     *
     * If $dirs is null, it will split the class name at underscores to
     * generate a path hierarchy (e.g., "Zend_Example_Class" will map
     * to "Zend/Example/Class.php").
     *
     * If the file was not found in the $dirs, or if no $dirs were specified,
     * it will attempt to load it from PHP's include_path.
     *
     * @param string $class      - The full class name of a Zend component.
     * @param string|array $dirs - OPTIONAL Either a path or an array of paths
     *                             to search.
     * @return void
     * @throws ArgumentOutOfRangeException
     */
    public static function loadClass($class, $dirs = null) {
        if (class_exists($class, false) || 
            interface_exists($class, false)) {
            return;
        }

        if ($dirs !== null && 
            !is_string($dirs) && 
            !is_array($dirs)) {
            require_once 'System/ArgumentOutOfRangeException.php';
            throw new ArgumentOutOfRangeException('dirs', 'Debe ser una cadena de texto o un array');
        }

        // Autodiscover the path from the class name
        // Implementation is PHP namespace-aware, and based on
        // Framework Interop Group reference implementation:
        // http://groups.google.com/group/php-standards/web/psr-0-final-proposal
        $className = ltrim($class, '\\');
        $file      = '';
        $namespace = '';
        if ($lastNsPos = strripos($className, '\\')) {
            $namespace = substr($className, 0, $lastNsPos);
            $className = substr($className, $lastNsPos + 1);
            $file      = str_replace('\\', DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
        }
        $file .= str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';

        if (!empty($dirs)) {
            // use the autodiscovered path
            $dirPath = dirname($file);
            if (is_string($dirs)) {
                $dirs = explode(PATH_SEPARATOR, $dirs);
            }
            foreach ($dirs as $key => $dir) {
                if ($dir == '.') {
                    $dirs[$key] = $dirPath;
                } else {
                    $dir = rtrim($dir, '\\/');
                    $dirs[$key] = $dir . DIRECTORY_SEPARATOR . $dirPath;
                }
            }
            $file = basename($file);
            self::loadFile($file, $dirs, true);
        } else {
            self::loadFile($file, null, true);
        }

    }

    /**
     * Loads a PHP file.  This is a wrapper for PHP's include() function.
     *
     * $filename must be the complete filename, including any
     * extension such as ".php".  Note that a security check is performed that
     * does not permit extended characters in the filename.  This method is
     * intended for loading Zend Framework files.
     *
     * If $dirs is a string or an array, it will search the directories
     * in the order supplied, and attempt to load the first matching file.
     *
     * If the file was not found in the $dirs, or if no $dirs were specified,
     * it will attempt to load it from PHP's include_path.
     *
     * If $once is TRUE, it will use include_once() instead of include().
     *
     * @param  string        $filename
     * @param  string|array  $dirs - OPTIONAL either a path or array of paths
     *                       to search.
     * @param  boolean       $once
     * @return boolean
     * @throws ArgumentOutOfRangeException
     */
    public static function loadFile($filename, $dirs = null, $once = false) {
        self::_securityCheck($filename);

        /**
         * Search in provided directories, as well as include_path
         */
        $incPath = false;
        if (!empty($dirs) &&
            (is_array($dirs) || 
             is_string($dirs))) {
            if (is_array($dirs)) {
                $dirs = implode(PATH_SEPARATOR, $dirs);
            }
            $incPath = get_include_path();
            set_include_path($dirs . PATH_SEPARATOR . $incPath);
        }

        /**
         * Try finding for the plain filename in the include_path.
         */
        $reporting = error_reporting();
        error_reporting(0);

        if ($once) {
            include_once($filename);
        } else {
            include($filename);
        }

        error_reporting($reporting);

        /**
         * If searching in directories, reset include_path
         */
        if ($incPath) {
            set_include_path($incPath);
        }

        return true;
    }

    /**
     * Returns TRUE if the $filename is readable, or FALSE otherwise.
     * This function uses the PHP include_path, where PHP's is_readable()
     * does not.
     *
     * Note from ZF-2900:
     * If you use custom error handler, please check whether return value
     *  from error_reporting() is zero or not.
     * At mark of fopen() can not suppress warning if the handler is used.
     *
     * @param string   $filename
     * @return boolean
     */
    public static function isReadable(string $filename) {
        if (is_readable($filename)) { // Return early if the filename is readable without needing the
            return true;              // include_path
        }

        if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN' &&
            preg_match('/^[a-z]:/i', $filename)) {  // If on windows, and path provided is clearly an absolute path,
            return false;                           // return false immediately
        }

        foreach (self::explodeIncludePath() as $path) {
            if ($path == '.') {
                continue;
            }
            $file = $path . '/' . $filename;
            if (is_readable($file)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Explode an include path into an array
     *
     * If no path provided, uses current include_path. Works around issues that
     * occur when the path includes stream schemas.
     *
     * @param  string|null $path
     * @return array
     */
    public static function explodeIncludePath($path = null) {
        if (null === $path) {
            $path = get_include_path();
        }

        if (PATH_SEPARATOR == ':') { // On *nix systems, include_paths which include paths with a stream schema cannot be safely explode'd, so we have to be a bit more intelligent in the approach.
            $paths = preg_split('#:(?!//)#', $path);
        } else {
            $paths = explode(PATH_SEPARATOR, $path);
        }
        return $paths;
    }


    /**
     * Ensure that filename does not contain exploits
     *
     * @param  string $filename
     * @return void
     * @throws FileNotFoundException
     */
    protected static function _securityCheck($filename) {
        if (preg_match('/[^a-z0-9\\/\\\\_.:-]/i', $filename)) {
            require_once 'System/IO/FileNotFoundException.php';
            throw new FileNotFoundException('Comprobación de seguridad: Caracteres no admitidos en el nombre de archivo');
        }
    }

}
