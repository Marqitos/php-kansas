<?php
/**
 * @see       https://github.com/zendframework/zend-loader for the canonical source repository
 */

namespace Kansas;

use Traversable;
use System\ArgumentOutOfRangeException;
use System\Configurable;
use Kansas\Loader\SplInterface;

require_once "System/Configurable.php";
require_once "Kansas/Loader/SplInterface.php";

/**
 * PSR-0 compliant autoloader
 *
 * Allows autoloading both namespaced and vendor-prefixed classes. Class
 * lookups are performed on the filesystem. If a class file for the referenced
 * class is not found, a PHP warning will be raised by include().
 */
class Autoloader extends Configurable implements SplInterface {
    const NS_SEPARATOR     = '\\';
    const PREFIX_SEPARATOR = '_';
    const LOAD_NS          = 'namespaces';
    const LOAD_PREFIX      = 'prefixes';
    const ACT_AS_FALLBACK  = 'fallback_autoloader';

    /**
     * @var array Namespace/directory pairs to search; ZF library added by default
     */
    protected $namespaces = [];

    /**
     * @var array Prefix/directory pairs to search
     */
    protected $prefixes = [];

    /**
     * Constructor
     *
     * @param  null|array|\Traversable $options
     */
    public function __construct($options = []) {
        parent::__construct($options);
    }

	// Miembros de System\Configurable\ConfigurableInterface
    public function getDefaultOptions(string $environment) : array {
        return [
            self::LOAD_NS         => [],
            self::LOAD_PREFIX     => [],
            self::ACT_AS_FALLBACK => false];
        
    }
    /**
     * Configure autoloader
     *
     *
     * @param  string $key
     * @param  mixed  $value
     * @throws Exception\InvalidArgumentException
     * @return StandardAutoloader
     */
    public function setOption($key, $value) : void {
        switch ($key) {
            case self::LOAD_NS:
                if (is_array($value) || $value instanceof Traversable) {
                    $this->registerNamespaces($value);
                }
                break;
            case self::LOAD_PREFIX:
                if (is_array($value) || $value instanceof Traversable) {
                    $this->registerPrefixes($value);
                }
                break;
            default:
                parent::setOption($key, $value);
                break;
        }
    }

    /**
     * Set flag indicating fallback autoloader status
     *
     * @param  bool $flag
     * @return StandardAutoloader
     */
    public function setFallbackAutoloader(bool $flag) {
        $this->options[self::ACT_AS_FALLBACK] = $flag;
        return $this;
    }

    /**
     * Is this autoloader acting as a fallback autoloader?
     *
     * @return bool
     */
    public function isFallbackAutoloader() : bool {
        return $this->options[self::ACT_AS_FALLBACK];
    }

    /**
     * Register a namespace/directory pair
     *
     * @param  string $namespace
     * @param  string $directory
     * @return StandardAutoloader
     */
    public function registerNamespace(string $namespace, string $directory) : self {
        $namespace = rtrim($namespace, self::NS_SEPARATOR) . self::NS_SEPARATOR;
        $this->namespaces[$namespace] = $this->normalizeDirectory($directory);
        return $this;
    }

    /**
     * Register many namespace/directory pairs at once
     *
     * @param  array $namespaces
     * @throws ArgumentOutOfRangeException
     * @return Autoloader
     */
    public function registerNamespaces($namespaces) {
        if (!is_array($namespaces) && !$namespaces instanceof Traversable) {
            require_once "System/ArgumentOutOfRangeException.php";
            throw new ArgumentOutOfRangeException('callback', 'Se esperaba un iterable', $namespaces);
        }

        foreach ($namespaces as $namespace => $directory) {
            $this->registerNamespace($namespace, $directory);
        }
        return $this;
    }

    /**
     * Register a prefix/directory pair
     *
     * @param  string $prefix
     * @param  string $directory
     * @return StandardAutoloader
     */
    public function registerPrefix($prefix, $directory) {
        $prefix = rtrim($prefix, self::PREFIX_SEPARATOR). self::PREFIX_SEPARATOR;
        $this->prefixes[$prefix] = $this->normalizeDirectory($directory);
        return $this;
    }

    /**
     * Register many namespace/directory pairs at once
     *
     * @param  array $prefixes
     * @throws ArgumentOutOfRangeException
     * @return Autoloader
     */
    public function registerPrefixes($prefixes) {
        if (!is_array($prefixes) && !$prefixes instanceof Traversable) {
            require_once "System/ArgumentOutOfRangeException.php";
            throw new ArgumentOutOfRangeException('callback', 'Se esperaba un iterable', $namespaces);
        }

        foreach ($prefixes as $prefix => $directory) {
            $this->registerPrefix($prefix, $directory);
        }
        return $this;
    }

