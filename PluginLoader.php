<?php
/**
 * Zend Framework
 *
 * @package    Zend_Loader
 * @subpackage PluginLoader
 * @version    $Id: PluginLoader.php 22603 2010-07-17 00:02:10Z ramon $
 */

require_once 'Kansas/PluginLoader/Interface.php';

/** Zend_Loader */
require_once 'Kansas/Loader.php';

/**
 * Generic plugin class loader
 */
class Kansas_PluginLoader implements Kansas_PluginLoader_Interface {
    /**
     * Class map cache file
     * @var string
     */
    protected static $_includeFileCache;

    /**
     * Instance loaded plugin paths
     *
     * @var array
     */
    protected $_loadedPluginPaths = array();

    /**
     * Instance loaded plugins
     *
     * @var array
     */
    protected $_loadedPlugins = array();

    /**
     * Instance registry property
     *
     * @var array
     */
    protected $_prefixToPaths = array();

    /**
     * Statically loaded plugin path mappings
     *
     * @var array
     */
    protected static $_staticLoadedPluginPaths = array();

    /**
     * Statically loaded plugins
     *
     * @var array
     */
    protected static $_staticLoadedPlugins = array();

    /**
     * Static registry property
     *
     * @var array
     */
    protected static $_staticPrefixToPaths = array();

