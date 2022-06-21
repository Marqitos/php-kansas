<?php
/**
 * Proporciona almacenamiento en cache mediante ficheros
 *
 * @package Kansas
 * @since v0.4
 */

namespace Kansas\Cache;

use System\ArgumentOutOfRangeException;
use System\IO\DirectoryNotFoundException;
use System\IO\IOException;
use System\NotSupportedException;
use Kansas\Cache;
use Kansas\Cache\ExtendedCacheInterface;
use Kansas\Environment;

require_once 'Kansas/Cache.php';
require_once 'Kansas/Cache/ExtendedCacheInterface.php';
require_once 'Kansas/Environment.php';

/**
 * Zend Framework
 * @package    Zend_Cache
 * @subpackage Zend_Cache_Backend
 * @version    $Id: File.php 21636 2010-03-24 17:10:23Z mabe $
 */

class File extends Cache implements ExtendedCacheInterface {
    /**
     * Available options
     *
     * =====> (string) cacheDir :
     * - Directory where to put the cache files
     *
     * =====> (boolean) read_control :
     * - Enable / disable read control
     * - If enabled, a control key is embeded in cache file and this key is compared with the one
     * calculated after the reading.
     *
     * =====> (string) read_control_type :
     * - Type of read control (only if read control is enabled). Available values are :
     *   'md5' for a md5 hash control (best but slowest)
     *   'crc32' for a crc32 hash control (lightly less safe but faster, better choice)
     *   'adler32' for an adler32 hash control (excellent choice too, faster than crc32)
     *   'strlen' for a length only test (fastest)
     *
     * =====> (int) hashed_directory_level :
     * - Hashed directory level
     * - Set the hashed directory structure level. 0 means "no hashed directory
     * structure", 1 means "one level of directory", 2 means "two levels"...
     * This option can speed up the cache only when you have many thousands of
     * cache file. Only specific benchs can help you to choose the perfect value
     * for you. Maybe, 1 or 2 is a good start.
     *
     * =====> (int) hashed_directory_umask :
     * - Umask for hashed directory structure
     *
     * =====> (string) file_name_prefix :
     * - prefix for cache files
     * - be really carefull with this option because a too generic value in a system cache dir
     *   (like /tmp) can cause disasters when cleaning the cache
     *
     * =====> (int) cache_file_umask :
     * - Umask for cache files
     *
     * =====> (int) metatadatas_array_max_size :
     * - max size for the metadatas array (don't change this value unless you
     *   know what you are doing)
     *
     * @var array available options
     */
    protected $_cacheDir;
    protected $_fileNamePrefix;
    protected $_metadatasArrayMaxSize;
    protected $_hashedDirectoryUmask;
    protected $_cacheFileUmask;

    /**
     * Array of metadatas (each item is an associative array)
     *
     * @var array
     */
    protected $_metadatasArray = [];

    /// Miembros de System_Configurable_Interface
    public function getDefaultOptions(string $enviromentStatus) : array {
        global $environment;
        switch ($enviromentStatus) {
            case 'production':
            case 'development':
            case 'test':
                return [
                    'cache_dir' => $environment->getSpecialFolder(Environment::SF_CACHE),
                    'read_control' => true,
                    'read_control_type' => 'crc32',
                    'hashed_directory_level' => 0,
                    'hashed_directory_umask' => 0770,
                    'file_name_prefix' => 'cache',
                    'cache_file_umask' => 0600,
                    'metadatas_array_max_size' => 100
                ];
            default:
                require_once 'System/NotSupportedException.php';
                throw new NotSupportedException("Entorno no soportado [$enviromentStatus]");
        }
    }

    /**
     * Obtiene el directorio de cache
     *
     * @throws DirectoryNotFoundException
     * @throws IOException
     * @return string
     */
    protected function getCacheDir() {
        global $environment;
        if($this->_cacheDir == null) {
            $value = ($this->options['cache_dir'] !== null)
                ? $this->options['cache_dir']
                : $environment->getSpecialFolder(Environment::SF_TEMP);
            if (!is_dir($value)) {
                require_once 'System/IO/DirectoryNotFoundException.php';
                throw new DirectoryNotFoundException();
            }
            if (!is_writable($value)) {
                require_once 'System/IO/IOException.php';
                throw new IOException('No se puede escribir en el directorio cache');
            }
            $this->_cacheDir = rtrim(realpath($value), '\\/') . DIRECTORY_SEPARATOR; // add a trailing DIRECTORY_SEPARATOR if necessary
        }
        return $this->_cacheDir;
    }

