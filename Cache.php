<?php
require_once 'System/Configurable/Abstract.php';
/**
 * Zend Framework
 * @package    Zend_Cache
 * @version    $Id: Cache.php 23154 2010-10-18 17:41:06Z mabe $
 */

abstract class Kansas_Cache
  extends System_Configurable_Abstract {

    /**
     * =====> (int) lifetime :
     * - Cache lifetime (in seconds)
     * - If null, the cache is valid forever
     *
     * =====> (int) logging :
     * - if set to true, a logging is activated throw Zend_Log
     *
     * @var array Frontend or Core directives
     */
    protected $_directives = array(
        'lifetime' => 3600,
        'logging'  => false
    );

    /**
     * Consts for clean() method
     */
    const CLEANING_MODE_ALL              = 'all';
    const CLEANING_MODE_OLD              = 'old';
    const CLEANING_MODE_MATCHING_TAG     = 'matchingTag';
    const CLEANING_MODE_NOT_MATCHING_TAG = 'notMatchingTag';
    const CLEANING_MODE_MATCHING_ANY_TAG = 'matchingAnyTag';

    
    /**
     * Factory
     *
     * @param mixed  $backend         backend name (string) or Kansas_Cache_ object
     * @param array  $backendOptions  associative array of options for the corresponding backend constructor
     * @param boolean $customBackendNaming if true, the backend argument is used as a complete class name ; if false, the backend argument is used as the end of "Zend_Cache_Backend_[...]" class name
     * @param boolean $autoload if true, there will no require_once for backend and frontend (useful only for custom backends/frontends)
     * @throws System_ArgumentOutOfRangeException
     * @return Kansas_Cache_Interface
     */
    public static function factory($backend, array $backendOptions = [], $customBackendNaming = false, $autoload = false) {
			if (is_string($backend))
				return self::_makeBackend($backend, $backendOptions, $customBackendNaming, $autoload);
			elseif ($backend instanceof Kansas_Cache_Interface) {
				return $backend;
      }
			else {
				require_once 'System/ArgumentOutOfRange.php';
				throw new System_ArgumentOutOfRangeException('backend must be a backend name (string) or an object which implements Kansas_Cache_Interface');
			}
    }

    /**
     * Backend Constructor
     *
     * @param string  $backend
     * @param array   $backendOptions
     * @param boolean $customBackendNaming
     * @param boolean $autoload
     * @throws System_ArgumentOutOfRangeException		 
     * @return Kansas_Cache_Backend
     */
    public static function _makeBackend($backend, array $backendOptions, $customBackendNaming = false, $autoload = false) {
        if (!$customBackendNaming)
          $backend  = self::_normalizeName($backend);
        // we use a custom backend
        if (!preg_match('~^[\w]+$~D', $backend)) {
					require_once 'System/ArgumentOutOfRange.php';
					throw new System_ArgumentOutOfRangeException("Invalid backend name [$backend]");
				}
        if (!$customBackendNaming) // we use this boolean to avoid an API break
          $backendClass = 'Kansas_Cache_' . $backend;
        else
          $backendClass = $backend;
        if (!$autoload) {
          $file = str_replace('_', DIRECTORY_SEPARATOR, $backendClass) . '.php';
          if (!(self::_isReadable($file))) {
            self::throwException("file $file not found in include_path");
          }
          require_once $file;
        }
        return new $backendClass($backendOptions);
    }

    /**
     * Normalize frontend and backend names to allow multiple words TitleCased
     *
     * @param  string $name  Name to normalize
     * @return string
     */
    protected static function _normalizeName($name) {
			$name = strtolower($name);
			$name = str_replace(['-', '_', '.'], ' ', $name);
			$name = ucwords($name);
			$name = str_replace(' ', '', $name);
			if (stripos($name, 'ZendServer') === 0)
				$name = 'ZendServer_' . substr($name, strlen('ZendServer'));

			return $name;
    }

    /**
     * Returns TRUE if the $filename is readable, or FALSE otherwise.
     * This function uses the PHP include_path, where PHP's is_readable()
     * does not.
     *
     * Note : this method comes from Zend_Loader (see #ZF-2891 for details)
     *
     * @param string   $filename
     * @return boolean
     */
    private static function _isReadable($filename) {
			if (!$fh = @fopen($filename, 'r', true))
				return false;
			@fclose($fh);
			return true;
    }

    /**
     * Set the frontend directives
     *
     * @param  array $directives Assoc of directives
     * @throws System_ArgumentOutOfRangeException
     * @return void
     */
    public function setDirectives(array $directives) {
      while (list($name, $value) = each($directives)) {
        if (!is_string($name)) {
					require_once 'System/ArgumentOutOfRange.php';
					throw new System_ArgumentOutOfRangeException("Incorrect option name : $name");
				}
        $name = strtolower($name);
        if (array_key_exists($name, $this->_directives))
          $this->_directives[$name] = $value;
      }
    }

    /**
     * Get the life time
     *
     * if $specificLifetime is not false, the given specific life time is used
     * else, the global lifetime is used
     *
     * @param  int $specificLifetime
     * @return int Cache life time
     */
    public function getLifetime($specificLifetime) {
      return ($specificLifetime === false) ? $this->_directives['lifetime']: $specificLifetime;
    }

}