    /**
     * Defined by Autoloadable; autoload a class
     *
     * @param  string $class
     * @return false|string
     */
    public function autoload($class) {
        $isFallback = $this->isFallbackAutoloader();
        if (false !== strpos($class, self::NS_SEPARATOR)) {
            if ($this->loadClass($class, self::LOAD_NS)) {
                return $class;
            } elseif ($isFallback) {
                return $this->loadClass($class, self::ACT_AS_FALLBACK);
            }
            return false;
        }
        if (false !== strpos($class, self::PREFIX_SEPARATOR)) {
            if ($this->loadClass($class, self::LOAD_PREFIX)) {
                return $class;
            } elseif ($isFallback) {
                return $this->loadClass($class, self::ACT_AS_FALLBACK);
            }
            return false;
        }
        if ($isFallback) {
            return $this->loadClass($class, self::ACT_AS_FALLBACK);
        }
        return false;
    }

    /**
     * Register the autoloader with spl_autoload
     *
     * @return void
     */
    public function register() {
        spl_autoload_register([$this, 'autoload']);
    }

    /**
     * Transform the class name to a filename
     *
     * @param  string $class
     * @param  string $directory
     * @return string
     */
    protected function transformClassNameToFilename($class, $directory) {
        // $class may contain a namespace portion, in  which case we need
        // to preserve any underscores in that portion.
        $matches = [];
        preg_match('/(?P<namespace>.+\\\)?(?P<class>[^\\\]+$)/', $class, $matches);

        $class     = (isset($matches['class'])) ? $matches['class'] : '';
        $namespace = (isset($matches['namespace'])) ? $matches['namespace'] : '';

        return $directory
             . str_replace(self::NS_SEPARATOR, '/', $namespace)
             . str_replace(self::PREFIX_SEPARATOR, '/', $class)
             . '.php';
    }

    /**
     * Load a class, based on its type (namespaced or prefixed)
     *
     * @param  string $class
     * @param  string $type
     * @return bool|string
     * @throws Exception\InvalidArgumentException
     */
    protected function loadClass($class, $type) {
        if (!in_array($type, [self::LOAD_NS, self::LOAD_PREFIX, self::ACT_AS_FALLBACK])) {
            require_once "System/ArgumentOutOfRangeException.php";
            throw new ArgumentOutOfRangeException('type');
        }

        // Fallback autoloading
        if ($type === self::ACT_AS_FALLBACK) {
            // create filename
            $filename     = $this->transformClassNameToFilename($class, '');
            $resolvedName = stream_resolve_include_path($filename);
            if ($resolvedName !== false) {
                return include $resolvedName;
            }
            return false;
        }

        // Namespace and/or prefix autoloading
        foreach ($this->$type as $leader => $path) {
            if (0 === strpos($class, $leader)) {
                // Trim off leader (namespace or prefix)
                $trimmedClass = substr($class, strlen($leader));

                // create filename
                $filename = $this->transformClassNameToFilename($trimmedClass, $path);
                if (file_exists($filename)) {
                    return include $filename;
                }
            }
        }
        return false;
    }

    /**
     * Normalize the directory to include a trailing directory separator
     *
     * @param  string $directory
     * @return string
     */
    protected function normalizeDirectory($directory) {
        $last = $directory[strlen($directory) - 1];
        if (in_array($last, ['/', '\\'])) {
            $directory[strlen($directory) - 1] = DIRECTORY_SEPARATOR;
            return $directory;
        }
        $directory .= DIRECTORY_SEPARATOR;
        return $directory;
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
    public static function isReadable(string $filename) : bool {
        if(is_readable($filename)) { // Return early if the filename is readable without needing the
            return true;            // include_path
        }

        if(strtoupper(substr(PHP_OS, 0, 3)) == 'WIN' &&
           preg_match('/^[a-z]:/i', $filename)) { // If on windows, and path provided is clearly an absolute path,
            return false;                         // return false immediately
        }

        foreach (self::explodeIncludePath() as $path) {
            if($path == '.') {
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
    public static function explodeIncludePath(string $path = null) {
        if(null === $path) {
            $path = get_include_path();
        }

        return (PATH_SEPARATOR == ':') // On *nix systems, include_paths which include paths with a stream schema cannot be safely explode'd, so we have to be a bit more intelligent in the approach.
            ? preg_split('#:(?!//)#', $path)
            : explode(PATH_SEPARATOR, $path);
    }

}