    /**
     * Obtiene el prefijo de los archivos de cache
     *
     * @throws IOException
     * @return string
     */
    protected function getFileNamePrefix() {
        if($this->_fileNamePrefix == null) {
            if (isset($this->options['file_name_prefix'])) {
                if (!preg_match('~^[a-zA-Z0-9_]+$~D', $this->options['file_name_prefix'])) {
                    require_once 'System/IO/IOException.php';
                    throw new IOException('Prefijo incorrecto: debe usar solo [a-zA-Z0-9_]');
                }
                $this->_fileNamePrefix = $this->options['file_name_prefix'];
            } else {
                $this->_fileNamePrefix = "";
            }
        }
        return $this->_fileNamePrefix;
    }

    /**
     * Obtiene el maximo de metadatos que se pueden almacenar
     *
     * @throws NotSupportedException
     * @return string
     */
    protected function getMetadatasArrayMaxSize() {
        if($this->_metadatasArrayMaxSize == null) {
            if ($this->options['metadatas_array_max_size'] < 10) {
                require_once 'System/NotSupportedException.php';
                throw new NotSupportedException('Invalid metadatas_array_max_size, must be > 10');
            }
            $this->_metadatasArrayMaxSize = $this->options['metadatas_array_max_size'];
        }
        return $this->_metadatasArrayMaxSize;
    }

    /**
     * Obtiene Umask for hashed directory structure
     *
     * @return string
     */
    protected function getHashedDirectoryUmask() {
        if($this->_hashedDirectoryUmask == null) {
            $this->_hashedDirectoryUmask = is_string($this->options['hashed_directory_umask']) // See #ZF-4422
                ? octdec($this->options['hashed_directory_umask'])
                : $this->options['hashed_directory_umask'];
        }
        return $this->_hashedDirectoryUmask;
    }

    /**
     * Obtiene Umask for cache files
     *
     * @return int
     */
    protected function getCacheFileUmask() {
        if($this->_cacheFileUmask == null) {
            $this->_cacheFileUmask = is_string($this->options['cache_file_umask']) // See #ZF-4422
                ? octdec($this->options['cache_file_umask'])
                : $this->options['cache_file_umask'];
        }
        return $this->_cacheFileUmask;
    }


    /**
     * Test if a cache is available for the given id and (if yes) return it (false else)
     *
     * @param string $id cache id
     * @param boolean $doNotTestCacheValidity if set to true, the cache validity won't be tested
     * @return string|false cached datas
     */
    public function load($id, $doNotTestCacheValidity = false) {
        if (!($this->_test($id, $doNotTestCacheValidity))) { // The cache is not hit !
            return false;
        }
        $metadatas = $this->_getMetadatas($id);
        $file = $this->getFile($id);
        $data = $file->read();
        if ($this->options['read_control']) {
            $hashData = $this->_hash($data);
            if ($hashData != $metadatas['hash']) { // Problem detected by the read control !
                global $application;
                $application->log(E_USER_NOTICE, 'Kansas\Cache\File::load() / read_control : El hash almacenado y el calculado no coinciden: ' . $id);
                $this->remove($id);
                return false;
            }
        }
        return $data;
    }

    /**
     * Test if a cache is available or not (for the given id)
     *
     * @param string $id cache id
     * @return mixed false (a cache is not available) or "last modified" timestamp (int) of the available cache record
     */
    public function test($id) {
        clearstatcache();
        return $this->_test($id, false);
    }

