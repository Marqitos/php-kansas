<?php declare(strict_types = 1);
/**
 * Carga un plugin desde las rutas indicadas
 * 
 * Basado en código de Zend Framework 2.0\PluginLoader
 * 
 * @package Kansas
 * @author Zend Framework 2.0, editado por Marcos Porto
 * @copyright Zend Framework 2.0
 * @since v0.4
 */
namespace Kansas;

use ReflectionClass;
use System\Collections\KeyNotFoundException;
use System\IO\IOException;
use Kansas\Autoloader;
use Kansas\Loader\NotFoundException;

/**
 * Generic plugin class loader
 */
class PluginLoader {
    /**
     * Class map cache file
     * @var string
     */
    protected static $includeFileCache;

    /**
     * Instance loaded plugin paths
     *
     * @var array
     */
    protected $loadedPluginPaths = [];

    /**
     * Instance loaded plugins
     *
     * @var array
     */
    protected $loadedPlugins = [];

    /**
     * Instance registry property
     *
     * @var array
     */
    protected $prefixToPaths = [];

    /**
     * Constructor
     *
     * @param array $prefixToPaths
     */
    public function __construct(array $prefixToPaths = []) {
        foreach ($prefixToPaths as $prefix => $path) {
            $this->addPrefixPath($prefix, $path);
        }
    }

    /**
     * Format prefix for internal use
     *
     * @param  string $prefix
     * @return string
     */
    protected function _formatPrefix(string $prefix) : string {
        if($prefix == "") {
            return $prefix;
        }

        $last = strlen($prefix) - 1;
        if ($prefix[$last] == '\\') {
            return $prefix;
        }

        return rtrim($prefix, '_') . '_';
    }

    /**
     * Add prefixed paths to the registry of paths
     *
     * @param string $prefix
     * @param string $path
     * @return PluginLoader
     */
    public function addPrefixPath(string $prefix, string $path) {
        $prefix = $this->_formatPrefix($prefix);
        $path   = strtr(rtrim($path, '/\\') . '\\', '/\\', DIRECTORY_SEPARATOR);

        if (!isset($this->prefixToPaths[$prefix])) {
            $this->prefixToPaths[$prefix] = [];
        }
        if (!in_array($path, $this->prefixToPaths[$prefix])) {
            $this->prefixToPaths[$prefix][] = $path;
        }
        return $this;
    }

    /**
     * Get path stack
     *
     * @param  string $prefix
     * @return false|array False if prefix does not exist, array otherwise
     */
    public function getPaths(string $prefix = null) {
        if ((null !== $prefix) && is_string($prefix)) {
            $prefix = $this->_formatPrefix($prefix);

            if (isset($this->prefixToPaths[$prefix])) {
                return $this->prefixToPaths[$prefix];
            }

            return false;
        }

        return $this->prefixToPaths;
    }

    /**
     * Clear path stack
     *
     * @param  string $prefix
     * @return bool False only if $prefix does not exist
     */
    public function clearPaths($prefix = null) {
        if ((null !== $prefix) && is_string($prefix)) {
            $prefix = $this->_formatPrefix($prefix);

            if (isset($this->prefixToPaths[$prefix])) {
                unset($this->prefixToPaths[$prefix]);
                return true;
            }

            return false;
        }

        $this->prefixToPaths = [];

        return true;
    }

    /**
     * Remove a prefix (or prefixed-path) from the registry
     *
     * @param string $prefix
     * @param string $path OPTIONAL
     * @return PluginLoader
     * @throws KeyNotFoundException
     */
    public function removePrefixPath(string $prefix, $path = null) {
			$prefix = $this->_formatPrefix($prefix);
			$registry =& $this->prefixToPaths;

			if (!isset($registry[$prefix])) {
				require_once 'System/Collections/KeyNotFoundException.php';
				throw new KeyNotFoundException('Prefix ' . $prefix . ' was not found in the PluginLoader.');
			}

			if ($path != null) {
				$pos = array_search($path, $registry[$prefix]);
				if (false === $pos) {
					require_once 'System/Collections/KeyNotFoundException.php';
					throw new KeyNotFoundException('Prefix ' . $prefix . ' / Path ' . $path . ' was not found in the PluginLoader.');
				}
				unset($registry[$prefix][$pos]);
			} else {
				unset($registry[$prefix]);
			}

			return $this;
    }

    /**
     * Normalize plugin name
     *
     * @param  string $name
     * @return string
     */
    protected function _formatName(string $name) {
        return ucfirst($name);
    }

