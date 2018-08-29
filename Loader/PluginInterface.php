<?php
/**
 * @package    Zend_Loader
 * @subpackage PluginLoader
 */

 namespace Kansas\Loader;

/**
 * Plugin class loader interface
 */
interface PluginInterface {
    /**
     * Add prefixed paths to the registry of paths
     *
     * @param string $prefix
     * @param string $path
     * @return PluginInterface
     */
    public function addPrefixPath($prefix, $path);

    /**
     * Remove a prefix (or prefixed-path) from the registry
     *
     * @param string $prefix
     * @param string $path OPTIONAL
     * @return PluginInterface
     */
    public function removePrefixPath($prefix, $path = null);

    /**
     * Whether or not a Helper by a specific name
     *
     * @param string $name
     * @return PluginInterface
     */
    public function isLoaded($name);

    /**
     * Return full class name for a named helper
     *
     * @param string $name
     * @return string
     */
    public function getClassName($name);

    /**
     * Load a helper via the name provided
     *
     * @param string $name
     * @return string
     */
    public function load($name);
}