    /**
     * Save some string datas into a cache record
     *
     * Note : $data is always "string" (serialization is done by the
     * core not by the backend)
     *
     * @param  string $data             Datas to cache
     * @param  string $id               Cache id
     * @param  array  $tags             Array of strings, the cache record will be tagged by each string entry
     * @param  int    $specificLifetime If != false, set a specific lifetime for this cache record (null => infinite lifetime)
     * @return boolean true if no problem
     */
    public function save($data, $id, array $tags = [], $specificLifetime = false) {
        clearstatcache();
        $file = $this->getFile($id);
        $hash = $this->options['read_control']
            ? $this->_hash($data)
            : '';
        $metadatas = [
            'hash' => $hash,
            'mtime' => time(),
            'expire' => $this->_expireTime($this->getLifetime($specificLifetime)),
            'tags' => $tags
        ];
        $res = $this->_setMetadatas($id, $metadatas);
        if (!$res) {
            global $application;
            $application->log(E_USER_NOTICE, 'Kansas\Cache\File::save() / error guardando metadatos');
            return false;
        }
        $result = $file->write($data, 'c', $this->getCacheFileUmask());
        return ($result !== false);
    }

    /**
     * Remove a cache record
     *
     * @param  string $id cache id
     * @return boolean true if no problem
     */
    public function remove($id) {
        $fileName = $this->getFileName($id, $path);
        $boolRemove   = $this->_remove($path . $fileName);
        $boolMetadata = $this->_delMetadatas($id);
        return $boolMetadata && $boolRemove;
    }

    /**
     * Clean some cache records
     *
     * Available modes are :
     * 'all' (default)  => remove all cache entries ($tags is not used)
     * 'old'            => remove too old cache entries ($tags is not used)
     * 'matchingTag'    => remove cache entries matching all given tags
     *                     ($tags can be an array of strings or a single string)
     * 'notMatchingTag' => remove cache entries not matching one of the given tags
     *                     ($tags can be an array of strings or a single string)
     * 'matchingAnyTag' => remove cache entries matching any given tags
     *                     ($tags can be an array of strings or a single string)
     *
     * @param string $mode clean mode
     * @param tags array $tags array of tags
     * @return boolean true if no problem
     */
    public function clean($mode = CacheInterface::CLEANING_MODE_ALL, array $tags = []) {
        // We use this protected method to hide the recursive stuff
        clearstatcache();
        return $this->_clean($this->getCacheDir(), $mode, $tags);
    }

    /**
     * Return an array of stored cache ids
     *
     * @return array array of stored cache ids (string)
     */
    public function getIds() {
        return $this->_get($this->getCacheDir(), 'ids', []);
    }

    /**
     * Return an array of stored tags
     *
     * @return array array of stored tags (string)
     */
    public function getTags() {
        return $this->_get($this->getCacheDir(), 'tags', []);
    }

    /**
     * Return an array of stored cache ids which match given tags
     *
     * In case of multiple tags, a logical AND is made between tags
     *
     * @param array $tags array of tags
     * @return array array of matching cache ids (string)
     */
    public function getIdsMatchingTags(array $tags = []) {
        return $this->_get($this->getCacheDir(), 'matching', $tags);
    }

    /**
     * Return an array of stored cache ids which don't match given tags
     *
     * In case of multiple tags, a logical OR is made between tags
     *
     * @param array $tags array of tags
     * @return array array of not matching cache ids (string)
     */
    public function getIdsNotMatchingTags(array $tags = []) {
        return $this->_get($this->getCacheDir(), 'notMatching', $tags);
    }

    /**
     * Return an array of stored cache ids which match any given tags
     *
     * In case of multiple tags, a logical AND is made between tags
     *
     * @param array $tags array of tags
     * @return array array of any matching cache ids (string)
     */
    public function getIdsMatchingAnyTags(array $tags = []) {
        return $this->_get($this->getCacheDir(), 'matchingAny', $tags);
    }

    /**
     * Return the filling percentage of the backend storage
     *
     * @throws IOException
     * @return int integer between 0 and 100
     */
    public function getFillingPercentage() {
        $free = disk_free_space($this->getCacheDir());
        $total = disk_total_space($this->getCacheDir());
        if ($total == 0) {
            require_once 'System/IO/IOException.php';
            throw new IOException('No se obtener el espacio libre en disco');
        }
        if ($free >= $total) {
            return 100;
        }
        return ((int) (100. * ($total - $free) / $total));
    }

