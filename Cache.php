<?php
/**
 * Zend Framework
 * @package    Zend_Cache
 * @version    $Id: Cache.php 23154 2010-10-18 17:41:06Z mabe $
 */

abstract class Kansas_Cache {

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
     * @var array available options
     */
    protected $_options = [];

    /**
     * Consts for clean() method
     */
    const CLEANING_MODE_ALL              = 'all';
    const CLEANING_MODE_OLD              = 'old';
    const CLEANING_MODE_MATCHING_TAG     = 'matchingTag';
    const CLEANING_MODE_NOT_MATCHING_TAG = 'notMatchingTag';
    const CLEANING_MODE_MATCHING_ANY_TAG = 'matchingAnyTag';

    /**
     * Constructor
     *
     * @param  array $options Associative array of options
     * @throws Zend_Cache_Exception
     * @return void
     */
    public function __construct(array $options = array()) {
      foreach($options as $name => $value)
        $this->setOption($name, $value);
    }
    
    /**
     * Factory
     *
     * @param mixed  $backend         backend name (string) or Zend_Cache_Backend_ object
     * @param array  $backendOptions  associative array of options for the corresponding backend constructor
     * @param boolean $customBackendNaming if true, the backend argument is used as a complete class name ; if false, the backend argument is used as the end of "Zend_Cache_Backend_[...]" class name
     * @param boolean $autoload if true, there will no require_once for backend and frontend (useful only for custom backends/frontends)
     * @throws Zend_Cache_Exception
     * @return Zend_Cache_Core|Zend_Cache_Frontend
     */
    public static function factory($backend, $backendOptions = array(), $customBackendNaming = false, $autoload = false) {
        if (is_string($backend))
          return self::_makeBackend($backend, $backendOptions, $customBackendNaming, $autoload);
        elseif ($backend instanceof Kansas_Cache_Interface)
          return $backend;
        else
          self::throwException('backend must be a backend name (string) or an object which implements Zend_Cache_Backend_Interface');
    }

    /**
     * Backend Constructor
     *
     * @param string  $backend
     * @param array   $backendOptions
     * @param boolean $customBackendNaming
     * @param boolean $autoload
     * @return Zend_Cache_Backend
     */
    public static function _makeBackend($backend, $backendOptions, $customBackendNaming = false, $autoload = false) {
        if (!$customBackendNaming)
          $backend  = self::_normalizeName($backend);
        // we use a custom backend
        if (!preg_match('~^[\w]+$~D', $backend))
          Zend_Cache::throwException("Invalid backend name [$backend]");
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
     * Throw an exception
     *
     * @param  string $msg  Message for the exception
     * @throws Zend_Cache_Exception
     */
    public static function throwException($msg, Exception $e = null)
    {
        require_once 'Zend/Cache/Exception.php';
        throw new Zend_Cache_Exception($msg, 0, $e);
    }

    /**
     * Normalize frontend and backend names to allow multiple words TitleCased
     *
     * @param  string $name  Name to normalize
     * @return string
     */
    protected static function _normalizeName($name)
    {
        $name = ucfirst(strtolower($name));
        $name = str_replace(array('-', '_', '.'), ' ', $name);
        $name = ucwords($name);
        $name = str_replace(' ', '', $name);
        if (stripos($name, 'ZendServer') === 0) {
            $name = 'ZendServer_' . substr($name, strlen('ZendServer'));
        }

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
    private static function _isReadable($filename)
    {
        if (!$fh = @fopen($filename, 'r', true)) {
            return false;
        }
        @fclose($fh);
        return true;
    }

    /**
     * Set the frontend directives
     *
     * @param  array $directives Assoc of directives
     * @throws Zend_Cache_Exception
     * @return void
     */
    public function setDirectives(array $directives) {
      while (list($name, $value) = each($directives)) {
        if (!is_string($name))
          Zend_Cache::throwException("Incorrect option name : $name");
        $name = strtolower($name);
        if (array_key_exists($name, $this->_directives))
          $this->_directives[$name] = $value;
      }
    }

    /**
     * Set an option
     *
     * @param  string $name
     * @param  mixed  $value
     * @throws Zend_Cache_Exception
     * @return void
     */
    public function setOption($name, $value) {
      if (!is_string($name))
        Zend_Cache::throwException("Incorrect option name : $name");
      $name = strtolower($name);
      if (array_key_exists($name, $this->_options))
        $this->_options[$name] = $value;
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

    /**
     * Determine system TMP directory and detect if we have read access
     *
     * inspired from Zend_File_Transfer_Adapter_Abstract
     *
     * @return string
     * @throws Zend_Cache_Exception if unable to determine directory
     */
    public function getTmpDir() {
        $tmpdir = array();
        foreach (array($_ENV, $_SERVER) as $tab) {
            foreach (array('TMPDIR', 'TEMP', 'TMP', 'windir', 'SystemRoot') as $key) {
                if (isset($tab[$key])) {
                    if (($key == 'windir') or ($key == 'SystemRoot')) {
                        $dir = realpath($tab[$key] . '\\temp');
                    } else {
                        $dir = realpath($tab[$key]);
                    }
                    if ($this->_isGoodTmpDir($dir)) {
                        return $dir;
                    }
                }
            }
        }
        $upload = ini_get('upload_tmp_dir');
        if ($upload) {
            $dir = realpath($upload);
            if ($this->_isGoodTmpDir($dir)) {
                return $dir;
            }
        }
        if (function_exists('sys_get_temp_dir')) {
            $dir = sys_get_temp_dir();
            if ($this->_isGoodTmpDir($dir)) {
                return $dir;
            }
        }
        // Attemp to detect by creating a temporary file
        $tempFile = tempnam(md5(uniqid(rand(), TRUE)), '');
        if ($tempFile) {
            $dir = realpath(dirname($tempFile));
            unlink($tempFile);
            if ($this->_isGoodTmpDir($dir)) {
                return $dir;
            }
        }
        if ($this->_isGoodTmpDir('/tmp')) {
            return '/tmp';
        }
        if ($this->_isGoodTmpDir('\\temp')) {
            return '\\temp';
        }
        Zend_Cache::throwException('Could not determine temp directory, please specify a cache_dir manually');
    }

    /**
     * Verify if the given temporary directory is readable and writable
     *
     * @param $dir temporary directory
     * @return boolean true if the directory is ok
     */
    protected function _isGoodTmpDir($dir) {
      return is_readable($dir) && is_writable($dir);
    }    

}