    /**
     * Constructor
     *
     * @param array $prefixToPaths
     * @param string $staticRegistryName OPTIONAL
     */
    public function __construct(Array $prefixToPaths = array()) {
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
    protected function _formatPrefix($prefix)
    {
        if($prefix == "") {
            return $prefix;
        }

        $last = strlen($prefix) - 1;
        if ($prefix{$last} == '\\') {
            return $prefix;
        }

        return rtrim($prefix, '_') . '_';
    }

    /**
     * Add prefixed paths to the registry of paths
     *
     * @param string $prefix
     * @param string $path
     * @return System_ArgumentOutOfRangeException
     */
    public function addPrefixPath($prefix, $path) {
        if (!is_string($prefix) || !is_string($path)) {
            require_once 'System/ArgumentOutOfRangeException.php';
            throw new System_ArgumentOutOfRangeException('prefix & path', 'PluginLoader::addPrefixPath() method only takes strings for prefix and path.');
        }

        $prefix = $this->_formatPrefix($prefix);
        $path   = rtrim($path, '/\\') . '/';

				if (!isset($this->_prefixToPaths[$prefix])) {
						$this->_prefixToPaths[$prefix] = array();
				}
				if (!in_array($path, $this->_prefixToPaths[$prefix])) {
						$this->_prefixToPaths[$prefix][] = $path;
				}
        return $this;
    }

    /**
     * Get path stack
     *
     * @param  string $prefix
     * @return false|array False if prefix does not exist, array otherwise
     */
    public function getPaths($prefix = null)
    {
        if ((null !== $prefix) && is_string($prefix)) {
            $prefix = $this->_formatPrefix($prefix);

            if (isset($this->_prefixToPaths[$prefix])) {
                return $this->_prefixToPaths[$prefix];
            }

            return false;
        }

        return $this->_prefixToPaths;
    }

    /**
     * Clear path stack
     *
     * @param  string $prefix
     * @return bool False only if $prefix does not exist
     */
    public function clearPaths($prefix = null)
    {
        if ((null !== $prefix) && is_string($prefix)) {
            $prefix = $this->_formatPrefix($prefix);

            if (isset($this->_prefixToPaths[$prefix])) {
                unset($this->_prefixToPaths[$prefix]);
                return true;
            }

            return false;
        }

				$this->_prefixToPaths = array();

        return true;
    }

    /**
     * Remove a prefix (or prefixed-path) from the registry
     *
     * @param string $prefix
     * @param string $path OPTIONAL
     * @return System_Collections_KeyNotFoundException
     */
    public function removePrefixPath($prefix, $path = null) {
			$prefix = $this->_formatPrefix($prefix);
			$registry =& $this->_prefixToPaths;

			if (!isset($registry[$prefix])) {
				require_once 'System/Collections/KeyNotFoundException.php';
				throw new System_Collections_KeyNotFoundException('Prefix ' . $prefix . ' was not found in the PluginLoader.');
			}

			if ($path != null) {
				$pos = array_search($path, $registry[$prefix]);
				if (false === $pos) {
					require_once 'System/Collections/KeyNotFoundException.php';
					throw new System_Collections_KeyNotFoundException('Prefix ' . $prefix . ' / Path ' . $path . ' was not found in the PluginLoader.');
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
    protected function _formatName($name) {
        return ucfirst((string) $name);
    }

    /**
     * Whether or not a Plugin by a specific name is loaded
     *
     * @param string $name
     * @return boolean
     */
    public function isLoaded($name) {
			$name = $this->_formatName($name);
			return isset($this->_loadedPlugins[$name]);
    }

    /**
     * Return full class name for a named plugin
     *
     * @param string $name
     * @return string|false False if class not found, class name otherwise
     */
    public function getClassName($name) {
        $name = $this->_formatName($name);
        if (isset($this->_loadedPlugins[$name])) {
            return $this->_loadedPlugins[$name];
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
			$name = $this->_formatName($name);
			if (!empty($this->_loadedPluginPaths[$name])) {
				return $this->_loadedPluginPaths[$name];
			}

			if ($this->isLoaded($name)) {
				$class = $this->getClassName($name);
				$r     = new ReflectionClass($class);
				$path  = $r->getFileName();
				$this->_loadedPluginPaths[$name] = $path;
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
    public function load($name, $throwExceptions = true) {
			$name = $this->_formatName($name);
			if ($this->isLoaded($name)) {
				return $this->getClassName($name);
			}

			$registry  = array_reverse($this->_prefixToPaths, true);
			$found     = false;
			$classFile = str_replace('_', DIRECTORY_SEPARATOR, $name) . '.php';
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
					if (Kansas_Loader::isReadable($loadFile)) {
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
				if (!$throwExceptions)
					return false;

				require_once 'Kansas/PluginLoader/NotFoundException.php';
				throw new Kansas_PluginLoader_NotFoundException($name, $registry);
		  }

			$this->_loadedPlugins[$name]     = $className;
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
    public static function setIncludeFileCache($file) {
			if (null === $file) {
				self::$_includeFileCache = null;
				return;
			}

			if (!file_exists($file) && !file_exists(dirname($file))) {
				require_once 'System/IO/IOException.php';
				throw new System_IO_IOException('Specified file does not exist and/or directory does not exist (' . $file . ')');
			}
			if (file_exists($file) && !is_writable($file)) {
				require_once 'System/IO/IOException.php';
				throw new System_IO_IOException('Specified file is not writeable (' . $file . ')');
			}
			if (!file_exists($file) && file_exists(dirname($file)) && !is_writable(dirname($file))) {
				require_once 'System/IO/IOException.php';
				throw new System_IO_IOException('Specified file is not writeable (' . $file . ')');
			}

			self::$_includeFileCache = $file;
    }

    /**
     * Retrieve class file cache path
     *
     * @return string|null
     */
    public static function getIncludeFileCache() {
        return self::$_includeFileCache;
    }

    /**
     * Append an include_once statement to the class file cache
     *
     * @param  string $incFile
     * @return void
     */
    protected static function _appendIncFile($incFile)
    {
        if (!file_exists(self::$_includeFileCache)) {
            $file = '<?php';
        } else {
            $file = file_get_contents(self::$_includeFileCache);
        }
        if (!strstr($file, $incFile)) {
            $file .= "\ninclude_once '$incFile';";
            file_put_contents(self::$_includeFileCache, $file);
        }
    }
}