    /**
     * Return an array of metadatas for the given cache id
     *
     * The array must include these keys :
     * - expire : the expire timestamp
     * - tags : a string array of tags
     * - mtime : timestamp of last modification time
     *
     * @param string $id cache id
     * @return array array of metadatas (false if the cache id is not found)
     */
    public function getMetadatas($id) {
        $metadatas = $this->_getMetadatas($id);
        if (!$metadatas || 
            time() > $metadatas['expire']) {
            return false;
        }
        return [
            'expire' => $metadatas['expire'],
            'tags' => $metadatas['tags'],
            'mtime' => $metadatas['mtime']
        ];
    }

    /**
     * Give (if possible) an extra lifetime to the given cache id
     *
     * @param string $id cache id
     * @param int $extraLifetime
     * @return boolean true if ok
     */
    public function touch($id, $extraLifetime) {
        $metadatas = $this->_getMetadatas($id);
        if (!$metadatas || 
            time() > $metadatas['expire']) {
            return false;
        }
        $metadatas['mtime'] = time();
        $metadatas['expire'] = $metadatas['expire'] + $extraLifetime;
        $res = $this->_setMetadatas($id, $metadatas);
        return (!$res)
            ? false
            : true;
    }

    /**
     * Return an associative array of capabilities (booleans) of the backend
     *
     * The array must include these keys :
     * - automatic_cleaning (is automating cleaning necessary)
     * - tags (are tags supported)
     * - expired_read (is it possible to read expired cache records
     *                 (for doNotTestCacheValidity option for example))
     * - priority does the backend deal with priority when saving
     * - infinite_lifetime (is infinite lifetime can work with this backend)
     * - get_list (is it possible to get the list of cache ids and the complete list of tags)
     *
     * @return array associative of with capabilities
     */
    public function getCapabilities() {
      return [
        'automatic_cleaning' => true,
        'tags' => true,
        'expired_read' => true,
        'priority' => false,
        'infinite_lifetime' => true,
        'get_list' => true
      ];
    }

    /**
     * Get a metadatas record
     *
     * @param  string $id  Cache id
     * @return array|false Associative array of metadatas
     */
    protected function _getMetadatas($id) {
        if (isset($this->_metadatasArray[$id])) {
            return $this->_metadatasArray[$id];
        } else {
            $metadatas = $this->_loadMetadatas($id);
            if (!$metadatas) {
                return false;
            }
            $this->_setMetadatas($id, $metadatas, false);
            return $metadatas;
        }
    }

    /**
     * Set a metadatas record
     *
     * @param  string $id        Cache id
     * @param  array  $metadatas Associative array of metadatas
     * @param  boolean $save     optional pass false to disable saving to file
     * @return boolean True if no problem
     */
    protected function _setMetadatas($id, $metadatas, $save = true) {
        if (count($this->_metadatasArray) >= $this->getMetadatasArrayMaxSize()) {
            $n = (int) ($this->getMetadatasArrayMaxSize() / 10);
            $this->_metadatasArray = array_slice($this->_metadatasArray, $n);
        }
        if ($save) {
            $result = $this->_saveMetadatas($id, $metadatas);
            if (!$result) {
                return false;
            }
        }
        $this->_metadatasArray[$id] = $metadatas;
        return true;
    }

    /**
     * Drop a metadata record
     *
     * @param  string $id Cache id
     * @return boolean True if no problem
     */
    protected function _delMetadatas($id) {
        if (isset($this->_metadatasArray[$id])) {
            unset($this->_metadatasArray[$id]);
        }
        $file = $this->_metadatasFile($id);
        return $this->_remove($file);
    }

    /**
     * Clear the metadatas array
     *
     * @return void
     */
    protected function _cleanMetadatas() {
			$this->_metadatasArray = [];
    }