    /**
     * Whether or not a Plugin by a specific name is loaded
     *
     * @param string $name
     * @return boolean
     */
    public function isLoaded(string $name) {
			$name = $this->_formatName($name);
			return isset($this->loadedPlugins[$name]);
    }

    /**
     * Return full class name for a named plugin
     *
     * @param string $name
     * @return string|false False if class not found, class name otherwise
     */
    public function getClassName(string $name) {
        $name = $this->_formatName($name);
        if (isset($this->loadedPlugins[$name])) {
            return $this->loadedPlugins[$name];
        }

        return false;
    }

    /**
     * Get path to plugin class
     *
     * @param  mixed $name
     * @return string|false False if not found
     */
    public function getClassPath($name) {
			$name = $this->_formatName((string)$name);
			if (!empty($this->loadedPluginPaths[$name])) {
				return $this->loadedPluginPaths[$name];
			}

			if ($this->isLoaded($name)) {
				$class = $this->getClassName($name);
				$r     = new ReflectionClass($class);
				$path  = $r->getFileName();
				$this->loadedPluginPaths[$name] = $path;
				return $path;
			}

			return false;
    }

    /**
     * Load a plugin via the name provided
     *
     * @param  string $name
     * @param  bool $throwExceptions Whether or not to throw exceptions if the
     * class is not resolved
     * @return string|false Class name of loaded class; false if $throwExceptions
     * if false and no class found
     * @throws Kansas_PluginLoader_NotFoundException if class not found
     */
    public function load(string $name, bool $throwExceptions = true) {
        require_once 'Kansas/Autoloader.php';
        $name = $this->_formatName($name);
        if ($this->isLoaded($name)) {
            return $this->getClassName($name);
        }

        $registry  = array_reverse($this->prefixToPaths, true);
        $found     = false;
        $classFile = str_replace('\\', DIRECTORY_SEPARATOR, $name) . '.php';
        $incFile   = self::getIncludeFileCache();
        foreach ($registry as $prefix => $paths) {
            $className = $prefix . $name;

            if (class_exists($className, false)) {
                $found = true;
                break;
            }

            $paths     = array_reverse($paths, true);

            foreach ($paths as $path) {
                $loadFile = $path . $classFile;
                if (Autoloader::isReadable($loadFile)) {
                    include_once $loadFile;
                    if (class_exists($className, false)) {
                        if (null !== $incFile) {
                            self::_appendIncFile($loadFile);
                        }
                        $found = true;
                        break 2;
                    }
                }
            }
        }

        if (!$found) {
            if (!$throwExceptions) {
                return false;
            }
            require_once 'Kansas/Loader/NotFoundException.php';
            throw new NotFoundException($name, $registry);
        }

        $this->loadedPlugins[$name]     = $className;
        return $className;
    }

    /**
     * Set path to class file cache
     *
     * Specify a path to a file that will add include_once statements for each
     * plugin class loaded. This is an opt-in feature for performance purposes.
     *
     * @param  string $file
     * @return void
     * @throws System_IO_IOException if file is not writeable or path does not exist
     */
    public static function setIncludeFileCache(string $file) : void {
        if (null === $file) {
            self::$includeFileCache = null;
            return;
        }
        if(!file_exists($file) && 
           !file_exists(dirname($file))) {
            require_once 'System/IO/IOException.php';
            throw new IOException('Specified file does not exist and directory does not exist (' . $file . ')');
        }
        if(file_exists($file) &&
           !is_writable($file)) {
            require_once 'System/IO/IOException.php';
            throw new IOException('Specified file is not writeable (' . $file . ')');
        }
        if(!file_exists($file) &&
           file_exists(dirname($file)) &&
           !is_writable(dirname($file))) {
            require_once 'System/IO/IOException.php';
            throw new IOException('Specified directory is not writeable (' . dirname($file) . ')');
        }

        self::$includeFileCache = $file;
    }

    /**
     * Retrieve class file cache path
     *
     * @return string|null
     */
    public static function getIncludeFileCache() {
        return self::$includeFileCache;
    }

    /**
     * Append an include_once statement to the class file cache
     *
     * @param  string $incFile
     * @return void
     */
    protected static function _appendIncFile($incFile) {
        if (!file_exists(self::$includeFileCache)) {
            $file = '<?php';
        } else {
            $file = file_get_contents(self::$includeFileCache);
        }
        if (!strstr($file, $incFile)) {
            $file .= "\ninclude_once '$incFile';";
            file_put_contents(self::$includeFileCache, $file);
        }
    }
}