    /**
     * Load metadatas from disk
     *
     * @param  string $id Cache id
     * @return array|false Metadatas associative array
     */
    protected function _loadMetadatas($id) {
        global $environment;
        $file = $environment->getFile($this->_metadatasFile($id));
        $result = $file->read();
        return ($result === false)
            ? false
            : @unserialize($result);
    }

    /**
     * Save metadatas to disk
     *
     * @param  string $id        Cache id
     * @param  array  $metadatas Associative array
     * @return boolean True if no problem
     */
    protected function _saveMetadatas($id, $metadatas) {
        global $environment;
        $file = $environment->getFile($this->_metadatasFile($id));
        $result = $file->write(serialize($metadatas), 'c', $this->getCacheFileUmask());
        return ($result !== false);
}

    /**
     * Make and return a file name (with path) for metadatas
     *
     * @param  string $id Cache id
     * @return string Metadatas file name (with path)
     */
    protected function _metadatasFile($id) {
        $path = null;
        $filename = $this->getFilename($id . '.metadatas', $path);
		return $path . $filename;
    }

    /**
     * Check if the given filename is a metadatas one
     *
     * @param  string $fileName File name
     * @return boolean True if it's a metadatas one
     */
    protected function _isMetadatasFile($fileName) {
        $id = $this->_fileNameToId($fileName);
        return (substr($id, strlen($id) - 10) == '.metadatas');
    }

    /**
     * Remove a file
     *
     * If we can't remove the file (because of locks or any problem), we will touch
     * the file to invalidate it
     *
     * @param  string $file Complete file path
     * @return boolean True if ok
     */
    protected function _remove($file) {
			if (!is_file($file)) {
				return false;
            }
			if (!@unlink($file)) { // we can't remove the file (because of locks or any problem)
				global $application;
				$application->log(E_USER_NOTICE, 'Kansas\Cache\File::_remove() : no podemos eliminar ' .$file);
				return false;
			}
			return true;
    }

    /**
     * Clean some cache records (protected method used for recursive stuff)
     *
     * Available modes are :
     * Kansas_Cache::CLEANING_MODE_ALL (default)    => remove all cache entries ($tags is not used)
     * Kansas_Cache::CLEANING_MODE_OLD              => remove too old cache entries ($tags is not used)
     * Kansas_Cache::CLEANING_MODE_MATCHING_TAG     => remove cache entries matching all given tags
     *                                               ($tags can be an array of strings or a single string)
     * Kansas_Cache::CLEANING_MODE_NOT_MATCHING_TAG => remove cache entries not {matching one of the given tags}
     *                                               ($tags can be an array of strings or a single string)
     * Kansas_Cache::CLEANING_MODE_MATCHING_ANY_TAG => remove cache entries matching any given tags
     *                                               ($tags can be an array of strings or a single string)
     *
     * @param  string $dir  Directory to clean
     * @param  string $mode Clean mode
     * @param  array  $tags Array of tags
     * @throws ArgumentOutOfRangeException
     * @return boolean True if no problem
     */
    protected function _clean($dir, $mode = CacheInterface::CLEANING_MODE_ALL, $tags = []) {
        global $application;
        if($logger = $application->hasPlugin('Logger')) {
            $message = "Eliminando cache ($mode): " . implode(', ', $tags);
            $logger->debug($message);
        }
        if (!is_dir($dir)) {
            return false;
        }
        $result = true;
        $glob = glob($dir . $this->getFileNamePrefix() . '*');
        if ($glob === false) { // On some systems it is impossible to distinguish between empty match and an error.
            return true;
        }
        foreach ($glob as $file)  {
            if (is_file($file)) {
                $fileName = basename($file);
                if ($this->_isMetadatasFile($fileName) && $mode != CacheInterface::CLEANING_MODE_ALL) { // in CLEANING_MODE_ALL, we drop anything, even remainings old metadatas files
                    continue;
                }
                $id = $this->_fileNameToId($fileName);
                $metadatas = $this->_getMetadatas($id);
                if ($metadatas === FALSE) {
                    $metadatas = array('expire' => 1, 'tags' => []);
                }
                switch ($mode) {
                    case CacheInterface::CLEANING_MODE_ALL:
                        $res = $this->remove($id);
                        if (!$res) { // in this case only, we accept a problem with the metadatas file drop
                            $res = $this->_remove($file);
                        }
                        $result = $result && $res;
                        break;
                    case CacheInterface::CLEANING_MODE_OLD:
                        if (time() > $metadatas['expire']) {
                            $result = $this->remove($id) && $result;
                        }
                        break;
                    case CacheInterface::CLEANING_MODE_MATCHING_TAG:
                        $matching = true;
                        foreach ($tags as $tag) {
                            if (!in_array($tag, $metadatas['tags'])) {
                                $matching = false;
                                break;
                            }
                        }
                        if ($matching) {
                            $result = $this->remove($id) && $result;
                        }
                        break;
                    case CacheInterface::CLEANING_MODE_NOT_MATCHING_TAG:
                        $matching = false;
                        foreach ($tags as $tag) {
                            if (in_array($tag, $metadatas['tags'])) {
                                $matching = true;
                                break;
                            }
                        }
                        if (!$matching) {
                            $result = $this->remove($id) && $result;
                        }
                        break;
                    case CacheInterface::CLEANING_MODE_MATCHING_ANY_TAG:
                        $matching = false;
                        foreach ($tags as $tag) {
                            if (in_array($tag, $metadatas['tags'])) {
                                $matching = true;
                                break;
                            }
                        }
                        if ($matching) {
                            $result = $this->remove($id) && $result;
                        }
                        break;
                    default:
                        require_once 'System/ArgumentOutOfRangeException.php';
                        throw new ArgumentOutOfRangeException('Invalid mode for clean() method');
                        break;
                }
            }
            if ((is_dir($file)) && ($this->options['hashed_directory_level']>0)) {
                // Recursive call
                $result = $this->_clean($file . DIRECTORY_SEPARATOR, $mode, $tags) && $result;
                if ($mode=='all') { // if mode=='all', we try to drop the structure too
                    @rmdir($file);
                }
            }
        }
        return $result;
    }

    protected function _get($dir, $mode, $tags = []) {
        if (!is_dir($dir)) {
            return false;
        }
        $glob = glob($dir . $this->getFileNamePrefix() . '*.metadatas');
        if ($glob === false) { // On some systems it is impossible to distinguish between empty match and an error.
            return [];
        }
        $result = [];
        foreach ($glob as $file)  {
            if (is_file($file)) {
                $fileName = basename($file);
                $id = $this->_fileNameToId($fileName);
                $metadatas = $this->_getMetadatas($id);
                if ($metadatas === FALSE) {
                    continue;
                }
                if (time() > $metadatas['expire']) {
                    continue;
                }
                switch ($mode) {
                    case 'ids':
                        $result[] = $id;
                        break;
                    case 'tags':
                        $result = array_unique(array_merge($result, $metadatas['tags']));
                        break;
                    case 'matching':
                        $matching = true;
                        foreach ($tags as $tag) {
                            if (!in_array($tag, $metadatas['tags'])) {
                                $matching = false;
                                break;
                            }
                        }
                        if ($matching) {
                            $result[] = $id;
                        }
                        break;
                    case 'notMatching':
                        $matching = false;
                        foreach ($tags as $tag) {
                            if (in_array($tag, $metadatas['tags'])) {
                                $matching = true;
                                break;
                            }
                        }
                        if (!$matching) {
                            $result[] = $id;
                        }
                        break;
                    case 'matchingAny':
                        $matching = false;
                        foreach ($tags as $tag) {
                            if (in_array($tag, $metadatas['tags'])) {
                                $matching = true;
                                break;
                            }
                        }
                        if ($matching) {
                            $result[] = $id;
                        }
                        break;
                    default:
                    require_once 'System/ArgumentOutOfRangeException.php';
                        throw new ArgumentOutOfRangeException('Invalid mode for _get() method');
                        break;
                }
            }
            if ((is_dir($file)) && ($this->options['hashed_directory_level']>0)) {
                // Recursive call
                $recursiveRs =  $this->_get($file . DIRECTORY_SEPARATOR, $mode, $tags);
                if ($recursiveRs === false) {
                  global $application;
                  $application->log(E_USER_NOTICE, 'Kansas\Cache\File::_get() / recursive call : can\'t list entries of "'.$file.'"');
                } else {
                  $result = array_unique(array_merge($result, $recursiveRs));
                }
            }
        }
        return array_unique($result);
    }

    /**
     * Compute & return the expire time
     *
     * @return int expire time (unix timestamp)
     */
    protected function _expireTime($lifetime) {
        if ($lifetime === null) {
            return 9999999999;
        }
        return time() + $lifetime;
    }

    /**
     * Make a control key with the string containing datas
     *
     * @param  string $data        Data
     * @throws ArgumentOutOfRangeException
     * @return string Control key
     */
    protected function _hash($data) {
        switch ($this->options['read_control_type']) {
        case 'md5':
            return md5($data);
        case 'crc32':
            return crc32($data);
        case 'strlen':
            return strlen($data);
        case 'adler32':
            return hash('adler32', $data);
        default:
            require_once 'System/ArgumentOutOfRangeException.php';
            throw new ArgumentOutOfRangeException("Incorrect hash function : " . $this->options['read_control_type']);
        }
    }

    /**
     * Transform a cache id into a file name and return it
     *
     * @param  string $id Cache id
     * @return string File name
     */
    protected function getFilename($id, &$path) {
        $path = $this->getPath($id);
        return $this->getFileNamePrefix() . '-' . $id;
    }

    /**
     * Devuelve un archivo
     *
     * @param  string $id Cache id
     * @return System\IO\File File name (with path)
     */
    protected function getFile($id) {
        global $environment;
        $fileName = $this->getFileName($id, $path);
        return $environment->getFile($path . $fileName);
    }

    /**
     * Return the complete directory path of a filename (including hashedDirectoryStructure)
     *
     * @param  string $id Cache id
     * @param  boolean $parts if true, returns array of directory parts instead of single string
     * @return string Complete directory path
     */
    protected function getPath($id, $parts = false) {
        $partsArray = [];
        $root = $this->getCacheDir();
        if ($this->options['hashed_directory_level'] > 0) {
            $hash = hash('adler32', $id);
            for ($i=0 ; $i < $this->options['hashed_directory_level'] ; $i++) {
                $root = $root . $this->getFileNamePrefix() . '--' . substr($hash, 0, $i + 1) . DIRECTORY_SEPARATOR;
                $partsArray[] = $root;
            }
            if (!is_writable($path)) { // maybe, we just have to build the directory structure
                $this->_recursiveMkdirAndChmod($partsArray);
            }
            if (!is_writable($path)) {
                return $root;
            }
        }
        return $parts
            ? $partsArray
            : $root;
    }

    /**
     * Make the directory structure for the given id
     *
     * @param string $id cache id
     * @return boolean true
     */
    protected function _recursiveMkdirAndChmod(array $partsArray) {
        foreach ($partsArray as $part) {
            if (!is_dir($part)) {
                @mkdir($part, $this->getHashedDirectoryUmask());
                @chmod($part, $this->getHashedDirectoryUmask()); // see #ZF-320 (this line is required in some configurations)
            }
        }
        return true;
    }

    /**
     * Test if the given cache id is available (and still valid as a cache record)
     *
     * @param  string  $id                     Cache id
     * @param  boolean $doNotTestCacheValidity If set to true, the cache validity won't be tested
     * @return boolean|mixed false (a cache is not available) or "last modified" timestamp (int) of the available cache record
     */
    protected function _test($id, $doNotTestCacheValidity) {
        $metadatas = $this->_getMetadatas($id);
        if (!$metadatas) {
            return false;
        }
        if ($doNotTestCacheValidity || (time() <= $metadatas['expire'])) {
            return $metadatas['mtime'];
        }
        return false;
    }

    /**
     * Transform a file name into cache id and return it
     *
     * @param  string $fileName File name
     * @return string Cache id
     */
    protected function _fileNameToId($fileName) {
        return preg_replace('~^' . $this->getFileNamePrefix() . '-(.*)$~', '$1', $fileName);
    }

}